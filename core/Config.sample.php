<?php


namespace Core;


class Config
{
	/***********************************************************
	 * ВКЛЮЧЕНИЕ/ОТКЛЮЧЕНИЕ БОТА
	 ***********************************************************/

	/**
	 * Включение/отключение системы бота в целом
	 * @var boolean
	 */
	public const BOT_SYSTEM_ONLINE = true;

	/**
	 * Включение/отключение выполнения команд ботом
	 * @var boolean
	 */
	public const BOT_ONLINE = true;


	/***********************************************************
	 * НАСТРОЙКА РЕЖИМА ОТЛАДКИ И ВЫВОДА ОШИБОК
	 ***********************************************************/

	/**
	 * Включение/отключение режима отладки
	 * @var boolean
	 */
	public const DEBUG_ON = true;

	/**
	 * Задает, какие ошибки PHP попадут в отчет
	 * см. https://www.php.net/manual/en/function.error-reporting.php
	 */
	public const ERROR_REPORTING_LEVEL = E_ALL;


	/***********************************************************
	 * НАСТРОЙКА ЛОГИРОВАНИЯ ОШИБОК
	 ***********************************************************/

	/**
	 * Включает/отключает $BOT_LOG
	 * @var boolean
	 */
	public const BOT_LOG_ON = true;

	/**
	 * Логирование ошибок в определённый файл или системный журнал
	 * true - логирование ошибок будет осуществляться в указанной в static::ERRORS_LOG_FILE_DIRECTORY директории
	 * false - системный файл ошибок
	 * @var boolean
	 */
	public const LOG_ERRORS_TO_FILE = true;

	/**
	 * Каталог, в котором будут создаваться файлы с логами ошибок
	 * Указывать абсолютный путь + "/" на конце
	 * @var string
	 */
	public const ERRORS_LOG_FILE_DIRECTORY = '/home/b/bernigend/bot.bernigend.tmweb.ru/public_html/logs/';


	/***********************************************************
	 * НАСТРОЙКА ПОДКЛЮЧЕНИЯ К БАЗЕ ДАННЫХ
	 ***********************************************************/

	/**
	 * Хост базы данных
	 * @var string
	 */
	public const DB_HOST = 'localhost';

	/**
	 * Название базы данных
	 * @var string
	 */
	public const DB_NAME = 'bernigend_botdev';

	/**
	 * Пользователь базы данных
	 * @var string
	 */
	public const DB_USER = 'bernigend_botdev';

	/**
	 * Пароль пользователя базы данных
	 * @var string
	 */
	public const DB_PASS = '7380678';

	/**
	 * Кодировка базы данных
	 * @var string
	 */
	public const DB_CHARSET = 'utf8';

	/**
	 * Префикс таблиц в базе данных
	 * @var string
	 */
	public const DB_PREFIX = '';


	/***********************************************************
	 * НАСТРОЙКА ПОДКЛЮЧЕНИЯ К VK API
	 ***********************************************************/

	/**
	 * Хранит данные обо всех сообществах, которые имеют доступ к боту
	 * @var array
	 *  - @key string: ID группы, строка начинается с "id", например id123456789
	 *  - - @var string name - Название бота
	 *  - - @var string confirmation_token - токен подтверждения владения сервером
	 *  - - @var string access_token - токен доступа к группе
	 *  - - @var string secret_key - секретный ключ группы
	 *  - - @var int developers_talk_peer_id - куда отправлять разработчикам уведомления от бота
	 */
	public const VK_GROUPS = array (
		// Группа МФ
		'id186394025' => array (
			'name' => 'Группа МФ',
			'confirmation_token' => '030af8d8',
			'access_token' => 'e5b8a1fe7dfda1a63640718387c28fa1bd30715808288654772d4716fe7a0b3b2a94dde3529aa85277987',
			'secret_key'   => 'mdkmwri3ir2r12rj2ir3n3gmlqkwmkl3rkn34tn2nl',
			'developers_talk_peer_id' => '2000000001'
		),
		// Группа МГТУ
		'id172748106' => array (
			'name' => 'Группа МГТУ',
			'confirmation_token' => 'ef0e1915',
			'access_token' => '9d175246df22c73d83107adffaaaec3a61b6222770fea7169e5b531dbf78ad839d09e42e930eb789f3d27',
			'secret_key'   => 'qpomksacbygkJHFuiHUjkbfvjnckmsjafbuiabklansfn',
			'developers_talk_peer_id' => '2000000002'
		),
		// Группа разработки
		'id186813513' => array (
			'name' => 'Группа разработки',
			'confirmation_token' => '983e02bd',
			'access_token' => 'abee8d26931829fabc0c78148cdce55d1a59d46f4a0af5dd078e5365b5359b10b85062860030157a1d955',
			'secret_key'   => 'dgsrye43wrtfewy5t8jljiklyuikrty45rq23reytrikjhlhjk',
			'developers_talk_peer_id' => '2000000001'
		)
	);

	/**
	 * Версия VK API по умолчанию
	 * @var string
	 */
	public const VK_API_DEFAULT_VERSION = '5.101';


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
	 * Пользователь прокси (для авторизации)
	 * @var string
	 */
	public const TELEGRAM_PROXY_USER = '';

	/**
	 * Пароль пользователя прокси (для авторизации)
	 * @var string
	 */
	public const TELEGRAM_PROXY_PASSWORD = '';


	/***********************************************************
	 * НАСТРОЙКА АДМИНИСТРАТОРОВ
	 ***********************************************************/

	/**
	 * Список администраторов бота
	 * - Из ВК:       peerID-<peerID>; Например: peerID-123456789
	 * - Из Telegram: chatID-<chatID>; Например: chatID-123456789
	 * @var array
	 */
	public const ADMIN_USERS = array (
		'peerID-222734159'
	);
}