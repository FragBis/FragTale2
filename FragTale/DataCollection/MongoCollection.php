<?php

namespace FragTale\DataCollection;

use FragTale\DataCollection;
use MongoDB\Driver\Manager;
use MongoDB\Driver\Query;
use MongoDB\Driver\BulkWrite;
use MongoDB\BSON\ObjectId;
use MongoDB\Driver\WriteResult;
use MongoDB\Driver\Command;

/**
 *
 * @author Fabrice Dant <fragtale.development@gmail.com>
 * @copyright 2024 FragTale 2 - Fabrice Dant
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-fr.txt CeCILL Licence 2.1 (French version)
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-en.txt CeCILL Licence 2.1 (English version)
 *         
 */
class MongoCollection extends DataCollection {
	private ?Manager $Source = null;
	private ?string $dbName = null;
	private ?string $collectionName = null;
	private array $writeResults = [ ];

	/**
	 * MongoDB\Driver\Manager
	 * Must have been set by setSource function.
	 *
	 * @return Manager
	 */
	public function getSource(): ?Manager {
		return $this->Source;
	}
	/**
	 * Database name.
	 * Must have been set in setSource function.
	 *
	 * @return string
	 */
	public function getDbName(): string {
		return $this->dbName;
	}
	/**
	 * Collection used.
	 * Must have been set in setSource function.
	 *
	 * @return string
	 */
	public function getCollectionName(): string {
		return $this->collectionName;
	}

	/**
	 * Returns host:port
	 *
	 * @throws \Exception
	 * @return string
	 */
	public function getServer(): string {
		if (! $this->Source instanceof Manager) {
			$message = dgettext ( 'core', 'You must set source to this MongoCollection to be able to query MongoDb server.' );
			$this->log ( $message );
			throw new \Exception ( $message );
		} elseif (isset ( $this->Source->getServers () [0] )) {
			$MongoServer = $this->Source->getServers () [0];
			return $MongoServer->getHost () . ':' . $MongoServer->getPort ();
		}
		return '';
	}

	/**
	 * Get all WriteResult instances kept after a save or a deleteFromSource.
	 *
	 * @return WriteResult[]
	 */
	public function getAllWriteResults(): array {
		return $this->writeResults;
	}

	/**
	 * Get the last WriteResult instance kept after a save or a deleteFromSource.
	 *
	 * @return WriteResult|NULL
	 */
	public function getLastWriteResult(): ?WriteResult {
		$WriteResult = end ( $this->writeResults );
		return $WriteResult instanceof WriteResult ? $WriteResult : null;
	}

	/**
	 * Importing data from MongoDb requires MongoDb php extension to be installed.
	 * You can use "pecl": sudo pecl install mongodb
	 * Or using Debian-like distro and PHP repo "sury", for example if you have installed PHP7.4: sudo apt install php7.4-mongodb
	 *
	 * Old legacy "MongoClient" library used in PHP5 is not supported by PHP7.
	 * So you have to pass a "MongoDb\Driver\Manager" as first parameter intending to connect your MongoDb host.
	 *
	 * Usage:
	 * $Collection = ( new MongoCollection() )->setSource ( new \MongoDb\Driver\Manager("mongodb://localhost"), "myDb", "myCollection", [ "_id" => new \MongoDb\BSON\ObjectId("anyid") ], [ "sort" => [ "_id" => 1 ] ] );
	 *
	 * @param Manager $Mongo
	 *        	For example: new MongoDb\Driver\Manager("mongodb://localhost:27017", ["username" => "mongouser", "password" => "pwd"])
	 * @param string $dbName
	 *        	The database to use
	 * @param string $collectionName
	 *        	Collection name
	 * @return self
	 */
	public function setSource(Manager $Mongo, string $dbName, string $collectionName): self {
		$this->Source = $Mongo;
		$this->dbName = $dbName;
		$this->collectionName = $collectionName;
		return $this;
	}

