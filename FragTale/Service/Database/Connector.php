<?php

namespace FragTale\Service\Database;

use FragTale\Implement\AbstractService;
use FragTale\DataCollection;
use FragTale\Constant\Setup\Database\Driver;
use MongoDB\Driver\Manager;
use MongoDB\Driver\Exception\ConnectionException;
use FragTale\Service\Project\CliPurpose;

/**
 *
 * @author Fabrice Dant <fragtale.development@gmail.com>
 * @copyright 2024 FragTale 2 - Fabrice Dant
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-fr.txt CeCILL Licence 2.1 (French version)
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-en.txt CeCILL Licence 2.1 (English version)
 *         
 */
class Connector extends AbstractService {

	/**
	 * Collection containing all configured PDO connectors from Db settings
	 *
	 * @var array
	 */
	private array $PdoCollection = [ ];

	/**
	 * Collection containing all configured Mongo managers from Db settings
	 *
	 * @var array
	 */
	private array $MongoManagerCollection = [ ];

	/**
	 *
	 * @var DataCollection
	 */
	private DataCollection $ProjectDatabaseSettings;

	/**
	 *
	 * @var DataCollection
	 */
	private DataCollection $CliPurposeProjectDatabaseSettings;

	/**
	 */
	function __construct() {
		$this->ProjectDatabaseSettings = $this->getSuperServices ()->getProjectService ()->getDatabaseSettings ();
		$this->CliPurposeProjectDatabaseSettings = IS_CLI ? $this->getService ( CliPurpose::class )->getDatabaseSettings () : new DataCollection ();
		$this->registerSingleInstance ();
	}

	/**
	 *
	 * @param string $connectorId
	 * @return DataCollection|NULL If exists, keys must at least contain: "driver", "host", "port", "database", "user", "password" as set in your project.json file
	 */
	public function getConnectorConfiguration(string $connectorId): ?DataCollection {
		return IS_CLI ? $this->CliPurposeProjectDatabaseSettings->findByKey ( $connectorId ) : $this->ProjectDatabaseSettings->findByKey ( $connectorId );
	}

	/**
	 * Returns an instance of \PDO (or null if error or not exists)
	 *
	 * @param string $connectorId
	 *        	The connector ID as declared in your database settings (project.json file), listed in current environment settings
	 * @return \PDO|NULL
	 */
	public function getPDO(string $connectorId): ?\PDO {
		if (! $connectorId) // Get default
			if (! ($connectorId = $this->getSuperServices ()->getProjectService ()->getDefaultSqlConnectorID ()) && IS_CLI)
				$connectorId = $this->getService ( CliPurpose::class )->getDefaultSqlConnectorID ();
		if (! array_key_exists ( $connectorId, $this->PdoCollection )) {
			// Get conf
			$DbConf = $this->getConnectorConfiguration ( $connectorId );
			// if (! $DbConf instanceof DataCollection)
			// $DbConf = $this->CliPurposeProjectDatabaseSettings->findByKey ( $connectorId );
			if ($DbConf instanceof DataCollection) {
				$connectionString = $this->buildConnectionString ( $DbConf );
				$user = $DbConf->findByKey ( 'user' );
				$pwd = $DbConf->findByKey ( 'password' );
				try {
					$this->PdoCollection [$connectorId] = new \PDO ( $connectionString, $user, $pwd );
				} catch ( \PDOException $Exc ) {
					$this->getSuperServices ()->getErrorHandlerService ()->catchThrowable ( $Exc );
				}
			} else
				$this->PdoCollection [$connectorId] = null;
		}
		return $this->PdoCollection [$connectorId];
	}

	/**
	 * Returns an instance of \MongoDB\Driver\Manager (or null if error or not exists).
	 * Mind that this type of auto connection does not allow to connect a MongoDB server with
	 * advanced options, such as SSL connections or connection to replicated databases.
	 *
	 * @param string $connectorId
	 *        	The connector ID as declared in your database settings (project.json file), listed in current environment settings
	 * @return Manager
	 */
	public function getMongoManager(string $connectorId): ?Manager {
		if (! $connectorId) // Get default
			$connectorId = $this->getSuperServices ()->getProjectService ()->getDefaultMongoConnectorID ();

		if (! array_key_exists ( $connectorId, $this->MongoManagerCollection )) {
			// Get conf
			if (($DbConf = $this->getConnectorConfiguration ( $connectorId )) && $DbConf instanceof DataCollection /*|| (($DbConf = $this->CliPurposeProjectDatabaseSettings->findByKey ( $connectorId )) && $DbConf instanceof DataCollection)*/) {
				$connectionString = $this->buildConnectionString ( $DbConf );
				try {
					$this->MongoManagerCollection [$connectorId] = new Manager ( $connectionString );
				} catch ( ConnectionException $Exc ) {
					$this->getSuperServices ()->getErrorHandlerService ()->catchThrowable ( $Exc );
				}
			} else
				$this->MongoManagerCollection [$connectorId] = null;
		}
		return $this->MongoManagerCollection [$connectorId];
	}

