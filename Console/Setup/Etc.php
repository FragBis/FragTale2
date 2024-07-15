<?php

namespace Console\Setup;

use Console\Setup;
use FragTale\Service\Cli;

/**
 *
 * @author Fabrice Dant
 *        
 */
class Etc extends Setup {
	protected function executeOnTop(): void {
	}
	protected function executeOnConsole(): void {
		$this->CliService->printInColor ( dgettext ( 'core', '**********************' ), Cli::COLOR_LCYAN )
			->printInColor ( dgettext ( 'core', 'Here, you can setup your servers:' ), Cli::COLOR_CYAN )
			->print ( '	' . sprintf ( dgettext ( 'core', '· "%s": You can add a configuration file into "sites-available".' ), 'Apache' ) )
			->print ( '	' . dgettext ( 'core', '· "Hosts": You can add a domain bound to 127.0.0.1 in your /etc/hosts file (only for Linux distributions).' ) )
			->print ( '	' . sprintf ( dgettext ( 'core', '· "%s": You can add a configuration file into "sites-available".' ), 'Nginx' ) )
			->printInColor ( dgettext ( 'core', '**********************' ), Cli::COLOR_LCYAN );
		$this->launchSubController ();
	}
	protected function executeOnBottom(): void {
	}
}