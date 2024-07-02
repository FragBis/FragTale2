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
class Request extends AbstractService {

	/**
	 *
	 * @var DataCollection
	 */
	private static DataCollection $Params;

	/**
	 */
	function __construct() {
		self::$Params = new DataCollection ( IS_HTTP_REQUEST ? $_REQUEST : null );
		$inputs = [ ];
		parse_str ( trim ( ( string ) file_get_contents ( 'php://input' ), '&' ), $inputs );
		if (! empty ( $inputs ))
			foreach ( $inputs as $key => $value )
				self::$Params->upsert ( $key, $value );

		parent::__construct ();
	}

	/**
	 * It returns all requested, posted parameters.
	 *
	 * @return DataCollection
	 */
	final public function getParams(): DataCollection {
		return self::$Params->clone ();
	}

	/**
	 * Returns NULL if key does not exist (an empty passed param should be an empty string)
	 *
	 * @param string $key
	 * @return mixed
	 */
	final public function getParamValue(string $key): mixed {
		return $this->getParams ()->findByKey ( $key );
	}

	/**
	 * Returns all existing keys from requested and posted parameters.
	 *
	 * @return array
	 */
	final public function getParamKeys(): array {
		return self::$Params->keys ();
	}

	/**
	 * Returns the request method (GET, POST, PUT etc.)
	 *
	 * @return string|NULL
	 */
	final public function getMethod(): ?string {
		return ! empty ( $_SERVER ['REQUEST_METHOD'] ) ? strtoupper ( $_SERVER ['REQUEST_METHOD'] ) : null;
	}

	/**
	 * Get the $_SERVER['REQUEST_URI'] value, but you can take URI without parameters.
	 *
	 * @param bool $ignoreParams
	 * @return string
	 */
	final public function getUri(bool $ignoreParams = true): string {
		$requestUri = isset ( $_SERVER ['REQUEST_URI'] ) ? trim ( ( string ) $_SERVER ['REQUEST_URI'], '/' ) : null;
		if (! $requestUri)
			return '';
		$questPos = strpos ( $requestUri, '?' );
		if (! $ignoreParams || $questPos === false)
			return $requestUri;
		return substr ( $requestUri, 0, $questPos );
	}

	/**
	 * Same as getUri but it includes base URL.
	 *
	 * @return string
	 */
	final public function getUrl(bool $ignoreParams = true) {
		$baseUrl = $this->getSuperServices ()->getHttpServerService ()->getBaseUrl ();
		$uri = $this->getUri ( $ignoreParams );
		return $uri ? "$baseUrl/$uri" : $baseUrl;
	}

	/**
	 * Returns $_SERVER ['REQUEST_TIME'] or $_SERVER ['REQUEST_TIME_FLOAT']
	 *
	 * @param bool $asFloat
	 * @return float|NULL
	 */
	final public function getTime(bool $asFloat = false): ?float {
		if (! IS_HTTP_REQUEST)
			return null;
		$requestTime = isset ( $_SERVER ['REQUEST_TIME'] ) ? $_SERVER ['REQUEST_TIME'] : null;
		$requestTimeFloat = isset ( $_SERVER ['REQUEST_TIME_FLOAT'] ) ? $_SERVER ['REQUEST_TIME_FLOAT'] : $requestTime;
		return $asFloat ? $requestTimeFloat : $requestTime;
	}

	/**
	 * Returns $_SERVER ['QUERY_STRINNG']
	 *
	 * @return string|NULL
	 */
	final public function getQueryString(): ?string {
		return isset ( $_SERVER ['QUERY_STRINNG'] ) ? $_SERVER ['QUERY_STRINNG'] : null;
	}

	/**
	 * Check if the request method matches given verb (GET, POST, PUT etc.)
	 *
	 *
	 * @param string $verb
	 *        	GET, POST, PUT etc.
	 * @return bool
	 */
	final public function isMethod(string $verb): bool {
		return $this->getMethod () === strtoupper ( $verb );
	}
}