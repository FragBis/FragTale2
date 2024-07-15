<?php

namespace Console\Project\Configure;

use Console\Project\Configure;
use FragTale\DataCollection;
use FragTale\Constant\Setup\Database\Driver;
use FragTale\Service\Cli;

/**
 *
 * @author Fabrice Dant
 *        
 */
class Database extends Configure {
	/**
	 *
	 * {@inheritdoc}
	 * @see \Console\Project\Configure::executeOnTop()
	 */
	protected function executeOnTop(): void {
		if ($this->isHelpInvoked ())
			return;

		$this->CliService->printInColor ( sprintf ( dgettext ( 'core', 'Entering project "%s" database configuration:' ), $this->getProjectName () ), Cli::COLOR_YELLOW )
			->printInColor ( dgettext ( 'core', '**********************' ), Cli::COLOR_LCYAN )
			->printInColor ( dgettext ( 'core', "You'll have to choose one configuration among the list of connector IDs" ), Cli::COLOR_CYAN )
			->printInColor ( dgettext ( 'core', "Then, enter credentials step-by-step" ), Cli::COLOR_CYAN )
			->printInColor ( dgettext ( 'core', '**********************' ), Cli::COLOR_LCYAN );
	}

	/**
	 *
	 * {@inheritdoc}
	 * @see \Console\Project\Configure::executeOnConsole()
	 */
	protected function executeOnConsole(): void {
		if ($this->isHelpInvoked ())
			return;

		if (! ($Databases = $this->getProjectAppConfig ()->findByKey ( 'databases' )))
			$this->setProjectAppConfig ( true );
		if ($Databases = $this->getProjectAppConfig ()->findByKey ( 'databases' ))
			$this->setupDbConnectionsFromCollection ( $Databases );
		else
			$this->CliService->printError ( dgettext ( 'core', 'Could not find database configuration' ) );
	}

