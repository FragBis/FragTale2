<?php

namespace FragTale\Service;

use FragTale\Implement\AbstractService;
use FragTale\DataCollection;
use FragTale\Constant\MessageType;

/**
 *
 * @author Fabrice Dant <fragtale.development@gmail.com>
 * @copyright 2024 FragTale 2 - Fabrice Dant
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-fr.txt CeCILL Licence 2.1 (French version)
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-en.txt CeCILL Licence 2.1 (English version)
 *         
 */
class FrontMessage extends AbstractService {
	const SESSION_KEY = 'FragTaleFrontMessages';

	/**
	 * Messages collection
	 *
	 * @var DataCollection
	 */
	private DataCollection $MessagesByType;

	/**
	 */
	function __construct() {
		parent::__construct ();
		$this->MessagesByType = new DataCollection ();
	}

	/**
	 * Get non persistent messages.
	 *
	 * @param int $messageType
	 *        	Refers to FragTale\Constant\MessageType constants
	 * @return DataCollection|NULL
	 */
	public function getMessages(?int $messageType = null): ?DataCollection {
		$Messages = ($messageType === null) ? $this->MessagesByType : $this->MessagesByType->findByKey ( $messageType );
		return $Messages instanceof DataCollection ? $Messages : null;
	}

	/**
	 * Get messages stored in session variables.
	 *
	 * @param int $messageType
	 *        	Refers to FragTale\Constant\MessageType constants
	 * @param bool $release
	 *        	If true, it will immediately delete fetched messages from the session vars.
	 * @return DataCollection|NULL
	 */
	public function getSessionMessages(?int $messageType = null, bool $release = false): ?DataCollection {
		$SessionVars = $this->getSuperServices ()->getSessionService ()->getVars ();
		if (! ($SessionMessages = $SessionVars->findByKey ( self::SESSION_KEY )))
			return null;
		$Messages = ($messageType === null) ? $SessionMessages : $SessionMessages->findByKey ( $messageType );
		if ($release)
			if ($messageType)
				$SessionMessages->delete ( $messageType );
			else
				$SessionVars->delete ( self::SESSION_KEY );
		return $Messages instanceof DataCollection ? $Messages : null;
	}

	/**
	 * Add message to a list of front messages to display after during template rendering, regrouped by given message type.
	 * These messages are not kept into session. They will be lost after a redirection or a page refresh.
	 *
	 * @param string $message
	 * @param int $messageType
	 *        	Refers to FragTale\Constant\MessageType constants. For example: MessageType::ERROR
	 */
	public function add(string $message, int $messageType = 0): self {
		if (! trim ( strip_tags ( $message ) ))
			return $this;
		$Messages = $this->MessagesByType->findByKey ( $messageType );
		if (! $Messages instanceof DataCollection)
			$Messages = new DataCollection ();
		// Prevent duplicate messages
		if (! $Messages->find ( function ($ix, $elt) use ($message) {
			return is_string ( $elt ) && ($elt == $message);
		} )->count ())
			$Messages->push ( $message );
		$this->MessagesByType->upsert ( $messageType, $Messages );
		return $this;
	}

	/**
	 * <b>Session</b> must have been <b>started</b> before!
	 *
	 * Store your messages in session variables.
	 * This will keep your messages from one page to another.
	 * It is recommended to store messages in session before a redirection.
	 *
	 * @param string $message
	 * @param int $messageType
	 *        	Refers to FragTale\Constant\MessageType constants. For example: MessageType::ERROR
	 */
	public function addInSession(string $message, int $messageType = MessageType::DEFAULT): self {
		if (! trim ( strip_tags ( $message ) ))
			return $this;
		$SessionVars = $this->getSuperServices ()->getSessionService ()->getVars ();
		if (! ($SessionMessages = $SessionVars->findByKey ( self::SESSION_KEY )) || ! ($SessionMessages instanceof DataCollection))
			$SessionMessages = $SessionVars->upsert ( self::SESSION_KEY, [ ] )->findByKey ( self::SESSION_KEY );
		if (! ($Messages = $SessionMessages->findByKey ( $messageType )) || ! ($Messages instanceof DataCollection))
			$Messages = $SessionMessages->upsert ( $messageType, [ ] )->findByKey ( $messageType );
		// Prevent duplicate messages
		if (! $Messages->find ( function ($ix, $elt) use ($message) {
			return is_string ( $elt ) && ($elt == $message);
		} )->count ())
			$Messages->push ( $message );
		return $this;
	}
}