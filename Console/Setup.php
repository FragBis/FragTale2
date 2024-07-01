<?php

namespace Console;

use Console;
use FragTale\Constant\Setup\CorePath;
use FragTale\Service\Cli;
use Console\Setup\CliApplication\Locale;
use FragTale\DataCollection\JsonCollection;

/**
 *
 * @author Fabrice Dant
 *        
 */
class Setup extends Console {

	/**
	 * The core application settings collection.
	 *
	 * @var JsonCollection
	 */
	protected ?JsonCollection $CliApplicationSettings;

	/**
	 * The core hosts settings collection.
	 * It lists the association between a domain or an IP and a project name.
	 * A project can have many hosts.
	 * A host can have only one project.
	 *
	 * @var JsonCollection
	 */
	protected ?JsonCollection $HostsSettings;

	/**
	 * Checking core folders are presents.
	 */
	protected function executeOnTop(): void {
		// Check required dir
		foreach ( CorePath::getConstants () as $key => $corePath ) {
			if ($corePath === 'Project')
				continue;
			if (in_array ( substr ( $key, - 4 ), [ 
					'_DIR',
					'ROOT'
			] ) && ! is_dir ( $corePath )) {
				if (in_array ( $key, [ 
						'CONSOLE_DIR',
						'PUBLIC_DIR',
						'RESOURCES_DIR',
						'CODE_PATTERNS_DIR'
				] )) {
					$this->CliService->printError ( dgettext ( 'core', 'Missing required core folder:' ) . " $corePath" )->printError ( dgettext ( 'core', 'You should rebase your framework from the GIT source!' ) );
					return;
				} else
					$this->getSuperServices ()->getFilesystemService ()->createDir ( $corePath );
			} elseif (substr ( $corePath, - 5 ) === '.json' && strpos ( $corePath, CorePath::CODE_PATTERNS_DIR ) === false) {
				if (! file_exists ( $corePath )) {
					if ((strpos ( $corePath, 'application.json' ) && copy ( CorePath::PATTERN_APP_SETUP_FILE, $corePath ))) {
						$this->CliService->printSuccess ( dgettext ( 'core', 'Created file:' ) . " $corePath" );
						(new Locale ())->run ();
					} elseif (file_put_contents ( $corePath, '{}' ))
						$this->CliService->printSuccess ( dgettext ( 'core', 'Created file:' ) . " $corePath" );
					else
						$this->CliService->printError ( dgettext ( 'core', 'Could not create file:' ) . " $corePath; " . dgettext ( 'core', 'You might not have write access on targetted folder.' ) );
				}
			} elseif (! file_exists ( $corePath ))
				$this->CliService->printError ( sprintf ( dgettext ( 'core', 'Missing versioned core file: %s' ), $corePath ) );
		}

		// Load core settings and init param structure
		$this->CliApplicationSettings = (new JsonCollection ())->setSource ( CorePath::APP_SETTINGS_FILE )->load ();
		$this->HostsSettings = (new JsonCollection ())->setSource ( CorePath::HOSTS_SETTINGS_FILE )->load ();
	}

	/**
	 *
	 * {@inheritdoc}
	 * @see \FragTale\Application\Controller::executeOnConsole()
	 */
	protected function executeOnConsole(): void {
		$this->getSuperServices ()
			->getCliService ()
			->printInColor ( dgettext ( 'core', '**********************' ), Cli::COLOR_LCYAN )
			->printInColor ( dgettext ( 'core', 'You have 3 sections:' ), Cli::COLOR_LCYAN )
			->print ( '	' . dgettext ( 'core', '· "CliApplication": Setup your CLI application global settings with 2 more sub sections: "Locale" (define your language) and "Parameter" (optional).' ) )
			->print ( '	' . dgettext ( 'core', '· "Hosts": This part is important. It will allows you to route a host (domain or IP) to a specified existing project.' ) )
			->print ( '	' . dgettext ( 'core', '· "Module": offers a list of modules to install.' ) )
			->printInColor ( dgettext ( 'core', '**********************' ), Cli::COLOR_LCYAN );
		$this->launchSubController ();
	}
}