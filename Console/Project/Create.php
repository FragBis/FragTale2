<?php

namespace Console\Project;

use Console\Project;
use FragTale\DataCollection;
use FragTale\Constant\Setup\Locale;
use FragTale\Constant\Setup\CustomProjectPattern;
use FragTale\Constant\Setup\CorePath;
use FragTale\Service\Cli;
use FragTale\Constant\TemplateFormat;
use Console\Setup\Hosts;

/**
 *
 * @author Fabrice Dant
 *        
 */
class Create extends Project {
	private ?string $hostname = null;

	/**
	 *
	 * @var DataCollection
	 */
	protected DataCollection $ProjectTree;

	/**
	 */
	function __construct() {
		parent::__construct ();
		if ($this->isHelpInvoked ()) {
			$this->CliService->printInColor ( dgettext ( 'core', '**** Help invoked ****' ), Cli::COLOR_LCYAN )
				->printInColor ( dgettext ( 'core', 'CLI arguments:' ), Cli::COLOR_LCYAN )
				->print ( '	' . dgettext ( 'core', '· "--project": the project name to create (in camel case and no special chars)' ) )
				->print ( '	' . dgettext ( 'core', '· "--host": if passed, it will automatically bind this project to a hostname' ) )
				->printInColor ( dgettext ( 'core', '**********************' ), Cli::COLOR_LCYAN );
			return;
		}
		$this->ProjectTree = (new DataCollection ( json_decode ( file_get_contents ( CorePath::PATTERN_PROJECT_TREE_FILE ), true ) ));
	}

	/**
	 * Set host name from another controller before running this one (see Console\Install).
	 *
	 * @param string $hostname
	 * @return self
	 */
	public function setHostname(?string $hostname): self {
		$this->hostname = $hostname;
		return $this;
	}

	/**
	 *
	 * {@inheritdoc}
	 * @see \Console\Project::executeOnTop()
	 */
	protected function executeOnTop(): void {
		if ($this->isHelpInvoked ())
			return;

		$this->CliService->printInColor ( dgettext ( 'core', 'Entering project creator' ), Cli::COLOR_YELLOW );
	}

