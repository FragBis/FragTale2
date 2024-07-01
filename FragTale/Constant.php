<?php

namespace FragTale;

/**
 *
 * @author Fabrice Dant <fragtale.development@gmail.com>
 * @copyright 2024 FragTale 2 - Fabrice Dant
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-fr.txt CeCILL Licence 2.1 (French version)
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-en.txt CeCILL Licence 2.1 (English version)
 *         
 */
abstract class Constant {
	static DataCollection $Constants;
	/**
	 * Get all class constants as key/value datacollection
	 *
	 * @return DataCollection
	 */
	static function getConstants(): DataCollection {
		if (! isset ( self::$Constants ))
			self::$Constants = new DataCollection ();
		if (! self::$Constants->findByKey ( static::class ))
			self::$Constants->upsert ( static::class, (new \ReflectionClass ( static::class ))->getConstants () );
		return self::$Constants->findByKey ( static::class );
	}
	/**
	 * Get class constant value
	 *
	 * @param string $name
	 * @return NULL|string|number|boolean|\FragTale\DataCollection
	 */
	static function getConstant(?string $name) {
		return self::getConstants ()->findByKey ( $name );
	}
	/**
	 * Convert class constants collection into a JSON string
	 *
	 * @param bool $pretty
	 * @return string
	 */
	static function toJsonString(bool $pretty = false) {
		return self::getConstants ()->toJsonString ( $pretty );
	}
}