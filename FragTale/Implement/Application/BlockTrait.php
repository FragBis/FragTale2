<?php

namespace FragTale\Implement\Application;

use FragTale\DataCollection;
use FragTale\Application\Controller;

/**
 *
 * @author Fabrice Dant <fragtale.development@gmail.com>
 * @copyright 2024 FragTale 2 - Fabrice Dant
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-fr.txt CeCILL Licence 2.1 (French version)
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-en.txt CeCILL Licence 2.1 (English version)
 *         
 */
trait BlockTrait {

	/**
	 * Process the given controller and return the view rendering
	 *
	 * @param string $controllerClassName
	 *        	Such as: Project\MyProject\Controller\Home::class
	 * @param iterable $TemplateVarsAndObjects
	 *        	Link template variables between different objects and their children. This iterable must be a key/value matrix.
	 *        	You can include objects to this list.
	 *        	Note that DataCollections passed as template var DO NOT KEEP their reference (they are cloned). But any else object will be passed by reference.
	 * @return string
	 */
	public function getBlock(string $controllerClassName, ?iterable $TemplateVarsAndObjects = null): ?string {
		if (class_exists ( $controllerClassName )) {
			$Controller = new $controllerClassName ();
			if ($Controller instanceof Controller) {
				if ($TemplateVarsAndObjects instanceof DataCollection)
					$Controller->getTemplate ()->setVars ( $TemplateVarsAndObjects->clone () );
				elseif ($TemplateVarsAndObjects)
					foreach ( $TemplateVarsAndObjects as $key => $value )
						if (is_object ( $value ) && ! ($value instanceof DataCollection))
							$Controller->getTemplate ()->setObject ( $key, $value );
						else
							$Controller->getTemplate ()->setVar ( $key, $value );
				return $Controller->run ( true )->getContent ();
			}
		}
		return null;
	}
}