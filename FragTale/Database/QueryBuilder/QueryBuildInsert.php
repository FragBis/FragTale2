<?php

namespace FragTale\Database\QueryBuilder;

use FragTale\Database\QueryBuilder;
use FragTale\Application\Model;
use FragTale\DataCollection;

/**
 *
 * @author Fabrice Dant <fragtale.development@gmail.com>
 * @copyright 2024 FragTale 2 - Fabrice Dant
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-fr.txt CeCILL Licence 2.1 (French version)
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-en.txt CeCILL Licence 2.1 (English version)
 *         
 */
class QueryBuildInsert extends QueryBuilder {
	protected ?DataCollection $Row;

	/**
	 *
	 * @param Model $Entity
	 * @param string $alias
	 */
	function __construct(Model $Entity, ?string $alias = null) {
		parent::__construct ( $Entity, $alias );
		$this->Row = null;
	}

	/**
	 * Set values to insert
	 *
	 * @param iterable $Row
	 * @return self
	 */
	public function setRow(iterable $Row): self {
		$this->Row = new DataCollection ( $Row );
		return $this;
	}

	/**
	 *
	 * {@inheritdoc}
	 * @see \FragTale\Database\QueryBuilder::build()
	 */
	public function build(): self {
		parent::build ();
		if (! $this->Row || ! $this->Row->count ())
			return $this;

		$tableName = $this->Entity->getTableName ();
		$columns = $marks = [ ];
		foreach ( $this->Row as $columnName => $value ) {
			if ($this->Entity->isEntityColumn ( $columnName ) && $this->Entity->castColumnValue ( $columnName, $value )) {
				$columns [] = "`$columnName`";
				if ($this->Entity->isBool ( $columnName )) {
					// Handling specific case for "bool" or "bit" type that are buggy with PDO
					$marks [] = ( int ) $value ? 1 : 0;
				} else {
					$rand = substr ( md5 ( rand () . microtime () ), 0, 8 );
					$mark = ":{$columnName}_{$rand}";
					$marks [] = $mark;
					$this->preparedValues [$mark] = $value;
				}
			}
		}
		if (empty ( $columns ) || empty ( $marks ))
			throw new \Exception ( dgettext ( 'core', 'No column and/or no value passed building insert query.' ) );

		$columns = implode ( ', ', $columns );
		$marks = implode ( ', ', $marks );
		$this->queryString = "INSERT INTO `$tableName` ($columns) VALUES ($marks)";
		return $this;
	}

	/**
	 * Execute insert statement giving the new row.
	 *
	 * @param iterable $Row
	 *        	(optional but a row must have been set before) New row to insert.
	 * @return Model
	 */
	final public function execute(?iterable $Row = null): Model {
		$microtimeStart = microtime ( true );
		if ($Row)
			$this->setRow ( $Row );
		if (! $this->Row || ! $this->Row->count ())
			return $this;

		$query = null;
		try {
			$query = $this->build ()->getQueryString ();
			$preparedValues = $this->getPreparedValues ();
		} catch ( \Exception $Exc ) {
			$this->Entity->getSuperServices ()->getErrorHandlerService ()->catchThrowable ( $Exc );
			return $this->Entity->logTransactionStatus ( $this->Entity::STATUS_ERROR, $this->Row->getData ( true ), get_class ( $this->Entity ) . ' on insert', $Exc->getMessage (), $query, $microtimeStart );
		}
		$lastInsertId = $msg = null;
		$status = $this->Entity::STATUS_ERROR;
		try {
			$Statement = $this->Entity->getPDO ()->prepare ( $query );
			if (! $Statement->execute ( $preparedValues ))
				return $this->Entity->logTransactionStatus ( $this->Entity::STATUS_ERROR, $preparedValues, get_class ( $this->Entity ) . ' on insert', $Statement->errorInfo (), $query, $microtimeStart );
			$affectedRows = $Statement->rowCount ();
			if (! $affectedRows) {
				$status = $this->Entity::STATUS_NEUTRAL;
				$msg = implode ( "|", $Statement->errorInfo () ) . "\n$query\n" . implode ( ', ', $preparedValues );
			} else {
				$status = $this->Entity::STATUS_SUCCESS;
				$lastInsertId = $this->Entity->getPDO ()->lastInsertId ();
				$msg = sprintf ( dgettext ( 'core', 'Rows affected: %s' ), $affectedRows );
			}
		} catch ( \PDOException $Exc ) {
			$this->Entity->getSuperServices ()->getErrorHandlerService ()->catchThrowable ( $Exc );
			$msg = $Exc->getMessage ();
		}
		return $this->Entity->logTransactionStatus ( $status, $this->Row->getData ( true ), get_class ( $this->Entity ) . ' on insert', $msg, $query, $microtimeStart, $lastInsertId );
	}
}