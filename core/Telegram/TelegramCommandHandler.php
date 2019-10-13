<?php


namespace Core\Telegram;


use Core\CommandHandler;
use Core\Config;
use Core\VK\VKBot;
use Exception;
use unreal4u\TelegramAPI\Telegram\Methods\GetChat;

class TelegramCommandHandler extends CommandHandler
{
	/**
	 * Идентификатор чата
	 *
	 * @var integer
	 */
	protected $chatID;

	/**
	 * TelegramCommandHandler constructor.
	 *
	 * @param int $chatID - идентификатор чата
	 * @param string $command - переданная пользователем команда
	 */
	public function __construct (int $chatID, string $command)
	{
		parent::__construct();

		$this->chatID  = $chatID;
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
		$userID = TelegramUser::find ($this->chatID);
		if (!$userID) {
			TelegramUser::register($this->chatID, 'group_name');
			TelegramBot::sendMessage($this->chatID, $this->answers['greetings_with_send_group_name']);
			return;
		}

		// Инициализируем пользователя
		$this->user = new TelegramUser($userID);
		$this->user->loadData();

		// Получаем обработанный ответ на команду пользователя
		$message = $this->getAnswerToCommand();

		TelegramBot::sendMessage($this->chatID, $message['text'], $message['params']);
		return;
	}

	/**
	 * Возвращает данные о сообщении в виде массива
	 *
	 * @param string $message - текст сообщения
	 * @param array $params - параметры сообщения
	 * @return array
	 */
	protected function createMessage (string $message, array $params = array()) : array
	{
		if (isset($params['keyboard']))
			$params['keyboard'] = $this->getKeyboard($params['keyboard']);

		return array (
			'text' => $message,
			'params' => $params
		);
	}

	/**
	 * Возвращает клавиатуру для отправки пользователю
	 *
	 * @param string|null $type - тип клавиатуры
	 * @return mixed
	 */
	protected function getKeyboard (?string $type)
	{
		return null;
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
		$telegramChatMember = new GetChat();
		$telegramChatMember->chat_id = $this->chatID;
		$promise = TelegramBot::$telegramApiTgLog->performApiRequest($telegramChatMember);
		TelegramBot::$telegramApiLoop->run();

		$promise->then(
			function ($response) {
				$message  = '⚠ Новый вопрос от пользователя ' . $response->username . ' [Telegram]<br><br>';
				$message .= 'Вопрос:<br>"' . $this->command['original'] . '"';

				// Отправляем уведомление в беседу разработчиков
				VKBot::initVkApiClient();
				VKBot::sendMessage(Config::VK_DEVELOPERS_TALK_PEER_ID, $message);
			}
		);

		$this->user->update('expected_input', null);
		return $this->createMessage('Ваш вопрос был успешно отправлен.<br>С вами свяжутся в ближайшее время', array ('keyboard' => 'full'));
	}
}