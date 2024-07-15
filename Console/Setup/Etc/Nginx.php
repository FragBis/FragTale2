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
class Nginx extends Etc {
	private const ETC_DEST_DIR = '/etc/nginx/sites-available';
	private const CONF_PATTERN_FILE = CorePath::CODE_PATTERNS_DIR . '/etc/debian/nginx.pattern';
	private ?string $hostname = null;
	private ?string $dest = null;
	private ?string $root = null;
	private ?string $phpv = null;
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
	/**
	 * Set apache conf destination folder from another controller before running this one (see Console\Install).
	 *
	 * @param string $dest
	 *        	Absolute path
	 * @return self
	 */
	public function setDestinationFolder(?string $dest): self {
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
	public function setDocumentRoot(?string $root): self {
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
	public function setPhpVersion(?string $version): self {
		if (! $version)
			return $this;
		$expV = explode ( '.', $version );
		if (count ( $expV ) < 2 || ! is_numeric ( $expV [0] ) || ! is_numeric ( $expV [1] ) || ( int ) $expV [0] !== 8) {
			$this->getSuperServices ()->getErrorHandlerService ()->catchThrowable ( new \Exception ( sprintf ( dgettext ( 'core', 'Invalid given PHP version "%s"' ), $version ) ) );
			return $this;
		}
		$this->phpv = "{$expV[0]}.{$expV[1]}";
		return $this;
	}
	protected function executeOnConsole(): void {
		if (! $this->isUserRoot ()) {
			$this->CliService->printError ( dgettext ( 'core', 'You need to be root or sudoer.' ) );
			return;
		}
		$this->CliService->printInColor ( dgettext ( 'core', '**********************' ), Cli::COLOR_LCYAN )
			->printInColor ( dgettext ( 'core', 'Basic virtual host configuration deployment.' ), Cli::COLOR_LCYAN )
			->printWarning ( dgettext ( 'core', 'Nginx must have been installed with PHP-FPM!' ) )
			->printInColor ( dgettext ( 'core', '**********************' ), Cli::COLOR_LCYAN );
		if ($this->isHelpInvoked ()) {
			$this->CliService->printInColor ( dgettext ( 'core', 'CLI arguments:' ), Cli::COLOR_LCYAN )
				->print ( dgettext ( 'core', '· "--host": server name' ), Cli::COLOR_LCYAN )
				->print ( dgettext ( 'core', '· "--root": absolute path of document root. If not passed, it will use the public folder of this application' ), Cli::COLOR_LCYAN )
				->print ( dgettext ( 'core', '· "--php-version": for example, "8.3". If not specified, it will use the PHP version of the current executed PHP binary.' ), Cli::COLOR_LCYAN )
				->print ( dgettext ( 'core', '· "--dest": destination folder where the nginx configuration will be placed (default is "/etc/nginx/sites-available")' ), Cli::COLOR_LCYAN )
				->print ( dgettext ( 'core', '· "--force": [0|1] If true, it will run all configuration processes with minimum prompts.' ) )
				->printInColor ( dgettext ( 'core', '**********************' ), Cli::COLOR_LCYAN );
			return;
		}

		$force = ( int ) $this->CliService->getOpt ( 'force' );
		try {
			// Check destination folder exists
			if (! $this->dest)
				if (! ($this->dest = trim ( ( string ) $this->CliService->getOpt ( 'dest' ) )))
					$this->dest = $force ? self::ETC_DEST_DIR : trim ( ( string ) $this->CliService->prompt ( dgettext ( 'core', 'Type Nginx "sites-available" absolute path:' ), self::ETC_DEST_DIR ) );
			if (! $this->dest)
				$this->dest = self::ETC_DEST_DIR;
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
			if (! file_exists ( self::CONF_PATTERN_FILE )) {
				$this->getSuperServices ()->getErrorHandlerService ()->catchThrowable ( new \Exception ( sprintf ( dgettext ( 'core', 'Required file is missing: %s. Exiting.' ), self::CONF_PATTERN_FILE ) ) );
				return;
			}

			$confContent = file_get_contents ( self::CONF_PATTERN_FILE );
			$confContent = str_replace ( [ 
					'/*server_name*/',
					'/*root*/',
					'/*php_version*/'
			], [ 
					$this->hostname,
					$this->root,
					$this->phpv
			], $confContent );
			if ($this->getSuperServices ()->getFilesystemService ()->createFile ( $confFilename, $confContent, Filesystem::FILE_OVERWRITE_KEEP ))
				$this->CliService->printWarning ( dgettext ( 'core', 'You must enable your new Nginx host and restart your web server. For example:' ) )->print ( sprintf ( '$ ln -s %s %s', $confFilename, dirname ( $this->dest ) . '/sites-enabled/' ) )->print ( '$ service nginx reload|restart' );
		} catch ( \Exception $Exc ) {
			$this->getSuperServices ()->getErrorHandlerService ()->catchThrowable ( $Exc );
		}
	}
}