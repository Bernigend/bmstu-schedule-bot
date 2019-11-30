<?php


namespace Core;


use Exception;
use PDO;
use PDOStatement;

class DataBase
{
	/**
	 * @var PDO
	 */
	protected static $connection = NULL;

	/**
	 * Выполняет подключение к базе данных
	 */
	public static function connect(): void
	{
		if (is_null(static::$connection)) {
			static::$connection = new PDO('mysql:host=' . Config::DB_HOST . ';dbname=' . Config::DB_NAME . ';charset=' . Config::DB_CHARSET, Config::DB_USER, Config::DB_PASS, array(
				PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
				PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
				PDO::ATTR_EMULATE_PREPARES => false
			));
		}
	}

	/**
	 * Выполнение запроса к базе данных
	 *
	 * @param string $sql
	 * @param array|null $params
	 * @return bool|false|PDOStatement|string
	 * @throws Exception
	 */
	public static function query(string $sql, array $params = null)
	{
		static::connect();

		if (is_null($params))
			return static::$connection->query($sql);

		$sql = static::$connection->prepare($sql);
		if (!$sql)
			throw new Exception('Не удалось подготовить запрос к исполнению: ' . $sql->errorInfo()[2]);

		$sql->execute($params);

		unset($params);
		return $sql;
	}

	/**
	 * Возвращает скалярное значение одного столбца
	 *
	 * @param string $sql - требуемый SQL запрос
	 * @param array|null $params - массив значений, которые следует подставить вместо "?" в подготовленном запросе
	 * @return bool|mixed
	 * @throws Exception
	 */
	public static function getOne(string $sql, array $params = null)
	{
		static::connect();

		$row = static::query($sql, $params)->fetch();
		if (is_array($row))
			return reset($row);
		else
			return false;
	}

	/**
	 * Возвращает массив значений из строки
	 *
	 * @param string $sql - требуемый SQL запрос
	 * @param array|null $params - массив значений, которые следует подставить вместо "?" в подготовленном запросе
	 * @return bool|mixed
	 * @throws Exception
	 */
	public static function getRow(string $sql, array $params = null)
	{
		static::connect();

		$row = static::query($sql, $params)->fetch();
		if (is_array($row))
			return $row;
		else
			return false;
	}

	/**
	 * Возвращает все строки таблицы, удовлетворяющие условию выборки
	 * @param string $sql - требуемый SQL запрос
	 * @param array|null $params - массив значений, которые следует подставить вместо "?" в подготовленном запросе
	 * @return array|bool
	 * @throws Exception
	 */
	public static function getAll(string $sql, array $params = null)
	{
		static::connect();

		$row = static::query($sql, $params)->fetchAll();
		if (is_array($row))
			return $row;
		else
			return false;
	}

	/**
	 * Возвращает ID последнего запроса INSERT
	 * @return integer
	 */
	public static function insertId()
	{
		static::connect();
		return (int)static::$connection->lastInsertId();
	}

	/**
	 * Возвращает информацию о последней ошибке
	 * @return mixed
	 */
	public static function getLastError()
	{
		static::connect();
		return static::$connection->errorInfo()[2];
	}
}