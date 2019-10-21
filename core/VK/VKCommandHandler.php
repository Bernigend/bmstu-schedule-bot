<?php


namespace Core\VK;


use Core\CommandHandler;
use Core\Config;
use Exception;

class VKCommandHandler extends CommandHandler
{
	/**
	 * @var VKBot
	 */
	protected $bot;

	/**
	 * Идентификатор назначения (куда отправлять ответ)
	 *
	 * @var integer
	 */
	protected $peerID;

	/**
	 * VKCommandHandler constructor.
	 *
	 * @param VKBot $vkBot
	 * @param int $peerID - идентификатор назначения (куда отправлять ответ)
	 * @param string $command - переданная пользователем команда
	 */
	public function __construct (VKBot $vkBot, int $peerID, string $command)
	{
		parent::__construct();

		$this->bot     = $vkBot;
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
			$this->bot->sendMessage($this->peerID, $this->answers['greetings_with_send_group_name'], array ('keyboard' => $this->getKeyboard('cancel')));
			return;
		}

		// Инициализируем пользователя
		$this->user = new VKUser($userID);
		$this->user->loadData();

		// Получаем обработанный ответ на команду пользователя
		$message = $this->getAnswerToCommand();
		if (is_null($message))
			return;

		$this->bot->sendMessage($this->peerID, $message['text'], $message['params']);
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
		$params['keyboard'] = $this->getKeyboard($params['keyboard_type'] ?? null);

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
		$userInfo = $this->bot->getVKApiClient()->users()->get($this->bot->config['access_token'], array (
			'user_ids' => $this->peerID,
			'fields' => 'first_name', 'last_name'
		));

		$message  = '⚠ Новый вопрос от ' . $userInfo[0]['first_name'] . ' ' . $userInfo[0]['last_name'] . ' @id' . $this->peerID . ' [VK]<br><br>';
		$message .= 'Вопрос:<br>"' . $this->command['original'] . '"';

		// Отправляем уведомление в беседу разработчиков
		$this->bot->sendMessage(Config::VK_DEVELOPERS_TALK_PEER_ID, $message);

		$this->user->update('expected_input', null);
		return $this->createMessage('Ваш вопрос был успешно отправлен.<br>С вами свяжутся в ближайшее время', array ('keyboard_type' => 'full'));
	}
}