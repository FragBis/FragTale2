<?php

namespace FragTale\Service;

use FragTale\Implement\AbstractService;
use FragTale\Constant\MessageType;

/**
 *
 * @author Fabrice Dant <fragtale.development@gmail.com>
 * @copyright 2024 FragTale 2 - Fabrice Dant
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-fr.txt CeCILL Licence 2.1 (French version)
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-en.txt CeCILL Licence 2.1 (English version)
 *         
 */
class Filesystem extends AbstractService {
	const FILE_OVERWRITE_KEEP = 0;
	const FILE_OVERWRITE_FORCE = 1;
	const FILE_OVERWRITE_PROMPT = 2;

	/**
	 * Returns true if dir already exists or if mkdir successfully created new folder
	 *
	 * @param string $dir
	 * @param bool $recursively
	 * @return bool
	 */
	public function createDir(string $dir, bool $recursively = false): bool {
		$dir = rtrim ( $dir, '/' );
		$success = true;
		try {
			if ($lastSlashPos = strrpos ( $dir, '/' )) {
				$parentDir = substr ( $dir, 0, $lastSlashPos );
				if (! is_dir ( $parentDir )) {
					if ($recursively) {
						if (! $this->createDir ( $parentDir, true ))
							return false;
					} else {
						$message = sprintf ( dgettext ( 'core', 'Could not create folder: "%s" because parent folder does not exist yet. You should create it before.' ), $dir );
						if (IS_CLI)
							$this->getSuperServices ()->getCliService ()->printInColor ( $message, Cli::COLOR_RED );
						else
							$this->getSuperServices ()->getFrontMessageService ()->add ( $message, MessageType::WARNING );
						return false;
					}
				}
			}
			if (is_dir ( $dir )) {
				$message = sprintf ( dgettext ( 'core', 'Folder: "%s" already exists' ), $dir );
				$typeOrColor = IS_CLI ? Cli::COLOR_ORANGE : MessageType::WARNING;
			} elseif (mkdir ( $dir )) {
				$message = sprintf ( dgettext ( 'core', 'Created folder: "%s"' ), $dir );
				$typeOrColor = IS_CLI ? Cli::COLOR_GREEN : MessageType::SUCCESS;
			} else {
				$message = sprintf ( dgettext ( 'core', 'Could not create folder: "%s"' ), $dir );
				$typeOrColor = IS_CLI ? Cli::COLOR_RED : MessageType::ERROR;
				$success = false;
			}
		} catch ( \Exception $Exc ) {
			$message = $Exc->getMessage ();
			$typeOrColor = IS_CLI ? Cli::COLOR_RED : MessageType::ERROR;
			$success = false;
		}
		if (IS_CLI)
			$this->getSuperServices ()->getCliService ()->printInColor ( $message, $typeOrColor );
		else
			$this->getSuperServices ()->getFrontMessageService ()->add ( $message, $typeOrColor );
		return $success;
	}

	/**
	 * Returns true if file already exists or if successfully created or overwriten
	 *
	 * @param string $filename
	 * @param string $content
	 * @param int $overwriteMode
	 * @return bool
	 */
	public function createFile(string $filename, string $content, int $overwriteMode = self::FILE_OVERWRITE_KEEP): bool {
		$success = true;
		$writeFile = false;
		$fileShortName = trim ( substr ( $filename, ( int ) strrpos ( '/', $filename ) ), '/' );
		if (file_exists ( $filename )) {
			if ($overwriteMode === self::FILE_OVERWRITE_FORCE)
				$writeFile = true;
			elseif ($overwriteMode === self::FILE_OVERWRITE_PROMPT && IS_CLI)
				$writeFile = $this->getSuperServices ()->getLocalizeService ()->meansYes ( $this->getSuperServices ()
					->getCliService ()
					->prompt ( dgettext ( 'core', 'File already exists. Overwrite it? [yn]' ) ) );
			else {
				$message = sprintf ( dgettext ( 'core', 'File "%s" already exists' ), IS_CLI ? $filename : $fileShortName );
				$typeOrColor = IS_CLI ? Cli::COLOR_ORANGE : MessageType::WARNING;
			}
		} else
			$writeFile = true;

		if ($writeFile) {
			try {
				if (file_put_contents ( $filename, $content )) {
					$message = sprintf ( dgettext ( 'core', 'File "%s" successfully written' ), IS_CLI ? $filename : $fileShortName );
					$typeOrColor = IS_CLI ? Cli::COLOR_GREEN : MessageType::SUCCESS;
				} else {
					$message = sprintf ( dgettext ( 'core', 'Unabled to create file "%s"' ), IS_CLI ? $filename : $fileShortName );
					$typeOrColor = IS_CLI ? Cli::COLOR_RED : MessageType::ERROR;
					$success = false;
				}
			} catch ( \Exception $Exc ) {
				$message = $Exc->getMessage ();
				$typeOrColor = IS_CLI ? Cli::COLOR_RED : MessageType::ERROR;
				$success = false;
			}
		}

		if (IS_CLI)
			$this->getSuperServices ()->getCliService ()->printInColor ( $message, $typeOrColor );
		else
			$this->getSuperServices ()->getFrontMessageService ()->add ( $message, $typeOrColor );
		return $success;
	}
}