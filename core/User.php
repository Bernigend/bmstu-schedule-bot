<?php


namespace Core;



abstract class User
{
	/**
	 * Идентификатор пользователя в базе данных
	 *
	 * @var integer
	 */
	public $id;

	/**
	 * Данные пользователя из базы данных
	 *
	 * @var object
	 */
	public $data;

	/**
	 * User constructor.
	 *
	 * @param $id
	 */
	public abstract function __construct ($id);

	/**
	 * Изменяет данные пользователя в базе данных
	 *
	 * @param string $column - стобец таблицы базы данных
	 * @param $value - новое значение
	 * @return bool - true, если изменение прошло успешно
	 */
	public abstract function update (string $column, $value) : bool;

	/**
	 * Загружает данные пользователя из базы данных в переменную $this->data
	 *
	 * @return object - загруженные данные из базы данных
	 */
	public abstract function loadData () : object;

	/**
	 * Поиск пользователя в баз данных по его идентификатору
	 *
	 * @param $id - специфичный для этой таблицы БД идентификатор пользователя
	 * @return integer|false - ID пользователя в базе данных, либо false, если тот не найден
	 */
	public static abstract function find ($id);

	/**
	 * Регистрирует пользователя в системе, добавляя информацию о нём в базу данных
	 *
	 * @param $id - специфичный для этой таблицы БД идентификатор пользователя
	 * @return int - ID нового пользователя из базы данных
	 */
	public static abstract function register ($id) : int;
}