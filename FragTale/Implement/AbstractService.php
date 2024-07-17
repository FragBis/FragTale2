<?php

namespace FragTale\Implement;

use FragTale\Service;

/**
 *
 * @author Fabrice Dant <fragtale.development@gmail.com>
 * @copyright 2024 FragTale 2 - Fabrice Dant
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-fr.txt CeCILL Licence 2.1 (French version)
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-en.txt CeCILL Licence 2.1 (English version)
 *         
 */
class AbstractService {
	use SingletonTrait, LoggerTrait;

	/**
	 * Mind that if you override this constructor in inherited classes, you should call function "registerThisSingleInstance()"
	 * intending to list this object as a singleton.
	 * Or call "parent::_construct()"
	 */
	function __construct() {
		// Singletonize
		$this->registerSingleInstance ();
	}

	/**
	 *
	 * @return string
	 */
	function __toString(): string {
		return static::class;
	}

	/**
	 * Get Service singleton registered in the list of instanciated Services.
	 * All core services are singletons.
	 * Service object has no setter in controller, because it cannot be set anywhere else during application run.
	 *
	 * @param string $class
	 * @param array $constructParams
	 * @return Service|null
	 */
	public function getService(string $class = null, ?array $constructParams = null): ?AbstractService {
		if (is_subclass_of ( $class, AbstractService::class, true ))
			return $this->getSingleInstance ( $class ) ? $this->getSingleInstance ( $class ) : $this->createSingleInstance ( $class, $constructParams );
		return null;
	}

	/**
	 *
	 * @return Service
	 */
	public function getSuperServices(): Service {
		return $this->getSingleInstance ( Service::class );
	}

	/**
	 * Get instance of "Service" placed on root of your project folder: Project/{YourProjectName}/Service.php
	 * This file must have been created at the same time than your project.
	 * This file is required and gives functions that returned custom services created for the project.
	 *
	 * @return Service
	 */
	public function getCustomServices(): ?Service {
		$superClassName = 'Project\\' . $this->getSuperServices ()->getProjectService ()->getName () . '\\Service';
		if (class_exists ( $superClassName ))
			return $this->createSingleInstance ( $superClassName );
		return null;
	}

	/**
	 * Default log directory is logs/{project_name}/{date `Ym`}
	 *
	 * @param string $message
	 * @param string $folderSuffix
	 * @param string $filePrefix
	 * @return self
	 */
	public function log(string $message, ?string $folderSuffix = null, ?string $filePrefix = null): self {
		if (! $folderSuffix)
			$folderSuffix = $this->getSuperServices ()->getProjectService ()->getName ();
		return $this->_log ( $message, $folderSuffix, $filePrefix );
	}
}