<?php

namespace FragTale\Database\QueryBuilder;

use FragTale\Database\QueryBuilder;
use FragTale\Implement\QueryBuilder\JoinTrait;
use FragTale\Application\Model;
use FragTale\Constant\Database\SqlOperator;
use FragTale\Constant\Database\SqlOrder;

/**
 *
 * @author Fabrice Dant <fragtale.development@gmail.com>
 * @copyright 2024 FragTale 2 - Fabrice Dant
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-fr.txt CeCILL Licence 2.1 (French version)
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-en.txt CeCILL Licence 2.1 (English version)
 *         
 */
class QueryBuildSelect extends QueryBuilder {
	use JoinTrait;
	/**
	 *
	 * @var array|null
	 */
	protected ?array $select;
	/**
	 *
	 * @var bool
	 */
	protected bool $distinct;
	/**
	 *
	 * @var array|null
	 */
	protected ?array $orderBy;
	/**
	 *
	 * @var array|null
	 */
	protected ?array $groupBy;
	/**
	 *
	 * @var array|null
	 */
	protected ?array $having;
	/**
	 *
	 * @var array|null
	 */
	protected ?array $limit;

	/**
	 * Build SQL query.
	 * You can join multiple entities. Filters (conditions in "WHERE" clause) must be passed in an iterable (array or DataCollection) following rules set to SqlOperators.
	 *
	 * @param Model $Entity
	 *        	The first entity that will be returned with function "execute()". It corresponds to the first table after "FROM".
	 * @param string $alias
	 *        	The table/entity alias
	 */
	function __construct(Model $Entity, ?string $alias = null) {
		parent::__construct ( $Entity, $alias );
		$this->select = null;
		$this->distinct = false;
		$this->orderBy = null;
		$this->groupBy = null;
		$this->having = null;
		$this->limit = null;
	}

	/**
	 * Set all selection in a row.
	 * This will replace previous values.
	 * You may want to add fields one by one, use "addSelect()" function.
	 *
	 * @param mixed|array|string|null $selection
	 *        	(optional) It can be a full string or an array of string. If empty, default is '*'
	 * @throws \Exception
	 * @return self
	 */
	public function select($selection = '*'): self {
		$this->select = [ ];

		if (empty ( $selection ))
			$selection = '*';

		if (is_string ( $selection ))
			$this->select [] = trim ( $selection );
		elseif (is_iterable ( $selection )) {
			foreach ( $selection as $sel ) {
				if (! is_string ( $sel ))
					throw new \Exception ( dgettext ( 'core', 'Parameter "$select" must be a single array containing string value or a full string.' ) );
				$this->select [] = trim ( $sel );
			}
		} else
			throw new \Exception ( dgettext ( 'core', 'Parameter "$select" must be a single array containing string value or a full string.' ) );

		return $this;
	}

	/**
	 * Add a field or a statement.
	 * You should directly set an alias (AS) directly in your selection string.
	 *
	 * @param string $selection
	 * @return self
	 */
	public function addSelect(string $selection): self {
		$this->select [] = trim ( $selection );
		return $this;
	}

	/**
	 * Get list of fields and "SELECT" statements.
	 *
	 * @return array
	 */
	public function getSelect(): array {
		return $this->select;
	}

	/**
	 * Build the query string.
	 * It is important to call this function before calling "getQueryString()".
	 *
	 * @return self
	 */
	public function build(): self {
		parent::build ();
		$this->setJoins ()->setConditions ();
		$select = empty ( $this->select ) ? '*' : implode ( ",\n", $this->select );
		$distinct = $this->distinct ? " DISTINCT" : '';
		$tableName = $this->Entity->getTableName ();
		$quote = strtolower ( $this->Entity->getPDO ()->getAttribute ( \PDO::ATTR_DRIVER_NAME ) ) === 'mysql' ? '`' : '"';
		$alias = $this->alias;
		$from = "{$quote}$tableName{$quote} $alias\n$this->queryJoins";
		$where = $this->queryConditions ? "WHERE $this->queryConditions" : '';
		$queryEndings = '';
		if ($groupBy = $this->getGroupBy ())
			$queryEndings .= "\n$groupBy";
		if ($having = $this->getHaving ())
			$queryEndings .= "\n$having";
		if ($orderBy = $this->getOrderBy ())
			$queryEndings .= "\n$orderBy";
		if ($limit = $this->getLimit ())
			$queryEndings .= "\n$limit";
		$queryEndings = trim ( $where . $queryEndings );
		$this->queryString = "SELECT{$distinct}\n{$select}\nFROM {$from}{$queryEndings}";
		return $this;
	}

	/**
	 * Get the "LIMIT" statement that comes at the end of the query string.
	 * MySQL compatible
	 *
	 * @todo Convert "LIMIT" using "TOP" for SQL Server, and "ROWNUM <=" for Oracle. Add "OFFSET" for PostgreSQL
	 * @return string
	 */
	protected function getLimit(): string {
		if (! empty ( $this->limit )) {
			ksort ( $this->limit );
			return 'LIMIT ' . implode ( ', ', $this->limit );
		}
		return '';
	}
	/**
	 * Set limit.
	 * MySQL compatible
	 *
	 * @todo Convert "LIMIT" using "TOP" for SQL Server, and "ROWNUM <=" for Oracle. Add "OFFSET" for PostgreSQL
	 * @param mixed $limit
	 *        	Can be an array with 2 integers (for example: [0, 10]) or a string (for example: "0, 10")
	 * @return self
	 */
	public function limit($limit): self {
		$this->limit = [ ];
		if ($limit) {
			if (is_string ( $limit ) || is_int ( $limit )) {
				$limit = ( string ) $limit;
				if (strpos ( $limit, ',' ) !== false) {
					$exp = explode ( ',', $limit );
					$this->limit [] = ( int ) trim ( $exp [0] );
					$this->limit [] = ( int ) trim ( $exp [1] );
				} elseif (is_numeric ( $limit )) {
					$this->limit [] = ( int ) trim ( $limit );
				}
			} elseif (is_array ( $limit )) {
				for($i = 0; $i < 2; $i ++) {
					$exp = array_shift ( $limit );
					if (is_int ( $exp ) || is_numeric ( $exp ))
						$this->limit [] = ( int ) trim ( $exp );
				}
			}
		}
		return $this;
	}

