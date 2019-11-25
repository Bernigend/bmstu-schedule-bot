<?php

use Core\Bots\VK\VkBot;
use Core\Logger;

require_once 'handler-main.php';

// Декодируем полученное событие из JSON в объект
$event = json_decode(file_get_contents('php://input'));

if (json_last_error() !== JSON_ERROR_NONE)
	throw new Exception ('Error during JSON event decoding: ' . json_last_error_msg() . '; Event: ' . print_r(file_get_contents('php://input'), true));

if (is_null($event))
	throw new Exception ('Decoded event is null...');

// Запускаем бота
$VkBot  = new VKBot();
$VkBot->handle($event);

// Завершаем логирование
if (isset($BOT_LOG)) {
	$BOT_LOG->addToLog('Script time: ' . round(microtime(true) - $START_TIME, 4) . " sec;");
	Logger::log($BOT_LOG->fileName, $BOT_LOG->textLog);
}