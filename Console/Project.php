<?php

namespace Console;

use Console;
use FragTale\DataCollection;
use FragTale\Constant\Setup\CorePath;
use FragTale\Service\Cli;
use FragTale\DataCollection\JsonCollection;
use FragTale\Constant\Setup\CustomProjectPattern;
use Console\Project\Create;

/**
 *
 * @author Fabrice Dant
 *        
 */
class Project extends Console {

	/**
	 *
	 * @var string
	 */
	private static ?string $projectName = null;

	/**
	 * Specified project configuration
	 *
	 * @var JsonCollection
	 */
	private static ?JsonCollection $ProjectAppConfig = null;

	/**
	 * Just check if project folder exist
	 */
	protected function executeOnTop(): void {
		$this->CliService->printInColor ( sprintf ( dgettext ( 'core', 'Entering project management space' ), $this->getProjectName () ), Cli::COLOR_YELLOW )
			->printInColor ( dgettext ( 'core', '**********************' ), Cli::COLOR_LCYAN )
			->printInColor ( dgettext ( 'core', 'You have 4 sections:' ), Cli::COLOR_LCYAN )
			->print ( '	' . dgettext ( 'core', '· "Configure": offers 3 actions to configure your project databases, environments and custom parameters' ), Cli::COLOR_CYAN )
			->print ( '	' . dgettext ( 'core', '· "Controller": you can create a new controller' ), Cli::COLOR_CYAN )
			->print ( '	' . dgettext ( 'core', '· "Create": allows you to create a new project from scratch' ), Cli::COLOR_CYAN )
			->print ( '	' . dgettext ( 'core', '· "Model": map your database tables and relations (ORM)' ), Cli::COLOR_CYAN )
			->print ( dgettext ( 'core', '**********************' ), Cli::COLOR_LCYAN );
	}

	/**
	 *
	 * {@inheritdoc}
	 * @see \FragTale\Application\Controller::executeOnConsole()
	 */
	protected function executeOnConsole(): void {
		$this->launchSubController ();
	}

	/**
	 * Save project settings changes
	 */
	protected function executeOnBottom(): void {
		if ($this->getProjectAppConfig () && $this->getProjectAppConfig ()->modified ()) {
			try {
				$this->getProjectAppConfig ()->save ();
				$this->setProjectAppConfig ( true );
				$this->CliService->printSuccess ( sprintf ( dgettext ( 'core', 'File "%s" successfully updated' ), $this->getProjectAppConfig ()->getSource () ) );
			} catch ( \Throwable $T ) {
				$this->getSuperServices ()->getErrorHandlerService ()->catchThrowable ( $T );
			}
		}
	}

	/**
	 * The "project.json" file loaded into a JsonCollection, specified by project name selected or given.
	 *
	 * @return JsonCollection|NULL
	 */
	protected function getProjectAppConfig(): JsonCollection {
		return self::$ProjectAppConfig instanceof JsonCollection ? self::$ProjectAppConfig : new JsonCollection ();
	}

	/**
	 * Load "project.json" file into a JsonCollection, specified by project name selected or given.
	 *
	 * @see Project::getProjectAppConfig()
	 * @param bool $reload
	 *        	If true, it will reload the config file anyway
	 * @return self
	 */
	protected function setProjectAppConfig(bool $reload = false): self {
		if ((! (self::$ProjectAppConfig instanceof JsonCollection) || $reload) && $this->getProjectName ()) {
			$projectConfFile = sprintf ( CustomProjectPattern::SETTINGS_FILE, $this->getProjectName () );
			if (file_exists ( $projectConfFile )) {
				// Load conf file
				self::$ProjectAppConfig = (new JsonCollection ())->setSource ( $projectConfFile )->load ();
				return $this;
			}
		}
		// self::$ProjectAppConfig = null;
		return $this;
	}

	/**
	 *
	 * @return string|NULL
	 */
	protected function getProjectName(): ?string {
		return static::$projectName;
	}

	/**
	 * You can set externally the project name if you intend to call and run this controller from another one.
	 *
	 * @param string $projectName
	 * @return self
	 */
	protected function setProjectName(?string $projectName = null, bool $force = false): self {
		while ( ! $force && ! $this->getProjectFolders ()->findByKey ( $projectName ) && ! $this->getProjectName () ) {
			if ($projectName)
				$this->CliService->printWarning ( sprintf ( dgettext ( 'core', 'Unknown project "%s"' ), $projectName ) );
			$projectName = $this->promptToFindElementInCollection ( dgettext ( 'core', 'Select project' ), $this->getProjectFolders (), null, true );
			$this->CliService->print ( $projectName );
		}
		// Set project service name (to load targetted project settings) and check if project exists
		if ($projectName === $this->getSuperServices ()
			->getProjectService ()
			->setProjectNameInCliMode ( $projectName )
			->getName ())
			static::$projectName = $projectName;
		return $this;
	}

	/**
	 *
	 * @return DataCollection
	 */
	protected function getProjectFolders(): DataCollection {
		// Scan Project folder
		$ProjectFolders = new DataCollection ();
		if (! is_dir ( CorePath::PROJECT_ROOT )) {
			$this->CliService->printError ( sprintf ( dgettext ( 'core', 'Directory "%s" does not exist yet. You should execute first command: ./fragtale Console/Setup' ), CorePath::PROJECT_ROOT ) );
			exit ();
		}
		foreach ( scandir ( CorePath::PROJECT_ROOT ) as $project ) {
			$fullpath = CorePath::PROJECT_ROOT . "/$project";
			if (substr ( $project, 0, 1 ) !== '.' && is_dir ( $fullpath ))
				$ProjectFolders->upsert ( $project, $fullpath );
		}
		return $ProjectFolders;
	}

	/**
	 * Controllers that modify an existing project must prompt to select the project name if it was not passed as CLI argument
	 */
	protected function setOrPromptProjectName(): void {
		if ($this->isHelpInvoked ())
			return;
		if ($this->getProjectFolders ()->count ()) {
			// Get project name from CLI argument or prompt project name to update
			$this->setProjectName ( $this->CliService->getOpt ( 'project' ) )->setProjectAppConfig ();
		} elseif (! $this instanceof Create) {
			$this->CliService->printWarning ( dgettext ( 'core', 'You have not created a project yet. Going through project creation:' ) );
			(new Create ())->run ();
			exit ();
		}
	}
}