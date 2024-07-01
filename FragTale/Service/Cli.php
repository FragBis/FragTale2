<?php

namespace FragTale\Service;

use FragTale\Implement\AbstractService;
use FragTale\DataCollection;

/**
 *
 * @author Fabrice Dant <fragtale.development@gmail.com>
 * @copyright 2024 FragTale 2 - Fabrice Dant
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-fr.txt CeCILL Licence 2.1 (French version)
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-en.txt CeCILL Licence 2.1 (English version)
 *         
 */
class Cli extends AbstractService {
	private DataCollection $Opts;
	private bool $isTimeDisplayedByDefault = true;
	private bool $isPrintedInColor = true;
	const COLOR_RED = "\033[0;31m";
	const COLOR_WHITE = "\033[0m";
	const COLOR_CYAN = "\033[0;36m";
	const COLOR_LCYAN = "\033[1;36m";
	const COLOR_ORANGE = "\033[0;33m";
	const COLOR_GREEN = "\033[1;32m";
	const COLOR_YELLOW = "\033[1;33m";
	const COLOR_BLUE = "\033[1;34m";

	/**
	 *
	 * @return bool
	 */
	final function isTimeDisplayedByDefault(): bool {
		return $this->isTimeDisplayedByDefault;
	}

	/**
	 *
	 * @param bool $isTimeDisplayed
	 */
	final public function setIsTimeDisplayedByDefault(bool $isTimeDisplayed): self {
		$this->isTimeDisplayedByDefault = $isTimeDisplayed;
		return $this;
	}

	/**
	 *
	 * @return bool
	 */
	final public function isPrintedInColor(): bool {
		return $this->isPrintedInColor;
	}

	/**
	 * If false, all colors will be removed
	 *
	 * @param bool $isPrintedInColor
	 */
	final public function forceColorPrinting(bool $isPrintedInColor): self {
		$this->isPrintedInColor = $isPrintedInColor;
		return $this;
	}

	/**
	 *
	 * @param string $opt
	 *        	The Cli option name must be prefixed by one or two dashes. For example: --help or -h
	 *        	Mind that your program must handle any option passed while calling a controller.
	 * @return string Argument value
	 */
	final public function getOpt($opt): ?string {
		return $this->getOptions ()->findByKey ( $opt );
	}

	/**
	 * Returns all arguments passed in CLI.
	 *
	 * @return array
	 */
	final public function getOptions(): ?DataCollection {
		if (! isset ( $this->Opts ))
			$this->setOptions ();
		return $this->Opts;
	}

	/**
	 *
	 * @return bool
	 */
	private function setOptions(): self {
		global $argv;
		$this->Opts = (new DataCollection ())->upsert ( '_route_index', null );
		if (empty ( $argv ))
			return $this;
		foreach ( $argv as $i => $option ) {
			if (! $i)
				continue;
			$option = ( string ) $option;
			if ($i === 1) {
				$this->Opts->upsert ( '_route_index', trim ( $option ) );
				continue;
			}
			if (substr ( $option, 0, 2 ) === '--') {
				// Get long option
				if (! ($opt = trim ( substr ( $option, 2 ) )))
					continue;
				if ($eqPos = strpos ( $opt, '=' )) {
					if ($key = trim ( substr ( $opt, 0, $eqPos ) ))
						$this->Opts->upsert ( $key, trim ( substr ( $opt, $eqPos + 1 ) ) );
				} elseif (isset ( $argv [$i + 1] ) && strpos ( ( string ) $argv [$i + 1], '-' ) !== 0)
					$this->Opts->upsert ( $opt, trim ( $argv [$i + 1] ) );
				else
					$this->Opts->upsert ( $opt, true );
			} elseif (substr ( $option, 0, 1 ) === '-') {
				// Get short option (first letter after dash)
				if (! ($key = trim ( substr ( $option, 1, 1 ) )))
					continue;
				$value = trim ( substr ( $option, 2 ) );
				if (strpos ( $value, '=' ) === 0)
					$value = substr ( $value, 1 );
				if ($value)
					$this->Opts->upsert ( $key, $value );
				elseif ((isset ( $argv [$i + 1] ) && strpos ( $argv [$i + 1], '-' ) !== 0))
					$this->Opts->upsert ( $key, trim ( ( string ) $argv [$i + 1] ) );
				else
					$this->Opts->upsert ( $key, true );
			}
		}
		return $this;
	}

	/**
	 * Echoes the message into the console.
	 * The message is prepended by the date/time (usefull if you log your output into a file)
	 *
	 * @param string $message
	 * @param boolean $breakLine
	 *        	If true, this function will append a break line after the message (default: true)
	 * @param boolean $isTimeDisplayed
	 *        	Default is true: prepend time before printing message
	 * @return self
	 */
	public function print(?string $message, bool $breakLine = true, ?bool $isTimeDisplayed = null): self {
		if ($isTimeDisplayed === null)
			$isTimeDisplayed = $this->isTimeDisplayedByDefault;
		$fullmsg = $message . ($breakLine ? "\n" : '');
		if (IS_CLI)
			echo ($isTimeDisplayed ? $this->getCurrentTimeFormat () : '') . ($this->isPrintedInColor ? self::COLOR_WHITE : '') . $fullmsg;
		else
			$this->log ( $fullmsg, null, 'web2cli_' );
		return $this;
	}

