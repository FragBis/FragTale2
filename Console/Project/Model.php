<?php

namespace Console\Project;

use Console\Project;
use FragTale\Constant\Setup\CustomProjectPattern;
use FragTale\Service\Cli;
use FragTale\DataCollection;
use FragTale\Constant\Setup\Database\Driver;
use FragTale\Constant\Setup\CorePath;
use FragTale\Service\Filesystem;

class Model extends Project {

	/**
	 * Executed in child controllers
	 */
	function __construct() {
		parent::__construct ();
		$this->setOrPromptProjectName ();
	}

	/**
	 *
	 * {@inheritdoc}
	 * @see \Console\Project::executeOnTop()
	 */
	protected function executeOnTop(): void {
		$this->CliService->printInColor ( sprintf ( dgettext ( 'core', 'Model mapping for project "%s"' ), $this->getProjectName () ), Cli::COLOR_YELLOW )
			->printInColor ( dgettext ( 'core', '**********************' ), Cli::COLOR_LCYAN )
			->printInColor ( dgettext ( 'core', 'CLI option:' ), Cli::COLOR_LCYAN )
			->print ( '	' . dgettext ( 'core', '· "--project": The project name (if not passed, application will prompt you to select an existing project)' ) )
			->print ( '' )
			->printInColor ( dgettext ( 'core', 'This controller automatically maps database tables to build all classes and entities that simply correspond to the relational database structure' ), Cli::COLOR_LCYAN )
			->printInColor ( dgettext ( 'core', '**********************' ), Cli::COLOR_LCYAN );
	}

