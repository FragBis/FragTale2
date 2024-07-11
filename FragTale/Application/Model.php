<?php

namespace FragTale\Application;

use FragTale\Application;
use FragTale\DataCollection;
use FragTale\Application\Model\T_;
use FragTale\Database\QueryBuilder\QueryBuildInsert;
use FragTale\Database\QueryBuilder\QueryBuildSelect;
use FragTale\Database\QueryBuilder\QueryBuildDelete;
use FragTale\Database\QueryBuilder\QueryBuildUpdate;
use FragTale\Service\Project\CliPurpose;
use Iterator;

/**
 * Entity is instance of Model.
 * All entities extend Model.
 *
 * NOTE: This ORM is focused on simple queries fecthing data collection (to be bound to a form or to display single tables).
 * You might prefer writing full SQL queries for complex syntaxes with multiple conditions and joins.
 * Writing huge SQL queries using directly PDO performs better most of time.
 *
 * NOTE 2: Mind that this class is ITERABLE. A forech will loop the COLLECTION loaded.
 *
 * @author Fabrice Dant <fragtale.development@gmail.com>
 * @copyright 2024 FragTale 2 - Fabrice Dant
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-fr.txt CeCILL Licence 2.1 (French version)
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-en.txt CeCILL Licence 2.1 (English version)
 *         
 */
abstract class Model extends Application implements Iterator {
	/**
	 *
	 * @var string
	 */
	protected string $tableName = 'To be auto filled by model generator via console app';

	/**
	 * This is the collection loaded by any query (select).
	 * Model is iterable on this collection.
	 *
	 * @var DataCollection
	 */
	protected DataCollection $Collection;

	/**
	 * ID of the database connector set into your project settings.
	 *
	 * @var string
	 */
	protected string $connectorId;

	/**
	 * Instance of PDO, using settings given by $connectorID.
	 *
	 * @var \PDO
	 */
	protected ?\PDO $PDO;

	/**
	 * Table structure, defining primary key, field types, foreign keys and unique indexes
	 *
	 * @var array
	 */
	protected array $structure;

	/**
	 * Primary key is an array, because a table can contain a combined primary key on multiple fields.
	 *
	 * @var array
	 */
	protected ?array $primaryKey;

	/**
	 * Log all DB transactions
	 *
	 * @var DataCollection
	 */
	protected DataCollection $TransactionStatutes;

	/**
	 * Log cast errors
	 *
	 * @var array
	 */
	protected array $castErrors;

	/**
	 * Last key in $TransactionStatutes.
	 *
	 * @var string
	 */
	protected ?string $lastTransactionKey;

	/**
	 * List of foreign objects bound as foreign keys.
	 * Foreign keys are declared in json file that maps its table into model's folder.
	 *
	 * @var array
	 */
	protected ?array $foreignEntities;

	/**
	 * If false, this entity has not seeked yet any related (foreign) data from foreign keys declared for this entity.
	 *
	 * @var boolean
	 */
	protected bool $foreignLoaded = false;

	/**
	 * When this entity is automatically loaded via any service (such as FormTagBuilder Service), indicate which column to display preferably.
	 * For example, if this entity is loaded as a foreign entity in a "select" placed in a form, "options" will be filled with "ID" as value and the
	 * "prefered displayed column" as text.
	 *
	 * @var string|null
	 */
	protected ?string $preferedDisplayedColumn = null;

	/**
	 * Maps column => label.
	 * Label is the displayed text shown in a form.
	 * You have to edit this property in each entity declaration.
	 *
	 * @var array
	 */
	protected array $labels = [ ];

	/**
	 *
	 * @var string
	 */
	const STATUS_ERROR = 'error';

	/**
	 *
	 * @var string
	 */
	const STATUS_SUCCESS = 'success';

	/**
	 * Corresponds to a situation where nothing happened.
	 *
	 * @var string
	 */
	const STATUS_NEUTRAL = 'neutral';

	/**
	 * If $connectorId is not passed, it will use the (default) connector ID defined in your configuration file (it is prompted while creating your model).
	 * Most of time, you should not have to pass $connectorId, unless you want it (for example for debug purposes)
	 *
	 * @param string $connectorId
	 *        	ID of the database connector set into your project settings.
	 */
	function __construct(?string $connectorId = null) {
		// Init data collection
		$this->Collection = new DataCollection ();
		$this->PDO = null;

		// Init loggers
		$this->TransactionStatutes = new DataCollection ();
		$this->castErrors = [ ];
		$this->lastTransactionKey = null;

		$class = get_class ( $this );
		$namespace = substr ( $class, 0, strrpos ( $class, '\\' ) );
		$model = substr ( $namespace, 0, strrpos ( $namespace, '\\' ) );
		// Instanciate PDO
		// if not connectorId, get the declared one into confs
		$iEnv = 0;
		$settings = [ 
				$this->getSuperServices ()->getProjectService ()->getEnvSettings (),
				$this->getSuperServices ()->getProjectService ()->getSettings ()
		];
		if (IS_CLI) {
			$settings [] = $this->getService ( CliPurpose::class )->getEnvSettings ();
			$settings [] = $this->getService ( CliPurpose::class )->getSettings ();
		}
		while ( ! $connectorId ) {
			if ($iEnv === count ( $settings )) {
				if (! ($connectorId = $this->getSuperServices ()->getProjectService ()->getDefaultSqlConnectorID ()) && IS_CLI)
					$connectorId = $this->getService ( CliPurpose::class )->getDefaultSqlConnectorID ();
				break;
			} else {
				$CurSett = $settings [$iEnv];
				if (($models = $CurSett->findByKey ( 'models' )) && $models instanceof DataCollection) {
					// take the connector ID set to "ENVIRONMENT" model if defined
					$connectorId = $models->findByKey ( $model );
				}
			}
			$iEnv ++;
		}
		$this->setConnectorID ( $connectorId );

		// Loading table structure as object
		$structFile = APP_ROOT . '/' . str_replace ( '\\', '/', $namespace ) . '/' . $this->tableName . '.json';
		if (file_exists ( $structFile ))
			$this->structure = json_decode ( file_get_contents ( $structFile ), true );
		else
			throw new \Exception ( sprintf ( dgettext ( 'core', 'Missing required file "%s". You should remap your database model.' ), $structFile ) );
	}

