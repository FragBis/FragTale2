<?php

namespace FragTale\Implement\QueryBuilder;

use FragTale\Constant\Database\SqlOperator;
use FragTale\DataCollection;
use FragTale\Database\QueryBuilder\QueryBuildSelect;

/**
 *
 * @author Fabrice Dant <fragtale.development@gmail.com>
 * @copyright 2024 FragTale 2 - Fabrice Dant
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-fr.txt CeCILL Licence 2.1 (French version)
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-en.txt CeCILL Licence 2.1 (English version)
 *         
 */
trait FilterTrait {
	protected ?array $filters = null;
	protected array $preparedValues;
	protected string $queryConditions = '';

	/**
	 * Get all conditions set in where function.
	 *
	 * @return DataCollection
	 */
	public function getFilters(): ?array {
		return $this->filters;
	}

	/**
	 * You must set all filters in a row, into an array.
	 * You cannot add ulterior filters one by one.
	 *
	 * @param array|null $filters
	 *        	Example 1: [ T_::field => "value" ]
	 *        	means that the field of table is equal to "value"
	 *        	Example 2: [ T_::field => [ "or" => [ "=" => "value" ] ] ]
	 *        	To remove filters, set null
	 * @return self
	 */
	public function where(?iterable $filters): self {
		$this->filters = [ ]; // Replacing
		if ($filters)
			foreach ( $filters as $key => $filter )
				$this->filters [$key] = $filter;
		else
			$this->filters = null;
		return $this;
	}

	/**
	 * This will append new main condition(s) in WHERE clause.
	 * Use function "getFilters" to check current filters, preventing overwriting previous conditions.
	 * You might want to use "where" function instead, because this function will append the new condition with "AND" operator.
	 *
	 * @param array $condition
	 *        	Example 1: [ T_::field => "value" ]
	 *        	Example 2, with "OR" operator: [ SqlOperator::OR => [T_::field => "value", T_::field2 => "value2" ] ]
	 * @return self
	 */
	public function addWhere(iterable $filters): self {
		if (! is_array ( $this->filters ))
			$this->filters = [ ];
		foreach ( $filters as $key => $filter )
			$this->filters [$key] = $filter;
		return $this;
	}

	/**
	 *
	 * @return self
	 */
	final protected function setConditions(): self {
		$globalConds = $this->buildFiltersArray ( $this->filters );
		$this->queryConditions = ! empty ( $globalConds ) ? implode ( "\n" . SqlOperator::AND . ' ', $globalConds ) : '';
		return $this;
	}

	/**
	 * Returns the top conditions that are by default imploded with "AND" operator
	 *
	 * @param array $filters
	 * @return array|null
	 */
	final protected function buildFiltersArray(?iterable $filters): ?array {
		if (empty ( $filters ))
			return null;
		$globalConds = [ ];
		foreach ( $filters as $key => $conditions ) {
			$operator = is_int ( $key ) ? SqlOperator::AND : null;
			if ($operator || ($operator = SqlOperator::getConstant ( $key ))) {
				if ($subConditions = is_iterable ( $conditions ) ? $this->buildFiltersArray ( $conditions ) : trim ( ( string ) $conditions ))
					$globalConds [] = is_iterable ( $subConditions ) ? '(' . implode ( " $operator ", $subConditions ) . ')' : "($subConditions)";
			} else {
				if (is_iterable ( $conditions )) {
					$resConditions = [ ];
					foreach ( $conditions as $operator => $subConditions ) {
						$strConditions = ( string ) $this->buildFilterStringOnOperator ( strtoupper ( $operator ), $key, $subConditions );
						if (substr ( $strConditions, 0, 1 ) === '(')
							$resConditions [] = $strConditions;
						else
							$resConditions [] = "$key $strConditions";
					}
					$globalConds [] = '(' . implode ( ' ' . SqlOperator::AND . ' ', $resConditions ) . ')';
				} else {
					$operator = SqlOperator::EQ;
					$rand = substr ( md5 ( rand () . microtime () ), 0, 8 );
					$modKey = preg_replace ( "/[^A-Za-z0-9]/", '', substr ( $key, 0, 10 ) ) . '_';
					$mark = ":$modKey$rand";
					$this->preparedValues [$mark] = $conditions;
					$globalConds [] = "$key $operator $mark";
				}
			}
		}
		return $globalConds;
	}

