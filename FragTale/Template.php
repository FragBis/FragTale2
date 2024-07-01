<?php

namespace FragTale;

use FragTale\Constant\MessageType;
use FragTale\Constant\TemplateFormat;
use FragTale\Constant\Setup\CorePath;
use FragTale\Implement\LoggerTrait;

/**
 *
 * @author Fabrice Dant <fragtale.development@gmail.com>
 * @copyright 2024 FragTale 2 - Fabrice Dant
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-fr.txt CeCILL Licence 2.1 (French version)
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-en.txt CeCILL Licence 2.1 (English version)
 *         
 */
class Template {
	use LoggerTrait;

	/**
	 *
	 * @var DataCollection
	 */
	protected DataCollection $Vars;

	/**
	 *
	 * @var array
	 */
	protected array $loadedObjects;

	/**
	 * Web page title
	 *
	 * @var string
	 */
	protected ?string $title;

	/**
	 * A value set in \FragTale\Constant\TemplateFormat
	 * By default, using PHTML
	 *
	 * @see \FragTale\Constant\TemplateFormat
	 * @var int
	 */
	protected int $formatId;

	/**
	 * Relative path of the view template
	 *
	 * @var string
	 */
	protected string $path;

	/**
	 * Relative path to the html layout file.
	 *
	 * @var string
	 */
	protected ?string $layoutPath = null;

	/**
	 * Print JSON or XML string in pretty readable format
	 *
	 * @var boolean
	 */
	protected bool $prettyPrint = false;

	/**
	 *
	 * @var DataCollection
	 */
	protected static DataCollection $CssSources;

	/**
	 *
	 * @var DataCollection
	 */
	protected static DataCollection $JsSources;

	/**
	 *
	 * @param string $title
	 * @param string $templatePath
	 * @param string $layoutPath
	 */
	function __construct(?string $title = null, ?string $templatePath = null, ?string $layoutPath = null) {
		$this->Vars = new DataCollection ();
		$this->loadedObjects = [ ];
		$this->setTitle ( $title )->setPath ( $templatePath )->setLayoutPath ( $layoutPath );
		if (! isset ( static::$CssSources ))
			static::$CssSources = new DataCollection ();
		if (! isset ( static::$JsSources ))
			static::$JsSources = new DataCollection ();
	}

	/**
	 * Web page title
	 *
	 * @return string
	 */
	public function getTitle(): string {
		return $this->title;
	}

	/**
	 * Template Format IDs are listed in \FragTale\Constant\TemplateFormat as Constants.
	 * If this template format ID is null or empty, controller will use the default template format defined in your project settings.
	 *
	 * @return int|NULL
	 */
	public function getFormatId(): ?int {
		return $this->formatId;
	}

	/**
	 * Relative path from your folder: "/Project/{my_project}/resources/templates/views"
	 * For example, your homepage will bind "/Project/{my_project}/resources/templates/views/home.phtml" file to your instance of template (and by extension: view and controller).
	 *
	 * @return string
	 */
	public function getPath(): string {
		return $this->path;
	}

	/**
	 * The layout file relative path.
	 *
	 * @return string|null
	 */
	public function getLayoutPath(): ?string {
		return $this->layoutPath;
	}

	/**
	 *
	 * @param string|int $key
	 * @return NULL|string|number|boolean|\FragTale\DataCollection
	 */
	public function getVar($key) {
		return $this->Vars->findByKey ( $key );
	}

	/**
	 *
	 * @return DataCollection
	 */
	public function getVars(): DataCollection {
		return $this->Vars;
	}

	/**
	 * Get an object passed to this template's objects list, giving its key.
	 *
	 * @param string|int $key
	 * @return object|NULL
	 */
	public function getObject($key): ?object {
		return isset ( $this->loadedObjects [$key] ) ? $this->loadedObjects [$key] : null;
	}

	/**
	 * Get this template's objects list.
	 *
	 * @return array
	 */
	public function getObjects(): array {
		return $this->loadedObjects;
	}

	/**
	 * Check if printing JSON or XML string in pretty readable format
	 *
	 * @return bool
	 */
	public function isPrettyPrint(): bool {
		return $this->prettyPrint;
	}

	/**
	 *
	 * @param string $title
	 * @return self
	 */
	public function setTitle(?string $title): self {
		$this->title = $title;
		return $this;
	}

	/**
	 *
	 * @param int $templateFormatId
	 * @return self
	 */
	public function setFormatId(int $templateFormatId): self {
		if ($templateFormatId && in_array ( $templateFormatId, TemplateFormat::getConstants ()->getData ( true ) ))
			$this->formatId = $templateFormatId;
		return $this;
	}

	/**
	 * Set the template path, such as:
	 * "Project/MyProject/resources/templates/views/home.phtml"
	 *
	 * @param string $templatePath
	 *        	Relative path from APP_ROOT
	 */
	public function setPath(?string $templatePath = null): self {
		global $Application;
		if ($templatePath) {
			$fullpath = strpos ( $templatePath, APP_ROOT ) === 0 ? $templatePath : APP_ROOT . "/$templatePath";
			if (! file_exists ( $fullpath )) {
				$Application->getSuperServices ()->getFrontMessageService ()->add ( sprintf ( dgettext ( 'core', 'File "%s" not found. Using default template file.' ), $fullpath ), MessageType::WARNING );
				$fullpath = CorePath::DEFAULT_TEMPLATE_PATH;
			}
		} else
			$fullpath = CorePath::DEFAULT_TEMPLATE_PATH;

		$this->path = str_replace ( APP_ROOT . '/', '', realpath ( $fullpath ) );
		return $this;
	}

