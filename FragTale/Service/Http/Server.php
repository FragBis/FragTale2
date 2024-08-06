<?php

namespace FragTale\Service\Http;

use FragTale\Implement\AbstractService;

/**
 *
 * @author Fabrice Dant <fragtale.development@gmail.com>
 * @copyright 2024 FragTale 2 - Fabrice Dant
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-fr.txt CeCILL Licence 2.1 (French version)
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-en.txt CeCILL Licence 2.1 (English version)
 *         
 */
class Server extends AbstractService {
	function getProtocolVersion(): string {
		return isset ( $_SERVER ['SERVER_PROTOCOL'] ) ? $_SERVER ['SERVER_PROTOCOL'] : '';
	}
	function getProtocol(): string {
		return isset ( $_SERVER ['REQUEST_SCHEME'] ) ? strtolower ( $_SERVER ['REQUEST_SCHEME'] ) : '';
	}
	function getHost(bool $includeProtocol = false): string {
		if (! IS_HTTP_REQUEST)
			return '';
		$protocol = $includeProtocol ? $this->getProtocol () . '://' : '';
		return isset ( $_SERVER ['HTTP_HOST'] ) ? $protocol . $_SERVER ['HTTP_HOST'] : '';
	}
	function getAddress(): string {
		return isset ( $_SERVER ['SERVER_ADDR'] ) ? $_SERVER ['SERVER_ADDR'] : '';
	}
	function getPort(): string {
		return isset ( $_SERVER ['SERVER_PORT'] ) ? $_SERVER ['SERVER_PORT'] : '';
	}
	function getSoftware(): string {
		return isset ( $_SERVER ['SERVER_SOFTWARE'] ) ? $_SERVER ['SERVER_SOFTWARE'] : '';
	}
	function getAlias(): string {
		return isset ( $_SERVER ['PHP_SELF'] ) ? trim ( ( string ) $_SERVER ['PHP_SELF'], '/index.php' ) : '';
	}
	function getBaseUrl(): string {
		if (defined ( 'BASE_URL' ) && is_string ( BASE_URL ))
			return BASE_URL;
		if (IS_HTTP_REQUEST) {
			$host = $this->getHost ( true );
			$port = ( int ) $this->getPort ();
			$baseUrl = $host . (! in_array ( $port, [ 
					80,
					443
			] ) ? ":$port" : '');
			$alias = $this->getAlias ();
			return $baseUrl . ($alias ? "/$alias" : '');
		}
		return ( string ) $this->getSuperServices ()
			->getProjectService ()
			->getCustomParameters ()
			->findByKey ( 'base_url' );
	}
	function getRemoteAddress(): string {
		return isset ( $_SERVER ['REMOTE_ADDR'] ) ? $_SERVER ['REMOTE_ADDR'] : '';
	}
}