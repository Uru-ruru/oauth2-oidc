<?php

namespace App\Api\V2;

use App\Api\BaseController;
use App\Api\V2\Entities\ClientEntity;
use App\Api\V2\Entities\Interfaces\ClaimEntityInterface;
use App\Api\V2\Entities\UserEntity;
use App\Api\V2\Grant\AuthCodeGrant;
use App\Api\V2\Grant\PasswordGrant;
use App\Api\V2\Repositories\AccessTokenRepository;
use App\Api\V2\Repositories\AuthCodeRepository;
use App\Api\V2\Repositories\ClaimRepository;
use App\Api\V2\Repositories\ClientRepository;
use App\Api\V2\Repositories\Interfaces\AccessTokenRepositoryInterface;
use App\Api\V2\Repositories\Interfaces\ClaimRepositoryInterface;
use App\Api\V2\Repositories\Interfaces\UserRepositoryInterface;
use App\Api\V2\Repositories\RefreshTokenRepository;
use App\Api\V2\Repositories\ScopeRepository;
use App\Api\V2\Repositories\UserRepository;
use App\Api\V2\ResponseTypes\BearerTokenResponse;
use App\EventHandlers\UserHandlers;
use App\Models\HL\OauthRefreshTokens;
use App\Models\HL\OauthTokens;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Grant\RefreshTokenGrant;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Strobotti\JWK\KeyFactory;
use Strobotti\JWK\KeySet;

/**
 * Class Oauth2Controller.
 */
class Oauth2Controller extends BaseController
{
    private const alg = 'RS256';

    protected UserRepository|UserRepositoryInterface $userRepository;

    protected AccessTokenRepository|AccessTokenRepositoryInterface $tokenRepository;

    protected ClaimRepository|ClaimRepositoryInterface $claimRepository;

    protected string $privateKey;

    private AuthorizationServer $server;

    /**
     * @throws \Exception
     */
    public function __construct()
    {
        $this->privateKey = 'file://'.env('PRIVATE_KEY');

        $clientRepository = new ClientRepository();
        $this->tokenRepository = new AccessTokenRepository();
        $scopeEntity = new ScopeRepository();
        $refreshTokenRepository = new RefreshTokenRepository();
        $authCodeRepository = new AuthCodeRepository();
        $this->claimRepository = new ClaimRepository();
        $session = new Session();
        $bearerResponse = new BearerTokenResponse();
        $this->userRepository = new UserRepository();
        $encryptionKey = env('JWT_TOKEN');
        $this->server = new AuthorizationServer(
            $clientRepository,
            $this->tokenRepository,
            $scopeEntity,
            $this->privateKey,
            $encryptionKey,
            $bearerResponse
        );
        $grantAuthCode = new AuthCodeGrant(
            $authCodeRepository,
            $refreshTokenRepository,
            $this->claimRepository,
            $session,
            new \DateInterval('PT10M'), // authorization codes will expire after 10 minutes
            new \DateInterval('PT10M') // token will expire after 10 minutes
        );

        $grantAuthCode->setRefreshTokenTTL(new \DateInterval('P1M')); // refresh tokens will expire after 1 month

        $this->server->enableGrantType(
            $grantAuthCode,
            new \DateInterval('PT1H') // access tokens will expire after 1 hour
        );

        $grantRefresh = new RefreshTokenGrant($refreshTokenRepository);
        $grantRefresh->setRefreshTokenTTL(new \DateInterval('P1M')); // The refresh token will expire in 1 month

        $this->server->enableGrantType(
            $grantRefresh,
            new \DateInterval('PT1H') // The new access token will expire after 1 hour
        );

        $grantPassword = new PasswordGrant(
            $this->userRepository,
            $refreshTokenRepository,
            $this->claimRepository,
            $session,
            new \DateInterval('PT10M'), // authorization codes will expire after 10 minutes
            new \DateInterval('PT10M') // token will expire after 10 minutes
        );

        $grantPassword->setRefreshTokenTTL(new \DateInterval('P1M')); // refresh tokens will expire after 1 month

        // Enable the password grant on the server
        $this->server->enableGrantType(
            $grantPassword,
            new \DateInterval('PT12H') // access tokens will expire after 12 hours
        );
    }

