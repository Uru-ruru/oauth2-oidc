<?php

namespace App\Api\V2\Grant;

use App\Api\V2\AuthenticationRequest;
use App\Api\V2\Entities\IdTokenEntity;
use App\Api\V2\Repositories\Interfaces\ClaimRepositoryInterface;
use App\Api\V2\Session;
use App\Api\V2\SessionInformation;
use League\OAuth2\Server\Entities\UserEntityInterface;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Exception\UniqueTokenIdentifierConstraintViolationException;
use League\OAuth2\Server\Repositories\UserRepositoryInterface;
use League\OAuth2\Server\RequestTypes\AuthorizationRequest;
use League\OAuth2\Server\ResponseTypes\RedirectResponse;
use Psr\Http\Message\ServerRequestInterface;

class ImplicitGrant extends \League\OAuth2\Server\Grant\ImplicitGrant
{
    use OIDCTrait;

    /**
     * @var UserRepositoryInterface
     */
    protected $userRepository;

    protected ClaimRepositoryInterface $claimRepositoryInterface;

    private \DateInterval $authCodeTTL;

    private \DateInterval $idTokenTTL;

    private string $queryDelimiter;

    private \DateInterval $accessTokenTTL;

    public function __construct(
        UserRepositoryInterface $userRepository,
        ClaimRepositoryInterface $claimRepositoryInterface,
        \DateInterval $accessTokenTTL,
        \DateInterval $idTokenTTL,
        string $queryDelimiter = '#'
    ) {
        parent::__construct($accessTokenTTL, $queryDelimiter);

        $this->userRepository = $userRepository;
        $this->claimRepositoryInterface = $claimRepositoryInterface;

        $this->accessTokenTTL = $accessTokenTTL;
        $this->idTokenTTL = $idTokenTTL;
        $this->queryDelimiter = $queryDelimiter;

        $this->setIssuer('https://'.SITE_SERVER_NAME);
    }

    public function getIdentifier(): string
    {
        return 'implicit_oidc';
    }

    public function canRespondToAuthorizationRequest(ServerRequestInterface $request): bool
    {
        $result = (isset($request->getQueryParams()['response_type'], $request->getQueryParams()['client_id']) && ('id_token token' === $request->getQueryParams()['response_type'] || 'id_token' === $request->getQueryParams()['response_type'] || 'token' === $request->getQueryParams()['response_type']));

        $queryParams = $request->getQueryParams();
        $scopes = ($queryParams && isset($queryParams['scope'])) ? $queryParams['scope'] : null;

        return $result && ($scopes && in_array('openid', explode(' ', $scopes), true));
    }

    /**
     * @throws OAuthServerException
     */
    public function validateAuthorizationRequest(ServerRequestInterface $request): AuthorizationRequest|AuthenticationRequest
    {
        $result = parent::validateAuthorizationRequest($request);

        $result = AuthenticationRequest::fromAuthorizationRequest($result);

        $result->setResponseType($this->getQueryStringParameter('response_type', $request));
        $result->setResponseMode($this->getQueryStringParameter('response_mode', $request));

        $nonce = $this->getQueryStringParameter('nonce', $request);

        // In OIDC, a nonce is required for the implicit flow
        if ('' === $nonce) {
            throw OAuthServerException::invalidRequest('nonce');
        }

        $result->setNonce($nonce);

        $redirectUri = $this->getQueryStringParameter(
            'redirect_uri',
            $request
        );

        // In constract with OAuth 2.0, in OIDC, the redirect_uri parameter is required
        if (is_null($redirectUri)) {
            throw OAuthServerException::invalidRequest('redirect_uri');
        }

        // When max_age is used, the ID Token returned MUST include an auth_time Claim Value
        $maxAge = $this->getQueryStringParameter('max_age', $request);

        if (!empty($maxAge) && !is_numeric($maxAge)) {
            throw OAuthServerException::invalidRequest('max_age', 'max_age must be numeric');
        }

        $result->setMaxAge($maxAge);

        $result->setPrompt($this->getQueryStringParameter('prompt', $request));

        if (!empty($uiLocales = $this->getQueryStringParameter('ui_locales', $request))) {
            $result->setUILocales(explode(' ', $uiLocales));
        }

        $result->setLoginHint($this->getQueryStringParameter('login_hint', $request));

        if (!empty($acrValues = $this->getQueryStringParameter('acr_values', $request))) {
            $result->setAcrValues(explode(' ', $acrValues));
        }

        return $result;
    }

