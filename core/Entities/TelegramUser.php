<?php
/**
 * Created by PhpStorm.
 * User: Bernigend
 * Date: 12.11.2019
 * Time: 15:33
 */

namespace Core\Entities;


use Core\AUser;
use Core\Config;
use Core\DataBase as DB;
use Exception;

class TelegramUser extends AUser
{
	/**
	 * TelegramUser constructor.
	 *
	 * @param $dbID - ID пользователя в базе данных
	 * @param $destinationID - идентификатор назначения (куда/кому отправлять ответное сообщение)
	 */
	public function __construct($dbID, $destinationID)
	{
		$this->dbID = $dbID;
		$this->destinationID = $destinationID;
	}

	/**
	 * Изменяет данные пользователя в базе данных
	 *
	 * @param string $column - стобец таблицы базы данных
	 * @param $value - новое значение
	 * @return bool - true, если изменение прошло успешно
	 * @throws Exception
	 */
	public function update(string $column, $value): bool
	{
		DB::query('UPDATE `' . Config::DB_PREFIX . 'users_telegram` SET `' . $column . '` = ? WHERE `id` = ?', array ($value, $this->dbID));
		return true;
	}

	/**
	 * Загружает данные пользователя из базы данных в переменную $this->data
	 *
	 * @throws Exception
	 */
	public function loadData(): void
	{
		$data = DB::getRow('SELECT * FROM `' . Config::DB_PREFIX . 'users_telegram` WHERE `id` = ?', array ($this->dbID));
		foreach ($data as $columnName => $value) {
			$this->{$columnName} = $value;
		}
	}

	/**
	 * Поиск пользователя в баз данных по его идентификатору
	 *
	 * @param $chatID - специфичный для этой таблицы БД идентификатор пользователя
	 * @return integer|false - ID пользователя в базе данных, либо false, если тот не найден
	 * @throws Exception
	 */
	public static function find($chatID)
	{
		return DB::getOne('SELECT `id` FROM `' . Config::DB_PREFIX . 'users_telegram` WHERE `chat_id` = ?', array ($chatID));
	}

	/**
	 * Регистрирует пользователя в системе, добавляя информацию о нём в базу данных
	 *
	 * @param $chatID - специфичный для этой таблицы БД идентификатор пользователя
	 * @param string|null $expectedInput - требуемый от пользователя ввод
	 * @return int - ID нового пользователя из базы данных
	 * @throws Exception
	 */
	public static function register($chatID, ?string $expectedInput = null): int
	{
		DB::query ('INSERT INTO `' . Config::DB_PREFIX . 'users_telegram` SET `chat_id` = ?, `expected_input` = ?', array ($chatID, $expectedInput));
		return DB::insertId();
	}
}