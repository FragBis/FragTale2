<?php

namespace Console\Project\Configure;

use Console\Project\Configure;
use FragTale\DataCollection;
use FragTale\Service\Cli;
use FragTale\Constant\Setup\Database\Driver;
use FragTale\Constant\Setup\CorePath;
use Console\Setup\CliApplication\Locale;
use Console\Setup\CliApplication\Parameter;

/**
 *
 * @author Fabrice Dant
 *        
 */
class Environment extends Configure {
	protected function executeOnTop(): void {
		if ($this->isHelpInvoked ())
			return;

		$this->CliService->printInColor ( sprintf ( dgettext ( 'core', 'Entering project "%s" environments setup' ), $this->getProjectName () ), Cli::COLOR_YELLOW );
	}
	protected function executeOnConsole(): void {
		if ($this->isHelpInvoked ())
			return;

		$choices = [ 
				1 => dgettext ( 'core', 'Edit or create environment: define which database connector to use as default, choose your locale and enable debug mode.' ),
				2 => dgettext ( 'core', 'Bind a hostname to a project is not enough. You should also bind an environment to a hostname.' )
		];

		$projectHosts = [ ];
		foreach ( $this->getSuperServices ()->getConfigurationService ()->getHostsSettings () as $host => $projectName ) {
			if ($projectName === $this->getProjectName ())
				$projectHosts [$host] = $host;
		}
		$ProjectHosts = new DataCollection ( $projectHosts );

		$continue = true;
		while ( $continue ) {
			if ($Environments = $this->getProjectAppConfig ()->findByKey ( 'environments' )) {
				$EnvironmentSettings = $Environments->find ( function ($key, $Setting) {
					return $Setting instanceof DataCollection;
				} );
				switch ($this->promptToFindElementInCollection ( dgettext ( 'core', 'You have 2 action groups:' ), new DataCollection ( $choices ), null, true )) {
					case 1 :
						$this->setupEnvironmentSettings ( $EnvironmentSettings, $this->getProjectAppConfig ()->findByKey ( 'databases' ) );
						break;
					case 2 :
						$EnvironmentHosts = $this->getProjectAppConfig ()->findByKey ( 'environments' )->find ( function ($key, $host) {
							return is_string ( $host );
						} );
						$this->setupEnvironmentHosts ( $EnvironmentSettings, $EnvironmentHosts, $ProjectHosts );
						break;
				}
				$continue = $this->getSuperServices ()->getLocalizeService ()->meansYes ( $this->CliService->prompt ( dgettext ( 'core', 'Choose another action group? [Yn]' ), dgettext ( 'core', 'y {means yes}' ) ) );
			} else
				break;
		}
	}

	/**
	 *
	 * @param DataCollection $EnvironmentsSettings
	 * @param DataCollection $EnvironmentHosts
	 */
	protected function setupEnvironmentSettings(DataCollection $EnvironmentSettings, DataCollection $DatabasesSettings) {
		$LocalizeService = $this->getSuperServices ()->getLocalizeService ();
		$EnvConf = new DataCollection ( json_encode ( file_get_contents ( CorePath::PATTERN_ENVIRONMENT_SETUP_FILE ), true ) );
		if ($env = $this->promptToFindElementInCollection ( dgettext ( 'core', 'Select which environment to update (leave empty to enter new one)' ), $EnvironmentSettings, null, true ))
			$EnvConf = $EnvironmentSettings->findByKey ( $env );
		else {
			while ( ! $env ) {
				$env = $this->CliService->prompt ( dgettext ( 'core', 'Enter new environment name' ) );
			}
		}
		$this->CliService->print ( $env );

		# Define the default SQL connector ID
		$envkey = 'default_sql_connector_id';
		$default_sql_connector_id = $EnvConf->findByKey ( $envkey );
		// Get SQL connectors
		$SqlConnectors = $DatabasesSettings->find ( function ($key, $DbSet) {
			if (! $DbSet instanceof DataCollection)
				return false;
			return $DbSet->findByKey ( 'driver' ) !== Driver::MONGO;
		} );
		$defaultPosition = $SqlConnectors->position ( $default_sql_connector_id );
		if (is_int ( $defaultPosition ))
			$defaultPosition ++;
		$newValue = $this->promptToFindElementInCollection ( sprintf ( dgettext ( 'core', 'Select one of these SQL credentials to set as default for environment "%s"' ), $env ), $SqlConnectors, $defaultPosition, true );
		$this->CliService->print ( $newValue );
		$EnvConf->upsert ( $envkey, $newValue );

		# Define the default MongoDB connector ID
		$envkey = 'default_mongo_connector_id';
		$default_mongo_connector_id = $EnvConf->findByKey ( $envkey );
		// Get MongoDB connectors
		$NoSqlConnectors = $DatabasesSettings->find ( function ($key, $DbSet) {
			if (! $DbSet instanceof DataCollection)
				return false;
			return $DbSet->findByKey ( 'driver' ) === Driver::MONGO;
		} );
		$defaultPosition = $NoSqlConnectors->position ( $default_mongo_connector_id );
		if (is_int ( $defaultPosition ))
			$defaultPosition ++;
		$newValue = $this->promptToFindElementInCollection ( sprintf ( dgettext ( 'core', 'Select one of these Mongo credentials to set as default for environment "%s"' ), $env ), $NoSqlConnectors, $defaultPosition, true );
		$this->CliService->print ( $newValue );
		$EnvConf->upsert ( $envkey, $newValue );

		# Define locale & encoding
		(new Locale ())->setupLocale ( $EnvConf );

		# Set debug mode
		$defaultDebug = $EnvConf->findByKey ( 'debug' ) ? dgettext ( 'core', 'y {means yes}' ) : dgettext ( 'core', 'n {means no}' );
		$EnvConf->upsert ( 'debug', $LocalizeService->meansYes ( $this->CliService->prompt ( dgettext ( 'core', 'Activate debug mode? [yn]' ), $defaultDebug ) ) );

		# Set parameters
		(new Parameter ())->setupParameters ( $EnvConf->findByKey ( 'parameters' ) );

		# Finally, update or insert this conf
		$EnvironmentSettings->upsert ( $env, $EnvConf );
		$this->getProjectAppConfig ()->findByKey ( 'environments' )->upsert ( $env, $EnvConf );

		$this->executeOnBottom ();
	}

