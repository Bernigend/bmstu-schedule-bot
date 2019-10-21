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
	protected $vkApiClient;

	/**
	 * Конфигурация бота
	 *
	 * @var array
	 */
	public $config = null;

	/**
	 * VKBot constructor.
	 */
	public function __construct ()
	{
		$this->initVKApiClient();
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

		die ($this->config['confirmation_token']);
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
			$commandHandler = new VKCommandHandler($this, $eventData['peer_id'], $eventData['text']);
			$commandHandler->handle();
		}
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
		if (isset(Config::VK_DATA['id' . $groupId]))
			$this->config = Config::VK_DATA['id' . $groupId];

		if (isset($this->config['secret_key']) && !is_null($this->config['secret_key']))
			if (strcmp($secret, $this->config['secret_key']) !== 0)
				return false;

		return true;
	}

	/**
	 * Отправляет сообщение
	 *
	 * @param int $peerID
	 * @param string $message
	 * @param array $params
	 * @throws \VK\Exceptions\Api\VKApiMessagesCantFwdException
	 * @throws \VK\Exceptions\Api\VKApiMessagesChatBotFeatureException
	 * @throws \VK\Exceptions\Api\VKApiMessagesChatUserNoAccessException
	 * @throws \VK\Exceptions\Api\VKApiMessagesContactNotFoundException
	 * @throws \VK\Exceptions\Api\VKApiMessagesDenySendException
	 * @throws \VK\Exceptions\Api\VKApiMessagesKeyboardInvalidException
	 * @throws \VK\Exceptions\Api\VKApiMessagesPrivacyException
	 * @throws \VK\Exceptions\Api\VKApiMessagesTooLongForwardsException
	 * @throws \VK\Exceptions\Api\VKApiMessagesTooLongMessageException
	 * @throws \VK\Exceptions\Api\VKApiMessagesTooManyPostsException
	 * @throws \VK\Exceptions\Api\VKApiMessagesUserBlockedException
	 * @throws \VK\Exceptions\VKApiException
	 * @throws \VK\Exceptions\VKClientException
	 */
	public function sendMessage (int $peerID, string $message, array $params = array ())
	{
		$this->vkApiClient->messages()->send($this->config['access_token'], array (
			'peer_id' => $peerID,
			'message' => $message,
			'keyboard' => $params['keyboard'] ?? '',
			'dont_parse_links' => 1,
			'random_id' => random_int(1, 999999999999)
		));
	}

	/**
	 * Возвращает VKApiClient
	 *
	 * @return VKApiClient
	 */
	public function getVKApiClient ()
	{
		return $this->vkApiClient;
	}

	/**
	 * Инициализирует подключение к VK API
	 */
	public function initVKApiClient () : void
	{
		$this->vkApiClient = new VKApiClient(($this->config['api_version']) ?? Config::VK_API_DEFAULT_VERSION);
	}
}