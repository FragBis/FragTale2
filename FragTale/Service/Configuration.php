<?php

namespace FragTale\Service;

use FragTale\Implement\AbstractService;
use FragTale\DataCollection;
use FragTale\Constant\MessageType;
use FragTale\Constant\Setup\CorePath;
use FragTale\Constant\Setup\Locale;

/**
 *
 * @author Fabrice Dant <fragtale.development@gmail.com>
 * @copyright 2024 FragTale 2 - Fabrice Dant
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-fr.txt CeCILL Licence 2.1 (French version)
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-en.txt CeCILL Licence 2.1 (English version)
 *         
 */
class Configuration extends AbstractService {

	/**
	 * Collection containing application parameters.
	 *
	 * @var DataCollection
	 */
	private ?DataCollection $CliApplicationSettings = null;

	/**
	 * Collection containing host's project.
	 *
	 * @var DataCollection
	 */
	private ?DataCollection $HostsSettings = null;

	/**
	 *
	 * @param string $word
	 * @param bool $alsoMatchConstants
	 * @return bool
	 */
	public function isPhpKeyword(?string $word, ?bool $alsoMatchConstants = false): bool {
		$keywords = [ 
				'__halt_compiler',
				'abstract',
				'and',
				'array',
				'as',
				'bool',
				'break',
				'callable',
				'case',
				'catch',
				'class',
				'clone',
				'const',
				'continue',
				'declare',
				'default',
				'die',
				'do',
				'echo',
				'else',
				'elseif',
				'empty',
				'enddeclare',
				'endfor',
				'endforeach',
				'endif',
				'endswitch',
				'endwhile',
				'eval',
				'exit',
				'extends',
				'false',
				'final',
				'float',
				'for',
				'foreach',
				'function',
				'global',
				'goto',
				'if',
				'implements',
				'include',
				'include_once',
				'instanceof',
				'insteadof',
				'int',
				'interface',
				'isset',
				'iterable',
				'list',
				'namespace',
				'new',
				'null',
				'object',
				'or',
				'print',
				'private',
				'protected',
				'public',
				'require',
				'require_once',
				'return',
				'static',
				'string',
				'switch',
				'throw',
				'trait',
				'true',
				'try',
				'unset',
				'use',
				'var',
				'void',
				'while',
				'xor'
		];
		return $alsoMatchConstants ? (in_array ( $word, $keywords ) || defined ( $word )) : in_array ( $word, $keywords );
	}

	/**
	 * Collection containing the global application configuration from file "resources/configuration/application.json"
	 *
	 * @return DataCollection
	 */
	final public function getCliApplicationSettings(): DataCollection {
		if (! $this->CliApplicationSettings instanceof DataCollection) {
			if (! file_exists ( CorePath::APP_SETTINGS_FILE )) {
				$this->getSuperServices ()->getCliService ()->printError ( dgettext ( 'core', 'Global application settings file does not exist yet. You should setup your app first:' ) . ' ./fragtale Console/Setup', MessageType::ERROR );
			}
			$this->CliApplicationSettings = (new DataCollection ( json_decode ( file_get_contents ( CorePath::APP_SETTINGS_FILE ), true ) ));
		}
		return $this->CliApplicationSettings;
	}

	/**
	 * Collection containing hosts and projects bindings from file "resources/configuration/hosts.json"
	 *
	 * @return DataCollection
	 */
	final public function getHostsSettings(): DataCollection {
		if (! $this->HostsSettings instanceof DataCollection) {
			$hosts = file_exists ( CorePath::HOSTS_SETTINGS_FILE ) ? json_decode ( file_get_contents ( CorePath::HOSTS_SETTINGS_FILE ), true ) : null;
			$this->HostsSettings = new DataCollection ( $hosts );
		}
		return $this->HostsSettings;
	}

	/**
	 * You can check your current Unix locale by typing in your terminal: locale
	 *
	 * @param string $locale
	 *        	Such as en_US, en_GB, fr_FR...
	 * @param string $encoding
	 *        	By default: UTF-8
	 * @return self
	 */
	public function setLocale(?string $locale, ?string $encoding = 'UTF-8'): self {
		if (! $locale || Locale::getConstants ()->position ( $locale ) === null)
			$locale = Locale::getConstants ()->key ( 0 );
		if ($encoding)
			$locale .= ".$encoding";
		putenv ( "LANG=$locale" );
		putenv ( "LANGUAGE=$locale" );
		putenv ( "LC_MESSAGES=$locale" );
		putenv ( "LC_ALL=$locale" );
		setlocale ( LC_ALL, $locale );
		return $this;
	}

	/**
	 *
	 * @param string $domain
	 * @param string $locale_dir
	 * @return self
	 */
	public function setGettext(string $domain = 'messages', ?string $locale_dir = null): self {
		if (! $locale_dir)
			$locale_dir = CorePath::RESOURCES_DIR . '/locales';
		bindtextdomain ( $domain, $locale_dir );
		textdomain ( $domain );
		return $this;
	}
}