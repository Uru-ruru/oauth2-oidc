<?php

namespace App\Api\V2\Entities;

use App\Models\HL\OauthClients;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\Traits\ClientTrait;
use League\OAuth2\Server\Entities\Traits\EntityTrait;

/**
 * Class ClientEntity.
 */
class ClientEntity implements ClientEntityInterface
{
    use EntityTrait;
    use ClientTrait;

    private array $customVariables = [];

    private bool $loaded = false;

    public function __construct($clientIdentifier = null)
    {
        if ($clientIdentifier) {
            $client = OauthClients::getClientByName($clientIdentifier);
            if ($client) {
                $this->setLoaded();
                $this->setName($client->getName());
                $this->setRedirectUri($client->getRedirectUri());
                $this->setCustomVariables($client->getCustomVariables());
                if ($client->isConfidential()) {
                    $this->setConfidential();
                }
            }
        }
    }

    public function isLoaded(): bool
    {
        return $this->loaded;
    }

    /**
     * Set client loaded status.
     */
    public function setLoaded(): void
    {
        $this->loaded = true;
    }

    public function setName($name): void
    {
        $this->name = $name;
    }

    public function setRedirectUri($uri): void
    {
        $this->redirectUri = $uri;
    }

    public function setConfidential(): void
    {
        $this->isConfidential = true;
    }

    public function getCustomVariables(): array
    {
        return $this->customVariables;
    }

    public function setCustomVariables(array $customVariables): void
    {
        $this->customVariables = $customVariables;
    }
}
