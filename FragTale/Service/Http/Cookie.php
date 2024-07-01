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
class Cookie extends AbstractService {
	private string $cookie;
	private DataCollection $Cookies;

	/**
	 */
	function __construct() {
		$this->cookie = isset ( $_SERVER ['HTTP_COOKIE'] ) ? $_SERVER ['HTTP_COOKIE'] : '';
		$this->Cookies = new DataCollection ( isset ( $_COOKIE ) ? $_COOKIE : [ ] );
		$this->registerSingleInstance ();
	}

	/**
	 *
	 * @return array
	 */
	public function getCookies(): array {
		return $this->Cookies->getData ( true );
	}

	/**
	 *
	 * @param string $key
	 * @param string $value
	 * @param int $lifeTimeInDays
	 *        	Default, 30 days
	 * @param array $options
	 *        	By default, if $options is not passed, values are:
	 *        	[
	 *        	'path' => '/',
	 *        	'samesite' => 'lax', // Accept "strict"
	 *        	'secure' => true if your server is running HTTPS,
	 *        	'httponly' => false
	 *        	]
	 * @return self
	 */
	public function set(string $key, string $value, int $lifeTimeInDays = 30, ?array $options = null): self {
		$expires = time () + ($lifeTimeInDays * 86400);
		$tolowerOptions = [ ];
		if (! empty ( $options ))
			foreach ( $options as $k => $v )
				$tolowerOptions [strtolower ( $k )] = $v;
		$tolowerOptions ['expires'] = $expires;
		if (! isset ( $tolowerOptions ['path'] ))
			$tolowerOptions ['path'] = '/';
		if (! isset ( $tolowerOptions ['samesite'] ))
			$tolowerOptions ['samesite'] = 'lax';
		if (! isset ( $tolowerOptions ['secure'] ))
			$tolowerOptions ['secure'] = ($this->getSuperServices ()->getHttpServerService ()->getProtocol () === 'https');
		if (! isset ( $tolowerOptions ['httponly'] ))
			$tolowerOptions ['httponly'] = false;
		setcookie ( $key, $value, $tolowerOptions );
		$this->Cookies->upsert ( $key, $value );
		return $this;
	}

	/**
	 *
	 * @return string|null
	 */
	public function get($key): ?string {
		return $this->Cookies->findByKey ( $key );
	}

	/**
	 *
	 * @param string $key
	 * @return self
	 */
	public function unset(string $key): self {
		$this->Cookies->delete ( $key );
		setcookie ( $key, null, - 1, '/' );
		return $this;
	}

	/**
	 * $_SERVER ['HTTP_COOKIE']
	 *
	 * @return string
	 */
	public function __toString(): string {
		return $this->cookie;
	}
}