	/**
	 * Instructions executed only if application is launched via CLI
	 */
	protected function executeOnConsole(): void {
		$FsService = $this->getSuperServices ()->getFilesystemService ();

		# Prompt model
		if (! ($selectedModel = $this->promptModel ()))
			return;

		# create or remap
		// Get model's folder
		$modelsFolder = $this->getModelFolder ();
		// Define namespace
		$modelNamespace = $this->getModelNamespace ( $selectedModel );

		// Get project settings
		if (! $this->getProjectAppConfig () || ! $this->getProjectAppConfig ()->findByKey ( 'databases' ))
			$this->setProjectAppConfig ( true );
		$DatabasesSettings = $this->getProjectAppConfig () ? $this->getProjectAppConfig ()->findByKey ( 'databases' ) : null;
		if (! $DatabasesSettings instanceof DataCollection || ! $DatabasesSettings->count ()) {
			$this->CliService->printError ( dgettext ( 'core', 'You have not setup yet your database credentials. This is a pre-requisite.' ) );
			return;
		}

		$SqlDbSettings = new DataCollection ();
		foreach ( $DatabasesSettings as $connectorId => $Credentials ) {
			if ($Credentials instanceof DataCollection && $Credentials->findByKey ( 'driver' ) !== Driver::MONGO) {
				$SqlDbSettings->upsert ( $connectorId, $Credentials );
			}
		}
		if (! $SqlDbSettings->count ()) {
			$this->CliService->printError ( dgettext ( 'core', 'Your database configurations does not contain any SQL type connector (mysql, psql...).' ) );
			return;
		}

		$ModelsSettings = $this->getProjectAppConfig ()->findByKey ( 'models' );
		if (! $ModelsSettings instanceof DataCollection)
			$ModelsSettings = new DataCollection ();

		// Define which database to bind to the model
		$defaultPosition = ( int ) $SqlDbSettings->position ( $ModelsSettings->findByKey ( $modelNamespace ) ) + 1;
		$modelConnectorID = md5 ( rand () );
		while ( $SqlDbSettings->position ( $modelConnectorID ) === null ) {
			$modelConnectorID = $this->promptToFindElementInCollection ( dgettext ( 'core', 'Choose connectorId in the list below' ), $SqlDbSettings, $defaultPosition, true );
		}
		$this->CliService->print ( $modelConnectorID );

		// Confirm
		if (! $this->getSuperServices ()->getLocalizeService ()->meansYes ( $this->CliService->prompt ( sprintf ( dgettext ( 'core', 'Confirm model mapping "%1s" from database "%2s": [yN]' ), $selectedModel, $modelConnectorID ), dgettext ( 'core', 'n {means no}' ) ) )) {
			$this->CliService->printWarning ( dgettext ( 'core', 'Process interrupted' ) );
			return;
		}

		// Set connector ID to model name
		if ($modelConnectorID != $ModelsSettings->findByKey ( $modelNamespace )) {
			$ModelsSettings->upsert ( $modelNamespace, $modelConnectorID );
			$this->getProjectAppConfig ()->upsert ( 'models', $ModelsSettings );
		}

		$this->CliService->printInColor ( dgettext ( 'core', 'OK, mapping database...' ), Cli::COLOR_GREEN );

		$SelectedDbSettings = $DatabasesSettings->findByKey ( $modelConnectorID );
		if (! ($dbName = $SelectedDbSettings->findByKey ( 'database' ))) {
			$this->CliService->printError ( dgettext ( 'core', 'Parameter "database" (name) is required in selected configuration. You have to set this value. Please check your configuration file and mention a dataabse name.' ) );
			return;
		}
		$connectionString = $this->getSuperServices ()->getDatabaseConnectorService ()->buildConnectionString ( $SelectedDbSettings );
		$PDO = new \PDO ( $connectionString, $SelectedDbSettings->findByKey ( 'user' ), $SelectedDbSettings->findByKey ( 'password' ) );
		// Retrieve information schema
		$schema = [ ];
		switch ($SelectedDbSettings->findByKey ( 'driver' )) {
			case Driver::MYSQL :
				$schema = $this->retrieveMySqlInformationSchema ( $PDO, $dbName );
				break;
			case Driver::POSTGRESQL :
				// TODO: other driver (at least Postgresql)
				break;
		}

		if (empty ( $schema )) {
			$this->getSuperServices ()->getCliService ()->printError ( dgettext ( 'core', 'No information retrieved' ) );
		} else {
			$modelDir = "$modelsFolder/$selectedModel";
			if ($FsService->createDir ( $modelDir )) {
				foreach ( $schema as $tableName => $tableStrucuture ) {
					if (empty ( $tableStrucuture ['columns'] )) {
						$this->getSuperServices ()->getCliService ()->printError ( sprintf ( dgettext ( 'core', 'No columns found for table "%s"' ), $tableName ) );
						continue;
					}
					$columns = array_keys ( $tableStrucuture ['columns'] );

					$entityName = $this->getSuperServices ()->getRouteService ()->convertUriToNamespace ( $tableName );
					$entityDir = "$modelDir/$entityName";
					if ($FsService->createDir ( $entityDir )) {
						$entityNamespace = "$modelNamespace\\$entityName";
						$modelEPatternFile = sprintf ( CorePath::PATTERN_MODEL_E, $this->getProjectName () );
						$modelMPatternFile = sprintf ( CorePath::PATTERN_MODEL_M, $this->getProjectName () );
						$modelTPatternFile = sprintf ( CorePath::PATTERN_MODEL_T, $this->getProjectName () );
						// Export JSON structure
						$jsonStructFile = "$entityDir/$tableName.json";
						$this->CliService->print ( sprintf ( dgettext ( 'core', 'Exporting table "%1s" structure into "%2s"' ), $tableName, $jsonStructFile ) );
						(new DataCollection ( $tableStrucuture ))->exportToJsonFile ( $jsonStructFile, true );

						// Creating constant class containg colum names
						$this->CliService->print ( sprintf ( dgettext ( 'core', 'Creating constant class containg colum names into "%s"' ), "T_$entityName" ) );
						$fields = [ ];
						foreach ( $columns as $col ) {
							if (! is_string ( $col ))
								continue;
							$colProps = $tableStrucuture ['columns'] [$col];
							$dataType = $colProps ['type'];
							if ($length = $colProps ['length']) {
								if (is_array ( $length ))
									$length = implode ( ',', $length );
								$dataType .= "($length)";
							}
							$fields [] = '	/**';
							$fields [] = "	 * @datatype $dataType";
							if (! empty ( $colProps ['index'] ))
								$fields [] = '	 * @index ' . $colProps ['index'];
							$fields [] = '	 * @nullable ' . (! empty ( $colProps ['nullable'] ) ? 'true' : 'false');
							if ($colProps ['default'] === null && ! empty ( $colProps ['nullable'] ))
								$fields [] = '	 * @default NULL';
							elseif ($colProps ['default'] !== null)
								$fields [] = '	 * @default ' . $colProps ['default'];
							if ($colProps ['comment'])
								$fields [] = '	 * @desc ' . $colProps ['comment'];
							$fields [] = '	 */';
							$fields [] = '	const ' . strtoupper ( $col ) . " = '$col';";
						}
						$fields = implode ( "\n", $fields );
						$classTContent = str_replace ( [ 
								'%namespace%',
								'%tableName%',
								'%entityName%',
								'%fields%'
						], [ 
								$entityNamespace,
								$tableName,
								$entityName,
								$fields
						], file_get_contents ( $modelTPatternFile ) );
						$tFile = "$entityDir/T_$entityName.php";
						if (! $FsService->createFile ( $tFile, $classTContent, Filesystem::FILE_OVERWRITE_FORCE )) {
							$this->CliService->printWarning ( dgettext ( 'core', 'Process interrupted' ) );
							return;
						}

						// Creating Super Abstract Class
						$this->CliService->print ( sprintf ( dgettext ( 'core', 'Creating model super abstract class "%s"' ), "M_$entityName" ) );
						$classMContent = str_replace ( [ 
								'%namespace%',
								'%entityName%',
								'%tableName%'
						], [ 
								$entityNamespace,
								$entityName,
								$tableName
						], file_get_contents ( $modelMPatternFile ) );
						$mFile = "$entityDir/M_$entityName.php";
						if (! $FsService->createFile ( $mFile, $classMContent, Filesystem::FILE_OVERWRITE_FORCE )) {
							$this->CliService->printWarning ( dgettext ( 'core', 'Process interrupted' ) );
							return;
						}

						// Creating Entity Class
						$eFile = "$entityDir/E_$entityName.php";
						$classEContent = str_replace ( [ 
								'%namespace%',
								'%entityName%',
								'%tableName%'
						], [ 
								$entityNamespace,
								$entityName,
								$tableName
						], file_get_contents ( $modelEPatternFile ) );
						if (! $FsService->createFile ( $eFile, $classEContent, Filesystem::FILE_OVERWRITE_KEEP )) {
							$this->CliService->printWarning ( dgettext ( 'core', 'Process interrupted' ) );
							return;
						}
					}
				}
			}
		}
	}

