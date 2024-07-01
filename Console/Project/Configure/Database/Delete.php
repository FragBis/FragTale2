<?php

namespace Console\Project\Configure\Database;

use Console\Project\Configure\Database;
use FragTale\Service\Cli;

class Delete extends Database {
	protected function executeOnTop(): void {
		$this->CliService->printInColor ( sprintf ( dgettext ( 'core', 'Delete a database connector from project "%s"' ), $this->getProjectName () ), Cli::COLOR_YELLOW )
			->printInColor ( dgettext ( 'core', '**********************' ), Cli::COLOR_LCYAN )
			->printInColor ( dgettext ( 'core', "You'll have to choose one configuration among the list of connector IDs" ), Cli::COLOR_CYAN )
			->printInColor ( dgettext ( 'core', "This will definitely remove this configuration from your file" ), Cli::COLOR_CYAN )
			->printInColor ( dgettext ( 'core', 'CLI option:' ), Cli::COLOR_CYAN )
			->printInColor ( '	' . dgettext ( 'core', '"--project": The project name (if not passed, application will prompt you to select an existing project)' ), Cli::COLOR_CYAN )
			->printInColor ( dgettext ( 'core', '**********************' ), Cli::COLOR_LCYAN );
	}
	protected function executeOnConsole(): void {
		$StrService = $this->getSuperServices ()->getLocalizeService ();
		$DatabaseSettings = $this->getProjectAppConfig ()->findByKey ( 'databases' );
		$continue = true;
		while ( $continue ) {
			if ($databaseKey = $this->promptToFindElementInCollection ( dgettext ( 'core', 'Select the configuration you want to delete (you cannot roll back, backup your configuration file if not sure):' ), $DatabaseSettings, null, true )) {
				if ($StrService->meansYes ( $this->CliService->prompt ( sprintf ( dgettext ( 'core', 'Confirm removing "%s" from settings: [yN]' ), $databaseKey ), dgettext ( 'core', 'n {means no}' ) ) ))
					$DatabaseSettings->delete ( $databaseKey );
			} else
				$this->CliService->printWarning ( dgettext ( 'core', 'You have not selected a database configuration to delete.' ) );
			$continue = $StrService->meansYes ( $this->CliService->prompt ( dgettext ( 'core', 'Delete another one? [yN]' ), dgettext ( 'core', 'n {means no}' ) ) );
		}
	}
}