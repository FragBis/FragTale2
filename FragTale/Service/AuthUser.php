<?php

namespace FragTale\Service;

use FragTale\Implement\AbstractService;
use FragTale\DataCollection;

/**
 *
 * @author Fabrice Dant <fragtale.development@gmail.com>
 * @copyright 2024 FragTale 2 - Fabrice Dant
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-fr.txt CeCILL Licence 2.1 (French version)
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-en.txt CeCILL Licence 2.1 (English version)
 *         
 */
class AuthUser extends AbstractService {
	protected string $sessionKey = 'FragTaleAuthUser';
	protected string $defaultIdentifyingKey = 'id';

	/**
	 *
	 * @param string $matchRequiredKey
	 * @return bool
	 */
	function isAuthenticated(?string $matchRequiredKey = null): bool {
		if (! $matchRequiredKey)
			$matchRequiredKey = $this->defaultIdentifyingKey;
		if (! $this->getUserData () || ! $this->getUserData ()->findByKey ( $matchRequiredKey ))
			return false;
		return true;
	}

	/**
	 *
	 * @return DataCollection|NULL
	 */
	public function getUserData(): ?DataCollection {
		$AuthUser = $this->getSuperServices ()->getSessionService ()->getVar ( $this->sessionKey );
		return $AuthUser instanceof DataCollection && $AuthUser->findByKey ( $this->defaultIdentifyingKey ) ? $AuthUser : null;
	}

	/**
	 *
	 * @param DataCollection $UserData
	 * @return self
	 */
	public function setUserData(DataCollection $UserData): self {
		$this->getSuperServices ()->getSessionService ()->setVar ( $this->sessionKey, $UserData );
		return $this;
	}

	/**
	 *
	 * @return self
	 */
	public function clearUserSession(): self {
		$this->getSuperServices ()->getSessionService ()->unsetVar ( $this->sessionKey );
		return $this;
	}
}