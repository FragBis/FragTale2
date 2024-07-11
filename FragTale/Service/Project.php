<?php

namespace FragTale\Service;

use FragTale\Implement\AbstractService;
use FragTale\DataCollection;
use FragTale\Constant\Setup\CustomProjectPattern;
use FragTale\Constant\Setup\Locale;
use FragTale\Constant\Setup\Database\Driver;
use FragTale\Constant\MessageType;
use FragTale\Constant\TemplateFormat;
use FragTale\Constant\Setup\CorePath;

/**
 *
 * @author Fabrice Dant <fragtale.development@gmail.com>
 * @copyright 2024 FragTale 2 - Fabrice Dant
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-fr.txt CeCILL Licence 2.1 (French version)
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-en.txt CeCILL Licence 2.1 (English version)
 *         
 */
class Project extends AbstractService {

	/**
	 * Collection containing project parameters.
	 *
	 * @var DataCollection
	 */
	protected DataCollection $Settings;

	/**
	 * Collection containing project parameters for current environment (production, staging, development etc.).
	 *
	 * @var DataCollection
	 */
	protected DataCollection $EnvSettings;

	/**
	 * Collection containing database credentials
	 *
	 * @var DataCollection
	 */
	protected DataCollection $DatabasesSettings;

	/**
	 * Project parameters
	 *
	 * @var DataCollection
	 */
	protected DataCollection $Parameters;

	/**
	 * Project name
	 *
	 * @var string
	 */
	protected string $name;

	/**
	 * Environnement used (production, development...)
	 *
	 * @var string
	 */
	protected string $env;

	/**
	 *
	 * @var string
	 */
	protected string $defaultSqlConnectorID;

	/**
	 *
	 * @var string
	 */
	protected string $defaultMongoConnectorID;

	/**
	 *
	 * @return string
	 */
	final public function getBaseNamespace(): string {
		return sprintf ( CustomProjectPattern::NAMESPACE, $this->getName () );
	}

	/**
	 *
	 * @return string
	 */
	final public function getSqlModelNamespace(): string {
		return sprintf ( CustomProjectPattern::SQL_MODEL_NAMESPACE, $this->getName () );
	}

	/**
	 *
	 * @return string
	 */
	final public function getSqlModelDir(): string {
		return sprintf ( CustomProjectPattern::SQL_MODEL_DIR, $this->getName () );
	}

	/**
	 *
	 * @return string
	 */
	final public function getBaseDir(): string {
		return sprintf ( CustomProjectPattern::PATH, $this->getName () );
	}

	/**
	 *
	 * @return string
	 */
	final public function getBlockControllerDir(): string {
		return sprintf ( CustomProjectPattern::BLOCK_CONTROLLER_DIR, $this->getName () );
	}

	/**
	 *
	 * @return string
	 */
	final public function getCliControllerDir(): string {
		return sprintf ( CustomProjectPattern::CLI_CONTROLLER_DIR, $this->getName () );
	}

	/**
	 *
	 * @return string
	 */
	final public function getControllerDir(): string {
		return sprintf ( CustomProjectPattern::CONTROLLER_DIR, $this->getName () );
	}

	/**
	 *
	 * @return string
	 */
	final public function getDefaultLogsDir(): string {
		return sprintf ( CustomProjectPattern::LOGS_DIR, $this->getName () );
	}

	/**
	 *
	 * @return string
	 */
	final public function getModelDir(): string {
		return sprintf ( CustomProjectPattern::MODEL_DIR, $this->getName () );
	}

	/**
	 *
	 * @return string
	 */
	final public function getResourcesDir(): string {
		return sprintf ( CustomProjectPattern::RESOURCES_DIR, $this->getName () );
	}

	/**
	 *
	 * @return string
	 */
	final public function getConfigurationDir(): string {
		return sprintf ( CustomProjectPattern::CONFIGURATION_DIR, $this->getName () );
	}

	/**
	 *
	 * @return string
	 */
	final public function getTemplatesDir(): string {
		return sprintf ( CustomProjectPattern::TEMPLATES_DIR, $this->getName () );
	}

	/**
	 *
	 * @return string
	 */
	final public function getLayoutsDir(): string {
		return sprintf ( CustomProjectPattern::LAYOUTS_DIR, $this->getName () );
	}

	/**
	 *
	 * @return string
	 */
	final public function getViewsDir(): string {
		return sprintf ( CustomProjectPattern::VIEWS_DIR, $this->getName () );
	}

