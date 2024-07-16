<?php

namespace Console\Setup\Etc;

use Console\Setup\Etc;
use FragTale\Service\Cli;
use FragTale\Constant\Setup\CorePath;
use FragTale\Service\Filesystem;

/**
 *
 * @author Fabrice Dant
 *        
 */
class Apache extends Etc {
	private const SERVER_APP_NAME = 'Apache2';
	private const CONF_PATTERN_FILE = CorePath::CODE_PATTERNS_DIR . '/etc/debian/apache.pattern';
	protected const ETC_DEST_DIR = '/etc/%s/sites-available';
	protected ?string $hostname = null;
	protected ?string $dest = null;
	protected ?string $root = null;
	protected ?string $phpv = null;

	/**
	 * Apache2
	 *
	 * @return self
	 */
	protected function getWebServerName(): string {
		return self::SERVER_APP_NAME;
	}
	/**
	 *
	 * @return self
	 */
	protected function getConfPatternFile(): string {
		return self::CONF_PATTERN_FILE;
	}
	/**
	 *
	 * @return self
	 */
	protected function getDefaultEtcDestinationDirectory(): string {
		return sprintf ( self::ETC_DEST_DIR, strtolower ( $this->getWebServerName () ) );
	}
	/**
	 * Set hostname from another controller before running this one (see Console\Install).
	 *
	 * @param string $hostname
	 * @return self
	 */
	protected function setHostname(?string $hostname): self {
		$this->hostname = $hostname;
		return $this;
	}
	/**
	 * Set apache conf destination folder from another controller before running this one (see Console\Install).
	 *
	 * @param string $dest
	 *        	Absolute path
	 * @return self
	 */
	protected function setDestinationFolder(?string $dest): self {
		$this->dest = $dest;
		return $this;
	}
	/**
	 * Set "DocumentRoot" apache conf from another controller before running this one (see Console\Install).
	 *
	 * @param string $root
	 *        	Absolute path
	 * @return self
	 */
	protected function setDocumentRoot(?string $root): self {
		$this->root = $root;
		return $this;
	}
	/**
	 * Set PHP version used by PHP-FPM apache module from another controller before running this one (see Console\Install).
	 *
	 * @param string $version
	 *        	Like 8.3 (third number is not used, for example if you pass 8.3.9, it will only keep 8.3)
	 * @return self
	 */
	protected function setPhpVersion(?string $version): self {
		if (! $version)
			return $this;
		$expV = explode ( '.', $version );
		$phpShortVersion = "{$expV[0]}.{$expV[1]}";
		$isInstalled = ! empty ( shell_exec ( "which php{$phpShortVersion}" ) );
		if (! $isInstalled || count ( $expV ) < 2) {
			$errMsg = sprintf ( dgettext ( 'core', 'Given PHP version "%s" does not correspond to a valid installed executable binary' ), $phpShortVersion );
			throw new \Exception ( $errMsg );
		} elseif ($phpShortVersion < '8.1')
			$this->CliService->printWarning ( dgettext ( 'core', 'This framework could encounter issues with PHP versions < 8.1' ) );
		$this->phpv = $phpShortVersion;
		return $this;
	}
	/**
	 * Executed for Apache2 and Nginx
	 */
	protected function executeOnConsole(): void {
		if (! $this->isUserRoot ()) {
			$this->CliService->printError ( dgettext ( 'core', 'You need to be root or sudoer.' ) );
			return;
		}
		$text = $this->getWebServerName () === self::SERVER_APP_NAME ? dgettext ( 'core', 'Apache2 must have been installed with its PHP module for Apache, using PHP-FPM!' ) : dgettext ( 'core', 'Nginx must have been installed with PHP-FPM!' );
		$this->CliService->printInColor ( dgettext ( 'core', '**********************' ), Cli::COLOR_LCYAN )
			->printInColor ( dgettext ( 'core', 'Basic virtual host configuration deployment.' ), Cli::COLOR_LCYAN )
			->printWarning ( $text )
			->printInColor ( dgettext ( 'core', '**********************' ), Cli::COLOR_LCYAN );
		if ($this->isHelpInvoked ()) {
			$this->CliService->printInColor ( dgettext ( 'core', 'CLI arguments:' ), Cli::COLOR_LCYAN )
				->print ( dgettext ( 'core', '· "--host": server name' ), Cli::COLOR_LCYAN )
				->print ( dgettext ( 'core', '· "--root": absolute path of document root. If not passed, it will use the public folder of this application' ), Cli::COLOR_LCYAN )
				->print ( dgettext ( 'core', '· "--php-version": for example, "8.3". If not specified, it will use the PHP version of the current executed PHP binary.' ), Cli::COLOR_LCYAN )
				->print ( sprintf ( dgettext ( 'core', '· "--dest": destination folder where the %s configuration will be placed (default is "%s")' ), $this->getWebServerName (), $this->getDefaultEtcDestinationDirectory () ), Cli::COLOR_LCYAN )
				->print ( dgettext ( 'core', '· "--force": [0|1] If true, it will run all configuration processes with minimum prompts.' ) )
				->printInColor ( dgettext ( 'core', '**********************' ), Cli::COLOR_LCYAN );
			return;
		}

		$force = ( int ) $this->CliService->getOpt ( 'force' );
		try {
			// Check destination folder exists
			if (! $this->dest)
				if (! ($this->dest = trim ( ( string ) $this->CliService->getOpt ( 'dest' ) )))
					$this->dest = $force ? $this->getDefaultEtcDestinationDirectory () : trim ( ( string ) $this->CliService->prompt ( sprintf ( dgettext ( 'core', 'Type %s "sites-available" absolute path:' ), $this->getWebServerName () ), $this->getDefaultEtcDestinationDirectory () ) );
			if (! $this->dest)
				$this->dest = $this->getDefaultEtcDestinationDirectory ();
			if (! is_dir ( $this->dest )) {
				$this->CliService->printError ( sprintf ( dgettext ( 'core', 'Destination folder "%s" does not exist or is not accessible.' ), $this->dest ) );
				return;
			}

			// Get/Set parameters
			if (! $this->hostname)
				if (! ($this->hostname = trim ( ( string ) $this->CliService->getOpt ( 'host' ) )))
					if (! ($this->hostname = trim ( ( string ) $this->CliService->prompt ( dgettext ( 'core', 'Type the server name (host):' ) ) ))) {
						$this->CliService->printError ( dgettext ( 'core', 'Server name cannot be empty!' ) );
						return;
					}
			$confFilename = "{$this->dest}/{$this->hostname}";
			if ($this->getWebServerName () === self::SERVER_APP_NAME)
				$confFilename .= '.conf';
			if (file_exists ( $confFilename )) {
				$this->CliService->printError ( sprintf ( dgettext ( 'core', 'File "%s" already exists and will not be overwritten.' ), $confFilename ) );
				return;
			}

			if ($this->hostname == 'localhost')
				$this->CliService->printWarning ( dgettext ( 'core', 'You are setting up "localhost" that should already have been configured by default. Check any configuration conflict.' ) );

			if (! $this->root)
				if (! ($this->root = trim ( ( string ) $this->CliService->getOpt ( 'root' ) )))
					$this->root = $force ? APP_ROOT . '/public' : trim ( ( string ) $this->CliService->prompt ( dgettext ( 'core', 'Type the server document root:' ), APP_ROOT . '/public' ) );
			if (! $this->root)
				$this->root = APP_ROOT . '/public';
			if (! is_dir ( $this->root )) {
				$this->CliService->printError ( sprintf ( dgettext ( 'core', 'Document root "%s" does not exist or is not accessible.' ), $this->root ) );
				return;
			}
			if (! $this->phpv) {
				$this->setPhpVersion ( trim ( ( string ) $this->CliService->getOpt ( 'php-version' ) ) );
				if (! $this->phpv) {
					$expV = explode ( '.', PHP_VERSION );
					$version = "{$expV[0]}.{$expV[1]}";
					if ($force || ($version = $this->CliService->prompt ( dgettext ( 'core', 'Type PHP version:' ), $version )))
						$this->setPhpVersion ( $version );
				}
			}
			if (! $this->phpv)
				$this->setPhpVersion ( PHP_VERSION );

			// Then copy file to destination folder
			if (! file_exists ( $this->getConfPatternFile () ))
				throw new \Exception ( sprintf ( dgettext ( 'core', 'Required file is missing: %s.' ), $this->getConfPatternFile () ) );

			$confContent = file_get_contents ( $this->getConfPatternFile () );
			if ($this->getWebServerName () === self::SERVER_APP_NAME)
				$confContent = str_replace ( [ 
						'/*server_name*/',
						'/*root*/',
						'/*php_version*/',
						'/*php_int*/'
				], [ 
						$this->hostname,
						$this->root,
						$this->phpv,
						explode ( '.', $this->phpv ) [0]
				], $confContent );
			else
				$confContent = str_replace ( [ 
						'/*server_name*/',
						'/*root*/',
						'/*php_version*/'
				], [ 
						$this->hostname,
						$this->root,
						$this->phpv
				], $confContent );

			if ($this->getSuperServices ()->getFilesystemService ()->createFile ( $confFilename, $confContent, Filesystem::FILE_OVERWRITE_KEEP )) {
				$this->CliService->printWarning ( sprintf ( dgettext ( 'core', 'You must enable your new %s host and restart your web server:' ), $this->getWebServerName () ) );
				if ($this->getWebServerName () === self::SERVER_APP_NAME)
					$this->CliService->print ( sprintf ( '$ a2ensite %s', $this->hostname ) )->print ( '$ service apache2 reload|restart' );
				else
					$this->CliService->print ( sprintf ( '$ ln -s %s %s', $confFilename, dirname ( $this->dest ) . '/sites-enabled/' ) )->print ( '$ service nginx reload|restart' );
			}
		} catch ( \Exception $Exc ) {
			$this->getSuperServices ()->getErrorHandlerService ()->catchThrowable ( $Exc );
		}
	}
}