	/**
	 * Usage:
	 * $Collection = ( new MongoCollection() )
	 * ->setSource ( new \MongoDb\Driver\Manager("mongodb://localhost"), "myDb", "myCollection")
	 * ->load ([ "_id" => new \MongoDb\BSON\ObjectId("anyid") ], [ "sort" => [ "_id" => 1 ] ] ));
	 *
	 * @param array $filters
	 *        	For example: ["_id" => ['$gt' => 0]]
	 * @param array $options
	 *        	For example: [ "sort" => ['_id' => -1], "limit" => 10 ]
	 *        	Mind that option: "projection" => ["_id" => 0] cannot by passed because this datacollection needs this ID
	 * @throws \Exception
	 * @return self
	 */
	public function load(array $filters = [ ], ?array $options = [ ]): self {
		if (! $this->Source instanceof Manager) {
			$message = dgettext ( 'core', 'You must set source to this MongoCollection to be able to query MongoDb server.' );
			if (IS_CLI)
				throw new \Exception ( $message );
			else
				$this->log ( $message, null, 'MongoCollection_' );
			return $this->import ( null );
		}

		global $Application;
		$DebugService = $Application->getSuperServices ()->getDebugService ();
		$timestart = microtime ( true );
		$namespace = "$this->dbName.$this->collectionName";

		unset ( $options ['projection'] ['_id'] ); // In case of this option has been passed, it is removed because intending to save this collection, we need _id

		$result = $this->Source->executeQuery ( $namespace, new Query ( $filters, $options ) )->toArray ();

		if ($DebugService->isActivated ())
			$DebugService->setDebugInfo ( "$timestart `$namespace` (load)", [ 
					'query' => [ 
							'filters' => $filters,
							'options' => $options
					],
					'result' => count ( $result ) . ' row(s)',
					'execution_time' => number_format ( microtime ( true ) - $timestart, 5 ) . 's',
					'@server' => $this->getServer ()
			], 'MONGOCOLLECTIONS' );

		return $this->import ( $result );
	}