	/**
	 *
	 * @param DataCollection $EnvironmentSettings
	 * @param DataCollection $EnvironmentHosts
	 */
	protected function setupEnvironmentHosts(DataCollection $EnvironmentSettings, DataCollection $EnvironmentHosts, DataCollection $ProjectHosts) {
		$LocalizeService = $this->getSuperServices ()->getLocalizeService ();
		if (! $EnvironmentHosts->count ())
			$EnvironmentHosts = new DataCollection ( [ 
					'default' => ''
			] );

		// Checking fake hosts
		$EnvironmentHosts->forEach ( function ($host, $env) use ($EnvironmentHosts, $ProjectHosts, $LocalizeService) {
			if ($host === 'default')
				return;
			if ($ProjectHosts->getElementPosition ( $host ) === null) {
				$this->CliService->printWarning ( sprintf ( dgettext ( 'core', 'Host "%s" contained in these environments is not bound to this project (as declared in "hosts.json" conf file).' ), $host ) );
				if ($LocalizeService->meansYes ( $this->CliService->prompt ( dgettext ( 'core', 'Do you want to remove it? [Yn]' ), dgettext ( 'core', 'y {means yes}' ) ) )) {
					$EnvironmentHosts->delete ( $host );
					$this->getProjectAppConfig ()
						->findByKey ( 'environments' )
						->delete ( $host );
				}
			}
		} );

		$ListOfHosts = new DataCollection ();
		$EnvironmentHosts->forEach ( function ($host, $env) use ($ListOfHosts) {
			$ListOfHosts->upsert ( "$host ($env)", $host );
		} );
		$ProjectHosts->forEach ( function ($h, $host) use ($ListOfHosts) {
			if (! $ListOfHosts->getElementKey ( $host ))
				$ListOfHosts->upsert ( $host, $host );
		} );

		if (! ($host = $this->promptToFindElementInCollection ( dgettext ( 'core', 'Choose between one of these hosts ("default" is the environment used by default)' ), $ListOfHosts )))
			return;

		$this->CliService->print ( $host );

		if ($host) {
			$env = $EnvironmentHosts->findByKey ( $host );
			$defaultPosition = $EnvironmentSettings->position ( $env );
			if (is_int ( $defaultPosition ))
				$defaultPosition ++;
			if ($newEnv = $this->promptToFindElementInCollection ( dgettext ( 'core', 'Bind it to one of these environment' ), $EnvironmentSettings, $defaultPosition, true )) {
				$this->CliService->print ( $newEnv );
				$EnvironmentHosts->upsert ( $host, $newEnv );
				$this->getProjectAppConfig ()->findByKey ( 'environments' )->upsert ( $host, $newEnv );
			}
		}

		$this->executeOnBottom ();
	}
}