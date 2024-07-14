<?php

namespace FragTale\Constant\Database;

/**
 * This class does not check if following functions are compatibles with any database system being used, but they should work for most common systems.
 *
 * @author Fabrice Dant <fragtale.development@gmail.com>
 * @copyright 2024 FragTale 2 - Fabrice Dant
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-fr.txt CeCILL Licence 2.1 (French version)
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-en.txt CeCILL Licence 2.1 (English version)
 *         
 */
abstract class SqlFunction {
	/**
	 * Average function
	 *
	 * @param string $columnName
	 * @param string $alias
	 *        	(optional) If you use this function in a SELECT clause, you should define a custom alias. Obviously, don't pass an alias in a WHERE clause.
	 * @return string The SQL expression
	 */
	public static function AVG(string $columnName, ?string $alias = null): string {
		return SqlAlias::ADD ( "AVG($columnName)", $alias );
	}
	/**
	 * COALESCE is equivalent to IFNULL, ISNULL or NVL.
	 * Set a default value when a field value is NULL
	 *
	 * @param string $columnName
	 *        	The column name to test
	 * @param string $default
	 *        	For example: 0 for a numeric column. You don't need to escape single quotes as they are escaped in this function for this parameter.
	 *        	Then, you can just set '' for varchar columns.
	 *        	<b>Attention! You can't pass another field name!</b>
	 * @param string $alias
	 *        	(optional) If you use this function in a SELECT clause, you should define a custom alias. Obviously, don't pass an alias in a WHERE clause.
	 * @return string The SQL expression
	 */
	public static function COALESCE(string $columnName, string $default, ?string $alias = null): string {
		if (! is_numeric ( $default )) {
			$default = str_replace ( "'", "''", $default );
			$default = "'$default'";
		}
		return SqlAlias::ADD ( "COALESCE($columnName, $default)", $alias );
	}
	/**
	 * Concatenate function
	 *
	 * @param array $columns
	 *        	List of string (column names or any expression)
	 * @param string $alias
	 *        	(optional) If you use this function in a SELECT clause, you should define a custom alias. Obviously, don't pass an alias in a WHERE clause.
	 * @return string The SQL expression
	 */
	public static function CONCAT(array $columns, ?string $alias = null): string {
		return ! empty ( $columns ) ? SqlAlias::ADD ( 'CONCAT(' . implode ( ',', $columns ) . ')', $alias ) : '';
	}
	/**
	 * Count function.
	 *
	 * @param string $columnName
	 * @param string $alias
	 *        	(optional) If you use this function in a SELECT clause, you should define a custom alias. Obviously, don't pass an alias in a WHERE clause.
	 * @return string The SQL expression
	 */
	public static function COUNT(string $columnName, ?string $alias = null): string {
		return SqlAlias::ADD ( "COUNT($columnName)", $alias );
	}
	/**
	 * Count(distinct...) function.
	 *
	 * @param string $columnName
	 * @param string $alias
	 *        	(optional) If you use this function in a SELECT clause, you should define a custom alias. Obviously, don't pass an alias in a WHERE clause.
	 * @return string The SQL expression
	 */
	public static function COUNT_DISTINCT(string $columnName, ?string $alias = null): string {
		return SqlAlias::ADD ( "COUNT(DISTINCT $columnName)", $alias );
	}
	/**
	 * Most common alternative for "IF()" function.
	 * <b>Attention! You must include string quotes by yourself.</b>
	 *
	 * @param string $condition
	 *        	Any condition such as "field1 = field2" or "field = 'value'".
	 *        	Mind that you must include single quotes by yourself in that case.
	 * @param string $then
	 *        	A column name or for example: "''" for empty string.
	 *        	Mind that you must include single quotes by yourself.
	 * @param string $else
	 *        	A column name or for example: "''" for empty string.
	 *        	Mind that you must include single quotes by yourself.
	 * @param string $alias
	 *        	(optional) If you use this function in a SELECT clause, you should define a custom alias. Obviously, don't pass an alias in a WHERE clause.
	 * @return string The SQL expression
	 */
	public static function CASE_WHEN(string $condition, string $then, string $else, ?string $alias = null): string {
		return SqlAlias::ADD ( "CASE WHEN $condition THEN $then ELSE $else END", $alias );
	}
	/**
	 * Current date function
	 *
	 * @return string CURDATE() function
	 */
	public static function CURDATE(): string {
		return 'CURDATE()';
	}
	/**
	 * MySQL compatible only
	 *
	 * @param string $columnName
	 * @param string $separator
	 *        	Default separator is ','. You don't need to escape single quotes as they are escaped in this function for this parameter.
	 * @param string $alias
	 *        	(optional) If you use this function in a SELECT clause, you should define a custom alias. Obviously, don't pass an alias in a WHERE clause.
	 * @return string The SQL expression
	 */
	public static function GROUP_CONCAT(string $columnName, ?string $separator = null, ?string $alias = null): string {
		$separator = str_replace ( "'", "''", $separator );
		$sepExpr = $separator ? " SEPARATOR '$separator'" : '';
		return SqlAlias::ADD ( "GROUP_CONCAT({$columnName}{$sepExpr})", $alias );
	}
	/**
	 * MySQL compatible only
	 *
	 * @param string $columnName
	 * @param string $separator
	 *        	Default separator is ','. You don't need to escape single quotes as they are escaped in this function for this parameter.
	 * @param string $alias
	 *        	(optional) If you use this function in a SELECT clause, you should define a custom alias. Obviously, don't pass an alias in a WHERE clause.
	 * @return string The SQL expression
	 */
	public static function GROUP_CONCAT_DISTINCT(string $columnName, ?string $separator = null, ?string $alias = null): string {
		$separator = str_replace ( "'", "''", $separator );
		$sepExpr = $separator ? " SEPARATOR '$separator'" : '';
		return SqlAlias::ADD ( "GROUP_CONCAT(DISTINCT {$columnName}{$sepExpr})", $alias );
	}
	/**
	 * IFNULL is equivalent to COALESCE, ISNULL or NVL.
	 * Set a default value when a field value is NULL
	 *
	 * @param string $columnName
	 *        	The column name to test
	 * @param string $default
	 *        	For example: 0 for a numeric column. You don't need to escape single quotes as they are escaped in this function for this parameter.
	 *        	Then, you can just set '' for varchar columns.
	 *        	<b>Attention! You can't pass another field name!</b>
	 * @param string $alias
	 *        	(optional) If you use this function in a SELECT clause, you should define a custom alias. Obviously, don't pass an alias in a WHERE clause.
	 * @return string The SQL expression
	 */
	public static function IFNULL(string $columnName, string $default, ?string $alias = null): string {
		return self::COALESCE ( $columnName, $default, $alias );
	}
	/**
	 * LPAD function.
	 * For example, adding leading 0 to specified colum name, giving string length where $pad = '0'
	 *
	 * @param string $columnName
	 * @param int $length
	 *        	Specifies the length to which to pad $columName value
	 * @param string $pad
	 *        	Specifies a string of characters to use for padding instead of spaces.
	 * @param string $alias
	 *        	(optional) If you use this function in a SELECT clause, you should define a custom alias. Obviously, don't pass an alias in a WHERE clause.
	 * @return string The SQL expression
	 */
	public static function LPAD(string $columnName, int $length, ?string $pad = ' ', ?string $alias = null): string {
		return SqlAlias::ADD ( "LPAD({$columnName}, {$length}, '{$pad}')", $alias );
	}
	/**
	 * Maximum function
	 *
	 * @param string $columnName
	 * @param string $alias
	 *        	(optional) If you use this function in a SELECT clause, you should define a custom alias. Obviously, don't pass an alias in a WHERE clause.
	 * @return string The SQL expression
	 */
	public static function MAX(string $columnName, ?string $alias = null): string {
		return SqlAlias::ADD ( "MAX($columnName)", $alias );
	}
	/**
	 * Minimum function
	 *
	 * @param string $columnName
	 * @param string $alias
	 *        	(optional) If you use this function in a SELECT clause, you should define a custom alias. Obviously, don't pass an alias in a WHERE clause.
	 * @return string The SQL expression
	 */
	public static function MIN(string $columnName, ?string $alias = null): string {
		return SqlAlias::ADD ( "MIN($columnName)", $alias );
	}
	/**
	 * Current date and time function
	 *
	 * @return string NOW() function
	 */
	public static function NOW(): string {
		return 'NOW()';
	}
	/**
	 * Replace function.
	 * Examples:
	 * 1. REPLACE(mycolumn, 'john', 'jane')
	 * 2. REPLACE('john doe', 'john', 'jane')
	 *
	 * @param string $columnName
	 *        	Mind that you must include single quotes by yourself if you pass a string instead of a column name, you have to add quotes: "'john doe'" or "'john''s does'"
	 * @param string $find
	 *        	The string to search and replace (you don't have to escape single quotes)
	 * @param string $replace
	 *        	The replacement string (you don't have to escape single quotes)
	 * @param string $alias
	 *        	(optional) If you use this function in a SELECT clause, you should define a custom alias. Obviously, don't pass an alias in a WHERE clause.
	 * @return string The SQL expression
	 */
	public static function REPLACE(string $columnName, string $find, string $replace, ?string $alias = null): string {
		$find = str_replace ( "'", "''", $find );
		$replace = str_replace ( "'", "''", $replace );
		return SqlAlias::ADD ( "REPLACE($columnName, '$find', '$replace')", $alias );
	}
	/**
	 * Round function.
	 * Example: ROUND(102.3412, 2) = 102.34
	 *
	 * @param string $columnName
	 *        	Expects an existing column name or a numeric value
	 * @param int $precision
	 *        	(optional) By default 0 (integer)
	 * @param string $alias
	 *        	(optional) If you use this function in a SELECT clause, you should define a custom alias. Obviously, don't pass an alias in a WHERE clause.
	 * @return string The SQL expression
	 */
	public static function ROUND(string $columnName, ?int $precision = 0, ?string $alias = null): string {
		return SqlAlias::ADD ( $precision ? "ROUND($columnName, $precision)" : "ROUND($columnName)", $alias );
	}
	/**
	 * SQL function POINT().
	 * Useful in combination with ST_DISTANCE_SPHERE().
	 *
	 * @param string $longitude
	 *        	Might be a field name or a float value
	 * @param string $latitude
	 *        	Might be a field name or a float value
	 * @return string The SQL expression
	 */
	public static function POINT(string $longitude, string $latitude): string {
		return "POINT($longitude, $latitude)";
	}
	/**
	 * SQL function ST_DISTANCE_SPHERE().
	 * It will calculate the distance in meters between 2 geolocalized POINTS.
	 *
	 * @param string $longitude1
	 *        	Longitude of the 1st point. Might be a field name or a float value
	 * @param string $latitude1
	 *        	Latitude of the 1st point. Might be a field name or a float value
	 * @param string $longitude2
	 *        	Longitude of the 2nd point. Might be a field name or a float value
	 * @param string $latitude2
	 *        	Latitude of the 2nd point. Might be a field name or a float value
	 * @return string The SQL expression
	 */
	public static function ST_DISTANCE_SPHERE(string $longitude1, string $latitude1, string $longitude2, string $latitude2): string {
		return 'ST_DISTANCE_SPHERE(' . self::POINT ( $longitude1, $latitude1 ) . ', ' . self::POINT ( $longitude2, $latitude2 ) . ')';
	}
	/**
	 * <b>Not compatible with SQL Server</b>
	 *
	 * @param string $columnName
	 *        	Expects an existing column name or a string expression.
	 *        	Mind that you must include single quotes by yourself in that case.
	 * @param int $start
	 *        	First position is 1 (and not 0)
	 * @param int $length
	 *        	Set null or 0 to take the rest of the string
	 * @param string $alias
	 *        	(optional) If you use this function in a SELECT clause, you should define a custom alias. Obviously, don't pass an alias in a WHERE clause.
	 * @return string The SQL expression
	 */
	public static function SUBSTR(string $columnName, int $start, ?int $length = null, ?string $alias = null): string {
		return SqlAlias::ADD ( $length ? "SUBSTR($columnName, $start, $length)" : "SUBSTR($columnName, $start)", $alias );
	}
	/**
	 * Sum function.
	 *
	 * @param string $columnName
	 * @param string $alias
	 *        	(optional) If you use this function in a SELECT clause, you should define a custom alias. Obviously, don't pass an alias in a WHERE clause.
	 * @return string The SQL expression
	 */
	public static function SUM(string $columnName, ?string $alias = null): string {
		return SqlAlias::ADD ( "SUM($columnName)", $alias );
	}
	/**
	 * Sum(distinct...) function.
	 *
	 * @param string $columnName
	 * @param string $alias
	 *        	(optional) If you use this function in a SELECT clause, you should define a custom alias. Obviously, don't pass an alias in a WHERE clause.
	 * @return string The SQL expression
	 */
	public static function SUM_DISTINCT(string $columnName, ?string $alias = null): string {
		return SqlAlias::ADD ( "SUM(DISTINCT $columnName)", $alias );
	}
	/**
	 * Trim both leading and trailing chars.
	 *
	 * @param string $from
	 *        	Expects an existing column name or a string expression.
	 *        	Mind that if you pass a string to trim, you must add single quotes yourself.
	 * @param string $chars
	 *        	(optional) By default, it is space (' '). You don't need to escape single quotes as they are escaped in this function for this parameter.
	 * @param string $alias
	 *        	(optional) If you use this function in a SELECT clause, you should define a custom alias. Obviously, don't pass an alias in a WHERE clause.
	 * @return string The SQL expression
	 */
	public static function TRIM_BOTH(string $from, string $chars = ' ', ?string $alias = null): string {
		$chars = str_replace ( "'", "''", $chars );
		return SqlAlias::ADD ( "TRIM(BOTH '$chars' FROM $from)", $alias );
	}
	/**
	 * Trim only leading chars.
	 *
	 * @param string $from
	 *        	Expects an existing column name or a string expression.
	 *        	Mind that if you pass a string to trim, you must add single quotes yourself.
	 * @param string $chars
	 *        	(optional) By default, it is space (' '). You don't need to escape single quotes as they are escaped in this function for this parameter.
	 * @param string $alias
	 *        	(optional) If you use this function in a SELECT clause, you should define a custom alias. Obviously, don't pass an alias in a WHERE clause.
	 * @return string The SQL expression
	 */
	public static function TRIM_LEADING(string $from, string $chars = ' ', ?string $alias = null): string {
		$chars = str_replace ( "'", "''", $chars );
		return SqlAlias::ADD ( "TRIM(LEADING '$chars' FROM $from)", $alias );
	}
	/**
	 * Trim only trailing chars.
	 *
	 * @param string $from
	 *        	It can be a column name or a string. Mind that if you pass a string to trim, you must add single quotes yourself.
	 * @param string $chars
	 *        	(optional) By default, it is space (' '). You don't need to escape single quotes as they are escaped in this function for this parameter.
	 * @param string $alias
	 *        	(optional) If you use this function in a SELECT clause, you should define a custom alias. Obviously, don't pass an alias in a WHERE clause.
	 * @return string The SQL expression
	 */
	public static function TRIM_TRAILING(string $from, string $chars = ' ', ?string $alias = null): string {
		$chars = str_replace ( "'", "''", $chars );
		return SqlAlias::ADD ( "TRIM(TRAILING '$chars' FROM $from)", $alias );
	}
}