<?php


namespace Core;


use Exception;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use Throwable;

class ExceptionHandler
{
	/**
	 * Обработчик исключений
	 * Логирует переданное исключение в системный лог-файл или указанный в конфигурации бота
	 * Если включён режим отладки - выводит информацию об исключении на экран пользователя
	 *
	 * @param Throwable $exception
	 * @throws \PHPMailer\PHPMailer\Exception
	 */
	public static function handle(Throwable $exception): void
	{
		$messageToLog  = 'Uncaught exception: \'' . get_class($exception) . '\' ';
		$messageToLog .= "with message '{$exception->getMessage()}'; ";
		$messageToLog .= "Stack trace: {$exception->getTraceAsString()}; ";
		$messageToLog .= "Throw in {$exception->getFile()} on line {$exception->getLine()}";

		// Отправляем уведомление администратору
		$mail = new PHPMailer();
		//Server settings
		$mail->SMTPDebug = SMTP::DEBUG_OFF;                      // Enable verbose debug output
		$mail->isSMTP();                                            // Send using SMTP
		$mail->Host       = Config::SMTP_SERVER;                    // Set the SMTP server to send through
		$mail->SMTPAuth   = true;                                   // Enable SMTP authentication
		$mail->Username   = Config::SMTP_USER;                      // SMTP username
		$mail->Password   = Config::SMTP_PASS;                      // SMTP password
		$mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;         // Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` also accepted
		$mail->Port       = Config::SMTP_PORT;                      // TCP port to connect to
		//Recipients
		$mail->setFrom(Config::SMTP_USER);
		$mail->addAddress(Config::ADMIN_EMAIL);
		// Content
		$mail->isHTML(true);
		$mail->Subject = '[bmstu-schedule-bot] New exception';
		$mail->Body = '[' . date('d.m.Y H:i:s') . '] Произошла ошибка при выполнении скрипта:<br><code>' . $messageToLog . '</code>';
		// Send
		$mail->send();

		if ($mail->isError())
			$messageToLog .= "\nSEND EMAIL ERROR: {$mail->ErrorInfo}";

		// Логируем исключение
		if (Config::LOG_ERRORS_TO_FILE)
			error_log("[" . date('d.m.Y H:i:s') . "] " . $messageToLog . "\n\n", 3, Config::ERRORS_LOG_FILE_DIRECTORY . date("d.m.Y") . '.txt');
		else
			error_log("[" . date('d.m.Y H:i:s') . "] " . $messageToLog . "\n\n");

		// Выводим его на экран, если включён режим отладки
		if (Config::DEBUG_ON) {
			echo $messageToLog;
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
	public static function handleError($level, $message, $file, $line): void
	{
		throw new Exception($message);
	}
}