<?php

namespace FragTale\Database\QueryBuilder;

use FragTale\Database\QueryBuilder;
use FragTale\Implement\QueryBuilder\JoinTrait;
use FragTale\Application\Model;
use FragTale\DataCollection;
use FragTale\Constant\Database\SqlOperator;

/**
 *
 * @author Fabrice Dant <fragtale.development@gmail.com>
 * @copyright 2024 FragTale 2 - Fabrice Dant
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-fr.txt CeCILL Licence 2.1 (French version)
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-en.txt CeCILL Licence 2.1 (English version)
 *         
 */
class QueryBuildUpdate extends QueryBuilder {
	use JoinTrait;
	protected ?iterable $Row;

	/**
	 * Important note: You cannot build a query that update fields in multiple entities.
	 * You can only set values to only one entity passed in constructor.
	 *
	 * @param Model $Entity
	 * @param string $alias
	 */
	function __construct(Model $Entity, ?string $alias = null) {
		parent::__construct ( $Entity, $alias );
		$this->Row = null;
	}

	/**
	 * Set values to update
	 *
	 * @param iterable $Row
	 *        	The row to update (pass the fields to set).
	 *        	You can pass an array: [ 'field_name' => $value ]. Do not pass the alias. This update function does not update fields from another entity.
	 *        	$value should be a single value (string, int, bool, float, null).
	 *        	If you want to set another field value, this is the special case when you must set $value as an itarable (array):
	 *        	[ 'field_name' => [ SqlOperator::EQ_FIELD => 'another_field_name' ] ]
	 *        	This will result to a query part like this: entity_alias.field_name = entity_alias.another_field_name
	 *        	If the another_field was autmatically detected as a field as entity property.
	 *        	You can also use EQ_LITT, it behaves the same.
	 * @return self
	 */
	public function setRow(iterable $Row): self {
		$this->Row = $Row;
		return $this;
	}

	/**
	 * Build query string
	 *
	 * {@inheritdoc}
	 * @see \FragTale\Database\QueryBuilder::build()
	 */
	public function build(): self {
		parent::build ();

		if (empty ( $this->Row ) || ($this->Row instanceof DataCollection && ! $this->Row->count ()))
			return $this;

		$this->setJoins ()->setConditions ();

		$quote = strtolower ( $this->Entity->getPDO ()->getAttribute ( \PDO::ATTR_DRIVER_NAME ) ) === 'mysql' ? '`' : '"';
		$mainAlias = $this->alias;
		$tableName = $quote . $this->Entity->getTableName () . "$quote $mainAlias\n$this->queryJoins";

		$columns = [ ];
		foreach ( $this->Row as $columnName => $value ) {
			if ($this->Entity->isEntityColumn ( $columnName ) && ! $this->Entity->isPrimaryKey ( $columnName ) && $this->Entity->castColumnValue ( $columnName, $value )) {
				if (is_iterable ( $value )) {
					// If value is an array (or an iterable) it must contain specific condition, like setting another filed value.
					// SqlOperators are limited
					foreach ( $value as $sqlOp => $subvalue ) {
						switch ($sqlOp) {
							case SqlOperator::EQ_FIELD :
							case SqlOperator::EQ_LITT :
								if (! is_iterable ( $subvalue )) {
									// We set another field value or the value as it is passed.
									if ($this->Entity->isEntityColumn ( $subvalue ))
										$columns [] = "{$mainAlias}.{$columnName} = {$mainAlias}.{$subvalue}";
									else
										$columns [] = "{$mainAlias}.{$columnName} = $subvalue";
								}
								break;
							default :
								// Try to set the default behaviour
								if ($this->Entity->isBool ( $columnName )) {
									// Handling specific case for "bool" or "bit" type that are buggy with PDO
									$mark = ( int ) $subvalue ? 1 : 0;
								} else {
									$rand = substr ( md5 ( rand () . microtime () ), 0, 8 );
									$mark = ":{$columnName}_{$rand}";
									$this->preparedValues [$mark] = $subvalue;
								}
								$columns [] = "{$mainAlias}.{$columnName} = $mark";
						}
					}
				} elseif ($this->Entity->isBool ( $columnName )) {
					// Handling specific case for "bool" or "bit" type that are buggy with PDO
					$mark = ( int ) $value ? 1 : 0;
				} else {
					$rand = substr ( md5 ( rand () . microtime () ), 0, 8 );
					$mark = ":{$columnName}_{$rand}";
					$this->preparedValues [$mark] = $value;
				}
				$columns [] = "{$mainAlias}.{$columnName} = $mark";
			}
		}
		if (! empty ( $columns )) {
			$columns = implode ( ', ', $columns );
			$this->queryString = "UPDATE $tableName SET $columns WHERE $this->queryConditions";
		} else
			throw new \Exception ( dgettext ( 'core', 'Row to update has no value passed that is updatable. Query builder cannot build the update query.' ) );
		return $this;
	}

	/**
	 * Execute update query
	 *
	 * @return Model
	 */
	final public function execute(): Model {
		$microtimeStart = microtime ( true );
		$query = null;
		$preparedValues = [ ];
		try {
			$query = $this->build ()->getQueryString ();
			$preparedValues = $this->getPreparedValues ();
		} catch ( \Exception $Exc ) {
			return $this->Entity->logTransactionStatus ( $this->Entity::STATUS_ERROR, [ 
					'filters' => $this->filters,
					'prepared' => $preparedValues
			], get_class ( $this->Entity ) . ' on execute update', $Exc->getMessage (), $query, $microtimeStart );
		}
		try {
			$Statement = $this->Entity->getPDO ()->prepare ( $query );
			if (! $Statement->execute ( $preparedValues ))
				return $this->Entity->logTransactionStatus ( $this->Entity::STATUS_ERROR, [ 
						'filters' => $this->filters,
						'prepared' => $preparedValues
				], get_class ( $this->Entity ) . ' on execute update', $Statement->errorInfo (), $query, $microtimeStart );
		} catch ( \PDOException $Exc ) {
			$this->Entity->getSuperServices ()->getErrorHandlerService ()->catchThrowable ( $Exc );
			return $this->Entity->logTransactionStatus ( $this->Entity::STATUS_ERROR, [ 
					'filters' => $this->filters,
					'prepared' => $preparedValues
			], get_class ( $this->Entity ) . ' on execute update', $Exc->getMessage (), $query, $microtimeStart );
		}

		if ($Statement->rowCount ()) {
			$successMsg = sprintf ( dgettext ( 'core', 'Data successfully updated.' ), $query, $microtimeStart );
			return $this->Entity->logTransactionStatus ( $this->Entity::STATUS_SUCCESS, [ 
					'filters' => $this->filters,
					'prepared' => $preparedValues
			], get_class ( $this->Entity ) . ' on execute update', $successMsg, $query, $microtimeStart );
		} else {
			$neutralMsg = sprintf ( dgettext ( 'core', 'Nothing changed.' ), $query, $microtimeStart );
			return $this->Entity->logTransactionStatus ( $this->Entity::STATUS_NEUTRAL, [ 
					'filters' => $this->filters,
					'prepared' => $preparedValues
			], get_class ( $this->Entity ) . ' on execute update', $neutralMsg, $query, $microtimeStart );
		}

		return $this->Entity;
	}
}