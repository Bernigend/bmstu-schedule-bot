<?php

use Core\Config;

// Подключаем автозагрузчик
require_once '../vendor/autoload.php';

// Если бот выключен, прекращаем работу
if (!Config::BOT_ONLINE)
	die();

// Настройка вывода ошибок
error_reporting(Config::ERROR_REPORTING_LEVEL);
set_error_handler('\Core\ExceptionHandler::handleError');
set_exception_handler('\Core\ExceptionHandler::handle');