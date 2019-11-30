<?php


namespace Core\Bots\VK;


use Core\ACommandHandler;
use Core\Bots\IBot;
use Core\Config;
use Core\DataBase;
use Core\Entities\Command;
use Core\Entities\VkUser;
use Exception;
use VK\CallbackApi\Server\VKCallbackApiServerHandler;
use VK\Client\VKApiClient;
use VK\Exceptions\Api\VKApiMessagesCantFwdException;
use VK\Exceptions\Api\VKApiMessagesChatBotFeatureException;
use VK\Exceptions\Api\VKApiMessagesChatUserNoAccessException;
use VK\Exceptions\Api\VKApiMessagesContactNotFoundException;
use VK\Exceptions\Api\VKApiMessagesDenySendException;
use VK\Exceptions\Api\VKApiMessagesKeyboardInvalidException;
use VK\Exceptions\Api\VKApiMessagesPrivacyException;
use VK\Exceptions\Api\VKApiMessagesTooLongForwardsException;
use VK\Exceptions\Api\VKApiMessagesTooLongMessageException;
use VK\Exceptions\Api\VKApiMessagesTooManyPostsException;
use VK\Exceptions\Api\VKApiMessagesUserBlockedException;
use VK\Exceptions\VKApiException;
use VK\Exceptions\VKClientException;

class VkBot extends VKCallbackApiServerHandler implements IBot
{
	/**
	 * Доступ к VK API
	 * @var VKApiClient
	 */
	public $vkApiClient;

	/**
	 * Конфигурация бота
	 * @var array
	 */
	public $config = null;

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
			parent::parseObject($event->group_id, $event->secret ?? null, $event->type, (array)$event->object);
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
		global $BOT_LOG;

		if (!$this->checkSenderServer($groupId, $secret))
			die ('Access denied');

		if (!isset($eventData['id']))
			throw new Exception('Required parameter "id" is not passed: ' . print_r($eventData, true));

		if (!isset($eventData['peer_id']))
			throw new Exception('Required parameter "peer_id" is not passed: ' . print_r($eventData, true));

		if (!isset($eventData['text']))
			throw new Exception('Required parameter "text" is not passed: ' . print_r($eventData, true));

		echo 'ok';

		if (Config::BOT_LOG_ON) $BOT_LOG->addToLog("VK: group_id={$groupId}({$this->config['name']}); message_id={$eventData['id']}, peer_id={$eventData['peer_id']}, text='{$eventData['text']}';\n");

		// Проверяем, был ли уже обработан запрос
		$date = DataBase::getOne('SELECT `date` FROM `' . Config::DB_PREFIX . 'handled_messages_vk` WHERE `message_id` = ? AND `peer_id` = ?', array ($eventData['id'], $eventData['peer_id']));
		if ($date) {
			if (Config::BOT_LOG_ON) $BOT_LOG->addToLog("Message has already been processed at '{$date}';\n");
			return false;
		}

		// Добавляем запрос в обработанные
		DataBase::query('INSERT INTO `' . Config::DB_PREFIX . 'handled_messages_vk` SET `peer_id` = ?, `message_id` = ?', array($eventData['peer_id'], $eventData['id']));

		// Если бот отключён и пользователь не имеет администраторских прав
		if (!Config::BOT_ONLINE && !array_search('peerID-' . $eventData['peer_id'], Config::ADMIN_USERS)) {
			if (Config::BOT_LOG_ON) $BOT_LOG->addToLog("Bot is offline and user has not admin privileges;\n");
			$this->sendMessage($eventData['peer_id'], ACommandHandler::$answers['bot_is_offline'], $this->getKeyboard('full'));
			return true;
		}

		// Регистрируем пользователя, если того нет в БД
		$userId = VkUser::find($eventData['peer_id']);
		if (!$userId) {
			VkUser::register($eventData['peer_id'], 'group_name');
			if (Config::BOT_LOG_ON) $BOT_LOG->addToLog("The user was registered;\n");
			$this->sendMessage($eventData['peer_id'], ACommandHandler::$answers['greetings_with_send_group_name'], $this->getKeyboard('cancel'));
			return true;
		}

		// Инициализируем пользователя
		$user = new VkUser($userId, $eventData['peer_id']);
		$user->loadData();

		// Инициализируем команду пользователя
		$command = new Command($eventData['text'], $eventData['payload'] ?? null);
		if (!$command->init($user))
			return false;

		// Передаём команду её обработчику
		$commandHandler = new VkCommandHandler($this, $command, $user, new VkViewer());
		$commandHandler->handle();

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
	 * @param string $keyboardType
	 * @return bool
	 * @throws VKApiMessagesCantFwdException
	 * @throws VKApiMessagesChatBotFeatureException
	 * @throws VKApiMessagesChatUserNoAccessException
	 * @throws VKApiMessagesContactNotFoundException
	 * @throws VKApiMessagesDenySendException
	 * @throws VKApiMessagesKeyboardInvalidException
	 * @throws VKApiMessagesPrivacyException
	 * @throws VKApiMessagesTooLongForwardsException
	 * @throws VKApiMessagesTooLongMessageException
	 * @throws VKApiMessagesTooManyPostsException
	 * @throws VKApiMessagesUserBlockedException
	 * @throws VKApiException
	 * @throws VKClientException
	 */
	public function sendMessage($peerID, $message, $keyboardType = null): bool
	{
		global $BOT_LOG;
		$send_message_start = microtime(true);

		$response = $this->vkApiClient->messages()->send($this->config['access_token'], array (
			'peer_id'  => $peerID,
			'message'  => $message,
			'keyboard' => ($peerID == $this->config['developers_talk_peer_id']) ? null : $this->getKeyboard($keyboardType),
			'dont_parse_links' => 1,
			'random_id' => random_int(1, 999999999999)
		));

		if (Config::BOT_LOG_ON) $BOT_LOG->addToLog(" - Send message finished in " . round(microtime(true) - $send_message_start, 4) . " sec; Response: " . print_r($response, true) . ";\n");

		return true;
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
									'payload' => array (
										'command' => '/today'
									)
								),
								'color' => 'primary'
							),
							array (
								'action' => array (
									'type' => 'text',
									'label' => 'На завтра',
									'payload' => array (
										'command' => '/tomorrow'
									)
								),
								'color' => 'primary'
							)
						),
						array (
							array (
								'action' => array (
									'type' => 'text',
									'label' => 'На эту неделю',
									'payload' => array (
										'command' => '/currentweek'
									)
								),
								'color' => 'primary'
							),
							array (
								'action' => array (
									'type' => 'text',
									'label' => 'На следующую неделю',
									'payload' => array (
										'command' => '/nextweek'
									)
								),
								'color' => 'primary'
							)
						),
						array (
							array (
								'action' => array (
									'type' => 'text',
									'label' => 'Изменить группу',
									'payload' => array (
										'command' => '/changegroup'
									)
								),
								'color' => 'secondary'
							)
						),
						array (
							array (
								'action' => array (
									'type' => 'text',
									'label' => 'Задать вопрос',
									'payload' => array (
										'command' => '/askquestion'
									)
								),
								'color' => 'secondary'
							),
							array (
								'action' => array (
									'type' => 'text',
									'label' => 'Список команд',
									'payload' => array (
										'command' => '/help'
									)
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
									'payload' => array (
										'command' => '/cancel'
									)
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