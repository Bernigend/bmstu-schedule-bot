<?php


namespace Core\Bots\VK;


use Core\ACommandHandler;
use Core\Config;
use Core\DataBase;
use Core\Entities\Command;
use Core\Entities\VkUser;
use Core\Logger;
use Exception;
use VK\CallbackApi\Server\VKCallbackApiServerHandler;
use VK\Client\VKApiClient;

class VkBot extends VKCallbackApiServerHandler
{
	/**
	 * Доступ к VK API
	 * @var VKApiClient
	 */
	protected $vkApiClient;

	/**
	 * Конфигурация бота
	 * @var array
	 */
	protected $config = null;

	/**
	 * VKBot constructor.
	 */
	public function __construct()
	{
		$this->initVKApiClient();
	}

	/**
	 * Передаёт полученное событие соответствующему обработчику события
	 *
	 * @param object $event - полученное событие
	 * @throws Exception
	 */
	public function handle(object $event): void
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
	public function confirmation(int $groupId, ?string $secret): void
	{
		if (!$this->checkSenderServer($groupId, $secret))
			die ('Access denied');

		die ($this->config['confirmation_token']);
	}

	/**
	 * Передаёт команду полученного события соответствующему обработчику,
	 * либо добавляет её в очередь на выполнение
	 *
	 * @param int $groupId
	 * @param string|null $secret
	 * @param array $eventData
	 * @return bool
	 * @throws Exception
	 */
	public function messageNew(int $groupId, ?string $secret, array $eventData): bool
	{
		if (!$this->checkSenderServer($groupId, $secret))
			die ('Access denied');

		if (!isset ($eventData['id']))
			throw new Exception('Required parameter "id" is not passed: ' . print_r($eventData, true));

		if (!isset ($eventData['peer_id']))
			throw new Exception('Required parameter "peer_id" is not passed: ' . print_r($eventData, true));

		if (!isset ($eventData['text']))
			throw new Exception('Required parameter "text" is not passed: ' . print_r($eventData, true));

		echo 'ok';

		$date = DataBase::getOne('SELECT `date` FROM `' . Config::DB_PREFIX . 'handled_messages_vk` WHERE `message_id` = ? AND `peer_id` = ?', array ($eventData['id'], $eventData['peer_id']));
		if ($date) {
			Logger::log('vk_handled_message_errors.log', 'The message with id=' . $eventData['id'] . ' AND peer_id=' . $eventData['peer_id'] . ' has already been processed ' . $date);
			return false;
		}

		// Если бот отключён и пользователь не имеет администратрских прав
		if (!Config::BOT_ONLINE && !array_search('peerID-' . $eventData['peer_id'], Config::ADMIN_USERS)) {
			$this->sendMessage($eventData['peer_id'], ACommandHandler::$answers['bot_is_offline'], $this->getKeyboard('full'));
			return true;
		}

		// Регистрируем пользователя, если того нет в БД
		$userId = VkUser::find($eventData['peer_id']);
		if (!$userId) {
			VkUser::register($eventData['peer_id'], 'group_name');
			$this->sendMessage($eventData['peer_id'], ACommandHandler::$answers['greetings_with_send_group_name'], $this->getKeyboard('cancel'));
			return true;
		}

		// Инициализируем пользователя
		$user = new VkUser($userId);
		$user->loadData();

		// Инициализируем команду пользователя
		$command = new Command($eventData['text']);
		if (!$command->init($user))
			return false;

		// Передаём команду её обработчику
		$commandHandler = new VkCommandHandler($command, $user, new VkViewer());
		$commandAnswer  = $commandHandler->handle();

		// Отправляем ответ
		if (!is_null($commandAnswer))
			$this->sendMessage($eventData['peer_id'], $commandAnswer->text, $this->getKeyboard($commandAnswer->keyboardType));

		DataBase::query('INSERT INTO `' . Config::DB_PREFIX . 'handled_messages_vk` SET `peer_id` = ?, `message_id` = ?', array($eventData['peer_id'], $eventData['id']));
		return true;
	}

	/**
	 * Проверяет отправителя события
	 * Если совпадает ID группы и секретный ключ - событие пришло от сервера VK
	 *
	 * @param int $groupId - ID группы VK
	 * @param null|string $secret - секретный ключ группы (если тот установлен)
	 * @return bool
	 */
	protected function checkSenderServer(int $groupId, ?string $secret): bool
	{
		if (isset(Config::VK_GROUPS['id' . $groupId]))
			$this->config = Config::VK_GROUPS['id' . $groupId];

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
	 * @param string $keyboard
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
	protected function sendMessage(int $peerID, string $message, string $keyboard = null): void
	{
		$response = $this->vkApiClient->messages()->send($this->config['access_token'], array (
			'peer_id'  => $peerID,
			'message'  => $message,
			'keyboard' => $keyboard,
			'dont_parse_links' => 1,
			'random_id' => random_int(1, 999999999999)
		));

		Logger::log('vk_send_messages_' . date('d.m.Y') . '.log', 'peerID-' . $peerID . '; Message strlen: ' . strlen($message) . '; Message: "' . substr($message, 0, 128) . '..."; Response: ' . print_r($response, true));
	}

	/**
	 * Инициализирует подключение к VK API
	 */
	protected function initVKApiClient(): void
	{
		$this->vkApiClient = new VKApiClient(($this->config['api_version']) ?? Config::VK_API_DEFAULT_VERSION);
	}

	/**
	 * Возвращает JSON представление inline клавиатуры
	 *
	 * @param string|null $type
	 * @return false|mixed|string|null
	 */
	protected function getKeyboard(?string $type)
	{
		switch ($type) {
			case 'full':
				$keyboard = array (
					'one_time' => false,
					'buttons' => array (
						array (
							array (
								'action' => array (
									'type' => 'text',
									'label' => 'На сегодня',
									'payload' => '1'
								),
								'color' => 'primary'
							),
							array (
								'action' => array (
									'type' => 'text',
									'label' => 'На завтра',
									'payload' => '2'
								),
								'color' => 'primary'
							)
						),
						array (
							array (
								'action' => array (
									'type' => 'text',
									'label' => 'На эту неделю',
									'payload' => '3'
								),
								'color' => 'primary'
							),
							array (
								'action' => array (
									'type' => 'text',
									'label' => 'На следующую неделю',
									'payload' => '4'
								),
								'color' => 'primary'
							)
						),
						array (
							array (
								'action' => array (
									'type' => 'text',
									'label' => 'Изменить группу',
									'payload' => '5'
								),
								'color' => 'secondary'
							)
						),
						array (
							array (
								'action' => array (
									'type' => 'text',
									'label' => 'Задать вопрос',
									'payload' => '6'
								),
								'color' => 'secondary'
							),
							array (
								'action' => array (
									'type' => 'text',
									'label' => 'Список команд',
									'payload' => '0'
								),
								'color' => 'secondary'
							)
						)
					)
				);
				break;
			case 'cancel':
				$keyboard = array (
					'buttons' => array (
						array (
							array (
								'action' => array (
									'type' => 'text',
									'label' => 'Отмена',
									'payload' => '-1'
								),
								'color' => 'secondary'
							)
						),
					),
				);
				break;
			default:
				$keyboard = array (
					'one_time' => true,
					'buttons'  => array ()
				);
				break;
		}
		$keyboard = json_encode($keyboard, JSON_UNESCAPED_UNICODE);
		return $keyboard;
	}
}