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
class Client extends AbstractService {
	function getUserAgent() {
		return isset ( $_SERVER ['HTTP_USER_AGENT'] ) ? $_SERVER ['HTTP_USER_AGENT'] : null;
	}
	function getAccept() {
		return isset ( $_SERVER ['HTTP_ACCEPT'] ) ? $_SERVER ['HTTP_ACCEPT'] : null;
	}
	function getAcceptLanguage() {
		return isset ( $_SERVER ['HTTP_ACCEPT_LANGUAGE'] ) ? $_SERVER ['HTTP_ACCEPT_LANGUAGE'] : null;
	}
	function getAcceptEncoding() {
		return isset ( $_SERVER ['HTTP_ACCEPT_ENCODING'] ) ? $_SERVER ['HTTP_ACCEPT_ENCODING'] : null;
	}
	function getConnection() {
		return isset ( $_SERVER ['HTTP_CONNECTION'] ) ? $_SERVER ['HTTP_CONNECTION'] : null;
	}
}