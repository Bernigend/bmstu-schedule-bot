<?php


namespace Core;


use Core\Schedule\Schedule;
use Core\Schedule\ScheduleViewer;
use Exception;

abstract class CommandHandler
{
	/**
	 * Пользователь бота
	 *
	 * @var User
	 */
	protected $user;

	/**
	 * Данные о команде пользователя
	 * array (
	 * 	 ['original'] - переданная команда пользователя без изменений
	 * 	 ['name'] - отделённая от остальных параметров команда
	 *   ['arguments'] - переданные аргументы
	 * )
	 *
	 * Например:
	 * array (
	 *   ['original'] => "/today ИУ1-11Б",
	 * 	 ['name'] => "/today",
	 *   ['arguments'] => "ИУ1-11Б"
	 * )
	 *
	 * @var array
	 */
	protected $command = array (
		'original' => null,
		'name' => null,
		'arguments' => null
	);

	/**
	 * Список команд, доступных любому боту
	 * array (<command> => <methodName>)
	 *
	 * @var array
	 */
	protected $commands = array (
		// Список доступных команд
		'0' => 'sendHelp',
		'/help' => 'sendHelp',

		// Расписание на сегодня
		'1' => 'sendScheduleForToday',
		'/today' => 'sendScheduleForToday',

		// Расписание на завтра
		'2' => 'sendScheduleForTomorrow',
		'/tomorrow' => 'sendScheduleForTomorrow',

		// Расписание на текущую неделю
		'3' => 'sendScheduleForThisWeek',
		'/thisweek' => 'sendScheduleForThisWeek',

		// Расписание на текущую неделю
		'4' => 'sendScheduleForNextWeek',
		'/nextweek' => 'sendScheduleForNextWeek',

		// Изменение группы
		'5' => 'changeUserGroup',
		'/changegroup' => 'changeUserGroup',

		// Изменение группы
		'6' => 'askNewQuestion',
		'/askquestion' => 'askNewQuestion'
	);

	/**
	 * Список команд, доступных только определённому боту
	 * array (<command> => <methodName>)
	 *
	 * @var array
	 */
	protected $localCommands = array ();

	/**
	 * Список обработчиков ожидаемого от пользователя ввода
	 * array (<expectedInputType> => <methodName>)
	 *
	 * @var array
	 */
	protected $expectedInputTypes = array (
		// Название группы
		'group_name' => 'inputUserGroup',
		// Текст вопроса
		'question_text' => 'inputQuestionText'
	);

	/**
	 * Ответные сообщения на действия пользователя доступные любому боту
	 *
	 * @var array
	 */
	protected $answers = array (
		// Сообщения
		'greetings' => 'Здравствуйте, Вы были успешно зарегестрированы в системе :)<br>Чтобы получить помощь используйте команду /help',
		'available_commands' => 'Доступные команды:<br>1. На сегодня (/today)<br>2. На завтра (/tomorrow)<br>3. На эту неделю (/thisWeek)<br>4. На следующую неделю (/nextWeek)<br>5. Изменить группу (/changeGroup)<br>6. Задать вопрос разработчику (/askQuestion)<br>Можно присылать цифрами и текстом, указанным в скобках',

		// Уведомления
		'send_group_name' => 'Пришлите название своей группы.<br>Например: ИУ1-11Б, К3-12Б и др.',
		'send_question_text' => 'Пришлите свой вопрос, он будет передан разработчику',

		// Ошибки
		'undefined_command' => 'Неизвестная команда, попробуйте изменить запрос :)',
		'undefined_expected_input' => 'От вас ожидается непонятный системе ввод. Свяжитесь с разработчиком для исправления ошибки.',
		'cannot_find_group' => 'Группа не найдена. Проверьте правильность написания.<br>Например:  ИУ1-11Б, К3-12Б и др.',
		'set_group_name' => 'Вы не установили группу по умолчанию.<br>Установите её с помощью соответствующей команды (используйте команду /help для получения справки)'
	);

	/**
	 * Ответные сообщения на действия пользователя доступные только определённому боту
	 *
	 * @var array
	 */
	protected $localAnswers = array ();

	/**
	 * "Шаблонизатор" вывода расписания
	 *
	 * @var ScheduleViewer
	 */
	protected $scheduleViewer;

	/**
	 * CommandHandler constructor.
	 */
	public function __construct ()
	{
		$this->answers = array_merge($this->answers, $this->localAnswers);
		$this->commands = array_merge($this->commands, $this->localCommands);

		// Подключаем шаблонизатор вывода расписания
		$this->scheduleViewer = new ScheduleViewer();
	}

