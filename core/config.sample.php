<?php

namespace Core;


class Config
{
	/***********************************************************
	 * НАСТРОЙКА РАБОТЫ БОТА
	 ***********************************************************/


	/**
	 * Включение/отключение бота
	 * true - включить, false - выключить
	 * @var boolean
	 */
	public const BOT_ONLINE = true;


	/***********************************************************
	 * НАСТРОЙКА РЕЖИМА ОТЛАДКИ
	 ***********************************************************/

	/**
	 * Включение/отключение режима отладки
	 * true - включить, false - отключить
	 * @var boolean
	 */
	public const DEBUG_ON = false;

	/**
	 * Задает, какие ошибки PHP попадут в отчет
	 * см. https://www.php.net/manual/en/function.error-reporting.php
	 */
	public const ERROR_REPORTING_LEVEL = E_ALL;

	/**
	 * Логирование ошибок в определённый файл или системный журнал
	 * true - логирование ошибок будет осуществляться в указанной в static::ERRORS_LOG_FILE_DIRECTORY директории
	 * false - системный файл ошибок
	 */
	public const LOG_ERRORS_TO_FILE = 0;

	/**
	 * Каталог, в котором будут создаваться файлы с логами ошибок
	 * Указывать абсолютный путь + "/" на конце
	 */
	public const ERRORS_LOG_FILE_DIRECTORY = '';


	/***********************************************************
	 * НАСТРОЙКА ПОДКЛЮЧЕНИЯ К БАЗЕ ДАННЫХ
	 ***********************************************************/


	/**
	 * Хост базы данных
	 */
	public const DB_HOST = '';

	/**
	 * Название базы данных
	 */
	public const DB_NAME = '';

	/**
	 * Пользователь базы данных
	 */
	public const DB_USER = '';

	/**
	 * Пароль пользователя базы данных
	 */
	public const DB_PASS = '';

	/**
	 * Кодировка базы данных
	 */
	public const DB_CHARSET = 'utf8';

	/**
	 * Префикс таблиц в базе данных
	 */
	public const DB_PREFIX = '';


	/***********************************************************
	 * НАСТРОЙКА ПОДКЛЮЧЕНИЯ К VK API
	 ***********************************************************/


	 /**
     	 * Хранит данные обо всех сообществах, которые имеют доступ к боту
     	 * @var array
     	 */
     	public const VK_DATA = array (
     		'id186813513' => array (
     			'confirmation_token' => '',
     			'access_token' => '',
     			'secret_key'   => ''
     		),
     		'id61281268' => array (
     			'confirmation_token' => '',
     			'access_token' => '',
     			'secret_key'   => ''
     		)
     	);

     	/**
     	 * Токен подтверждения сервером, выданный VK
     	 * @var string
     	 */
     //	public const VK_API_CONFIRMATION_TOKEN = '';

     	/**
     	 * Токен доступа к функциям сообщества, выданный VK
     	 * @var string
     	 */
     //	public const VK_API_ACCESS_TOKEN = '';

     	/**
     	 * Секретный ключ, который будет присылать VK
     	 * Укажите значение null, чтобы выключить проверку секретного ключа
     	 * @var string|null
     	 */
     //	public const VK_API_SECRET_KEY = '';

     	/**
     	 * ID группы VK, в которой будет работать бот
     	 * @var integer
     	 */
     //	public const VK_API_GROUP_ID = '';

     	/**
     	 * Версия VK API по умолчанию
     	 * @var string
     	 */
     	public const VK_API_DEFAULT_VERSION = '5.101';

     	/**
     	 * Идентификатор беседы разработчиков, куда прислылать различные уведомления
     	 * Берётся из базы данных
     	 * @var integer
     	 */
     	public const VK_DEVELOPERS_TALK_PEER_ID = '';


	/***********************************************************
	 * НАСТРОЙКА ПОДКЛЮЧЕНИЯ К TELEGRAM API
	 ***********************************************************/


	/**
	 * Токен доступа к функциям бота, выданный Telegram
	 * @var string
	 */
	public const TELEGRAM_API_ACCESS_TOKEN = '';

	/**
	 * Прокси сервер для подключения к Telegram
	 * @var string
	 */
	public const TELEGRAM_PROXY_SERVER = '';

	/**
	 * Порт прокси сервера для подключения к Telegram
	 * @var integer
	 */
	public const TELEGRAM_PROXY_PORT = '';

	/**
	 * Пользоватль прокси (для авторизации)
	 * @var static
	 */
	public const TELEGRAM_PROXY_USER = '';

	/**
	 * Пароль пользователя прокси (для авторизации)
	 * @var static
	 */
	public const TELEGRAM_PROXY_PASSWORD = '';


	/***********************************************************
	 * НАСТРОЙКА ОЧЕРЕДИ ЗАДАЧ
	 ***********************************************************/

	/**
	 * Включение/отклбчение очереди задач
	 * true - включить, false - выключить
	 * @var bool
	 */
	public const QUEUE_ON = false;
}