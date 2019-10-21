<?php


namespace Core\Telegram;



use Clue\React\Socks\Client;
use Core\Config;
use Exception;
use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;
use React\Socket\Connector;
use unreal4u\TelegramAPI\HttpClientRequestHandler;
use unreal4u\TelegramAPI\Telegram\Methods\SendMessage;
use unreal4u\TelegramAPI\Telegram\Types\Custom\KeyboardButtonArray;
use unreal4u\TelegramAPI\Telegram\Types\ReplyKeyboardMarkup;
use unreal4u\TelegramAPI\Telegram\Types\Update;
use unreal4u\TelegramAPI\TgLog;

class TelegramBot
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
	public function __construct ()
	{
		static::$telegramApiLoop = Factory::create();

		$proxy = new Client('socks5://' . Config::TELEGRAM_PROXY_USER . ':' . Config::TELEGRAM_PROXY_PASSWORD . '@' . Config::TELEGRAM_PROXY_SERVER . ':' . Config::TELEGRAM_PROXY_PORT, new Connector(static::$telegramApiLoop, array ('dns' => false, 'timeout' => 20.0)));

		static::$telegramApiHandler = new HttpClientRequestHandler(static::$telegramApiLoop, array (
			'tcp' => $proxy,
			'timeout' => 20.0,
			'dns' => false
		));

		static::$telegramApiTgLog = new TgLog(Config::TELEGRAM_API_ACCESS_TOKEN, static::$telegramApiHandler);
	}

	/**
	 * Передаёт событие соответствующему обработчику
	 *
	 * @param Update $event
	 * @throws Exception
	 */
	public function handle (Update $event)
	{
		$commandHandler = new TelegramCommandHandler($event->message->chat->id, $event->message->text);
		$commandHandler->handle();
	}

	/**
	 * Отправляет сообщение в Telegram чат
	 *
	 * @param int $chatID
	 * @param string $message
	 * @param array $params
	 */
	public static function sendMessage (int $chatID, string $message, array $params = array ())
	{
		$sendMessage = new SendMessage();
		$sendMessage->chat_id = $chatID;

		$message = str_replace('<br>', "\n", $message);
		$sendMessage->text = $message;

		$sendMessage->reply_markup = new ReplyKeyboardMarkup();
		$sendMessage->reply_markup->one_time_keyboard = $params['keyboard']['one_time_keyboard'] ?? true;
		$sendMessage->reply_markup->keyboard = $params['keyboard']['buttons'];
		$sendMessage->reply_markup->resize_keyboard = true;

		$sendMessage->disable_web_page_preview = true;
		$sendMessage->parse_mode = 'Markdown';

		static::$telegramApiTgLog->performApiRequest($sendMessage);

		static::$telegramApiLoop->run();
	}
}