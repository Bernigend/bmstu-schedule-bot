<?php


namespace Core\VK;


use Core\Config;
use Core\DataBase as DB;
use Core\User;
use Exception;

class VKUser extends User
{
	/**
	 * User constructor.
	 *
	 * @param $id
	 */
	public function __construct ($id)
	{
		$this->id = $id;
	}

	/**
	 * Поиск пользователя в баз данных по его идентификатору
	 *
	 * @param $peerId - специфичный для этой таблицы БД идентификатор пользователя
	 * @return integer|false - ID пользователя в базе данных, либо false, если тот не найден
	 * @throws Exception
	 */
	public static function find ($peerId)
	{
		return DB::getOne ('SELECT `id` FROM `' . Config::DB_PREFIX . 'users_vk` WHERE `peer_id` = ?', array ($peerId));
	}

	/**
	 * Регистрирует пользователя в системе, добавляя информацию о нём в базу данных
	 *
	 * @param $peerId - специфичный для этой таблицы БД идентификатор пользователя
	 * @return int - ID нового пользователя из базы данных
	 * @throws Exception
	 */
	public static function register ($peerId) : int
	{
		DB::query ('INSERT INTO `' . Config::DB_PREFIX . 'users_vk` SET `peer_id` = ?', array ($peerId));
		return DB::insertId();
	}

	/**
	 * Изменяет данные пользователя в базе данных
	 *
	 * @param string $column - стобец таблицы базы данных
	 * @param $value - новое значение
	 * @return bool - true, если изменение прошло успешно
	 * @throws Exception
	 */
	public function update (string $column, $value) : bool
	{
		DB::query ('UPDATE `' . Config::DB_PREFIX . 'users_vk` SET `' . $column . '` = ? WHERE `id` = ?', array ($value, $this->id));
		return true;
	}

	/**
	 * Загружает данные пользователя из базы данных в переменную $this->data
	 *
	 * @return object - загруженные данные из базы данных
	 * @throws Exception
	 */
	public function loadData () : object
	{
		$this->data = DB::query ('SELECT * FROM `' . Config::DB_PREFIX . 'users_vk` WHERE `id` = ?', array ($this->id))->fetchObject();
		return $this->data;
	}
}