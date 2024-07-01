<?php

namespace FragTale\Application;

use FragTale\Application;
use FragTale\Template;
use FragTale\Constant\Setup\CorePath;
use FragTale\Constant\MessageType;
use FragTale\Constant\TemplateFormat;
use FragTale\Implement\Application\BlockTrait;

/**
 *
 * @author Fabrice Dant <fragtale.development@gmail.com>
 * @copyright 2024 FragTale 2 - Fabrice Dant
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-fr.txt CeCILL Licence 2.1 (French version)
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-en.txt CeCILL Licence 2.1 (English version)
 *         
 */
class View extends Application {
	use BlockTrait;

	/**
	 *
	 * @var Template
	 */
	private Template $Template;

	/**
	 *
	 * @var string
	 */
	private string $content;

	/**
	 * Count each time generateContent had been launched for this View
	 *
	 * @var integer
	 */
	private int $renderIter = 0;

	/**
	 * This object stores all the usefull informations to render the Web page.
	 * The controller can set new Template variables intending to display specific informations.
	 */
	function __construct(Template $Template) {
		$this->Template = $Template;
		$this->content = '';
	}

	/**
	 * Include the template (and/or the layout)
	 *
	 * @param string $templatePath
	 */
	private function includeTemplate(string $templatePath): void {
		// Using $View variable into template files (here, $this and $View are same instance)
		$View = $this;

		// Using buffer
		ob_start ();
		try {
			include $templatePath; // Template file is directly included in this function, so the template file - as it is a PHP file - is able to use this class methods and properties, even privates.
		} catch ( \Throwable $T ) {
			$this->getSuperServices ()->getErrorHandlerService ()->catchThrowable ( $T );
		}
		$View->content = ob_get_clean ();
	}

