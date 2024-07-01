<?php

namespace Console\Setup\CliApplication;

use Console\Setup\CliApplication;
use FragTale\DataCollection;
use FragTale\Service\Cli;

/**
 *
 * @author Fabrice Dant
 *        
 */
class Parameter extends CliApplication {
	protected function executeOnConsole(): void {
		$this->CliService->printInColor ( dgettext ( 'core', 'Entering CLI application parameters setup' ), Cli::COLOR_YELLOW );

		$Parameters = $this->CliApplicationSettings->findByKey ( 'parameters' );
		if (! $Parameters) {
			$Parameters = new DataCollection ();
			$this->CliApplicationSettings->upsert ( 'parameters', $Parameters );
		}
		$this->setupParameters ( $Parameters );
	}

	/**
	 *
	 * @param DataCollection $Parameters
	 */
	public function setupParameters(DataCollection $Parameters): void {
		$this->CliService->printInColor ( dgettext ( 'core', '**********************' ), Cli::COLOR_LCYAN )
			->printInColor ( dgettext ( 'core', 'You can create your own custom parameters. It is up to you to use them in your project.' ), Cli::COLOR_CYAN )
			->printWarning ( dgettext ( 'core', 'Attention! Configuration will be saved only at the very end of the process.' ) )
			->printWarning ( dgettext ( 'core', 'Do not "Ctrl+C" (force quit) if you want to save file.' ) )
			->printInColor ( dgettext ( 'core', '**********************' ), Cli::COLOR_LCYAN );
		$setCustomParameters = $this->CliService->prompt ( dgettext ( 'core', 'Setup parameters? [yN]' ), dgettext ( 'core', 'n {means no}' ) );
		$continue = $this->getSuperServices ()->getLocalizeService ()->meansYes ( $setCustomParameters );
		while ( $continue ) {
			$this->CliService->print ( sprintf ( dgettext ( 'core', '%d parameter(s) registered' ), $Parameters->count () ) );
			if ($key = $this->promptToFindElementInCollection ( dgettext ( 'core', 'Leave empty and press Enter to create new parameter' ), $Parameters, null, true )) {
				$value = $Parameters->findByKey ( $key );
				if (! $value instanceof DataCollection) {
					$newValue = $this->CliService->prompt ( sprintf ( dgettext ( 'core', 'Enter new value for param "%s"' ), $key ), $value );
					if ($newValue != $value)
						$Parameters->upsert ( $key, $newValue );
				} else {
					$this->CliService->printWarning ( dgettext ( 'core', 'This CLI application cannot handle modifications on parameters containing objects (only single values). Update JSON file manually.' ) )->print ( sprintf ( dgettext ( 'core', 'Param "%1s" contains: %2s' ), $key, substr ( $value->toJsonString (), 0, 25 ) ) . '...' );
				}
			} else {
				// Create parameter
				if ($newKey = $this->CliService->prompt ( dgettext ( 'core', 'Enter new parameter key:' ) )) {
					if ($Parameters->findByKey ( $newKey ))
						$this->CliService->printWarning ( sprintf ( dgettext ( 'core', 'Key "%s" already exists.' ), $newKey ) );
					else {
						$newValue = $this->CliService->prompt ( sprintf ( dgettext ( 'core', 'Enter new value for param "%s":' ), $newKey ) );
						$Parameters->upsert ( $newKey, $newValue );
					}
				} else
					$this->CliService->printWarning ( dgettext ( 'core', 'Key cannot be empty' ) );
			}
			$continue = $this->getSuperServices ()->getLocalizeService ()->meansYes ( $this->CliService->prompt ( dgettext ( 'core', 'Setup another parameter? [yN]' ), dgettext ( 'core', 'n {means no}' ) ) );
		}
	}

	/**
	 * Save conf file
	 */
	protected function executeOnBottom(): void {
		$this->saveApplicationConfigurationFile ();
	}
}