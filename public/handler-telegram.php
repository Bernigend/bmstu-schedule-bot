<?php
/**
 * Created by PhpStorm.
 * User: Bernigend
 * Date: 12.11.2019
 * Time: 18:22
 */

use Core\Bots\Telegram\TelegramBot;
use Core\Config;
use Core\Logger;
use unreal4u\TelegramAPI\Telegram\Types\Update;

require_once 'handler-main.php';

// Декодируем полученное событие из JSON в объект
$event = json_decode(file_get_contents('php://input'), true);

if (json_last_error() !== JSON_ERROR_NONE)
	throw new Exception ('Error during JSON event decoding: ' . json_last_error_msg() . '; Event: ' . print_r(file_get_contents('php://input'), true));

if (is_null($event))
	throw new Exception ('Decoded event is null...');

// Запускаем бота
$event = new Update($event);
$VkBot = new TelegramBot();
$VkBot->handle($event);

// Завершаем логирование
if (isset($BOT_LOG) && Config::BOT_LOG_ON) {
	$BOT_LOG->addToLog(' - Script time: ' . round(microtime(true) - $START_TIME, 4) . ' sec; Memory usage: ' . memory_get_usage() . ' bytes; Memory peak usage: ' . memory_get_peak_usage() . ' bytes');
	Logger::log($BOT_LOG->fileName, $BOT_LOG->textLog);
}

// Логируем время выполнения скрипта в БД
Logger::logScriptTime(round(microtime(true) - $START_TIME, 4));