	/**
	 * Возвращает ответ на пользовательскую команду после её обработки соответствующим обработчиком
	 *
	 * @return array
	 */
	public function getAnswerToCommand () : array
	{
		// Если от пользователя не ожидается какой-либо ввод, передаём команду её обработчику
		if (is_null ($this->user->data->expected_input)) {
			if (isset ($this->commands [$this->command['name']]))
				$message = $this->{$this->commands [$this->command['name']]}();
			else
				$message = $this->createMessage($this->answers['undefined_command'], array ('keyboard' => 'full'));
		// Если от пользователя ожидается какой-либо ввод, обрабатываем его
		} else {
			if (isset ($this->expectedInputTypes [$this->user->data->expected_input]))
				$message = $this->{$this->expectedInputTypes [$this->user->data->expected_input]}();
			else
				$message = $this->createMessage($this->answers['undefined_expected_input']);
		}

		return $message;
	}

	/**
	 * Удаляет пробелы и приводит к нижнему регистру команду пользователя
	 *
	 * @param string $command - полученная команда
	 * @return array
	 */
	protected function prepareCommand (string $command) : array
	{
		$returnCommand = array (
			'original'  => $command,
			'name'      => preg_replace('#|\[(.*)\]|is#', '', preg_replace('/\s+/', '', mb_strtolower($command, 'UTF-8'))),
			'arguments' => null
		);

		$preparedCommand = preg_replace('#|\[(.*)\]|is#', '', preg_replace('/\s+/', ' ', mb_strtolower($command, 'UTF-8')));
		$preparedCommand = trim($preparedCommand);
		$preparedCommand = explode(' ', $preparedCommand);

		if (isset($this->commands[$preparedCommand[0]]) && count($preparedCommand) > 1) {
			$returnCommand['name'] = array_shift($preparedCommand);
			$returnCommand['arguments'] = $preparedCommand;
		}

		return $returnCommand;
	}

	/**
	 * Возвращает расписание группы, переданной в качестве параметра, либо установленной по умолчанию
	 *
	 * @return array|mixed
	 * @throws Exception
	 */
	protected function getGroupSchedule () : array
	{
		// Если не переданы никакие параметры, ищем расписание по группе пользователя
		if (is_null($this->command['arguments'])) {
			if (!is_null($this->user->data->group_symbolic))
				$schedule = Schedule::loadSchedule($this->user->data->group_symbolic);
			else
				return array ('error' => 'set_group_name');
		} else {
			// Ищем группу
			$group = Schedule::searchGroup($this->command['arguments'][0]);
			if (!$group)
				return array ('error' => 'cannot_find_group');

			$schedule = Schedule::loadSchedule($group['symbolic']);
		}

		return $schedule;
	}

	/**
	 * Возвращает данные о сообщении в виде массива
	 *
	 * @param string $message - текст сообщения
	 * @param array $params - параметры сообщения
	 * @return array
	 */
	protected abstract function createMessage (string $message, array $params = array()) : array;

	/**
	 * Возвращает клавиатуру для отправки пользователю
	 *
	 * @param string|null $type - тип клавиатуры
	 * @return mixed
	 */
	protected abstract function getKeyboard (?string $type);


	/******************************************************************************
	 * ОБРАБОТЧИКИ КОМАНД
	 ******************************************************************************/


	/**
	 * Обработчик команды "Помощь"
	 * @return array
	 */
	protected function sendHelp () : array
	{
		return $this->createMessage($this->answers['available_commands'], array ('keyboard' => 'full'));
	}

	/**
	 * Обработчик команды "Прислать расписание на сегодня"
	 * @return array
	 * @throws Exception
	 */
	protected function sendScheduleForToday() : array
	{
		$schedule = $this->getGroupSchedule();
		if (isset ($schedule['error']) && isset($this->answers[$schedule['error']]))
			return $this->createMessage($this->answers[$schedule['error']]);

		if (date('W')%2)
			$message = 'Вы учитесь по числителю';
		else
			$message = 'Вы учитесь по знаменателю';
		$message .= '<br>';

		if (date('n') > 8)
			$message .= 'Семестр: 1';
		else
			$message .= 'Семестр: 2';
		$message .= '<br>';

		$message .= 'Группа: ' . $schedule['data']['group']['name'] . '<br><br>';
		$message .= ' ---- ---- <br><br>';

		$message .= $this->scheduleViewer->getToday($schedule);

		return $this->createMessage($message, array ('keyboard' => 'full'));
	}

