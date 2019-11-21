<?php

use Core\Config;

// Подключаем автозагрузчик
require_once '../vendor/autoload.php';

// Если бот выключен на системном уровне - прекращаем работу
if (!Config::BOT_SYSTEM_ONLINE)
	die ('System offline');

// Настройка вывода ошибок
error_reporting(Config::ERROR_REPORTING_LEVEL);
set_error_handler('\Core\ExceptionHandler::handleError');
set_exception_handler('\Core\ExceptionHandler::handle');