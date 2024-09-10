<?php

namespace FragTale\Constant\Setup;

use FragTale\Constant;

/**
 *
 * @author Fabrice Dant <fragtale.development@gmail.com>
 * @copyright 2024 FragTale 2 - Fabrice Dant
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-fr.txt CeCILL Licence 2.1 (French version)
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-en.txt CeCILL Licence 2.1 (English version)
 *         
 */
abstract class Locale extends Constant {
	const en_US = [ 
			'code' => 'en_US',
			'short_code' => 'en',
			'long_code' => 'en-us',
			'language' => 'English',
			'country' => 'United States',
			'country_code' => 'US',
			'currency_code' => 'USD',
			'currency' => 'Dollar',
			'currency_sym' => '$',
			'date_format' => 'Y-m-d',
			'datetime_format' => 'Y-m-d H:i',
			'datetime_sec_format' => 'Y-m-d H:i:s',
			'long_date_format' => 'EEEE, d MMMM y',
			'long_datetime_format' => 'EEEE, d MMMM y %H:%M',
			'decimal_separator' => '.',
			'thousand_separator' => ','
	];
	const fr_FR = [ 
			'code' => 'fr_FR',
			'short_code' => 'fr',
			'long_code' => 'fr-fr',
			'language' => 'French',
			'country' => 'France',
			'country_code' => 'FR',
			'currency_code' => 'EUR',
			'currency' => 'Euro',
			'currency_sym' => '€',
			'date_format' => 'd/m/Y',
			'datetime_format' => 'd/m/Y H:i',
			'datetime_sec_format' => 'd/m/Y H:i:s',
			'long_date_format' => 'EEEE d MMMM y',
			'long_datetime_format' => "EEEE d MMMM y 'à' HH:mm",
			'decimal_separator' => ',',
			'thousand_separator' => ' '
	];
}