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
abstract class MessageType extends Constant {
	const DEFAULT = 0;
	const INFO = 1;
	const SUCCESS = 2;
	const WARNING = 3;
	const QUESTION = 4;
	const ERROR = 5;
	const FATAL_ERROR = 6;
	const DEBUG = 7;
	const RESTRICTED = 8;
	const FORBIDDEN = 9;
	const HELP = 10;
	const ALLOWED = 11;
	const GRANTED = 12;
	const HAPPY = 13;
	const ANGRY = 14;
	const SAD = 15;
	const FUNNY = 16;
}