	/**
	 *
	 * @return string
	 */
	final public function getWebControllerDir(): string {
		return sprintf ( CustomProjectPattern::WEB_CONTROLLER_DIR, $this->getName () );
	}

	/**
	 *
	 * @return string
	 */
	final public function getBlocksDir(): string {
		return sprintf ( CustomProjectPattern::BLOCKS_DIR, $this->getName () );
	}

	/**
	 *
	 * @return string
	 */
	final public function getBaseControllerNamespace(): string {
		return sprintf ( CustomProjectPattern::CONTROLLER_NAMESPACE, $this->getName () );
	}

	/**
	 *
	 * @return string
	 */
	final public function getBaseWebControllerNamespace(): string {
		return sprintf ( CustomProjectPattern::WEB_CONTROLLER_NAMESPACE, $this->getName () );
	}

	/**
	 *
	 * @return string
	 */
	final public function getBaseBlockControllerNamespace(): string {
		return sprintf ( CustomProjectPattern::BLOCK_CONTROLLER_NAMESPACE, $this->getName () );
	}

	/**
	 *
	 * @return string
	 */
	final public function getBaseCliControllerNamespace(): string {
		return sprintf ( CustomProjectPattern::CLI_CONTROLLER_NAMESPACE, $this->getName () );
	}

	/**
	 * Collection containing full project configuration from file "Project/{projectName}/resources/configuration/project.json"
	 *
	 * @return DataCollection
	 */
	final public function getSettings(): DataCollection {
		if (! isset ( $this->Settings )) {
			$projectSettingsFile = sprintf ( CustomProjectPattern::SETTINGS_FILE, $this->getName () );
			if (file_exists ( $projectSettingsFile ))
				$this->Settings = new DataCollection ( json_decode ( file_get_contents ( $projectSettingsFile ), true ) );
			else {
				if ($this->getName ()) {
					$message = sprintf ( dgettext ( 'core', 'Your project "%s" does not contain a "project.json" configuration file.' ), $this->getName () );
					if (IS_CLI)
						$this->getSuperServices ()->getCliService ()->printError ( $message );
					else
						$this->getSuperServices ()->getFrontMessageService ()->add ( $message, MessageType::FATAL_ERROR );
				}
				$this->Settings = new DataCollection ();
			}
		}
		return $this->Settings;
	}

	/**
	 * Return the current environment name (ID) as defined in "project.json" configuration file.
	 *
	 * @return string|NULL
	 */
	public function getEnv(): string {
		if (! isset ( $this->env )) {
			if (IS_CLI && ($env = $this->getSuperServices ()->getCliService ()->getOpt ( 'env' ))) {
				$this->getSuperServices ()->getCliService ()->printWarning ( sprintf ( dgettext ( 'core', "Using env '%s'" ), $env ) );
				$this->env = $env;
			} else {
				$Environments = $this->getSettings ()->findByKey ( 'environments' );
				if ($Environments instanceof DataCollection) {
					$host = $this->getSuperServices ()->getHttpServerService ()->getHost ();
					if ($env = $Environments->findByKey ( $host )) {
						$continue = true;
						while ( $continue ) {
							$this->env = $env;
							$env = $Environments->findByKey ( $env );
							$continue = is_string ( $env );
						}
					} elseif ($env = $Environments->findByKey ( 'default' ))
						$this->env = $env;
					else
						$this->env = $Environments->key ( 0 ) ? $Environments->key ( 0 ) : '';
				} else
					$this->env = '';
			}
		}
		return $this->env;
	}

	/**
	 * Returns the corresponding environment settings for running host and working project.
	 * If not properly defined, it returns "default" one, or at least, an empty DataCollection.
	 *
	 * @return DataCollection
	 */
	public function getEnvSettings(): DataCollection {
		if (! isset ( $this->EnvSettings )) {
			$Environments = $this->getSettings ()->findByKey ( 'environments' );
			$CurEnvSettings = $Environments instanceof DataCollection ? $Environments->findByKey ( $this->getEnv () ) : null;
			$this->EnvSettings = $CurEnvSettings instanceof DataCollection ? $CurEnvSettings : new DataCollection ();
		}
		return $this->EnvSettings;
	}