	/**
	 * Set the web page layout file relative path, such as:
	 * "Project/MyProject/resources/templates/layouts/default.phtml"
	 *
	 * @param string|null $layoutPath
	 *        	If empty, no page layout will be used.
	 * @throws \Exception When $layoutPath is not empty and does not match an existing file
	 * @return self
	 */
	public function setLayoutPath(?string $layoutPath = null): self {
		global $Application;
		if ($layoutPath) {
			$fullpath = strpos ( $layoutPath, APP_ROOT ) === 0 ? $layoutPath : APP_ROOT . "/$layoutPath";
			if (! file_exists ( $fullpath )) {
				$Application->getSuperServices ()->getFrontMessageService ()->add ( sprintf ( dgettext ( 'core', 'Layout file "%s" not found.' ), $fullpath ), MessageType::WARNING );
				$this->layoutPath = null;
			} else
				$this->layoutPath = str_replace ( APP_ROOT . '/', '', realpath ( $fullpath ) );
		} else
			$this->layoutPath = null;
		return $this;
	}

	/**
	 * Define printing JSON or XML string in pretty readable format or not
	 *
	 * @param bool $isPretty
	 * @return self
	 */
	public function setPrettyPrint(bool $isPretty): self {
		$this->prettyPrint = $isPretty;
		return $this;
	}

	/**
	 * Set one of the variables usable by the template.
	 * Attention! Any object passed as value will be considered as iterable. Objects cannot keep their reference, they will be parsed as DataCollection.
	 * If you want to use a referenced object, then use function "setObject" instead.
	 *
	 * @param string|int|float $key
	 * @param int|float|string|iterable $value
	 *        	Any value (scalar) or iterable.
	 * @return self
	 */
	public function setVar($key, $value): self {
		if (! is_string ( $key ) && ! is_int ( $key ) && ! is_float ( $key ))
			$key = ( string ) $key;
		$this->Vars->upsert ( $key, $value );
		return $this;
	}

	/**
	 * Set variable collection usable by the template.
	 *
	 * @param DataCollection $Collection
	 * @return self
	 */
	public function setVars(DataCollection $Collection): self {
		$this->Vars = $Collection;
		return $this;
	}

	/**
	 * Set a referenced object usable by the template.
	 *
	 * @param string|int|float $key
	 * @param object $Object
	 * @return self
	 */
	public function setObject($key, ?object $Object): self {
		if ($Object)
			$this->loadedObjects [$key] = $Object;
		return $this;
	}

	/**
	 * Remove a template var giving its key
	 *
	 * @param string|number $key
	 * @return self
	 */
	public function unsetVar($key): self {
		$this->Vars->delete ( $key );
		return $this;
	}

	/**
	 * Add a CSS source file
	 *
	 * @param string $url
	 *        	Full URL
	 * @param array $properties
	 *        	(optional) Additional tag custom properties (such as "integrity", "origin" etc...)
	 * @return self
	 */
	public function addCssSource(string $url, ?array $properties = null): self {
		if ($url = trim ( $url )) {
			$tagProps = [ 
					'rel' => 'stylesheet',
					'href' => $url
			];
			if ($properties)
				foreach ( $properties as $k => $v )
					$tagProps [$k] = $v;
			static::$CssSources->upsert ( $url, $tagProps );
		}
		return $this;
	}

	/**
	 * Add a JS source file
	 *
	 * @param string $url
	 *        	Full URL
	 * @param array $properties
	 *        	(optional) Additional tag custom properties
	 * @return self
	 */
	public function addJsSource(string $url, array $properties = [ ]): self {
		if ($url = trim ( $url )) {
			$tagProps = [ 
					'src' => $url
			];
			if ($properties)
				foreach ( $properties as $k => $v )
					$tagProps [$k] = $v;
			static::$JsSources->upsert ( $url, $tagProps );
		}
		return $this;
	}

	/**
	 * HTML tag output giving CSS source files to include
	 *
	 * @return string
	 */
	public function getCssSourceTags() {
		$tags = '';
		foreach ( static::$CssSources as $properties ) {
			if ($properties instanceof DataCollection && $properties->position ( 'href' ) !== null) {
				$tagProps = [ ];
				foreach ( $properties as $key => $value )
					$tagProps [] = $key . '="' . $value . '"';
				$tags .= '<link ' . implode ( ' ', $tagProps ) . '/>';
			}
		}
		return $tags;
	}

	/**
	 *
	 * @return string: HTML tag output giving JS source files to include
	 */
	public function getJsSourceTags() {
		$tags = '';
		foreach ( static::$JsSources as $properties ) {
			if ($properties instanceof DataCollection && $properties->position ( 'src' ) !== null) {
				$tagProps = [ ];
				foreach ( $properties as $key => $value )
					$tagProps [] = $key . '="' . $value . '"';
				$tags .= '<script ' . implode ( ' ', $tagProps ) . '></script>';
			}
		}
		return $tags;
	}
}