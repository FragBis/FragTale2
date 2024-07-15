<?php

namespace Console\Project\Configure\Parameter;

use Console\Project\Configure\Parameter;
use FragTale\DataCollection;
use FragTale\Service\Cli;

class SetMaintenance extends Parameter {
	protected function executeOnConsole(): void {
		if ($this->isHelpInvoked ())
			return;

		$StrService = $this->getSuperServices ()->getLocalizeService ();

		$this->CliService->printInColor ( sprintf ( dgettext ( 'core', 'Entering project "%s" maintenance parameter setup' ), $this->getProjectName () ), Cli::COLOR_YELLOW );

		$Parameters = null;
		$isForAllEnv = $StrService->meansYes ( $this->CliService->prompt ( dgettext ( 'core', 'Do you want to set maintenance parameter for all environments? [Yn]' ), dgettext ( 'core', 'y {means yes}' ) ) );
		if ($isForAllEnv) {
			$Parameters = $this->getProjectAppConfig ()->findByKey ( 'parameters' );
			if (! $Parameters instanceof DataCollection) {
				$Parameters = new DataCollection ();
				$this->getProjectAppConfig ()->upsert ( 'parameters', $Parameters );
			}
		} else {
			$EnvironmentSettings = $this->getProjectAppConfig ()->findByKey ( 'environments' );
			if (! $EnvironmentSettings instanceof DataCollection) {
				$this->CliService->printError ( sprintf ( dgettext ( 'core', 'There is no environment set yet in your %s file.' ), $this->getProjectAppConfig ()->getSource () ) );
				return;
			} else {
				$SelectableEnvironements = $EnvironmentSettings->find ( function ($ix, $Env) {
					return $Env instanceof DataCollection;
				} );
				$selectedEnvName = $this->promptToFindElementInCollection ( dgettext ( 'core', 'Choose the environment you want to set maintenance parameter:' ), $SelectableEnvironements, null, true );
				$SelectedEnv = $EnvironmentSettings->findByKey ( $selectedEnvName );
				if ($SelectedEnv instanceof DataCollection) {
					$Parameters = $SelectedEnv->findByKey ( 'parameters' );
					if (! $Parameters instanceof DataCollection) {
						$Parameters = new DataCollection ();
						$SelectedEnv->upsert ( 'parameters', $Parameters );
					}
					$this->CliService->print ( sprintf ( dgettext ( 'core', 'Selected environment: %s' ), $selectedEnvName ) );
				} else {
					$this->CliService->printError ( dgettext ( 'core', 'Invalid environment selection' ) );
					return;
				}
			}
		}

		if (! $Parameters) {
			$this->CliService->printError ( dgettext ( 'core', 'Unabled to initialize parameters.' ) );
			return;
		}

		// Prompt 0/1 to set maintenance
		$answer = ( int ) $StrService->meansYes ( $this->CliService->prompt ( sprintf ( dgettext ( 'core', 'Type 1 to set maintenance ON, type 0 to set maintenance OFF (actual value: %s):' ), $Parameters->findByKey ( 'maintenance' ) ) ) );
		if ($answer === 1) {
			$this->CliService->printWarning ( dgettext ( 'core', 'You have chosen to set your website maintenance ON!' ) );
			$Parameters->upsert ( 'maintenance', $answer );
		} elseif ($answer === 0) {
			$this->CliService->printSuccess ( dgettext ( 'core', 'You have chosen to set your website maintenance OFF' ) );
			$Parameters->upsert ( 'maintenance', $answer );
		} else
			$this->CliService->printError ( dgettext ( 'core', 'Invalid answer' ) );
	}
}