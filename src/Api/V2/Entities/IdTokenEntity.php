<?php

namespace App\Api\V2\Entities;

use App\Api\V2\CryptKey;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Token\Plain;

/**
 * Class IdTokenEntity.
 *
 * @see https://openid.net/specs/openid-connect-core-1_0.html
 */
class IdTokenEntity
{
    /**
     * REQUIRED. Issuer Identifier for the Issuer of the response.
     * The iss value is a case sensitive URL using the https scheme that contains scheme, host, and optionally,
     * port number and path components and no query or fragment components.
     */
    protected string $issuer;

    /**
     * REQUIRED. Subject Identifier.
     * A locally unique and never reassigned identifier within the Issuer for the End-User, which is intended to be consumed by the Client, e.g.,
     * 24400320 or AItOawmwtWwcT0k51BayewNvutrJUqsvl6qs7A4. It MUST NOT exceed 255 ASCII characters in length.
     * The sub value is a case sensitive string.
     */
    protected string $subject;

    /**
     * REQUIRED. Audience(s) that this ID Token is intended for.
     * It MUST contain the OAuth 2.0 client_id of the Relying Party as an audience value. It MAY also contain identifiers for other audiences.
     * In the general case, the aud value is an array of case sensitive strings. In the common special case when there is one audience,
     * the aud value MAY be a single case sensitive string.
     */
    protected string $audience;

    /**
     * REQUIRED. Expiration time on or after which the ID Token MUST NOT be accepted for processing.
     * The processing of this parameter requires that the current date/time MUST be before the expiration date/time listed in the value.
     * Implementers MAY provide for some small leeway, usually no more than a few minutes, to account for clock skew.
     * Its value is a JSON number representing the number of seconds from 1970-01-01T0:0:0Z as measured in UTC until the date/time.
     * See RFC 3339 [RFC3339] for details regarding date/times in general and UTC in particular.
     */
    protected \DateTimeImmutable $expiration;

    /**
     * REQUIRED. Time at which the JWT was issued.
     * Its value is a JSON number representing the number of seconds from 1970-01-01T0:0:0Z as measured in UTC until the date/time.
     */
    protected \DateTimeImmutable $iat; // Time at which the JWT was issued

    /**
     * Time when the End-User authentication occurred.
     * Its value is a JSON number representing the number of seconds from 1970-01-01T0:0:0Z as measured in UTC until the date/time.
     * When a max_age request is made or when auth_time is requested as an Essential Claim, then this Claim is REQUIRED;
     * otherwise, its inclusion is OPTIONAL. (The auth_time Claim semantically corresponds to the OpenID 2.0 PAPE [OpenID.PAPE] auth_time response parameter.).
     */
    protected \DateTime $authTime;

    /**
     * String value used to associate a Client session with an ID Token, and to mitigate replay attacks.
     * The value is passed through unmodified from the Authentication Request to the ID Token.
     * If present in the ID Token, Clients MUST verify that the nonce Claim Value is equal to the value of the nonce parameter sent in the Authentication Request.
     * If present in the Authentication Request, Authorization Servers MUST include a nonce Claim in the ID Token with
     * the Claim Value being the nonce value sent in the Authentication Request. Authorization Servers SHOULD perform no other processing on nonce values used.
     * The nonce value is a case sensitive string.
     */
    protected ?string $nonce;

    /**
     * OPTIONAL. Authentication Context Class Reference.
     * String specifying an Authentication Context Class Reference value that identifies the Authentication Context Class that the authentication performed satisfied.
     * The value "0" indicates the End-User authentication did not meet the requirements of ISO/IEC 29115 [ISO29115] level 1.
     * Authentication using a long-lived browser cookie, for instance, is one example where the use of "level 0" is appropriate.
     * Authentications with level 0 SHOULD NOT be used to authorize access to any resource of any monetary value.
     * (This corresponds to the OpenID 2.0 PAPE [OpenID.PAPE] nist_auth_level 0.) An absolute URI or an RFC 6711 [RFC6711] registered name SHOULD be used as the acr value;
     * registered names MUST NOT be used with a different meaning than that which is registered. Parties using this claim will need to agree upon the meanings of the values used,
     * which may be context-specific. The acr value is a case sensitive string.
     */
    protected ?string $acr; // Authentication Context Class Reference

    /**
     * OPTIONAL. Authentication Methods References.
     * JSON array of strings that are identifiers for authentication methods used in the authentication.
     * For instance, values might indicate that both password and OTP authentication methods were used.
     * The definition of particular values to be used in the amr Claim is beyond the scope of this specification.
     * Parties using this claim will need to agree upon the meanings of the values used, which may be context-specific.
     * The amr value is an array of case sensitive strings.
     *
     * @var array|string[]
     */
    protected array $amr = []; // Authentication Methods References