	/**
	 *
	 * @param DataCollection $InitialData
	 *        	A complete collection containing existing rows in the database
	 * @param DataCollection $NewData
	 *        	Attention, if this collection is empty, that means all previous data must be removed. Be careful passing empty collection.
	 * @param [string] $keys2match
	 *        	Array of strings, the properties' name being compared (commonly, the primary keys)
	 * @param \Closure $closureForUpdate
	 *        	Executed to update rows (contained both in $InitialData and in $NewData). You must pass an closure function having no code into it to prevent default update.
	 *        	If null, the default behavior (update on $keys2match) will be executed.
	 *        	It takes 2 arguments: <b>(DataCollection) $NewRow, (Model) $SelfEntity</b>
	 * @param \Closure $closureForDeletion
	 *        	Executed to delete rows (contained in $InitialData but not in $NewData). You must pass an closure function having no code into it to prevent default deletion.
	 *        	If null, the default behavior (deletion on $keys2match) will be executed.
	 *        	It takes 2 arguments: <b>(DataCollection) $InitialRow, (Model) $SelfEntity</b>
	 * @param \Closure $closureForInsertion
	 *        	Executed to insert new rows (contained in $NewData but not in $InitialData). You must pass an closure function having no code into it to prevent default insertion.
	 *        	If null, the default behavior (insertIntoDB $NewRow) will be executed.
	 *        	It takes 2 arguments: <b>(DataCollection) $NewRow, (Model) $SelfEntity</b>
	 * @return Model New instance of Model (or inherited class), containing all transaction logs specifically for this process.
	 */
	public function bulkCommitDiffsBetweenCollections(DataCollection $InitialData, DataCollection $NewData, array $keys2match, ?\Closure $closureForUpdate = null, ?\Closure $closureForDeletion = null, ?\Closure $closureForInsertion = null): Model {
		foreach ( $keys2match as $key2match )
			if (! $this->isEntityColumn ( $key2match )) {
				$this->getSuperServices ()->getErrorHandlerService ()->catchThrowable ( new \Exception ( dgettext ( 'core', 'Key to match must be a property of the entity used.' ) ) );
				return new static ( $this->getConnectorId () );
			}
		$SelfEntity = new static ( $this->getConnectorId () );
		foreach ( $InitialData as $InitialRow ) {
			if (! ($InitialRow instanceof DataCollection))
				continue;
			// Find existing into new
			$existingMatchedValues = [ ];
			foreach ( $keys2match as $key2match ) {
				if (! in_array ( $key2match, $InitialRow->keys () )) {
					$this->getSuperServices ()->getErrorHandlerService ()->catchThrowable ( new \Exception ( dgettext ( 'core', 'Row of InitialData does not contain one of keys to match passed as argument.' ) ) );
					return new self ();
				}
				$existingMatchedValues [$key2match] = $InitialRow->findByKey ( $key2match );
			}
			$hasError = false;
			if (! $NewData->find ( function ($key, $NewRow) use ($existingMatchedValues, &$hasError) {
				if (! ($NewRow instanceof DataCollection)) {
					$hasError = true;
					return false;
				}
				foreach ( $existingMatchedValues as $key2match => $value2match ) {
					if (! in_array ( $key2match, $NewRow->keys () )) {
						$hasError = true;
						return false;
					}
					if ($NewRow->findByKey ( $key2match ) != $value2match)
						return false;
				}
				return true;
			} )->count ()) {
				if ($hasError) {
					$this->getSuperServices ()->getErrorHandlerService ()->catchThrowable ( new \Exception ( dgettext ( 'core', 'NewData passed must contain rows typed as DataCollection and each row must contain key/value corresponding to the keys 2 match.' ) ) );
					return $this;
				}
				if ($closureForDeletion)
					// You can pass a closure to handle deletion (or to prevent it)
					$closureForDeletion ( $InitialRow, $SelfEntity );
				else
					// Default behavior is to delete the existing row
					$SelfEntity->deleteFromDb ( $existingMatchedValues );
			}
		}
		foreach ( $NewData as $NewRow ) {
			if (! ($NewRow instanceof DataCollection))
				continue;
			$newValues = [ ];
			foreach ( $keys2match as $key2match ) {
				if (! in_array ( $key2match, $NewRow->keys () )) {
					$this->getSuperServices ()->getErrorHandlerService ()->catchThrowable ( new \Exception ( dgettext ( 'core', 'Row of NewData does not contain one of keys to match passed as argument.' ) ) );
					return new self ();
				}
				$newValues [$key2match] = $NewRow->findByKey ( $key2match );
			}
			// Handle updates when keys matched
			if ($InitialData->find ( function ($key, $InitialRow) use ($newValues, &$hasError) {
				foreach ( $newValues as $key2match => $value2match ) {
					if ($InitialRow->findByKey ( $key2match ) != $value2match)
						return false;
				}
				return true;
			} )->count ()) {
				// Update
				if ($closureForUpdate)
					$closureForUpdate ( $NewRow, $SelfEntity );
				else {
					$newRow = $NewRow->getData ( true );
					foreach ( $this->getPrimaryKey () as $pk )
						unset ( $newRow [$pk] );
					$SelfEntity->updateDb ( $newRow, $newValues );
				}
			} elseif ($closureForInsertion)
				// Insert
				$closureForInsertion ( $NewRow, $SelfEntity );
			else
				$SelfEntity->insertIntoDb ( $NewRow );
		}
		return $SelfEntity;
	}

	/**
	 * This will check validity of value for given column.
	 *
	 * @param string $columnName
	 *        	Column name listed in its class constant T_
	 * @param mixed $value
	 * @return array|null An array containing error messages. If array is empty, then all constraints passed. If null, then column does not exist.
	 */
	public function checkField(string $columnName, $value): ?array {
		if (! in_array ( $columnName, $this->getColumns ()->getData () ))
			return null;
		$errors = [ ];
		$colDef = $this->getColumnDefinition ( $columnName );
		// Check type
		if (! empty ( $value )) {
			if ($this->isInteger ( $columnName ) && ! filter_var ( $value, FILTER_VALIDATE_INT ))
				$errors ['type'] = sprintf ( dgettext ( 'core', 'Field "%s" must be an integer.' ), $this->getColumnLabel ( $columnName ) );
			elseif ($this->isFloat ( $columnName ) && ! filter_var ( $value, FILTER_VALIDATE_FLOAT ))
				$errors ['type'] = sprintf ( dgettext ( 'core', 'Field "%s" must be numeric.' ), $this->getColumnLabel ( $columnName ) );
			elseif ($this->isTime ( $columnName )) {
				if (! is_int ( $value )) {
					$hasError = false;
					$expTimes = $expValues = [ ];
					$countValues = 0;
					$isNegative = substr ( $value, 0, 1 ) === '-';
					$time = strtotime ( $value );
					if (is_int ( $time )) {
						if ($expValues = explode ( ':', $value ))
							$countValues = count ( $expValues );
						$expTimes = explode ( ':', date ( 'H:i:s', $time ) );
					}
					if (! $time || ! $expValues || ! $expTimes || ! in_array ( $countValues, [ 
							2,
							3
					] )) {
						$hasError = true;
					} elseif (! $isNegative && ($expTimes [0] != $expValues [0] || $expTimes [1] != $expValues [1])) {
						$hasError = true;
					} elseif ($isNegative) {
						for($i = 0; $i < $countValues; $i ++) {
							if (! is_numeric ( $expValues [$i] )) {
								$hasError = true;
								break;
							}
						}
					}
					if ($hasError)
						$errors ['format'] = sprintf ( dgettext ( 'core', 'Field "%1s" must be an integer corresponding to a time in seconds or a string having format like "00:00:00". Given: "%2s"' ), $this->getColumnLabel ( $columnName ), $value );
				}
			} elseif (($this->isDate ( $columnName ) || $this->isDatetime ( $columnName )) && ! strtotime ( $value ))
				$errors ['type'] = sprintf ( dgettext ( 'core', 'Date "%1s" for column "%2s" is invalid.' ), $value, $this->getColumnLabel ( $columnName ) );
			elseif ($this->isBool ( $columnName ) && ! filter_var ( $value, FILTER_VALIDATE_BOOLEAN ))
				$errors ['type'] = sprintf ( dgettext ( 'core', 'Field "%s" must be a boolean (true/false).' ), $this->getColumnLabel ( $columnName ) );
			elseif (is_object ( $value ) || is_iterable ( $value ))
				$errors ['type'] = sprintf ( dgettext ( 'core', 'Value for field "%1s" must be a scalar. Given: "%2s"' ), $this->getColumnLabel ( $columnName ), gettype ( $value ) );
		}

		// Check length
		if (! empty ( $colDef ['length'] )) {
			$length = is_array ( $colDef ['length'] ) ? ( int ) $colDef ['length'] [0] : ( int ) $colDef ['length'];
			if ($this->isFloat ( $columnName )) {
				$intLength = $length - $colDef ['length'] [1];
				$tmpValues = explode ( '.', str_replace ( ',', '.', ( string ) $value ) );
				if (strlen ( $tmpValues [0] ) > $intLength)
					$errors ['length'] = sprintf ( dgettext ( 'core', 'Field "%1s" must be a decimal number having maximum %2s digit before dot.' ), $this->getColumnLabel ( $columnName ), $intLength );
			} elseif (strlen ( $value ) > $length)
				$errors ['length'] = sprintf ( dgettext ( 'core', 'Field "%1s" must contain maximum %2s signs.' ), $this->getColumnLabel ( $columnName ), $length );
		}

		// Check nullable
		if (array_key_exists ( 'nullable', $colDef ) && $colDef ['nullable'] == false && $value === null)
			$errors ['nullable'] = sprintf ( dgettext ( 'core', 'Field "%s" must not be empty.' ), $this->getColumnLabel ( $columnName ) );
		return $errors;
	}

