<?php

namespace FragTale;

use Iterator;
use Closure;
use FragTale\Implement\LoggerTrait;

/**
 * Design pattern Iterator.
 *
 * @author Fabrice Dant <fragtale.development@gmail.com>
 * @copyright 2024 FragTale 2 - Fabrice Dant
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-fr.txt CeCILL Licence 2.1 (French version)
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-en.txt CeCILL Licence 2.1 (English version)
 *         
 */
class DataCollection implements Iterator {
	use LoggerTrait;
	protected array $data = [ ];
	protected array $initialData = [ ];
	protected bool $keepInitialData = false;
	protected array $keys = [ ];
	protected int $position = 0;
	protected bool $modified = false;
	protected ?DataCollection $ParentNode = null;
	protected bool $breakLoop = false;

	/**
	 * Design pattern Iterator.
	 *
	 * @param mixed|iterable|string|int|float|bool|null $data
	 *        	Any type of data. All iterable objects or arrays will be parsed as DataCollection.
	 * @param bool $keepInitialData
	 *        	If true, it will keep passed data into a duplicate array "initialData" that can be retrieved using function "getInitialData".
	 *        	Set false (default) to save memory.
	 */
	function __construct($data = null, bool $keepInitialData = false) {
		$this->keepInitialData = $keepInitialData;
		$this->import ( $data );
	}

	/**
	 *
	 * @param DataCollection $ParentNode
	 * @return self
	 */
	protected function setParentNode(DataCollection $ParentNode): self {
		$this->ParentNode = $ParentNode;
		return $this;
	}

	/**
	 * Define if you want to keep the initial data loaded in "initialData" array.
	 * If might want to get initial data for comparisons. By default, initial data are not kept.
	 * You must set it via constructor.
	 *
	 * @param bool $keep
	 * @return self
	 */
	public function keepInitialData(bool $keep): self {
		$this->keepInitialData = $keep;
		if (! $this->keepInitialData)
			$this->initialData = [ ];
		return $this;
	}

	/**
	 * If function returns null, this collection is the top node.
	 *
	 * @return self|NULL
	 */
	public function getParentNode(): ?self {
		return isset ( $this->ParentNode ) ? $this->ParentNode : null;
	}

	/**
	 *
	 * @return string|NULL
	 */
	public function getKeyFromParentNode(): ?string {
		if (! $this->getParentNode ())
			return null;
		$returnedKey = null;
		$MatchingCollection = $this;
		$this->getParentNode ()->forEach ( function ($key, $element) use (&$returnedKey, $MatchingCollection) {
			if ($MatchingCollection === $element) {
				$returnedKey = $key;
				return false;
			}
		} );
		return is_numeric ( $returnedKey ) ? ( string ) $returnedKey : $returnedKey;
	}

	/**
	 *
	 * @return int|NULL
	 */
	public function getPositionFromParentNode(): ?int {
		if (! $this->getParentNode ())
			return null;
		return $this->getParentNode ()->position ( $this->getKeyFromParentNode () );
	}

	/**
	 * Get all data keys at the first step.
	 *
	 * @return array
	 */
	public function keys(): array {
		return $this->keys;
	}

	/**
	 * The original (recursive) array containing the initial data passed on construct.
	 *
	 * @return array
	 */
	public function getInitialData(): array {
		return $this->initialData;
	}

	/**
	 * Get all data collection, optionnally converts all objects to array.
	 *
	 * @param bool $asRecursiveArray
	 *        	If true, it will recursively parse all DataCollection into array. False by default.
	 * @return array
	 */
	public function getData(bool $asRecursiveArray = false): array {
		if (! $asRecursiveArray)
			return $this->data;
		$data = [ ];
		foreach ( $this->data as $key => $element ) {
			if ($element instanceof DataCollection)
				$data [$key] = $element->getData ( true );
			else
				$data [$key] = is_object ( $element ) ? ( array ) $element : $element;
		}
		return $data;
	}

	/**
	 *
	 * {@inheritdoc}
	 * @see Iterator::next()
	 *
	 * @return void
	 */
	public function next(): void {
		$this->position ++;
	}

	/**
	 * Check if current position is valid
	 *
	 * {@inheritdoc}
	 * @see Iterator::valid()
	 *
	 * @return bool
	 */
	public function valid(): bool {
		return array_key_exists ( $this->position, $this->keys );
	}

