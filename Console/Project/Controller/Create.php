<?php

namespace Console\Project\Controller;

use Console\Project\Controller;
use FragTale\Constant\Setup\ControllerType;
use FragTale\Constant\Setup\CorePath;
use FragTale\Constant\Setup\CustomProjectPattern;
use FragTale\Service\Cli;

class Create extends Controller {
	protected ?bool $swapTemplate;
	protected ?string $controllerName;
	protected ?string $controllerFolder;

	/**
	 */
	protected function executeOnTop(): void {
		if ($this->isHelpInvoked ()) {
			$this->CliService->printInColor ( dgettext ( 'core', '**** Help invoked ****' ), Cli::COLOR_LCYAN )
				->printInColor ( sprintf ( dgettext ( 'core', 'There are %s CLI options handled (not required):' ), 5 ), Cli::COLOR_LCYAN )
				->print ( '	' . dgettext ( 'core', '· "--project": the project name' ) )
				->print ( '	' . dgettext ( 'core', '· "--dir": the controller folder in which to place it; It can be the relative path from the project directory or from controller type folder (e.g.: Project/{projectName}/Controller/{controllerType})' ) )
				->print ( '	' . dgettext ( 'core', '· "--name": the new controller class name' ) )
				->print ( '	' . dgettext ( 'core', '· "--type": controller type in list [\'Web\', \'Cli\', \'Block\']' ) )
				->print ( '	' . dgettext ( 'core', '· "--swap-template": [0, 1] (or [true, false]); Indicate that you do not want to create the ".phtml" file associated with a Web Controller. This option is useless for Cli controller and Block controller always create the corresponding template.' ) )
				->print ( '' )
				->printInColor ( dgettext ( 'core', 'About controller types:' ), Cli::COLOR_LCYAN )
				->print ( dgettext ( 'core', '· a "WEB" controller is exposed on the Web (but it can be executed in CLI or used as BLOCK).' ) )
				->print ( dgettext ( 'core', '· a "BLOCK" controller is not directly exposed on the Web and included in another controller or view.' ) )
				->print ( dgettext ( 'core', '· a "CLI" controller is only executed in console.' ) )
				->printInColor ( dgettext ( 'core', '**********************' ), Cli::COLOR_LCYAN );
			return;
		}

		$this->swapTemplate = $this->getSuperServices ()->getLocalizeService ()->meansYes ( $this->CliService->getOpt ( 'swap-template' ) );
		$this->controllerName = ( string ) $this->getControllerName ();
		$this->controllerFolder = ( string ) $this->getRelativeFolder ();
	}

	/**
	 */
	protected function executeOnConsole(): void {
		if ($this->isHelpInvoked ())
			return;

		$controllerType = $this->CliService->getOpt ( 'type' );

		$this->CliService->printInColor ( dgettext ( 'core', 'Create a new controller' ), Cli::COLOR_YELLOW )->printInColor ( '*** ' . dgettext ( 'core', 'Add option "-h" for more information:' ) . Cli::COLOR_WHITE . ' ./fragtale2 Console/Project/Controller/Create -h' . Cli::COLOR_LCYAN . ' ***', Cli::COLOR_LCYAN );

		// Select the controller type if not set yet
		$controllerTypes = ControllerType::getConstants ();
		while ( ! $controllerTypes->getElementKey ( $controllerType ) ) {
			if ($controllerType)
				$this->CliService->printWarning ( sprintf ( dgettext ( 'core', 'Controller type "%s" not handled' ), $controllerType ) );
			$controllerType = $this->promptToFindElementInCollection ( dgettext ( 'core', 'Which kind of controller do you wish to create?' ), $controllerTypes );
		}

		$this->createController ( $controllerType );
	}

