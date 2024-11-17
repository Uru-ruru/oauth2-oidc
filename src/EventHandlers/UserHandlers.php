<?php

namespace App\EventHandlers;

use App\Api\V2\Oauth2Controller;

/**
 * Class UserHandlers.
 */
class UserHandlers
{
    /**
     * Редиректим пользователей на страницу по умолчанию (или в переадресовываем).
     *
     * @param mixed $fields
     */
    public function redirectAfterLogin(&$fields): void
    {
        $from = static::checkFromParam();
        $url = false;
        if ($fields['USER_ID'] && $from) {
            if ('oauth2' === $from) {
                $url = Oauth2Controller::getAuthorizationLink(static::checkClientParam());
            }
            unset($_COOKIE['sso_from']);
            setcookie('sso_from', null, -1, '/');
            LocalRedirect($url, true);
        }

        if ($fields && 'ERROR' !== $fields['RESULT_MESSAGE']['TYPE']) {
            $uri = parse_url($_SERVER['REQUEST_URI']);
            if ('/' === $uri['path']) {
                LocalRedirect('/');
            }
        }
    }

    public static function checkRedirectParam(): false|string
    {
        return $_GET['redirect_uri'] ?? $_COOKIE['sso_redirect_uri'] ?? false;
    }

    public static function checkFromParam(): false|string
    {
        return $_GET['from'] ?? $_COOKIE['sso_from'] ?? false;
    }

    public static function checkClientParam(): false|string
    {
        return $_GET['client_id'] ?? $_COOKIE['sso_client_id'] ?? false;
    }
}