	/**
	 *
	 * {@inheritdoc}
	 * @see Iterator::current()
	 *
	 * @return mixed The current element
	 */
	public function current(): mixed {
		if (! $this->valid ())
			return null;
		$key = $this->keys [$this->position];
		return $this->data [$key];
	}

	/**
	 * Reset position.
	 *
	 * {@inheritdoc}
	 * @see Iterator::rewind()
	 *
	 * @return void
	 */
	public function rewind(): void {
		$this->keys = array_keys ( $this->data );
		$this->position = 0;
	}

	/**
	 * Get the current key where cursor is positioned.
	 * Initial data set in constructor should contain associative arrays.
	 * This iterator keeps initial keys of data passed in constructor. Except if you sort the object by a closure function.
	 * Note that since it is an implemented method from Iterator interface, this method is not prefixed by "get".
	 *
	 * {@inheritdoc}
	 * @see Iterator::key()
	 *
	 * @param int|NULL $position
	 *        	If null, it will return the current key, if is int it will return the matching key.
	 * @return mixed
	 */
	public function key(?int $position = null): mixed {
		if ($position === null)
			return $this->valid () ? $this->keys [$this->position] : null;
		return isset ( $this->keys [$position] ) ? $this->keys [$position] : null;
	}

	/**
	 * If $key is null, it will return the current cursor position in data list.
	 * Else, returns position of specified key or null if not exists.
	 *
	 * @param string $key
	 * @return int|NULL
	 */
	public function position(?string $key = null): ?int {
		if ($key === null)
			return $this->valid () ? $this->position : null;
		$position = array_search ( $key, $this->keys );
		return $position === false ? null : $position;
	}

	/**
	 * Number of elements.
	 *
	 * @return int
	 */
	public function count(): int {
		return count ( $this->data );
	}

	/**
	 * Iterator is rewind after sorting.
	 * All initial keys will be replaced by ordinal numeric indexes.
	 * This sort function behaves the same than PHP function "usort".
	 *
	 * @see https://www.php.net/manual/fr/function.usort.php (native PHP function)
	 * @param \Closure $closure
	 *        	Closure function must handle 2 passed arguments: $element1 and $element2 to be compared, and it must return -1, 0 or 1
	 * @return self
	 */
	public function sort(Closure $closure): self {
		usort ( $this->data, $closure );
		$this->keys = array_keys ( $this->data );
		$this->rewind ();
		return $this;
	}

	/**
	 * Alphanumerical order on keys.
	 *
	 * @param bool $ascending
	 *        	If false, it will reverse sort
	 * @return self
	 */
	public function ksort(bool $ascending = true): self {
		if ($ascending)
			ksort ( $this->data );
		else
			krsort ( $this->data );
		$this->keys = array_keys ( $this->data );
		$this->rewind ();
		return $this;
	}

	/**
	 * Typically used to break a "find".
	 * Example: $FoundItems = $DataCollection->find ( function ($key, $item) use ($DataCollection) {
	 * if ($anyCondition === "any matched condition") $DataCollection->breakLoop();
	 * return [true, false]; // Depending on your conditions that will return $item or not.
	 * } );
	 *
	 * @return self
	 */
	public function breakLoop(): self {
		$this->breakLoop = true;
		return $this;
	}

	/**
	 * "forEach" expects closure function to return a boolean.
	 * Collection is rewind before and after the loop.
	 * Returning false breaks the loop.
	 *
	 * @param \Closure $closure
	 *        	The closure must handles 2 passed parameters: $index and $element
	 * @return self
	 */
	public function forEach(Closure $closure): self {
		$this->rewind ();
		foreach ( $this->data as $index => $element ) {
			$this->position ++;
			if ($this->breakLoop) {
				$this->breakLoop = false;
				break;
			}
			if ($closure ( $index, $element ) === false)
				break;
		}
		$this->rewind ();
		return $this;
	}

	/**
	 * Closure function must handles 2 arguments ($key and $element) and must return true or false.
	 * "find" will return all elements matching given conditions (returning true).
	 *
	 * @param \Closure $closure
	 *        	Closure function must return a boolean. It must return true if an element match criterias.
	 *        	It has 2 arguments: <b>$key</b> and <b>$element</b> ($element can be int, string, float, bool, null or DataCollection)
	 * @return DataCollection
	 */
	public function find(Closure $closure): self {
		$elements = [ ];
		foreach ( $this->data as $key => $element ) {
			if ($this->breakLoop) {
				$this->breakLoop = false;
				break;
			}
			if ($closure ( $key, $element ))
				$elements [$key] = $element;
		}
		return new DataCollection ( $elements );
	}