	/**
	 * Returns the full collection of custom parameters for given project and given environment
	 *
	 * @return DataCollection
	 */
	public function getCustomParameters(): DataCollection {
		if (! isset ( $this->Parameters )) {
			$parameters = $this->getSettings ()->findByKey ( 'parameters' ) instanceof DataCollection ? $this->getSettings ()->findByKey ( 'parameters' )->getData ( true ) : [ ];
			if ($this->getEnvSettings ()->findByKey ( 'parameters' ) instanceof DataCollection) {
				foreach ( $this->getEnvSettings ()->findByKey ( 'parameters' )->getData ( true ) as $key => $value )
					$parameters [$key] = $value;
			}
			$this->Parameters = new DataCollection ( $parameters );
		}
		return $this->Parameters;
	}

	/**
	 * Project name
	 *
	 * @return string
	 */
	final public function getName(): string {
		if (! isset ( $this->name )) {
			$this->name = '';
			if (IS_HTTP_REQUEST) {
				$host = $this->getSuperServices ()->getHttpServerService ()->getHost ();
				if (($HostsSettings = $this->getSuperServices ()->getConfigurationService ()->getHostsSettings ()) && $HostsSettings instanceof DataCollection)
					$this->name = $HostsSettings->findByKey ( $host ) ? $HostsSettings->findByKey ( $host ) : '';
			} elseif (($routeSections = explode ( '/', ( string ) $this->getSuperServices ()->getCliService ()->getOpt ( '_route_index' ) )) && $routeSections [0] === 'Project')
				$this->name = $routeSections [1];
		}
		return $this->name;
	}

	/**
	 *
	 * @param string $connnectorID
	 *        	If null, return full collection
	 * @return DataCollection|NULL
	 */
	final public function getDatabaseSettings(?string $connnectorID = null): DataCollection {
		if (! isset ( $this->DatabasesSettings )) {
			// Load first the root database settings
			$this->DatabasesSettings = $this->getSettings ()->findByKey ( 'databases' ) ? $this->getSettings ()->findByKey ( 'databases' ) : new DataCollection ();
			// If specific database definitions are set into the project section, they will be taken and they will override eventually the root ones
			$ProjectDbSettings = $this->getEnvSettings ()->findByKey ( 'databases' );
			if ($ProjectDbSettings instanceof DataCollection) {
				foreach ( $ProjectDbSettings as $connectorIdSet => $DbSettings )
					$this->DatabasesSettings->upsert ( $connectorIdSet, $DbSettings );
			}
		}
		return $connnectorID ? $this->DatabasesSettings->findByKey ( $connnectorID ) : $this->DatabasesSettings;
	}

	/**
	 * Default SQL connector (parameter) ID
	 *
	 * @return string|NULL
	 */
	final public function getDefaultSqlConnectorID(): ?string {
		if (! isset ( $this->defaultSqlConnectorID )) {
			if ($defaultSqlConnectorID = $this->getEnvSettings ()->findByKey ( 'default_sql_connector_id' ))
				$this->defaultSqlConnectorID = $defaultSqlConnectorID;
			else {
				$this->defaultSqlConnectorID = '';
				// Get the first one
				foreach ( $this->getDatabaseSettings () as $connectorId => $Settings ) {
					if ($Settings instanceof DataCollection && ($driver = $Settings->findByKey ( 'driver' ))) {
						if (in_array ( $driver, Driver::getConstants ()->getData ( true ) ) && $driver !== Driver::MONGO) {
							$this->defaultSqlConnectorID = $connectorId;
							break;
						}
					}
				}
			}
		}
		return $this->defaultSqlConnectorID;
	}

	/**
	 * The default SQL configuration
	 *
	 * @return DataCollection|NULL
	 */
	final public function getDefaultSqlConfiguration(): ?DataCollection {
		return $this->getDatabaseSettings ( $this->getDefaultSqlConnectorID () );
	}

	/**
	 * Default Mongo connector (parameter) ID
	 *
	 * @return string|NULL
	 */
	final public function getDefaultMongoConnectorID(): ?string {
		if (! isset ( $this->defaultMongoConnectorID )) {
			if (! ($this->defaultMongoConnectorID = $this->getEnvSettings ()->findByKey ( 'default_mongo_connector_id' ))) {
				$this->defaultMongoConnectorID = '';
				// Get the first one
				foreach ( $this->getDatabaseSettings () as $connectorId => $Settings ) {
					if ($Settings instanceof DataCollection && ($driver = $Settings->findByKey ( 'driver' ))) {
						if ($driver === Driver::MONGO) {
							$this->defaultMongoConnectorID = $connectorId;
							break;
						}
					}
				}
			}
		}
		return $this->defaultMongoConnectorID;
	}

