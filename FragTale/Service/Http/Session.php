<?php

namespace FragTale\Service\Http;

use FragTale\Implement\AbstractService;
use FragTale\Application\SessionHandler;
use FragTale\Constant\MessageType;
use FragTale\DataCollection;

/**
 * Using this Session service will allow you to store and read session variables from MongoDB.
 * This service use exclusively the MongoDB server configured in your "default_mongo" project database settings.
 *
 * @author Fabrice Dant <fragtale.development@gmail.com>
 * @copyright 2024 FragTale 2 - Fabrice Dant
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-fr.txt CeCILL Licence 2.1 (French version)
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-en.txt CeCILL Licence 2.1 (English version)
 *         
 */
class Session extends AbstractService {
	protected SessionHandler $Handler;
	protected DataCollection $Vars;
	function __construct() {
		parent::__construct ();
		$this->Vars = new DataCollection ();
	}

	/**
	 * Check if session is active
	 *
	 * @return bool
	 */
	public function isActive(): bool {
		return session_status () === PHP_SESSION_ACTIVE;
	}

	/**
	 * Do not use this function if you don't want to handle sessions with MongoDB.
	 * Use the default file session handler from your system or override the session handler with your methods.
	 *
	 * @return self
	 */
	public function start(): self {
		if (IS_HTTP_REQUEST) {
			if (! $this->isActive ()) {
				if (! isset ( $this->Handler )) {
					$this->Handler = new SessionHandler ();
					session_save_path ( 'mongodb://' );
					session_set_save_handler ( $this->Handler, true );
				}
				if (! session_start ()) {
					$msg = dgettext ( 'core', 'Unabled to start session' );
					$this->log ( $msg )
						->getSuperServices ()
						->getFrontMessageService ()
						->add ( $msg, MessageType::ERROR );
				} else
					// Set Session Vars
					$this->Vars = $this->Handler->getRow ();
			}
		}
		return $this;
	}

	/**
	 * Keep the session always alive.
	 * Only working if handler is a FragTale\Application\SessionHandler
	 *
	 * @param bool $keep
	 * @return self
	 */
	public function keepSessionAlive(bool $keep): self {
		if (! $this->isActive ())
			$this->start ();
		if (is_a ( $this->Handler, SessionHandler::class ))
			$this->Handler->keepSession ( $keep );
		return $this;
	}

	/**
	 * Run garbage collector
	 *
	 * @see \SessionHandler::gc()
	 * @param int $maxlifetime
	 *        	In seconds
	 * @return self
	 */
	public function gc(int $maxlifetime): self {
		if (is_a ( $this->Handler, SessionHandler::class ))
			$this->Handler->gc ( $maxlifetime );
		return $this;
	}

	/**
	 * Get all $_SESSION variables
	 *
	 * @return DataCollection
	 */
	public function getVars(): DataCollection {
		if (! $this->isActive ())
			$this->start ();
		return $this->Vars;
	}

	/**
	 *
	 * @param string $key
	 * @return mixed|NULL
	 */
	public function getVar(string $key) {
		if (! $this->isActive ())
			$this->start ();
		return $this->Vars->findByKey ( $key );
	}

	/**
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return self
	 */
	public function setVar(string $key, $value): self {
		if (! $this->isActive ())
			$this->start ();
		$this->Vars->upsert ( $key, $value );
		return $this;
	}

	/**
	 *
	 * @param string $key
	 * @return self
	 */
	public function unsetVar(string $key): self {
		if (! $this->isActive ())
			$this->start ();
		$this->Vars->delete ( $key );
		return $this;
	}

	/**
	 *
	 * @return SessionHandler
	 */
	public function getHandler(): SessionHandler {
		return $this->Handler;
	}

	/**
	 * You can create a specific session handler, not using MongoDB by default, but you must create a new class that extends FragTale\Application\SessionHandler
	 * Default PHP session handler is not used in this service.
	 * To use the default PHP session handler that uses the filesystem, just use the native session classes and functions.
	 *
	 * @param SessionHandler $SessionHandler
	 *        	Instance of (or child of) FragTale\Application\SessionHandler
	 * @return self
	 */
	public function setHandler(SessionHandler $SessionHandler): self {
		$this->Handler = $SessionHandler;
		return $this;
	}
}