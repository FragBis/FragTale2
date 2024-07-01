<?php

namespace Console\Setup;

use Console\Setup;
use FragTale\Service\Cli;
use FragTale\DataCollection;
use FragTale\Constant\Setup\CorePath;
use FragTale\DataCollection\JsonCollection;

/**
 *
 * @author Fabrice Dant
 *        
 */
class Module extends Setup {

	/**
	 *
	 * {@inheritdoc}
	 * @see \Console\Setup::executeOnConsole()
	 */
	protected function executeOnConsole(): void {
		$this->CliService->printInColor ( dgettext ( 'core', 'Entering modules management' ), Cli::COLOR_YELLOW )
			->printInColor ( dgettext ( 'core', '**********************' ), Cli::COLOR_LCYAN )
			->printInColor ( dgettext ( 'core', 'Here, you can install new module between the list below' ), Cli::COLOR_CYAN )
			->printInColor ( dgettext ( 'core', '**********************' ), Cli::COLOR_LCYAN );

		$Modules = (new JsonCollection ())->setSource ( CorePath::MODULES_LIST )->load ();
		$installedModules = scandir ( CorePath::MODULE_DIR );
		$locale = explode ( '.', ( string ) setlocale ( LC_ALL, 0 ) ) [0];
		foreach ( $Modules as $key => $Module ) {
			$installed = in_array ( $Module->findByKey ( 'target_folder' ), $installedModules ) ? dgettext ( 'core', '(installed)' ) : null;
			$this->CliService->printInColor ( "	* $key $installed", CLI::COLOR_BLUE )->print ( '		' . $Module->findByKey ( 'description' )->findByKey ( $locale ) );
		}

		if ($moduleName = $this->CliService->prompt ( dgettext ( 'core', 'Please, type the module name you want to install' ) )) {
			if (($Module = $Modules->findByKey ( $moduleName )) && $Module instanceof DataCollection && $gitRepo = $Module->findByKey ( 'repository' )) {
				chdir ( CorePath::MODULE_DIR );
				$folder = $Module->findByKey ( 'target_folder' );
				echo exec ( "git clone $gitRepo $folder" );
			} else {
				$this->CliService->printError ( sprintf ( dgettext ( 'core', 'Unknown module "%s"' ), $moduleName ) );
			}
		}
	}
}

