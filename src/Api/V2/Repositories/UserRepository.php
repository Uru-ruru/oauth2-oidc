<?php

namespace App\Api\V2\Repositories;

use App\Api\V2\Entities\AccessTokenEntity;
use App\Api\V2\Entities\Interfaces\ClaimEntityInterface;
use App\Api\V2\Entities\Interfaces\UserEntityInterface;
use App\Api\V2\Entities\UserEntity;
use App\Api\V2\Repositories\Interfaces\ClaimRepositoryInterface;
use App\Api\V2\Repositories\Interfaces\UserRepositoryInterface;
use App\Models\Partner\PartnerStatus;
use App\Models\User;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;

/**
 * Class UserRepository
 * @package App\Api\V2\Repositories
 */
class UserRepository implements UserRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function getUserEntityByUserCredentials($username, $password, $grantType, ClientEntityInterface $clientEntity)
    {
        $user = User::getByLogin($username);
        if ($user) {
            return $user->isUserPassword($password) ? (new UserEntity($user)) : null;
        }
        return null;
    }

    /**
     * @param ClaimRepositoryInterface $claimRepository
     * @param ScopeEntityInterface $scope
     * @return array|ClaimEntityInterface[]
     */
    public function getClaims(ClaimRepositoryInterface $claimRepository, ScopeEntityInterface $scope): array
    {
        return $claimRepository->getClaimsByScope($scope);
    }

    /**
     * @param $userEntity
     * @param $claims
     * @param $scopes
     * @return array
     */
    public function getAttributes($userEntity, $claims, $scopes): array
    {
        $user = $userEntity->isUserExist() ? $userEntity->getUser() : user();
        return [
            'id' => $user->getId(),
            'name' => $user->getName(),
            'last_name' => $user->getLastName(),
            'email' => $user->getEmail(),
        ];
    }

    /**
     * @param UserEntityInterface $userEntity
     * @param $claims
     * @param $scopes
     * @return array
     */
    public function getUserInfoAttributes(UserEntityInterface $userEntity, $claims, $scopes): array
    {
        $user = $userEntity->isUserExist() ? $userEntity->getUser() : user();
        $accessToken = new AccessTokenEntity();
        $accessToken->setUsertype($user);
        return [
            'sub' => $user->getId(),
            'givenname' => $user->getName(),
            'surname' => $user->getLastName(),
            'email' => $user->getEmail(),
            'email_verified' => true,
            'phone_number' => $user->getPhone(),
            'phone_number_verified' => false,
            'skype' => $user->getSkype(),
            'scopes' => $scopes,
            'usertype' => $accessToken->getUsertype()
        ];
    }

    /**
     * @param $identifier
     * @return UserEntityInterface
     */
    public function getUserByIdentifier($identifier): UserEntityInterface
    {
        $user = user($identifier);
        return new UserEntity($user);
    }
}
