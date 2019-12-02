<?php

$START_TIME = microtime(true);

ignore_user_abort(true);

use Core\Config;
use Core\Logger;

// Подключаем автозагрузчик
require_once '../vendor/autoload.php';

// Запуск логера
$BOT_LOG = new Logger('requests_' . date('d.m.Y') . '.log');

// Если бот выключен на системном уровне - прекращаем работу
if (!Config::BOT_SYSTEM_ONLINE)
	die ('System offline');

// Настройка вывода ошибок
error_reporting(Config::ERROR_REPORTING_LEVEL);
set_error_handler('\Core\ExceptionHandler::handleError');
set_exception_handler('\Core\ExceptionHandler::handle');