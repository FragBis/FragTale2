<?php

namespace FragTale\Implement\QueryBuilder;

use FragTale\Application\Model;
use FragTale\Constant\Database\SqlJoin;
use FragTale\Database\QueryBuilder\QueryBuildSelect;
use FragTale\Constant\Database\SqlOperator;

/**
 *
 * @author Fabrice Dant <fragtale.development@gmail.com>
 * @copyright 2024 FragTale 2 - Fabrice Dant
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-fr.txt CeCILL Licence 2.1 (French version)
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-en.txt CeCILL Licence 2.1 (English version)
 *         
 */
trait JoinTrait {
	use FilterTrait;
	protected string $queryJoins = '';
	protected ?array $joins = null;

	/**
	 *
	 * @return array
	 */
	public function getJoins(): ?array {
		return $this->joins;
	}

	/**
	 * Here, $onClause is nullable.
	 * Some join types, such as NATURAL and CROSS does not require on clause.
	 *
	 * @param string $joinType
	 *        	Allowed "join types" are defined in SqlJoin class: SqlJoin::LEFT, SqlJoin::RIGHT, SqlJoin::INNER etc.
	 * @param Model $Entity
	 *        	Here, you join entity with another entity. You do not pass the explicit SQL table name. Example: new E_Table2()
	 * @param string $alias
	 *        	You must set a distinguishable table alias related to the entity passed. Example: 'table_alias2'
	 * @param string|iterable|null $onClause
	 *        	You must write your conditions. Most of the time, it follows this form: "table_alias1.id = table_alias2.foreign_key_id"
	 * @throws \Exception
	 * @return self
	 */
	public function join(string $joinType, Model $Entity, string $alias, $onClause): self {
		$joinType = trim ( strtoupper ( $joinType ) );
		if (! in_array ( $joinType, SqlJoin::getConstants ()->getData ( true ) ))
			throw new \Exception ( sprintf ( dgettext ( 'core', 'Unhandled join type "%s"' ) ), $joinType );
		$this->joins [$alias] = [ 
				'entity' => $Entity,
				'type' => $joinType,
				'on' => $onClause
		];
		return $this;
	}

	/**
	 *
	 * @param string $joinType
	 *        	Allowed "join types" are defined in SqlJoin class: SqlJoin::LEFT, SqlJoin::RIGHT, SqlJoin::INNER etc.
	 * @param Model $QuerySelect
	 *        	Here, you join a query builder, returning a SELECT full SQL statement. It will build the query placing a sub query to JOIN.
	 * @param string $alias
	 *        	You must set a distinguishable table alias related to the entity passed. Example: 'view_alias2'
	 * @param string|iterable|null $onClause
	 *        	You must write your conditions. Most of the time, it follows this form: "table_alias1.id = table_alias2.foreign_key_id"
	 * @throws \Exception
	 * @return self
	 */
	public function joinSelect(string $joinType, QueryBuildSelect $QuerySelect, string $alias, $onClause): self {
		$joinType = trim ( strtoupper ( $joinType ) );
		if (! in_array ( $joinType, SqlJoin::getConstants ()->getData ( true ) ))
			throw new \Exception ( sprintf ( dgettext ( 'core', 'Unhandled join type "%s"' ) ), $joinType );
		$this->joins [$alias] = [ 
				'select' => $QuerySelect,
				'type' => $joinType,
				'on' => $onClause
		];
		return $this;
	}

	/**
	 *
	 * @param Model $QuerySelect
	 *        	Here, you join a query builder, returning a SELECT full SQL statement. It will build the query placing a sub query to JOIN.
	 * @param string $alias
	 *        	You must set a distinguishable table alias related to the entity passed. Example: 'view_alias2'
	 * @param string|iterable $onClause
	 *        	You must write your conditions. Most of the time, it follows this form: "table_alias1.id = table_alias2.foreign_key_id"
	 * @return self
	 */
	public function innerJoin(Model $Entity, string $alias, $onClause): self {
		if (empty ( $onClause ))
			throw new \Exception ( dgettext ( 'core', '"ON" clause is mandatory' ) );
		return $this->join ( SqlJoin::INNER, $Entity, $alias, $onClause );
	}

	/**
	 * Join a sub query
	 *
	 * @param Model $QuerySelect
	 *        	Here, you join a query builder, returning a SELECT full SQL statement. It will build the query placing a sub query to JOIN.
	 * @param string $alias
	 *        	You must set a distinguishable table alias related to the entity passed. Example: 'view_alias2'
	 * @param string|iterable $onClause
	 *        	You must write your conditions. Most of the time, it follows this form: "table_alias1.id = table_alias2.foreign_key_id"
	 * @return self
	 */
	public function innerJoinSelect(QueryBuildSelect $QuerySelect, string $alias, $onClause): self {
		if (empty ( $onClause ))
			throw new \Exception ( dgettext ( 'core', '"ON" clause is mandatory' ) );
		return $this->joinSelect ( SqlJoin::INNER, $QuerySelect, $alias, $onClause );
	}

	/**
	 *
	 * @param Model $Entity
	 *        	Here, you join entity with another entity. You do not pass the explicit SQL table name. Example: new E_Table2()
	 * @param string $alias
	 *        	You must set a distinguishable table alias related to the entity passed. Example: 'table_alias2'
	 * @param string|iterable $onClause
	 *        	You must write your conditions. Most of the time, it follows this form: "table_alias1.id = table_alias2.foreign_key_id"
	 * @return self
	 */
	public function leftJoin(Model $Entity, string $alias, $onClause): self {
		if (empty ( $onClause ))
			throw new \Exception ( dgettext ( 'core', '"ON" clause is mandatory' ) );
		return $this->join ( SqlJoin::LEFT, $Entity, $alias, $onClause );
	}