	/**
	 * Returns one element placed at given position.
	 *
	 * @param int $position
	 * @return mixed
	 */
	public function findAt(int $position): mixed {
		if (! array_key_exists ( $position, $this->keys ))
			return null;
		$key = $this->keys [$position];
		return isset ( $this->data [$key] ) ? $this->data [$key] : null;
	}

	/**
	 * Returns first element in collection.
	 *
	 * @return mixed
	 */
	public function findFirst(): mixed {
		return $this->findAt ( 0 );
	}

	/**
	 * Returns last element in collection.
	 *
	 * @return mixed
	 */
	public function findLast(): mixed {
		return $this->findAt ( ($this->count () - 1) );
	}

	/**
	 * Returns one element associated with given key.
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function findByKey(?string $key): mixed {
		return isset ( $this->data [$key] ) ? $this->data [$key] : null;
	}

	/**
	 * Returns a collection of element(s) having change(s).
	 *
	 * @return DataCollection
	 */
	public function findDiffs(): self {
		return $this->find ( function ($key, $element) {
			if (! array_key_exists ( $key, $this->initialData ))
				return true;
			if ($element instanceof DataCollection)
				return $element->modified ();
			return ($element != $this->initialData [$key]);
		} );
	}

	/**
	 * Get key of the first occurence matching given element.
	 *
	 * @param mixed $element
	 * @param bool $strict
	 * @return mixed Null should mean element does not exist, but obviously, you should not have any element having null or '' as key
	 */
	public function getElementKey($element, bool $strict = false): mixed {
		$key = array_search ( $element, $this->data, $strict );
		if (in_array ( $key, [ 
				'',
				null,
				false
		], true )) {
			if (! array_key_exists ( $key, $this->keys () ))
				return null;
		}
		return $key;
	}

	/**
	 * Get position of the first occurence (strictly) matching given element.
	 *
	 * @param mixed $element
	 * @param bool $strict
	 * @return int|NULL Null should mean element does not exist, but obviously, you should not have any element having null or '' as key
	 */
	public function getElementPosition($element, bool $strict = false): ?int {
		$eltKey = $this->getElementKey ( $element, $strict );
		$eltPos = array_search ( $eltKey, $this->keys (), $strict );
		return is_numeric ( $eltPos ) ? $eltPos : null;
	}

	/**
	 * Get first item of data.
	 * Also set the internal pointer of collection to the first element.
	 *
	 * @return mixed
	 */
	public function first(): mixed {
		$this->rewind ();
		return reset ( $this->data );
	}

	/**
	 * Get last item of data.
	 * Also set the internal pointer of collection to the last element.
	 *
	 * @return mixed
	 */
	public function last(): mixed {
		$this->position = $this->count () - 1;
		return end ( $this->data );
	}

	/**
	 * Tells if collection has been modified (using upsert or delete).
	 *
	 * @return bool
	 */
	public function modified(): bool {
		// Check if children are modified
		if (! ($modified = $this->modified)) {
			$this->forEach ( function ($key, $element) use (&$modified) {
				if ($element instanceof DataCollection && $element->modified ()) {
					$modified = true;
					return false; // break loop: found one child having change(s).
				}
			} );
			$this->modified = $modified;
		}
		return $this->modified;
	}

	/**
	 * Replace an existing element or add a new one.
	 *
	 * @param string $key
	 * @param mixed $newElement
	 * @return self
	 */
	public function upsert(string $key, $newElement): self {
		$this->data [$key] = is_iterable ( $newElement ) || is_object ( $newElement ) ? (new DataCollection ( $newElement, false ))->setParentNode ( $this ) : $newElement;
		$this->modified = true;
		if ($this->data [$key] instanceof DataCollection)
			$this->data [$key]->modified = true;
		$this->rewind ();
		return $this;
	}

