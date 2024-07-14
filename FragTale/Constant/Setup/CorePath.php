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
abstract class CorePath extends Constant {
	const BASE_PROJECT_NAMESPACE = 'Project';
	const PROJECT_ROOT = APP_ROOT . '/' . self::BASE_PROJECT_NAMESPACE;
	const CONSOLE_DIR = APP_ROOT . '/Console';
	const MODULE_DIR = APP_ROOT . '/Module';
	const PUBLIC_DIR = APP_ROOT . '/public';
	const RESOURCES_DIR = APP_ROOT . '/resources';
	const CONFIGURATION_DIR = self::RESOURCES_DIR . '/configuration';
	const APP_SETTINGS_FILE = self::CONFIGURATION_DIR . '/application.json';
	const HOSTS_SETTINGS_FILE = self::CONFIGURATION_DIR . '/hosts.json';
	const LOG_DIR = APP_ROOT . '/logs';
	const LOCALES_DIR = self::RESOURCES_DIR . '/locales';
	const MEDIA_DIR = self::RESOURCES_DIR . '/media';
	const CODE_PATTERNS_DIR = self::RESOURCES_DIR . '/code_patterns';
	const TEMPLATE_DIR = self::RESOURCES_DIR . '/templates';
	const DEFAULT_TEMPLATE_PATH = self::TEMPLATE_DIR . '/view.phtml';
	const DEFAULT_LAYOUT_PATH = self::TEMPLATE_DIR . '/layout.phtml';
	const DEFAULT_404_PATH = self::TEMPLATE_DIR . '/404.phtml';
	const DEFAULT_MAINTENANCE_PATH = self::TEMPLATE_DIR . '/maintenance.phtml';
	const JSON_TEMPLATE_PATH = self::TEMPLATE_DIR . '/json.phtml';
	const XML_TEMPLATE_PATH = self::TEMPLATE_DIR . '/xml.phtml';
	const TEXT_TEMPLATE_PATH = self::TEMPLATE_DIR . '/text.phtml';
	const DEBUG_TEMPLATE_PATH = self::TEMPLATE_DIR . '/debug.phtml';
	const PATTERN_WEB_CONTROLLER = self::CODE_PATTERNS_DIR . '/php/webcontroller.php.pattern';
	const PATTERN_CLI_CONTROLLER = self::CODE_PATTERNS_DIR . '/php/clicontroller.php.pattern';
	const PATTERN_BLOCK_CONTROLLER = self::CODE_PATTERNS_DIR . '/php/blockcontroller.php.pattern';
	const PATTERN_MODEL_E = self::CODE_PATTERNS_DIR . '/php/e_.php.pattern';
	const PATTERN_MODEL_M = self::CODE_PATTERNS_DIR . '/php/m_.php.pattern';
	const PATTERN_MODEL_T = self::CODE_PATTERNS_DIR . '/php/t_.php.pattern';
	const PATTERN_DEFAULT_TEMPLATE_PATH = self::CODE_PATTERNS_DIR . '/phtml/view.phtml.pattern';
	const PATTERN_PROJECT_TREE_FILE = self::CODE_PATTERNS_DIR . '/json/project_tree.json.pattern';
	const PATTERN_PROJECT_SETUP_FILE = self::CODE_PATTERNS_DIR . '/json/project.json.pattern';
	const PATTERN_APP_SETUP_FILE = self::CODE_PATTERNS_DIR . '/json/application.json.pattern';
	const PATTERN_ENVIRONMENT_SETUP_FILE = self::CODE_PATTERNS_DIR . '/json/environment.json.pattern';
	const PATTERN_FORM_CONTROLLER_ENTITY_LIST = self::CODE_PATTERNS_DIR . '/php/form_list.php.pattern';
	const PATTERN_FORM_CONTROLLER_ENTITY_ACTION = self::CODE_PATTERNS_DIR . '/php/form_action.php.pattern';
	const PATTERN_FORM_TEMPLATE_ENTITY_LIST = self::CODE_PATTERNS_DIR . '/phtml/form_list.phtml.pattern';
	const PATTERN_FORM_TEMPLATE_ENTITY_ACTION = self::CODE_PATTERNS_DIR . '/phtml/form_action.phtml.pattern';
	const MODULES_LIST = self::CODE_PATTERNS_DIR . '/json/modules.json';
}