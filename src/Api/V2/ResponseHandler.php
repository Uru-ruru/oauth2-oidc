<?php

namespace App\Api\V2;

use App\Api\V2\ResponseHandlers\RedirectResponseHandler;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\ResponseTypes\RedirectResponse;

/**
 * Class ResponseHandler.
 */
class ResponseHandler
{
    /**
     * @var array|RedirectResponseHandler[]
     */
    protected array $handlers;

    public function __construct()
    {
        $this->handlers = [
            new RedirectResponseHandler(),
        ];
    }

    /**
     * @param mixed $code
     *
     * @throws OAuthServerException
     */
    public function getResponse(AuthenticationRequest $authenticationRequest, $code): RedirectResponse
    {
        foreach ($this->handlers as $handler) {
            if ($handler->canRespondToAuthorizationRequest($authenticationRequest)) {
                $response = $handler->generateResponse($authenticationRequest, $code);
            }
        }

        if (!isset($response)) {
            throw OAuthServerException::invalidRequest('response_mode', 'No valid response_mode provided');
        }

        return $response;
    }
}
