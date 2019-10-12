<?php


namespace Core;


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
	protected $command;

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
		'cannot_find_group' => 'Группа не найдена. Проверьте правильность написания.<br>Например:  ИУ1-11Б, К3-12Б и др.'
	);

	/**
	 * Ответные сообщения на действия пользователя доступные только определённому боту
	 *
	 * @var array
	 */
	protected $localAnswers = array ();

	/**
	 * CommandHandler constructor.
	 */
	public function __construct ()
	{
		$this->answers = array_merge($this->answers, $this->localAnswers);
		$this->commands = array_merge($this->commands, $this->localCommands);
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
				$message = $this->createMessage($this->answers['undefined_command']);
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
			'name'      => preg_replace('/\s+/', '', mb_strtolower($command, 'UTF-8')),
			'arguments' => null
		);

		$preparedCommand = preg_replace('/\s+/', ' ', mb_strtolower($command, 'UTF-8'));
		$preparedCommand = trim($preparedCommand);
		$preparedCommand = explode(' ', $preparedCommand);

		if (isset($this->commands[$preparedCommand[0]]) && count($preparedCommand) > 1) {
			$returnCommand['name'] = array_shift($preparedCommand);
			$returnCommand['arguments'] = $preparedCommand;
		}

		return $returnCommand;
	}

	/**
	 * Возвращает данные о сообщении в виде массива
	 *
	 * @param string $message - текст сообщения
	 * @param array $params - параметры сообщения
	 * @return array
	 */
	protected abstract function createMessage (string $message, array $params = array()) : array;


	/******************************************************************************
	 * ОБРАБОТЧИКИ КОМАНД
	 ******************************************************************************/


	/**
	 * Обработчик команды "Помощь"
	 * @return array
	 */
	protected abstract function sendHelp () : array;

	/**
	 * Обработчик команды "Прислать расписание на сегодня"
	 * @return array
	 */
	protected abstract function sendScheduleForToday () : array;

	/**
	 * Обработчик команды "Прислать расписание на завтра"
	 * @return array
	 */
	protected abstract function sendScheduleForTomorrow () : array;

	/**
	 * Обработчик команды "Прислать расписание на эту неделю"
	 * @return array
	 */
	protected abstract function sendScheduleForThisWeek () : array;

	/**
	 * Обработчик команды "Прислать расписание на следующую неделю"
	 * @return array
	 */
	protected abstract function sendScheduleForNextWeek () : array;

	/**
	 * Обработчик команды "Изменить группу"
	 * @return array
	 */
	protected abstract function changeUserGroup () : array;

	/**
	 * Обработчик команды "Задать вопрос"
	 * @return array
	 */
	protected abstract function askNewQuestion () : array;


	/**
	 * Обработчик ввода группы пользователя
	 * @return array
	 */
	protected abstract function inputUserGroup () : array;

	/**
	 * Обработчик ввода текста вопроса
	 * @return array
	 */
	protected abstract function inputQuestionText () : array;
}