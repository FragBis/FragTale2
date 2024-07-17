<?php

namespace FragTale;

use FragTale\Constant\Setup\CorePath;
use FragTale\Implement\LoggerTrait;
use FragTale\Implement\AbstractService;

/**
 * FragTale 2.1 PHP Open Source Framework
 *
 * Getting the application params
 *
 * @author Fabrice Dant <fragtale.development@gmail.com>
 * @copyright 2024 FragTale 2 - Fabrice Dant
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-fr.txt CeCILL Licence 2.1 (French version)
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-en.txt CeCILL Licence 2.1 (English version)
 *         
 */
class Application {
	use LoggerTrait;
	private static Service $Service;

	/**
	 * On construct, Application set default locales & gettext configuration
	 */
	function __construct() {
		self::$Service = new Service ();
		$ConfService = $this->getSuperServices ()->getConfigurationService ();
		$ProjService = $this->getSuperServices ()->getProjectService ();
		// Define locale & encoding
		// for CLI app
		if (IS_CLI && file_exists ( CorePath::APP_SETTINGS_FILE )) {
			$AppSettings = $ConfService->getCliApplicationSettings ();
			$locale = $AppSettings->findByKey ( 'locale' );
			$encoding = $AppSettings->findByKey ( 'encoding' );
			$ConfService->setLocale ( $locale, $encoding )->setGettext ( 'core' );
		}
		// for project
		if ($ProjService->getEnvSettings ()) {
			// Load locale from project settings
			if ($locale = $ProjService->getEnvSettings ()->findByKey ( 'locale' )) {
				if ($ProjService->getEnvSettings ()->findByKey ( 'encoding' ))
					$encoding = $ProjService->getEnvSettings ()->findByKey ( 'encoding' );
				$localeDir = $ProjService->getResourcesDir () . '/locales';
				$ConfService->setLocale ( $locale, $encoding )->setGettext ( 'core' )->setGettext ( 'messages', $localeDir );
			}
		}
	}

	/**
	 * Get the super service
	 *
	 * @return Service|NULL
	 */
	public function getSuperServices(): Service {
		return self::$Service;
	}

	/**
	 * Get instance of "Service" placed on root of your project folder: Project/{YourProjectName}/Service.php
	 * This file must have been created at the same time than your project.
	 * This file is required.
	 *
	 * @return Service
	 */
	public function getCustomServices(): ?Service {
		return $this->getSuperServices ()->getCustomServices ();
	}

	/**
	 * Get Service singleton registered in the list of instanciated Services.
	 * All core services are singletons.
	 * Service object has no setter in controller, because it cannot be set anywhere else during application run.
	 *
	 * @param string $class
	 * @param array $constructParams
	 * @return AbstractService|null
	 */
	public function getService(string $class = null, ?array $constructParams = null): ?AbstractService {
		return $this->getSuperServices ()->getService ( $class, $constructParams );
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

	/**
	 * Returns class name.
	 *
	 * @return string
	 */
	function __toString(): string {
		return static::class;
	}
}