<?php

namespace Console\Setup;

use Console\Setup;
use FragTale\DataCollection;
use FragTale\Constant\Setup\CorePath;
use FragTale\Service\Cli;

/**
 *
 * @author Fabrice Dant
 *        
 */
class CliApplication extends Setup {

	/**
	 * "datatases" settings section.
	 *
	 * @var DataCollection
	 */
	protected ?DataCollection $DatabasesSettings;

	/**
	 *
	 * {@inheritdoc}
	 * @see \Console\Setup::executeOnTop()
	 */
	protected function executeOnTop(): void {
		parent::executeOnTop ();
	}

	/**
	 *
	 * {@inheritdoc}
	 * @see \Console\Setup::executeOnConsole()
	 */
	protected function executeOnConsole(): void {
		$this->CliService->printInColor ( dgettext ( 'core', '**********************' ), Cli::COLOR_LCYAN )
			->printInColor ( dgettext ( 'core', 'Here, you can setup your CLI application with 2 sections:' ), Cli::COLOR_CYAN )
			->print ( '	' . dgettext ( 'core', '· "Locale": choose one of the supported languages and define encoding being used by your system (usually, "UTF-8" or "utf8").' ) )
			->print ( '	  ' . dgettext ( 'core', 'To know your system locale, just type in console following command: locale' ) )
			->print ( '	' . dgettext ( 'core', '· "Parameter": you can setup custom parameters that you\'ll have to handle.' ) )
			->print ( '	  ' . dgettext ( 'core', 'Here, the configuration file involved is "resources/configuration/application.json".' ) )
			->printInColor ( dgettext ( 'core', '**********************' ), Cli::COLOR_LCYAN );
		$this->launchSubController ();
	}

	/**
	 * If modifications have been made on collection "CliApplicationSettings", then save in json file.
	 */
	protected function executeOnBottom(): void {
	}

	/**
	 * Save modifications in configuration file "resources/configuration/application.json"
	 */
	protected function saveApplicationConfigurationFile(): void {
		if ($this->CliApplicationSettings->modified ()) {
			$this->CliApplicationSettings->save ();
			$this->CliService->printSuccess ( dgettext ( 'core', 'OK, check configuration file:' ) . ' ' . CorePath::APP_SETTINGS_FILE );
		} else
			$this->CliService->printWarning ( dgettext ( 'core', 'No modification made' ) );
	}
}