<?php

namespace Console\Project\Configure;

use Console\Project\Configure;
use FragTale\DataCollection;
use FragTale\Service\Cli;
use Console\Setup\CliApplication\Parameter as CliParamController;

/**
 *
 * @author Fabrice Dant
 *        
 */
class Parameter extends Configure {
	protected function executeOnTop(): void {
		// Do not execute parent function
	}
	protected function executeOnConsole(): void {
		if ($this->isHelpInvoked ())
			return;

		$this->CliService->printInColor ( sprintf ( dgettext ( 'core', 'Entering project "%s" parameters setup' ), $this->getProjectName () ), Cli::COLOR_YELLOW );

		$Parameters = $this->getProjectAppConfig ()->findByKey ( 'parameters' );
		if (! $Parameters instanceof DataCollection) {
			$Parameters = new DataCollection ();
			$this->getProjectAppConfig ()->upsert ( 'parameters', $Parameters );
		}
		(new CliParamController ())->setupParameters ( $Parameters );
	}
}