<?php


namespace Core\VK;


use Core\Config;
use Exception;
use VK\CallbackApi\Server\VKCallbackApiServerHandler;
use VK\Client\VKApiClient;

class VKBot extends VKCallbackApiServerHandler
{
	/**
	 * Переданное сервером VK событие
	 *
	 * @var object
	 */
	protected $event;

	/**
	 * Доступ к VK API
	 *
	 * @var VKApiClient
	 */
	protected static $vkApiClient;

	/**
	 * VKBot constructor.
	 */
	public function __construct ()
	{
		static::initVKApiClient();
	}

	/**
	 * Передаёт полученное событие соответствующему обработчику события
	 *
	 * @param object $event - полученное событие
	 * @throws Exception
	 */
	public function handle (object $event) : void
	{
		if (!isset($event->type))
			throw new Exception('Required parameter "type" is not passed: ' . print_r($event, true));

		if (!isset($event->group_id))
			throw new Exception('Required parameter "group_id" is not passed: ' . print_r($event, true));

		if ($event->type == 'confirmation')
			$this->confirmation($event->group_id, $event->secret ?? null);
		else
			parent::parseObject($event->group_id, $event->secret ?? null, $event->type, (array)$event->object ?? null);
	}

	/**
	 * Выводит подтверждающий владение сервером токен,
	 * если переданные group_id и secret соответствуют указанным в конфигурации бота
	 *
	 * @param int $groupId - ID группы VK
	 * @param null|string $secret - секретный ключ группы (если тот установлен)
	 */
	public function confirmation (int $groupId, ?string $secret) : void
	{
		if (!$this->checkSenderServer($groupId, $secret))
			die ();

		die (Config::VK_API_CONFIRMATION_TOKEN);
	}

	/**
	 * Передаёт команду полученного события соответствующему обработчику,
	 * либо добавляет её в очередь на выполнение
	 *
	 * @param int $groupId
	 * @param string|null $secret
	 * @param array $eventData
	 * @throws Exception
	 */
	public function messageNew (int $groupId, ?string $secret, array $eventData) : void
	{
		if (!$this->checkSenderServer ($groupId, $secret))
			die ();

		if (!isset ($eventData['peer_id']))
			throw new Exception('Required parameter "peer_id" is not passed: ' . print_r($eventData, true));

		if (!isset ($eventData['text']))
			throw new Exception('Required parameter "text" is not passed: ' . print_r($eventData, true));

		echo 'ok';

		// Если включена очередь задач - добавляем задачу в очередь на обработку,
		// иначе передаём её обработчику напрямую
		if (Config::QUEUE_ON) {
			// TODO: добавление новой задачи в очередь
		} else {
			$commandHandler = new VKCommandHandler($eventData['peer_id'], $eventData['text']);
			$commandHandler->handle();
		}
	}

	public static function sendMessage (int $peerID, string $message, ?array $params)
	{

	}

	/**
	 * Проверяет отправителя события
	 * Если совпадает ID группы и секретный ключ - событие пришло от сервера VK
	 *
	 * @param int $groupId - ID группы VK
	 * @param null|string $secret - секретный ключ группы (если тот установлен)
	 * @return bool
	 */
	protected function checkSenderServer (int $groupId, ?string $secret) : bool
	{
		if (!is_null(Config::VK_API_SECRET_KEY))
			if (strcmp($secret, Config::VK_API_SECRET_KEY) !== 0)
				return false;

		if ($groupId !== Config::VK_API_GROUP_ID)
			return false;

		return true;
	}

	/**
	 * Инициализирует подключение к VK API
	 */
	protected static function initVKApiClient () : void
	{
		static::$vkApiClient = new VKApiClient(Config::VK_API_VERSION);
	}
}