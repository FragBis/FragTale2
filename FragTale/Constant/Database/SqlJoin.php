<?php

namespace FragTale\Constant\Database;

use FragTale\Constant;

/**
 *
 * @author Fabrice Dant <fragtale.development@gmail.com>
 * @copyright 2024 FragTale 2 - Fabrice Dant
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-fr.txt CeCILL Licence 2.1 (French version)
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-en.txt CeCILL Licence 2.1 (English version)
 *         
 */
abstract class SqlJoin extends Constant {
	const INNER = 'INNER JOIN';
	const LEFT = 'LEFT JOIN';
	const RIGHT = 'RIGHT JOIN';
	const LEFT_OUTER = 'LEFT OUTER JOIN';
	const RIGHT_OUTER = 'RIGHT OUTER JOIN';
	const FULL_OUTER = 'FULL OUTER JOIN';
	const NATURAL = 'NATURAL JOIN';
	const CROSS = 'CROSS JOIN';
}