    /**
     * @throws UniqueTokenIdentifierConstraintViolationException
     * @throws OAuthServerException
     */
    public function completeAuthorizationRequest(AuthorizationRequest $authorizationRequest): RedirectResponse
    {
        if (!$authorizationRequest instanceof AuthenticationRequest) {
            throw OAuthServerException::invalidRequest('not possible');
        }

        if (false === $authorizationRequest->getUser() instanceof UserEntityInterface) {
            throw new \LogicException('An instance of UserEntityInterface should be set on the AuthorizationRequest');
        }

        $finalRedirectUri = $authorizationRequest->getRedirectUri();

        // The user approved the client, redirect them back with an access token
        if (true === $authorizationRequest->isAuthorizationApproved()) {
            $accessToken = $this->issueAccessToken(
                $this->accessTokenTTL,
                $authorizationRequest->getClient(),
                $authorizationRequest->getUser()->getIdentifier(),
                $authorizationRequest->getScopes()
            );

            $idToken = new IdTokenEntity();

            $idToken->setIssuer($this->getIssuer());
            $idToken->setSubject($authorizationRequest->getUser()->getIdentifier());
            $idToken->setAudience($authorizationRequest->getClient()->getIdentifier());
            $idToken->setExpiration(\DateTimeImmutable::createFromMutable((new \DateTime())->add($this->idTokenTTL)));
            $idToken->setIat(new \DateTimeImmutable());
            $idToken->setAuthTime((new Session())->getAuthTime());
            $idToken->setNonce($authorizationRequest->getNonce());

            // If there is no access token returned, include the supported claims
            if ('id_token' === $authorizationRequest->getResponseType()) {
                $claimsRequested = [];
                $scopes = [];

                foreach ($authorizationRequest->getScopes() as $scope) {
                    $claims = $this->userRepository->getClaims(
                        $this->claimRepositoryInterface,
                        $scope
                    );
                    if (count($claims) > 0) {
                        array_push($claimsRequested, ...$claims);
                    }
                }

                $attributes = $this->userRepository->getAttributes(
                    $authorizationRequest->getUser(),
                    $claimsRequested,
                    $scopes
                );

                foreach ($attributes as $key => $value) {
                    $idToken->addExtra($key, $value);
                }
            }

            /**
             * @var SessionInformation
             */
            $sessionInformation = $authorizationRequest->getSessionInformation();

            $idToken->setAcr($sessionInformation->getAcr());
            $idToken->setAmr($sessionInformation->getAmr());
            $idToken->setAzp($sessionInformation->getAzp());

            $parameters = [];

            // Only add the access token and related parameters if requested
            // TODO: Check if OpenID Connect flow is allowed if only a token is requested.
            if ('id_token token' === $authorizationRequest->getResponseType() || 'token' === $authorizationRequest->getResponseType()) {
                $accessToken->setPrivateKey($this->privateKey);
                $parameters['access_token'] = (string) $accessToken;
                $parameters['token_type'] = 'Bearer';
                $parameters['expires_in'] = $accessToken->getExpiryDateTime()->getTimestamp() - (new \DateTime())->getTimestamp();
            }

            $parameters['state'] = $authorizationRequest->getState();
            $parameters['id_token'] = (string) $idToken->convertToJWT($this->privateKey)->toString();

            $response = new RedirectResponse();
            $response->setRedirectUri(
                $this->makeRedirectUri(
                    $finalRedirectUri,
                    $parameters,
                    $this->queryDelimiter
                )
            );

            return $response;
        }

        // The user denied the client, redirect them back with an error
        throw OAuthServerException::accessDenied(
            'The user denied the request',
            $this->makeRedirectUri(
                $finalRedirectUri,
                [
                    'state' => $authorizationRequest->getState(),
                ]
            )
        );
    }
}
