<?php

namespace FragTale\Database;

use FragTale\Application\Model;

/**
 * Note that any ORM can encounter some issues to reproduce advanced SQL queries.
 * This query builder targets simple queries that are at least compatible with popular and recent MySQL database server.
 *
 * @author Fabrice Dant <fragtale.development@gmail.com>
 * @copyright 2024 FragTale 2 - Fabrice Dant
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-fr.txt CeCILL Licence 2.1 (French version)
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-en.txt CeCILL Licence 2.1 (English version)
 *         
 */
abstract class QueryBuilder {
	protected Model $Entity;
	protected string $alias;
	protected string $queryString;
	protected array $preparedValues;

	/**
	 * Build the query string.
	 * Note that this class won't execute the query built. It will only return an executable SQL query.
	 *
	 * @param Model $Entity
	 * @param string $alias
	 */
	function __construct(Model $Entity, ?string $alias = null) {
		$this->Entity = $Entity;
		if (! $alias)
			$alias = $Entity->getTableName () . substr ( md5 ( rand () ), 0, 6 );
		$this->alias = $alias;
		$this->queryString = '';
		$this->preparedValues = [ ];
	}

	/**
	 * This function MUST be called ALWAYS BEFORE calling <b>"getQueryString()"</b> because this queryString is built here.
	 * Build must also be called after passing filters and eventually, any else option that composes the query string.
	 * Basically: instanciate, set, build and finally get.
	 *
	 * @return self
	 */
	public function build(): self {
		$this->queryString = '';
		$this->preparedValues = [ ];
		return $this;
	}

	/**
	 *
	 * @return string
	 */
	public function getAlias(): string {
		return $this->alias;
	}

	/**
	 *
	 * @return \FragTale\Application\Model
	 */
	public function getEntity(): Model {
		return $this->Entity;
	}

	/**
	 *
	 * @return array|NULL
	 */
	public function getPreparedValues(): ?array {
		return $this->preparedValues;
	}

	/**
	 *
	 * @return string
	 */
	public function getQueryString(): string {
		return $this->queryString;
	}
}