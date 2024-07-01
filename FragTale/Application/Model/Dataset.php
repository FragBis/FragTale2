<?php

namespace FragTale\Application\Model;

use FragTale\DataCollection;

/**
 * This class is used to import or update defined data to corresponding SQL table.
 * That might be set to primary tables having few data. Example: table "role"
 *
 * @author Fabrice Dant <fragtale.development@gmail.com>
 * @copyright 2024 FragTale 2 - Fabrice Dant
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-fr.txt CeCILL Licence 2.1 (French version)
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-en.txt CeCILL Licence 2.1 (English version)
 *         
 */
abstract class Dataset {
	/**
	 * Must contain all values to insert or update into the database.
	 *
	 * @var array
	 */
	protected array $definition;
	function __construct() {
		$this->definition = [ ];
	}

	/**
	 * Contains all values to insert or update into the database.
	 * It is a fixed dataset.
	 *
	 * @return DataCollection
	 */
	public function getDefinition(): DataCollection {
		return new DataCollection ( $this->definition );
	}
}