	/**
	 * Impact modifications made to this collection into the MongoDB instance.
	 * You have previously used function "setSource".
	 * <b>Note that removed rows from this collection won't be deleted from source database.
	 * Use function "deleteFromSource" to really remove entries from MongoDB.</b>
	 * <b>Attention!</b> This function ALWAYS perform an "upsert" replacement.
	 * Mind that each row REPLACE the previous one if it exists.
	 * If you want to update one row on specified fields without removing the previous ones, use function "upsertOneIntoSource" or "upsertManyIntoSource".
	 *
	 * Object "WriteResult" can be returned by calling function "getLastWriteResult".
	 *
	 *
	 * @see BulkWrite::update()
	 * @see WriteResult
	 *
	 * @param bool $fullReplaceOnUpsert
	 *        	If true, saving will perform a FULL replacement of each documents in the collection, that means it not performs a regular update, setting values field by field.
	 * @throws \Exception
	 * @return self
	 */
	public function save(bool $fullReplaceOnUpsert = false): self {
		$message = null;
		if (! $this->Source instanceof Manager) {
			$message = dgettext ( 'core', 'You must set source to this MongoCollection to be able to upsert (save) data from MongoDb.' );
			$this->log ( $message );
			throw new \Exception ( $message );
			return $this;
		}

		global $Application;
		$DebugService = $Application->getSuperServices ()->getDebugService ();
		$timestart = microtime ( true );
		$namespace = "$this->dbName.$this->collectionName";

		$upres = 0;
		$debugValues = [ ];

		// upsert element only if modified or new
		// This not handles deletions
		$Diffs = $this->findDiffs ();
		if ($nbDiffs = $Diffs->count ()) {
			$Bulk = new BulkWrite ();
			$Diffs->forEach ( function ($ix, $Elt) use ($Bulk, $fullReplaceOnUpsert, $DebugService, $debugValues) {
				if ($Elt instanceof DataCollection && ($eltId = $Elt->findByKey ( '_id' ))) {
					if ($eltId instanceof DataCollection) {
						if ($oid = $eltId->findByKey ( 'oid' ))
							$eltId = new ObjectId ( $oid );
						else
							$eltId = $eltId->getData ( true );
					}
					// Set values
					$values = [ ];
					$elt = $Elt->getData ( true );
					$upsert = $fullReplaceOnUpsert ? true : ! $this->_idExistsIntoSource ( $eltId );
					if (! $upsert) {
						unset ( $elt ['_id'] );
						$values = [ 
								'$set' => $elt
						];
					} else {
						$values = $elt;
						$values ['_id'] = $eltId;
					}
					// Set options
					$options = [ 
							'upsert' => $upsert,
							'multi' => false
					];
					// Add bulk update
					$Bulk->update ( [ 
							'_id' => $eltId
					], $values, $options );
					// Add debug info
					if ($DebugService->isActivated ())
						$debugValues [] = [ 
								'_id' => $eltId,
								'values' => $values,
								'options' => $options
						];
				}
			} );
			if ($nbExpected = $Bulk->count ()) {
				$WriteResult = $this->Source->executeBulkWrite ( $namespace, $Bulk );
				$this->writeResults [] = $WriteResult;
				$upres = ( int ) $WriteResult->getUpsertedCount () + ( int ) $WriteResult->getInsertedCount () + ( int ) $WriteResult->getModifiedCount ();
				if ($this->modified && ($upres != $nbDiffs)) {
					$message = sprintf ( dgettext ( 'core', '%1s elements written (expected %2s) from collection %3s' ), $upres, $nbDiffs, $this->collectionName );
					if ($DebugService->isActivated () && ! empty ( $WriteResult->getWriteErrors () ))
						$message .= "\n" . print_r ( $WriteResult->getWriteErrors (), true );
					$this->log ( $message );
				}
			} elseif ($nbExpected !== $nbDiffs) {
				$message = sprintf ( dgettext ( 'core', 'Something went wrong with Mongo Bulk Write. Diffs counted: %1s; Expected by bulk: %2s' ), $nbDiffs, $nbExpected );
				$this->log ( $message );
				if ($DebugService->isActivated ())
					throw new \Exception ( $message );
			}
		}
		if ($DebugService->isActivated ()) {
			$DebugService->setDebugInfo ( "$timestart `$namespace` (save)", [ 
					'query' => $debugValues,
					'result' => [ 
							'affected_count' => "$upres",
							'status' => $message ? 'error' : 'ok',
							'message' => $message
					],
					'execution_time' => number_format ( microtime ( true ) - $timestart, 5 ) . 's',
					'@server' => $this->getServer ()
			], 'MONGOCOLLECTIONS' );
		}

		return $this;
	}

	/**
	 * This function uses "BulkWrite::delete" and do remove entries directly from the MongoDB source.
	 *
	 * Object "WriteResult" can be returned by calling function "getLastWriteResult".
	 *
	 * @see BulkWrite::delete()
	 * @see WriteResult
	 *
	 * @param array $filter
	 *        	The search filter
	 * @param array $deleteOptions
	 *        	See PHP documentation about BulkWrite::delete()
	 * @throws \Exception
	 * @return self
	 */
	public function deleteFromSource(array $filter, ?array $deleteOptions = null): self {
		if (! $this->Source instanceof Manager) {
			$message = dgettext ( 'core', 'You must set source to this MongoCollection to be able to delete data from MongoDb.' );
			$this->log ( $message );
			throw new \Exception ( $message );
			return $this;
		}

		global $Application;
		$DebugService = $Application->getSuperServices ()->getDebugService ();
		$timestart = microtime ( true );
		$namespace = "$this->dbName.$this->collectionName";

		$Bulk = new BulkWrite ();
		$Bulk->delete ( $filter, $deleteOptions );
		$WriteResult = $this->Source->executeBulkWrite ( $namespace, $Bulk );
		$this->writeResults [] = $WriteResult;

		if ($DebugService->isActivated ()) {
			$result = [ 
					'deleted_count' => $WriteResult->getDeletedCount ()
			];
			if (! empty ( $WriteResult->getWriteErrors () )) {
				$result ['status'] = 'error';
				$result ['message'] = print_r ( $WriteResult->getWriteErrors (), true );
			}
			$DebugService->setDebugInfo ( "$timestart `$namespace` (deleteFromSource)", [ 
					'query' => [ 
							'filters' => $filter,
							'options' => $deleteOptions
					],
					'result' => $result,
					'execution_time' => number_format ( microtime ( true ) - $timestart, 5 ) . 's',
					'@server' => $this->getServer ()
			], 'MONGOCOLLECTIONS' );
		}

		return $this;
	}