	/**
	 *
	 * @param string $controllerType
	 *        	Web|Cli|Block
	 */
	protected function createController(string $controllerType): void {
		// Getting options (if passed)
		$projectName = $this->getProjectName ();
		$controllerType = ucwords ( strtolower ( trim ( $controllerType ) ) );

		// using aliases for common services
		$RouteService = $this->getSuperServices ()->getRouteService ();
		$FsService = $this->getSuperServices ()->getFilesystemService ();
		$this->CliService->printInColor ( sprintf ( dgettext ( 'core', 'Creating controller type "%s"' ), $controllerType ), Cli::COLOR_LCYAN );

		// Defining base directories & namespace
		$baseControllerNamespace = sprintf ( CustomProjectPattern::CONTROLLER_NAMESPACE, $projectName );
		$controllerNamespace = "$baseControllerNamespace\\$controllerType";
		$baseControllerDir = sprintf ( CustomProjectPattern::CONTROLLER_DIR, $projectName );
		$controllerDir = "$baseControllerDir/$controllerType";

		$namespace = '';
		if (! $this->controllerFolder) {
			if (! is_dir ( $controllerDir ) && ! $FsService->createDir ( $controllerDir, true ))
				return;
			chdir ( $controllerDir );
			$this->controllerFolder = $this->CliService->prompt ( sprintf ( dgettext ( 'core', "Please type the folder you want to place the %1s controller in:\n from \"%2s\" (you can press [TAB] to browse existing folders or leave blank to write in this folder)" ), $controllerType, $controllerDir ) );
		}
		if ($this->controllerFolder) {
			foreach ( [ 
					$controllerDir,
					str_replace ( APP_ROOT . '/', '', $controllerDir ),
					$baseControllerDir,
					str_replace ( APP_ROOT . '/', '', $baseControllerDir ),
					APP_ROOT
			] as $baseDir ) {
				if (strpos ( $this->controllerFolder, "$baseDir/" ) !== false)
					$this->controllerFolder = str_replace ( "$baseDir/", '', $this->controllerFolder );
			}

			$this->controllerFolder = trim ( $this->controllerFolder, '/' );
			if (($namespace = $RouteService->convertUriToNamespace ( $this->controllerFolder )) === null) {
				$this->CliService->printError ( sprintf ( dgettext ( 'core', '"%s" does not match a valid namespace format. Remove any special chars, spaces or underscores... Class name must not be a PHP keyword.' ), $this->controllerFolder ) );
				return;
			}
		}
		while ( strpos ( $namespace, '//' ) !== false )
			$namespace = str_replace ( '//', '/', $namespace );

		$relDir = str_replace ( '\\', '/', $namespace );
		if ($namespace) {
			$controllerDir .= "/$relDir";
			$controllerNamespace .= "\\$namespace";
		}
		$this->CliService->print ( sprintf ( dgettext ( 'core', 'Using namespace "%s"' ), $controllerNamespace ) );

		// Define new controller name (if not set yet)
		while ( ! $this->controllerName )
			$this->controllerName = $this->CliService->prompt ( dgettext ( 'core', 'Type controller name (in camel case, no spaces, no underscore or any special characters, no .php file extension)' ) );

		if (! ($this->controllerName = $RouteService->convertUriToNamespace ( $this->controllerName ))) {
			$this->CliService->printError ( sprintf ( dgettext ( 'core', '"%s" does not match a valid class name format. Remove any special chars, spaces or underscores... Class name must not be a PHP keyword.' ), $this->controllerName ) );
			return;
		}
		$this->CliService->print ( sprintf ( dgettext ( 'core', 'Creating controller "%s"' ), $this->controllerName ) );

		$controllerClass = "$controllerNamespace\\$this->controllerName";
		$controllerFile = "$controllerDir/$this->controllerName.php";

		// Checking if class exists
		if (class_exists ( $controllerClass )) {
			$this->CliService->printError ( sprintf ( dgettext ( 'core', 'Class "%s" already exists. You cannot replace existing classes via Console.' ), $this->controllerName ) );
			return;
		}

		// Defining patterns following controller type
		$controllerPatternFile = $templatePatternFile = $finalTemplateFile = $templateDir = null;
		switch ($controllerType) {
			case ControllerType::CLI :
				$controllerPatternFile = CorePath::PATTERN_CLI_CONTROLLER;
				$finalTemplateFile = null;
				break;
			case ControllerType::WEB :
				if ($this->swapTemplate) {
					$controllerPatternFile = CorePath::PATTERN_DEFAULT_WEB_CONTROLLER;
					$this->CliService->print ( dgettext ( 'core', 'No ".phtml" file will be created.' ) );
				} else {
					$controllerPatternFile = CorePath::PATTERN_WEB_CONTROLLER;
					$templatePatternFile = CorePath::PATTERN_DEFAULT_TEMPLATE_PATH;
					$templateDir = sprintf ( CustomProjectPattern::VIEWS_DIR, $projectName ) . '/' . $RouteService->convertNamespaceToUri ( $relDir );
					$finalTemplateFile = rtrim ( $templateDir, '/' ) . '/' . ltrim ( $RouteService->convertNamespaceToUri ( $this->controllerName ), '/' ) . '.phtml';
				}

				$uri = $RouteService->convertNamespaceToUri ( str_replace ( $baseControllerNamespace . '\\' . ControllerType::WEB, '', $controllerClass ) );
				$this->CliService->print ( sprintf ( dgettext ( 'core', 'This controller will be accessible on the Web following URI: /%s' ), $uri ) );
				break;
			case ControllerType::BLOCK :
				$controllerPatternFile = CorePath::PATTERN_BLOCK_CONTROLLER;
				$templatePatternFile = CorePath::PATTERN_DEFAULT_TEMPLATE_PATH;
				$templateDir = sprintf ( CustomProjectPattern::BLOCKS_DIR, $projectName ) . '/' . $RouteService->convertNamespaceToUri ( $relDir );
				$finalTemplateFile = rtrim ( $templateDir, '/' ) . '/' . ltrim ( $RouteService->convertNamespaceToUri ( $this->controllerName ), '/' ) . '.phtml';
				$this->CliService->printWarning ( dgettext ( 'core', 'Note that you can also use a Web controller as a block.' ) );
				$this->CliService->printWarning ( dgettext ( 'core', 'Placing a controller in the blocks folder will not allowed it to be accessible via an http request' ) );
				break;
			default :
				$this->CliService->printError ( sprintf ( dgettext ( 'core', 'Unhandled controller type "%s"' ) ) );
				return;
		}

		// Creating folders recursively if not exists yet
		if (! is_dir ( $controllerDir ) && ! $FsService->createDir ( $controllerDir, true ))
			return;
		if ($finalTemplateFile && ! is_dir ( $templateDir ) && ! $FsService->createDir ( $templateDir, true ))
			return;

		// Generate the controller PHP code & save file, eventually copy the template
		$controllerContent = str_replace ( [ 
				'/*namespace*/',
				'/*use*/',
				'/*useWeb*/',
				'/*class*/'
		], [ 
				$controllerNamespace,
				$baseControllerNamespace,
				sprintf ( CustomProjectPattern::NAMESPACE, $projectName ) . '\\WebController',
				$this->controllerName
		], file_get_contents ( $controllerPatternFile ) );

		// Creating files
		if (! $FsService->createFile ( $controllerFile, $controllerContent ))
			return;
		if ($templatePatternFile && $finalTemplateFile) {
			// copying templates patterns
			if (copy ( $templatePatternFile, $finalTemplateFile ))
				$this->CliService->printSuccess ( sprintf ( dgettext ( 'core', 'Template file "%s" created' ), $finalTemplateFile ) );
			else
				$this->CliService->printError ( sprintf ( dgettext ( 'core', 'Unabled to copy file "%1s" to "%2s"' ), $templatePatternFile, $finalTemplateFile ) );
		}
	}

	/**
	 * From Cli options
	 *
	 * @return string|NULL
	 */
	protected function getControllerName(): ?string {
		$name = $this->CliService->getOpt ( 'name' );
		if ($name === null)
			return null;
		$name = trim ( $name, '/' );
		if (strpos ( $name, '/' )) {
			$this->CliService->printError ( dgettext ( 'core', 'Controller name must not contain part of URI. Please enter the single class name.' ) );
			return null;
		}
		return $name;
	}

	/**
	 * From Cli options
	 *
	 * @return string|NULL
	 */
	protected function getRelativeFolder(): ?string {
		$folder = $this->CliService->getOpt ( 'dir' );
		if ($folder === null)
			return null;
		if (in_array ( $folder, [ 
				'',
				1
		] ))
			$folder = '/';
		if (is_numeric ( $folder )) {
			$this->CliService->printError ( '"dir" must not be a number' );
			return null;
		}
		return trim ( str_replace ( APP_ROOT . '/', '', $folder ), '/' ) . '/';
	}
}