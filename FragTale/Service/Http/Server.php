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
		return $_SERVER ['SERVER_PROTOCOL'] ?? '';
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
		return $_SERVER ['SERVER_ADDR'] ?? '';
	}
	function getPort(): string {
		return $_SERVER ['SERVER_PORT'] ?? '';
	}
	function getSoftware(): string {
		return $_SERVER ['SERVER_SOFTWARE'] ?? '';
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
		return $_SERVER ['REMOTE_ADDR'] ?? '';
	}
	/**
	 * IMPORTANT: never trust the user agent because it is sent by the client.
	 * Note: You can also use native PHP function "get_browser()" if you have configured "browscap" in php.ini file (for more details: https://www.php.net/manual/fr/function.get-browser.php)
	 * Mind that lists of user agents always evolve. Most commonly, the value of $_SERVER['HTTP_USER_AGENT'] is sufficient.
	 * This function just returns the most popular browser names and OS.
	 *
	 * @param bool $returnFullChain
	 *        	If true, returns $_SERVER['HTTP_USER_AGENT'] as it is, if false (default), returns only browser name and OS.
	 * @return string
	 */
	function getUserAgent(bool $returnFullChain = false): string {
		$userAgent = $_SERVER ['HTTP_USER_AGENT'] ?? '';
		if ($returnFullChain || empty ( $userAgent ))
			return $userAgent;
		$browser = $os = '';
		if (stripos ( $userAgent, 'android' ) !== false) {
			$os = 'Android';
			if (stripos ( $userAgent, 'tablet' ) !== false)
				$os .= ' (tablet)';
			elseif (stripos ( $userAgent, 'mobile' ) !== false)
				$os .= ' (mobile)';
		} elseif (stripos ( $userAgent, 'ipod' ) !== false)
			$os = 'iOS (iPod)';
		elseif (stripos ( $userAgent, 'ipad' ) !== false)
			$os = 'iOS (iPad)';
		elseif (stripos ( $userAgent, 'iphone' ) !== false)
			$os = 'iOS (iPhone)';
		elseif (stripos ( $userAgent, 'linux' ) !== false) {
			$os = 'Linux';
			if (stripos ( $userAgent, 'ubuntu' ) !== false)
				$os .= ' (Ubuntu)';
			elseif (stripos ( $userAgent, 'fedora' ) !== false)
				$os .= ' (Fedora)';
			elseif (stripos ( $userAgent, 'debian' ) !== false)
				$os .= ' (Debian)';
			elseif (stripos ( $userAgent, 'arch' ) !== false)
				$os .= ' (Arch)';
			elseif (stripos ( $userAgent, 'suse' ) !== false)
				$os .= ' (Suse)';
			elseif (stripos ( $userAgent, 'centos' ) !== false)
				$os .= ' (CentOS)';
			elseif (stripos ( $userAgent, 'redhat' ) !== false)
				$os .= ' (Redhat)';
			elseif (stripos ( $userAgent, 'slackware' ) !== false)
				$os .= ' (Slackware)';
			elseif (stripos ( $userAgent, 'gentoo' ) !== false)
				$os .= ' (Gentoo)';
		} elseif (stripos ( $userAgent, 'macintosh' ) !== false || stripos ( $userAgent, 'mac os' ) !== false)
			$os = 'MacOS';
		elseif (strpos ( $userAgent, 'Win' ) !== false)
			$os = 'Windows';
		elseif (stripos ( $userAgent, 'webos' ) !== false)
			$os = 'webOS';
		elseif (stripos ( $userAgent, 'x11' ) !== false)
			$os = 'Unix';

		if (stripos ( $userAgent, 'firefox/' ) !== false)
			$browser = 'Firefox';
		elseif (stripos ( $userAgent, 'seamonkey/' ) !== false)
			$browser = 'Seamonkey';
		elseif (stripos ( $userAgent, 'falkon/' ) !== false)
			$browser = 'Falkon';
		elseif (strpos ( $userAgent, 'Edge' ) !== false || stripos ( $userAgent, 'edg/' ) !== false)
			$browser = 'Microsoft Edge';
		elseif (stripos ( $userAgent, 'MSIE' ) !== false)
			$browser = 'Microsoft Internet Explorer';
		elseif (stripos ( $userAgent, 'opr/' ) !== false || stripos ( $userAgent, 'opera/' ) !== false)
			$browser = 'Opera';
		elseif (stripos ( $userAgent, 'yabrowser' ) !== false || stripos ( $userAgent, 'yowser' ) !== false)
			$browser = 'Yandex';
		elseif (stripos ( $userAgent, 'miui' ) !== false)
			$browser = 'Miui Browser';
		elseif (stripos ( $userAgent, 'chrome/' ) !== false)
			$browser = 'Chrome';
		elseif (stripos ( $userAgent, 'chromium/' ) !== false)
			$browser = 'Chromium';
		elseif (stripos ( $userAgent, 'safari/' ) !== false)
			$browser = 'Safari';

		return trim ( "{$browser} {$os}" );
	}
}