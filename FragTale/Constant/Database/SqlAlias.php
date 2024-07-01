<?php

namespace FragTale\Constant\Database;

/**
 *
 * @author Fabrice Dant <fragtale.development@gmail.com>
 * @copyright 2024 FragTale 2 - Fabrice Dant
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-fr.txt CeCILL Licence 2.1 (French version)
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-en.txt CeCILL Licence 2.1 (English version)
 *         
 */
abstract class SqlAlias {
	/**
	 * Add an alias to a column name or on expression (commonly in SELECT clause): column_name AS 'alias'
	 *
	 * @param string $columnName
	 * @param string $columnAlias
	 *        	(optional)
	 * @return string
	 */
	public static function ADD(string $columnName, ?string $columnAlias = null): string {
		return $columnAlias ? "$columnName AS '$columnAlias'" : $columnName;
	}

	/**
	 * Prepend the table alias to the column name: table_alias.column_name
	 *
	 * @param string $tableAlias
	 * @param string $columnName
	 * @return string
	 */
	public static function PRE(string $tableAlias, string $columnName): string {
		return "$tableAlias.$columnName";
	}

	/**
	 * Prepend table alias and add column alias (commonly in SELECT clause): table_alias.column_name AS 'alias'
	 *
	 * @param string $tableAlias
	 * @param string $columnName
	 * @param string $columnAlias
	 *        	(optional)
	 * @return string
	 */
	public static function PRE_ADD(string $tableAlias, string $columnName, ?string $columnAlias = null): string {
		return self::ADD ( self::PRE ( $tableAlias, $columnName ), $columnAlias );
	}
}