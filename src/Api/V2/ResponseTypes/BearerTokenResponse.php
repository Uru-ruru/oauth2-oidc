<?php

namespace App\Api\V2\ResponseTypes;

use App\Api\V2\CryptKey;
use App\Api\V2\Entities\Interfaces\ClaimEntityInterface;
use App\Api\V2\ResponseTypes\Interfaces\ResponseTypeInterface;
use League\OAuth2\Server\CryptKey as BaseCryptKey;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\ResponseTypes\BearerTokenResponse as LeagueBearerTokenResponse;

/**
 * Class BearerTokenResponse.
 */
class BearerTokenResponse extends LeagueBearerTokenResponse implements ResponseTypeInterface
{
    /**
     * @var null|mixed
     */
    protected $idToken;

    /**
     * @var BaseCryptKey|CryptKey
     */
    protected $privateKey;

    public function setIdToken($idToken): void
    {
        $this->idToken = $idToken;
    }

    public function getIdToken(): mixed
    {
        return $this->idToken;
    }

    public function getAccessToken(): AccessTokenEntityInterface
    {
        return $this->accessToken;
    }

    protected function getExtraParams(AccessTokenEntityInterface $accessToken): array
    {
        /*
         The Claims requested by the profile, email, address, and phone scope values
         are returned from the UserInfo Endpoint, as described in Section 5.3.2,
         when a response_type value is used that results in an Access Token being issued.
         However, when no Access Token is issued (which is the case for the response_type
         value id_token), the resulting Claims are returned in the ID Token.
         */
        if (null !== $this->getIdToken()) {
            $idToken = $this->getIdToken()->convertToJWT($this->privateKey);

            // FIXME: Since an AuthorizationServer does not get re-created for every call, the BearerTokenResponse object does not either.
            // Clear the IdToken since it should be set seperatly for every request
            $this->setIdToken(null);

            return [
                ClaimEntityInterface::TYPE_ID_TOKEN => $idToken->toString(),
            ];
        }

        return [];
    }
}
