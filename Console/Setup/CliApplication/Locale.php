<?php

namespace Console\Setup\CliApplication;

use Console\Setup\CliApplication;
use FragTale\DataCollection;
use FragTale\Service\Cli;
use FragTale\Constant\Setup\Locale as LocaleConstant;

/**
 *
 * @author Fabrice Dant
 *        
 */
class Locale extends CliApplication {
	const DEFAULT_ENCODING = 'UTF-8';
	protected function executeOnConsole(): void {
		$this->CliService->printInColor ( dgettext ( 'core', 'Manage CLI application locale and encoding' ), Cli::COLOR_YELLOW );
		$this->setupLocale ( $this->CliApplicationSettings );
	}

	/**
	 *
	 * @param DataCollection $Settings
	 */
	public function setupLocale(DataCollection $Settings): void {
		$curLocale = $Settings->findByKey ( 'locale' );
		$curEncoding = $Settings->findByKey ( 'encoding' );
		$defaultEncoding = $curEncoding ? $curEncoding : self::DEFAULT_ENCODING;
		$locales = [ ];
		$defaultLocaleIndex = 1;
		LocaleConstant::getConstants ()->forEach ( function ($locale, $elt) use (&$locales, &$defaultLocaleIndex, $curLocale) {
			if ($elt instanceof DataCollection) {
				if ($locale === $curLocale)
					$defaultLocaleIndex = LocaleConstant::getConstants ()->position ( $locale ) + 1;
				$locales [$locale] = $elt->findByKey ( 'language' ) . ' (' . $elt->findByKey ( 'country' ) . ')';
			}
		} );
		if ($locale = $this->promptToFindElementInCollection ( dgettext ( 'core', _ ( 'Select locale from the list below:' ) ), new DataCollection ( $locales ), $defaultLocaleIndex, true )) {
			$this->CliService->print ( $locale );
			if ($locale !== $curLocale)
				$Settings->upsert ( 'locale', $locale );
		}
		if ($encoding = $this->CliService->prompt ( sprintf ( dgettext ( 'core', 'Encoding (%s preferable):' ), self::DEFAULT_ENCODING ), $defaultEncoding )) {
			$this->CliService->print ( $encoding );
			if ($encoding !== $curEncoding)
				$Settings->upsert ( 'encoding', $encoding );
		}
		$this->getSuperServices ()
			->getConfigurationService ()
			->setLocale ( $locale, $encoding )
			->setGettext ( 'core' );
	}

	/**
	 * Save conf file
	 */
	protected function executeOnBottom(): void {
		$this->saveApplicationConfigurationFile ();
	}
}