<?php

namespace FragTale\Service;

use FragTale\Implement\AbstractService;
use FragTale\Constant\MessageType;

/**
 *
 * @author Fabrice Dant <fragtale.development@gmail.com>
 * @copyright 2024 FragTale 2 - Fabrice Dant
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-fr.txt CeCILL Licence 2.1 (French version)
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-en.txt CeCILL Licence 2.1 (English version)
 *         
 */
class ErrorHandler extends AbstractService {
	/**
	 * Catching PHP exceptions
	 *
	 * @param \Throwable $Throwable
	 * @return self
	 */
	public function catchThrowable(\Throwable $Throwable): self {
		$message = $Throwable->getMessage ();
		$errclass = get_class ( $Throwable );
		$type = substr ( $errclass, ( int ) strrpos ( $errclass, '\\' ) );
		if (! $type)
			$type = dgettext ( 'core', 'Error' );
		$file = $Throwable->getFile ();
		$line = $Throwable->getLine ();
		$fullMessage = sprintf ( dgettext ( 'core', '%1s in "%2s" at line %3s: %4s' ), dgettext ( 'core', $type ), $file, $line, $message ) . "\n";
		foreach ( $Throwable->getTrace () as $trace ) {
			$fullMessage .= '	# ';
			if (isset ( $trace ['file'] ))
				$fullMessage .= '"' . $trace ['file'] . '" (' . $trace ['line'] . '): ';
			if (isset ( $trace ['class'] ))
				$fullMessage .= $trace ['class'];
			if (isset ( $trace ['class'] ) && isset ( $trace ['function'] ))
				$fullMessage .= '::';
			if (isset ( $trace ['function'] ))
				$fullMessage .= $trace ['function'];
			if (isset ( $trace ['args'] ))
				$fullMessage .= ', args: ' . print_r ( $trace ['args'], true );
			$fullMessage .= "\n";
		}
		if (IS_CLI)
			$this->getSuperServices ()->getCliService ()->printError ( $fullMessage );
		else {
			$debugOn = $this->getSuperServices ()->getDebugService ()->isActivated ();
			$this->getSuperServices ()->getFrontMessageService ()->add ( nl2br ( $debugOn ? $fullMessage : $message ), MessageType::FATAL_ERROR );
		}
		return $this->log ( $fullMessage, null, 'throwables_caught_' );
	}

	/**
	 * Handling PHP errors
	 *
	 * @param array $error
	 * @return self
	 */
	public function handle(array $error): self {
		// The first 4 rows of a PHP error array (given by native PHP error handler):
		// 1. err code
		// 2. error message
		// 3. file
		// 4. line
		$fullMessage = sprintf ( dgettext ( 'core', "Error code %1s: %2s\n" ), array_shift ( $error ), array_shift ( $error ) );
		$fullMessage .= sprintf ( dgettext ( 'core', 'In %1s (%2s)' ), array_shift ( $error ), array_shift ( $error ) );
		if ($this->getSuperServices ()->getDebugService ()->isActivated ())
			$fullMessage .= "\nContext variables: " . print_r ( $error, true );

		if (IS_CLI)
			$this->getSuperServices ()->getCliService ()->printError ( $fullMessage );
		else
			$this->getSuperServices ()->getFrontMessageService ()->add ( nl2br ( $fullMessage ), MessageType::FATAL_ERROR );
		$this->log ( $fullMessage );
		return $this;
	}
}