	/**
	 * Generate content to send to client.
	 *
	 * @param bool $isFromMainController
	 * @return self
	 */
	public function generateContent(bool $isFromMainController = false): self {
		$this->renderIter ++;
		$startTime = microtime ( true );

		// Prevent this function to be called recursively within its own template file
		if ($this->renderIter > 1) {
			if (function_exists ( 'debug_backtrace' ) && ($backTrace = debug_backtrace ( DEBUG_BACKTRACE_IGNORE_ARGS )) && isset ( $backTrace [0] ['file'] ) && str_replace ( APP_ROOT . '/', '', $backTrace [0] ['file'] ) === $this->Template->getPath ()) {
				$this->getSuperServices ()->getErrorHandlerService ()->catchThrowable ( new \Exception ( dgettext ( 'core', 'You cannot recursively call "generateContent" function from inside its template' ) ) );
				return $this;
			}
		}

		$RequestService = $this->getSuperServices ()->getHttpRequestService ();
		$ResponseService = $this->getSuperServices ()->getHttpResponseService ();
		$DebugService = $this->getSuperServices ()->getDebugService ();
		$debugModeOn = $DebugService->isActivated ();

		// You can pass request param "template_format_id" to force use of specified template format
		// For example, calling web page via AJAX request: set "template_format_id=2" to retrieve HTML output without the page layout
		if ($isFromMainController) {
			if ($templateFormatId = $RequestService->getParamValue ( 'template_format_id' )) {
				$this->Template->setFormatId ( $templateFormatId );
			}
		}

		try {
			switch ($this->Template->getFormatId ()) {
				case TemplateFormat::HTML :
					if ($isFromMainController)
						$ResponseService->addHeader ( 'Content-Type:text/html' );
					break;
				case TemplateFormat::HTML_NO_LAYOUT :
					if ($isFromMainController)
						$ResponseService->addHeader ( 'Content-Type:text/html' );
					$this->Template->setLayoutPath ( null );
					break;
				case TemplateFormat::HTML_DEBUG :
					if ($isFromMainController)
						$ResponseService->addHeader ( 'Content-Type:text/html' );
					if ($debugModeOn)
						$this->Template->setPath ( CorePath::DEBUG_TEMPLATE_PATH );
					break;
				case TemplateFormat::JSON :
					if ($isFromMainController)
						$ResponseService->addHeader ( 'Content-Type:application/json' );
					$this->Template->setLayoutPath ( null )->setPath ( CorePath::JSON_TEMPLATE_PATH );
					if ($debugModeOn) {
						$DebugInfo = $DebugService->setDebugInfo ( 'Template: ' . $this->Template->getPath (), $this->Template->getVars () )
							->setDebugInfo ( 'Template: ' . $this->Template->getPath (), $this->Template->getObjects (), 'Objects' )
							->getDebugInfo ();
						$this->Template->setVar ( '_DEBUG_' . substr ( md5 ( microtime ( true ) ), 0, 8 ), $DebugInfo );
					}
					break;
				case TemplateFormat::XML :
					if ($isFromMainController)
						$ResponseService->addHeader ( 'Content-Type:application/xml' );
					$this->Template->setLayoutPath ( null )->setPath ( CorePath::XML_TEMPLATE_PATH );
					if ($debugModeOn) {
						$DebugInfo = $DebugService->setDebugInfo ( 'Template: ' . $this->Template->getPath (), $this->Template->getVars () )
							->setDebugInfo ( 'Template: ' . $this->Template->getPath (), $this->Template->getObjects (), 'Objects' )
							->getDebugInfo ();
						$this->Template->setVar ( '_DEBUG_' . substr ( md5 ( microtime ( true ) ), 0, 8 ), $DebugInfo );
					}
					break;
				case TemplateFormat::MEDIA :
					// Headers should have been sent from Media controller
					$this->Template->setLayoutPath ( null );
					break;
				case TemplateFormat::PLAIN_TEXT :
				default :
					if ($isFromMainController)
						$ResponseService->addHeader ( 'Content-Type:text/plain' );
					$this->Template->setLayoutPath ( null )->setPath ( CorePath::TEXT_TEMPLATE_PATH );
					break;
			}
			$templateFile = $this->Template->getPath () ? $this->Template->getPath () : CorePath::DEFAULT_TEMPLATE_PATH;
			$fullPath = file_exists ( $templateFile ) ? $templateFile : (file_exists ( APP_ROOT . "/$templateFile" ) ? APP_ROOT . "/$templateFile" : null);
			if (! $fullPath) {
				$this->getSuperServices ()->getFrontMessageService ()->add ( sprintf ( dgettext ( 'core', 'Trying to use inexisting template file: %s' ), $templateFile ), MessageType::ERROR );
				return $this->renderContent = '';
			}

			// include template
			$this->includeTemplate ( $fullPath );

			if ($isFromMainController) {
				if (($layoutPath = $this->Template->getLayoutPath ()) && ($fullPath = file_exists ( $layoutPath ) ? $layoutPath : (file_exists ( APP_ROOT . "/$layoutPath" ) ? APP_ROOT . "/$layoutPath" : null))) {
					// include layout (where previous template rendering is fractally included in this layout)
					$this->includeTemplate ( $fullPath );
				} elseif ($this->Template->getFormatId () === TemplateFormat::HTML_NO_LAYOUT) {
					// This case is for AJAX call
					$this->content .= $this->Template->getCssSourceTags () . $this->Template->getJsSourceTags ();
				}
			}

			if ($debugModeOn && in_array ( $this->Template->getFormatId (), [ 
					TemplateFormat::HTML,
					TemplateFormat::HTML_NO_LAYOUT
			] )) {
				$vars = [ 
						'LAYOUT' => $this->Template->getLayoutPath (),
						'VARIABLES' => $this->Template->getVars ()->getData ( true ),
						'OBJECTS' => $this->Template->getObjects (),
						'rendering_time' => number_format ( microtime ( true ) - $startTime, 5 ) . 's'
				];
				$DebugService->setDebugInfo ( 'Template: ' . $this->Template->getPath (), $vars );
			}
		} catch ( \Throwable $T ) {
			$this->getSuperServices ()->getErrorHandlerService ()->catchThrowable ( $T );
		}
		return $this;
	}

	// ## Tools
	/**
	 * HTML output.
	 * The view returns the rendering output.
	 *
	 * @return string
	 */
	public function getContent(): string {
		return $this->content;
	}
	public function __toString(): string {
		return $this->content;
	}
}