	/**
	 * Cast a column value to the proper type.
	 * It is strongly recommended to run function "getValidationErrors" or "checkField" before casting values.
	 * Cast is used during an insert or an update.
	 *
	 * @param string $columnName
	 *        	Column name that belongs to this database table
	 * @param mixed $value
	 *        	Passed by reference: value to be casted
	 * @return bool True on successful casting
	 */
	public function castColumnValue(string $columnName, &$value): bool {
		if ($value === null)
			return ( bool ) $this->isNullable ( $columnName );

		$colDef = $this->getColumnDefinition ( $columnName );
		$colType = isset ( $colDef ['type'] ) ? strtolower ( $colDef ['type'] ) : null;
		if (! $colType) {
			$errMsg = sprintf ( dgettext ( 'core', 'Column "%s" has no type declared in table structure. You should remap your database.' ), $columnName );
			$this->castErrors ['column_' . $columnName . '_type_error'] = $errMsg;
			$this->log ( get_class ( $this ) . ' Error: ' . $errMsg );
			return false;
		}
		$length = isset ( $colDef ['length'] ) ? $colDef ['length'] : null;
		// Cast following type
		if ($this->isBool ( $columnName )) {
			if (! in_array ( $value, [ 
					true,
					false,
					null,
					0,
					1,
					'0',
					'1'
			] )) {
				$this->castErrors [] = sprintf ( dgettext ( 'core', 'Column "%1s" is a boolean. Value passed "%2s" is not valid (should be 0, 1, true or false).' ), $columnName, print_r ( $value, true ) );
				return false;
			}
			// in database, booleans are 0 or 1 but via PDO, you need to set a mysql "bit" type with a boolean value
			$value = ( bool ) $value;
		} elseif ($this->isInteger ( $columnName )) {
			if (! is_numeric ( $value )) {
				$this->castErrors [] = sprintf ( dgettext ( 'core', 'Column "%1s" expected value to be an integer. Value passed: "%2s"' ), $columnName, print_r ( $value, true ) );
				return false;
			}
			if (! is_numeric ( $length ) || strpos ( ( string ) $length, '.' ) !== false || strpos ( ( string ) $length, ',' ) !== false) {
				$errMsg = sprintf ( dgettext ( 'core', 'Property "length" for column "%1s" must be an integer. Value set: %2s' ), $columnName, $length );
				$this->castErrors ['column_' . $columnName . '_length_error'] = $errMsg;
				$this->log ( get_class ( $this ) . ' Error: ' . $errMsg );
				return false;
			}
			if (strlen ( $value ) > $length) {
				$this->castErrors [] = sprintf ( dgettext ( 'core', 'Integer length limit for column "%1s" is %2s. Value passed: %3s (%4s)' ), $columnName, $length, print_r ( $value, true ), strlen ( $value ) );
				return false;
			}
			$value = round ( $value );
		} elseif ($this->isFloat ( $columnName )) {
			if (! is_numeric ( $value )) {
				$this->castErrors [] = sprintf ( dgettext ( 'core', 'Column "%1s" expected a float. Value passed: %2s' ), $columnName, print_r ( $value, true ) );
				return false;
			}
			if ($length) {
				if (! is_array ( $length ) || count ( $length ) !== 2 || ! is_numeric ( $length [0] ) || ! is_numeric ( $length [1] )) {
					$errMsg = sprintf ( dgettext ( 'core', 'Property "length" for column "%s" must be an array of 2 integers corresponding to decimal maximum length. You should remap your database.' ), $columnName );
					$this->castErrors ['column_' . $columnName . '_type_error'] = $errMsg;
					$this->log ( get_class ( $this ) . ' Error: ' . $errMsg );
					return false;
				}
				$expVal = explode ( '.', ( string ) $value );
				if (($length [0] && strlen ( $expVal [0] ) > $length [0]) || ($length [1] && ! empty ( $expVal [1] ) && strlen ( $expVal [1] ) > $length [1])) {
					$this->castErrors [] = sprintf ( dgettext ( 'core', 'Column "%1s" expected decimal(%2s,%3s). Value passed: %4s will be truncated' ), $columnName, $length [0], $length [1], print_r ( $value, true ) );
					// Value will be truncated, do not return false. Continue process.
				}
			}
			$value = ( float ) $value;
		} elseif ($this->isDate ( $columnName ))
			$value = ! empty ( $value ) ? date ( 'Y-m-d', strtotime ( $value ) ) : null;
		elseif ($this->isDatetime ( $columnName ))
			$value = ! empty ( $value ) ? date ( 'Y-m-d H:i:s', strtotime ( $value ) ) : null;
		elseif ($this->isTime ( $columnName )) {
			if (! is_int ( $value ))
				$value = ! empty ( $value ) ? date ( 'H:i:s', strtotime ( $value ) ) : null;
			else
				$value = date ( 'H:i:s', $value );
		} else {
			if ($length) {
				if (! is_numeric ( $length )) {
					$errMsg = sprintf ( dgettext ( 'core', 'Property "length" for column "%1s" must be an integer. Value set: %2s' ), $columnName, $length );
					$this->castErrors ['column_' . $columnName . '_length_error'] = $errMsg;
					$this->log ( get_class ( $this ) . ' Error: ' . $errMsg );
					return false;
				} elseif (strlen ( $value ) > $length) {
					$this->castErrors [] = sprintf ( dgettext ( 'core', 'Column "%1s" must contain maximum %2s signs. Value passed: "%3s" (%4s)' ), $columnName, $length, substr ( $value, 0, 30 ) . '...', strlen ( $value ) );
					return false;
				}
			}
			$value = ( string ) $value;
		}
		return true;
	}

