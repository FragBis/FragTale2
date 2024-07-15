<?php

namespace FragTale\Implement;

use FragTale\Constant\Setup\CorePath;

/**
 *
 * @author Fabrice Dant <fragtale.development@gmail.com>
 * @copyright 2024 FragTale 2 - Fabrice Dant
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-fr.txt CeCILL Licence 2.1 (French version)
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-en.txt CeCILL Licence 2.1 (English version)
 *         
 */
trait LoggerTrait {

	/**
	 * Protected base function to log errors or events.
	 * Unchangeable.
	 *
	 * @param string $message
	 * @param string $folder
	 * @param string $filePrefix
	 * @return self
	 */
	final protected function _log(string $message, ?string $folder = null, ?string $filePrefix = null): self {
		$prepend = date ( '[Y-m-d H:i:s] ' );
		$oldmask = umask ( 0 );
		if (! $folder || (! is_dir ( $folder ) && ! mkdir ( $folder, '0775', true )))
			$folder = CorePath::LOG_DIR;

		$filePrefix = IS_CLI ? 'cli_' . $filePrefix : 'http_' . $filePrefix;
		$logFile = $folder . '/' . $filePrefix . date ( 'Y-m' ) . '.log';
		try {
			if (is_writable ( $folder ) && ((file_exists ( $logFile ) && is_writable ( $logFile )) || ! file_exists ( $logFile )))
				file_put_contents ( $logFile, $prepend . $message . "\n", FILE_APPEND );
			else {
				$fallbackfile = CorePath::LOG_DIR . '/fallback_wrong_filemod-' . date ( 'Y-m' ) . '.log';
				if (! file_exists ( $fallbackfile ) || is_writable ( $fallbackfile )) {
					file_put_contents ( $fallbackfile, $prepend . "[Unwritable file: $logFile]\n", FILE_APPEND );
					file_put_contents ( $fallbackfile, $prepend . $message . "\n", FILE_APPEND );
				}
			}
		} catch ( \Exception $Exc ) {
			throw $Exc;
		}
		umask ( $oldmask );
		return $this;
	}

	/**
	 * Default public function to log errors or events.
	 * Can be overwritten.
	 *
	 * @param string $message
	 * @param string $folder
	 * @param string $filePrefix
	 * @return self
	 */
	public function log(string $message, ?string $folder = null, ?string $filePrefix = null): self {
		return $this->_log ( $message, $folder, $filePrefix );
	}
}