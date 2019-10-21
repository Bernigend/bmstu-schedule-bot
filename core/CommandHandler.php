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
		// Начало использования бота
		'начать' => 'startUsingBot',

		// Список доступных команд
		0 => 'sendHelp',
		'/help' => 'sendHelp',
		'помощь' => 'sendHelp',
		'списоккоманд' => 'sendHelp',

		// Расписание на сегодня
		1 => 'sendScheduleForToday',
		'/today' => 'sendScheduleForToday',
		'насегодня' => 'sendScheduleForToday',

		// Расписание на завтра
		2 => 'sendScheduleForTomorrow',
		'/tomorrow' => 'sendScheduleForTomorrow',
		'назавтра' => 'sendScheduleForTomorrow',

		// Расписание на текущую неделю
		3 => 'sendScheduleForThisWeek',
		'/currentweek' => 'sendScheduleForThisWeek',
		'наэтунеделю' => 'sendScheduleForThisWeek',

		// Расписание на текущую неделю
		4 => 'sendScheduleForNextWeek',
		'/nextweek' => 'sendScheduleForNextWeek',
		'наследующуюнеделю' => 'sendScheduleForNextWeek',

		// Изменение группы
		5 => 'changeUserGroup',
		'/changegroup' => 'changeUserGroup',
		'изменитьгруппу' => 'changeUserGroup',

		// Изменение группы
		6 => 'askNewQuestion',
		'/askquestion' => 'askNewQuestion',
		'задатьвопрос' => 'askNewQuestion',

		// Отмена ввода
		'/cancel' => 'cancelInput',
		'отмена' => 'cancelInput'
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
		'group_name' => array (
			'method_name' => 'inputUserGroup',
			'allowed_methods' => array ('cancelInput', 'askNewQuestion')
		),
		// Текст вопроса
		'question_text' => array (
			'method_name' => 'inputQuestionText',
			'allowed_methods' => array ('cancelInput')
		)
	);

	/**
	 * Ответные сообщения на действия пользователя доступные любому боту
	 *
	 * @var array
	 */
	protected $answers = array (
		// Сообщения
		'greetings' => 'Здравствуйте, Вы были успешно зарегестрированы в системе :)<br>Чтобы получить помощь используйте команду /help',
		'greetings_with_send_group_name' => 'Здравствуйте, Вы были успешно зарегестрированы в системе :)<br><br>⚠ Теперь пришлите свою группу.<br>Например: ИУ1-11Б, К3-12Б и др.<br><br>❓ Если вы хотите задать вопрос, пришлите в ответ "Отмена", а затем соответствующую команду.<br><br>Чтобы получить справку (список команд), используйте команду /help',
		'canceled' => 'Отменено',
		'available_commands' => 'Доступные команды<br><br>0. Список команд (помощь, /help)<br>1. На сегодня (/today [группа])<br>2. На завтра (/tomorrow [группа])<br>3. На эту неделю (/thisWeek [группа])<br>4. На следующую неделю (/nextWeek [группа])<br>5. Изменить группу (/changeGroup [группа])<br>6. Задать вопрос (/askQuestion)<br><br>Можно присылать цифрами, русским текстом или командами, указанными в скобках',

		// Уведомления
		'send_group_name' => 'Пришлите название своей группы.<br>Например: ИУ1-11Б, К3-12Б и др.',
		'send_question_text' => 'Пришлите свой вопрос, он будет передан разработчику',
		'you_have_already_registered' => 'Вы уже зарегестрированы в системе',

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
	public function getAnswerToCommand () : ?array
	{
		// Если от пользователя не ожидается какой-либо ввод, передаём команду её обработчику
		if (is_null ($this->user->data->expected_input)) {
			if (isset ($this->commands [$this->command['name']]))
				$message = $this->{$this->commands[$this->command['name']]}();
			else
				$message = null;
		// Если от пользователя ожидается какой-либо ввод, обрабатываем его
		} else {
			if (isset($this->expectedInputTypes[$this->user->data->expected_input]))
				if (isset($this->commands[$this->command['name']]) && array_search($this->commands[$this->command['name']], $this->expectedInputTypes[$this->user->data->expected_input]['allowed_methods'] ?? array()) !== false)
					$message = $this->{$this->commands[$this->command['name']]}();
				else
					$message = $this->{$this->expectedInputTypes[$this->user->data->expected_input]['method_name']}();
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

		if (array_key_exists($preparedCommand[0], $this->commands) && count($preparedCommand) > 1) {
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
	 * Обработчик команды "Начать"
	 * @return array
	 */
	public function startUsingBot () : array
	{
		return $this->createMessage($this->answers['you_have_already_registered'], array ('keyboard_type' => 'full'));
	}


	/**
	 * Обработчик команды "Отмена"
	 * @return array
	 */
	public function cancelInput () : ?array
	{
		if (is_null($this->user->data->expected_input))
			return null;

		$this->user->update('expected_input', null);
		return $this->createMessage($this->answers['canceled'], array ('keyboard_type' => 'full'));
	}


	/**
	 * Обработчик команды "Помощь"
	 * @return array
	 */
	protected function sendHelp () : array
	{
		return $this->createMessage($this->answers['available_commands'], array ('keyboard_type' => 'full'));
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
			return $this->createMessage($this->answers[$schedule['error']], array ('keyboard_type' => 'full'));

		$week = Schedule::getWeekName($schedule['data']['usual_time']);
		if ($week == 'at_numerator')
			$message = 'Вы учитесь по числителю';
		else
			$message = 'Вы учитесь по знаменателю';

		$message .= '<br>';

		if (date('n') > 8)
			$message .= 'Семестр: 1';
		else
			$message .= 'Семестр: 2';
		$message .= '<br>';

		$message .= 'Группа: ' . $schedule['data']['group']['name'] . ' (' . $schedule['data']['group']['city']  . ')<br><br>';
		$message .= ' ---- ---- <br><br>';

		$message .= $this->scheduleViewer->getToday($schedule) . '<br><br>';
		$message .= ' ---- ---- <br><br>';

		if ($schedule['data']['group']['city'] == 'MF') {
			$message .= '🏛 Мероприятия:<br>';
			$message .= $this->scheduleViewer->getEventsForDay(date('Y-m-d'), $schedule['data']['group']['city']) . '<br><br>';
			$message .= ' ---- ---- <br><br>';
		}

		$message .= 'Для вывода списка команд, пришлите Помощь (/help)';

		return $this->createMessage($message, array ('keyboard_type' => 'full'));
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
			return $this->createMessage($this->answers[$schedule['error']], array ('keyboard_type' => 'full'));

		$week = Schedule::getWeekName($schedule['data']['usual_time']);
		if ($week == 'at_numerator')
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

		$message .= $this->scheduleViewer->getTomorrow($schedule) . '<br><br>';
		$message .= ' ---- ---- <br><br>';

		if ($schedule['data']['group']['city'] == 'MF') {
			$message .= '🏛 Мероприятия:<br>';
			$message .= $this->scheduleViewer->getEventsForDay(date('Y-m-d', time()+86400), $schedule['data']['group']['city']) . '<br><br>';
			$message .= ' ---- ---- <br><br>';
		}

		$message .= 'Для вывода списка команд, пришлите Помощь (/help)';

		return $this->createMessage($message, array ('keyboard_type' => 'full'));
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
			return $this->createMessage($this->answers[$schedule['error']], array ('keyboard_type' => 'full'));

		$week = Schedule::getWeekName($schedule['data']['usual_time']);
		if ($week == 'at_numerator')
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

		$message .= $this->scheduleViewer->getWeek($schedule) . '<br><br>';
		$message .= ' ---- ---- <br><br>';

		if ($schedule['data']['group']['city'] == 'MF') {
			$message .= '🏛 Мероприятия:<br>';
			$message .= $this->scheduleViewer->getEventsForWeek(false, $schedule['data']['group']['city']) . '<br><br>';
			$message .= ' ---- ---- <br><br>';
		}

		$message .= 'Для вывода списка команд, пришлите Помощь (/help)';

		return $this->createMessage($message, array ('keyboard_type' => 'full'));
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
			return $this->createMessage($this->answers[$schedule['error']], array ('keyboard_type' => 'full'));

		$week = Schedule::getWeekName($schedule['data']['usual_time'], true);
		if ($week == 'at_numerator')
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

		$message .= $this->scheduleViewer->getWeek($schedule, true) . '<br><br>';
		$message .= ' ---- ---- <br><br>';

		if ($schedule['data']['group']['city'] == 'MF') {
			$message .= '🏛 Мероприятия:<br>';
			$message .= $this->scheduleViewer->getEventsForWeek(true, $schedule['data']['group']['city']) . '<br><br>';
			$message .= ' ---- ---- <br><br>';
		}

		$message .= 'Для вывода списка команд, пришлите Помощь (/help)';

		return $this->createMessage($message, array ('keyboard_type' => 'full'));
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

		return $this->createMessage($this->answers['send_group_name'], array ('keyboard_type' => 'cancel'));
	}

	/**
	 * Обработчик команды "Задать вопрос"
	 * @return array
	 */
	protected function askNewQuestion () : array
	{
		$this->user->update('expected_input', 'question_text');
		return $this->createMessage($this->answers['send_question_text'], array ('keyboard_type' => 'cancel'));
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
			return $this->createMessage($this->answers['cannot_find_group'], array ('keyboard_type' => 'cancel'));

		$this->user->update('group_symbolic', $group['symbolic']);
		$this->user->update('expected_input', null);
		return $this->createMessage('Ваша группа была успешно изменена на ' . $group['caption'], array ('keyboard_type' => 'full'));
	}

	/**
	 * Обработчик ввода текста вопроса
	 * @return array
	 */
	protected abstract function inputQuestionText () : array;
}