<?php

namespace FragTale\Service\Factory;

use FragTale\Implement\AbstractService;
use FragTale\Application\Controller;
use FragTale\Service\Cli;
use FragTale\Service\Configuration;
use Console\Help;
use FragTale\Application\Controller\Page404;
use FragTale\Application\Controller\Media;
use FragTale\Application\Controller\Maintenance;

/**
 *
 * @author Fabrice Dant <fragtale.development@gmail.com>
 * @copyright 2024 FragTale 2 - Fabrice Dant
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-fr.txt CeCILL Licence 2.1 (French version)
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-en.txt CeCILL Licence 2.1 (English version)
 *         
 */
class RouteControllerFactory extends AbstractService {

	/**
	 * This must not be set in constructor
	 *
	 * @var Controller
	 */
	private Controller $MainController;

	/**
	 * The main controller is the requested controller called via URL or CLI.
	 * All controllers called inside another controller (for example, a block) are not main controllers.
	 * There is obviously only one main controller, and it is the first controller being instanciated.
	 *
	 * @return Controller|NULL
	 */
	final public function getMainController(): ?Controller {
		return isset ( $this->MainController ) ? $this->MainController : null;
	}

	/**
	 * Set instance of main controller (the top requested controller).
	 * Note that controllers used inside another controller cannot be main controllers.
	 * There is obviously only one main controller, and it is the first controller being instanciated.
	 *
	 * @param Configuration $Configuration
	 * @return Controller
	 */
	final public function createMainController(): ?Controller {
		if (! isset ( $this->MainController )) {
			try {
				$this->MainController = IS_CLI ? $this->createMainCliController () : $this->createMainWebController ();
			} catch ( \Throwable $T ) {
				$this->getSuperServices ()->getErrorHandlerService ()->catchThrowable ( $T );
			}
		}
		return $this->getMainController ();
	}

	/**
	 * Instance of main controller, in CLI mode.
	 *
	 * @return Controller|NULL
	 */
	private function createMainCliController(): Controller {
		$CliService = $this->getSuperServices ()->getCliService ();
		$controllerClassName = '';
		$route = trim ( ( string ) $CliService->getOpt ( '_route_index' ), '/' );
		if (substr ( $route, - 4 ) === '.php')
			$route = substr ( $route, 0, strlen ( $route ) - 4 );

		// Define if colors must be used
		if ($this->getSuperServices ()->getLocalizeService ()->meansYes ( $CliService->getOpt ( 'no-color' ) ))
			$CliService->forceColorPrinting ( false );

		if (in_array ( $route, [ 
				null,
				'',
				'.',
				'Console',
				'fragtale',
				'main'
		] )) {
			// Executing HELP if _route_index matches one of these
			$CliService->setIsTimeDisplayedByDefault ( false );
			return new Help ();
		} else {
			$CliService->printInColor ( dgettext ( 'core', 'START' ), Cli::COLOR_GREEN );

			$retracedPath = APP_ROOT . '/' . $route;
			if (! pathinfo ( $retracedPath, PATHINFO_EXTENSION ))
				$retracedPath .= '.php';
			if ($absoluteFilePath = realpath ( $retracedPath )) {
				$ftype = strtolower ( mime_content_type ( $absoluteFilePath ) );
				if ($ftype === 'text/x-php')
					$controllerClassName = str_replace ( '/', '\\', $route );
				else
					$CliService->printError ( "$absoluteFilePath " . dgettext ( 'core', 'is not a well formed PHP file.' ) );
			} else
				$CliService->printError ( "$retracedPath " . dgettext ( 'core', 'does not exist' ) );
		}

		if ($controllerClassName && class_exists ( $controllerClassName )) {
			try {
				$Controller = new $controllerClassName ();
			} catch ( \Throwable $T ) {
				$this->getSuperServices ()->getErrorHandlerService ()->catchThrowable ( $T );
			}
			if ($Controller instanceof Controller)
				return $Controller;
			else
				$CliService->printError ( dgettext ( 'core', "$controllerClassName " . dgettext ( 'core', "Please choose a class that extends a Controller" ) ) );
		} else
			$CliService->printError ( "$controllerClassName " . dgettext ( 'core', 'File and/or class not found' ) );
		return new Controller ();
	}

	/**
	 * Instance of main controller, in Web mode.
	 *
	 * @return Controller|NULL
	 */
	private function createMainWebController(): Controller {
		$ProjectService = $this->getSuperServices ()->getProjectService ();
		$RequestService = $this->getSuperServices ()->getHttpRequestService ();
		$projectPath = $ProjectService->getBaseDir ();
		if (! $projectPath || ! file_exists ( $projectPath )) {
			// Application cannot find a project bound to the host name being requested or its directory.
			// A project should have not been correctly deployed.
			// Check into project's "resources/configuration/hosts.json" file to see if the host name is listed in.
			return new Maintenance ();
		}

		// Is website in maintenance?
		$EnvParams = $ProjectService->getEnvSettings ()->findByKey ( 'parameters' );
		$isInMaintenance = ( int ) $ProjectService->getCustomParameters ()->findByKey ( 'maintenance' ) || ($EnvParams && ( int ) $EnvParams->findByKey ( 'maintenance' ));

		if (! ($route = strtolower ( trim ( ( string ) $RequestService->getParamValue ( '_route_index' ), '/' ) )))
			$route = 'home';

		$is404 = false;
		$isMedia = (strpos ( $route, 'media/' ) === 0);
		$route = $this->getSuperServices ()->getRouteService ()->convertUriToNamespace ( $route ); // TODO, if route index is null, anticipate use of module "Alias" that will allow bindings between controllers and custom URI
		$controllerClassName = $ProjectService->getBaseControllerNamespace () . "\\Web\\$route";
		if ($isMedia) {
			$customMediaClass = $ProjectService->getBaseControllerNamespace () . '\\Web\\Media';
			if (class_exists ( $customMediaClass ) && ($Controller = new $customMediaClass ()) && $Controller instanceof Controller)
				return $Controller;
			else
				return new Media (); // use default Media otherwise
		} elseif ($isInMaintenance) {
			$controllerClassName = $ProjectService->getBaseControllerNamespace () . "\\Maintenance";
			if (! class_exists ( $controllerClassName ))
				return new Maintenance ();
			return new $controllerClassName ();
		} elseif (! class_exists ( $controllerClassName ))
			$is404 = true;
		else {
			try {
				$Controller = new $controllerClassName ();
			} catch ( \Throwable $T ) {
				$this->getSuperServices ()->getErrorHandlerService ()->catchThrowable ( $T );
			}
			if (! ($Controller instanceof Controller))
				$is404 = true;
		}
		if ($is404) {
			// Create 404
			$custom404Class = $ProjectService->getName () . '\\Page404'; // Try to instanciate the 404 project controller first
			if (class_exists ( $custom404Class ) && ($Controller = new $custom404Class ()) && $Controller instanceof Controller)
				return $Controller;
			else
				return new Page404 (); // use default 404 otherwise
		}
		return $Controller;
	}

	/**
	 * Check if a controller is the main controller, e.g.
	 * the one being requested and routed (other controllers are most of time blocks)
	 *
	 * @param Controller $Controller
	 * @return bool
	 */
	final public function isMainController(Controller $Controller): bool {
		if (! isset ( $this->MainController ))
			return true;
		return $Controller === $this->MainController;
	}
}