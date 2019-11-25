<?php


namespace Core;


class Logger
{
	/**
	 * Текст, который попадёт в лог
	 * @var string
	 */
	public $textLog;

	/**
	 * Название файла в каталоге с логами
	 * @var string
	 */
	public $fileName;

	/**
	 * Logger constructor.
	 *
	 * @param string $fileName - название файла в каталоге с логами
	 */
	public function __construct(string $fileName)
	{
		$this->fileName = $fileName;
		$this->textLog  = '';
	}

	/**
	 * Добавляет в лог необходимые данные
	 *
	 * @param string $value - данные для добавления
	 */
	public function addToLog(string $value): void
	{
		$this->textLog .= $value;
	}

	/**
	 * Заносит данные в требуемый лог файл
	 *
	 * @param string $fileName - название файла в каталоге с логами
	 * @param $value - данные для внесения в лог
	 * @return bool
	 */
	public static function log(string $fileName, $value): bool
	{
		if (is_null(Config::ERRORS_LOG_FILE_DIRECTORY))
			return false;

		if (empty(Config::ERRORS_LOG_FILE_DIRECTORY))
			return false;

		$fw = fopen(Config::ERRORS_LOG_FILE_DIRECTORY . $fileName, "a");
		fwrite($fw, '[' . date('H:i:s') . '] ' .  print_r($value, true) . "\n");
		fclose($fw);

		return true;
	}
}