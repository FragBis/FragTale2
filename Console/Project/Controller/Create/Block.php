<?php

namespace Console\Project\Controller\Create;

use Console\Project\Controller\Create;
use FragTale\Constant\Setup\ControllerType;

class Block extends Create {
	/**
	 */
	protected function executeOnTop(): void {
		if ($this->isHelpInvoked ()) {
			$this->CliService->printInColor ( dgettext ( 'core', '**** Help invoked ****' ), Cli::COLOR_LCYAN )
				->printInColor ( sprintf ( dgettext ( 'core', 'There are %s CLI options handled (not required):' ), 4 ), Cli::COLOR_LCYAN )
				->print ( '	' . dgettext ( 'core', '· "--project": the project name' ) )
				->print ( '	' . dgettext ( 'core', '· "--dir": the controller folder in which to place it; It can be the relative path from the project directory or from controller type folder (e.g.: Project/{projectName}/Controller/{controllerType})' ) )
				->print ( '	' . dgettext ( 'core', '· "--name": the new controller class name' ) )
				->print ( '	' . dgettext ( 'core', '· "--pattern": pattern folder name (placed in your project template patterns directory). Only for types "Web" and "Block"' ) )
				->printInColor ( dgettext ( 'core', '**********************' ), Cli::COLOR_LCYAN );
			return;
		}
		$this->swapTemplate = false;
		$this->patternFolderName = trim ( ( string ) $this->CliService->getOpt ( 'pattern' ) );
		$this->controllerName = ( string ) $this->getControllerName ();
		$this->controllerFolder = ( string ) $this->getRelativeFolder ();
	}

	/**
	 */
	protected function executeOnConsole(): void {
		if ($this->isHelpInvoked ())
			return;
		$this->createController ( ControllerType::BLOCK );
	}
}