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
		$dir = trim ( $dir );
		$success = true;
		try {
			$parentDir = dirname ( $dir );
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
		$filename = trim ( $filename );
		$fileShortName = basename ( $filename );
		if (file_exists ( $filename )) {
			if ($overwriteMode === self::FILE_OVERWRITE_FORCE)
				$writeFile = true;
			elseif ($overwriteMode === self::FILE_OVERWRITE_PROMPT && IS_CLI) {
				$this->getSuperServices ()->getCliService ()->printWarning ( sprintf ( dgettext ( 'core', 'File "%s" already exists.' ), $filename ) );
				$answer = $this->getSuperServices ()->getCliService ()->prompt ( dgettext ( 'core', 'Overwrite file? [Yn]' ) );
				$writeFile = $this->getSuperServices ()->getLocalizeService ()->meansYes ( $answer );
			} else {
				$message = sprintf ( dgettext ( 'core', 'File "%s" already exists' ), IS_CLI ? $filename : $fileShortName );
				$typeOrColor = IS_CLI ? Cli::COLOR_ORANGE : MessageType::WARNING;
			}
		} else
			$writeFile = true;

		if ($writeFile) {
			try {
				$dir = dirname ( $filename );
				if (! is_dir ( $dir )) {
					$createDir = ($overwriteMode === self::FILE_OVERWRITE_FORCE);
					if (IS_CLI && ! $createDir) {
						$this->getSuperServices ()->getCliService ()->printWarning ( sprintf ( dgettext ( 'core', 'Folder "%s" does not exist yet.' ), $dir ) );
						$answer = $this->getSuperServices ()->getCliService ()->prompt ( dgettext ( 'core', 'Create folder? [Yn]' ), dgettext ( 'core', 'y {means yes}' ) );
						$createDir = $this->getSuperServices ()->getLocalizeService ()->meansYes ( $answer );
					}

					if (! $createDir || ! $this->createDir ( $dir ))
						return false;
				}
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