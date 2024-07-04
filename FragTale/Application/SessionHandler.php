<?php

namespace FragTale\Application;

use FragTale\Application;
use MongoDB\Driver\Manager;
use FragTale\DataCollection;
use MongoDB\Driver\BulkWrite;
use FragTale\Constant\MessageType;
use FragTale\DataCollection\MongoCollection;

/**
 * This session handler uses MongoDB
 *
 * @author Fabrice Dant <fragtale.development@gmail.com>
 * @copyright 2024 FragTale 2 - Fabrice Dant
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-fr.txt CeCILL Licence 2.1 (French version)
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-en.txt CeCILL Licence 2.1 (English version)
 *         
 */
class SessionHandler extends Application implements \SessionHandlerInterface {
	protected string $host;
	protected string $collectionName = 'FragTaleSession';
	protected string $dbName;
	protected string $sessionName;
	protected Manager $Mongo;
	protected DataCollection $DbConf;

	/**
	 * This collection must contain only 1 row at index 0
	 *
	 * @var MongoCollection
	 */
	protected MongoCollection $Collection;

	/**
	 * Sessions are stored in MongoDB
	 */
	function __construct() {
		$this->host = $this->getSuperServices ()->getHttpServerService ()->getHost ();
		$this->Mongo = $this->getSuperServices ()->getDatabaseConnectorService ()->getDefaultMongoManager ();
		$this->DbConf = $this->getSuperServices ()->getProjectService ()->getDefaultMongoConfiguration () ? $this->getSuperServices ()->getProjectService ()->getDefaultMongoConfiguration () : new DataCollection ();
		// By default, db name is project name unless you have set a database name in configuration file
		$this->dbName = $this->DbConf->findByKey ( 'database' ) ? $this->DbConf->findByKey ( 'database' ) : $this->getSuperServices ()->getProjectService ()->getName ();
		$this->Collection = new MongoCollection ();
	}

	/**
	 * Returns the database name used to read & store this session data
	 *
	 * @return string
	 */
	public function getDbName(): string {
		return $this->dbName;
	}

	/**
	 * Returns the collection name used to read & store this session data
	 *
	 * @return string
	 */
	public function getCollectionName(): string {
		return $this->collectionName;
	}

	/**
	 * It is obviously preferable to set the MongoDb Manager before this class instance is set as SessionHandler with function "session_set_save_handler()"
	 *
	 * @param Manager $Mongo
	 * @return self
	 */
	public function setMongoManager(Manager $Mongo): self {
		$this->Mongo = $Mongo;
		return $this;
	}

	/**
	 * Define the database name used to read & store this session data
	 *
	 * @param string $dbName
	 */
	public function setDbName(string $dbName): self {
		$this->dbName = $dbName;
		return $this;
	}

	/**
	 * Define the collection name used to read & store this session data
	 *
	 * @param string $dbName
	 */
	public function setCollectionName(string $collectionName): self {
		$this->collectionName = $collectionName;
		return $this;
	}

	/**
	 *
	 * @param string $session_id
	 * @return string
	 */
	protected function buildSearchedId($session_id): string {
		return "$this->sessionName/$session_id";
	}

	/**
	 * This function does not really matter, but must exist due to implementation and native session handler behavior using file
	 *
	 * @see \SessionHandlerInterface::open()
	 * @return bool
	 */
	public function open(string $save_path, string $session_name): bool {
		$this->sessionName = $session_name;
		return true;
	}

