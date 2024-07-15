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
	 *        	Leave empty to use default directories. By default, new folders are created with permissions 0775
	 * @param string $filePrefix
	 * @return self
	 */
	final protected function _log(string $message, ?string $folder = null, ?string $filePrefix = null): self {
		$prepend = date ( '[Y-m-d H:i:s] ' );
		$prevmask = null;
		if (function_exists ( 'umask' ))
			$prevmask = umask ( 0 );
		try {
			if (! $folder || (! is_dir ( $folder ) && ! mkdir ( $folder, '0775', true ))) {
				$folder = CorePath::LOG_DIR . '/' . (IS_HTTP_REQUEST ? 'web' : 'cli');
				if (! is_dir ( $folder ))
					mkdir ( $folder, '0775', true );
			}
			if (function_exists ( 'umask' ) && $prevmask)
				umask ( $prevmask );

			$logFile = $folder . '/' . ( string ) $filePrefix . date ( 'Y-m' ) . '.log';
			if (is_writable ( $folder ) && ((file_exists ( $logFile ) && is_writable ( $logFile )) || ! file_exists ( $logFile )))
				file_put_contents ( $logFile, $prepend . $message . "\n", FILE_APPEND );
			else {
				$fallbackfile = CorePath::LOG_DIR . '/fallback_wrong_filemod-' . date ( 'Y-m' ) . '.log';
				if (! file_exists ( $fallbackfile ) || is_writable ( $fallbackfile )) {
					file_put_contents ( $fallbackfile, $prepend . "[Unwritable file: $logFile]\n", FILE_APPEND );
					file_put_contents ( $fallbackfile, $prepend . $message . "\n", FILE_APPEND );
				} else
					throw new \Exception ( sprintf ( 'In LoggerTrait::_log(), permission denied on %s', $fallbackfile ) );
			}
		} catch ( \Exception $Exc ) {
			if (function_exists ( 'umask' ) && $prevmask)
				umask ( $prevmask );
			throw $Exc;
		}
		return $this;
	}

	/**
	 * Default public function to log errors or events.
	 * This function can be overwritten in inherited and implemented classes.
	 *
	 * @param string $message
	 * @param string $folder
	 *        	Leave empty to use default directories. By default, new folders are created with permissions 0775
	 * @param string $filePrefix
	 * @return self
	 */
	public function log(string $message, ?string $folder = null, ?string $filePrefix = null): self {
		return $this->_log ( $message, $folder, $filePrefix );
	}
}