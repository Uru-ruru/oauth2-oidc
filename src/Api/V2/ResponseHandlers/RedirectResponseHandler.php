<?php

namespace App\Api\V2\ResponseHandlers;

use App\Api\V2\AuthenticationRequest;
use League\OAuth2\Server\ResponseTypes\RedirectResponse;

/**
 * Class RedirectResponseHandler.
 */
class RedirectResponseHandler
{
    public function canRespondToAuthorizationRequest(AuthenticationRequest $authenticationRequest): bool
    {
        return
            null === $authenticationRequest->getResponseMode()
            || 'fragment' === $authenticationRequest->getResponseMode()
            || 'form_post' === $authenticationRequest->getResponseMode()
            || 'query' === $authenticationRequest->getResponseMode();
    }

    public function generateResponse(AuthenticationRequest $authenticationRequest, $code): RedirectResponse
    {
        $queryDelimiter = '?';

        if ('fragment' === $authenticationRequest->getResponseMode()
            || !str_contains($authenticationRequest->getResponseType(), 'code')
        ) {
            $queryDelimiter = '#';
        }

        if ('query' === $authenticationRequest->getResponseMode()) {
            $queryDelimiter = '?';
        }

        $response = new RedirectResponse();
        $response->setRedirectUri(
            $this->makeRedirectUri(
                $authenticationRequest->getRedirectUri(),
                [
                    'code' => $code,
                    'state' => $authenticationRequest->getState(),
                ],
                $queryDelimiter
            )
        );

        return $response;
    }

    public function makeRedirectUri($uri, array $params = [], string $queryDelimiter = '?'): string
    {
        $uri .= (!str_contains($uri, $queryDelimiter)) ? $queryDelimiter : '&';

        return $uri.http_build_query($params);
    }
}
