<?php


namespace Core;


abstract class AUser
{
	/**
	 * Идентификатор пользователя в базе данных
	 * @var integer
	 */
	public $dbID;

	/**
	 * Идентификатор назначения (кому/куда отправлять ответные сообщения)
	 * @var mixed
	 */
	public $destinationID;

	/**
	 * Символическое название группы
	 * @var ?string
	 */
	public $group_symbolic;

	/**
	 * Ожидаемый от пользователя ввод
	 * @var ?string
	 */
	public $expected_input;

	/**
	 * AUser constructor.
	 *
	 * @param $dbID - ID пользователя в базе данных
	 * @param $destinationID - идентификатор назначения (куда/кому отправлять ответное сообщение)
	 */
	public abstract function __construct($dbID, $destinationID);

	/**
	 * Изменяет данные пользователя в базе данных
	 *
	 * @param string $column - стобец таблицы базы данных
	 * @param $value - новое значение
	 * @return bool - true, если изменение прошло успешно
	 */
	public abstract function update(string $column, $value): bool;

	/**
	 * Загружает данные пользователя из базы данных в переменную $this->data
	 */
	public abstract function loadData(): void;

	/**
	 * Поиск пользователя в баз данных по его идентификатору
	 *
	 * @param $id - специфичный для этой таблицы БД идентификатор пользователя
	 * @return integer|false - ID пользователя в базе данных, либо false, если тот не найден
	 */
	public static abstract function find($id);

	/**
	 * Регистрирует пользователя в системе, добавляя информацию о нём в базу данных
	 *
	 * @param $id - специфичный для этой таблицы БД идентификатор пользователя
	 * @param string|null $expectedInput - требуемый от пользователя ввод
	 * @return int - ID нового пользователя из базы данных
	 */
	public static abstract function register($id, ?string $expectedInput = null): int;
}