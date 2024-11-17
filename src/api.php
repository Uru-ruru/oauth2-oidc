<?php

use App\Api\V2\AuthorizationMiddleware;
use App\Api\V2\Oauth2Controller;
use App\Api\V2\ResourceServerMiddleware;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Slim\Factory\AppFactory;
use Slim\Routing\RouteCollectorProxy;

// api logger
$logger = new Logger('api');
$logger->pushHandler(new StreamHandler('logs/api.log'));

$app = AppFactory::create();
$app->addBodyParsingMiddleware();
$app->addErrorMiddleware(true, true, true, $logger);
$app->setBasePath('/api');

// OAUTH2 SERVER
$app->get('/.well-known/openid-configuration', [Oauth2Controller::class, 'configuration']);
$app->get('/certs', [Oauth2Controller::class, 'jwksKeys']);
$app->get('/authorize', [Oauth2Controller::class, 'authorizeCode'])->add(new AuthorizationMiddleware());

$app->post('/access_token', [Oauth2Controller::class, 'accessToken']);
$app->get('/logout', [Oauth2Controller::class, 'logout']);
$app->group('/v2', function (RouteCollectorProxy $group) {
    $group->get('/user', [Oauth2Controller::class, 'userInfo']);
})->add(
    new ResourceServerMiddleware(Oauth2Controller::resourceServerFactory())
);

$app->run();
