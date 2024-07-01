<?php

namespace FragTale\Service\Http;

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
class Response extends AbstractService {
	private DataCollection $Headers;
	function __construct() {
		$this->Headers = new DataCollection ();
		parent::__construct ();
	}

	/**
	 *
	 * @param string $header
	 * @return self
	 */
	public function addHeader(string $header): self {
		$this->Headers->upsert ( $header, $header );
		return $this;
	}

	/**
	 *
	 * @return array
	 */
	public function getHeaders(): array {
		return $this->Headers;
	}

	/**
	 *
	 * @param int $position
	 * @return NULL|string|number|boolean|\FragTale\DataCollection
	 */
	public function getHeader(int $position) {
		return $this->Headers->findAt ( $position );
	}

	/**
	 *
	 * @return self
	 */
	public function sendHeaders(): self {
		foreach ( $this->Headers as $header )
			header ( $header );
		return $this;
	}

	/**
	 *
	 * @param string $url
	 */
	public function redirect(string $url): void {
		header ( 'Location:' . $url );
		exit ();
	}

	/**
	 *
	 * @param string $class
	 * @param array $params
	 */
	public function redirectToController(string $class, ?array $params = null): void {
		$this->redirect ( $this->getSuperServices ()
			->getRouteService ()
			->getControllerUrl ( $class, $params ) );
	}

	/**
	 * Immediately send 403 Forbidden with a message (kill process).
	 *
	 * @param string $message
	 */
	public function sendForbiddenImmediately(string $message): void {
		header ( 'HTTP/1.0 403 Forbidden' );
		die ( $message );
	}
}