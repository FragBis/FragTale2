<?php

namespace FragTale\Constant;

use FragTale\Constant;

/**
 *
 * @author Fabrice Dant <fragtale.development@gmail.com>
 * @copyright 2024 FragTale 2 - Fabrice Dant
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-fr.txt CeCILL Licence 2.1 (French version)
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-en.txt CeCILL Licence 2.1 (English version)
 *         
 */
abstract class TemplateFormat extends Constant {
	const HTML = 1;
	const HTML_NO_LAYOUT = 2;
	const JSON = 3;
	const PLAIN_TEXT = 4;
	const XML = 5;
	const MEDIA = 6;
	const HTML_DEBUG = 7;
}