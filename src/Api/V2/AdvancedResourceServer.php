<?php

namespace App\Api\V2;

use League\OAuth2\Server\AuthorizationValidators\AuthorizationValidatorInterface;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;
use League\OAuth2\Server\ResourceServer;

/**
 * Class AdvancedResourceServer.
 */
class AdvancedResourceServer extends ResourceServer
{
    /**
     * New server instance.
     *
     * @param CryptKey|string $publicKey
     */
    public function __construct(
        AccessTokenRepositoryInterface $accessTokenRepository,
        private $publicKey,
        ?AuthorizationValidatorInterface $authorizationValidator = null
    ) {
        parent::__construct($accessTokenRepository, $publicKey, $authorizationValidator);

        if (false === $publicKey instanceof CryptKey) {
            $publicKey = new CryptKey($publicKey);
        }
        $this->publicKey = $publicKey;
    }
}
