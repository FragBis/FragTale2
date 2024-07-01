<?php

namespace Console;

use FragTale\Service\Cli;
use Console;

/**
 *
 * @author Fabrice Dant
 *        
 */
class Help extends Console {
	function executeOnConsole(): void {
		if ($this->CliService->isStdoutInteractive ()) {
			foreach ( [ 
					Cli::COLOR_LCYAN . 'FragTale PHP Open Source Framework version ' . FRAGTALE_VERSION . Cli::COLOR_WHITE,
					Cli::COLOR_CYAN . dgettext ( 'core', 'Usage:' ) . Cli::COLOR_WHITE . '	' . dgettext ( 'core', './fragtale2 <Path/To/Controller> [args...]' ),
					'',
					Cli::COLOR_CYAN . dgettext ( 'core', 'It is strongly recommended to create a controller by this command:' ) . Cli::COLOR_WHITE,
					'	' . dgettext ( 'core', './fragtale2 Console/Controller/Create(.php)' ),
					'',
					Cli::COLOR_CYAN . dgettext ( 'core', 'You can also inspect controllers in "Console" folder by pressing [TAB] twice:' ) . Cli::COLOR_WHITE,
					'	' . dgettext ( 'core', './fragtale2 Console/[TAB][TAB]' ),
					'',
					Cli::COLOR_CYAN . dgettext ( 'core', '"fragtale" command can also execute controllers placed into "Project" folder: (example)' ) . Cli::COLOR_WHITE,
					'	' . dgettext ( 'core', './fragtale2 Project/MyProject/Controller/Cli/MyController.php' ),
					'',
					Cli::COLOR_CYAN . dgettext ( 'core', 'Obviously, you created the project before:' ) . Cli::COLOR_WHITE,
					'	' . dgettext ( 'core', './fragtale2 Console/Project/Create' ),
					'',
					Cli::COLOR_CYAN . dgettext ( 'core', 'And at the very beginning, after install, you launched:' ) . Cli::COLOR_WHITE,
					'	' . dgettext ( 'core', './fragtale2 Console/Setup' ),
					''
			] as $text )
				$this->CliService->print ( " $text" );
		} else
			$this->CliService->print ( dgettext ( 'core', '#ROUTE_ERROR: Missing explicit controller argument in non-interactive Shell' ), true, true )->print ( '' );
	}
}