	/**
	 * Get all errors during a column value cast
	 *
	 * @return DataCollection
	 */
	final public function getCastErrors(): DataCollection {
		return new DataCollection ( $this->castErrors );
	}

	/**
	 * Get the result of a query (select).
	 * You must have passed this entity into a QueryBuildSelect constructor and the instance of QueryBuildSelect must have load collection.<br>
	 * <b>IMPORTANT NOTE:</b> This entity is iterable on this collection. "foreach($this as $k=>$v)" is the same as "foreach($this->getCollection() as $k=>$v)"
	 *
	 * @return DataCollection
	 */
	final public function getCollection(): DataCollection {
		return $this->Collection;
	}

	/**
	 * Get a specified table column definition.
	 * This definition is described into model's JSON file ({table_name}.json).
	 * Those JSON files are auto-generated by the framework database mapper, using CLI command: ./fragtale Console/Project/Configure/Model
	 *
	 * @param string $columnName
	 *        	Must be the exact column name of this entity.
	 * @return array|NULL Parsed JSON into array
	 */
	final public function getColumnDefinition(string $columnName): ?array {
		return isset ( $this->getColumnsDefinitions () [$columnName] ) ? $this->getColumnsDefinitions () [$columnName] : null;
	}

	/**
	 * Get the default value defined to a given SQL column.
	 *
	 * @param string $columnName
	 * @return mixed
	 */
	final public function getColumnDefaultValue(string $columnName): mixed {
		$colDefs = $this->getColumnDefinition ( $columnName );
		$defaultValue = isset ( $colDefs ['default'] ) ? $colDefs ['default'] : null;
		if ($this->isBool ( $columnName ))
			$defaultValue = is_numeric ( $defaultValue ) ? ( int ) (( bool ) $defaultValue) : ($defaultValue === "b'1'" ? 1 : 0);
		return $defaultValue;
	}

	/**
	 * Get a column maximum length.
	 *
	 * @param string $columnName
	 *        	Must be the exact column name of this entity.
	 * @return int|NULL
	 */
	final public function getColumnLength(string $columnName): ?int {
		$colDef = $this->getColumnDefinition ( $columnName );
		if ($colDef === null)
			return null;
		return $colDef ['length'];
	}

	/**
	 * Get full list of this entity columns.
	 *
	 * @return DataCollection
	 */
	public function getColumns(): DataCollection {
		return T_::getConstants ();
	}

	/**
	 * Get full definitions for all columns.
	 * These definitions are described into model's JSON file ({table_name}.json).
	 * Those JSON files are auto-generated by the framework database mapper, using CLI command: ./fragtale Console/Project/Configure/Model
	 *
	 * @return array|NULL
	 */
	final public function getColumnsDefinitions(): ?array {
		return isset ( $this->structure ['columns'] ) ? $this->structure ['columns'] : null;
	}

	/**
	 * ID of the database connector set into your project settings (and eventually, passed as argument in the constructor).
	 * Current connector ID used by this entity.
	 *
	 * @return string
	 */
	final public function getConnectorId(): ?string {
		return isset ( $this->connectorId ) ? $this->connectorId : null;
	}

	/**
	 * Get references of given column name if it is a foreign key.
	 * If it's not a foreign key, this function function returns null
	 *
	 * @param string $columnName
	 * @return array|NULL
	 */
	final public function getFKDefinition(string $columnName): ?array {
		if ($foreignKeys = $this->getForeignKeys ())
			foreach ( $foreignKeys as $fkDefinitions ) {
				if (isset ( $fkDefinitions ['referenced_keys'] [$columnName] ))
					return $fkDefinitions;
			}
		return null;
	}

	/**
	 * Returns the displayed label shown in a form for given column name.
	 * It is used in service FormTagBuilder to build html output.
	 * You have to set label values in each entity declaration, into __construct function.
	 * You can override this function to handle special cases.
	 * Entity classes are not overwritten while building model (abtract classes Model and table constants are overwritten).
	 *
	 * @param string $columnName
	 * @return string
	 */
	public function getColumnLabel(string $columnName): string {
		return isset ( $this->labels [$columnName] ) ? $this->labels [$columnName] : $columnName;
	}

	/**
	 * After insert, update or delete: get the last transaction status and message(s).
	 *
	 * @return DataCollection
	 */
	final public function getLastTransactionLog(): DataCollection {
		return $this->TransactionStatutes->findByKey ( $this->lastTransactionKey ) instanceof DataCollection ? $this->TransactionStatutes->findByKey ( $this->lastTransactionKey )->clone () : new DataCollection ();
	}

	/**
	 * Get the last transaction status (error, neutral or success).
	 *
	 * @return string|NULL
	 */
	final public function getLastTransactionStatus(): ?string {
		return $this->getLastTransactionLog ()->findByKey ( 'status' );
	}

	/**
	 * For a given column name declared as a foreign key (of this entity), retrieve related data at specified row index (position of the row in this entity collection).
	 *
	 * @param string $columnName
	 *        	Column name must be a foreign key and an existing column of this entity. Otherwise, function will return null.
	 * @param int $rowIndex
	 *        	Position of the row where the foreign key is. This will give the value of the foreign key and then, seek the ID into the related (foreign) entity collection.
	 * @return DataCollection|NULL
	 */
	final public function getForeignRowFrom(string $columnName, int $rowIndex = 0): ?DataCollection {
		if (($Row = $this->Collection->findAt ( $rowIndex )) && $Row instanceof DataCollection && ($fkDef = $this->getFKDefinition ( $columnName ))) {
			if (! $this->foreignLoaded)
				$this->loadForeignCollection ();
			$matchValues = [ ];
			foreach ( $fkDef ['referenced_keys'] as $curKey => $refKey ) {
				$value = $Row->findByKey ( $curKey );
				if ($value === null)
					return null;
				$matchValues [$refKey] = $value;
			}
			foreach ( $this->getForeignEntityFrom ( $columnName ) as $refData ) {
				if ($refData instanceof DataCollection) {
					$allMatch = true;
					foreach ( $matchValues as $refKey => $value )
						if ($refData->findByKey ( $refKey ) !== $value)
							$allMatch = false;
					if ($allMatch)
						return $refData;
				}
			}
		}
		return null;
	}

