<?php

namespace FragTale\Service;

use FragTale\Implement\AbstractService;
use FragTale\Service\Project\CliPurpose;

/**
 *
 * @author Fabrice Dant <fragtale.development@gmail.com>
 * @copyright 2024 FragTale 2 - Fabrice Dant
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-fr.txt CeCILL Licence 2.1 (French version)
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-en.txt CeCILL Licence 2.1 (English version)
 *         
 */
class Localize extends AbstractService {

	/**
	 *
	 * @param string $char
	 * @return bool
	 */
	public function meansNo(?string $char): bool {
		return in_array ( strtolower ( trim ( ( string ) $char ) ), [ 
				// For locale purpose, we need to translate "n" to different languages
				'no',
				'n {means no}',
				dgettext ( 'core', 'no' ),
				dgettext ( 'core', 'n {means no}' ),
				'false',
				'0',
				'n',
				'non',
				'ko',
				'off',
				'',
				'null',
				null
		] );
	}

	/**
	 *
	 * @param string $char
	 * @return bool
	 */
	public function meansYes(?string $char): bool {
		return in_array ( strtolower ( trim ( ( string ) $char ) ), [ 
				// For locale purpose, we need to translate "y" to different languages
				'yes',
				'y {means yes}',
				dgettext ( 'core', 'yes' ),
				dgettext ( 'core', 'y {means yes}' ),
				'true',
				'1',
				'y',
				'o',
				'oui',
				'ok',
				'on'
		] );
	}

	/**
	 *
	 * @param int $timestamp
	 * @return string
	 */
	public function toLocaleDate(?int $timestamp): string {
		if (! $timestamp)
			return '';
		$dateformat = 'Y-m-d';
		$Locale = IS_CLI ? $this->getService ( CliPurpose::class )->getLocaleAdditionalProperties () : $this->getSuperServices ()->getProjectService ()->getLocaleAdditionalProperties ();
		if ($Locale && $Locale->findByKey ( 'date_format' ))
			$dateformat = $Locale->findByKey ( 'date_format' );
		return date ( $dateformat, $timestamp );
	}

	/**
	 *
	 * @param int $timestamp
	 * @param bool $withSeconds
	 * @return string
	 */
	public function toLocaleDateTime(?int $timestamp, bool $withSeconds = false): string {
		if (! $timestamp)
			return '';
		$dateformat = $withSeconds ? 'Y-m-d H:i:s' : 'Y-m-d H:i';
		$Locale = IS_CLI ? $this->getService ( CliPurpose::class )->getLocaleAdditionalProperties () : $this->getSuperServices ()->getProjectService ()->getLocaleAdditionalProperties ();
		if ($Locale && $Locale->findByKey ( 'datetime_sec_format' ) && $Locale->findByKey ( 'datetime_format' ))
			$dateformat = $withSeconds ? $Locale->findByKey ( 'datetime_sec_format' ) : $Locale->findByKey ( 'datetime_format' );
		return date ( $dateformat, $timestamp );
	}

	/**
	 *
	 * @param int $timestamp
	 * @return string
	 */
	/**
	 *
	 * @param int $timestamp
	 * @param string $dateformat
	 *        	Something like "EEEE, d MMMM y"
	 * @return string
	 */
	public function toLocaleLongDate(?int $timestamp, ?string $dateformat = null): string {
		if (! $timestamp)
			return '';
		$Locale = IS_CLI ? $this->getService ( CliPurpose::class )->getLocaleAdditionalProperties () : $this->getSuperServices ()->getProjectService ()->getLocaleAdditionalProperties ();
		if (! $dateformat)
			$dateformat = $Locale && $Locale->findByKey ( 'long_date_format' ) ? $Locale->findByKey ( 'long_date_format' ) : 'EEEE, d MMMM y';
		$lang = $Locale && $Locale->findByKey ( 'code' ) ? $Locale->findByKey ( 'code' ) : locale_get_default ();
		$Formatter = new \IntlDateFormatter ( $lang );
		if ($dateformat)
			$Formatter->setPattern ( $dateformat );
		return $Formatter->format ( $timestamp );
	}

	/**
	 *
	 * @param int $timestamp
	 * @param string $dateformat
	 *        	Something like "EEEE, d MMMM y HH:mm"
	 * @return string
	 */
	public function toLocaleLongDateTime(?int $timestamp, ?string $dateformat = null): string {
		if (! $timestamp)
			return '';
		if (! $dateformat) {
			$Locale = IS_CLI ? $this->getService ( CliPurpose::class )->getLocaleAdditionalProperties () : $this->getSuperServices ()->getProjectService ()->getLocaleAdditionalProperties ();
			$dateformat = $Locale && $Locale->findByKey ( 'long_datetime_format' ) ? $Locale->findByKey ( 'long_datetime_format' ) : 'EEEE, d MMMM y HH:mm';
		}
		return $this->toLocaleLongDate ( $timestamp, $dateformat );
	}