	/**
	 *
	 * @return string
	 */
	protected function getModelFolder(): ?string {
		$FsService = $this->getSuperServices ()->getFilesystemService ();
		// Check models dir exists
		$modelsFolder = sprintf ( CustomProjectPattern::MODEL_DIR, $this->getProjectName () );
		if (! is_dir ( $modelsFolder )) {
			if (! $FsService->createDir ( $modelsFolder ))
				return null;
		}
		$modelsFolder .= "/Sql";
		if (! is_dir ( $modelsFolder )) {
			if (! $FsService->createDir ( $modelsFolder ))
				return null;
		}
		return $modelsFolder;
	}
	/**
	 *
	 * @param string $selectedModel
	 * @return string
	 */
	protected function getModelNamespace(string $selectedModel): string {
		return sprintf ( CustomProjectPattern::SQL_MODEL_NAMESPACE, $this->getProjectName () ) . '\\' . $selectedModel;
	}
	/**
	 *
	 * @return array
	 */
	protected function getModels(): DataCollection {
		$models = [ ];
		foreach ( scandir ( $this->getModelFolder () ) as $dir ) {
			if (! in_array ( $dir, [ 
					'.',
					'..'
			] ))
				$models [] = $dir;
		}
		return new DataCollection ( $models );
	}
	/**
	 *
	 * @param bool $toCreateNew
	 * @return string
	 */
	protected function promptModel(bool $toCreateNew = false): string {
		$selectedModel = '';
		$Models = $this->getModels ();
		if ($Models->count ()) {
			$message = $toCreateNew ? dgettext ( 'core', 'Please, select one of these models to remap it or leave empty to create new model:' ) : dgettext ( 'core', 'Please, select one of these models:' );
			$selectedModel = $this->promptToFindElementInCollection ( $message, $Models );
			if ($selectedModel)
				$this->CliService->print ( $selectedModel );
		} else
			$toCreateNew = true;

		if ($toCreateNew && ! $selectedModel) {
			$input = $this->CliService->prompt ( dgettext ( 'core', 'Type your new model name:' ) );
			if ($input) {
				if ($selectedModel = $this->getSuperServices ()->getRouteService ()->convertUriToNamespace ( $input ))
					$this->CliService->printInColor ( $selectedModel, Cli::COLOR_LCYAN );
				else
					$this->CliService->printError ( dgettext ( 'core', 'Failed to parse your model name. Special chars are not allowed.' ) );
			}
		}
		return $selectedModel;
	}