	/**
	 * Join a sub query
	 *
	 * @param Model $QuerySelect
	 *        	Here, you join a query builder, returning a SELECT full SQL statement. It will build the query placing a sub query to JOIN.
	 * @param string $alias
	 *        	You must set a distinguishable table alias related to the entity passed. Example: 'view_alias2'
	 * @param string|iterable $onClause
	 *        	You must write your conditions. Most of the time, it follows this form: "table_alias1.id = table_alias2.foreign_key_id"
	 * @return self
	 */
	public function leftJoinSelect(QueryBuildSelect $QuerySelect, string $alias, $onClause): self {
		if (empty ( $onClause ))
			throw new \Exception ( dgettext ( 'core', '"ON" clause is mandatory' ) );
		return $this->joinSelect ( SqlJoin::LEFT, $QuerySelect, $alias, $onClause );
	}

	/**
	 *
	 * @param Model $Entity
	 *        	Here, you join entity with another entity. You do not pass the explicit SQL table name. Example: new E_Table2()
	 * @param string $alias
	 *        	You must set a distinguishable table alias related to the entity passed. Example: 'table_alias2'
	 * @param string|iterable $onClause
	 *        	You must write your conditions. Most of the time, it follows this form: "table_alias1.id = table_alias2.foreign_key_id"
	 * @return self
	 */
	public function rightJoin(Model $Entity, string $alias, $onClause): self {
		if (empty ( $onClause ))
			throw new \Exception ( dgettext ( 'core', '"ON" clause is mandatory' ) );
		return $this->join ( SqlJoin::RIGHT, $Entity, $alias, $onClause );
	}

	/**
	 * Join a sub query
	 *
	 * @param Model $QuerySelect
	 *        	Here, you join a query builder, returning a SELECT full SQL statement. It will build the query placing a sub query to JOIN.
	 * @param string $alias
	 *        	You must set a distinguishable table alias related to the entity passed. Example: 'view_alias2'
	 * @param string|iterable $onClause
	 *        	You must write your conditions. Most of the time, it follows this form: "table_alias1.id = table_alias2.foreign_key_id"
	 * @return self
	 */
	public function rightJoinSelect(QueryBuildSelect $QuerySelect, string $alias, $onClause): self {
		if (empty ( $onClause ))
			throw new \Exception ( dgettext ( 'core', '"ON" clause is mandatory' ) );
		return $this->joinSelect ( SqlJoin::RIGHT, $QuerySelect, $alias, $onClause );
	}

	/**
	 * A natural join implicitly joins 2 tables on the <b>SAME column name</b>.
	 * It is not relevant for many cases. It doesn't need any on clause.
	 *
	 * @param Model $Entity
	 *        	Here, you join entity with another entity. You do not pass the explicit SQL table name. Example: new E_Table2()
	 * @param string $alias
	 *        	You must set a distinguishable table alias related to the entity passed. Example: 'table_alias2'
	 * @return self
	 */
	public function naturalJoin(Model $Entity, string $alias): self {
		return $this->join ( SqlJoin::NATURAL, $Entity, $alias, null );
	}

	/**
	 * Join a sub query
	 *
	 * @param Model $QuerySelect
	 *        	Here, you join a query builder, returning a SELECT full SQL statement. It will build the query placing a sub query to JOIN.
	 * @param string $alias
	 *        	You must set a distinguishable table alias related to the entity passed. Example: 'view_alias2'
	 * @return self
	 */
	public function naturalJoinSelect(QueryBuildSelect $QuerySelect, string $alias): self {
		return $this->joinSelect ( SqlJoin::NATURAL, $QuerySelect, $alias );
	}

	/**
	 *
	 * @return array
	 */
	public function getJoinAliases(): ?array {
		return ! empty ( $this->joins ) ? array_keys ( $this->joins ) : null;
	}

	/**
	 *
	 * @return self
	 */
	final protected function setJoins(): self {
		$this->queryJoins = '';
		$joins = $this->getJoins ();
		if (! empty ( $joins )) {
			foreach ( $joins as $alias => $props ) {
				$QueryableObject = isset ( $props ['entity'] ) ? $props ['entity'] : (isset ( $props ['select'] ) ? $props ['select'] : null);
				$joinType = $props ['type'];
				if ($QueryableObject instanceof Model) {
					$tableName = $QueryableObject->getTableName ();
					$quote = strtolower ( $QueryableObject->getPDO ()->getAttribute ( \PDO::ATTR_DRIVER_NAME ) ) === 'mysql' ? '`' : '"';
					$this->queryJoins .= "$joinType {$quote}$tableName{$quote} $alias";
				} elseif ($QueryableObject instanceof QueryBuildSelect) {
					$subQueryString = $QueryableObject->build ()->getQueryString ();
					$this->queryJoins .= "$joinType ($subQueryString) $alias";
					$subPrepared = $QueryableObject->getPreparedValues ();
					if (! empty ( $subPrepared ))
						$this->preparedValues = array_merge ( $this->preparedValues, $subPrepared );
				} else
					continue;
				$onClause = '';
				if (! empty ( $props ['on'] )) {
					$onClause = is_iterable ( $props ['on'] ) ? $this->buildFiltersArray ( $props ['on'] ) : trim ( ( string ) $props ['on'] );
					if (is_array ( $onClause ) && ! empty ( $onClause ))
						$onClause = trim ( implode ( ' ' . SqlOperator::AND . ' ', $onClause ) );
					if ($onClause)
						$this->queryJoins .= " ON $onClause";
				}
				$this->queryJoins .= "\n";
			}
		}
		return $this;
	}
}