	/**
	 *
	 * @param DataCollection $DatabaseSettings
	 * @return self
	 */
	public function setupDbConnectionsFromCollection(DataCollection $DatabaseSettings): self {
		$namedSetting = '';
		$isNew = true;
		$NewDbConf = new DataCollection ();
		if ($nbConnectors = $DatabaseSettings->count ()) {
			$NewDbConf = $this->promptToFindElementInCollection ( sprintf ( dgettext ( 'core', 'Choose one available connection to modify (leave empty if you want to create new connection, Ctrl+C to quit): (total %d)' ), $nbConnectors ), $DatabaseSettings );
			if ($NewDbConf instanceof DataCollection) {
				$namedSetting = $NewDbConf->getKeyFromParentNode ();
				$isNew = false;
				$this->CliService->print ( dgettext ( 'core', 'Updating configuration:' ) . " $namedSetting" );
			} else
				$NewDbConf = new DataCollection ();
		} else if (! $this->getSuperServices ()->getLocalizeService ()->meansYes ( $this->CliService->prompt ( dgettext ( 'core', 'You have not setup a database yet. Do you want to configure new database connection? Y, N' ), dgettext ( 'core', 'y {means yes}' ) ) ))
			return $this;
		if ($isNew)
			$this->CliService->print ( dgettext ( 'core', 'Creating new database configuration:' ) );
		foreach ( [ 
				'name' => [ 
						'ask' => dgettext ( 'core', 'Give a name to your database configuration:' ),
						'default_response' => $namedSetting ? $namedSetting : null,
						'accept_blank' => false
				],
				'driver' => [ 
						'ask' => dgettext ( 'core', 'Type one of the following database drivers:' ) . ' ' . implode ( ', ', Driver::getConstants ()->getData ( true ) ),
						'default_response' => $NewDbConf->findByKey ( 'driver' ) ? $NewDbConf->findByKey ( 'driver' ) : Driver::MYSQL,
						'accept_blank' => false
				],
				'host' => [ 
						'ask' => dgettext ( 'core', 'Type server name or IP address of database host:' ),
						'default_response' => $NewDbConf->findByKey ( 'host' ) ? $NewDbConf->findByKey ( 'host' ) : 'localhost',
						'accept_blank' => false
				],
				'port' => [ 
						'ask' => dgettext ( 'core', 'Type port number (leave blank to use default port or to keep previous entry):' ),
						'default_response' => $NewDbConf->findByKey ( 'port' ) ? $NewDbConf->findByKey ( 'port' ) : null,
						'accept_blank' => true
				],
				'database' => [ 
						'ask' => dgettext ( 'core', 'Type database name:' ),
						'default_response' => $NewDbConf->findByKey ( 'database' ) ? $NewDbConf->findByKey ( 'database' ) : null,
						'accept_blank' => true
				],
				'user' => [ 
						'ask' => dgettext ( 'core', 'Type username:' ),
						'default_response' => $NewDbConf->findByKey ( 'user' ) ? $NewDbConf->findByKey ( 'user' ) : null,
						'accept_blank' => true
				],
				'password' => [ 
						'ask' => dgettext ( 'core', 'Type password:' ),
						'default_response' => $NewDbConf->findByKey ( 'password' ) ? $NewDbConf->findByKey ( 'password' ) : null,
						'accept_blank' => true
				],
				'charset' => [ 
						'ask' => dgettext ( 'core', 'Type charset (leave blank to use database default charset, most commonly "utf8"):' ),
						'default_response' => $NewDbConf->findByKey ( 'charset' ) ? $NewDbConf->findByKey ( 'charset' ) : null,
						'accept_blank' => true
				]
		] as $paramName => $promptParams ) {
			if ($paramName === 'name' && ! $isNew)
				continue;
			if (! $promptParams ['accept_blank'] || ($NewDbConf->findByKey ( 'driver' ) !== Driver::MONGO && in_array ( $paramName, [ 
					'user',
					'password'
			] ))) {
				$isDriverSupported = $response = false;
				while ( ! $response || ! $isDriverSupported ) {
					$response = $this->CliService->prompt ( $promptParams ['ask'], $promptParams ['default_response'] );
					$isDriverSupported = $paramName !== 'driver' || in_array ( $response, Driver::getConstants ()->getData ( true ) );
					if (! $response) {
						$this->CliService->printWarning ( dgettext ( 'core', 'This parameter is required and cannot be empty' ) );
						continue;
					}
					if ($paramName === 'driver' && ! in_array ( $response, Driver::getConstants ()->getData ( true ) ))
						$this->CliService->printWarning ( dgettext ( 'core', 'Unsupported driver:' ) . ' ' . $response );
					elseif ($paramName === 'name') {
						// Check if a name is already taken
						if ($DatabaseSettings->findByKey ( $response )) {
							$this->CliService->printWarning ( sprintf ( dgettext ( 'core', 'A database connection named "%s" already exists. Please enter another name.' ), $response ) );
							$response = false;
						} else
							$namedSetting = $response;
					}
				}
			} else
				$response = $this->CliService->prompt ( $promptParams ['ask'], $promptParams ['default_response'] );
			if ($paramName !== 'name')
				$NewDbConf->upsert ( $paramName, $response );
		}
		if ($this->getSuperServices ()->getLocalizeService ()->meansYes ( $this->CliService->prompt ( dgettext ( 'core', 'Confirm save: [Yn]' ), dgettext ( 'core', 'y {means yes}' ) ) )) {
			if (($DbSettings = $DatabaseSettings->findByKey ( $namedSetting )) && $DbSettings instanceof DataCollection && $DbSettings->count ())
				$this->CliService->print ( dgettext ( 'core', 'Replacing database settings named:' ) . " $namedSetting" );
			else {
				$DatabaseSettings->upsert ( $namedSetting, $NewDbConf );
				$this->CliService->print ( dgettext ( 'core', 'Adding new database settings named:' ) . " $namedSetting" );
			}
		}
		return $this;
	}
}