    public static function resourceServerFactory(): AdvancedResourceServer
    {
        $publicKey = 'file://'.env('PUBLIC_KEY');

        return new AdvancedResourceServer(
            new AccessTokenRepository(),
            $publicKey
        );
    }

    public static function getAuthorizationLink($clientId): string
    {
        $params = [];
        $client = new ClientEntity($clientId);
        if ($client->isLoaded()) {
            foreach (AuthorizationMiddleware::transferVariables as $paramName => $val) {
                if (null === $val) {
                    $params[$paramName] = $_GET[$paramName] ?: $_COOKIE[AuthorizationMiddleware::sso_prefix.$paramName];
                }
            }
            $params['client_id'] = $client->getName();
            $params['redirect_uri'] = $client->getRedirectUri();
            $params += static::getCustomVariables($client);

            return '/api/authorize?'.http_build_query($params);
        }
        $redirect = UserHandlers::checkRedirectParam();

        return $redirect ?: SITE_SERVER_NAME.'/?'.http_build_query([
            'code' => 404,
            'message' => 'Client not found',
        ]);
    }

    public function authorizeCode(Request $request, Response $response): Response
    {
        try {
            // Validate the HTTP request and return an AuthorizationRequest object.
            // The auth request object can be serialized into a user's session
            $authRequest = $this->server->validateAuthorizationRequest($request);
            // Once the user has logged in set the user on the AuthorizationRequest
            $userEntity = new UserEntity();
            $authRequest->setUser($userEntity);
            // Once the user has approved or denied the client update the status
            // (true = approved, false = denied)
            $authRequest->setAuthorizationApproved($userEntity->isUserExist());

            // Return the HTTP redirect response
            return $this->server->completeAuthorizationRequest($authRequest, $response);
        } catch (OAuthServerException $exception) {
            return $exception->generateHttpResponse($response);
        } catch (\Exception $exception) {
            return $this->withJson($request, $response, [
                'code' => $exception->getCode(),
                'message' => $exception->getMessage(),
            ])->withStatus(500);
        }
    }

    public function accessToken(Request $request, Response $response): Response
    {
        try {
            return $this->server->respondToAccessTokenRequest($request, $response);
        } catch (OAuthServerException $exception) {
            return $exception->generateHttpResponse($response);
        } catch (\Exception $exception) {
            return $this->withJson($request, $response, [
                'code' => $exception->getCode(),
                'message' => $exception->getMessage(),
            ])->withStatus(500);
        }
    }

    public function userInfo(Request $request, Response $response): Response
    {
        try {
            $resourceServer = self::resourceServerFactory();
            $validated = $resourceServer->validateAuthenticatedRequest($request);
            $token = $this->tokenRepository->getAccessToken($validated->getAttribute('oauth_access_token_id'));
            $claimsRequested = $token->getClaims();
            foreach ($token->getScopes() as $scope) {
                $claims = $this->userRepository->getClaims(
                    $this->claimRepository,
                    $scope
                );
                if (count($claims) > 0) {
                    array_push($claimsRequested, ...$claims);
                }
            }

            return $this->withJson($request, $response, $this->userRepository->getUserInfoAttributes(
                $this->userRepository->getUserByIdentifier(
                    $validated->getAttribute('oauth_user_id')
                ),
                $claimsRequested,
                $token->getScopes()
            ));
        } catch (OAuthServerException $exception) {
            return $exception->generateHttpResponse($response);
        } catch (\Exception $exception) {
            return $this->withJson($request, $response, [
                'code' => $exception->getCode(),
                'message' => $exception->getMessage(),
            ])->withStatus(500);
        }
    }

