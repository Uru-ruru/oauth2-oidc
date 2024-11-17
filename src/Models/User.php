<?php

namespace App\Models;

use Uru\BitrixModels\Models\UserModel;

/**
 * Расширенный класс Пользователя - Партнера - Разработчика
 * Class User.
 */
class User extends UserModel
{
    /**
     * Id пользователя.
     */
    public function getId(): ?int
    {
        return $this['ID'];
    }

    /**
     * Получить имя.
     *
     * @return string
     */
    public function getName(): ?string
    {
        return $this['NAME'];
    }

    /**
     * Получить фамилию.
     *
     * @return string
     */
    public function getLastName(): ?string
    {
        return $this['LAST_NAME'];
    }

    /**
     * Получить Email.
     *
     * @return string
     */
    public function getEmail(): ?string
    {
        return $this['EMAIL'];
    }

    /**
     * Получить номер телефона.
     *
     * @return string
     */
    public function getPhone(): ?string
    {
        return $this['PERSONAL_PHONE'];
    }

    /**
     * Получить ID скайпа.
     *
     * @return string
     */
    public function getSkype(): ?string
    {
        return $this['UF_SKYPE'];
    }

    /**
     * Проверяем, является ли $password текущим паролем пользователя.
     */
    public function isUserPassword(string $password): bool
    {
        global $USER;
        if (!is_object($USER)) {
            $USER = new CUser();
        }
        $result = $USER->Login($this['LOGIN'], $password);

        if (is_bool($result)) {
            return $result;
        }

        return false;
    }
}
