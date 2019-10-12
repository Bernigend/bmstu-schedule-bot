<?php


namespace Core;


use Exception;
use Throwable;

class ExceptionHandler
{
	/**
	 * Обработчик исключений
	 * Логирует переданное исключение в системный лог-файл или указанный в конфигурации бота
	 * Если включён режим отладки - выводит информацию об исключении на экран пользователя
	 *
	 * @param Throwable $exception
	 */
	public static function handle (Throwable $exception) {
		$messageToLog  = 'Uncaught exception: \'' . get_class($exception) . '\' ';
		$messageToLog .= "with message '{$exception->getMessage()}'; ";
		$messageToLog .= "Stack trace: {$exception->getTraceAsString()}; ";
		$messageToLog .= "Throw in {$exception->getFile()} on line {$exception->getLine()}";

		// Логируем исключение
		if (Config::LOG_ERRORS_TO_FILE)
			error_log($messageToLog, 3, Config::ERRORS_LOG_FILE_DIRECTORY . date("d.m.Y") . '.txt');
		else
			error_log($messageToLog);

		// Выводим его на экран, если включён режим отладки
		if (Config::DEBUG_ON) {
			echo $messageToLog;
			ob_end_flush();
		}

		exit;
	}

	/**
	 * Обработчик ошибок. Перебрасывает ошибку в исключение
	 *
	 * @param $level
	 * @param $message
	 * @param $file
	 * @param $line
	 * @throws Exception
	 */
	public static function handleError ($level, $message, $file, $line)
	{
		throw new Exception ($message);
	}
}