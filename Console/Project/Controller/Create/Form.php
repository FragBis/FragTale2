<?php

namespace Console\Project\Controller\Create;

use Console\Project\Controller\Create;
use FragTale\Service\Cli;
use FragTale\Application\Model;
use FragTale\Constant\Setup\CorePath;
use FragTale\Constant\Setup\CustomProjectPattern;
use FragTale\DataCollection;
use FragTale\DataCollection\JsonCollection;

/**
 *
 * @author Fabrice Dant
 *        
 */
class Form extends Create {
	/**
	 */
	protected function executeOnTop(): void {
		if ($this->isHelpInvoked ()) {
			$this->CliService->printInColor ( dgettext ( 'core', '**** Help invoked ****' ), Cli::COLOR_LCYAN )
				->printInColor ( dgettext ( 'core', 'There are 5 CLI options (not required) handled:' ), Cli::COLOR_LCYAN )
				->printInColor ( '	' . dgettext ( 'core', '· "--project": the project name' ), Cli::COLOR_CYAN )
				->printInColor ( '	' . dgettext ( 'core', '· "--dir": the controller folder in which to place it; It can be the relative path from the project directory (e.g.: Project/{projectName}/Controller/Web)' ), Cli::COLOR_CYAN )
				->printInColor ( '	' . dgettext ( 'core', '· "--model": the model name' ), Cli::COLOR_CYAN )
				->printInColor ( '	' . dgettext ( 'core', '· "--entity": the entity name (without prefix)' ), Cli::COLOR_CYAN )
				->printInColor ( dgettext ( 'core', '**********************' ), Cli::COLOR_LCYAN );
			return;
		}

		// Prompt project name to update
		if (! $this->getProjectName ())
			$this->setProjectName ( $this->CliService->getOpt ( 'project' ) );
		$this->CliService->printInColor ( sprintf ( dgettext ( 'core', 'Creating new form for project "%s".' ), $this->getProjectName () ), Cli::COLOR_YELLOW );
	}

