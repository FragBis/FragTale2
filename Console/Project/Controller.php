<?php

namespace Console\Project;

use Console\Project;
use Console\Project\Controller\Create;
use FragTale\Service\Cli;

/**
 *
 * @author Fabrice Dant
 *        
 */
class Controller extends Project {
	/**
	 * Executed in child controllers
	 */
	function __construct() {
		parent::__construct ();
		$this->setOrPromptProjectName ();
	}

	/**
	 */
	protected function executeOnTop(): void {
	}

	/**
	 */
	protected function executeOnConsole(): void {
		$this->CliService->printInColor ( sprintf ( dgettext ( 'core', 'Running controller %s' ), Create::class ), Cli::COLOR_ORANGE );
		(new Create ())->run ();
	}

	/**
	 */
	protected function executeOnBottom(): void {
	}
}