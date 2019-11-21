<?php


namespace Core;


use Core\Entities\Command;
use Core\Entities\CommandAnswer;
use Exception;

abstract class ACommandHandler
{
	/**
	 * Переданная пользователем команда
	 * @var Command
	 */
	protected $command;

	/**
	 * Пользователь
	 * @var AUser
	 */
	protected $user;

	/**
	 * Пользователь
	 * @var AViewer
	 */
	protected $viewer;

	/**
	 * Ожидаемые типы ввода от пользователя
	 * @var array
	 */
	protected $expectedInputTypes = array (
		// Ввод названия группы
		'group_name' => array (
			'method_name' => 'inputUserGroup',
			'allowed_methods' => array ('cancelInput', 'askNewQuestion')
		),

		// Ввод текста вопроса
		'question_text' => array (
			'method_name' => 'inputQuestionText',
			'allowed_methods' => array ('cancelInput')
		)
	);

	/**
	 * Шаблоны ответов на команды пользователя
	 * @var array
	 */
	public static $answers = array (
		// Сообщения
		'greetings' => 'Здравствуйте, Вы были успешно зарегистрированы в системе :)<br>Чтобы получить помощь используйте команду /help',
		'greetings_with_send_group_name' => 'Здравствуйте, Вы были успешно зарегистрированы в системе :)<br><br>⚠ Теперь пришлите свою группу.<br>Например: ИУ1-11Б, К3-12Б и др.<br><br>❓ Если вы хотите задать вопрос, пришлите в ответ "Отмена", а затем соответствующую команду.<br><br>Чтобы получить справку (список команд), используйте команду /help',
		'canceled' => 'Отменено',
		'available_commands' => 'Доступные команды<br><br>0. Список команд (помощь, /help)<br>1. На сегодня (/today группа)<br>2. На завтра (/tomorrow группа)<br>3. На эту неделю (/thisWeek группа)<br>4. На следующую неделю (/nextWeek группа)<br>5. Изменить группу (/changeGroup группа)<br>6. Задать вопрос (/askQuestion)<br><br>Можно присылать цифрами, русским текстом или командами, указанными в скобках',

		// Уведомления
		'send_group_name' => 'Пришлите название своей группы.<br>Например: ИУ1-11Б, К3-12Б и др.',
		'send_question_text' => 'Пришлите свой вопрос, он будет передан разработчику',
		'you_have_already_registered' => 'Вы уже зарегистрированы в системе',
		'bot_is_offline' => 'Извините, бот отключён. Попробуйте позже',

		// Ошибки
		'undefined_command' => 'Неизвестная команда, попробуйте изменить запрос :)',
		'undefined_expected_input' => 'От вас ожидается непонятный системе ввод. Свяжитесь с разработчиком для исправления ошибки.',
		'cannot_find_group' => 'Группа не найдена. Проверьте правильность написания.<br>Например:  ИУ1-11Б, К3-12Б и др.',
		'set_group_name' => 'Вы не установили группу по умолчанию.<br>Установите её с помощью соответствующей команды (используйте команду /help для получения справки)',
		'get_group_schedule_undefined_error' => 'Неизвестная ошибка при получении расписания группы'
	);

	/**
	 * ACommandHandler constructor.
	 *
	 * @param Command $command - Переданная пользователем команда
	 * @param AUser $user - Пользователь
	 * @param AViewer $viewer - Обработчик вывода
	 */
	public function __construct(Command $command, AUser $user, AViewer $viewer)
	{
		$this->command = $command;
		$this->user    = $user;
		$this->viewer  = $viewer;
	}

	/**
	 * Передаёт обработку команды соответсвующему обработчику
	 *
	 * @return CommandAnswer|null
	 * @throws Exception
	 */
	public function handle(): ?CommandAnswer
	{
		// Нет никаких проверок на существование метода в классе, т.к. его отсутствие в любом случае вызовет исключение

		// Если от пользователя НЕ ожидается какой-либо ввод
		if (is_null($this->user->expected_input))
			$return = $this->{$this->command->handlerName}();
		// Если от пользователя ожидается какой-либо ввод
		else {
			if (array_key_exists($this->user->expected_input, $this->expectedInputTypes))
				// Если пользователь ввёл разрешённую команду при ожидаемом вводе, передаём обработку её обработчику, иначе обработчику ожидаемого ввода
				if (array_search($this->command->handlerName, $this->expectedInputTypes[$this->user->expected_input]['allowed_methods'] ?? array()) !== false)
					$return = $this->{$this->command->handlerName}();
				else
					$return = $this->{$this->expectedInputTypes[$this->user->expected_input]['method_name']}();
			else
				$return = new CommandAnswer(static::$answers['undefined_expected_input']);
		}

		return $return;
	}

	/**
	 * Возвращает расписание установленной по умолчанию или переданной в качестве аргумента группы
	 *
	 * @return array
	 * @throws Exception
	 */
	protected function getGroupSchedule(): array
	{
		if (is_null($this->command->arguments))
			if (!is_null($this->user->group_symbolic))
				$schedule = Schedule::loadSchedule($this->user->group_symbolic);
			else
				return array ('error' => 'set_group_name');
		else {
			// Ищем группу
			$group = Schedule::searchGroup($this->command->arguments[0]);
			if (!$group)
				return array('error' => 'cannot_find_group');

			$schedule = Schedule::loadSchedule($group['symbolic']);
		}

		return $schedule;
	}


