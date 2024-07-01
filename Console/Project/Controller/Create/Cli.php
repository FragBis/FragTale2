<?php

namespace Console\Project\Controller\Create;

use Console\Project\Controller\Create;
use FragTale\Constant\Setup\ControllerType;

class Cli extends Create {
	/**
	 */
	protected function executeOnTop(): void {
		if ($this->isHelpInvoked ()) {
			$this->CliService->printInColor ( dgettext ( 'core', '**** Help invoked ****' ), Cli::COLOR_LCYAN )
				->printInColor ( sprintf ( dgettext ( 'core', 'There are %s CLI options handled (not required):' ), 3 ), Cli::COLOR_LCYAN )
				->printInColor ( '	' . dgettext ( 'core', '"--project": the project name' ), Cli::COLOR_CYAN )
				->printInColor ( '	' . dgettext ( 'core', '"--dir": the controller folder in which to place it; It can be the relative path from the project directory or from controller type folder (e.g.: Project/{projectName}/Controller/{controllerType})' ), Cli::COLOR_CYAN )
				->printInColor ( '	' . dgettext ( 'core', '"--name": the new controller name' ), Cli::COLOR_CYAN )
				->printInColor ( dgettext ( 'core', '**********************' ), Cli::COLOR_LCYAN );
			return;
		}
		$this->swapTemplate = true;
		$this->controllerName = $this->getControllerName ();
		$this->controllerFolder = $this->getRelativeFolder ();
	}

	/**
	 */
	protected function executeOnConsole(): void {
		if ($this->isHelpInvoked ())
			return;
		$this->createController ( ControllerType::CLI );
	}
}