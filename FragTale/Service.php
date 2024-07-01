<?php

namespace FragTale;

use FragTale\Implement\AbstractService;
use FragTale\Service\Configuration;
use FragTale\Service\Debug;
use FragTale\Service\Database\Connector;
use FragTale\Service\Factory\RouteControllerFactory;
use FragTale\Service\Project;
use FragTale\Service\Cli;
use FragTale\Service\Filesystem;
use FragTale\Service\Localize;
use FragTale\Service\FrontMessage;
use FragTale\Service\ErrorHandler;
use FragTale\Service\Route;
use FragTale\Service\AuthUser;
use FragTale\Service\FormTagBuilder;
use FragTale\Service\Http\Client;
use FragTale\Service\Http\Cookie;
use FragTale\Service\Http\Request;
use FragTale\Service\Http\Response;
use FragTale\Service\Http\Server;
use FragTale\Service\Http\Session;

/**
 * This class and inherited classes are singletons and should be used as singleton
 *
 * @author Fabrice Dant <fragtale.development@gmail.com>
 * @copyright 2024 FragTale 2 - Fabrice Dant
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-fr.txt CeCILL Licence 2.1 (French version)
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-en.txt CeCILL Licence 2.1 (English version)
 *         
 */
class Service extends AbstractService {

	# # Getting directly some children
	public function getAuthUserService(): AuthUser {
		return $this->createSingleInstance ( AuthUser::class );
	}
	public function getCliService(): Cli {
		return $this->createSingleInstance ( Cli::class );
	}
	public function getConfigurationService(): Configuration {
		return $this->createSingleInstance ( Configuration::class );
	}
	public function getDatabaseConnectorService(): Connector {
		return $this->createSingleInstance ( Connector::class );
	}
	public function getDebugService(): Debug {
		return $this->createSingleInstance ( Debug::class );
	}
	public function getErrorHandlerService(): ErrorHandler {
		return $this->createSingleInstance ( ErrorHandler::class );
	}
	public function getFilesystemService(): Filesystem {
		return $this->createSingleInstance ( Filesystem::class );
	}
	public function getFormTagBuilderService(): FormTagBuilder {
		return $this->createSingleInstance ( FormTagBuilder::class );
	}
	public function getFrontMessageService(): FrontMessage {
		return $this->createSingleInstance ( FrontMessage::class );
	}
	public function getHttpClientService(): Client {
		return $this->createSingleInstance ( Client::class );
	}
	public function getHttpCookieService(): Cookie {
		return $this->createSingleInstance ( Cookie::class );
	}
	public function getHttpRequestService(): Request {
		return $this->createSingleInstance ( Request::class );
	}
	public function getHttpResponseService(): Response {
		return $this->createSingleInstance ( Response::class );
	}
	public function getHttpServerService(): Server {
		return $this->createSingleInstance ( Server::class );
	}
	public function getProjectService(): Project {
		return $this->createSingleInstance ( Project::class );
	}
	public function getRouteService(): Route {
		return $this->createSingleInstance ( Route::class );
	}
	public function getRouteControllerFactoryService(): RouteControllerFactory {
		return $this->createSingleInstance ( RouteControllerFactory::class );
	}
	public function getSessionService(): Session {
		return $this->createSingleInstance ( Session::class );
	}
	public function getLocalizeService(): Localize {
		return $this->createSingleInstance ( Localize::class );
	}
}