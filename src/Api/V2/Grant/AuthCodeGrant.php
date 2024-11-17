<?php

namespace App\Api\V2\Grant;

use App\Api\V2\AuthenticationRequest;
use App\Api\V2\Entities\IdTokenEntity;
use App\Api\V2\Repositories\Interfaces\AccessTokenRepositoryInterface;
use App\Api\V2\Repositories\Interfaces\ClaimRepositoryInterface;
use App\Api\V2\ResponseHandler;
use App\Api\V2\ResponseTypes\BearerTokenResponse;
use App\Api\V2\Session;
use App\Api\V2\SessionInformation;
use League\OAuth2\Server\Entities\UserEntityInterface;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Repositories\AuthCodeRepositoryInterface;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;
use League\OAuth2\Server\RequestTypes\AuthorizationRequest;
use League\OAuth2\Server\ResponseTypes\RedirectResponse;
use League\OAuth2\Server\ResponseTypes\ResponseTypeInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class AuthCodeGrant.
 */
class AuthCodeGrant extends \League\OAuth2\Server\Grant\AuthCodeGrant
{
    use OIDCTrait;

    protected \DateInterval $authCodeTTL;

    protected \DateInterval $idTokenTTL;

    protected Session $session;

    protected ClaimRepositoryInterface $claimRepository;

    /**
     * @var AccessTokenRepositoryInterface
     */
    protected $accessTokenRepository;

    /**
     * @throws \Exception
     */
    public function __construct(
        AuthCodeRepositoryInterface $authCodeRepository,
        RefreshTokenRepositoryInterface $refreshTokenRepository,
        ClaimRepositoryInterface $claimRepository,
        Session $session,
        \DateInterval $authCodeTTL,
        \DateInterval $idTokenTTL,
        bool $disableRequireCodeChallengeForPublicClients = true
    ) {
        parent::__construct($authCodeRepository, $refreshTokenRepository, $authCodeTTL);

        $this->claimRepository = $claimRepository;

        $this->authCodeTTL = $authCodeTTL;
        $this->idTokenTTL = $idTokenTTL;
        $this->session = $session;

        if ($disableRequireCodeChallengeForPublicClients) {
            $this->disableRequireCodeChallengeForPublicClients();
        }

        $this->setIssuer('https://'.SITE_SERVER_NAME);
    }

    public function getIdentifier(): string
    {
        return 'authorization_code_oidc';
    }

    public function canRespondToAuthorizationRequest(ServerRequestInterface $request): bool
    {
        $result = parent::canRespondToAuthorizationRequest($request);

        $queryParams = $request->getQueryParams();
        $scopes = ($queryParams && isset($queryParams['scope'])) ? $queryParams['scope'] : null;

        return $result && ($scopes && in_array('openid', explode(' ', $scopes), true));
    }

    /**
     * @throws \JsonException
     */
    public function canRespondToAccessTokenRequest(ServerRequestInterface $request): bool
    {
        $requestParameters = (array) $request->getParsedBody();
        // FIXME: for some reason, the unit test complete if the next three lines are removed
        if (!array_key_exists('code', $requestParameters)) {
            return false;
        }

        $authCodePayload = json_decode($this->decrypt($requestParameters['code']), false, 512, JSON_THROW_ON_ERROR);

        return in_array('openid', $authCodePayload->scopes, true)
            && array_key_exists('grant_type', $requestParameters)
            && 'authorization_code' === $requestParameters['grant_type'];
    }

    /**
     * @throws OAuthServerException
     * @throws \JsonException
     */
    public function validateAuthorizationRequest(ServerRequestInterface $request): AuthorizationRequest
    {
        $result = parent::validateAuthorizationRequest($request);

        $redirectUri = $this->getQueryStringParameter(
            'redirect_uri',
            $request
        );

        // In constract with OAuth 2.0, in OIDC, the redirect_uri parameter is required
        if (is_null($redirectUri)) {
            throw OAuthServerException::invalidRequest('redirect_uri');
        }

        $result = AuthenticationRequest::fromAuthorizationRequest($result);

        $result->setNonce($this->getQueryStringParameter('nonce', $request));

        // When max_age is used, the ID Token returned MUST include an auth_time Claim Value
        $maxAge = $this->getQueryStringParameter('max_age', $request);

        if (!empty($maxAge) && !is_numeric($maxAge)) {
            throw OAuthServerException::invalidRequest('max_age', 'max_age must be numeric');
        }

        $result->setMaxAge($maxAge);

        $result->setPrompt($this->getQueryStringParameter('prompt', $request));
        $result->setResponseMode($this->getQueryStringParameter('response_mode', $request));
        $result->setResponseType($this->getQueryStringParameter('response_type', $request));

        if (!empty($uiLocales = $this->getQueryStringParameter('ui_locales', $request))) {
            $result->setUILocales(explode(' ', $uiLocales));
        }

        $result->setLoginHint($this->getQueryStringParameter('login_hint', $request));

        if (!empty($acrValues = $this->getQueryStringParameter('acr_values', $request))) {
            $result->setAcrValues(explode(' ', $acrValues));
        }

        $claims = $this->getQueryStringParameter('claims', $request);

        $result->setClaims(
            $this->claimRepository->claimsRequestToEntities($claims ? json_decode($claims, true, 512, JSON_THROW_ON_ERROR) : null)
        );

        if (!empty($display = $this->getQueryStringParameter('display', $request))) {
            $result->setDisplay($display);
        }

        return $result;
    }

