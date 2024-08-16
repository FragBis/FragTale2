<?php

namespace FragTale\Constant\Database;

/**
 * Several functions that encapsalute fields or string values (between parenthesis or single quotes)
 *
 * @author Fabrice Dant <fragtale.development@gmail.com>
 * @copyright 2024 FragTale 2 - Fabrice Dant
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-fr.txt CeCILL Licence 2.1 (French version)
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-en.txt CeCILL Licence 2.1 (English version)
 *         
 */
abstract class SqlEncaps {
	/**
	 * Pass a string to encapsulate between single quotes.
	 * Original single quotes are escaped (doubled).
	 *
	 * @param string $string
	 *        	The string to be encapsulated
	 * @return string Encapsulated escaped string in single quotes for SQL queries
	 */
	public static function IN_QUOTES(string $string): string {
		return "'" . str_replace ( "'", "''", $string ) . "'";
	}
	/**
	 * Pass a list of SQL statements to encapsulate in parenthesis.
	 *
	 * @param array $statements
	 *        	Several SQL statements to encapsulate in parenthesis
	 * @param string $sqlOperatorOrSeparator
	 *        	SQL statements are joined with the given operator or separator. Default is "AND".
	 * @return string
	 */
	public static function IN_PARENTHESES(array $statements, string $sqlOperatorOrSeparator = SqlOperator::AND): string {
		$trimmedSep = trim ( $sqlOperatorOrSeparator );
		if (in_array ( $trimmedSep, [ 
				'',
				','
		] ))
			$sqlOperatorOrSeparator = "{$trimmedSep} ";
		elseif (stripos ( $sqlOperatorOrSeparator, '"' ) === false && stripos ( $sqlOperatorOrSeparator, "'" ) === false)
			$sqlOperatorOrSeparator = " {$trimmedSep} ";
		return '(' . implode ( $sqlOperatorOrSeparator, $statements ) . ')';
	}
}