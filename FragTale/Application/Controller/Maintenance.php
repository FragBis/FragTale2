<?php

namespace FragTale\Application\Controller;

use FragTale\Application\Controller;
use FragTale\Constant\Setup\CorePath;

/**
 *
 * @author Fabrice Dant <fragtale.development@gmail.com>
 * @copyright 2024 FragTale 2 - Fabrice Dant
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-fr.txt CeCILL Licence 2.1 (French version)
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-en.txt CeCILL Licence 2.1 (English version)
 *         
 */
class Maintenance extends Controller {
	function __construct() {
		parent::__construct ();
		$this->getTemplate ()->setPath ( CorePath::DEFAULT_MAINTENANCE_PATH );
	}
}