<?php

namespace FragTale\Service;

use FragTale\Implement\AbstractService;
use FragTale\DataCollection;
use FragTale\DataCollection\MongoCollection;
use FragTale\DataCollection\JsonCollection;
use FragTale\DataCollection\XmlCollection;
use MongoDB\Driver\WriteResult;
use MongoDB\Driver\WriteError;
use FragTale\Application\Model;
use FragTale\Application\Model\Dataset;

/**
 *
 * @author Fabrice Dant <fragtale.development@gmail.com>
 * @copyright 2024 FragTale 2 - Fabrice Dant
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-fr.txt CeCILL Licence 2.1 (French version)
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-en.txt CeCILL Licence 2.1 (English version)
 *         
 */
class Debug extends AbstractService {

	/**
	 *
	 * @var array
	 */
	private $debugInfo = [ ];

	/**
	 * Returns true if you have set "debug: 1" in your env settings (project.json).
	 * Activated, a debugger is included at the left bottom of your web page.
	 *
	 * @return bool
	 */
	public function isActivated(): bool {
		return IS_CLI || ( bool ) $this->getSuperServices ()
			->getProjectService ()
			->getEnvSettings ()
			->findByKey ( 'debug' );
	}

	/**
	 * Recursively build the debugger content.
	 *
	 * @param DataCollection $DebugBlock
	 * @return string
	 */
	private function buildDebugBlocks(DataCollection $DebugBlock): string {
		$blockOutput = '';
		foreach ( $DebugBlock as $blockKey => $BlockContent ) {
			$blockOutput .= '<div class="debug_block">';
			if ($BlockContent instanceof DataCollection) {
				$blockId = 'debug_block_' . md5 ( microtime () . rand () );
				$strContent = $this->buildDebugBlocks ( $BlockContent );
				$itemCount = $BlockContent->count ();
				$blockTitle = "$blockKey 	($itemCount)";
				$blockOutput .= "<div class=\"debug_block_title\" onclick=\"document.getElementById('$blockId').classList.toggle('isExpanded'); this.classList.toggle('isExpanded');\">$blockTitle</div>";
				$blockOutput .= "<div id=\"$blockId\" class=\"debug_block_content\">$strContent</div>";
			} else
				$blockOutput .= "<div class=\"debug_block_result\"><b>$blockKey:</b> 	$BlockContent</div>";
			$blockOutput .= '</div>';
		}
		return $blockOutput ? $blockOutput : '&nbsp;';
	}

	/**
	 * Highlight (in red) the word error (and its corresponding translation).
	 *
	 * @param string $text
	 * @return string
	 */
	private function highlightError(string $text): string {
		return str_replace ( [ 
				'ERROR',
				'Error',
				'error',
				dgettext ( 'core', 'ERROR' ),
				dgettext ( 'core', 'Error' ),
				dgettext ( 'core', 'error' )
		], [ 
				'<b style="color:red">ERROR</b>',
				'<b style="color:red">Error</b>',
				'<b style="color:red">error</b>',
				'<b style="color:red">' . dgettext ( 'core', 'ERROR' ) . '</b>',
				'<b style="color:red">' . dgettext ( 'core', 'Error' ) . '</b>',
				'<b style="color:red">' . dgettext ( 'core', 'error' ) . '</b>'
		], $text );
	}

	/**
	 *
	 * @param string $text
	 * @param iterable $info
	 * @return string
	 */
	public function getHtmlInfo($text = ''): string {
		$id = 'debug_' . rand ();
		$uri = $this->getSuperServices ()->getHttpRequestService ()->getUri ();
		$debugOutput = $this->highlightError ( $this->buildDebugBlocks ( $this->getDebugInfo () ) );
		$strDebug = htmlentities ( dgettext ( 'core', 'Debugger' ) );
		$strHide = htmlentities ( dgettext ( 'core', 'Close' ) );
		return <<<HTML
		<div id="open_$id" class="debug_button" onclick="
				document.getElementById('$id').style.display = 'block';
				this.style.display = 'none';
			">$strDebug</div>
		<div id="$id" class="debug_displayer">
			<div class="debug_uri">/$uri</div>
			<span class="debug_button_closer" onclick="
					document.getElementById('$id').style.display = 'none';
					document.getElementById('open_$id').style.display = 'block';
				">$strHide</span>
			$text
			<pre>$debugOutput</pre>
		</div>
		HTML;
	}

