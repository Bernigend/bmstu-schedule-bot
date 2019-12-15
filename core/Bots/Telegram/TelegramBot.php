<?php
/**
 * Created by PhpStorm.
 * User: Bernigend
 * Date: 12.11.2019
 * Time: 15:33
 */

namespace Core\Bots\Telegram;


use Clue\React\Socks\Client;
use Core\ACommandHandler;
use Core\Bots\IBot;
use Core\Config;
use Core\DataBase;
use Core\Entities\Command;
use Core\Entities\TelegramUser;
use Exception;
use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;
use React\Socket\Connector;
use unreal4u\TelegramAPI\HttpClientRequestHandler;
use unreal4u\TelegramAPI\Telegram\Methods\SendMessage;
use unreal4u\TelegramAPI\Telegram\Types\ReplyKeyboardMarkup;
use unreal4u\TelegramAPI\Telegram\Types\Update;
use unreal4u\TelegramAPI\TgLog;

class TelegramBot implements IBot
{
	/**
	 * @var LoopInterface
	 */
	public static $telegramApiLoop;

	/**
	 * @var HttpClientRequestHandler
	 */
	public static $telegramApiHandler;

	/**
	 * @var TgLog
	 */
	public static $telegramApiTgLog;

	/**
	 * TelegramBot constructor.
	 */
	public function __construct()
	{
		static::$telegramApiLoop = Factory::create();

		$proxy = new Client('socks5://' . Config::TELEGRAM_PROXY_USER . ':' . Config::TELEGRAM_PROXY_PASSWORD . '@' . Config::TELEGRAM_PROXY_SERVER . ':' . Config::TELEGRAM_PROXY_PORT, new Connector(static::$telegramApiLoop, array ('dns' => false, 'timeout' => 20.0)));

		static::$telegramApiHandler = new HttpClientRequestHandler(static::$telegramApiLoop, array (
			'tcp'     => $proxy,
			'timeout' => 20.0,
			'dns'     => false
		));

		static::$telegramApiTgLog = new TgLog(Config::TELEGRAM_API_ACCESS_TOKEN, static::$telegramApiHandler);
	}

	/**
	 * Передаёт событие соответствующему обработчику
	 *
	 * @param Update $event - полученное от сервера событие
	 * @return bool
	 * @throws Exception
	 */
	public function handle(Update $event): bool
	{
		global $BOT_LOG;

		// Проверка сервера отправления
		if (!$this->checkSenderServer())
			die ('Access denied');

		if (Config::BOT_LOG_ON) $BOT_LOG->addToLog("Telegram: chat_id={$event->message->chat->id}; message_id={$event->message->message_id}, user_id={$event->message->from->id}, username={$event->message->from->username}, text='{$event->message->text}';\n");

		// Проверяем, был ли уже обработан запрос
		$date = DataBase::getOne('SELECT `date` FROM `' . Config::DB_PREFIX . 'handled_messages_telegram` WHERE `message_id` = ? AND `chat_id` = ?', array($event->message->message_id, $event->message->chat->id));
		if ($date) {
			if (Config::BOT_LOG_ON) $BOT_LOG->addToLog("Message has already been processed at '{$date}';\n");
			return false;
		}

		// Добавляем запрос в обработанные
		DataBase::query('INSERT INTO `' . Config::DB_PREFIX . 'handled_messages_telegram` SET `message_id` = ?, `chat_id` = ?', array($event->message->message_id, $event->message->chat->id));

		// Если бот отключён и пользователь не имеет администраторских прав
		if (!Config::BOT_ONLINE && !array_search('chatID-' . $event->message->chat->id, Config::ADMIN_USERS)) {
			if (Config::BOT_LOG_ON) $BOT_LOG->addToLog("Bot is offline and user has not admin privileges;\n");
			$this->sendMessage($event->message->chat->id, ACommandHandler::$answers['bot_is_offline'], 'full');
			return true;
		}

		// Регистрируем пользователя, если того нет в БД
		$userId = TelegramUser::find($event->message->chat->id);
		if (!$userId) {
			TelegramUser::register($event->message->chat->id, 'group_name');
			$this->sendMessage($event->message->chat->id, ACommandHandler::$answers['greetings_with_send_group_name'], 'cancel');
			return true;
		}

		// Инициализируем пользователя
		$user = new TelegramUser($userId, $event->message->chat->id);
		$user->loadData();

		// Инициализируем команду пользователя
		$command = new Command($event->message->text);
		if (!$command->init($user))
			return false;

		// Передаём команду её обработчику
		$commandHandler = new TelegramCommandHandler($this, $command, $user, new TelegramViewer());
		$commandHandler->handle();

		return true;
	}

	/**
	 * Отправляет сообщение в Telegram чат
	 *
	 * @param int $chatID
	 * @param string $message
	 * @param string $keyboardType
	 * @return bool
	 */
	public function sendMessage($chatID, $message, $keyboardType = null): bool
	{
		global $BOT_LOG;
		$send_message_start = microtime(true);

		$sendMessage = new SendMessage();
		$sendMessage->chat_id = $chatID;
		$sendMessage->text = $message;

		$keyboard = $this->getKeyboard($keyboardType);

		$sendMessage->reply_markup = new ReplyKeyboardMarkup();
		$sendMessage->reply_markup->one_time_keyboard = $keyboard['one_time_keyboard'] ?? true;
		$sendMessage->reply_markup->keyboard = $keyboard['buttons'];
		$sendMessage->reply_markup->resize_keyboard = true;

		$sendMessage->disable_web_page_preview = true;
		$sendMessage->parse_mode = 'Markdown';

		static::$telegramApiTgLog->performApiRequest($sendMessage);
		static::$telegramApiLoop->run();

		if (Config::BOT_LOG_ON) $BOT_LOG->addToLog(" - Send message finished in " . round(microtime(true) - $send_message_start, 4) . " sec;\n");

		return true;
	}

	/**
	 * Проверяет наличие токена доступа в строке запроса
	 *
	 * @return bool
	 */
	protected function checkSenderServer(): bool
	{
		if (!in_array(Config::TELEGRAM_API_ACCESS_TOKEN, explode('/', $_SERVER['REQUEST_URI'])))
			return false;

		return true;
	}

	/**
	 * Возвращает клавиатуру для отправки пользователю
	 *
	 * @param string|null $type - тип клавиатуры
	 * @return array
	 */
	protected function getKeyboard (?string $type): array
	{
		switch ($type) {
			case 'full':
				$keyboard = array (
					'one_time_keyboard' => false,
					'buttons' => array (
						array (
							array (
								'text' => 'На сегодня'
							),
							array (
								'text' => 'На завтра'
							)
						),
						array (
							array (
								'text' => 'На эту неделю'
							),
							array (
								'text' => 'На следующую неделю'
							)
						),
						array (
							array (
								'text' => 'Экзамены'
							)
						),
						array (
							array (
								'text' => 'Изменить группу'
							)
						),
						array (
							array (
								'text' => 'Задать вопрос'
							),
							array (
								'text' => 'Список команд'
							)
						)
					)
				);
				break;
			case 'cancel':
				$keyboard = array (
					'one_time_keyboard' => false,
					'buttons' => array (
						array (
							array (
								'text' => 'Отмена'
							)
						)
					)
				);
				break;
			default:
				$keyboard = array (
					'one_time_keyboard' => true,
					'buttons'  => array ()
				);
				break;
		}
		return $keyboard;
	}
}