	/**
	 */
	protected function executeOnConsole(): void {
		if ($this->isHelpInvoked ())
			return;

		// using aliases for common services
		$RouteService = $this->getSuperServices ()->getRouteService ();
		$FsService = $this->getSuperServices ()->getFilesystemService ();

		// Getting options (if passed)
		if ($modelName = $this->getModelName ())
			$this->CliService->print ( sprintf ( dgettext ( 'core', 'Using model "%s"' ), $modelName ) );
		else
			return;

		if ($entityName = $this->getEntityName ())
			$this->CliService->print ( sprintf ( dgettext ( 'core', 'Creating form for entity "%s"' ), $entityName ) );
		else
			return;

		// Defining base directories & namespace
		$projectName = $this->getProjectName ();
		$modelNamespace = sprintf ( CustomProjectPattern::SQL_MODEL_NAMESPACE, $projectName ) . '\\' . $modelName;
		$controllerDir = '';
		if ($relDir = $this->getRelativeFolder ()) {
			$controllerDir = rtrim ( sprintf ( CustomProjectPattern::WEB_CONTROLLER_DIR, $projectName ) . "/$relDir", '/' );
			$this->CliService->print ( sprintf ( dgettext ( 'core', 'In folder "%s"' ), $controllerDir ) );
		} else
			return;

		if (! ($relNamespace = $RouteService->convertUriToNamespace ( $relDir )))
			return;
		$controllerNamespace = sprintf ( CustomProjectPattern::WEB_CONTROLLER_NAMESPACE, $projectName ) . '\\' . $relNamespace;
		$this->CliService->print ( sprintf ( dgettext ( 'core', 'Using controller namespace "%s"' ), $controllerNamespace ) );

		$baseTemplateDir = rtrim ( sprintf ( CustomProjectPattern::TEMPLATES_DIR, $projectName ), '/' );
		$formsRelDir = 'forms/' . trim ( strtolower ( $relDir ), '/' ) . '/' . trim ( strtolower ( $modelName ), '/' ) . '/' . trim ( strtolower ( $entityName ), '/' );
		$templateDir = "$baseTemplateDir/$formsRelDir";
		$this->CliService->print ( sprintf ( dgettext ( 'core', 'Templates will be placed in "%s"' ), $templateDir ) )->printInColor ( dgettext ( 'core', 'Creating folders...' ), Cli::COLOR_CYAN );
		foreach ( [ 
				"$controllerDir/$entityName",
				$templateDir
		] as $dir ) {
			if (! $FsService->createDir ( $dir, true ))
				return;
		}

		$entityNamespace = sprintf ( CustomProjectPattern::SQL_MODEL_NAMESPACE, $projectName ) . "\\$modelName\\$entityName";
		$entityClass = "$entityNamespace\\E_$entityName";
		if (! class_exists ( $entityClass )) {
			$this->getSuperServices ()->getErrorHandlerService ()->catchThrowable ( new \Exception ( sprintf ( dgettext ( 'core', 'Required class "%s" does not exist. Please check your model.' ), $entityClass ) ) );
			return;
		}
		// Get model connector ID
		$modelsSettings = (new JsonCollection ())->setSource ( sprintf ( CustomProjectPattern::SETTINGS_FILE, $projectName ) )->load ()->findByKey ( 'models' );
		$connectorId = $modelsSettings instanceof DataCollection ? $modelsSettings->findByKey ( $modelNamespace ) : 'default_sql';
		$Entity = new $entityClass ( $connectorId );
		if (! $Entity instanceof Model) {
			$this->getSuperServices ()->getErrorHandlerService ()->catchThrowable ( new \Exception ( sprintf ( dgettext ( 'core', 'Class "%s" must inherit from FragTale\Application\Model. Please check your model.' ), $entityClass ) ) );
			return;
		}

		// Creating controller: list
		$patternFile = CorePath::PATTERN_FORM_CONTROLLER_ENTITY_LIST;
		$this->CliService->print ( sprintf ( dgettext ( 'core', 'Using pattern "%s"' ), $patternFile ) );
		$listControllerContent = str_replace ( [ 
				'/*namespace*/',
				'/*useController*/',
				'/*useEntity*/',
				'/*Class*/',
				'/*Entity*/',
				'/*templatePath*/'
		], [ 
				$controllerNamespace,
				sprintf ( CustomProjectPattern::NAMESPACE, $projectName ) . '\\WebController',
				$entityClass,
				$entityName,
				"E_$entityName",
				"$formsRelDir/list.phtml"
		], file_get_contents ( $patternFile ) );
		if (! $FsService->createFile ( "$controllerDir/$entityName.php", $listControllerContent ))
			return;
		// Creating template: list
		$patternFile = CorePath::PATTERN_FORM_TEMPLATE_ENTITY_LIST;
		$this->CliService->print ( sprintf ( dgettext ( 'core', 'Using pattern "%s"' ), $patternFile ) );
		$listTemplateContent = str_replace ( '/*useAction*/', "$controllerNamespace\\$entityName\\Action", file_get_contents ( $patternFile ) );
		if (! $FsService->createFile ( "$templateDir/list.phtml", $listTemplateContent ))
			return;

		// Creating controller: action
		$patternFile = CorePath::PATTERN_FORM_CONTROLLER_ENTITY_ACTION;
		$this->CliService->print ( sprintf ( dgettext ( 'core', 'Using pattern "%s"' ), $patternFile ) );
		$actionControllerContent = str_replace ( [ 
				'/*namespace*/',
				'/*useController*/',
				'/*useEntity*/',
				'/*Entity*/',
				'/*templatePath*/',
				'/*ListController*/'
		], [ 
				"$controllerNamespace\\$entityName",
				sprintf ( CustomProjectPattern::NAMESPACE, $projectName ) . '\\WebController',
				"$entityNamespace\\E_$entityName",
				"E_$entityName",
				"$formsRelDir/action.phtml",
				$entityName
		], file_get_contents ( $patternFile ) );
		if (! $FsService->createFile ( "$controllerDir/$entityName/Action.php", $actionControllerContent ))
			return;
		// Creating template: action
		$patternFile = CorePath::PATTERN_FORM_TEMPLATE_ENTITY_ACTION;
		$this->CliService->print ( sprintf ( dgettext ( 'core', 'Using pattern "%s"' ), $patternFile ) );
		$actionTemplateContent = str_replace ( [ 
				'/*useListController*/',
				'/*ListController*/'
		], [ 
				"$controllerNamespace\\$entityName",
				$entityName
		], file_get_contents ( $patternFile ) );
		if (! $FsService->createFile ( "$templateDir/action.phtml", $actionTemplateContent ))
			return;

		// Creating templates: list & edit (create use the same template than edit)
	}

	/**
	 *
	 * @return string|NULL
	 */
	protected function getProjectSqlModelDir(): ?string {
		static $modelFolder;
		if (! isset ( $modelFolder )) {
			$projectName = $this->getProjectName ();
			$modelFolder = sprintf ( CustomProjectPattern::SQL_MODEL_DIR, $projectName );
			if (! is_dir ( $modelFolder )) {
				$modelFolder = null;
				$this->getSuperServices ()->getErrorHandlerService ()->catchThrowable ( new \Exception ( sprintf ( dgettext ( 'core', 'SQL Model\'s folder "%s" does not exist' ), $modelFolder ) ) );
			}
		}
		return $modelFolder;
	}

