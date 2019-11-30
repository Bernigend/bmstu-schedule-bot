<?php


namespace Core\Entities;


use Core\Config;
use Core\AUser;
use Core\DataBase as DB;
use Exception;

class VkUser extends AUser
{
	/**
	 * VkUser constructor.
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
		DB::query('UPDATE `' . Config::DB_PREFIX . 'users_vk` SET `' . $column . '` = ? WHERE `id` = ?', array ($value, $this->dbID));
		return true;
	}

	/**
	 * Загружает данные пользователя из базы данных в переменную $this->data
	 *
	 * @throws Exception
	 */
	public function loadData(): void
	{
		$data = DB::getRow('SELECT * FROM `' . Config::DB_PREFIX . 'users_vk` WHERE `id` = ?', array ($this->dbID));
		foreach ($data as $columnName => $value) {
			$this->{$columnName} = $value;
		}
	}

	/**
	 * Поиск пользователя в баз данных по его идентификатору
	 *
	 * @param $peerId - специфичный для этой таблицы БД идентификатор пользователя
	 * @return integer|false - ID пользователя в базе данных, либо false, если тот не найден
	 * @throws Exception
	 */
	public static function find($peerId)
	{
		return DB::getOne('SELECT `id` FROM `' . Config::DB_PREFIX . 'users_vk` WHERE `peer_id` = ?', array ($peerId));
	}

	/**
	 * Регистрирует пользователя в системе, добавляя информацию о нём в базу данных
	 *
	 * @param $peerId - специфичный для этой таблицы БД идентификатор пользователя
	 * @param string|null $expectedInput - требуемый от пользователя ввод
	 * @return int - ID нового пользователя из базы данных
	 * @throws Exception
	 */
	public static function register($peerId, ?string $expectedInput = null): int
	{
		DB::query('INSERT INTO `' . Config::DB_PREFIX . 'users_vk` SET `peer_id` = ?, `expected_input` = ?', array ($peerId, $expectedInput));
		return DB::insertId();
	}
}