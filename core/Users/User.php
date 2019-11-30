<?php


namespace Core\Users;


use Core\DataBase\DB;
use Exception;

class User
{
	/**
	 * ID пользователя в базе данных
	 *
	 * @var integer
	 */
	public $id;

	/**
	 * ID пользователя в VK
	 *
	 * @var integer
	 */
	public $peerId;

	/**
	 * Данные пользователя
	 *
	 * @var array
	 */
	public $data;

	/**
	 * User constructor.
	 *
	 * @param int $id - ID пользователя из базы данных
	 * @param int $peerId - Peer_ID от VK
	 */
	public function __construct(int $id, int $peerId)
	{
		$this->id     = $id;
		$this->peerId = $peerId;
	}

	/**
	 * Ищет в БД ID пользователя по его Peer_ID и возаращает его
	 *
	 * @param int $peerId
	 * @return bool|mixed
	 * @throws Exception
	 */
	public static function findByPeerId (int $peerId)
	{
		return DB::getCol('SELECT `id` FROM `users` WHERE `peer_id` = ?', array ($peerId));
	}

	/**
	 * Регистрирует пользователя в системе и озвращает его ID
	 *
	 * @param int $peerId
	 * @return bool|int
	 * @throws Exception
	 */
	public static function register (int $peerId)
	{
		DB::query('INSERT INTO `users` SET `peer_id` = ?, `waiting` = ?', array($peerId, 'group_name'));
		return DB::insertId();
	}

	/**
	 * Загружает данные о пользователе
	 *
	 * @throws Exception
	 */
	public function loadData ()
	{
		$this->data = DB::getRow('SELECT * FROM `users` WHERE `id` = ' . $this->id);
	}

	/**
	 * Изменяет данные пользователя
	 *
	 * @param string $param
	 * @param $value
	 * @return bool
	 * @throws Exception
	 */
	public function update (string $param, $value)
	{
		if (!DB::query ('UPDATE `users` SET `' . $param . '` = ? WHERE `id` = ' . $this->id, array ($value)))
			return false;
		return true;
	}
}