	/**
	 *
	 * @see \SessionHandlerInterface::read()
	 * @return string
	 */
	public function read($session_id): string {
		$_id = $this->buildSearchedId ( $session_id );
		$this->Collection->setSource ( $this->Mongo, $this->dbName, $this->collectionName )->load ( [ 
				'_id' => $_id
		] );

		if (! $this->Collection->count ()) {
			// No data found, the session is initialized
			$Row = (new DataCollection ())->upsert ( '_id', $_id );
			$this->Collection->push ( $Row );
		}

		if (! $this->getRow ()->findByKey ( 'created_time' ))
			$this->getRow ()->upsert ( 'created_time', time () );

		if (! $this->getRow ()->findByKey ( 'created_date' ))
			$this->getRow ()->upsert ( 'created_date', date ( 'Y-m-d H:i:s' ) );

		if (! $this->getRow ()->findByKey ( 'session_id' ))
			$this->getRow ()->upsert ( 'session_id', $session_id );

		if (! $this->getRow ()->findByKey ( 'host' ))
			$this->getRow ()->upsert ( 'host', $this->host );

		$this->getRow ()->upsert ( '@mongodb', $this->DbConf->findByKey ( 'host' ) . ':' . $this->DbConf->findByKey ( 'port' ) );

		return '';
	}

	/**
	 *
	 * @see \SessionHandlerInterface::destroy()
	 * @return bool
	 */
	public function destroy($session_id): bool {
		$Bulk = new BulkWrite ();
		$Bulk->delete ( [ 
				'_id' => $this->buildSearchedId ( $session_id )
		] );
		if ($this->Mongo->executeBulkWrite ( "$this->dbName.$this->collectionName", $Bulk )->getDeletedCount ()) {
			$this->Collection->delete ( 0 );
			return true;
		}
		return false;
	}

	/**
	 * Clean up obsolete sessions
	 *
	 * @see \SessionHandlerInterface::gc()
	 *
	 * @param int $maxlifetime
	 * @return int
	 */
	public function gc($maxlifetime): int {
		$diffTime = time () - $maxlifetime;
		$Bulk = new BulkWrite ();
		$Bulk->delete ( [ 
				'modified_time' => [ 
						'$lt' => $diffTime
				]
		] );
		return ( int ) $this->Mongo->executeBulkWrite ( "$this->dbName.$this->collectionName", $Bulk )->getDeletedCount ();
	}

	/**
	 *
	 * {@inheritdoc}
	 * @see \SessionHandlerInterface::close()
	 */
	public function close(): bool {
		return true;
	}

	/**
	 *
	 * {@inheritdoc}
	 * @see \SessionHandlerInterface::write()
	 */
	public function write($session_id, $session_data): bool {
		try {
			$objId = $this->buildSearchedId ( $session_id );
			if ($objId != $this->getRow ()->findByKey ( '_id' )) {
				$msg = sprintf ( dgettext ( 'core', 'Session ID moved from %1s to %2s' ), $this->getRow ()->findByKey ( '_id' ), $objId );
				$this->log ( $msg )
					->getSuperServices ()
					->getFrontMessageService ()
					->add ( $msg, MessageType::WARNING );
			}
			$client ['REMOTE_ADDR'] = $_SERVER ['REMOTE_ADDR'];
			if (! empty ( $_SERVER ['HTTP_X_FORWARDED_FOR'] ))
				$client ['HTTP_X_FORWARDED_FOR'] = $_SERVER ['HTTP_X_FORWARDED_FOR'];
			if (! empty ( $_SERVER ['HTTP_USER_AGENT'] ))
				$client ['HTTP_USER_AGENT'] = $_SERVER ['HTTP_USER_AGENT'];
			$this->getRow ()
				->upsert ( 'modified_time', time () )
				->upsert ( 'modified_date', date ( 'Y-m-d H:i:s' ) )
				->upsert ( 'Client', $client );
			if (! empty ( $session_data ))
				$this->getRow ()->upsert ( '_SESSION', unserialize ( $session_data ) );
			$this->Collection->save ( true );
		} catch ( \Exception $Exc ) {
			$this->getSuperServices ()->getErrorHandlerService ()->catchThrowable ( $Exc );
			return false;
		}
		return true;
	}

	/**
	 *
	 * @return DataCollection|NULL
	 */
	public function getRow(): ?DataCollection {
		return $this->Collection->findAt ( 0 );
	}

	/**
	 *
	 * @param bool $keep
	 * @return self
	 */
	public function keepSession(bool $keep): self {
		$this->getRow ()->upsert ( 'keep', $keep );
		return $this;
	}
}