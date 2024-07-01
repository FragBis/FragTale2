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
class Media extends Controller {
	/**
	 * Routed file
	 *
	 * @var string
	 */
	protected string $file;

	/**
	 * Cache life time in seconds
	 * (30 days)
	 *
	 * @var integer
	 */
	protected int $cachetime = 2592000;

	/**
	 * By default "public".
	 * Can be "public|private"
	 *
	 * @var string
	 */
	protected string $cachecontrol = 'public';

	/**
	 * Get routed file
	 */
	function __construct() {
		$this->file = ( string ) $this->getSuperServices ()->getHttpRequestService ()->getParamValue ( '_route_index' );
	}

	/**
	 */
	protected function send404File() {
		$httpProtocolVersion = $this->getSuperServices ()->getHttpServerService ()->getProtocolVersion ();
		$this->cachetime = 86400; // shorten lifetime for 404 (in case of file reuploaded): 1 day
		                          // using the 404 default image
		$filename = $this->getSuperServices ()->getProjectService ()->getResourcesDir () . '/media/img/404.jpg';
		if (! file_exists ( $filename ))
			$filename = CorePath::RESOURCES_DIR . '/media/img/404.jpg';
		$this->getSuperServices ()
			->getHttpResponseService ()
			->addHeader ( "$httpProtocolVersion 404 " . dgettext ( 'core', 'Not Found' ) )
			->addHeader ( "Cache-Control:$this->cachecontrol, max-age=$this->cachetime" )
			->addHeader ( "Content-Type: image/jpeg" )
			->sendHeaders ();
		readfile ( $filename );
		exit ();
	}

	/**
	 * Display on GET
	 *
	 * {@inheritdoc}
	 * @see \FragTale\Application\Controller::executeOnHttpRequestMethodGet()
	 */
	protected function executeOnHttpRequestMethodGet(): void {
		// Check file exists in custom project resources
		$filename = $this->getSuperServices ()->getProjectService ()->getResourcesDir () . '/' . $this->file;
		if (! file_exists ( $filename ))
			$filename = CorePath::RESOURCES_DIR . '/' . $this->file;

		// If not file exists, use the 404 image
		if (! file_exists ( $filename ))
			$this->send404File ();

		$httpProtocolVersion = $this->getSuperServices ()->getHttpServerService ()->getProtocolVersion ();
		$fileext = strtolower ( substr ( $filename, strrpos ( $filename, '.' ) + 1 ) );
		$mimeType = $fileext === 'css' ? 'text/css' : ($fileext === 'js' ? 'text/javascript' : mime_content_type ( $filename ));
		$contentLength = filesize ( $filename );
		$this->getSuperServices ()
			->getHttpResponseService ()
			->addHeader ( "$httpProtocolVersion 200 OK" )
			->addHeader ( "Cache-Control:$this->cachecontrol, max-age=$this->cachetime" )
			->addHeader ( "Content-Type: $mimeType" )
			->addHeader ( "Content-Length: $contentLength" )
			->sendHeaders ();
		readfile ( $filename );
		exit ();
	}

	/**
	 * Handles media upload
	 * Then implement authorizations
	 *
	 * {@inheritdoc}
	 * @see \FragTale\Application\Controller::executeOnHttpRequestMethodPost()
	 */
	protected function executeOnHttpRequestMethodPost(): void {
	}
}