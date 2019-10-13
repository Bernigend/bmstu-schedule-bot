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

//		$inlineKeyboard = new Markup([
//			'inline_keyboard' => [
//				[
//					['text' => '1', 'callback_data' => 'k=1'],
//					['text' => '2', 'callback_data' => 'k=2'],
//					['text' => '3', 'callback_data' => 'k=3'],
//				],
//				[
//					['text' => '4', 'callback_data' => 'k=4'],
//					['text' => '5', 'callback_data' => 'k=5'],
//					['text' => '6', 'callback_data' => 'k=6'],
//				],
//				[
//					['text' => '7', 'callback_data' => 'k=7'],
//					['text' => '8', 'callback_data' => 'k=8'],
//					['text' => '9', 'callback_data' => 'k=9'],
//				],
//				[
//					['text' => '0', 'callback_data' => 'k=0'],
//				],
//			]
//		]);

		$sendMessage->disable_web_page_preview = true;
		$sendMessage->parse_mode = 'Markdown';
//		$sendMessage->reply_markup = $inlineKeyboard;

		static::$telegramApiTgLog->performApiRequest($sendMessage);

		static::$telegramApiLoop->run();
	}
}