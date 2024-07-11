<?php

namespace Console\Project\Controller\Create;

use Console\Project\Controller\Create;
use FragTale\Service\Cli;
use FragTale\Application\Model;
use FragTale\Constant\Setup\CorePath;
use FragTale\Constant\Setup\CustomProjectPattern;
use FragTale\DataCollection;

/**
 *
 * @author Fabrice Dant
 *        
 */
class Form extends Create {
	/**
	 *
	 * @var string
	 */
	private ?string $modelNamespace;

	/**
	 */
	protected function executeOnTop(): void {
		if ($this->isHelpInvoked ()) {
			$this->CliService->printInColor ( dgettext ( 'core', '**** Help invoked ****' ), Cli::COLOR_LCYAN )
				->printInColor ( dgettext ( 'core', 'There are 5 CLI options (not required) handled:' ), Cli::COLOR_LCYAN )
				->printInColor ( '	' . dgettext ( 'core', '· "--project": the project name' ), Cli::COLOR_CYAN )
				->printInColor ( '	' . dgettext ( 'core', '· "--dir": the controller folder in which to place it; It can be the relative path from the project directory (e.g.: Project/{projectName}/Controller/Web)' ), Cli::COLOR_CYAN )
				->printInColor ( '	' . dgettext ( 'core', '· "--model": the model namespace' ), Cli::COLOR_CYAN )
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

		$entityNamespace = "{$this->modelNamespace}\\{$entityName}";
		$entityClass = "{$entityNamespace}\\E_{$entityName}";
		if (! class_exists ( $entityClass )) {
			$this->getSuperServices ()->getErrorHandlerService ()->catchThrowable ( new \Exception ( sprintf ( dgettext ( 'core', 'Required class "%s" does not exist. Please check your model.' ), $entityClass ) ) );
			return;
		}
		$Entity = new $entityClass ();
		if (! $Entity instanceof Model) {
			$this->getSuperServices ()->getErrorHandlerService ()->catchThrowable ( new \Exception ( sprintf ( dgettext ( 'core', 'Class "%s" must inherit from FragTale\Application\Model. Please check your model.' ), $entityClass ) ) );
			return;
		}

		// Creating controller: list
		$patternFile = sprintf ( CustomProjectPattern::PATTERN_FORM_CONTROLLER_ENTITY_LIST, $projectName );
		if (! file_exists ( $patternFile ))
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
		$patternFile = sprintf ( CustomProjectPattern::PATTERN_FORM_TEMPLATE_ENTITY_LIST, $projectName );
		if (! file_exists ( $patternFile ))
			$patternFile = CorePath::PATTERN_FORM_TEMPLATE_ENTITY_LIST;
		$this->CliService->print ( sprintf ( dgettext ( 'core', 'Using pattern "%s"' ), $patternFile ) );
		$listTemplateContent = str_replace ( '/*useAction*/', "$controllerNamespace\\$entityName\\Action", file_get_contents ( $patternFile ) );
		if (! $FsService->createFile ( "$templateDir/list.phtml", $listTemplateContent ))
			return;

		// Creating controller: action
		$patternFile = sprintf ( CustomProjectPattern::PATTERN_FORM_CONTROLLER_ENTITY_ACTION, $projectName );
		if (! file_exists ( $patternFile ))
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
		$patternFile = sprintf ( CustomProjectPattern::PATTERN_FORM_TEMPLATE_ENTITY_ACTION, $projectName );
		if (! file_exists ( $patternFile ))
			$patternFile = CorePath::PATTERN_FORM_TEMPLATE_ENTITY_ACTION;
		$this->CliService->print ( sprintf ( dgettext ( 'core', 'Using pattern "%s"' ), $patternFile ) );
		$actionTemplateContent = str_replace ( [ 
				'/*useListController*/',
				'/*ListController*/'
		], [ 
				"$controllerNamespace\\$entityName",
				$entityName
		], file_get_contents ( $patternFile ) );
		$FsService->createFile ( "$templateDir/action.phtml", $actionTemplateContent );
	}

	/**
	 *
	 * @return string|NULL
	 */
	protected function getModelNamespace(): ?string {
		if (! isset ( $this->modelNamespace )) {
			// Get list of existing models
			$ProjectSettings = $this->getSuperServices ()->getProjectService ()->getSettings ();
			$Models = $ProjectSettings->findByKey ( 'models' );
			if (! $Models instanceof DataCollection || ! $Models->count ()) {
				$this->CliService->printError ( dgettext ( 'core', 'You have not declared any models in your configuration file. You should generate a model first, by executing command: ./fragtale2 Console/Project/Model' ) );
				return null;
			}

			$modelNamespace = $this->CliService->getOpt ( 'model' );
			if (! $modelNamespace || ! $Models->findByKey ( $modelNamespace )) {
				// If not passed in cli option or not in list, prompt:
				$modelNamespace = $this->promptToFindElementInCollection ( dgettext ( 'core', 'Select a model namespace:' ), $Models, null, true );
			}

			if ($modelNamespace)
				$this->modelNamespace = $modelNamespace;
			else
				$this->getSuperServices ()->getErrorHandlerService ()->catchThrowable ( new \Exception ( dgettext ( 'core', 'Please select an existing model.' ) ) );
		}
		return $this->modelNamespace;
	}

	/**
	 *
	 * @return string|NULL
	 */
	protected function getModelName(): ?string {
		if (! $this->getModelNamespace ())
			return null;

		$exp = explode ( '\\', $this->modelNamespace );
		return end ( $exp );
	}

	/**
	 *
	 * @return string|NULL
	 */
	protected function getEntityName(): ?string {
		static $entityName;
		if (! isset ( $entityName )) {
			// Get list of entities from model folder
			$Entities = new DataCollection ();
			$modelDir = APP_ROOT . '/' . str_replace ( "\\", '/', ( string ) $this->getModelNamespace () );
			if ($entities = glob ( "$modelDir/*", GLOB_ONLYDIR )) {
				foreach ( $entities as $folder )
					$Entities->upsert ( basename ( $folder ), $folder );
			} else {
				$this->CliService->printError ( degettext ( 'core', 'Model namespace does not contain any valid entity.' ) );
				return null;
			}

			$entityFound = false;
			if ($entityName = $this->CliService->getOpt ( 'entity' )) {
				// Check if it is contained in list of entities
				foreach ( $Entities as $entity => $folder ) {
					if (strtolower ( $entity ) == strtolower ( $entityName )) {
						$entityName = $entity;
						$entityFound = true;
						break;
					}
				}
			}
			if (! $entityFound)
				$entityName = ( string ) $this->promptToFindElementInCollection ( dgettext ( 'core', 'Select one of the following entities:' ), $Entities, null, true );

			if ($entityName) {
				$entityNamespace = $this->getModelNamespace () . "\\{$entityName}";
				// Check entity exists
				$entityDir = APP_ROOT . '/' . str_replace ( "\\", '/', $entityNamespace );
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