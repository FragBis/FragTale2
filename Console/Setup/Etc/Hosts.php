<?php

namespace Console\Setup\Etc;

use Console\Setup\Etc;
use FragTale\Service\Cli;

/**
 *
 * @author Fabrice Dant
 *        
 */
class Hosts extends Etc {
	private ?string $hostname = null;
	/**
	 * Set hostname from another controller before running this one (see Console\Install).
	 *
	 * @param string $hostname
	 * @return self
	 */
	public function setHostname(?string $hostname): self {
		$this->hostname = $hostname;
		return $this;
	}
	protected function executeOnConsole(): void {
		if (! $this->isUserRoot ()) {
			$this->CliService->printError ( dgettext ( 'core', 'You need to be root or sudoer.' ) );
			return;
		}
		if ($this->isHelpInvoked ()) {
			$this->CliService->printInColor ( dgettext ( 'core', '**********************' ), Cli::COLOR_LCYAN )
				->print ( dgettext ( 'core', '· "--host": host name to append to /etc/hosts file' ), Cli::COLOR_LCYAN )
				->print ( dgettext ( 'core', '· "--force": [0|1] If true, it will run all configuration processes with minimum prompts.' ) )
				->printInColor ( dgettext ( 'core', '**********************' ), Cli::COLOR_LCYAN );
			return;
		}
		$this->CliService->printWarning ( dgettext ( 'core', 'Accessing your "/etc/hosts" file in write mode (only for Linux distributions)...' ) )
			->printInColor ( dgettext ( 'core', 'Modifying this file is only for local machines to use custom virtual hosts. Do not use it in production.' ), Cli::COLOR_CYAN )
			->printInColor ( dgettext ( 'core', 'This application appends new domain bound to 127.0.0.1 localhost' ), Cli::COLOR_CYAN )
			->printInColor ( dgettext ( 'core', 'Backup your file before...' ), Cli::COLOR_CYAN );

		$force = ( int ) $this->CliService->getOpt ( 'force' );
		try {
			$hosts = file_get_contents ( '/etc/hosts' );
			$hostLines = explode ( "\n", $hosts );
			$nLineToMod = 0;
			$newLine = null;
			foreach ( $hostLines as $n => $line ) {
				$line = trim ( $line );
				if (strpos ( $line, '127.0.0.1' ) === 0) {
					$nLineToMod = $n;
					$newLine = $line;
					break;
				}
			}
			if ($newLine) {
				$this->CliService->printInColor ( dgettext ( 'core', 'Here are your current hosts:' ), Cli::COLOR_LCYAN )->print ( $newLine, true, false );

				// Get or set hostname
				if (empty ( $this->hostname )) {
					if (! ($this->hostname = trim ( ( string ) $this->CliService->getOpt ( 'host' ) )))
						$this->hostname = trim ( ( string ) $this->CliService->prompt ( dgettext ( 'core', 'Type a new custom domain to append: (Ctrl+C to exit)' ) ) );
				}

				// Register new hostname
				if ($this->hostname) {
					// Check if new domain already exists
					$explodedLine = explode ( ' ', str_replace ( "\t", ' ', $newLine ) );
					$matched = false;
					foreach ( $explodedLine as $part ) {
						if ($part === $this->hostname) {
							$matched = true;
							break;
						}
					}
					if ($matched) {
						$this->CliService->printWarning ( dgettext ( 'core', 'Hostname is already registered! Nothing to do.' ) );
						return;
					}

					$newLine = "$newLine {$this->hostname}";
					$hostLines [$nLineToMod] = $newLine;
					$hosts = implode ( "\n", $hostLines );
					$this->CliService->printInColor ( dgettext ( 'core', 'Your new file content:' ), Cli::COLOR_LCYAN )->print ( $hosts, true, false );

					$answer = $force ? '1' : ( string ) $this->CliService->prompt ( dgettext ( 'core', 'Confirm registration [yN]' ), dgettext ( 'core', 'n {means no}' ) );
					if ($this->getSuperServices ()->getLocalizeService ()->meansYes ( trim ( $answer ) ))
						// Record new entry
						if (file_put_contents ( '/etc/hosts', $hosts ))
							$this->CliService->printSuccess ( dgettext ( 'core', 'OK, file written.' ) );
				}
			} else
				$this->CliService->printError ( dgettext ( 'core', 'Could not find line for 127.0.0.1' ) );
		} catch ( \Exception $Exc ) {
			$this->getSuperServices ()->getErrorHandlerService ()->catchThrowable ( $Exc );
		}
	}
}