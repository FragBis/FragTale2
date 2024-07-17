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
class XmlCollection extends DataCollection {
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
	 * Path to XML file.
	 * If accessing an XML file via HTTP, you must have set "allow_url_fopen = 1" into php ini file.
	 * But you'll have to export XML data into another specified file.
	 *
	 * @param string $xmlFile
	 *        	Filesystem path or URL pointing to XML content.
	 * @return self
	 */
	public function setSource(string $xmlFile): self {
		$this->source = $xmlFile;
		return $this;
	}

	/**
	 * You must have set XML source file before loading.
	 *
	 * @see XmlCollection::setSource()
	 * @return self
	 */
	public function load(): self {
		if (! file_exists ( $this->source )) {
			$message = sprintf ( dgettext ( 'core', '"%1s" file not found while importing %2s content into data collection.' ), $this->source, 'XML' );
			if (IS_CLI)
				throw new \Exception ( $message );
			else
				$this->log ( $_SERVER ['REQUEST_METHOD'] . ' ' . BASE_URL . $_SERVER ['REQUEST_URI'] . ": $message" );
			return $this->import ( null );
		}
		if ($xml = simplexml_load_file ( $this->source ))
			return $this->import ( $xml );

		$message = sprintf ( dgettext ( 'core', '"%s" is not a valid XML file.' ), $this->source );
		if (IS_CLI)
			throw new \Exception ( $message );
		else
			$this->log ( $_SERVER ['REQUEST_METHOD'] . ' ' . BASE_URL . $_SERVER ['REQUEST_URI'] . ": $message" );
		return $this->import ( null );
	}

	/**
	 * Overwrite <b>ALL</b> XML file.
	 * Make sure you have loaded all the XML file.
	 *
	 * @return self
	 */
	public function save(): self {
		return $this->exportToXmlFile ( $this->source );
	}

	/**
	 * To XML string
	 */
	function __toString() {
		return $this->toXmlString ();
	}
}