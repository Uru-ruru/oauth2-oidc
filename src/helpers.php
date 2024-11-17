<?php

use App\Models\User;
use Bitrix\Main\Entity\Base;
use Monolog\Logger;
use Monolog\Registry;
use Uru\BitrixIblockHelper\HLblock;
use Uru\BitrixIblockHelper\IblockId;

/**
 * Получение ID инфоблока по коду (или по коду и типу).
 * Всегда выполняет лишь один запрос в БД на скрипт.
 *
 * @throws RuntimeException
 */
function iblock_id(string $code, ?string $type = null): int
{
    return IblockId::getByCode($code, $type);
}

/**
 * Получение данных хайлоадблока по названию его таблицы.
 * Всегда выполняет лишь один запрос в БД на скрипт и возвращает массив вида:.
 *
 * array:3 [
 *   "ID" => "2"
 *   "NAME" => "Subscribers"
 *   "TABLE_NAME" => "app_subscribers"
 * ]
 */
function highloadblock(string $table): array
{
    return HLblock::getByTableName($table);
}

/**
 * Компилирование и возвращение класса для хайлоадблока для таблицы $table.
 *
 * Пример для таблицы `app_subscribers`:
 * $subscribers = highloadblock_class('app_subscribers');
 * $subscribers::getList();
 */
function highloadblock_class(string $table): string
{
    return HLblock::compileClass($table);
}

/**
 * Компилирование сущности для хайлоадблока для таблицы $table.
 * Выполняется один раз.
 *
 * Пример для таблицы `app_subscribers`:
 * $entity = \Uru\BitrixIblockHelper\HLblock::compileEntity('app_subscribers');
 * $query = new Entity\Query($entity);
 */
function highloadblock_entity(string $table): Base
{
    return HLblock::compileEntity($table);
}

/**
 * logger()->error('Error message here');.
 */
function logger(string $name = 'common'): Logger
{
    return Registry::getInstance($name);
}

/**
 * @return bool|User
 */
function user(?int $id = null)
{
    return is_null($id) ? User::current() : User::query()->getById($id);
}

/**
 * Получить объект работы с датой.
 */
function datetime(string $date, ?string $format = null): DateTime
{
    if (is_null($format)) {
        $dateTime = DateTime::createFromFormat(date_format_full(), $date);
        if ($dateTime) {
            return $dateTime;
        }

        $format = date_format_short();
    }

    return DateTime::createFromFormat($format, $date);
}

/**
 * @param bool $midnight Получить не текущее время, а полночь
 */
function date_format_full(bool $midnight = false): string
{
    global $DB;
    $format = $DB->DateFormatToPHP(CSite::GetDateFormat());

    if ($midnight) {
        return str_replace(
            ['H', 'i', 's'],
            '00',
            $format
        );
    }

    return $format;
}

function date_format_short(): string
{
    global $DB;

    return $DB->DateFormatToPHP(CSite::GetDateFormat('SHORT'));
}

/**
 * @param bool $dateOnly Показывать только дату или еще и время
 */
function date_format_filter(bool $dateOnly = false): string
{
    return $dateOnly ? 'Y-m-d' : 'Y-m-d H:i:s';
}
