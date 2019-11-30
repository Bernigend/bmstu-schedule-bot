<?php

// Включаем буфер обмена
ob_start (null, 0, PHP_OUTPUT_HANDLER_STDFLAGS);

// Если не передан запрос, выходим из обработчика
if (!isset($_REQUEST))
	die();

// Подключаем автозагрузчик
require_once '../vendor/autoload.php';

// Настраиваем вывод и обработку ошибок
error_reporting(Core\Config::ERROR_REPORTING_LEVEL);
set_error_handler('Core\Exceptions\Handler::handleError');
set_exception_handler('Core\Exceptions\Handler::handle');

if (!\Core\Config::BOT_ONLINE)
	die('ok');

if (isset($_POST['requestToWorker']) || isset($_GET['requestToWorker'])) {
	ignore_user_abort(true);
	// Начало работы обработчика очереди
	Core\Queue\Worker::start ();
	exit;
}

// Полученное событие
$receivedEvent = file_get_contents('php://input');
$receivedEvent = json_decode($receivedEvent, true);

// Запускаем бота
$bot = new Core\Bot\Handler($receivedEvent);
$bot->start();