	/**
	 * Get the entity (instance of model) related to a given column which is a foreign key of this entity.
	 * The foreign key's entity is then a "foreign entity".
	 *
	 * @param string $columnName
	 *        	The column name being a foreign key
	 * @return Model An instance of an inherited model (e.g. an entity)
	 */
	final public function getForeignEntityFrom(string $columnName): ?Model {
		if (! isset ( $this->foreignEntities ))
			$this->foreignEntities = [ ];

		// Check if column exists for this class
		if (! $this->isEntityColumn ( $columnName )) {
			$this->getSuperServices ()->getErrorHandlerService ()->catchThrowable ( new \Exception ( sprintf ( dgettext ( 'core', 'Column "%1s" does not belong to table "%2s"' ), $columnName, $this->getTableName () ) ) );
			return null;
		}

		// Check if column is a foreign key
		$fkDefinition = $this->getFKDefinition ( $columnName );
		if (! $fkDefinition) {
			$this->getSuperServices ()->getErrorHandlerService ()->catchThrowable ( new \Exception ( sprintf ( dgettext ( 'core', 'Column "%s" is not defined as a foreign key' ), $columnName ) ) );
			return null;
		}

		if (! isset ( $this->foreignEntities [$columnName] ) || ! ($this->foreignEntities [$columnName] instanceof Model)) {
			// Get referenced table name
			$refTableName = $fkDefinition ['referenced_table'];
			$refModName = $this->getSuperServices ()->getRouteService ()->convertUriToNamespace ( $refTableName );
			$refNamespace = trim ( substr ( get_class ( $this ), 0, strrpos ( get_class ( $this ), '\\' ) ), '\\' );
			$refNamespace = trim ( substr ( $refNamespace, 0, strrpos ( $refNamespace, '\\' ) ), '\\' );
			$refNamespace .= '\\' . $refModName;
			$refEntityName = $refNamespace . '\\E_' . $refModName;

			// If foreign key is combined, we must set the same entity for each keys
			$Entity = null;
			foreach ( array_keys ( $fkDefinition ['referenced_keys'] ) as $currentKey ) {
				if (isset ( $this->foreignEntities [$currentKey] ) && $this->foreignEntities [$currentKey] instanceof $refEntityName)
					$Entity = $this->foreignEntities [$currentKey];
			}
			$this->foreignEntities [$columnName] = $Entity instanceof $refEntityName ? $Entity : new $refEntityName ();
			foreach ( array_keys ( $fkDefinition ['referenced_keys'] ) as $currentKey ) {
				if (! isset ( $this->foreignEntities [$currentKey] ) || $this->foreignEntities [$currentKey] !== $this->foreignEntities [$columnName])
					$this->foreignEntities [$currentKey] = $this->foreignEntities [$columnName];
			}
		}
		return $this->foreignEntities [$columnName];
	}

	/**
	 * Get all this entity's columns declared as foreign keys.
	 *
	 * @return array|NULL
	 */
	final public function getForeignKeys(): ?array {
		return isset ( $this->structure ['foreign_keys'] ) ? $this->structure ['foreign_keys'] : null;
	}

	/**
	 * Current instance of PDO used by this entity.
	 *
	 * @return \PDO
	 */
	final public function getPDO(): \PDO {
		return $this->PDO;
	}

	/**
	 * When this entity is automatically loaded via any service (such as FormTagBuilder Service), indicate which column to display preferably.
	 * You must have set "$preferedDisplayedColumn" property.
	 * For example, if this entity is loaded as a foreign entity in a "select" placed in a form, "options" will be filled with "ID" as value and the
	 * "prefered displayed column" as text.
	 *
	 * @return string|NULL The prefered column
	 */
	final public function getPreferedDisplayedColumn(): ?string {
		return $this->preferedDisplayedColumn && $this->isEntityColumn ( $this->preferedDisplayedColumn ) ? $this->preferedDisplayedColumn : null;
	}

	/**
	 * A table can contain a combined primary key on multiple fields.
	 * This function does not return single value, but an array containing one or more values.
	 *
	 * @throws \LogicException
	 * @return array|NULL Returns null if there is no primary key
	 */
	final public function getPrimaryKey(): ?array {
		static $excMsg;
		if (! isset ( $excMsg ))
			$excMsg = dgettext ( 'core', 'Defined primary key "%1s" from entity "%2s" is not listed in columns list' );
		if (! isset ( $this->primaryKey )) {
			if (! isset ( $this->structure ['primary_key'] ))
				$this->primaryKey = null;
			elseif (is_string ( $this->structure ['primary_key'] )) {
				if (! $this->getColumns ()->getElementKey ( $this->structure ['primary_key'] ))
					throw new \LogicException ( sprintf ( $excMsg, $this->structure ['primary_key'], get_class ( $this ) ) );
				$this->primaryKey = [ 
						$this->structure ['primary_key']
				];
			} elseif (is_array ( $this->structure ['primary_key'] )) {
				foreach ( $this->structure ['primary_key'] as $colName ) {
					if (! $this->getColumns ()->getElementKey ( $colName ))
						throw new \LogicException ( sprintf ( $excMsg, $colName, get_class ( $this ) ) );
				}
				$this->primaryKey = $this->structure ['primary_key'];
			} else
				$this->primaryKey = null;
		}
		return $this->primaryKey;
	}

	/**
	 * Retrieves a specific row from the whole data collection, giving row position (first row is at index 0).
	 * Most of time, if you load this entity collection with only one row (generally for a form), you can use "current()" function
	 * exactly the same as this function. But mind that "current()" always returns the row at "current" position (that always increase during a loop
	 * made on this collection).
	 * IMPORTANT: to be sure to always get the first row of a collection, use "$this->getRow(0)" instead of "$this->current()".
	 *
	 * @param int $index
	 *        	By default, 0.
	 * @return DataCollection|NULL By default, returns first row.
	 */
	final public function getRow(int $index = 0): ?DataCollection {
		return $this->Collection->findAt ( $index );
	}

	/**
	 * The database tablename corresponding to this entity.
	 *
	 * @return string
	 */
	final public function getTableName(): string {
		return $this->tableName;
	}

	/**
	 * Returns the total row count <b>into the database</b> (not just into the resulting collection).
	 *
	 * @return int|NULL
	 */
	public function getTotalRowCount(): ?int {
		$count = 0;
		$query = "SELECT COUNT(*) as nbRows FROM $this->tableName";
		try {
			$Statement = $this->getPDO ()->prepare ( $query );
			if ($Statement->execute ()) {
				$row = $Statement->fetch ( \PDO::FETCH_ASSOC );
				if (isset ( $row ['nbRows'] ))
					$count = $row ['nbRows'];
			} else
				$this->log ( print_r ( $Statement->errorInfo (), true ) );
		} catch ( \PDOException $Exc ) {
			$this->getSuperServices ()->getErrorHandlerService ()->catchThrowable ( $Exc );
		}
		return $count;
	}

	/**
	 * Returns a list of errors.
	 * If empty (count = 0), validation is OK.
	 *
	 * @param iterable $row
	 *        	Must be an associative array where keys are column names
	 * @return DataCollection
	 */
	public function getValidationErrors(iterable $row): DataCollection {
		$Errors = new DataCollection ();
		foreach ( $row as $key => $value )
			if ($checkingMsg = $this->checkField ( $key, $value ))
				$Errors->upsert ( $key, $checkingMsg );
		return $Errors;
	}

	/**
	 * Get value from the whole data collection giving column name and optionnally, the row index (by default, 0 is first row).
	 *
	 * @param string $columnName
	 *        	Exact entity's column name.
	 * @param int $rowIndex
	 *        	(optional) Row position in the collection.
	 */
	final public function getValue(string $columnName, int $rowIndex = 0) {
		return $this->getRow ( $rowIndex ) ? $this->getRow ( $rowIndex )->findByKey ( $columnName ) : null;
	}

	/**
	 * Returns errors while updating, inserting or removing data
	 *
	 * @return DataCollection
	 */
	final public function getTransactionErrors(): DataCollection {
		return $this->TransactionStatutes->find ( function ($key, $logs) {
			if ($logs instanceof DataCollection)
				return $logs->findByKey ( 'status' ) === self::STATUS_ERROR;
		} )->clone ();
	}

