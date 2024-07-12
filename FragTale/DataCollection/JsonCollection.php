<?php

namespace FragTale\DataCollection;

use FragTale\DataCollection;

/**
 *
 * @author Fabrice Dant <fragtale.development@gmail.com>
 * @copyright 2024 FragTale 2 - Fabrice Dant
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-fr.txt CeCILL Licence 2.1 (French version)
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-en.txt CeCILL Licence 2.1 (English version)
 *         
 */
class JsonCollection extends DataCollection {
	private ?string $source = null;

	/**
	 * Get JSON source file path
	 *
	 * @return string
	 */
	public function getSource(): ?string {
		return $this->source;
	}

	/**
	 * Path to JSON file.
	 * If accessing a JSON file via HTTP, you must have set "allow_url_fopen = 1" into php ini file.
	 * But you'll have to export JSON data into another specified file.
	 *
	 * @param string $jsonFile
	 *        	Filesystem path or URL pointing to JSON content.
	 * @return self
	 */
	public function setSource(string $jsonFile): self {
		$this->source = $jsonFile;
		return $this;
	}

	/**
	 * Overwrite <b>ALL</b> JSON file.
	 * Make sure you have loaded all the JSON file.
	 *
	 * @return self
	 */
	public function save(): self {
		return $this->exportToJsonFile ( $this->source, true );
	}

	/**
	 * You must have set JSON source file before loading.
	 *
	 * @see JsonCollection::setSource()
	 * @return self
	 */
	public function load(): self {
		if (! file_exists ( $this->source )) {
			$message = sprintf ( dgettext ( 'core', '"%1s" file not found while importing %2s content into data collection.' ), $this->source, 'JSON' );
			if (IS_CLI)
				throw new \Exception ( $message );
			else
				$this->log ( $message );
			return $this->import ( null );
		}
		return $this->import ( json_decode ( file_get_contents ( $this->source ), true ) );
	}
}