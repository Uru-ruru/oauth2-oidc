<?php

namespace App\Api\V2;

use League\OAuth2\Server\RequestTypes\AuthorizationRequest;

/**
 * Class AuthenticationRequest.
 */
class AuthenticationRequest extends AuthorizationRequest
{
    protected ?string $nonce;

    protected $prompt;

    protected $maxAge;

    protected array $uiLocates = []; // (space-separated list of BCP47 [RFC5646] language tag)

    protected $idTokenHint;

    protected $loginHint;

    protected $display;

    protected array $acrValues = [];

    protected ?string $responseType = null;

    protected ?string $responseMode = null; // query, fragment,

    protected ?array $claims = [];

    protected SessionInformation $sessionInformation;

    public static function fromAuthorizationRequest(AuthorizationRequest $authorizationRequest): AuthenticationRequest
    {
        if ($authorizationRequest instanceof self) {
            return $authorizationRequest;
        }

        $result = new self();

        $result->setClient($authorizationRequest->getClient());
        $result->setCodeChallenge($authorizationRequest->getCodeChallenge());
        $result->setCodeChallengeMethod($authorizationRequest->getCodeChallengeMethod());
        $result->setGrantTypeId($authorizationRequest->getGrantTypeId());
        $result->setRedirectUri($authorizationRequest->getRedirectUri());
        $result->setScopes($authorizationRequest->getScopes());
        $result->setState($authorizationRequest->getState());

        if (null !== $authorizationRequest->getUser()) {
            $result->setUser($authorizationRequest->getUser());
        }

        return $result;
    }

    /**
     * @return $this
     */
    public function setSessionInformation(SessionInformation $sessionInformation): AuthenticationRequest
    {
        $this->sessionInformation = $sessionInformation;

        return $this;
    }

    public function getSessionInformation(): SessionInformation
    {
        return $this->sessionInformation ?? new SessionInformation();
    }

    public function getNonce(): ?string
    {
        return $this->nonce;
    }

    public function setNonce(?string $nonce): void
    {
        $this->nonce = $nonce;
    }

    public function setPrompt($prompt): void
    {
        $this->prompt = $prompt;
    }

    /**
     * @return mixed
     */
    public function getPrompt()
    {
        return $this->prompt;
    }

    public function setMaxAge($maxAge): void
    {
        $this->maxAge = $maxAge;
    }

    /**
     * @return mixed
     */
    public function getMaxAge()
    {
        return $this->maxAge;
    }

    public function setUILocales(array $uiLocales): void
    {
        $this->uiLocates = $uiLocales;
    }

    public function getUILocales(): array
    {
        return $this->uiLocates;
    }

    public function setIDTokenHint($idTokenHint)
    {
        $this->idTokenHint = $idTokenHint;
    }

    /**
     * @return mixed
     */
    public function getIDTokenHint()
    {
        return $this->idTokenHint;
    }

    public function setLoginHint($loginHint): void
    {
        $this->loginHint = $loginHint;
    }

    /**
     * @return mixed
     */
    public function getLoginHint()
    {
        return $this->loginHint;
    }

    public function setDisplay($display): void
    {
        $this->display = $display;
    }

    /**
     * @return mixed
     */
    public function getDisplay()
    {
        return $this->display;
    }

    public function setAcrValues(array $acrValues): void
    {
        $this->acrValues = $acrValues;
    }

    /**
     * @return array
     */
    public function getAcrValues()
    {
        return $this->acrValues;
    }

    public function setClaims(?array $claims): void
    {
        $this->claims = $claims;
    }

    public function getClaims(): ?array
    {
        return $this->claims;
    }

    /**
     * Get the value of responseType.
     */
    public function getResponseType(): ?string
    {
        return $this->responseType;
    }

    /**
     * Set the value of responseType.
     *
     * @param mixed $responseType
     */
    public function setResponseType($responseType): AuthenticationRequest
    {
        $this->responseType = $responseType;

        return $this;
    }

    /**
     * Get the value of responseType.
     */
    public function getResponseMode(): ?string
    {
        return $this->responseMode;
    }

    /**
     * Set the value of responseType.
     *
     * @param mixed $responseMode
     */
    public function setResponseMode($responseMode): AuthenticationRequest
    {
        $this->responseMode = $responseMode;

        return $this;
    }
}