	/**
	 *
	 * {@inheritdoc}
	 * @see \Console\Project::executeOnConsole()
	 */
	protected function executeOnConsole(): void {
		if ($this->isHelpInvoked ())
			return;

		$LocalizeService = $this->getSuperServices ()->getLocalizeService ();
		if (! ($projectName = $this->CliService->getOpt ( 'project' )) && ! ($projectName = $this->CliService->prompt ( dgettext ( 'core', 'Type your project name (in camel cases, no spaces, no special chars, no accents and not starting with a number)' ) )))
			return;
		if (! ($projectName = $this->getSuperServices ()->getRouteService ()->convertUriToNamespace ( $projectName ))) {
			$this->CliService->printError ( dgettext ( 'core', 'Invalid namespace format.' ) );
			return;
		}
		$this->setProjectName ( $projectName, true );
		$this->CliService->printInColor ( sprintf ( dgettext ( 'core', 'Creating project "%s"...' ), $projectName ), Cli::COLOR_YELLOW );

		$projectDir = sprintf ( CustomProjectPattern::PATH, $projectName );
		$namespace = sprintf ( CustomProjectPattern::NAMESPACE, $projectName );

		// Load base project settings
		$ProjectSettings = new DataCollection ( json_decode ( str_replace ( '/*projectName*/', $projectName, file_get_contents ( CorePath::PATTERN_PROJECT_SETUP_FILE ) ), true ) );

		// Define the default template format Id (refers to FragTale\Constant\TemplateFormat constants)
		$templateFormatId = null;
		while ( ! in_array ( $templateFormatId, TemplateFormat::getConstants ()->getData ( true ) ) ) {
			$this->CliService->print ( dgettext ( 'core', 'When you create controllers, this will also create the corresponding view (unless you specify not to create the view with the controller).' ) )
				->print ( dgettext ( 'core', 'The HTML format corresponds to a classic view of displaying a Web page (creation of a ".phtml" file with HTML and PHP tags).' ) )
				->print ( dgettext ( 'core', 'The HTML_NO_LAYOUT format corresponds to a view called in AJAX without the general "layout" (without the large <html> tag, without the <head> tag and therefore, without the JS and CSS sources which are not useful during a response AJAX).' ) )
				->print ( dgettext ( 'core', 'JSON and XML formats correspond to sending data and are useful for a REST API.' ) )
				->print ( dgettext ( 'core', 'Other formats should generally not be used as the default template. When in doubt, choose 1.' ) );
			$templateFormatId = $this->promptToFindElementInCollection ( dgettext ( 'core', 'Choose your default template format:' ), TemplateFormat::getConstants (), 1 );
		}
		$this->CliService->print ( TemplateFormat::getConstants ()->getElementKey ( $templateFormatId ) );

		// default template & layout path
		$defaultLayoutPath = str_replace ( APP_ROOT . '/', '', sprintf ( CustomProjectPattern::LAYOUTS_DIR, $projectName ) ) . '/default.phtml';
		$defaultTemplatePath = str_replace ( APP_ROOT . '/', '', sprintf ( CustomProjectPattern::VIEWS_DIR, $projectName ) ) . '/default.phtml';

		// Set these defaults to settings collection
		$ProjectSettings->findByKey ( 'environments' )->forEach ( function ($key, $element) use ($templateFormatId, $defaultLayoutPath, $defaultTemplatePath) {
			if ($element instanceof DataCollection)
				$element->upsert ( 'default_template_format_id', $templateFormatId )
					->upsert ( 'default_layout_path', $defaultLayoutPath )
					->upsert ( 'default_template_path', $defaultTemplatePath );
		} );

		// Set values to be replaced in pattern files
		$inputParams = [ ];
		$inputParams ['dateTime'] = date ( 'Y-m-d H:i' );
		$inputParams ['namespace'] = $namespace;
		$inputParams ['projectName'] = $projectName;
		$inputParams ['projectPath'] = (strpos ( $projectDir, APP_ROOT ) === 0) ? substr ( $projectDir, strlen ( APP_ROOT ) + 1 ) : $projectDir;
		$inputParams ['use'] = $namespace . '\\Controller';
		$inputParams ['useWeb'] = $namespace . '\\WebController';
		$inputParams ['class'] = 'Home';
		$inputParams ['templatePath'] = 'home.phtml';

		// Scanning recursively project tree to create folders and files
		if (is_dir ( $projectDir )) {
			if (! $LocalizeService->meansYes ( $this->CliService->prompt ( dgettext ( 'core', 'Project folder already exists. Do you want to continue? [yN]' ), dgettext ( 'core', 'n {means no}' ) ) ))
				return;
		} elseif (! $this->getSuperServices ()->getFilesystemService ()->createDir ( $projectDir ))
			return;

		if (! $this->createProjectTree ( $this->ProjectTree, $projectDir, $inputParams ))
			return;

		// Saving conf file
		$ProjectSettings->exportToJsonFile ( sprintf ( CustomProjectPattern::SETTINGS_FILE, $projectName ), true );

		// Load project conf
		$this->setProjectAppConfig ();

		// Setup host
		if (( int ) $this->CliService->getOpt ( 'force' ) || $LocalizeService->meansYes ( $this->CliService->prompt ( dgettext ( 'core', 'Do you want to set project host? [yN]' ), dgettext ( 'core', 'n {means no}' ) ) ))
			(new Hosts ())->setHostname ( $this->hostname )->setProjectname ( $projectName )->run ();

		// Setup project
		(new Configure ())->run ();
	}

