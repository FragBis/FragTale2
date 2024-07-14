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
	/**
	 * Concatenate 2 statements joined by "="
	 *
	 * @param string $statement1
	 *        	1st statement before equal sign
	 * @param string $statement
	 *        	2nd statement after equal sign
	 * @return string
	 */
	public static function EQ(string $statement1, string $statement2): string {
		return $statement1 . SqlOperator::EQ . $statement2;
	}
	/**
	 * Concatenate 2 statements joined by "LIKE"
	 *
	 * @param string $statement1
	 *        	1st statement before LIKE
	 * @param string $statement
	 *        	2nd statement after LIKE
	 * @return string
	 */
	public static function LIKE(string $statement1, string $statement2): string {
		return $statement1 . SqlOperator::LIKE . $statement2;
	}
	/**
	 * Concatenate 2 statements joined by "<>"
	 *
	 * @param string $statement1
	 *        	1st statement before diff sign
	 * @param string $statement
	 *        	2nd statement after diff sign
	 * @return string
	 */
	public static function DIFF(string $statement1, string $statement2): string {
		return $statement1 . SqlOperator::DIFFERENT . $statement2;
	}
	/**
	 * Concatenate 2 statements joined by "<"
	 *
	 * @param string $statement1
	 *        	1st statement before "<"
	 * @param string $statement
	 *        	2nd statement after "<"
	 * @return string
	 */
	public static function LT(string $statement1, string $statement2): string {
		return $statement1 . SqlOperator::LT . $statement2;
	}
	/**
	 * Concatenate 2 statements joined by "<="
	 *
	 * @param string $statement1
	 *        	1st statement before "<="
	 * @param string $statement
	 *        	2nd statement after "<="
	 * @return string
	 */
	public static function LTE(string $statement1, string $statement2): string {
		return $statement1 . SqlOperator::LTE . $statement2;
	}
	/**
	 * Concatenate 2 statements joined by ">"
	 *
	 * @param string $statement1
	 *        	1st statement before ">"
	 * @param string $statement
	 *        	2nd statement after ">"
	 * @return string
	 */
	public static function GT(string $statement1, string $statement2): string {
		return $statement1 . SqlOperator::GT . $statement2;
	}
	/**
	 * Concatenate 2 statements joined by ">="
	 *
	 * @param string $statement1
	 *        	1st statement before ">="
	 * @param string $statement
	 *        	2nd statement after ">="
	 * @return string
	 */
	public static function GTE(string $statement1, string $statement2): string {
		return $statement1 . SqlOperator::GTE . $statement2;
	}
	/**
	 * Returns SQL statement followed by IS NULL.
	 * You will use this function declaring "(INNER|LEFT|RIGHT) JOIN... ON" clause.
	 * <b>Attention:</b> it is NOT the SQL "isnull()" function (that is "SqlFunction::IFNULL()").
	 * Defining filters in "where" clause, you should use "SqlOperator::IS" instead
	 *
	 * @param string $statement
	 *        	Any SQL statement that will be tested
	 * @return string
	 */
	public static function IS_NULL(string $statement): string {
		return "$statement IS NULL";
	}
	/**
	 * Returns SQL statement followed by IS NOT NULL
	 * You will use this function declaring "(INNER|LEFT|RIGHT) JOIN... ON" clause.
	 * Attention: defining filters in "where" clause, you should use "SqlOperator::IS_NOT" instead
	 *
	 * @param string $statement
	 *        	Any SQL statement that will be tested
	 * @return string
	 */
	public static function IS_NOT_NULL(string $statement): string {
		return "$statement IS NOT NULL";
	}
}