<?php

namespace FragTale\Service\Project;

use FragTale\Service\Project;

/**
 * Can switch project from CLI
 *
 * @author Fabrice Dant <fragtale.development@gmail.com>
 * @copyright 2024 FragTale 2 - Fabrice Dant
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-fr.txt CeCILL Licence 2.1 (French version)
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-en.txt CeCILL Licence 2.1 (English version)
 *         
 */
class CliPurpose extends Project {
	function __construct() {
		if (! IS_CLI)
			throw new \Exception ( dgettext ( 'core', 'This class is for CLI purpose only!' ) );
		parent::__construct ();
	}
	/**
	 * Override the project name.
	 * That allows to switch project in console only.
	 *
	 * @param string $name
	 * @return self
	 */
	public function setName(string $name): self {
		// Unset all props to be reloaded
		$thisReflection = new \ReflectionClass ( $this );
		foreach ( $thisReflection->getProperties () as $Prop ) {
			$key = $Prop->name;
			unset ( $this->$key );
		}

		// set or reset name
		$this->name = $name;

		// Include locales
		if ($locale = $this->getEnvSettings ()->findByKey ( 'locale' )) {
			if ($this->getEnvSettings ()->findByKey ( 'encoding' ))
				$encoding = $this->getEnvSettings ()->findByKey ( 'encoding' );
			$localeDir = $this->getResourcesDir () . '/locales';
			$this->getSuperServices ()
				->getConfigurationService ()
				->setLocale ( $locale, $encoding )
				->setGettext ( 'core' )
				->setGettext ( 'messages', $localeDir );
		}
		return $this;
	}
}