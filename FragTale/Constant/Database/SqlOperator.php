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
abstract class SqlOperator extends Constant {
	/**
	 * Expects array
	 *
	 * @var string
	 */
	const AND = 'AND';
	/**
	 * Expects array
	 *
	 * @var string
	 */
	const OR = 'OR';
	/**
	 * Expects numeric
	 *
	 * @var string
	 */
	const PLUS = '+';
	/**
	 * Expects numeric
	 *
	 * @var string
	 */
	const MINUS = '-';
	/**
	 * Expects numeric
	 *
	 * @var string
	 */
	const MULTIPLY = '*';
	/**
	 * Expects numeric
	 *
	 * @var string
	 */
	const DIVIDE = '/';
	/**
	 * Expects numeric
	 *
	 * @var string
	 */
	const MODULO = '%';
	/**
	 * Expects string.
	 * Prepared statement.
	 *
	 * @var string
	 */
	const LIKE = 'LIKE';
	/**
	 * Expects string.
	 * Prepared statement.
	 *
	 * @var string
	 */
	const NOT_LIKE = 'NOT LIKE';
	/**
	 * Expects string | numeric.
	 * Prepared statement.
	 *
	 * @var string
	 */
	const EQ = '=';
	/**
	 * Expects string | numeric.
	 * Prepared statement.
	 *
	 * @var string
	 */
	const GT = '>';
	/**
	 * Expects string | numeric.
	 * Prepared statement.
	 *
	 * @var string
	 */
	const GTE = '>=';
	/**
	 * Expects string | numeric.
	 * Prepared statement.
	 *
	 * @var string
	 */
	const LT = '<';
	/**
	 * Expects string | numeric.
	 * Prepared statement.
	 *
	 * @var string
	 */
	const LTE = '<=';
	/**
	 * Expects string | numeric.
	 * Prepared statement.
	 *
	 * @var string
	 */
	const DIFFERENT = '<>';
	/**
	 * Expects an array containing exactly 2 values (string | numeric).
	 * Prepared statement.
	 *
	 * @var string
	 */
	const BETWEEN = 'BETWEEN';
	/**
	 * Expects an array containing exactly 2 values (string | numeric).
	 * Suffix <i>_LITT</i> means "LITTERALLY". It specifies the query builder to pass values as it is, with no quotes.
	 *
	 * @var string
	 */
	const BETWEEN_LITT = 'BETWEEN LITTERALLY';
	/**
	 * Expects an array containing exactly 2 values (string | numeric).
	 * Prepared statement.
	 *
	 * @var string
	 */
	const NOT_BETWEEN = 'NOT BETWEEN';
	/**
	 * Expects an array containing exactly 2 values (string | numeric).
	 * Suffix <i>_LITT</i> means "LITTERALLY". It specifies the query builder to pass values as it is, with no quotes.
	 *
	 * @var string
	 */
	const NOT_BETWEEN_LITT = 'NOT BETWEEN LITTERALLY';
	/**
	 * Expects an array (string | numeric).
	 * Prepared statement.
	 *
	 * @var string
	 */
	const IN = 'IN';
	/**
	 * Expects an array (string | numeric).
	 * Prepared statement.
	 *
	 * @var string
	 */
	const NOT_IN = 'NOT IN';
	/**
	 * This SqlOperator comes with <i>NULL</i>
	 *
	 * @var string
	 */
	const IS = 'IS';
	/**
	 * This SqlOperator comes with <i>NULL</i>
	 *
	 * @var string
	 */
	const IS_NOT = 'IS NOT';
	/**
	 * Symbol = (equal).
	 * Handling special case for boolean (parsed litterally as bit or integer [0,1].
	 * <b>NOTE: it will parse [ NULL, 0, FALSE ] to 0</b> in where conditions.<br>
	 * Use <i>SqlOperator::IS</i> to match <i>NULL</i> value
	 *
	 * @var string
	 */
	const EQ_BOOL = '=INT_AS_BOOL';
	/**
	 * Symbol = (equal).
	 * Specify the query builder not to prepare values and pass value litterally (except int, float and string types).
	 * It will <b>automatically add quotes to string values</b> in where conditions.
	 *
	 * @var string
	 */
	const EQ_LITT = '=LITTERALLY';
	/**
	 * Symbol = (equal).
	 * Specify the query builder not to prepare values and pass value litterally with no quotes, that is needed to set comparison between 2 fields.
	 *
	 * @var string
	 */
	const EQ_FIELD = '=FIELD';
	/**
	 * Symbol > (strict).
	 * Specify the query builder not to prepare values and pass value litterally (except int, float and string types).
	 * It will <b>automatically add quotes to string values</b> in where conditions.
	 *
	 * @var string
	 */
	const GT_LITT = '>LITTERALLY';
	/**
	 * Symbol > (strict).
	 * Specify the query builder not to prepare values and pass value litterally with no quotes, that is needed to set comparison between 2 fields.
	 *
	 * @var string
	 */
	const GT_FIELD = '>FIELD';
	/**
	 * Symbol >= (greater or equal).
	 * Specify the query builder not to prepare values and pass value litterally (except int, float and string types).
	 * It will <b>automatically add quotes to string values</b> in where conditions.
	 *
	 * @var string
	 */
	const GTE_LITT = '>=LITTERALLY';
	/**
	 * Symbol >= (greater or equal).
	 * Specify the query builder not to prepare values and pass value litterally with no quotes, that is needed to set comparison between 2 fields.
	 *
	 * @var string
	 */
	const GTE_FIELD = '>=FIELD';
	/**
	 * Symbol < (strict).
	 * Specify the query builder not to prepare values and pass value litterally (except int, float and string types).
	 * It will <b>automatically add quotes to string values</b> in where conditions.
	 *
	 * @var string
	 */
	const LT_LITT = '<LITTERALLY';
	/**
	 * Symbol < (strict).
	 * Specify the query builder not to prepare values and pass value litterally with no quotes, that is needed to set comparison between 2 fields.
	 *
	 * @var string
	 */
	const LT_FIELD = '<FIELD';
	/**
	 * Symbol <= (lower or equal).
	 * Specify the query builder not to prepare values and pass value litterally (except int, float and string types).
	 * It will <b>automatically add quotes to string values</b> in where conditions.
	 *
	 * @var string
	 */
	const LTE_LITT = '<=LITTERALLY';
	/**
	 * Symbol <= (lower or equal).
	 * Specify the query builder not to prepare values and pass value litterally with no quotes, that is needed to set comparison between 2 fields.
	 *
	 * @var string
	 */
	const LTE_FIELD = '<=FIELD';
	/**
	 * Symbol <> (different).
	 * Handling special case for boolean (parsed litterally as bit or integer [0,1].
	 * <b>NOTE: it will parse [ NULL, 0, FALSE ] to 0</b> in where conditions.<br>
	 * Use <i>SqlOperator::IS</i> to match <i>NULL</i> value
	 *
	 * @var string
	 */
	const DIFFERENT_BOOL = '<>INT_AS_BOOL';
	/**
	 * Symbol <> (different).
	 * Specify the query builder not to prepare values and pass value litterally (except int, float and string types).
	 * It will <b>automatically add quotes to string values</b> in where conditions.
	 *
	 * @var string
	 */
	const DIFFERENT_LITT = '<>LITTERALLY';
	/**
	 * Symbol <> (different).
	 * Specify the query builder not to prepare values and pass value litterally with no quotes, that is needed to set comparison between 2 fields.
	 *
	 * @var string
	 */
	const DIFFERENT_FIELD = '<>FIELD';
	/**
	 * Expects a query builder, instance of QueryBuildSelect.
	 *
	 * @var string
	 */
	const IN_SELECT = 'IN SELECT';
}