	/**
	 * Convert all conditions into SQL string
	 *
	 * @param string $operator
	 * @param string $key
	 * @param mixed $conditions
	 * @throws \Exception
	 * @return string|NULL
	 */
	private function buildFilterStringOnOperator(string $operator, string $key, $conditions): ?string {
		switch ($operator) {
			case SqlOperator::AND :
			case SqlOperator::OR :
				if (! is_iterable ( $conditions ))
					throw new \Exception ( sprintf ( dgettext ( 'core', 'Operator "%s" expects its value to be an array of other filters.' ), $operator ) );
				$resConditions = [ ];
				foreach ( $conditions as $subOperator => $subConds ) {
					if (in_array ( $subOperator, SqlOperator::getConstants ()->getData ( true ) ))
						$strConditions = ( string ) $this->buildFilterStringOnOperator ( $subOperator, $key, $subConds );
					else
						$strConditions = ( string ) $this->buildFilterStringOnOperator ( $operator, $key, $subConds );

					if (substr ( $strConditions, 0, 1 ) === '(')
						$resConditions [] = $strConditions;
					else
						$resConditions [] = "$key $strConditions";
				}
				return '(' . implode ( " $operator ", $resConditions ) . ')';
			case SqlOperator::IS :
			case SqlOperator::IS_NOT :
				if ($conditions === null)
					$conditions = 'NULL';
				if (! is_string ( $conditions ) || ! in_array ( trim ( strtoupper ( ( string ) $conditions ) ), [ 
						'NULL',
						'NOT NULL'
				] ))
					throw new \Exception ( sprintf ( dgettext ( 'core', 'Operator "%s" expects its value to be litterally "NULL" or "NOT NULL" (and not else).' ), $operator ) );
				return "$operator $conditions";
			case SqlOperator::LIKE :
			case SqlOperator::NOT_LIKE :
				if (! is_string ( $conditions ))
					throw new \Exception ( sprintf ( dgettext ( 'core', 'Operator "%s" expects its value to be a string.' ), $operator ) );
				$conditions = str_replace ( "'", "''", $conditions );
				return "$operator '$conditions'";
			case SqlOperator::EQ_BOOL :
			case SqlOperator::DIFFERENT_BOOL :
				if (is_iterable ( $conditions ))
					throw new \Exception ( sprintf ( dgettext ( 'core', 'Operator "%s" expects its value to be a boolean (e.g.: 0, 1, true, false).' ), $operator ) );
				$conditions = ( int ) (( bool ) $conditions);
				return ($operator === SqlOperator::EQ_BOOL ? SqlOperator::EQ : SqlOperator::DIFFERENT) . " $conditions";
			case SqlOperator::IN :
			case SqlOperator::NOT_IN :
				if (! is_array ( $conditions ) || empty ( $conditions ))
					throw new \Exception ( sprintf ( dgettext ( 'core', 'Operator "%s" expects its value to be a list (array).' ), $operator ) );
				$markedConds = [ ];
				foreach ( $conditions as $value ) {
					if (! is_iterable ( $value ) && ! is_object ( $value )) {
						$rand = substr ( md5 ( rand () . microtime () ), 0, 8 );
						$modKey = preg_replace ( "/[^A-Za-z0-9]/", '', substr ( $key, 0, 10 ) ) . '_';
						$mark = ":$modKey$rand";
						$this->preparedValues [$mark] = $value;
						$markedConds [] = $mark;
					} else
						throw new \Exception ( sprintf ( dgettext ( 'core', 'Operator "%s" expects its value to be a single list (1 dimension array).' ), $operator ) );
				}
				$markedConds = implode ( ', ', $markedConds );
				return "$operator ($markedConds)";
			case SqlOperator::BETWEEN :
			case SqlOperator::NOT_BETWEEN :
				if (! is_array ( $conditions ) || count ( $conditions ) !== 2)
					throw new \Exception ( sprintf ( dgettext ( 'core', 'Operator "%s" expects its value to be an array of 2 values exactly.' ), $operator ) );
				$values = [ ];
				foreach ( $conditions as $value ) {
					$rand = substr ( md5 ( rand () . microtime () ), 0, 8 );
					$modKey = preg_replace ( "/[^A-Za-z0-9]/", '', substr ( $key, 0, 10 ) ) . '_';
					$mark = ":$modKey$rand";
					$this->preparedValues [$mark] = $value;
					$values [] = $mark;
				}
				$val1 = reset ( $values );
				$val2 = end ( $values );
				return "$operator $val1 AND $val2";
			case SqlOperator::BETWEEN_LITT :
			case SqlOperator::NOT_BETWEEN_LITT :
				if (! is_array ( $conditions ) || count ( $conditions ) !== 2)
					throw new \Exception ( sprintf ( dgettext ( 'core', 'Operator "%s" expects its value to be an array of 2 values exactly.' ), $operator ) );
				$val1 = reset ( $conditions );
				$val2 = end ( $conditions );
				$operator = trim ( str_ireplace ( 'LITTERALLY', '', $operator ) );
				return "$operator $val1 AND $val2";
			case SqlOperator::EQ_LITT :
			case SqlOperator::DIFFERENT_LITT :
			case SqlOperator::LT_LITT :
			case SqlOperator::LTE_LITT :
			case SqlOperator::GT_LITT :
			case SqlOperator::GTE_LITT :
				if (is_iterable ( $conditions ) || is_object ( $conditions ))
					throw new \Exception ( sprintf ( dgettext ( 'core', 'Operator "%s" expects its value to be a single value, not an array nor an object.' ), $operator ) );
				if (is_bool ( $conditions ))
					$conditions = ( int ) $conditions ? 1 : 0;
				$operator = str_ireplace ( 'LITTERALLY', '', $operator );
				if (is_int ( $conditions ) || is_float ( $conditions ))
					return "$operator $conditions";
				else {
					$conditions = trim ( trim ( ( string ) $conditions, "'" ) );
					return "$operator '$conditions'";
				}
			case SqlOperator::EQ_FIELD :
			case SqlOperator::DIFFERENT_FIELD :
			case SqlOperator::LT_FIELD :
			case SqlOperator::LTE_FIELD :
			case SqlOperator::GT_FIELD :
			case SqlOperator::GTE_FIELD :
				if (is_iterable ( $conditions ) || is_object ( $conditions ))
					throw new \Exception ( sprintf ( dgettext ( 'core', 'Operator "%s" expects its value to be a string, not an array nor an object.' ), $operator ) );
				$operator = str_ireplace ( 'FIELD', '', $operator );
				return "$operator $conditions";
			default :
				if (is_iterable ( $conditions ) || is_object ( $conditions ))
					throw new \Exception ( sprintf ( dgettext ( 'core', 'Operator "%s" expects its value to be a single value, not an array nor an object.' ), $operator ) );
				$rand = substr ( md5 ( rand () . microtime () ), 0, 8 );
				$modKey = preg_replace ( "/[^A-Za-z0-9]/", '', substr ( $key, 0, 10 ) ) . '_';
				$mark = ":$modKey$rand";
				$this->preparedValues [$mark] = $conditions;
				return "$operator $mark";
			case SqlOperator::IN_SELECT :
				if (! $conditions instanceof QueryBuildSelect)
					throw new \Exception ( sprintf ( dgettext ( 'core', 'Operator IN_SELECT expects a query builder, instance of QueryBuildSelect.' ) ) );
				$queryString = $conditions->build ()->getQueryString ();
				if ($preparedValues = $conditions->getPreparedValues ())
					$this->preparedValues = array_merge ( $this->preparedValues, $preparedValues );
				return "IN ($queryString)";
		}
	}
}