	/**
	 * Returns successfull DB transactions
	 *
	 * @return DataCollection
	 */
	final public function getTransactionSuccesses(): DataCollection {
		return $this->TransactionStatutes->find ( function ($key, $logs) {
			if ($logs instanceof DataCollection)
				return $logs->findByKey ( 'status' ) === self::STATUS_SUCCESS;
		} )->clone ();
	}

	/**
	 * Returns all DB transaction logs
	 *
	 * @return DataCollection
	 */
	final public function getTransactionStatutes(): DataCollection {
		return $this->TransactionStatutes->clone ();
	}

	/**
	 * Insert a new row into the database table corresponding to this entity.
	 *
	 * @param iterable $Row
	 *        	Can be an array, a DataCollection or any iterable object that contains keys as column names.
	 * @return self
	 */
	public function insertIntoDb(iterable $Row): self {
		return (new QueryBuildInsert ( $this ))->execute ( $Row );
	}

	/**
	 * Get PDO last insert Id from last transaction (only if last transaction was an insert).
	 * Use it just after calling function "Model::insertIntoDb()"
	 *
	 * @see Model::insertIntoDb()
	 * @return mixed
	 */
	final public function getLastTransactionInsertId(): mixed {
		return $this->getLastTransactionLog ()->findByKey ( 'PDO_lastInsertId' );
	}

	/**
	 * Check if column is autoincrement.
	 *
	 * @param string $columnName
	 * @return bool
	 */
	final public function isAutoIncrement(string $columnName): bool {
		return ! empty ( $this->structure ['auto_increment'] ) && $this->structure ['auto_increment'] === $columnName;
	}

	/**
	 * Check if column is a boolean (or a type bit).
	 *
	 * @param string $columnName
	 * @return bool|NULL
	 */
	public function isBool(string $columnName): ?bool {
		$colDef = $this->getColumnDefinition ( $columnName );
		if ($colDef === null)
			return null;
		if (in_array ( strtolower ( $colDef ['type'] ), [ 
				'bit',
				'bool',
				'boolean'
		] ) && ( int ) $colDef ['length'] === 1)
			return true;
		return false;
	}

	/**
	 * Check if column is a date (no time).
	 *
	 * @param string $columnName
	 * @return bool
	 */
	public function isDate(string $columnName): ?bool {
		$colDef = $this->getColumnDefinition ( $columnName );
		if ($colDef === null)
			return null;
		return strtolower ( $colDef ['type'] ) === 'date';
	}

	/**
	 * Check if column is a date AND time.
	 * (types "datetime" or "timestamp")
	 *
	 * @param string $columnName
	 * @return bool
	 */
	public function isDatetime(string $columnName): ?bool {
		$colDef = $this->getColumnDefinition ( $columnName );
		if ($colDef === null)
			return null;
		if (in_array ( strtolower ( $colDef ['type'] ), [ 
				'datetime',
				'timestamp'
		] ))
			return true;
		return false;
	}

	/**
	 * Check if a column belongs to this entity.
	 *
	 * @param string $columnName
	 * @return bool
	 */
	final public function isEntityColumn(string $columnName): bool {
		return ! empty ( $this->getColumns ()->getElementKey ( $columnName ) );
	}

	/**
	 * Check if column is a float (or a decimal or any number else than integer).
	 *
	 * @param string $columnName
	 * @return bool
	 */
	public function isFloat(string $columnName): ?bool {
		$colDef = $this->getColumnDefinition ( $columnName );
		if ($colDef === null)
			return null;
		if (in_array ( strtolower ( $colDef ['type'] ), [ 
				'decimal',
				'numeric',
				'number',
				'float',
				'double'
		] ))
			return true;
		return false;
	}

	/**
	 * It returns null if column name does not belong to this entity.
	 * Otherwise, it returns true or false if it's a foreign key or not (according to the columns' definitions).
	 *
	 * @param string $columnName
	 * @return bool|NULL
	 */
	final public function isForeignKey(string $columnName): ?bool {
		if (! $this->getColumns ()->getElementKey ( $columnName ))
			return null;
		return ! empty ( $this->getFKDefinition ( $columnName ) );
	}

	/**
	 * Check if column is an integer.
	 *
	 * @param string $columnName
	 * @return bool
	 */
	public function isInteger(string $columnName): ?bool {
		$colDef = $this->getColumnDefinition ( $columnName );
		if ($colDef === null)
			return null;
		if (in_array ( strtolower ( $colDef ['type'] ), [ 
				'int',
				'tinyint',
				'smallint',
				'bigint'
		] ))
			return true;
		return false;
	}

	/**
	 * Check if last transaction (update, insert or delete DB) succeeded
	 *
	 * @return bool|NULL
	 */
	final public function isLastTransactionSucceeded(): ?bool {
		if (! $this->lastTransactionKey)
			return null;
		return $this->getLastTransactionStatus () === self::STATUS_SUCCESS;
	}

	/**
	 * Check if column allows null value.
	 *
	 * @param string $columnName
	 * @return bool|null If null, column name does not exist for this entity
	 */
	final public function isNullable(string $columnName): ?bool {
		if (! ($colDef = $this->getColumnDefinition ( $columnName )))
			return null;
		return ! empty ( $colDef ['nullable'] );
	}

	/**
	 * Check if a column is a primary key or is a part of a combined primary key.
	 *
	 * @param string $columnName
	 * @return bool
	 */
	final public function isPrimaryKey(string $columnName): bool {
		if (! $this->getPrimaryKey ())
			return false;
		return in_array ( $columnName, $this->getPrimaryKey () );
	}

	/**
	 * Check if a column is a varchar, a char, a text, a blob.
	 *
	 * @param string $columnName
	 * @return bool
	 */
	public function isString(string $columnName): ?bool {
		$colDef = $this->getColumnDefinition ( $columnName );
		if ($colDef === null)
			return null;
		if (in_array ( strtolower ( $colDef ['type'] ), [ 
				'string',
				'char',
				'varchar',
				'text',
				'blob'
		] ))
			return true;
		return false;
	}

	/**
	 * Check if a column is a type time (not date, not datetime, not timestamp).
	 *
	 * @param string $columnName
	 * @return bool
	 */
	public function isTime(string $columnName): ?bool {
		$colDef = $this->getColumnDefinition ( $columnName );
		if ($colDef === null)
			return null;
		return strtolower ( $colDef ['type'] ) === 'time';
	}

