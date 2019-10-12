<?php

use Core\Telegram\TelegramBot;
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