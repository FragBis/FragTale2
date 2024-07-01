<?php
use FragTale\DataCollection;
use FragTale\Application\Controller;
use FragTale\Service\Cli;

/**
 *
 * @author Fabrice Dant <fragtale.development@gmail.com>
 * @copyright 2024 FragTale 2 - Fabrice Dant
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-fr.txt CeCILL Licence 2.1 (French version)
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-en.txt CeCILL Licence 2.1 (English version)
 *         
 */
abstract class Console extends Controller {

	/**
	 *
	 * @var boolean
	 */
	private bool $helpInvoked = false;
	/**
	 *
	 * @var Cli
	 */
	protected Cli $CliService;

	/**
	 */
	function __construct() {
		$this->CliService = $this->getSuperServices ()->getCliService ();
		$this->helpInvoked = ($this->getSuperServices ()->getCliService ()->getOpt ( 'h' ) || $this->getSuperServices ()->getCliService ()->getOpt ( 'help' ));
	}

	/**
	 * Indicate if user passed options -h or --help to invoke information on the controller to execute
	 *
	 * @return boolean
	 */
	protected function isHelpInvoked() {
		return $this->helpInvoked;
	}

	/**
	 * Execute a selected sub controller.
	 *
	 * @return void
	 */
	protected function launchSubController(): void {
		$subControllers = [ ];
		foreach ( glob ( APP_ROOT . '/' . str_replace ( '\\', '/', static::class ) . '/*.php' ) as $i => $file ) {
			$exp = explode ( '/', $file );
			$subControllers [$i + 1] = str_replace ( '.php', '', end ( $exp ) );
		}
		// Ask for modification per section
		$this->CliService->printInColor ( dgettext ( 'core', 'Select one of the controller(s) listed bellow:' ), Cli::COLOR_BLUE );
		foreach ( $subControllers as $i => $section )
			$this->CliService->printInColor ( "	$i. $section", Cli::COLOR_CYAN );
		if (is_numeric ( $number = $this->CliService->prompt ( dgettext ( 'core', 'Type number:' ) ) )) {
			if (isset ( $subControllers [$number] )) {
				$subControllerName = static::class . "\\" . $subControllers [$number];
				$this->CliService->printInColor ( sprintf ( dgettext ( 'core', 'Running controller %s' ), $subControllerName ), Cli::COLOR_ORANGE );
				(new $subControllerName ())->run ();
			}
		}
	}

	/**
	 *
	 * @param string $message
	 * @return mixed
	 */
	protected function promptToFindElementInCollection(string $message, DataCollection $Collection, ?string $defaultPosition = null, bool $returnKey = false) {
		if (! $Collection->count ())
			return null;
		$this->CliService->printInColor ( $message, Cli::COLOR_BLUE );
		foreach ( $Collection as $key => $element ) {
			$position = $Collection->position () + 1;
			if ($element instanceof DataCollection)
				$this->CliService->printInColor ( "	$position. $key " . ($element->findByKey ( 'connection_string' ) ? '(' . $element->findByKey ( 'connection_string' ) . ')' : ''), Cli::COLOR_LCYAN );
			else
				$this->CliService->printInColor ( "	$position. " . (is_numeric ( $key ) ? ( string ) $element : $key), Cli::COLOR_LCYAN );
		}
		if ($answer = $this->CliService->prompt ( dgettext ( 'core', 'Type number:' ), $defaultPosition )) {
			if (! is_numeric ( $answer ))
				return null;
			$ixToMatch = ( int ) $answer - 1;
			if (! $element = $Collection->findAt ( $ixToMatch ))
				return null;
			return $returnKey ? $Collection->key ( $ixToMatch ) : $element;
		} else
			return null;
	}
}