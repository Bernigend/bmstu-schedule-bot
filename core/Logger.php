<?php


namespace Core;


class Logger
{
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