<?php

namespace FragTale\Application;

use FragTale\Application;
use FragTale\Implement\Application\BlockTrait;
use FragTale\Constant\TemplateFormat;
use FragTale\Template;
use FragTale\Constant\Setup\CorePath;
use FragTale\Constant\Setup\ControllerType;

/**
 * Main class to be extended by all controllers.
 *
 * @author Fabrice Dant <fragtale.development@gmail.com>
 * @copyright 2024 FragTale 2 - Fabrice Dant
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-fr.txt CeCILL Licence 2.1 (French version)
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-en.txt CeCILL Licence 2.1 (English version)
 *         
 */
class Controller extends Application {
	use BlockTrait;

	/**
	 *
	 * @var Template
	 */
	private Template $Template;

	/**
	 *
	 * @return Template
	 */
	final public function getTemplate(): Template {
		return $this->setTemplate ()->Template;
	}

	/**
	 *
	 * @param Template $Template
	 * @return self
	 */
	private function setTemplate(?Template $Template = null): self {
		if ($Template)
			$this->Template = $Template;
		elseif (! isset ( $this->Template )) {
			$ProjectService = $this->getSuperServices ()->getProjectService ();
			$title = $ProjectService->getName ();
			// Define the defaults
			$templateFormatId = IS_CLI ? TemplateFormat::PLAIN_TEXT : $ProjectService->getDefaultFormatId ();
			$defaultLayoutPath = IS_CLI ? null : $ProjectService->getDefaultLayoutPath ();
			$defaultTemplatePath = $ProjectService->getDefaultTemplatePath ();
			// Here, prior view is the one having similar path than controller
			// A template path can point a web view or a block
			$controllerNamespace = $ProjectService->getBaseControllerNamespace ();
			$uri = ( string ) $this->getSuperServices ()->getRouteService ()->convertNamespaceToUri ( str_replace ( $controllerNamespace, '', get_class ( $this ) ) );
			$uriParts = explode ( '/', $uri );
			switch (array_shift ( $uriParts )) {
				case strtolower ( ControllerType::CLI ) :
					$defaultTemplatePath = CorePath::TEXT_TEMPLATE_PATH;
					break;
				case strtolower ( ControllerType::WEB ) :
					$fullViewPath = $ProjectService->getViewsDir () . '/' . implode ( '/', $uriParts ) . '.phtml';
					if (file_exists ( $fullViewPath ))
						$defaultTemplatePath = $fullViewPath;
					break;
				case strtolower ( ControllerType::BLOCK ) :
					$templateFormatId = TemplateFormat::HTML_NO_LAYOUT;
					$fullBlockPath = $ProjectService->getBlocksDir () . '/' . implode ( '/', $uriParts ) . '.phtml';
					if (file_exists ( $fullBlockPath ))
						$defaultTemplatePath = $fullBlockPath;
					break;
			}
			$this->Template = (new Template ( $title, $defaultTemplatePath, $defaultLayoutPath ))->setFormatId ( $templateFormatId );
		}
		return $this;
	}

	/**
	 * Check if this controller is the main controller.
	 * The main controller is the requested controller called via URL or CLI.
	 * All controllers called inside another controller (for example, a block) are not main controllers.
	 *
	 * @return bool
	 */
	final public function isMainController(): bool {
		return $this->getSuperServices ()->getRouteControllerFactoryService ()->getMainController () === $this;
	}

	/**
	 * This is the main application sequence.
	 *
	 * @param bool $asBlock
	 *        	It is passed at true when controller is run as block (by calling function "getBlock")
	 * @return View
	 */
	public function run(?bool $asBlock = null): View {
		try {
			$this->executeOnTop ();
		} catch ( \Throwable $T ) {
			$this->getSuperServices ()->getErrorHandlerService ()->catchThrowable ( $T );
		}
		if (IS_CLI && ! $asBlock) {
			$this->executeOnConsole ();
			$this->executeOnBottom ();
			return new View ( $this->getTemplate ()->setFormatId ( TemplateFormat::PLAIN_TEXT ) );
		} else {
			$isMainController = false;
			try {
				if ($method = $this->getSuperServices ()->getHttpRequestService ()->getMethod ()) {
					$this->executeBeforeHttpRequestMethod ();
					$this->executeOnHttpRequestMethod ( $method );
					$this->executeAfterHttpRequestMethod ();
				} elseif (IS_CLI)
					$this->executeOnConsole ();
				$this->executeOnBottom ();
			} catch ( \Throwable $T ) {
				$this->getSuperServices ()->getErrorHandlerService ()->catchThrowable ( $T );
			}
			// Only render layout on main controller (because other controllers can be instantiated and run, for example while calling blocks)
			if (! $asBlock)
				$isMainController = $this->getSuperServices ()->getRouteControllerFactoryService ()->isMainController ( $this );
			elseif ($templateFormatId = $this->getTemplate ()->getVar ( 'template_format_id' ))
				$this->getTemplate ()->setFormatId ( $templateFormatId );
			// Rendering comes after all
			return (new View ( $this->getTemplate () ))->generateContent ( $isMainController );
		}
	}

	/**
	 * Not overridable.
	 * This function is called on application start up.
	 * You should not have to call this function in your code.
	 */
	final protected function executeOnHttpRequestMethod(string $method, array $funcArgs = null): void {
		$method = ucfirst ( strtolower ( $method ) );
		$funcName = 'executeOnHttpRequestMethod' . $method;
		if (method_exists ( $this, $funcName ))
			$this->$funcName ( $funcArgs ? extract ( $funcArgs ) : null );
		else
			throw (new \Exception ( __METHOD__ . ' ' . dgettext ( 'core', 'Unknown function' ) . $funcName ));
	}

	/**
	 * To be overrided in each inherited controller, if needed.
	 */
	protected function executeOnTop(): void {
	}

	/**
	 * To be overrided in each inherited controller, if needed.
	 */
	protected function executeOnBottom(): void {
	}

	/**
	 * To be overrided in each inherited controller, if needed.
	 */
	protected function executeOnHttpRequestMethodConnect(): void {
	}

	/**
	 * To be overrided in each inherited controller, if needed.
	 */
	protected function executeOnHttpRequestMethodDelete(): void {
	}

	/**
	 * To be overrided in each inherited controller, if needed.
	 */
	protected function executeOnHttpRequestMethodGet(): void {
	}

	/**
	 * To be overrided in each inherited controller, if needed.
	 */
	protected function executeOnHttpRequestMethodHead(): void {
	}

	/**
	 * To be overrided in each inherited controller, if needed.
	 */
	protected function executeOnHttpRequestMethodOptions(): void {
	}

	/**
	 * To be overrided in each inherited controller, if needed.
	 */
	protected function executeOnHttpRequestMethodPatch(): void {
	}

	/**
	 * To be overrided in each inherited controller, if needed.
	 */
	protected function executeOnHttpRequestMethodPost(): void {
	}

	/**
	 * To be overrided in each inherited controller, if needed.
	 */
	protected function executeOnHttpRequestMethodPut(): void {
	}

	/**
	 * To be overrided in each inherited controller, if needed.
	 */
	protected function executeOnHttpRequestMethodTrace(): void {
	}

	/**
	 * To be overrided in each inherited controller, if needed.
	 */
	protected function executeBeforeHttpRequestMethod(): void {
	}

	/**
	 * To be overrided in each inherited controller, if needed.
	 */
	protected function executeAfterHttpRequestMethod(): void {
	}

	/**
	 * Instructions executed only in CLI.
	 * To be overrided in each inherited controller, if needed.
	 */
	protected function executeOnConsole(): void {
	}
}