	/**
	 * Display numbers in locale format.
	 *
	 * @param float $number
	 * @param int|null $nbDecimals
	 *        	Max decimal numbers
	 * @param bool $fixed
	 *        	If true, nb decimals is displayed to fixed, e.g: if $nbDecimals === 2, displayed format number is 00.00
	 * @return string
	 */
	public function toLocaleNumber(?float $number, ?int $nbDecimals = null, bool $fixed = false): string {
		if (! $number)
			$number = 0;
		$Locale = IS_CLI ? $this->getService ( CliPurpose::class )->getLocaleAdditionalProperties () : $this->getSuperServices ()->getProjectService ()->getLocaleAdditionalProperties ();
		if ($Locale && $Locale->count ()) {
			$decSep = $Locale->findByKey ( 'decimal_separator' );
			$thSep = $Locale->findByKey ( 'thousand_separator' );
		} else {
			$locale = localeconv ();
			$decSep = $locale ['decimal_point'];
			$thSep = $locale ['thousands_sep'];
		}
		if ($nbDecimals === null) {
			// decimal count
			$decSepPos = strpos ( ( string ) $number, $decSep );
			if ($decSepPos !== false)
				$nbDecimals = strlen ( substr ( ( string ) $number, $decSepPos + 1 ) );
		}
		if ($nbDecimals === null)
			$nbDecimals = 0;
		$returnedNumber = number_format ( $number, $nbDecimals, $decSep, $thSep );
		return $fixed || strpos ( $returnedNumber, $decSep ) === false ? $returnedNumber : $this->trimNumber ( $returnedNumber, $decSep );
	}

	/**
	 * Convert from locale to default number format.
	 * "Machine" number means that there is no thousand separator and '.' is the decimal separator.
	 * It can parse "12,000.001" to "12000.001", or "12 000,001" to "12000.001"
	 *
	 * @param string $number
	 * @param int $nbDecimals
	 *        	If null, the parser keeps the initial decimal count. If you pass 0, it will return an integer
	 * @param bool $fixed
	 *        	If true, nb decimals is displayed to fixed, e.g: if $nbDecimals === 2, displayed format number is 00.00
	 * @return string
	 */
	public function toMachineNumber(?string $number, int $nbDecimals = 0, bool $fixed = false): string {
		if (! $number)
			return '0';
		$Locale = IS_CLI ? $this->getService ( CliPurpose::class )->getLocaleAdditionalProperties () : $this->getSuperServices ()->getProjectService ()->getLocaleAdditionalProperties ();
		if ($Locale && $Locale->count ()) {
			$decSep = $Locale->findByKey ( 'decimal_separator' );
			$thSep = $Locale->findByKey ( 'thousand_separator' );
		} else {
			$locale = localeconv ();
			$decSep = $locale ['decimal_point'];
			$thSep = $locale ['thousands_sep'];
		}
		$number = str_replace ( [ 
				$decSep,
				$thSep
		], [ 
				'',
				'.'
		], $number );
		$returnedNumber = $nbDecimals ? number_format ( ( float ) $number, $nbDecimals, '.', '' ) : $number;
		return $fixed || strpos ( $returnedNumber, '.' ) === false ? $returnedNumber : $this->trimNumber ( $returnedNumber, '.' );
	}

	/**
	 * Remove trailing zeros after decimal separator
	 *
	 * @param string $number
	 * @param string $decimalSeparator
	 * @return string
	 */
	public function trimNumber(?string $number, string $decimalSeparator = '.'): string {
		if (in_array ( $number, [ 
				'',
				null
		], true ))
			return '';
		if ($number == 0)
			return '0';
		if (strpos ( $number, $decimalSeparator ) === false)
			return "$number";
		while ( substr ( $number, - 1 ) === '0' )
			$number = rtrim ( $number, '0' );
		return rtrim ( $number, $decimalSeparator );
	}

	/**
	 * Truncate string at max length given by 2nd argument.
	 * Last word is not truncated as far as possible.
	 * If passed $string length is higher than $length, returned value takes ... at the end.
	 *
	 * @param string $string
	 *        	The string to truncate
	 * @param int $length
	 *        	Max chars limit
	 * @param bool $strict
	 *        	If true, it will truncate exactly at the string at the given length. If false (by default), it will truncate and remove the last truncated word (this will return full words)
	 * @return string
	 */
	public function truncateAndEllipsis(?string $string, int $length = 50, bool $strict = false): string {
		if (! $string)
			return '';
		if (strlen ( $string ) <= $length)
			return "$string";
		$string = substr ( $string, 0, $length );
		if ($strict)
			return "$string...";
		$expr = explode ( ' ', $string );
		if (count ( $expr ) > 1)
			array_pop ( $expr );
		return implode ( ' ', $expr ) . '...';
	}
}