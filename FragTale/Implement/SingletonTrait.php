<?php

namespace FragTale\Implement;

/**
 *
 * @author Fabrice Dant <fragtale.development@gmail.com>
 * @copyright 2024 FragTale 2 - Fabrice Dant
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-fr.txt CeCILL Licence 2.1 (French version)
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-en.txt CeCILL Licence 2.1 (English version)
 *         
 */
trait SingletonTrait {
	private static array $singleInstances;

	/**
	 * Singletonize.
	 * Classes using this function should call it in constructor.
	 * Write this function intending to ensure an object will be set for only once.
	 *
	 * @return self
	 */
	final protected function registerSingleInstance(): ?self {
		$class = get_class ( $this );
		if (! isset ( self::$singleInstances [$class] ))
			self::$singleInstances [$class] = $this;
		return $this->getSingleInstance ( $class );
	}

	/**
	 * Only object implementing this trait can be returned.
	 * The object must have been instantiated using "instanciate" function.
	 *
	 * @param string $class
	 *        	Give the exact class name including its namespace. If empty, this will return the current object.
	 * @return self
	 */
	final public function getSingleInstance(string $class): ?self {
		return isset ( self::$singleInstances [$class] ) ? self::$singleInstances [$class] : null;
	}

	/**
	 *
	 * @param string $class
	 * @param array $constructParams
	 * @return self
	 */
	final public function createSingleInstance(string $class, array $constructParams = null): ?self {
		if (! class_exists ( $class ))
			return null;
		if (! isset ( self::$singleInstances [$class] ))
			self::$singleInstances [$class] = $constructParams ? new $class ( extract ( $constructParams ) ) : new $class ();
		return self::$singleInstances [$class];
	}

	/**
	 * Get all singleton instances.
	 * Classes using SingletonTrait
	 *
	 * @return array
	 */
	final public function getAllSingletonInstances(): array {
		return self::$singleInstances;
	}
}