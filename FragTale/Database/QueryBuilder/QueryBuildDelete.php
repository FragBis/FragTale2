<?php

namespace FragTale\Database\QueryBuilder;

use FragTale\Database\QueryBuilder;
use FragTale\Implement\QueryBuilder\JoinTrait;
use FragTale\Application\Model;

/**
 *
 * @author Fabrice Dant <fragtale.development@gmail.com>
 * @copyright 2024 FragTale 2 - Fabrice Dant
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-fr.txt CeCILL Licence 2.1 (French version)
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-en.txt CeCILL Licence 2.1 (English version)
 *         
 */
class QueryBuildDelete extends QueryBuilder {
	use JoinTrait;

	/**
	 *
	 * @param Model $Entity
	 * @param string $alias
	 */
	function __construct(Model $Entity, ?string $alias = null) {
		parent::__construct ( $Entity, $alias );
	}

	/**
	 *
	 * {@inheritdoc}
	 * @see \FragTale\Database\QueryBuilder::build()
	 */
	public function build(): QueryBuildDelete {
		parent::build ();
		$this->setJoins ()->setConditions ();
		if (empty ( $this->queryConditions ))
			throw new \Exception ( dgettext ( 'core', 'Building delete query requires valid filters.' ) );

		$mainAlias = $this->alias;
		$mainTableName = $this->Entity->getTableName ();
		$quote = strtolower ( $this->Entity->getPDO ()->getAttribute ( \PDO::ATTR_DRIVER_NAME ) ) === 'mysql' ? '`' : '"';
		$from = "{$quote}$mainTableName{$quote} $mainAlias\n$this->queryJoins";
		$where = "WHERE $this->queryConditions";
		$this->queryString = "DELETE {$mainAlias}.* FROM {$from}{$where}";
		return $this;
	}

	/**
	 * Remove database entries giving filters.
	 *
	 * @param iterable $filters
	 * @return Model
	 */
	final public function execute(iterable $filters): Model {
		$microtimeStart = microtime ( true );
		$query = null;
		$preparedValues = $errs = [ ];
		try {
			$query = $this->where ( $filters )->build ()->getQueryString ();
			$preparedValues = $this->getPreparedValues ();
		} catch ( \Exception $Exc ) {
			return $this->Entity->logTransactionStatus ( $this->Entity::STATUS_ERROR, [ 
					'filters' => $filters,
					'prepared' => $preparedValues
			], get_class ( $this->Entity ) . ' on delete', $Exc->getMessage (), $query, $microtimeStart );
		}
		try {
			$Statement = $this->Entity->getPDO ()->prepare ( $query );
			$Statement->execute ( $preparedValues );
			$affectedRows = $Statement->rowCount ();
			$msg = sprintf ( dgettext ( 'core', 'Affected row(s): %s' ), $affectedRows );
			$errs = $Statement->errorInfo ();
			return $this->Entity->logTransactionStatus ( $affectedRows ? $this->Entity::STATUS_SUCCESS : $this->Entity::STATUS_NEUTRAL, [ 
					'filters' => $filters,
					'prepared' => $preparedValues
			], get_class ( $this->Entity ) . ' on delete', ! empty ( $errs [2] ) ? $errs [2] : $msg, $query, $microtimeStart );
		} catch ( \PDOException $Exc ) {
			return $this->Entity->logTransactionStatus ( $this->Entity::STATUS_ERROR, [ 
					'filters' => $filters,
					'prepared' => $preparedValues
			], get_class ( $this->Entity ) . ' on delete', (! empty ( $errs [2] ) ? $errs [2] . "\n" : '') . $Exc->getMessage (), $query, $microtimeStart );
		}
	}
}