	/**
	 * Returns instance of PDO connected to your default configured SQL database
	 *
	 * @return \PDO|NULL
	 */
	public function getDefaultPDO(): ?\PDO {
		if (! ($defaultConnectorId = $this->getSuperServices ()->getProjectService ()->getDefaultSqlConnectorID ()) && IS_CLI)
			$defaultConnectorId = $this->getService ( CliPurpose::class )->getDefaultSqlConnectorID ();
		return $defaultConnectorId ? $this->getPDO ( $defaultConnectorId ) : null;
	}

	/**
	 * Returns instance of PDO connected to your default configured SQL database
	 *
	 * @return \PDO|NULL
	 */
	public function getDefaultMongoManager(): ?Manager {
		if (! ($defaultConnectorId = $this->getSuperServices ()->getProjectService ()->getDefaultMongoConnectorID ()) && IS_CLI)
			$defaultConnectorId = $this->getService ( CliPurpose::class )->getDefaultMongoConnectorID ();
		return $defaultConnectorId ? $this->getMongoManager ( $defaultConnectorId ) : null;
	}

	/**
	 * Depending on the driver, this will build the appropriate connection string for PDO or for Mongo\Driver\Manager
	 *
	 * @param DataCollection $DbSettings
	 *        	Must contains keys: driver, host
	 * @throws \Exception Occures when DataCollection does not contains driver nor host informations
	 * @return string|NULL
	 */
	public function buildConnectionString(DataCollection $DbSettings): ?string {
		if (! ($driver = $DbSettings->findByKey ( 'driver' )))
			throw new \LogicException ( sprintf ( dgettext ( 'core', 'Missing required parameter "%s" in collection passed as argument' ), 'driver' ) );
		if (! ($host = $DbSettings->findByKey ( 'host' )))
			throw new \LogicException ( sprintf ( dgettext ( 'core', 'Missing required parameter "%s" in collection passed as argument' ), 'host' ) );
		$database = ( string ) $DbSettings->findByKey ( 'database' );
		$port = ( string ) $DbSettings->findByKey ( 'port' );
		$charset = ( string ) $DbSettings->findByKey ( 'charset' );
		if (! $driver || ! $host)
			return null;
		switch (strtolower ( trim ( $driver ) )) {
			case Driver::MYSQL :
				// Also working for MARIADB
				if (! $port)
					$port = 3306;
				if (! $charset)
					$charset = 'utf8';
				return "$driver:host=$host;port=$port;dbname=$database;charset=$charset";
			case Driver::POSTGRESQL :
				if (! $port)
					$port = 5432;
				if (! $charset)
					$charset = 'UTF8';
				else
					$charset = strtoupper ( $charset );
				return "$driver:host=$host;port=$port;dbname=$database;options='--client_encoding=$charset'";
			case Driver::MSSQL :
				if (! $port)
					$port = 1433;
				// Charset must be set after PDO instanciation:
				// $Pdo->setAttribute(PDO::SQLSRV_ATTR_ENCODING, PDO::SQLSRV_ENCODING_UTF8);
				// Also, mind that you might need to specify the server instance. For example: $host = 'localhost\\SQLEXPRESS'
				return "$driver:Server=$host,$port;Database=$database";
			case Driver::ORACLE :
				if (! $port)
					$port = 1521;
				if (! $charset)
					$charset = 'UTF8';
				else
					$charset = strtoupper ( $charset );
				$tns = "( DESCRIPTION = ( ADDRESS = (PROTOCOL = TCP)(HOST = $host)(PORT = $port) ) ( CONNECT_DATA = (SERVICE_NAME = $database) ) )";
				return "$driver:dbname=$tns;charset=$charset";
			case Driver::MONGO :
				if (! $port)
					$port = 27017;
				// MongoDB is always running with UTF8 encoding, no need to specify charset
				if (($user = $DbSettings->findByKey ( 'user' )) && ($pwd = urlencode ( $DbSettings->findByKey ( 'password' ) )))
					return "$driver://$user:$pwd@$host:$port/$database";
				return "$driver://$host:$port/$database";
		}
		return null;
	}
}