	/**
	 * Update or insert a single document directly into MongoDb from the given source to this collection.
	 *
	 * @param string|int|ObjectId $_id
	 *        	_id can be type of MongoDB\BSON\ObjectId, int, string or any supported type by MongoDB and the PHP MongoDB library
	 * @param array $values
	 *        	Must contain fields and values corresponding to one document
	 * @param bool $forceReplace
	 *        	If true, the upsert is forced and the previous document is ENTIRELY replaced if it already exists (i.e. if new document miss previous fields, they are lost)
	 * @throws \Exception
	 * @return self
	 */
	public function upsertOneIntoSource($_id, array $values, bool $forceReplace = false): self {
		$message = null;
		if (! $this->Source instanceof Manager) {
			$message = dgettext ( 'core', 'You must set source to this MongoCollection to be able to upsert one data into MongoDb.' );
			$this->log ( $message );
			throw new \Exception ( $message );
			return $this;
		}

		global $Application;
		$DebugService = $Application->getSuperServices ()->getDebugService ();
		$timestart = microtime ( true );
		$namespace = "$this->dbName.$this->collectionName";

		$performUpsert = $forceReplace ? $forceReplace : ! $this->_idExistsIntoSource ( $_id );
		if ($performUpsert)
			$values ['_id'] = $_id;
		else {
			unset ( $values ['_id'] );
			$values = [ 
					'$set' => $values
			];
		}
		// Set options
		$options = [ 
				'upsert' => $performUpsert, // Upsert must be true only if you want to insert or replace a document. Set "false" for a single update.
				'multi' => false
		];
		// Set bulk for update/upsert
		$Bulk = new BulkWrite ();
		$Bulk->update ( [ 
				'_id' => $_id
		], $values, $options );
		$WriteResult = $this->Source->executeBulkWrite ( $namespace, $Bulk );
		$this->writeResults [] = $WriteResult;

		if ($DebugService->isActivated ()) {
			$upres = ( int ) $WriteResult->getUpsertedCount () + ( int ) $WriteResult->getInsertedCount () + ( int ) $WriteResult->getModifiedCount ();
			if (! empty ( $WriteResult->getWriteErrors () ))
				$message = print_r ( $WriteResult->getWriteErrors (), true );
			$DebugService->setDebugInfo ( "$timestart `$namespace` (upsertOneIntoSource)", [ 
					'query' => [ 
							'_id' => $_id,
							'values' => $values,
							'options' => $options
					],
					'result' => [ 
							'affected_count' => "$upres",
							'status' => $message ? 'error' : 'ok',
							'message' => $message
					],
					'execution_time' => number_format ( microtime ( true ) - $timestart, 5 ) . 's',
					'@server' => $this->getServer ()
			], 'MONGOCOLLECTIONS' );
		}

		return $this;
	}

