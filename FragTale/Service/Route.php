<?php

namespace FragTale\Service;

use FragTale\Implement\AbstractService;

/**
 *
 * @author Fabrice Dant <fragtale.development@gmail.com>
 * @copyright 2024 FragTale 2 - Fabrice Dant
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-fr.txt CeCILL Licence 2.1 (French version)
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-en.txt CeCILL Licence 2.1 (English version)
 *         
 */
class Route extends AbstractService {

	/**
	 * Parse namespace to corresponding URI (most of time, for routing purposes)
	 *
	 * @param string $namespace
	 * @return string
	 */
	public function convertNamespaceToUri(?string $namespace): ?string {
		if (! $namespace)
			return '';
		$namespace = str_replace ( '/', '\\', $namespace );
		$uri = [ ];
		foreach ( explode ( '\\', $namespace ) as $exp ) {
			if (empty ( $exp ))
				continue;
			$split = preg_split ( '/(?=[A-Z])/', $exp, - 1, PREG_SPLIT_NO_EMPTY );
			if (! empty ( $split ))
				$uri [] = strtolower ( str_replace ( ' ', '_', ucwords ( implode ( ' ', $split ) ) ) );
		}
		return trim ( implode ( '/', $uri ), '/' );
	}

	/**
	 * Parse URI to corresponding namespace (most of time, for routing purposes)
	 *
	 * @param string $uri
	 * @return string|NULL Returning null, the uri to parse is invalid.
	 */
	public function convertUriToNamespace(?string $uri): ?string {
		if (! $uri)
			return '';
		$uri = str_replace ( [ 
				'http:',
				'https:',
				'HTTP:',
				'HTTPS:'
		], '', $uri );
		while ( strpos ( $uri, '//' ) !== false )
			$uri = str_replace ( '//', '/', $uri );
		$namespace = trim ( trim ( trim ( $uri ), '/' ), '\\' );
		$namespace = str_replace ( [ 
				'_',
				'-'
		], ' ', $namespace );
		if (strpos ( $namespace, ' ' ))
			$namespace = ucwords ( $namespace );
		$namespace = str_replace ( [ 
				' ',
				'/'
		], [ 
				'',
				'\\'
		], $namespace );
		$namespace = ucwords ( $namespace, '\\' );
		foreach ( explode ( '\\', $namespace ) as $subspace ) {
			// A namespace can't contain a PHP keywords. A class can't equal a PHP keyword. It must be alphanum but not begins by a number.
			if ($this->getSuperServices ()->getConfigurationService ()->isPhpKeyword ( $subspace ) || ! preg_match ( '/^[A-Za-z0-9]+$/', $subspace ) || is_numeric ( substr ( ( string ) $subspace, 0, 1 ) ))
				return null;
		}
		return trim ( $namespace, '\\' );
	}

	/**
	 * Project base url include HTTP protocole and domain.
	 * It might contain alias.
	 *
	 * @return string|NULL
	 */
	public function getBaseUrl(): ?string {
		return $this->getSuperServices ()->getHttpServerService ()->getBaseUrl ();
	}

	/**
	 * Build URI given by a web controller.
	 * You can also pass URL parameters into an array
	 *
	 * @param string $controllerClass
	 *        	Must include full namespace
	 * @param array $params
	 *        	URL parameters
	 * @return string|NULL
	 */
	public function getControllerUri(string $controllerClass, ?array $params = null): ?string {
		if (strpos ( $controllerClass, '?' )) {
			$exp = explode ( '?', $controllerClass );
			$controllerClass = $exp [0];
			if (! empty ( $exp [1] ) && ($subexp = explode ( '&', ( string ) $exp [1] ))) {
				if (! $params)
					$params = [ ];
				foreach ( $subexp as $param ) {
					if (! $param)
						continue;
					if (strpos ( $param, '=' )) {
						$expParam = explode ( '=', $param );
						$key = $expParam [0];
						$value = isset ( $expParam [1] ) ? $expParam [1] : '';
					} else {
						$key = $param;
						$value = '';
					}
					if (! array_key_exists ( $key, $params ))
						$params [$key] = $value;
				}
			}
		}
		$controllerClass = trim ( str_replace ( $this->getSuperServices ()->getProjectService ()->getBaseWebControllerNamespace () . '\\', '', $controllerClass ), '\\' );
		$uri = trim ( ( string ) $this->convertNamespaceToUri ( $controllerClass ), '/' );
		if (! empty ( $params ))
			$uri .= '?' . http_build_query ( $params );
		return $uri;
	}

	/**
	 * Build full URL given by a web controller that include HTTP protocole and domain.
	 * You can also pass URL parameters into an array
	 *
	 * @param string $controllerClass
	 *        	Must include full namespace
	 * @param array $params
	 *        	URL parameters
	 * @return string|NULL
	 */
	public function getControllerUrl(?string $controllerClass = null, ?array $params = null): ?string {
		$uri = $this->getControllerUri ( $controllerClass, $params );
		$uri = ($uri === 'home') ? '' : "/$uri";
		return $this->getBaseUrl () . $uri;
	}
}