	/**
	 *
	 * @param DataCollection $Tree
	 * @param string $projectDir
	 * @param array $inputParams
	 * @return bool
	 */
	private function createProjectTree(DataCollection $Tree, string $projectDir, array $inputParams): bool {
		$this->CliService->print ( sprintf ( dgettext ( 'core', 'In folder "%s"' ), $projectDir ) );
		foreach ( $Tree as $key => $element ) {
			$this->getSuperServices ()->getCliService ()->print ( sprintf ( dgettext ( 'core', 'Working on %s' ), $key ) );
			if ($element instanceof DataCollection) {
				// It's a folder, then create it
				$currentDir = "$projectDir/$key";
				if (! $this->getSuperServices ()->getFilesystemService ()->createDir ( $currentDir ))
					return false;

				// Special case for locales
				if ($key === 'locales') {
					$localTree = $element->current ();
					Locale::getConstants ()->forEach ( function ($localeKey, $Locale) use ($localTree, $currentDir, $inputParams) {
						$lc_msg = $localTree->key ( 0 );
						$fileUri = $localTree->current ()->current ();
						$filename = $localTree->current ()->key ( 0 );
						$inputParams ['locale'] = $localeKey;
						if ($Locale instanceof DataCollection) {
							foreach ( [ 
									$localeKey,
									$lc_msg
							] as $subDir ) {
								$currentDir .= "/$subDir";
								if (! $this->getSuperServices ()
									->getFilesystemService ()
									->createDir ( $currentDir ))
									return false;
							}
						}
						if (! $this->createFile ( $fileUri, $currentDir, $filename, $inputParams ))
							return false;
					} );
				} elseif ($element->count ()) {
					$cloneParams = $inputParams;
					$cloneParams ['namespace'] = $inputParams ['namespace'] . "\\$key";
					if (! $this->createProjectTree ( $element, $currentDir, $cloneParams )) // Recursive
						return false;
				}
			} elseif ($element) {
				// element is URI of the code patterns
				if (! $this->createFile ( $element, $projectDir, $key, $inputParams ))
					return false;
			}
		}
		return true;
	}

	/**
	 *
	 * @param string $fileUri
	 * @param string $projectDir
	 * @param string $key
	 * @param string $namespace
	 * @param string $inputParams
	 * @return bool
	 */
	private function createFile(string $fileUri, string $directory, string $filename, array $inputParams): bool {
		// element is URI of the code patterns
		$expUri = explode ( '?', $fileUri );
		if (count ( $expUri ) > 1) {
			$uri = $expUri [0];
			$params = explode ( '&', ( string ) $expUri [1] );
		} else {
			$uri = $fileUri;
			$params = null;
		}
		$tplFile = APP_ROOT . "/$uri";
		$targetFile = $directory . "/$filename";
		if (! $params) {
			if (file_exists ( $targetFile ))
				$this->CliService->printWarning ( sprintf ( dgettext ( 'core', 'File "%s" already exists' ), $targetFile ) );
			// If URI has no parameters, there is nothing to replace, then just copy file
			elseif (copy ( $tplFile, $targetFile ))
				$this->CliService->printSuccess ( sprintf ( dgettext ( 'core', 'Created file: %s' ), $targetFile ) );
			else {
				$this->CliService->printError ( sprintf ( dgettext ( 'core', 'Could not create file: %s' ), $targetFile ) );
				return false;
			}
		} else {
			$outputParams = $replacingKeys = [ ];
			foreach ( $params as $paramName ) {
				if (array_key_exists ( $paramName, $inputParams )) {
					$outputParams [$paramName] = $inputParams [$paramName];
					$replacingKeys [] = "/*$paramName*/";
				}
			}

			$tplContent = str_replace ( $replacingKeys, $outputParams, file_get_contents ( $tplFile ) );
			return $this->getSuperServices ()->getFilesystemService ()->createFile ( $targetFile, $tplContent );
		}
		return true;
	}
}