<?php
/**
 *
 * FragTale 2.1 Open Source PHP Framework
 *
 * @author Fabrice Dant
 * @copyright FragTale 2024
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-fr.txt CeCILL Licence 2.1 (French version)
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-en.txt CeCILL Licence 2.1 (English version)
 *         
 */

/**
 * For measuring PHP process time
 */
define ( 'APP_START_TIME', microtime ( true ) );

/**
 * Application is requested by HTTP client
 */
define ( 'IS_HTTP_REQUEST', true );
define ( 'IS_CLI', false );

require_once '../main.php';