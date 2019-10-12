<?php


namespace Core\VK;


use Core\CommandHandler;

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
	public function __construct(int $peerID, string $command)
	{
		parent::__construct();

		$this->peerID  = $peerID;
		$this->command = $this->prepareCommand($command);
	}

	public function handle ()
	{
		// Ищем пользователя в БД, если не найден - регистрируем и отправляем сообщение о регистрации
		$userID = VKUser::find ($this->peerID);
		if (!$userID) {
			VKUser::register($this->peerID);
			VKBot::sendMessage($this->peerID, $this->answers['greetings'], null);
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
		return array_merge(array ('message' => $message), $params);
	}

	/**
	 * Обработчик команды "Помощь"
	 * @return array
	 */
	protected function sendHelp() : array
	{
		// TODO: Implement sendHelp() method.
	}

	/**
	 * Обработчик команды "Прислать расписание на сегодня"
	 * @return array
	 */
	protected function sendScheduleForToday() : array
	{
		// TODO: Implement sendScheduleForToday() method.
	}

	/**
	 * Обработчик команды "Прислать расписание на завтра"
	 * @return array
	 */
	protected function sendScheduleForTomorrow() : array
	{
		// TODO: Implement sendScheduleForTomorrow() method.
	}

	/**
	 * Обработчик команды "Прислать расписание на эту неделю"
	 * @return array
	 */
	protected function sendScheduleForThisWeek() : array
	{
		// TODO: Implement sendScheduleForThisWeek() method.
	}

	/**
	 * Обработчик команды "Прислать расписание на следующую неделю"
	 * @return array
	 */
	protected function sendScheduleForNextWeek() : array
	{
		// TODO: Implement sendScheduleForNextWeek() method.
	}

	/**
	 * Обработчик команды "Изменить группу"
	 * @return array
	 */
	protected function changeUserGroup() : array
	{
		// TODO: Implement changeUserGroup() method.
	}

	/**
	 * Обработчик команды "Задать вопрос"
	 * @return array
	 */
	protected function askNewQuestion() : array
	{
		// TODO: Implement askNewQuestion() method.
	}

	/**
	 * Обработчик ввода группы пользователя
	 * @return array
	 */
	protected function inputUserGroup() : array
	{
		// TODO: Implement inputUserGroup() method.
	}

	/**
	 * Обработчик ввода текста вопроса
	 * @return array
	 */
	protected function inputQuestionText() : array
	{
		// TODO: Implement inputQuestionText() method.
	}
}