	/**
	 * Обработчик команды "Прислать расписание на завтра"
	 * @return array
	 * @throws Exception
	 */
	protected function sendScheduleForTomorrow() : array
	{
		$schedule = $this->getGroupSchedule();
		if (isset ($schedule['error']) && isset($this->answers[$schedule['error']]))
			return $this->createMessage($this->answers[$schedule['error']]);

		if (date('W', time() + 86400)%2)
			$message = 'Завтра вы будете учиться по числителю';
		else
			$message = 'Завтра вы будете учиться по знаменателю';
		$message .= '<br>';

		if (date('n', time() + 86400) > 8)
			$message .= 'Семестр: 1';
		else
			$message .= 'Семестр: 2';
		$message .= '<br>';

		$message .= 'Группа: ' . $schedule['data']['group']['name'] . '<br><br>';
		$message .= ' ---- ---- <br><br>';

		$message .= $this->scheduleViewer->getTomorrow($schedule);

		return $this->createMessage($message, array ('keyboard' => 'full'));
	}

	/**
	 * Обработчик команды "Прислать расписание на эту неделю"
	 * @return array
	 * @throws Exception
	 */
	protected function sendScheduleForThisWeek() : array
	{
		$schedule = $this->getGroupSchedule();
		if (isset ($schedule['error']) && isset($this->answers[$schedule['error']]))
			return $this->createMessage($this->answers[$schedule['error']]);

		if (date('W')%2)
			$message = 'На этой неделе вы учитесь по числителю';
		else
			$message = 'На этой неделе вы учитесь по знаменателю';
		$message .= '<br>';

		if (date('n') > 8)
			$message .= 'Семестр: 1';
		else
			$message .= 'Семестр: 2';
		$message .= '<br>';

		$message .= 'Группа: ' . $schedule['data']['group']['name'] . '<br><br>';
		$message .= ' ---- ---- <br><br>';

		$message .= $this->scheduleViewer->getWeek($schedule);

		return $this->createMessage($message, array ('keyboard' => 'full'));
	}

	/**
	 * Обработчик команды "Прислать расписание на следующую неделю"
	 * @return array
	 * @throws Exception
	 */
	protected function sendScheduleForNextWeek() : array
	{
		$schedule = $this->getGroupSchedule();
		if (isset ($schedule['error']) && isset($this->answers[$schedule['error']]))
			return $this->createMessage($this->answers[$schedule['error']]);

		if (date('W', time()+86400*7)%2)
			$message = 'На следующей неделе вы будете учиться по числителю';
		else
			$message = 'На следующей неделе вы будете учиться по знаменателю';
		$message .= '<br>';

		if (date('n', time()+86400*7) > 8)
			$message .= 'Семестр: 1';
		else
			$message .= 'Семестр: 2';
		$message .= '<br>';

		$message .= 'Группа: ' . $schedule['data']['group']['name'] . '<br><br>';
		$message .= ' ---- ---- <br><br>';

		$message .= $this->scheduleViewer->getWeek($schedule, true);

		return $this->createMessage($message, array ('keyboard' => 'full'));
	}

	/**
	 * Обработчик команды "Изменить группу"
	 * @return array
	 * @throws Exception
	 */
	protected function changeUserGroup () : array
	{
		if (!is_null($this->command['arguments']))
			return $this->inputUserGroup($this->command['arguments'][0]);

		$this->user->update('expected_input', 'group_name');
		$this->user->update('group_symbolic', null);

		return $this->createMessage($this->answers['send_group_name']);
	}

	/**
	 * Обработчик команды "Задать вопрос"
	 * @return array
	 */
	protected function askNewQuestion () : array
	{
		$this->user->update('expected_input', 'question_text');
		return $this->createMessage($this->answers['send_question_text']);
	}

	/**
	 * Обработчик ввода группы пользователя
	 * @param string|null $groupName - группа пользователя
	 * @return array
	 * @throws Exception
	 */
	protected function inputUserGroup (string $groupName = null) : array
	{
		if (is_null($groupName))
			$group = Schedule::searchGroup($this->command['name']);
		else
			$group = Schedule::searchGroup($groupName);

		if (!$group)
			return $this->createMessage($this->answers['cannot_find_group']);

		$this->user->update('group_symbolic', $group['symbolic']);
		$this->user->update('expected_input', null);
		return $this->createMessage('Ваша группа была успешно изменена на ' . $group['caption'], array ('keyboard' => 'full'));
	}

	/**
	 * Обработчик ввода текста вопроса
	 * @return array
	 */
	protected abstract function inputQuestionText () : array;
}