	/**
	 * Specify wether or not you want to a "SELECT DISTINCT".
	 *
	 * @param bool $distinct
	 *        	True: includes a distinct after select
	 * @return self
	 */
	public function distinct(?bool $distinct): self {
		$this->distinct = ( bool ) $distinct;
		return $this;
	}

	/**
	 * Get the "ORDER BY" part of the query string.
	 *
	 * @return string
	 */
	protected function getOrderBy(): string {
		$orderBy = [ ];
		if (! empty ( $this->orderBy )) {
			foreach ( $this->orderBy as $key => $direction )
				$orderBy [] = "$key $direction";
			if (count ( $orderBy ))
				return 'ORDER BY ' . implode ( ', ', $orderBy );
		}
		return '';
	}

	/**
	 * Set ORDER BY.
	 * This will replace previous values.
	 * Here, you set the entire condition. Use "addOrderBy" to set conditions one by one.
	 *
	 * @param array $orderBy
	 *        	For example: ['id' => 'ASC']
	 * @return self
	 */
	public function orderBy(?array $orderBy): self {
		$this->orderBy = [ ];
		if (! empty ( $orderBy ))
			foreach ( $orderBy as $columnName => $direction )
				$this->addOrderBy ( $columnName, $direction );
		return $this;
	}

	/**
	 * Add one condition to ORDER BY.
	 *
	 * @param string $columnName
	 *        	The column name.
	 * @param string $direction
	 *        	"ASC" or "DESC". "ASC" by default.
	 * @return self
	 */
	public function addOrderBy(string $columnName, string $direction = SqlOrder::ASC): self {
		$direction = strtoupper ( $direction );
		if (in_array ( $direction, [ 
				SqlOrder::ASC,
				SqlOrder::DESC
		] ))
			$this->orderBy [$columnName] = $direction;
		return $this;
	}

	/**
	 * Get the "GROUP BY" part of the query string.
	 *
	 * @return string
	 */
	protected function getGroupBy(): string {
		return ! empty ( $this->groupBy ) ? 'GROUP BY ' . implode ( ', ', $this->groupBy ) : '';
	}

	/**
	 * Set the entire GROUP BY statement.
	 * This will replace previous values.
	 * You may want to set the conditions one by one, use "addGroupBy()" function.
	 *
	 * @param array $groupBy
	 *        	Single 1 dimension array containing the fields on which to group the result.
	 * @return self
	 */
	public function groupBy(?array $groupBy): self {
		$this->groupBy = [ ];
		if (! empty ( $groupBy ))
			foreach ( $groupBy as $columnName )
				$this->addGroupBy ( $columnName );
		return $this;
	}

	/**
	 * Add one field to GROUP BY statement.
	 *
	 * @param string $columnName
	 * @return self
	 */
	public function addGroupBy(string $columnName): self {
		if (is_string ( $columnName ))
			$this->groupBy [] = $columnName;
		return $this;
	}

	/**
	 * Get the HAVING part of the query string.
	 * It comes with "GROUP BY".
	 *
	 * @return string
	 */
	protected function getHaving(): string {
		return ! empty ( $this->having ) ? 'HAVING ' . implode ( ' ' . SqlOperator::AND . ' ', $this->buildFiltersArray ( $this->having ) ) : '';
	}

	/**
	 * Set the entire array containing conditions in HAVING clause.
	 * This will replace any previous conditions.
	 * You may want to use "addHaving()" function to set the conditions one by one.
	 *
	 * @param array|null $having
	 *        	List of conditions
	 * @return self
	 */
	public function having(?array $having): self {
		$this->having = $having;
		return $this;
	}

	/**
	 * Add a condition in HAVING clause.
	 *
	 * @param mixed|string|array $condition
	 * @return self
	 */
	public function addHaving($condition): self {
		if (! empty ( $condition ))
			$this->having [] = $condition;
		return $this;
	}

	/**
	 * Same as "execute", but returns a QueryBuildSelect ($this).
	 * Execute query and load the entity collection passed in the constructor, following filters set to this query builder.
	 *
	 * @param bool $withForeignData
	 *        	If true, and only if entity has foreign key (1/n relationships only), it will also load "foreign entity(ies)"
	 * @return self
	 */
	final public function loadEntityCollection(bool $withForeignData = false): self {
		$this->execute ( $withForeignData );
		return $this;
	}

	/**
	 * Same as "loadEntityCollection", but returns a Model instance (Entity)
	 * Execute query and load the entity collection passed in the constructor, following filters set to this query builder.
	 *
	 * @param bool $loadForeignData
	 *        	If true, and only if entity has foreign key (1/n relationships only), it will also load "foreign entity(ies)"
	 * @return Model
	 */
	final public function execute(bool $loadForeignData = false): Model {
		return $this->build ()->Entity->loadCollection ( $this, $loadForeignData );
	}
}