	/**
	 * Querying database following specified criterias.
	 * That loads a DataCollection
	 * You must use basic conditions. Query will return all table columns. You can't use SQL operators and aliases in SELECT clause.
	 *
	 * @param QueryBuildSelect $QuerySelect
	 * @param mixed $limit
	 *        	Can be an array with 2 integers (for example: [0, 10]) or a string (for example: "0, 10")
	 * @param bool $loadForeign
	 *        	If true, this will automatically load foreign entity from defined foreign keys
	 * @return self
	 */
	public function loadCollection(QueryBuildSelect $QuerySelect, bool $loadForeign = false): self {
		$microtimeStart = microtime ( true );
		$this->Collection = new DataCollection ();
		$this->foreignLoaded = false;
		$data = [ ];
		$query = null;
		$prepared = null;

		try {
			if (! ($query = $QuerySelect->getQueryString ()))
				$query = $QuerySelect->build ()->getQueryString ();
			$prepared = $QuerySelect->getPreparedValues ();

			$Statement = $this->getPDO ()->prepare ( $query );
			$Statement->execute ( $prepared );
			$data = $Statement->fetchAll ( \PDO::FETCH_ASSOC );
			if ($data === false) {
				$this->logTransactionStatus ( self::STATUS_ERROR, [ 
						'filters' => $QuerySelect->getFilters (),
						'prepared' => $prepared
				], get_class ( $this ) . '::' . __FUNCTION__, print_r ( $Statement->errorInfo (), true ), $query, $microtimeStart );
			} elseif (($errs = $Statement->errorInfo ()) && ! empty ( $errs [2] )) {
				$this->logTransactionStatus ( self::STATUS_ERROR, [ 
						'filters' => $QuerySelect->getFilters (),
						'prepared' => $prepared
				], get_class ( $this ) . '::' . __FUNCTION__, $errs [2], $query, $microtimeStart );
			} else {
				$this->logTransactionStatus ( self::STATUS_SUCCESS, [ 
						'filters' => $QuerySelect->getFilters (),
						'prepared' => $prepared
				], get_class ( $this ) . '::' . __FUNCTION__, sprintf ( 'Result: %s row(s)', count ( $data ) ), $query, $microtimeStart );
			}
		} catch ( \Throwable $T ) {
			$this->logTransactionStatus ( self::STATUS_ERROR, [ 
					'filters' => $QuerySelect->getFilters (),
					'prepared' => $prepared
			], get_class ( $this ) . '::' . __FUNCTION__, $T->getMessage (), $query, $microtimeStart );
			$this->getSuperServices ()->getErrorHandlerService ()->catchThrowable ( $T );
		}

		$this->Collection->import ( $data );

		if ($loadForeign)
			return $this->loadForeignCollection ();
		return $this;
	}

	/**
	 * You must have run "$this->loadCollection()" (or "QueryBuildSelect::loadEntityCollection()") before trying to get foreign data.
	 * This function load data from current data having foreign key and referenced data from another entity.
	 *
	 * @return self
	 */
	public function loadForeignCollection(): self {
		if (! $this->count () || ! ($fks = $this->getForeignKeys ()) || $this->foreignLoaded)
			return $this;
		foreach ( $fks as $fkDef ) {
			$currentKeys = array_keys ( $fkDef ['referenced_keys'] );
			$columnName = $currentKeys [0];
			$Entity = $this->getForeignEntityFrom ( $columnName );
			if ($Entity instanceof Model) {
				$nbRows = $this->count ();
				$conditions = [ ];
				$handledKeys = [ ];
				foreach ( $this->Collection as $DataRow ) {
					$cond = [ 
							'AND' => [ ]
					];
					$seekedKeys = [ ];
					$seekedKey = '';
					foreach ( $currentKeys as $curKey ) {
						$val = $DataRow->findByKey ( $curKey );
						$seekedKeys [$fkDef ['referenced_keys'] [$curKey]] = $val;
						$seekedKey .= $fkDef ['referenced_keys'] [$curKey] . ":$val;";
					}

					if (! isset ( $handledKeys [$seekedKey] )) {
						$handledKeys [$seekedKey] = 1;
						foreach ( $seekedKeys as $k => $v )
							$cond ['AND'] [$k] = $v;
						if ($nbRows > 1)
							$conditions ['OR'] [] = $cond;
						else
							$conditions = $cond;
					}
				}
				$this->Collection->rewind ();
				$orderBy = $Entity->preferedDisplayedColumn ? [ 
						$Entity->preferedDisplayedColumn => 'ASC'
				] : [ 
						'1' => 'ASC'
				];
				// Preventing recursive (unstoppable) loading of foreign collections, it is set to false here.
				(new QueryBuildSelect ( $Entity ))->distinct ( true )
					->where ( $conditions )
					->orderBy ( $orderBy )
					->loadEntityCollection ( false );
			}
		}
		$this->foreignLoaded = true;
		return $this;
	}

	/**
	 *
	 * @param string $status
	 * @param array $criterias
	 * @param string $context
	 * @param mixed|string|array $message
	 * @param string $query
	 * @param float $startedMicrotime
	 *        	Microtime (as float). If passed, calculate query execution time
	 * @param string|null $lastInsertId
	 * @return self
	 */
	final public function logTransactionStatus(string $status, array $criterias, string $context, $message, ?string $query = null, ?float $startedMicrotime = null, ?string $lastInsertId = null): self {
		if (! $startedMicrotime)
			$startedMicrotime = microtime ( true );
		if (in_array ( $startedMicrotime, $this->TransactionStatutes->keys () ))
			$startedMicrotime += .0000001;
		$this->lastTransactionKey = $startedMicrotime;
		$executionTime = $startedMicrotime ? number_format ( microtime ( true ) - $startedMicrotime, 5 ) . 's' : 'N/A';
		$log = [ 
				'status' => $status,
				'criterias' => $criterias,
				'context' => $context,
				'connector_id' => $this->getConnectorId (),
				'message' => $message,
				'query' => $query,
				'execution_time' => $executionTime
		];
		if ($lastInsertId)
			$log ['PDO_lastInsertId'] = $lastInsertId;

		$this->TransactionStatutes->upsert ( $this->lastTransactionKey, $log );

		if ($this->getSuperServices ()->getDebugService ()->isActivated ())
			$this->getSuperServices ()->getDebugService ()->setDebugInfo ( $this->lastTransactionKey . ' `' . $this->getTableName () . '`', $log, 'MODELS' );

		$query = str_replace ( "\n", "\n\t", $query );
		if ($status === self::STATUS_ERROR)
			$this->log ( "$context | $message\nQuery:\n\t$query\nCriterias: " . (new DataCollection ( $criterias )) );

		return $this;
	}

	/**
	 * Update and/or insert rows from data collection into the database table.
	 * This will not delete rows from database.
	 * Use the "remove" function to do so.
	 * This function auto register rows contained into this collection, updating database.
	 * It CANNOT work with having no primary key.
	 * You can use updateDb or insertDb functions instead.
	 *
	 * @return self
	 */
	public function registerCollection(): self {
		if (! ($pKs = $this->getPrimaryKey ())) {
			$errMsg = get_class ( $this ) . '::' . __FUNCTION__ . ' | ' . sprintf ( dgettext ( 'core', 'You cannot auto register collection if there is no primary key declared for table "%s"' ), $this->tableName );
			$this->log ( $errMsg );
			throw new \LogicException ( $errMsg );
		}

		foreach ( $this->Collection as $Row ) {
			if ($Row instanceof DataCollection && $Row->modified ()) {
				// First: building conditions
				$filters = [ ];
				// Matching PK
				foreach ( $pKs as $pk ) {
					if ($pkValue = $Row->findByKey ( $pk ))
						$filters [$pk] = $pkValue;
				}
				$this->upsertDb ( $Row, $filters );
			}
		}
		return $this;
	}

	/**
	 * Initialize a select query, giving columns to select.
	 * It returns a QueryBuildSelect instance, that allows to build the rest of the query (joins, filters etc...)
	 *
	 * @param string $alias
	 *        	(required) Table alias in SQL query.
	 * @param mixed|array|string|null $selection
	 *        	(optional) It can be a full string or an array of string. Example: '*', or ['id', 'COUNT(*) AS nb']
	 *        	If empty, default is '*'.
	 * @return QueryBuildSelect
	 */
	public function selectAs(string $alias, $selection = '*'): QueryBuildSelect {
		return (new QueryBuildSelect ( $this, $alias ))->select ( $selection );
	}

