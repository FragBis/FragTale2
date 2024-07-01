<?php
use FragTale\Application;
use Console\Help;
use FragTale\Constant\TemplateFormat;
use FragTale\Service\Cli;
use FragTale\DataCollection;

/**
 *
 * FragTale 2.1 Open Source PHP Framework
 *
 * @copyright FragTale 2024
 * @author Fabrice Dant
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-fr.txt CeCILL Licence 2.1 (French version)
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-en.txt CeCILL Licence 2.1 (English version)
 *         
 */

# # All system constants are declared here, on the beginning.
/**
 * For measuring PHP process time
 */
if (! defined ( 'APP_START_TIME' ))
	define ( 'APP_START_TIME', microtime ( true ) );

define ( 'FRAGTALE_VERSION', '2.1' );

/**
 * IS_HTTP_REQUEST is already set "true" in "public/index.php" file as it is meant to be requested by HTTP client.
 * If it has not been set yet, then it must be declared false here.
 */
if (! defined ( 'IS_HTTP_REQUEST' )) {
	define ( 'IS_HTTP_REQUEST', false );
	if (! defined ( 'IS_CLI' ))
		define ( 'IS_CLI', true );
}

/**
 * IS_CLI is already set "true" in "fragtale" file as it is meant to be executed in console.
 * If it has not been set yet, then it must be declared false here.
 */
if (! defined ( 'IS_CLI' ))
	define ( 'IS_CLI', false );

/**
 * Application's folder base path.
 */
define ( 'APP_ROOT', rtrim ( __DIR__, '/' ) );

/**
 * Class autoloader
 * All classes and namespaces must match folders and files tree from APP_ROOT
 * Namespace, path and route are fusionned notions.
 */
spl_autoload_register ( function ($class) {
	if (! IS_CLI && strpos ( $class, 'Console\\' ) === 0) {
		throw new LogicException ( dgettext ( 'core', '"Console" namespaces are not accessible in HTTP mode.' ) );
		return;
	}
	$filename = APP_ROOT . '/' . str_replace ( '\\', '/', "$class.php" );
	if (file_exists ( $filename ))
		require_once $filename;
} );

/* Routing application */
$Application = new Application ();

/* Handle errors & define reporting level */
set_exception_handler ( function ($Throwable) use ($Application) {
	$Application->getSuperServices ()->getErrorHandlerService ()->catchThrowable ( $Throwable );
} );

set_error_handler ( function ($args) use ($Application) {
	$args = func_get_args ();
	$Application->getSuperServices ()->getErrorHandlerService ()->handle ( $args );
} );

if ($Application->getSuperServices ()->getDebugService ()->isActivated ()) {
	ini_set ( 'display_errors', 'On' );
	error_reporting ( E_ALL );
}

/* Launch application */
define ( 'BASE_URL', IS_HTTP_REQUEST ? $Application->getSuperServices ()->getRouteService ()->getBaseUrl () : null );

if ($MainController = $Application->getSuperServices ()->getRouteControllerFactoryService ()->createMainController ()) {
	try {
		$View = $MainController->run ();
	} catch ( Throwable $T ) {
		$Application->getSuperServices ()->getErrorHandlerService ()->catchThrowable ( $T );
	}
	if (IS_HTTP_REQUEST) {
		$Application->getSuperServices ()->getHttpResponseService ()->sendHeaders ();
		echo $View;
		if ($Application->getSuperServices ()->getDebugService ()->isActivated () && in_array ( $MainController->getTemplate ()->getFormatId (), [ 
				TemplateFormat::HTML,
				TemplateFormat::HTML_NO_LAYOUT
		] )) {
			$message = dgettext ( 'core', 'PHP process time:' ) . ' ' . number_format ( (microtime ( true ) - APP_START_TIME), 5, '.', '' ) . 's | ';
			$message .= dgettext ( 'core', 'Allocated mem:' ) . ' ' . number_format ( memory_get_peak_usage () / 1024 / 1024, 2, '.', '' ) . dgettext ( 'core', 'MB' ) . ' | ';
			$message .= dgettext ( 'core', 'PHP version:' ) . ' ' . phpversion ();
			echo $Application->getSuperServices ()->getDebugService ()->getHtmlInfo ( $message );
		}
	}
} elseif ($Application->getSuperServices ()
	->getFrontMessageService ()
	->getMessages ()
	->count ()) {
	$msg = dgettext ( 'core', 'You have fatal errors at low level application. Controller could not have been instantiated:' );
	if (IS_HTTP_REQUEST)
		echo '<div style="color: red; font-weight: bold;">' . $msg . '</div>';
	else
		$Application->getSuperServices ()->getCliService ()->printError ( $msg );
	$Application->getSuperServices ()
		->getFrontMessageService ()
		->getMessages ()
		->forEach ( function ($key, $messages) use ($Application) {
		if ($messages instanceof DataCollection) {
			$messages->forEach ( function ($i, $msg) use ($Application) {
				if (IS_HTTP_REQUEST)
					echo '<div style="color: red;">' . $msg . '</div>';
				else
					$Application->getSuperServices ()
						->getCliService ()
						->printError ( $msg );
			} );
		}
	} );
}

# # info
if (IS_CLI && ! $MainController instanceof Help) {
	$message = dgettext ( 'core', 'PHP process time:' ) . ' ' . number_format ( (microtime ( true ) - APP_START_TIME), 5, '.', '' ) . 's | ';
	$message .= dgettext ( 'core', 'Allocated mem:' ) . ' ' . number_format ( memory_get_peak_usage () / 1024 / 1024, 2, '.', '' ) . dgettext ( 'core', 'MB' ) . ' | ';
	$message .= dgettext ( 'core', 'PHP version:' ) . ' ' . phpversion ();
	$Application->getSuperServices ()->getCliService ()->print ( ($Application->getSuperServices ()
		->getCliService ()
		->isPrintedInColor () ? Cli::COLOR_GREEN : '') . dgettext ( 'core', 'FINISH' ) . ($Application->getSuperServices ()
		->getCliService ()
		->isPrintedInColor () ? Cli::COLOR_WHITE : '') . ' - ' . $message );
}