	/**
	 * Append new element, generating a random key if second parameter is true.
	 * <b>You should use "push" when keys are positions (0, 1, 2...).</b>
	 * You can set the second parameter to "true" to generate a randow key when keys are not all an integer position.
	 * In most case, prefer using "upsert" function.
	 *
	 * @param mixed $newElement
	 * @param bool $generateRandowKey
	 *        	"true" to generate a randow key when keys are not all an integer position.
	 * @return \FragTale\DataCollection
	 */
	public function push($newElement, bool $generateRandowKey = false): self {
		// Generate a random key
		if ($generateRandowKey)
			$key = md5 ( microtime ( true ) . rand () );
		else {
			$key = $this->count ();
			while ( $this->position ( $key ) !== null )
				$key ++;
		}
		return $this->upsert ( $key, $newElement );
	}

	/**
	 * This function remove an element from this collection.
	 * If the source is a JSON|XML file which will be overwritten by "save" function, the element will be definitely removed.
	 * <b>For MongoDB source, it won't remove automatically an element from the database.</b>
	 *
	 * @param string $key
	 * @return self
	 */
	public function delete(string $key): self {
		unset ( $this->data [$key] );
		$this->modified = true;
		$this->rewind ();
		return $this;
	}

	/**
	 * JSON output of iterable elements
	 *
	 * @return string
	 */
	public function toJsonString(bool $prettyPrint = false) {
		return $prettyPrint ? json_encode ( $this->getData ( true ), JSON_PRETTY_PRINT ) : json_encode ( $this->getData ( true ) );
	}

	/**
	 *
	 * @param string $rootTagName
	 *        	Name of the XML object, the root tag.
	 * @return string
	 */
	public function toXmlString(string $rootTagName = null) {
		if (! $rootTagName)
			$rootTagName = strtolower ( str_replace ( "\\", "_", get_class ( $this ) ) );
		$xml = new \SimpleXMLElement ( "<$rootTagName/>" );
		array_walk_recursive ( $this->data, [ 
				$xml,
				'addChild'
		] );
		return $xml->asXML ();
	}

	/**
	 * JSON (or XML) data output
	 *
	 * @return string
	 */
	public function __toString(): string {
		return $this->toJsonString ( true );
	}

	/**
	 *
	 * @param mixed|iterable|string|int|float|bool|null $data
	 *        	Any type of data. All iterable objects or arrays will be parsed as DataCollection.
	 * @return self
	 */
	public function import($data): self {
		$this->data = [ ];
		$this->initialData = [ ];
		if ($data) {
			if (is_iterable ( $data ) || is_object ( $data )) {
				foreach ( $data as $k => $v ) {
					$this->data [$k] = is_iterable ( $v ) || is_object ( $v ) ? (new DataCollection ( $v, false ))->setParentNode ( $this ) : $v;
					if ($this->keepInitialData)
						$this->initialData [$k] = is_iterable ( $v ) || is_object ( $v ) ? new DataCollection ( $v, false ) : $v;
				}
			} else {
				$this->data [] = $data;
				if ($this->keepInitialData)
					$this->initialData [] = $data;
			}
		}

		$this->modified = false;
		$this->rewind ();
		return $this;
	}

	/**
	 *
	 * @param string $absolutePath
	 * @return self
	 */
	public function exportToJsonFile(string $absolutePath, bool $prettyPrint = false): self {
		if (! file_put_contents ( $absolutePath, $this->toJsonString ( $prettyPrint ) )) {
			$message = sprintf ( dgettext ( 'core', 'Unabled to save file %s' ), $absolutePath );
			if (IS_CLI)
				throw new \Exception ( $message );
			else
				$this->log ( $message, 'DataCollection_' );
		}
		return $this;
	}

	/**
	 *
	 * @param string $absolutePath
	 * @param string $rootTagName
	 *        	Name of the XML object, the root tag.
	 * @return self
	 */
	public function exportToXmlFile(string $absolutePath, string $rootTagName = null): self {
		if (! file_put_contents ( $absolutePath, $this->toXmlString ( $rootTagName ) )) {
			$message = sprintf ( dgettext ( 'core', 'Unabled to save file %s' ), $absolutePath );
			if (IS_CLI)
				throw new \Exception ( $message );
			else
				$this->log ( $message, 'DataCollection_' );
		}
		return $this;
	}

	/**
	 *
	 * @return self
	 */
	public function clone(): self {
		return new static ( $this->data );
	}
}