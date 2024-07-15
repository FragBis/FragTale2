<?php

namespace Console;

use FragTale\Service\Cli;
use Console\Setup\Etc\Hosts;
use FragTale\DataCollection;
use Console\Setup\Etc\Apache;
use Console\Setup\Etc\Nginx;
use Console\Project\Create;
use FragTale\Constant\Setup\CustomProjectPattern;

class Install extends Setup {
	private const SERVERS = [ 
			'APACHE',
			'NGINX'
	];
	/**
	 * Default is localhost
	 *
	 * @var string
	 */
	protected string $hostname = 'localhost';
	protected function executeOnTop(): void {
		if ($this->isUserRoot ())
			parent::executeOnTop ();
	}
	protected function executeOnConsole(): void {
		if (! $this->isUserRoot ()) {
			$this->CliService->printError ( dgettext ( 'core', 'You need to be root or sudoer.' ) );
			return;
		}
		if ($this->isHelpInvoked ()) {
			$this->CliService->printInColor ( dgettext ( 'core', '**********************' ), Cli::COLOR_LCYAN )
				->printInColor ( dgettext ( 'core', 'CLI arguments:' ), Cli::COLOR_LCYAN )
				->print ( dgettext ( 'core', '· "--host": server name' ), Cli::COLOR_LCYAN )
				->print ( dgettext ( 'core', '· "--project": project name to bind with host' ) )
				->print ( dgettext ( 'core', '· "--server": [Apache|Nginx]' ) )
				->print ( dgettext ( 'core', '· "--force": [0|1] If true, it will run all configuration processes with minimum prompts.' ) )
				->printInColor ( dgettext ( 'core', '**********************' ), Cli::COLOR_LCYAN );
		}
		$this->CliService->printInColor ( dgettext ( 'core', '**********************' ), Cli::COLOR_LCYAN )
			->printInColor ( dgettext ( 'core', 'Installation process supports Debian-like distributions.' ), Cli::COLOR_LCYAN )
			->printWarning ( dgettext ( 'core', 'It will help you to setup a development environment using a virtual host.' ) )
			->printWarning ( dgettext ( 'core', 'It comes with no warranty. It uses the most common setup deployed on a Debian server.' ) )
			->printInColor ( dgettext ( 'core', '**********************' ), Cli::COLOR_LCYAN )
			->printInColor ( sprintf ( dgettext ( 'core', 'You are using PHP version %s' ), PHP_VERSION ), Cli::COLOR_YELLOW );
		if ($this->isHelpInvoked ())
			return;

		// Full host configuration
		$force = ( int ) $this->CliService->getOpt ( 'force' );

		// a. /etc/hosts
		if ($hostname = $this->CliService->getOpt ( 'host' ))
			$this->hostname = trim ( $hostname );
		else
			$this->hostname = $this->CliService->prompt ( dgettext ( 'core', 'Type hostname you want to setup:' ), $this->hostname );
		$this->CliService->print ( sprintf ( dgettext ( 'core', 'Hostname to setup: %s' ), $this->hostname ) );

		$answer = $force ? '1' : trim ( ( string ) $this->CliService->prompt ( dgettext ( 'core', 'Do you want to append this hostname to your "/etc/hosts" file? [yN]' ), dgettext ( 'core', 'n {means no}' ) ) );
		if ($this->getSuperServices ()->getLocalizeService ()->meansYes ( $answer )) {
			// Check if file /etc/hosts exists
			if (file_exists ( '/etc/hosts' ))
				(new Hosts ())->setHostname ( $this->hostname )->run ();
			else
				$this->CliService->printError ( dgettext ( 'core', 'Could not find "/etc/hosts" file!' ) );
		}

		// b. Web server
		$server = strtoupper ( trim ( ( string ) $this->CliService->getOpt ( 'server' ) ) );
		if (! in_array ( $server, self::SERVERS )) {
			$answer = $force ? '1' : trim ( ( string ) $this->CliService->prompt ( dgettext ( 'core', 'Do you want to add a basic configuration file to your Apache or Nginx server? [yN]' ), dgettext ( 'core', 'n {means no}' ) ) );
			if ($this->getSuperServices ()->getLocalizeService ()->meansYes ( $answer ))
				$server = $this->promptToFindElementInCollection ( dgettext ( 'core', 'Select your web server:' ), new DataCollection ( self::SERVERS ) );
		}
		if ($server) {
			$this->CliService->print ( $server );
			switch ($server) {
				case 'APACHE' :
					(new Apache ())->setHostname ( $this->hostname )->run ();
					break;
				case 'NGINX' :
					(new Nginx ())->setHostname ( $this->hostname )->run ();
					break;
			}
		}

		// c. Prompt to create a project or use existing one
		if ($projectname = trim ( ( string ) $this->CliService->getOpt ( 'project' ) )) {
			// Check if project exists
			$folderToCheck = sprintf ( CustomProjectPattern::WEB_CONTROLLER_DIR, $projectname );
			if (! is_dir ( $folderToCheck )) {
				$this->CliService->printWarning ( sprintf ( dgettext ( 'core', 'Project "%s" does not exist or does not match required structure to run a website!' ), $projectname ) );
				$projectname = null;
			}
		}
		if (! $projectname && $force)
			$answer = '1';
		elseif ($projectname)
			$answer = '0';
		else
			$answer = trim ( ( string ) $this->CliService->prompt ( dgettext ( 'core', 'You have to bind your new host to an existing project. Do you want to create a new project? [yN]' ), dgettext ( 'core', 'n {means no}' ) ) );
		if ($this->getSuperServices ()->getLocalizeService ()->meansYes ( $answer )) {
			(new Create ())->run ();
			$this->CliService->printInColor ( dgettext ( 'core', 'You should "chown" your project folder to be the owner if you want to modify files.' ), Cli::COLOR_YELLOW )
				->printInColor ( dgettext ( 'core', 'You can let "root" to be owner of the FragTale2 framework.' ), Cli::COLOR_YELLOW )
				->printInColor ( dgettext ( 'core', 'You can use GIT and version your project folder.' ), Cli::COLOR_YELLOW )
				->printInColor ( dgettext ( 'core', 'Do not include FragTale2 framework in your version control.' ), Cli::COLOR_YELLOW );
		} else
			// Set host to project
			(new \Console\Setup\Hosts ())->setHostname ( $hostname )->setProjectname ( $projectname )->run ();

		$this->CliService->printSuccess ( dgettext ( 'core', 'You can now exit "root" or "sudo" mode.' ) )
			->printWarning ( dgettext ( 'core', "You'll have to bind your host to one of your project environment, but default is \"production\"." ) )
			->printInColor ( dgettext ( 'core', 'If not done yet, execute following command:' ), Cli::COLOR_LCYAN )
			->print ( '$ ./fragtale2 Console/Project/Configure/Environment' );
	}
}