    public function logout(Request $request, Response $response): Response
    {
        try {
            $user = user();
            if ($user->isAuthorized()) {
                $idToken = $this->getParam(
                    $request,
                    $response,
                    'id_token_hint'
                );
                if ($idToken) {
                    $jwtConfiguration = Configuration::forSymmetricSigner(
                        new Sha256(),
                        InMemory::plainText('empty', 'empty')
                    );

                    $token = $jwtConfiguration->parser()->parse($idToken);
                    if (!$token->isRelatedTo($user->id)) {
                        return $this->errorNotFound($request, $response);
                    }
                }
                $accessToken = OauthTokens::getTokenByUserId($user->id);
                if ($accessToken) {
                    $refreshToken = OauthRefreshTokens::getTokenByAccessIdentifier($accessToken->getIdentifier());
                    if ($refreshToken) {
                        $refreshToken->delete();
                    }
                    $accessToken->delete();
                }

                $user->logout();
            }
            $redirectUri = $this->getParam(
                $request,
                $response,
                'post_logout_redirect_uri'
            );

            $state = $this->getParam(
                $request,
                $response,
                'state'
            );

            return $this->withRedirect($request, $response, ($redirectUri ?: 'https://').($state ? '?state='.$state : ''));
        } catch (\Exception $exception) {
            return $this->withJson($request, $response, [
                'code' => $exception->getCode(),
                'message' => $exception->getMessage(),
            ])->withStatus(500);
        }
    }

    public function jwksKeys(Request $request, Response $response): Response
    {
        $publicKeyPath = 'file://'.env('PUBLIC_KEY');
        $options = [
            'use' => 'sig',
            'alg' => self::alg,
            'kid' => CryptKey::generateKeyId(),
        ];

        $keyFactory = new KeyFactory();
        $key = $keyFactory->createFromPem($publicKeyPath, $options);
        $keySet = new KeySet();
        $keySet->addKey($key);

        return $this->withJson($request, $response, $keySet);
    }

    public function configuration(Request $request, Response $response): Response
    {
        $scopeRepository = new ScopeRepository();
        $claimRepository = new ClaimRepository();

        return $this->withJson($request, $response, [
            'issuer' => 'https://'.SITE_SERVER_NAME,
            'authorization_endpoint' => 'https://'.SITE_SERVER_NAME.'/api/authorize',
            'end_session_endpoint' => 'https://'.SITE_SERVER_NAME.'/api/logout',
            'token_endpoint' => 'https://'.SITE_SERVER_NAME.'/api/access_token',
            'userinfo_endpoint' => 'https://'.SITE_SERVER_NAME.'/api/v2/user',
            'registration_endpoint' => 'https://'.SITE_SERVER_NAME.'/?register=yes',
            'jwks_uri' => 'https://'.SITE_SERVER_NAME.'/api/certs',
            'scopes_supported' => $scopeRepository->getScopes(),
            'response_types_supported' => [
                'code',
                'none',
                'id_token',
                'token',
                'id_token token',
                'code id_token',
                'code token',
                'code id_token token',
            ],
            'response_modes_supported' => [
                'query',
                'fragment',
                'form_post',
            ],
            'grant_types_supported' => [
                'authorization_code',
                'refresh_token',
                'password',
            ],
            'subject_types_supported' => [
                ClaimEntityInterface::TYPE_USERINFO,
            ],
            'id_token_signing_alg_values_supported' => [
                self::alg,
            ],
            'claims_supported' => $claimRepository->getClaims(),
        ]);
    }

    private static function getCustomVariables(ClientEntityInterface $client): array
    {
        $params = [];
        foreach ($client->getCustomVariables() as $var) {
            $params[$var] = $_GET[$var] ?: $_COOKIE[AuthorizationMiddleware::sso_prefix.$var];
        }

        return $params;
    }
}
