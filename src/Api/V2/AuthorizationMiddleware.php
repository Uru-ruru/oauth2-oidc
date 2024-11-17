<?php

namespace App\Api\V2;

use App\Api\BaseController;
use App\Models\HL\OauthClients;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

/**
 * Class AuthorizationMiddleware.
 */
class AuthorizationMiddleware extends BaseController
{
    /**
     * cookie prefix.
     *
     * @var string
     */
    public const sso_prefix = 'sso_';

    /**
     * cookie path.
     *
     * @var string
     */
    public const PATH = '/';

    /**
     * cookie exp time.
     *
     * @var string
     */
    public const EXP = 1800;

    /**
     * params array for redirect.
     *
     * @var array
     */
    public const transferVariables = [
        'from' => 'oauth2',
        'redirect_uri' => null,
        'client_id' => null,
        'scope' => null,
        'response_type' => null,
        'code_challenge' => null,
        'code_challenge_method' => null,
        'response_mode' => null,
        'nonce' => null,
        'x-client-SKU' => null,
        'x-client-ver' => null,
        'state' => null,
    ];

    public function __invoke(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        $user = self::Authorize();
        if (!$user || !$user->IsAuthorized()) {
            $this->cookiesManager($request, $response);
            $this->customVariablesManager($request, $response);
            $response = new Response();

            return $this->withRedirect($request, $response, '/?'.$this->paramsManager($request, $response));
        }
        // Pass the request and response on to the next responder in the chain
        return $response;
    }

    private function cookiesManager(ServerRequestInterface $request, ResponseInterface $response): void
    {
        foreach (self::transferVariables as $cookieName => $cookieVal) {
            if ($this->getParam($request, $response, $cookieName)) {
                setcookie(self::sso_prefix.$cookieName, $cookieVal ?: $this->getParam($request, $response, $cookieName), time() + self::EXP, self::PATH);
            }
        }
    }

    private function customVariablesManager(ServerRequestInterface $request, ResponseInterface $response): void
    {
        $clientId = $this->getParam($request, $response, 'client_id');
        if ($clientId) {
            $client = OauthClients::getClientByName($clientId);
            if ($client) {
                foreach ($client->getCustomVariables() as $var) {
                    if ($this->getParam($request, $response, $var)) {
                        setcookie(self::sso_prefix.$var, $this->getParam($request, $response, $var), time() + self::EXP, self::PATH);
                    }
                }
            }
        }
    }

    private function paramsManager(ServerRequestInterface $request, ResponseInterface $response): string
    {
        $params = [];
        foreach (self::transferVariables as $name => $val) {
            $params[$name] = $val ?: $this->getParam($request, $response, $name) ?: '';
        }
        $clientId = $this->getParam($request, $response, 'client_id');
        if ($clientId) {
            $client = OauthClients::getClientByName($clientId);
            if ($client) {
                foreach ($client->getCustomVariables() as $var) {
                    $params[$var] = $this->getParam($request, $response, $var) ?: '';
                }
            }
        }

        return http_build_query($params);
    }
}