	/**
	 *
	 * @return DataCollection
	 */
	public function getDebugInfo(): DataCollection {
		$ProjectService = $this->getSuperServices ()->getProjectService ();
		$SessionService = $this->getSuperServices ()->getSessionService ();
		$RequestService = $this->getSuperServices ()->getHttpRequestService ();
		$FrontMessageService = $this->getSuperServices ()->getFrontMessageService ();
		$SessionVars = null;
		if ($SessionService->isActive () && ! ($SessionVars = $SessionService->getVars ()) && $SessionVars instanceof DataCollection && $SessionVars->count () && isset ( $_SESSION ))
			$SessionVars = $_SESSION;
		// Parse debug info objects
		$debugInfo = $this->debugInfo;
		if (! empty ( $debugInfo ['VARS'] )) {
			foreach ( $debugInfo ['VARS'] as $templateIndex => $templateVars ) {
				if (is_array ( $templateVars ) && ! empty ( $templateVars ['OBJECTS'] )) {
					foreach ( $templateVars ['OBJECTS'] as $objKey => $Object ) {
						$newResult = null;
						$expClass = explode ( '\\', get_class ( $Object ) );
						$classname = end ( $expClass );
						$newKey = "$objKey	($classname)";
						if ($Object instanceof MongoCollection) {
							// Specific debug for MongoCollection
							$mongodbServers = $Object->getSource () ? $Object->getSource ()->getServers () : [ ];
							$Server = end ( $mongodbServers );
							$serverInfo = ! empty ( $Server ) ? [ 
									'host' => $Server->getHost (),
									'port' => $Server->getPort (),
									'database' => $Object->getDbName (),
									'collection' => $Object->getCollectionName (),
									'info' => $Server->getInfo ()
							] : null;
							$writeResults = [ ];
							foreach ( $Object->getAllWriteResults () as $WriteResult ) {
								if ($WriteResult instanceof WriteResult) {
									$writeErrors = [ ];
									foreach ( $WriteResult->getWriteErrors () as $WriteError ) {
										if ($WriteError instanceof WriteError) {
											$writeErrors [] = [ 
													'code' => $WriteError->getCode (),
													'info' => $WriteError->getInfo (),
													'message' => $WriteError->getMessage ()
											];
										}
									}
									$writeResults [] = [ 
											'deleted_count' => $WriteResult->getDeletedCount (),
											'inserted_count' => $WriteResult->getInsertedCount (),
											'matched_count' => $WriteResult->getMatchedCount (),
											'modified_count' => $WriteResult->getModifiedCount (),
											'upserted_count' => $WriteResult->getUpsertedCount (),
											'upserted_ids' => $WriteResult->getUpsertedIds (),
											'errors' => $writeErrors
									];
								}
							}
							$newResult = [ 
									'source' => $serverInfo,
									'data' => $Object->getData ()
							];
							if (! empty ( $writeResults ))
								$newResult ['WriteResults'] = $writeResults;
						} elseif (($Object instanceof JsonCollection) || ($Object instanceof XmlCollection)) {
							// Specific debug for JSON Collection & XML collection
							$newResult = [ 
									'source' => $Object->getSource (),
									'data' => $Object->getData ()
							];
						} elseif ($Object instanceof Model) {
							// Specific debug for Entities
							$newResult = [ 
									'source' => $Object->getConnectorId (),
									'data' => $Object->getCollection (),
									'log' => $Object->getTransactionStatutes ()
							];
						} elseif ($Object instanceof Dataset) {
							// Specific debug for Entities
							$newResult = [ 
									'definition' => $Object->getDefinition (),
									'constants' => (new \ReflectionClass ( $Object ))->getConstants ()
							];
						} elseif (! $Object instanceof DataCollection) {
							// A generic print_r for any object
							$newResult = [ 
									'print_r' => print_r ( $Object, true )
							];
						}
						if ($newResult) {
							$debugInfo ['VARS'] [$templateIndex] ['OBJECTS'] [$newKey] = $newResult;
							unset ( $debugInfo ['VARS'] [$templateIndex] ['OBJECTS'] [$objKey] );
						}
					}
				}
			}
		}

		$EnvSettings = [ ];
		foreach ( $ProjectService->getEnvSettings () as $k => $v )
			if (! is_object ( $v ))
				$EnvSettings [$k] = $v;

		return new DataCollection ( [ 
				'CONTROLLER' => get_class ( $this->getSuperServices ()->getRouteControllerFactoryService ()->getMainController () ),
				'APP_ROOT' => APP_ROOT,
				'DEBUG_INFO' => $debugInfo,
				'REQUEST_PARAMS' => $RequestService->getParams (),
				'FRONT_MESSAGES' => $FrontMessageService->getMessages (),
				'SESSION' => $SessionVars,
				'ENV_SETTINGS: ' . $ProjectService->getEnv () => $EnvSettings,
				'PROJECT_LOCALE: ' . $ProjectService->getLocale () => $ProjectService->getLocaleAdditionalProperties (),
				'_COOKIE' => $_COOKIE,
				'_SERVER' => $_SERVER
		] );
	}

	/**
	 *
	 * @param string $index
	 * @param mixed $info
	 * @param string $type
	 * @return self
	 */
	public function setDebugInfo(string $index, $info, string $type = 'VARS'): self {
		$index .= ' ~ ' . substr ( md5 ( microtime () . rand () ), 0, 8 );
		$this->debugInfo [$type] [$index] = $info;
		return $this;
	}
}