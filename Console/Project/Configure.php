<?php

namespace Console\Project;

use Console\Project;
use FragTale\Service\Cli;

/**
 *
 * @author Fabrice Dant
 *        
 */
class Configure extends Project {

	/**
	 * Executed in child controllers
	 */
	function __construct() {
		parent::__construct ();

		if ($this->isHelpInvoked ()) {
			$this->CliService->printInColor ( dgettext ( 'core', '**********************' ), Cli::COLOR_LCYAN )
				->printInColor ( dgettext ( 'core', 'CLI option:' ), Cli::COLOR_CYAN )
				->print ( '	' . dgettext ( 'core', '· "--project": The project name (if not passed, application will prompt you to select an existing project)' ) )
				->printInColor ( dgettext ( 'core', '**********************' ), Cli::COLOR_LCYAN );
			return;
		}

		$this->setOrPromptProjectName ();
	}

	/**
	 *
	 * {@inheritdoc}
	 * @see \FragTale\Application\Controller::executeOnTop()
	 */
	protected function executeOnTop(): void {
		if ($this->isHelpInvoked ())
			return;

		$title = sprintf ( dgettext ( 'core', 'Setting up project "%s"' ), $this->getProjectName () );
		$this->CliService->printInColor ( $title, Cli::COLOR_YELLOW )
			->printInColor ( dgettext ( 'core', '**********************' ), Cli::COLOR_LCYAN )
			->printInColor ( dgettext ( 'core', 'You have 3 actions:' ), Cli::COLOR_CYAN )
			->print ( '	' . dgettext ( 'core', '· "Database": setup your database credentials (SQL & MongoDB)' ) )
			->print ( '	' . dgettext ( 'core', '· "Environment": setup your environments, basically corresponding to "production" and "development"' ) )
			->print ( '	' . dgettext ( 'core', '· "Parameter": optionally edit some custom project parameters that you can use for your own purposes' ) )
			->printInColor ( dgettext ( 'core', '**********************' ), Cli::COLOR_LCYAN );
	}

	/**
	 * launchSubController
	 */
	protected function executeOnConsole(): void {
		if ($this->isHelpInvoked ())
			return;

		$StrService = $this->getSuperServices ()->getLocalizeService ();
		$continue = true;
		while ( $continue ) {
			$this->launchSubController ();
			$continue = $StrService->meansYes ( $this->CliService->prompt ( dgettext ( 'core', 'Continue configuring databases, environments or user paramaters? [Yn]' ), dgettext ( 'core', 'y {means yes}' ) ) );
		}
	}
}