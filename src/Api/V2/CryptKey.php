<?php

namespace App\Api\V2;

use League\OAuth2\Server\CryptKey as BaseCryptKey;

/**
 * Class CryptKey.
 */
class CryptKey extends BaseCryptKey
{
    /**
     * The key id. The key is found on the Json Web Key Set (JWKS) endpoint of the issuer.
     */
    public ?string $kid = null;

    public function __construct($keyPath, $passPhrase = null, bool $keyPermissionsCheck = true)
    {
        parent::__construct($keyPath, $passPhrase, $keyPermissionsCheck);
        $this->setKid(self::generateKeyId());
    }

    public function getKid(): ?string
    {
        return $this->kid;
    }

    public function setKid($kid): self
    {
        $this->kid = $kid;

        return $this;
    }

    public static function generateKeyId($value = null): string
    {
        return hash('sha256', $value ?: SITE_SERVER_NAME);
    }
}