	/**
	 *
	 * @return string|NULL
	 */
	protected function getModelName(): ?string {
		static $modelName;
		if (! isset ( $modelName )) {
			if (! ($modelName = $this->CliService->getOpt ( 'model' ))) {
				// Get list of existing models
				
				// If not passed in cli option, prompt:
				$modelName = $this->CliService->prompt ( dgettext ( 'core', 'Type model name:' ) );
			}
			if ($modelName) {
				// Check model exists
				if ($modelName = $this->getSuperServices ()->getRouteService ()->convertUriToNamespace ( $modelName )) {
					$modelFolder = $this->getProjectSqlModelDir () . "/$modelName";
					if (! is_dir ( $modelFolder )) {
						$modelName = null;
						$this->getSuperServices ()->getErrorHandlerService ()->catchThrowable ( new \Exception ( sprintf ( dgettext ( 'core', 'Model\'s folder "%s" does not exist' ), $modelFolder ) ) );
					}
				} else
					$modelName = null;
			}
		}
		return $modelName;
	}

	/**
	 *
	 * @return string|NULL
	 */
	protected function getEntityName(): ?string {
		static $entityName;
		if (! isset ( $entityName )) {
			if (! ($entityName = $this->CliService->getOpt ( 'entity' ))) {
				// If not passed in cli option, prompt:
				$entityName = $this->CliService->prompt ( dgettext ( 'core', 'Type table name:' ) );
			}
			if ($entityName) {
				$entityName = $this->getSuperServices ()->getRouteService ()->convertUriToNamespace ( $entityName );
				$entityNamespace = sprintf ( CustomProjectPattern::SQL_MODEL_NAMESPACE, $this->getProjectName () ) . '\\' . $this->getModelName () . '\\' . $entityName;
				// Check entity exists
				$entityDir = $this->getEntityDir ();
				$this->CliService->print ( sprintf ( dgettext ( 'core', 'Using entity namespace "%1s" in folder "%2s"' ), $entityNamespace, $entityDir ) );
				if (! is_dir ( $entityDir )) {
					$entityName = null;
					$this->getSuperServices ()->getErrorHandlerService ()->catchThrowable ( new \Exception ( sprintf ( dgettext ( 'core', 'Entity\'s folder "%s" does not exist' ), $entityDir ) ) );
				} else {
					foreach ( [ 
							"E_$entityName",
							"M_$entityName",
							"T_$entityName"
					] as $class ) {
						$fullClass = "$entityNamespace\\$class";
						if (! class_exists ( $fullClass )) {
							$this->getSuperServices ()->getErrorHandlerService ()->catchThrowable ( new \Exception ( sprintf ( dgettext ( 'core', 'Entity "%1s" misses required class "%2s"' ), $entityName, $fullClass ) ) );
							$entityName = null;
						}
					}
				}
			}
		}
		return $entityName;
	}

	/**
	 *
	 * @return string|NULL
	 */
	protected function getEntityDir(): ?string {
		return $this->getProjectSqlModelDir () . '/' . $this->getModelName () . '/' . $this->getEntityName ();
	}

	/**
	 *
	 * @return string|NULL
	 */
	protected function getRelativeFolder(): ?string {
		static $folder;
		if (! isset ( $folder )) {
			$projectName = $this->getProjectName ();
			$webControllerDir = sprintf ( CustomProjectPattern::WEB_CONTROLLER_DIR, $projectName );
			$folder = $this->CliService->getOpt ( 'dir' );
			if ($folder === null) {
				// prompt
				$this->CliService->print ( sprintf ( dgettext ( 'core', 'Entering folder "%s"' ), $webControllerDir ) );
				chdir ( $webControllerDir );
				$folder = $this->getSuperServices ()->getCliService ()->prompt ( dgettext ( 'core', 'Type folder in which to place your new controllers:' ) );
			}
			if (in_array ( $folder, [ 
					'',
					1
			] ))
				$folder = '/';
			$expFolder = explode ( '/', $folder );
			foreach ( $expFolder as $i => $exp ) {
				$exp = trim ( ( string ) $exp );
				if (empty ( $exp ))
					unset ( $expFolder [$i] );
				elseif (is_numeric ( substr ( $expFolder [$i], 0, 1 ) )) {
					$this->getSuperServices ()->getCliService ()->printError ( 'Each expression of controller path must not start by a number' );
					return null;
				} else
					$expFolder [$i] = $this->getSuperServices ()->getRouteService ()->convertUriToNamespace ( $expFolder [$i] );
			}
			$folder = implode ( '/', $expFolder );
			$folder = trim ( str_replace ( [ 
					"$webControllerDir/",
					APP_ROOT . '/',
					"Project/$projectName/Controller/Web/"
			], '', $folder ), '/' ) . '/';
		}

		return $folder;
	}
}