	/**
	 *
	 * @param \PDO $PDO
	 * @param string $dbName
	 * @return array
	 */
	protected function retrieveMySqlInformationSchema(\PDO $PDO, string $dbName): array {
		$schema = $info = [ ];
		try {
			$Statement = $PDO->prepare ( "SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? ORDER BY TABLE_NAME, ORDINAL_POSITION" );
			$Statement->execute ( [ 
					$dbName
			] );
			$info = $Statement->fetchAll ( \PDO::FETCH_ASSOC );
		} catch ( \PDOException $Exc ) {
			$this->getSuperServices ()->getErrorHandlerService ()->catchThrowable ( $Exc );
			return [ ];
		}
		if (! empty ( $info )) {
			foreach ( $info as $row ) {
				// all keys to upper case
				foreach ( $row as $key => $value )
					$row [strtoupper ( ( string ) $key )] = $value;

				switch (strtoupper ( ( string ) $row ['COLUMN_KEY'] )) {
					case 'PRI' :
						$keyIndex = 'primary key';
						if (! empty ( $row ['EXTRA'] ))
							$keyIndex .= ', ' . $row ['EXTRA'];
						break;
					case 'UNI' :
						$keyIndex = 'unique';
						break;
					default :
						$keyIndex = null;
				}

				$type = strtolower ( ( string ) $row ['DATA_TYPE'] );
				// Special case for decimal length
				$length = ! empty ( $row ['CHARACTER_MAXIMUM_LENGTH'] ) ? $row ['CHARACTER_MAXIMUM_LENGTH'] : $row ['NUMERIC_PRECISION'];
				if (in_array ( $type, [ 
						'decimal',
						'double',
						'float'
				] ))
					$length = [ 
							( int ) $row ['NUMERIC_PRECISION'],
							( int ) $row ['NUMERIC_SCALE']
					];

				$isNullable = $this->getSuperServices ()->getLocalizeService ()->meansYes ( $row ['IS_NULLABLE'] );
				// Default must be set the same with mariadb or mysql
				$default = null;
				if ($row ['COLUMN_DEFAULT'] !== null) {
					switch (strtolower ( $row ['COLUMN_DEFAULT'] )) {
						case 'null' :
							break;
						case 'current_timestamp' :
						case 'current_timestamp()' :
							$default = 'CURRENT_TIMESTAMP';
							break;
						default :
							$default = $row ['COLUMN_DEFAULT'];
					}
				}

				$schema [$row ['TABLE_NAME']] ['columns'] [$row ['COLUMN_NAME']] = [ 
						'index' => $keyIndex,
						'type' => $type,
						'length' => $length,
						'nullable' => $isNullable,
						'default' => $default,
						'comment' => $row ['COLUMN_COMMENT']
				];
				if (! empty ( $row ['EXTRA'] ) && stripos ( $row ['EXTRA'], 'auto_increment' ) !== false)
					$schema [$row ['TABLE_NAME']] ['auto_increment'] = $row ['COLUMN_NAME'];
				if (strtoupper ( ( string ) $row ['COLUMN_KEY'] ) === 'PRI')
					$schema [$row ['TABLE_NAME']] ['primary_key'] [] = $row ['COLUMN_NAME'];
			}

			// Get foreign keys & unique constraints
			// Note that this won't get referenced keys from foreign database.
			// All handled references are in same database name.
			$fkConstraints = [ ];
			$query = <<<SQL
				SELECT
					K.CONSTRAINT_NAME,
					K.TABLE_NAME,
					K.COLUMN_NAME,
					K.REFERENCED_TABLE_NAME,
					K.REFERENCED_COLUMN_NAME
				FROM information_schema.TABLE_CONSTRAINTS C
				INNER JOIN information_schema.KEY_COLUMN_USAGE K
					ON K.TABLE_SCHEMA = C.TABLE_SCHEMA
					AND K.TABLE_NAME = C.TABLE_NAME
					AND K.CONSTRAINT_NAME = C.CONSTRAINT_NAME
				WHERE C.TABLE_SCHEMA = :dbName
					AND K.REFERENCED_TABLE_SCHEMA = :dbName
					AND C.CONSTRAINT_TYPE = 'FOREIGN KEY'
				ORDER BY K.TABLE_NAME, K.CONSTRAINT_NAME, K.ORDINAL_POSITION
			SQL;
			try {
				$Statement = $PDO->prepare ( $query );
				$Statement->execute ( [ 
						'dbName' => $dbName
				] );
				$fkConstraints = $Statement->fetchAll ( \PDO::FETCH_ASSOC );
			} catch ( \PDOException $Exc ) {
				$this->getSuperServices ()->getErrorHandlerService ()->catchThrowable ( $Exc );
				return [ ];
			}
			if (empty ( $fkConstraints ))
				$this->getSuperServices ()->getCliService ()->printWarning ( dgettext ( 'core', 'Your database does not contain any foreign keys, that should not.' ) );
			else {
				foreach ( $fkConstraints as $row ) {
					$schema [$row ['TABLE_NAME']] ['foreign_keys'] [$row ['CONSTRAINT_NAME']] ['referenced_table'] = $row ['REFERENCED_TABLE_NAME'];
					$schema [$row ['TABLE_NAME']] ['foreign_keys'] [$row ['CONSTRAINT_NAME']] ['referenced_keys'] [$row ['COLUMN_NAME']] = $row ['REFERENCED_COLUMN_NAME'];
					$newIndex = 'foreign key references ' . $row ['REFERENCED_TABLE_NAME'] . ' (' . $row ['REFERENCED_COLUMN_NAME'] . ')';
					if ($previousIndex = $schema [$row ['TABLE_NAME']] ['columns'] [$row ['COLUMN_NAME']] ['index'])
						$newIndex = implode ( ', ', [ 
								$previousIndex,
								$newIndex
						] );
					$schema [$row ['TABLE_NAME']] ['columns'] [$row ['COLUMN_NAME']] ['index'] = $newIndex;
				}
			}

			// Getting unique keys
			$uniqueConstraints = [ ];
			$query = <<<SQL
				SELECT
					K.CONSTRAINT_NAME,
					K.TABLE_NAME,
					K.COLUMN_NAME
				FROM information_schema.TABLE_CONSTRAINTS C
				INNER JOIN information_schema.KEY_COLUMN_USAGE K
					ON K.TABLE_SCHEMA = C.TABLE_SCHEMA
					AND K.TABLE_NAME = C.TABLE_NAME
					AND K.CONSTRAINT_NAME = C.CONSTRAINT_NAME
				WHERE C.TABLE_SCHEMA = :dbName
					AND C.CONSTRAINT_TYPE = 'UNIQUE'
				ORDER BY K.TABLE_NAME, K.CONSTRAINT_NAME, K.ORDINAL_POSITION
			SQL;
			try {
				$Statement = $PDO->prepare ( $query );
				$Statement->execute ( [ 
						'dbName' => $dbName
				] );
				$uniqueConstraints = $Statement->fetchAll ( \PDO::FETCH_ASSOC );
			} catch ( \PDOException $Exc ) {
				$this->getSuperServices ()->getErrorHandlerService ()->catchThrowable ( $Exc );
			}
			if (empty ( $uniqueConstraints ))
				$this->getSuperServices ()->getCliService ()->printWarning ( dgettext ( 'core', 'No unique keys found.' ) );
			else {
				foreach ( $uniqueConstraints as $row ) {
					$schema [$row ['TABLE_NAME']] ['uniques'] [$row ['CONSTRAINT_NAME']] [] = $row ['COLUMN_NAME'];
				}
			}
		} else
			$this->CliService->printError ( sprintf ( dgettext ( 'core', 'Database "%s" seems to be empty. MySQL does not contain any information for this schema.' ), $dbName ) );
		return $schema;
	}
}