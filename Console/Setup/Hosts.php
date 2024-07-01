<?php

namespace Console\Setup;

use Console\Setup;
use FragTale\Constant\Setup\CorePath;
use FragTale\DataCollection;
use FragTale\Service\Cli;

/**
 *
 * @author Fabrice Dant
 *        
 */
class Hosts extends Setup {

	/**
	 *
	 * {@inheritdoc}
	 * @see \Console\Setup::executeOnConsole()
	 */
	protected function executeOnConsole(): void {
		$this->CliService->printInColor ( dgettext ( 'core', 'Entering hosts management' ), Cli::COLOR_YELLOW )
			->printInColor ( dgettext ( 'core', '**********************' ), Cli::COLOR_LCYAN )
			->printWarning ( dgettext ( 'core', 'Here, you can bind one host (domain or IP) to one project.' ) )
			->print ( dgettext ( 'core', 'This is needed to route a host to a specific project.' ) )
			->print ( dgettext ( 'core', 'One project can handle multiple hosts.' ) )
			->print ( dgettext ( 'core', 'But, one host is bound to only one project.' ) )
			->printInColor ( dgettext ( 'core', '**********************' ), Cli::COLOR_LCYAN );

		// Check if at least one project exists
		$Projects = new DataCollection ();
		foreach ( scandir ( CorePath::PROJECT_ROOT ) as $projectName ) {
			if (! in_array ( $projectName, [ 
					'.',
					'..'
			] ) && is_dir ( CorePath::PROJECT_ROOT . '/' . $projectName ))
				$Projects->upsert ( $projectName, $projectName );
		}
		if (! $Projects->count ()) {
			$this->CliService->printWarning ( dgettext ( 'core', 'There is no project created yet. Please, create project fisrt by launching command: ./fragtale2 Console/Project/Create' ) );
			return;
		}

		$Hosts = $this->HostsSettings;
		$isNew = true;
		if ($Hosts->count ()) {
			$Choices = (new DataCollection ())->upsert ( 1, dgettext ( 'core', '(add new)' ) );
			$i = 1;
			foreach ( $Hosts as $host => $projectName ) {
				$Choices->upsert ( ++ $i, $host . ' --> ' . $projectName );
			}

			$this->CliService->printInColor ( dgettext ( 'core', 'Below is the list of existing configured hosts.' ), Cli::COLOR_BLUE )->printInColor ( dgettext ( 'core', 'If you select "1", you\'ll create new domain to bind to an existing project.' ), Cli::COLOR_BLUE )->printInColor ( dgettext ( 'core', 'Other choice allows you to modify or delete a host and project binding.' ), Cli::COLOR_BLUE );
			$choice = $this->promptToFindElementInCollection ( dgettext ( 'core', 'host --> project' ), $Choices, null, true );
			$choice = $choice && is_numeric ( $choice ) ? ( int ) $choice : 0;
			if ($choice > 1) {
				$isNew = false;
				$hIndex = $choice - 2;
				$projectName = $Hosts->findAt ( $hIndex );
				$host = $Hosts->getElementKey ( $projectName );
				switch ($this->promptToFindElementInCollection ( dgettext ( 'core', 'Choose action' ), new DataCollection ( [ 
						dgettext ( 'core', 'Update' ),
						dgettext ( 'core', 'Delete' )
				] ) )) {
					case dgettext ( 'core', 'Update' ) :
						if ($projectName = $this->promptToFindElementInCollection ( dgettext ( 'core', 'Please select project to bind to:' ), $Projects )) {
							$Hosts->upsert ( $host, $projectName );
							$this->CliService->print ( sprintf ( dgettext ( 'core', 'Selected project "%1s" bound to host "%2s"' ), $projectName, $host ) )->print ( dgettext ( 'core', 'OK' ) );
						}
						break;
					case dgettext ( 'core', 'Delete' ) :
						if ($this->getSuperServices ()->getLocalizeService ()->meansYes ( $this->CliService->prompt ( dgettext ( 'core', 'Confirm deletion' ), dgettext ( 'core', 'n {means no}' ) ) )) {
							$Hosts->delete ( $host );
							$this->CliService->print ( dgettext ( 'core', 'OK' ) );
						}
						break;
				}
			} elseif ($choice !== 1)
				return;
		}
		if ($isNew) {
			if (! $Hosts->count ())
				$this->CliService->printWarning ( dgettext ( 'core', 'Host configuration file is empty.' ) );
			if ($host = $this->CliService->prompt ( dgettext ( 'core', 'Please, type a domain name or an IP to bind to an existing project.' ) )) {
				if ($projectName = $this->promptToFindElementInCollection ( dgettext ( 'core', 'Please select project to bind to:' ), $Projects )) {
					$Hosts->upsert ( $host, $projectName );
					$this->CliService->print ( sprintf ( dgettext ( 'core', 'Selected project "%1s" bound to host "%2s"' ), $projectName, $host ) );
				}
			}
		}
		if ($Hosts->modified ()) {
			$Hosts->exportToJsonFile ( $Hosts->getSource (), true );
			$this->CliService->printSuccess ( dgettext ( 'core', 'OK, hosts file modified' ) );
		} else
			$this->CliService->printWarning ( dgettext ( 'core', 'No modification made' ) );
	}
}