	/**
	 * Note that passing false to "Cli::setIsPrintedInColor(true/false)" will prevent this function to print any color.
	 * That is commonly the case to output logs via CRON jobs. You can specify to prevent color printing passing cli option "--no-color"
	 *
	 * @param string $message
	 * @param string $color
	 *        	In: [ Cli::COLOR_BLUE, Cli::COLOR_CYAN, Cli::COLOR_GREEN, Cli::COLOR_LCYAN, Cli::COLOR_ORANGE, Cli::COLOR_RED, Cli::COLOR_WHITE, Cli::COLOR_YELLOW ]
	 * @param bool $breakLine
	 * @param bool $isTimeDisplayed
	 * @return self
	 */
	public function printInColor(string $message, string $color = self::COLOR_WHITE, bool $breakLine = true, ?bool $isTimeDisplayed = null): self {
		return $this->print ( ($this->isPrintedInColor ? $color : '') . $message . ($this->isPrintedInColor ? self::COLOR_WHITE : ''), $breakLine, $isTimeDisplayed );
	}

	/**
	 * Note that passing false to "Cli::setIsPrintedInColor(true/false)" will prevent this function to print any color.
	 * That is commonly the case to output logs via CRON jobs. You can specify to prevent color printing passing cli option "--no-color"
	 *
	 * @param string $message
	 * @param boolean $breakLine
	 *        	If true, this function will append a break line after the message (default: true)
	 * @param boolean $isTimeDisplayed
	 *        	Default is true: prepend time before printing message
	 * @return self
	 */
	public function printError(string $message, bool $breakLine = true, ?bool $isTimeDisplayed = null): self {
		return $this->printInColor ( $message, self::COLOR_RED, $breakLine, $isTimeDisplayed );
	}

	/**
	 *
	 * @param string $message
	 * @param bool $breakLine
	 * @param bool $isTimeDisplayed
	 * @return self
	 */
	public function printSuccess(string $message, bool $breakLine = true, ?bool $isTimeDisplayed = null): self {
		return $this->printInColor ( $message, self::COLOR_GREEN, $breakLine, $isTimeDisplayed );
	}

	/**
	 *
	 * @param string $message
	 * @param bool $breakLine
	 * @param bool $isTimeDisplayed
	 * @return self
	 */
	public function printWarning(string $message, bool $breakLine = true, ?bool $isTimeDisplayed = null): self {
		return $this->printInColor ( $message, self::COLOR_ORANGE, $breakLine, $isTimeDisplayed );
	}

	/**
	 *
	 * @param string $message
	 * @param bool $breakLine
	 * @param bool $isTimeDisplayed
	 * @return self
	 */
	public function printNoColor(string $message, bool $breakLine = true, ?bool $isTimeDisplayed = null): self {
		return $this->print ( $message, $breakLine, $isTimeDisplayed, false );
	}

	/**
	 * CLI dialogue
	 *
	 * @param string $message
	 *        	Dialogue message
	 * @param string $defaultValue
	 *        	If specified, function will return this default value if user answer is emppty
	 * @param bool $isTimeDisplayed
	 * @return string|NULL
	 */
	public function prompt(string $message, ?string $defaultValue = '', ?bool $isTimeDisplayed = null): ?string {
		if (! IS_CLI)
			return null;
		if (! $this->isStdoutInteractive ()) {
			$this->printError ( $message );
			return null;
		}
		if ($isTimeDisplayed === null)
			$isTimeDisplayed = $this->isTimeDisplayedByDefault ();
		if ($isTimeDisplayed)
			echo $this->getCurrentTimeFormat ();
		echo self::COLOR_BLUE . $message . self::COLOR_WHITE . "\n";
		$response = trim ( ( string ) readline ( ($defaultValue ? "($defaultValue) " : '') . "> " ) );
		if ($response === '' && $defaultValue)
			return trim ( $defaultValue );
		return $response;
	}

	/**
	 *
	 * @return string
	 */
	public function getCurrentTimeFormat(): string {
		return $this->isPrintedInColor ? self::COLOR_CYAN . date ( dgettext ( 'core', '[Y-m-d H:i:s]' ) ) . ' ' . self::COLOR_WHITE : date ( dgettext ( 'core', '[Y-m-d H:i:s]' ) ) . ' ';
	}

	/**
	 *
	 * @param string $prependMsg
	 *        	(optional)
	 */
	public function printMemUsage($prependMsg = ''): self {
		return $this->print ( self::COLOR_LCYAN . '[MEMORY INFO] ' . self::COLOR_WHITE . $prependMsg . ' | Usage (current): ' . round ( memory_get_usage () / 1024 / 1024, 2 ) . 'Mo | Peak: ' . round ( memory_get_peak_usage () / 1024 / 1024, 2 ) . 'Mo' );
	}

	/**
	 * Verify if CLI is in interactive mode.
	 * It is useful to know it while using "prompt" (readline) that does not work for example on a CRON thread.
	 *
	 * @return boolean
	 */
	public function isStdoutInteractive() {
		return IS_CLI && function_exists ( 'posix_isatty' ) ? posix_isatty ( STDOUT ) : false;
	}
}