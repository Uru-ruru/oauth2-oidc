<?php

namespace App\Api\V2;

use League\OAuth2\Server\Exception\OAuthServerException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

/**
 * Class ResourceServerMiddleware.
 */
class ResourceServerMiddleware
{
    private AdvancedResourceServer $server;

    public function __construct(AdvancedResourceServer $server)
    {
        $this->server = $server;
    }

    public function __invoke(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            $request = $this->server->validateAuthenticatedRequest($request);
            $response = $handler->handle($request);
        } catch (OAuthServerException $exception) {
            $response = new Response();

            return $exception->generateHttpResponse($response);
            // @codeCoverageIgnoreStart
        } catch (\Exception $exception) {
            $response = new Response();

            return (new OAuthServerException($exception->getMessage(), 0, 'unknown_error', 500))
                ->generateHttpResponse($response)
            ;
            // @codeCoverageIgnoreEnd
        }
        // Pass the request and response on to the next responder in the chain
        return $response;
    }
}
