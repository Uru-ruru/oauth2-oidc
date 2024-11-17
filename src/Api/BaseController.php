<?php

namespace App\Api;

use App\Models\HL\OauthTokens;
use App\Models\User;
use Psr\Http\Message\ServerRequestInterface;
use Uru\SlimApiController\ApiController;

/**
 * Class BaseController.
 */
abstract class BaseController extends ApiController
{
    protected static function Authorize(): false|\CUser
    {
        global $USER;

        if (!is_object($USER)) {
            $USER = new \CUser();
        }

        if (!$USER->isAuthorized()) {
            $cookie_login = $_COOKIE[\COption::GetOptionString('main', 'cookie_name', 'BITRIX_SM').'_LOGIN'];
            $cookie_md5pass = $_COOKIE[\COption::GetOptionString('main', 'cookie_name', 'BITRIX_SM').'_UIDH'];

            if ($cookie_login && $cookie_md5pass && $USER->LoginByHash($cookie_login, $cookie_md5pass)) {
                return $USER;
            }

            return false;
        }

        return $USER;
    }

    protected function getUserByOauthToken(ServerRequestInterface $request): ?User
    {
        $tokenId = $request->getAttribute('oauth_access_token_id');
        $userId = $request->getAttribute('oauth_user_id');
        $token = OauthTokens::getTokenByIdentifier($tokenId);
        if ($token && $token->user->getId() === (int) $userId) {
            return $token->user;
        }

        return null;
    }
}
