<?php

namespace Console\Setup\Etc;

use FragTale\Constant\Setup\CorePath;

/**
 *
 * @author Fabrice Dant
 *        
 */
class Nginx extends Apache {
	private const SERVER_APP_NAME = 'Nginx';
	private const CONF_PATTERN_FILE = CorePath::CODE_PATTERNS_DIR . '/etc/debian/nginx.pattern';

	/**
	 * Nginx
	 *
	 * @return self
	 */
	protected function getWebServerName(): string {
		return self::SERVER_APP_NAME;
	}
	/**
	 *
	 * @return self
	 */
	protected function getConfPatternFile(): string {
		return self::CONF_PATTERN_FILE;
	}
}