    /**
     * @throws \Exception
     */
    public function respondToAccessTokenRequest(ServerRequestInterface $request, ResponseTypeInterface $responseType, \DateInterval $accessTokenTTL): BearerTokenResponse
    {
        /**
         * @var BearerTokenResponse $result
         */
        $result = parent::respondToAccessTokenRequest($request, $responseType, $accessTokenTTL);

        $encryptedAuthCode = $this->getRequestParameter('code', $request, null);
        $authCodePayload = json_decode($this->decrypt($encryptedAuthCode), false, 512, JSON_THROW_ON_ERROR);

        logger()->info(__CLASS__, (array) $authCodePayload);

        if ($authCodePayload->claims) {
            $authCodePayload->claims = (array) $authCodePayload->claims;
        }

        $idToken = new IdTokenEntity();
        $idToken->setIssuer($this->getIssuer());
        $idToken->setSubject($authCodePayload->user_id);
        $idToken->setAudience($authCodePayload->client_id);
        $idToken->setIdentified($authCodePayload->client_id.$authCodePayload->user_id);
        $idToken->setExpiration(\DateTimeImmutable::createFromMutable((new \DateTime())->add($this->idTokenTTL)));
        $idToken->setIat(new \DateTimeImmutable());

        $idToken->setAuthTime(new \DateTime('@'.$authCodePayload->auth_time));
        $idToken->setNonce($authCodePayload->nonce);

        if ($authCodePayload->claims) {
            $accessToken = $result->getAccessToken();

            $this->accessTokenRepository->storeClaims($accessToken, $authCodePayload->claims);
        }

        $sessionInformation = SessionInformation::fromJSON($authCodePayload->sessionInformation);

        $idToken->setAcr($sessionInformation->getAcr());
        $idToken->setAmr($sessionInformation->getAmr());
        $idToken->setAzp($sessionInformation->getAzp());

        $result->setIdToken($idToken);

        return $result;
    }

    /**
     * @throws \JsonException|OAuthServerException
     */
    public function completeAuthorizationRequest(AuthorizationRequest $authorizationRequest): RedirectResponse|ResponseTypeInterface
    {
        if (!$authorizationRequest instanceof AuthenticationRequest) {
            throw OAuthServerException::invalidRequest('not possible');
        }

        if (false === $authorizationRequest->getUser() instanceof UserEntityInterface) {
            throw new \LogicException('An instance of UserEntityInterface should be set on the AuthorizationRequest');
        }

        // The user approved the client, redirect them back with an auth code
        if (true === $authorizationRequest->isAuthorizationApproved()) {
            $authCode = $this->issueAuthCode(
                $this->authCodeTTL,
                $authorizationRequest->getClient(),
                $authorizationRequest->getUser()->getIdentifier(),
                $authorizationRequest->getRedirectUri(),
                $authorizationRequest->getScopes()
            );

            $payload = [
                'client_id' => $authCode->getClient()->getIdentifier(),
                'redirect_uri' => $authCode->getRedirectUri(),
                'auth_code_id' => $authCode->getIdentifier(),
                'scopes' => $authCode->getScopes(),
                'user_id' => $authCode->getUserIdentifier(),
                'expire_time' => (new \DateTime())->add($this->authCodeTTL)->format('U'),
                'code_challenge' => $authorizationRequest->getCodeChallenge(),
                'code_challenge_method' => $authorizationRequest->getCodeChallengeMethod(),

                // OIDC specifc parameters important for the id_token
                'nonce' => $authorizationRequest->getNonce(),
                'max_age' => $authorizationRequest->getMaxAge(),
                'id_token_hint' => $authorizationRequest->getIDTokenHint(),
                'claims' => $authorizationRequest->getClaims(),
                'sessionInformation' => (string) $authorizationRequest->getSessionInformation(),
                'auth_time' => $this->session->getAuthTime()->format('U'),
            ];

            $code = $this->encrypt(
                json_encode($payload, JSON_THROW_ON_ERROR)
            );

            return (new ResponseHandler())->getResponse($authorizationRequest, $code);
        }

        // The user denied the client, redirect them back with an error
        throw OAuthServerException::accessDenied(
            'The user denied the request',
            $this->makeRedirectUri(
                $authorizationRequest->getRedirectUri(),
                [
                    'state' => $authorizationRequest->getState(),
                ]
            )
        );
    }
}