    /**
     * OPTIONAL. Authorized party - the party to which the ID Token was issued.
     * If present, it MUST contain the OAuth 2.0 Client ID of this party.
     * This Claim is only needed when the ID Token has a single audience value and that audience is different than the authorized party.
     * It MAY be included even when the authorized party is the same as the sole audience.
     * The azp value is a case sensitive string containing a StringOrURI value.
     */
    protected ?string $azp; // Authorized party

    protected string $identified;

    protected array $extra = [];

    public function __construct()
    {
        $this->iat = new \DateTimeImmutable();
        $this->authTime = new \DateTime();
    }

    /**
     * @param CryptKey|\League\OAuth2\Server\CryptKey $privateKey
     */
    public function convertToJWT($privateKey): Plain
    {
        $config = Configuration::forAsymmetricSigner(
            new Sha256(),
            InMemory::plainText($privateKey->getKeyContents()),
            InMemory::plainText($privateKey->getKeyContents())
        );

        $token = $config->builder()
            ->withHeader('kid', method_exists($privateKey, 'getKid') ? $privateKey->getKid() : CryptKey::generateKeyId())
            ->issuedBy($this->getIssuer() ?? 'https://'.SITE_SERVER_NAME)
            ->withHeader('sub', $this->getSubject())
            ->relatedTo($this->getSubject())
            ->permittedFor($this->getAudience())
            ->expiresAt($this->getExpiration())
            ->issuedAt($this->getIat())
            ->identifiedBy($this->getIdentified() ?? '123')
            ->withClaim('auth_time', $this->getAuthTime()->getTimestamp())
            ->withClaim('nonce', $this->getNonce())
        ;

        foreach ($this->extra as $key => $value) {
            $token->withClaim($key, $value);
        }

        return $token->getToken($config->signer(), $config->signingKey());
    }

    /**
     * Get the value of subject.
     */
    public function getSubject(): string
    {
        return $this->subject;
    }

    /**
     * Set the value of subject.
     *
     * @param mixed $subject
     */
    public function setSubject($subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Get the value of audience.
     */
    public function getAudience(): string
    {
        return $this->audience;
    }

    /**
     * Set the value of audience.
     *
     * @param mixed $audience
     */
    public function setAudience($audience): self
    {
        $this->audience = $audience;

        return $this;
    }

    /**
     * Get the value of expiration.
     */
    public function getExpiration(): \DateTimeImmutable
    {
        return $this->expiration;
    }

    /**
     * Set the value of expiration.
     */
    public function setExpiration(\DateTimeImmutable $expiration): IdTokenEntity
    {
        $this->expiration = $expiration;

        return $this;
    }

    /**
     * Get the value of iat.
     */
    public function getIat(): \DateTimeImmutable
    {
        return $this->iat;
    }

    /**
     * Set the value of iat.
     */
    public function setIat(\DateTimeImmutable $iat): self
    {
        $this->iat = $iat;

        return $this;
    }

    /**
     * Get the value of authTime.
     */
    public function getAuthTime(): \DateTime
    {
        return $this->authTime;
    }

    /**
     * Set the value of authTime.
     */
    public function setAuthTime(\DateTime $authTime): self
    {
        $this->authTime = $authTime;

        return $this;
    }

    /**
     * Get the value of nonce.
     */
    public function getNonce(): ?string
    {
        return $this->nonce;
    }

    /**
     * Set the value of nonce.
     */
    public function setNonce(?string $nonce): self
    {
        $this->nonce = $nonce;

        return $this;
    }

    /**
     * Get the value of acr.
     */
    public function getAcr(): ?string
    {
        return $this->acr;
    }

    /**
     * Set the value of acr.
     */
    public function setAcr(?string $acr): self
    {
        $this->acr = $acr;

        return $this;
    }

    /**
     * Get the value of amr.
     *
     * @return array|string[]
     */
    public function getAmr(): array
    {
        return $this->amr;
    }

    /**
     * Set the value of amr.
     */
    public function setAmr(?array $amr): self
    {
        $this->amr = $amr ?? [];

        return $this;
    }

    /**
     * Get the value of azp.
     */
    public function getAzp(): ?string
    {
        return $this->azp;
    }

    /**
     * Set the value of azp.
     */
    public function setAzp(?string $azp): self
    {
        $this->azp = $azp;

        return $this;
    }

    /**
     * Get the value of issuer.
     */
    public function getIssuer(): string
    {
        return $this->issuer;
    }

    /**
     * Set the value of issuer.
     */
    public function setIssuer(string $issuer): self
    {
        $this->issuer = $issuer;

        return $this;
    }

    public function getIdentified(): string
    {
        return $this->identified;
    }

    public function setIdentified(string $identified): void
    {
        $this->identified = $identified;
    }

    public function addExtra($key, $value): void
    {
        $this->extra[$key] = $value;
    }
}
