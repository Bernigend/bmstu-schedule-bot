<?php


namespace Core\VK;


use Core\CommandHandler;
use Core\Config;
use Exception;

class VKCommandHandler extends CommandHandler
{
	/**
	 * Идентификатор назначения (куда отправлять ответ)
	 *
	 * @var integer
	 */
	protected $peerID;

	/**
	 * VKCommandHandler constructor.
	 *
	 * @param int $peerID - идентификатор назначения (куда отправлять ответ)
	 * @param string $command - переданная пользователем команда
	 */
	public function __construct (int $peerID, string $command)
	{
		$this->localCommands = array (
			'помощь' => 'sendHelp',
			'насегодня' => 'sendScheduleForToday',
			'назавтра' => 'sendScheduleForTomorrow',
			'наэтунеделю' => 'sendScheduleForThisWeek',
			'наследующуюнеделю' => 'sendScheduleForNextWeek',
			'изменитьгруппу' => 'changeUserGroup',
			'задатьвопрос' => 'askNewQuestion',
		);

		parent::__construct();

		$this->peerID  = $peerID;
		$this->command = $this->prepareCommand($command);
	}

	/**
	 * Обработчик команд пользователя
	 *
	 * @throws Exception
	 */
	public function handle ()
	{
		// Ищем пользователя в БД, если не найден - регистрируем и отправляем сообщение о регистрации
		$userID = VKUser::find ($this->peerID);
		if (!$userID) {
			VKUser::register($this->peerID, 'group_name');
			VKBot::sendMessage($this->peerID, $this->answers['greetings_with_send_group_name']);
			return;
		}

		// Инициализируем пользователя
		$this->user = new VKUser($userID);
		$this->user->loadData();

		// Получаем обработанный ответ на команду пользователя
		$message = $this->getAnswerToCommand();

		VKBot::sendMessage($this->peerID, $message['text'], $message['params']);
		return;
	}

	/**
	 * Возвращает данные о сообщении в виде массива
	 *
	 * @param string $message - текст сообщения
	 * @param array $params - параметры сообщения
	 * @return array
	 */
	protected function createMessage(string $message, array $params = array()) : array
	{
		if (isset($params['keyboard']))
			$params['keyboard'] = $this->getKeyboard($params['keyboard']);

		return array (
			'text' => $message,
			'params' => $params
		);
	}

	/**
	 * Возвращает JSON представление inline клавиатуры
	 *
	 * @param string|null $type
	 * @return false|mixed|string|null
	 */
	protected function getKeyboard (?string $type)
	{
		switch ($type) {
			case 'full':
				$keyboard = json_encode(
					array (
						'one_time' => true,
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
								)
							)
						)
					),
					JSON_UNESCAPED_UNICODE
				);
				break;

			default:
				$keyboard = null;
				break;
		}

		return $keyboard;
	}


	/******************************************************************************
	 * ОБРАБОТЧИКИ КОМАНД
	 ******************************************************************************/


	/**
	 * Обработчик ввода текста вопроса
	 * @return array
	 * @throws \VK\Exceptions\VKApiException
	 * @throws \VK\Exceptions\VKClientException
	 */
	protected function inputQuestionText() : array
	{
		// Получаем информацию о пользователе
		$userInfo = VKBot::getVKApiClient()->users()->get(Config::VK_API_ACCESS_TOKEN, array (
			'user_ids' => $this->peerID,
			'fields' => 'first_name', 'last_name'
		));

		$message  = '⚠ Новый вопрос от ' . $userInfo[0]['first_name'] . ' ' . $userInfo[0]['last_name'] . ' @id' . $this->peerID . ' [VK]<br><br>';
		$message .= 'Вопрос:<br>"' . $this->command['original'] . '"';

		// Отправляем уведомление в беседу разработчиков
		VKBot::sendMessage(Config::VK_DEVELOPERS_TALK_PEER_ID, $message);

		$this->user->update('expected_input', null);
		return $this->createMessage('Ваш вопрос был успешно отправлен.<br>С вами свяжутся в ближайшее время', array ('keyboard' => 'full'));
	}
}