	/**
	 * Define the model connectoID and immediately, set instance of PDO depending on this connector.
	 *
	 * @param string $connectorId
	 * @return self
	 */
	final protected function setConnectorID(string $connectorId): self {
		if ($this->PDO = $this->getSuperServices ()->getDatabaseConnectorService ()->getPDO ( $connectorId )) {
			$this->connectorId = $connectorId;
		} else {
			$message = sprintf ( dgettext ( 'core', 'Unknown connector ID "%s"' ), $connectorId );
			$this->log ( static::class . ': ' . $message );
			$this->PDO = $this->getSuperServices ()->getDatabaseConnectorService ()->getDefaultPDO ();
		}
		return $this;
	}

	/**
	 * Update a database table row.
	 * Both passed parameters are iterable, that means you can pass an array or a DataCollection or any iterable object whose keys are columns that belong to this entity.
	 *
	 * @param iterable $Row
	 *        	The row to update (pass the fields to set).
	 *        	You can pass an array: [ 'field_name' => $value ]. Do not pass the alias. This update function does not update fields from another entity.
	 *        	$value should be a single value (string, int, bool, float, null).
	 *        	If you want to set another field value, this is the special case when you must set $value as an itarable (array):
	 *        	[ 'field_name' => [ SqlOperator::EQ_FIELD => 'another_field_name' ] ]
	 *        	This will result to a query part like this: `entity_alias`.`field_name` = `entity_alias`.`another_field_name`
	 *        	If the another_field was autmatically detected as a field as entity property.
	 *        	You can also use EQ_LITT, it behaves the same and allows you to type litterally specific SQL operations to set value to specified field.
	 * @param iterable $filters
	 *        	Contains criterias matching row(s) to update (where conditions).
	 * @return self
	 */
	public function updateDb(iterable $Row, iterable $filters): self {
		return (new QueryBuildUpdate ( $this ))->setRow ( $Row )->where ( $filters )->execute ();
	}

	/**
	 * Upsert allows you to insert a row if it doesn't exist yet (especially if primary key is set in $Row and doesn't exist yet).
	 *
	 * @param iterable $Row
	 *        	Values to update or insert
	 * @return self
	 */
	public function upsertDb(iterable $Row): self {
		$microtimeStart = microtime ( true );
		$Row = new DataCollection ( $Row );
		$allPkPassed = true;
		$filters = [ ];
		foreach ( $this->getPrimaryKey () as $pk ) {
			if (! ($filters [$pk] = $Row->findByKey ( $pk ))) {
				$allPkPassed = false;
				break;
			}
		}
		// If all PK passed, then check into database to look for existing row
		if ($allPkPassed) {
			// Find an existing row
			$nbExistingEntries = ( int ) (new static ())->selectAs ( 'T', 'COUNT(*) AS existing' )
				->where ( $filters )
				->execute ()
				->getValue ( 'existing' );

			// Ambiguous result
			if ($nbExistingEntries > 1)
				return $this->logTransactionStatus ( self::STATUS_ERROR, [ 
						'filters' => $filters,
						'row' => $Row->getData ()
				], get_class ( $this ) . '::' . __FUNCTION__, dgettext ( 'core', 'Upsert cannot work if filters fetch more than 1 row. Process interrupted by ambiguous target data.' ), null, $microtimeStart );

			// Update
			if ($nbExistingEntries === 1)
				return $this->updateDb ( $Row, $filters );
		}
		// Insert
		return $this->insertIntoDb ( $Row );
	}

	# Functions implementing Iterator interface
	/**
	 * Current row from this entity collection.
	 * Current row is positioned on the current cursor from the data collection.
	 * If a loop has been applied on this iterator til the end of collection, then "current()" will return the last element
	 * except if collection has been rewound: $this->getCollection()->rewind();
	 *
	 * {@inheritdoc}
	 * @see Iterator::current()
	 *
	 * @return mixed
	 */
	function current(): mixed {
		return $this->Collection->current ();
	}

	/**
	 * Removing a given data row from the database.
	 * It is not required that $Row contains primary key.
	 * This function will remove all database rows that match values set to the table properties in the $Row collection.
	 * That means that this <strong>can remove multiple rows</strong> from database.
	 *
	 * @param iterable $filters
	 * @return self
	 */
	public function deleteFromDb(iterable $filters): self {
		return (new QueryBuildDelete ( $this ))->execute ( $filters );
	}

	/**
	 *
	 * @param bool $useColumnLabels
	 * @param string $sep
	 * @return string CSV output
	 */
	public function parseCollectionToCsvString(bool $useColumnLabels = false, string $sep = ';'): string {
		$csvcontent = '';
		$this->Collection->rewind ();
		if (! ($this->Collection->current () instanceof DataCollection))
			return $csvcontent;

		try {
			$csvheaders = $this->Collection->current ()->keys ();
			if (empty ( $csvheaders ))
				return $csvcontent;
			foreach ( $csvheaders as $header ) {
				if ($useColumnLabels)
					$header = $this->getColumnLabel ( $header );
				$csvcontent .= '"' . str_replace ( '"', '""', $header ) . '"' . $sep;
			}
			$csvcontent = trim ( $csvcontent, $sep ) . "\n";
			foreach ( $this->Collection as $Row ) {
				if (! $Row instanceof DataCollection)
					continue;
				$csvrow = '';
				foreach ( $Row as $cell )
					$csvrow .= '"' . str_replace ( '"', '""', $cell ) . '"' . $sep;
				$csvrow = trim ( $csvrow, $sep );
				$csvcontent .= "$csvrow\n";
			}
		} catch ( \Throwable $Throwable ) {
			$csvcontent .= "\n" . $Throwable->getMessage ();
		}
		return $csvcontent;
	}

	/**
	 * Collection count.
	 * Get number of elements in the collection.
	 *
	 * @return int
	 */
	function count(): int {
		return $this->Collection->count ();
	}

	/**
	 * Collection current key.
	 *
	 * {@inheritdoc}
	 * @see Iterator::key()
	 *
	 * @return mixed
	 */
	function key(): mixed {
		return $this->Collection->key ();
	}

	/**
	 * Increase current position in the collectionand fetch next element.
	 * <i>
	 * while ($this->valid()){<br>
	 * $this->next();<br>
	 * }<br>
	 * $this->rewind();
	 * </i>
	 *
	 * {@inheritdoc}
	 * @see Iterator::next()
	 *
	 * @return void
	 */
	function next(): void {
		$this->Collection->next ();
	}

	/**
	 * Reset collection position to 0.
	 *
	 * {@inheritdoc}
	 * @see Iterator::rewind()
	 *
	 * @return void
	 */
	function rewind(): void {
		$this->Collection->rewind ();
	}

	/**
	 * Check if current position in collection is valid (i.e it is not out of range).
	 * Usually, current position is out of range when it reach the end of collection and go next.
	 *
	 * {@inheritdoc}
	 * @see Iterator::valid()
	 *
	 * @return bool
	 */
	function valid(): bool {
		return $this->Collection->valid ();
	}
}