	/**
	 * The default MongoDB configuration
	 *
	 * @return DataCollection|NULL
	 */
	final public function getDefaultMongoConfiguration(): ?DataCollection {
		return $this->getDatabaseSettings ( $this->getDefaultMongoConnectorID () );
	}

	/**
	 *
	 * @return int
	 */
	final public function getDefaultFormatId(): int {
		$defaultFormatId = $this->getEnvSettings ()->findByKey ( 'default_template_format_id' );
		if (is_numeric ( $defaultFormatId ) && strpos ( ( string ) $defaultFormatId, '.' ) === false)
			$defaultFormatId = ( int ) $defaultFormatId;
		return TemplateFormat::getConstants ()->getElementKey ( $defaultFormatId ) ? $defaultFormatId : TemplateFormat::HTML;
	}

	/**
	 *
	 * @return string
	 */
	final public function getDefaultTemplatePath(): string {
		$defaultTemplatePath = $this->getEnvSettings ()->findByKey ( 'default_template_path' );
		return $defaultTemplatePath ? $defaultTemplatePath : CorePath::DEFAULT_TEMPLATE_PATH;
	}

	/**
	 *
	 * @return string
	 */
	final public function getDefaultLayoutPath(): string {
		$defaultLayoutPath = $this->getEnvSettings ()->findByKey ( 'default_layout_path' );
		return $defaultLayoutPath ? $defaultLayoutPath : CorePath::DEFAULT_LAYOUT_PATH;
	}

	/**
	 *
	 * @return string|null
	 */
	final public function getEncoding(): ?string {
		return $this->getEnvSettings ()->findByKey ( 'encoding' );
	}

	/**
	 *
	 * @return string|null
	 */
	final public function getLocale(): ?string {
		return $this->getEnvSettings ()->findByKey ( 'locale' );
	}

	/**
	 *
	 * @todo Get locale from cookie selection
	 * @return DataCollection
	 */
	final public function getLocaleAdditionalProperties(): DataCollection {
		static $LocaleProps;
		if (! isset ( $LocaleProps )) {
			$locale = $this->getLocale ();
			$LocaleProps = (new DataCollection ( Locale::getConstant ( $locale ) ))->upsert ( 'locale', $locale );
		}
		return $LocaleProps;
	}

	/**
	 * Targetted project can be modified only in CLI mode.
	 *
	 * @param string $projectName
	 * @return self
	 */
	final public function setProjectNameInCliMode(?string $projectName): self {
		$projectName = trim ( ( string ) $projectName );
		if (IS_CLI && $projectName) {
			// Check if configuration project folder exists
			$projectDir = sprintf ( CustomProjectPattern::PATH, $projectName );
			if (! is_dir ( $projectDir )) {
				$this->getSuperServices ()->getCliService ()->printError ( sprintf ( degettext ( 'core', 'Project "%s" does not exist!' ), $projectName ) );
				if (! empty ( $this->name ))
					$this->getSuperServices ()->getCliService ()->printError ( sprintf ( degettext ( 'core', 'Keeping project "%s".' ), $projectName ) );
				return $this;
			}
			// Check configuration file exists
			$settingsFile = sprintf ( CustomProjectPattern::SETTINGS_FILE, $projectName );
			if (! file_exists ( $settingsFile )) {
				// Create it as it is required
				$this->getSuperServices ()->getCliService ()->printWarning ( dgettext ( 'core', 'Missing required configuration file in your project!' ) );
				$answer = $this->getSuperServices ()->getCliService ()->prompt ( dgettext ( 'core', 'Create "project.json" file? [Yn]' ), dgettext ( 'core', 'y {means yes}' ) );
				if ($this->getSuperServices ()->getLocalizeService ()->meansYes ( $answer )) {
					$settingsContent = str_replace ( '/*projectName*/', $projectName, file_get_contents ( CorePath::PATTERN_PROJECT_SETUP_FILE ) );
					if (! $this->getSuperServices ()->getFilesystemService ()->createFile ( $settingsFile, $settingsContent, Filesystem::FILE_OVERWRITE_PROMPT ))
						return $this;
				}
			}
			$this->name = $projectName;
		}
		return $this;
	}
}