	/***********************************************************
	 * ОБРАБОТЧИКИ КОМАНД
	 ***********************************************************/


	/**
	 * Обработчик команды "Начать"
	 *
	 * @return CommandAnswer
	 */
	protected function start(): CommandAnswer
	{
		return new CommandAnswer(static::$answers['you_have_already_registered']);
	}

	/**
	 * Обработчик команды "Отмена"
	 *
	 * @return CommandAnswer
	 */
	protected function cancelInput(): CommandAnswer
	{
		if (is_null($this->user->expected_input))
			return new CommandAnswer('Нечего отменять');

		$this->user->update('expected_input', null);
		return new CommandAnswer(static::$answers['canceled']);
	}

	/**
	 * Обработчик команды "Список команд"
	 *
	 * @return CommandAnswer
	 */
	protected function sendHelp(): CommandAnswer
	{
		return new CommandAnswer(static::$answers['available_commands']);
	}

	/**
	 * Обработчик команды "На сегодня"
	 *
	 * @return CommandAnswer
	 * @throws Exception
	 */
	protected function sendScheduleForToday(): CommandAnswer
	{
		$schedule = $this->getGroupSchedule();
		if (isset($schedule['error']))
			return new CommandAnswer(static::$answers[$schedule['error']] ?? static::$answers['get_group_schedule_undefined_error']);

		$answer = $this->viewer->viewToday($schedule, Schedule::getEventsForDay(date('Y-m-d'), $schedule['data']['group']['city']));
		return new CommandAnswer($answer);
	}

	/**
	 * Обработчик команды "На завтра"
	 *
	 * @return CommandAnswer
	 * @throws Exception
	 */
	protected function sendScheduleForTomorrow(): CommandAnswer
	{
		$schedule = $this->getGroupSchedule();
		if (isset($schedule['error']))
			return new CommandAnswer(static::$answers[$schedule['error']] ?? static::$answers['get_group_schedule_undefined_error']);

		$answer = $this->viewer->viewTomorrow($schedule, Schedule::getEventsForDay(date('Y-m-d', time()+86400), $schedule['data']['group']['city']));
		return new CommandAnswer($answer);
	}

	/**
	 * Обработчик команды "На эту неделю"
	 *
	 * @return CommandAnswer
	 * @throws Exception
	 */
	protected function sendScheduleForThisWeek(): CommandAnswer
	{
		$schedule = $this->getGroupSchedule();
		if (isset($schedule['error']))
			return new CommandAnswer(static::$answers[$schedule['error']] ?? static::$answers['get_group_schedule_undefined_error']);

		$answer = $this->viewer->viewThisWeek($schedule, Schedule::getEventsForWeek(false, $schedule['data']['group']['city']));
		return new CommandAnswer($answer);
	}

	/**
	 * Обработчик команды "На следующую неделю"
	 *
	 * @return CommandAnswer
	 * @throws Exception
	 */
	protected function sendScheduleForNextWeek(): CommandAnswer
	{
		$schedule = $this->getGroupSchedule();
		if (isset($schedule['error']))
			return new CommandAnswer(static::$answers[$schedule['error']] ?? static::$answers['get_group_schedule_undefined_error']);

		$answer = $this->viewer->viewNextWeek($schedule, Schedule::getEventsForWeek(true, $schedule['data']['group']['city']));
		return new CommandAnswer($answer);
	}

	/**
	 * Обработчик команды "Изменить группу"
	 *
	 * @return CommandAnswer
	 * @throws Exception
	 */
	protected function changeUserGroup(): CommandAnswer
	{
		if (!is_null($this->command->arguments))
			return $this->inputUserGroup($this->command->arguments[0]);

		$this->user->update('expected_input', 'group_name');
		return new CommandAnswer(static::$answers['send_group_name'], 'cancel');
	}

	/**
	 * Обработчик команды "Задать вопрос"
	 *
	 * @return CommandAnswer
	 */
	protected function askNewQuestion(): CommandAnswer
	{
		$this->user->update('expected_input', 'question_text');
		return new CommandAnswer(static::$answers['send_question_text'], 'cancel');
	}

	/**
	 * Обработчик ввода группы пользователя
	 *
	 * @param string|null $groupName - группа пользователя
	 * @return CommandAnswer
	 * @throws Exception
	 */
	protected function inputUserGroup(string $groupName = null): CommandAnswer
	{
		if (is_null($groupName))
			$group = Schedule::searchGroup($this->command->original);
		else
			$group = Schedule::searchGroup($groupName);

		if (!$group)
			return new CommandAnswer(static::$answers['cannot_find_group'], 'cancel');

		$this->user->update('group_symbolic', $group['symbolic']);
		$this->user->update('expected_input', null);
		return new CommandAnswer('Ваша группа была успешно изменена на ' . $group['caption']);
	}

	/**
	 * Обработчик ввода текста вопроса
	 *
	 * @return CommandAnswer
	 */
	protected abstract function inputQuestionText(): CommandAnswer;
}