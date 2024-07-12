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
abstract class CustomProjectPattern extends Constant {
	const PATH = CorePath::PROJECT_ROOT . '/%s';
	const MODEL_DIR = self::PATH . '/Model';
	const SQL_MODEL_DIR = self::MODEL_DIR . '/Sql';
	const RESOURCES_DIR = self::PATH . '/resources';
	const CONFIGURATION_DIR = self::RESOURCES_DIR . '/configuration';
	const TEMPLATES_DIR = self::RESOURCES_DIR . '/templates';
	const LAYOUTS_DIR = self::TEMPLATES_DIR . '/layouts';
	const VIEWS_DIR = self::TEMPLATES_DIR . '/views';
	const BLOCKS_DIR = self::TEMPLATES_DIR . '/blocks';
	const CODE_PATTERNS_DIR = self::TEMPLATES_DIR . '/patterns';
	const MEDIA_DIR = self::RESOURCES_DIR . '/media';
	const SETTINGS_FILE = self::CONFIGURATION_DIR . '/project.json';
	const NAMESPACE = CorePath::BASE_PROJECT_NAMESPACE . '\\%s';
	const SQL_MODEL_NAMESPACE = self::NAMESPACE . '\\Model\\Sql';
	const CONTROLLER_NAMESPACE = self::NAMESPACE . '\\Controller';
	const CONTROLLER_DIR = self::PATH . '/Controller';
	const WEB_CONTROLLER_NAMESPACE = self::CONTROLLER_NAMESPACE . '\\' . ControllerType::WEB;
	const BLOCK_CONTROLLER_NAMESPACE = self::CONTROLLER_NAMESPACE . '\\' . ControllerType::BLOCK;
	const CLI_CONTROLLER_NAMESPACE = self::CONTROLLER_NAMESPACE . '\\' . ControllerType::CLI;
	const WEB_CONTROLLER_DIR = self::CONTROLLER_DIR . '/' . ControllerType::WEB;
	const BLOCK_CONTROLLER_DIR = self::CONTROLLER_DIR . '/' . ControllerType::BLOCK;
	const CLI_CONTROLLER_DIR = self::CONTROLLER_DIR . '/' . ControllerType::CLI;
	const PATTERN_FORM_CONTROLLER_ENTITY_LIST = self::CODE_PATTERNS_DIR . '/form/list/controller.pattern';
	const PATTERN_FORM_CONTROLLER_ENTITY_ACTION = self::CODE_PATTERNS_DIR . '/form/action/controller.pattern';
	const PATTERN_FORM_TEMPLATE_ENTITY_LIST = self::CODE_PATTERNS_DIR . '/form/list/view.pattern';
	const PATTERN_FORM_TEMPLATE_ENTITY_ACTION = self::CODE_PATTERNS_DIR . '/form/action/view.pattern';
}