	/**
	 * Update or insert multiple documents directly into MongoDb from the given source to this collection.
	 * Attention: _id can be type of MongoDB\BSON\ObjectId, int, string or any supported type by MongoDB and the PHP MongoDB library
	 *
	 * @param array $documents
	 *        	Each rows MUST contain the "_id" key/value required to identify a document into the MongoDB collection
	 * @param bool $forceReplace
	 *        	If true, the upsert is forced and the previous document is ENTIRELY replaced if it already exists (i.e. if new document miss previous fields, they are lost)
	 * @throws \Exception
	 * @return self
	 */
	public function upsertManyIntoSource(array $documents, bool $forceReplace = false): self {
		$message = null;
		if (! $this->Source instanceof Manager) {
			$message = dgettext ( 'core', 'You must set source to this MongoCollection to be able to upsert many data into MongoDb.' );
			$this->log ( $message );
			throw new \Exception ( $message );
		}

		global $Application;
		$DebugService = $Application->getSuperServices ()->getDebugService ();
		$timestart = microtime ( true );
		$namespace = "$this->dbName.$this->collectionName";
		$debugindex = "$timestart `$namespace` (upsertManyIntoSource)";

		if (! ($nbDocs = count ( $documents ))) {
			if ($DebugService->isActivated ())
				$DebugService->setDebugInfo ( $debugindex, [ 
						'result' => [ 
								'message' => 'Nothing to upsert (empty documents)'
						],
						'execution_time' => number_format ( microtime ( true ) - $timestart, 5 ) . 's',
						'@server' => $this->getServer ()
				], 'MONGOCOLLECTIONS' );
			return $this;
		}

		$upres = 0;
		$debugValues = [ ];
		$Bulk = new BulkWrite ();
		foreach ( $documents as $values ) {
			if (isset ( $values ['_id'] )) {
				$_id = $values ['_id'];
				$performUpsert = $forceReplace ? $forceReplace : ! $this->_idExistsIntoSource ( $_id );
				if (! $performUpsert) {
					unset ( $values ['_id'] );
					$values = [ 
							'$set' => $values
					];
				}
				// Set options
				$options = [ 
						'upsert' => $performUpsert, // Upsert must be true only if you want to insert or replace a document. Set "false" for a single update.
						'multi' => false
				];
				// Add bulk operation
				$Bulk->update ( [ 
						'_id' => $_id
				], $values, $options );
				// Add debug values
				if ($DebugService->isActivated ())
					$debugValues [] = [ 
							'_id' => $_id,
							'values' => $values,
							'options' => $options
					];
			}
		}
		if ($nbExpected = $Bulk->count ()) {
			$WriteResult = $this->Source->executeBulkWrite ( $namespace, $Bulk );
			$this->writeResults [] = $WriteResult;
			$upres = ( int ) $WriteResult->getUpsertedCount () + ( int ) $WriteResult->getInsertedCount () + ( int ) $WriteResult->getModifiedCount ();
			if ($this->modified && ($upres != $nbDocs)) {
				$message = sprintf ( dgettext ( 'core', '%1s elements written (expected %2s) from collection %3s' ), $upres, $nbDocs, $this->collectionName );
				if ($DebugService->isActivated () && ! empty ( $WriteResult->getWriteErrors () ))
					$message .= "\n" . print_r ( $WriteResult->getWriteErrors (), true );
				$this->log ( $message );
			}
		} else {
			$message = sprintf ( dgettext ( 'core', 'Each rows from parameter $documents must contain an "_id".' ), $nbDocs, $nbExpected );
			$this->log ( $message );
			if ($DebugService->isActivated ())
				throw new \Exception ( $message );
		}

		if ($DebugService->isActivated ())
			$DebugService->setDebugInfo ( $debugindex, [ 
					'query' => $debugValues,
					'result' => [ 
							'affected_count' => "$upres",
							'status' => $message ? 'error' : 'ok',
							'message' => $message
					],
					'execution_time' => number_format ( microtime ( true ) - $timestart, 5 ) . 's',
					'@server' => $this->getServer ()
			], 'MONGOCOLLECTIONS' );

		return $this;
	}

	/**
	 * Check if an _id exists into the source collection
	 *
	 * @param string|int|ObjectId $_id
	 *        	_id can be type of MongoDB\BSON\ObjectId, int, string or any supported type by MongoDB and the PHP MongoDB library
	 * @return bool
	 */
	public function _idExistsIntoSource($_id): bool {
		$Command = new Command ( [ 
				'count' => $this->collectionName,
				'query' => [ 
						'_id' => $_id
				]
		] );
		return ($result = $this->Source->executeCommand ( $this->dbName, $Command )->toArray () [0]) ? ( bool ) ( int ) $result->n : false;
	}
}