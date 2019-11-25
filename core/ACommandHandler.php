<?php


namespace Core;


use Core\Bots\IBot;
use Core\Entities\Command;
use Exception;

abstract class ACommandHandler
{
	/**
	 * Бот
	 * @var IBot
	 */
	protected $bot;

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
	 * Обработчик вывода
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
		'greetings' => "Здравствуйте, Вы были успешно зарегистрированы в системе :)\nДля вывода списка команд пришлите \"Список команд\" или \"/help\"",
		'greetings_with_send_group_name' => "Здравствуйте, Вы были успешно зарегистрированы в системе :)\n\n⚠ Теперь пришлите свою группу.\nНапример: ИУ1-11Б, К3-12Б и др.\n\n❓ Если вы хотите задать вопрос, пришлите в ответ \"Задать вопрос\"",
		'canceled' => 'Отменено',
		'available_commands' => "Доступные команды:\n\n0. \"Список команд\" или \"/help\";\n\n1. \"На сегодня\" или \"/today [группа]\";\n2. \"На завтра\" или \"/tomorrow [группа]\";\n3. \"На эту неделю\" или \"/currentWeek [группа]\";\n4. \"На следующую неделю\" или \"/nextWeek [группа]\";\n\n5. \"Изменить группу\" или \"/changeGroup [группа]\";\n\n6. \"Задать вопрос\" или \"/askQuestion\";\n-- \"Отмена\" или \"/cancel\"",

		// Уведомления
		'send_group_name' => "Пришлите название своей группы.\nНапример: ИУ1-11Б, К3-12Б и др.\n\nДля отмены пришлите \"Отмена\"",
		'send_question_text' => "Пришлите свой вопрос, он будет передан разработчику.\n\nДля отмены пришлите \"Отмена\"",
		'you_have_already_registered' => 'Вы уже зарегистрированы в системе',
		'bot_is_offline' => 'Извините, бот отключён. Попробуйте позже',
		'nothing_to_cancel' => 'Нечего отменять',
		'question_successfully_sent' => 'Вопрос был успешно отправлен. С вами свяжутся в ближайшее время',

		// Ошибки
		'undefined_command' => 'Неизвестная команда, попробуйте изменить запрос :)',
		'undefined_expected_input' => 'От вас ожидается непонятный системе ввод. Свяжитесь с разработчиком для исправления ошибки.',
		'cannot_find_group' => "Группа не найдена. Проверьте правильность написания.\nНапример:  ИУ1-11Б, К3-12Б и др.",
		'cannot_find_default_group' => 'Установленная по умолчанию группа не найдена. Измените её с помощью команды "Изменить группу"',
		'set_group_name' => "Вы не установили группу по умолчанию.\nУстановите её с помощью команды \"Изменить группу\"",
		'get_group_schedule_undefined_error' => 'Неизвестная ошибка при получении расписания группы'
	);

	/**
	 * ACommandHandler constructor.
	 *
	 * @param IBot $bot - Бот
	 * @param Command $command - команда пользователя
	 * @param AUser $user - Пользователь
	 * @param AViewer $viewer - Обработчик вывода
	 */
	public function __construct(IBot $bot, Command $command, AUser $user, AViewer $viewer)
	{
		$this->bot     = $bot;
		$this->command = $command;
		$this->user    = $user;
		$this->viewer  = $viewer;
	}

	/**
	 * Передаёт обработку команды соответсвующему обработчику
	 *
	 * @return bool
	 * @throws Exception
	 */
	public function handle(): bool
	{
		// Нет никаких проверок на существование метода в классе, т.к. его отсутствие в любом случае вызовет исключение

		$start_time_handle = microtime(true);

		// Если от пользователя НЕ ожидается какой-либо ввод
		if (is_null($this->user->expected_input))
			$this->{$this->command->handlerName}();
		// Если от пользователя ожидается какой-либо ввод
		else {
			if (array_key_exists($this->user->expected_input, $this->expectedInputTypes))
				// Если пользователь ввёл разрешённую команду при ожидаемом вводе, передаём обработку её обработчику, иначе обработчику ожидаемого ввода
				if (array_search($this->command->handlerName, $this->expectedInputTypes[$this->user->expected_input]['allowed_methods'] ?? array()) !== false)
					$this->{$this->command->handlerName}();
				else
					$this->{$this->expectedInputTypes[$this->user->expected_input]['method_name']}();
			else
				$this->bot->sendMessage($this->user->destinationID, static::$answers['undefined_expected_input'], 'full');
		}

		if (isset($BOT_LOG)) $BOT_LOG->addToLog("Command handle finished in " . microtime(true) - $start_time_handle . " sec;\n");

		return true;
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
			if (!is_null($this->user->group_symbolic)){
				$schedule = Schedule::loadSchedule($this->user->group_symbolic);
				if (!isset($schedule['data']['group']['symbolic']) || empty($schedule['data']['group']['symbolic']))
					return array ('error' => 'cannot_find_default_group');
			} else
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
	 * @return void
	 */
	protected function start(): void
	{
		$this->bot->sendMessage($this->user->destinationID, static::$answers['you_have_already_registered'], 'full');
		return;
	}

	/**
	 * Обработчик команды "Отмена"
	 *
	 * @return void
	 */
	protected function cancelInput(): void
	{
		if (is_null($this->user->expected_input)){
			$this->bot->sendMessage($this->user->destinationID, static::$answers['nothing_to_cancel'], 'full');
			return;
		}

		$this->user->update('expected_input', null);
		$this->bot->sendMessage($this->user->destinationID, static::$answers['canceled'], 'full');
		return;
	}

	/**
	 * Обработчик команды "Список команд"
	 *
	 * @return void
	 */
	protected function sendHelp(): void
	{
		$this->bot->sendMessage($this->user->destinationID, static::$answers['available_commands'], 'full');
		return;
	}

	/**
	 * Обработчик команды "На сегодня"
	 *
	 * @return void
	 * @throws Exception
	 */
	protected function sendScheduleForToday(): void
	{
		$schedule = $this->getGroupSchedule();
		if (isset($schedule['error'])){
			$this->bot->sendMessage($this->user->destinationID, static::$answers[$schedule['error']] ?? static::$answers['get_group_schedule_undefined_error'], 'full');
			return;
		}

		$answer = $this->viewer->viewToday($schedule, Schedule::getEventsForDay(date('Y-m-d'), $schedule['data']['group']['city']));
		$this->bot->sendMessage($this->user->destinationID, $answer, 'full');
		return;
	}

	/**
	 * Обработчик команды "На завтра"
	 *
	 * @return void
	 * @throws Exception
	 */
	protected function sendScheduleForTomorrow(): void
	{
		$schedule = $this->getGroupSchedule();
		if (isset($schedule['error'])){
			$this->bot->sendMessage($this->user->destinationID, static::$answers[$schedule['error']] ?? static::$answers['get_group_schedule_undefined_error'], 'full');
			return;
		}

		$answer = $this->viewer->viewTomorrow($schedule, Schedule::getEventsForDay(date('Y-m-d', time()+86400), $schedule['data']['group']['city']));
		$this->bot->sendMessage($this->user->destinationID, $answer, 'full');
		return;
	}

	/**
	 * Обработчик команды "На эту неделю"
	 *
	 * @return void
	 * @throws Exception
	 */
	protected function sendScheduleForThisWeek(): void
	{
		$schedule = $this->getGroupSchedule();
		if (isset($schedule['error'])){
			$this->bot->sendMessage($this->user->destinationID, static::$answers[$schedule['error']] ?? static::$answers['get_group_schedule_undefined_error'], 'full');
			return;
		}

		$answer = $this->viewer->viewThisWeek($schedule, Schedule::getEventsForWeek(false, $schedule['data']['group']['city']));
		$this->bot->sendMessage($this->user->destinationID, $answer, 'full');
		return;
	}

	/**
	 * Обработчик команды "На следующую неделю"
	 *
	 * @return void
	 * @throws Exception
	 */
	protected function sendScheduleForNextWeek(): void
	{
		$schedule = $this->getGroupSchedule();
		if (isset($schedule['error'])){
			$this->bot->sendMessage($this->user->destinationID, static::$answers[$schedule['error']] ?? static::$answers['get_group_schedule_undefined_error'], 'full');
			return;
		}

		$answer = $this->viewer->viewNextWeek($schedule, Schedule::getEventsForWeek(true, $schedule['data']['group']['city']));
		$this->bot->sendMessage($this->user->destinationID, $answer, 'full');
		return;
	}

	/**
	 * Отправляет статистику времени выполнения скрипта админам
	 *
	 * @throws Exception
	 */
	protected function sendStatistic(): void
	{
		if (array_search("peerID-{$this->user->destinationID}", Config::ADMIN_USERS) === false && array_search("chatID-{$this->user->destinationID}", Config::ADMIN_USERS) === false)
			return;

		DataBase::connect();

		$minTime = DataBase::getOne('SELECT MIN(`script_time`) as min FROM `stats` WHERE `date` = ?', array(date('Y-m-d')));
		$maxTime = DataBase::getOne('SELECT MAX(`script_time`) as max FROM `stats` WHERE `date` = ?', array(date('Y-m-d')));
		$avgTime = DataBase::getOne('SELECT AVG(`script_time`) as avg FROM `stats` WHERE `date` = ?', array(date('Y-m-d')));

		$message  = "Статистика запросов:\n\n";
		$message .= "Min: {$minTime} sec\n";
		$message .= "Max: {$maxTime} sec\n";
		$message .= "Avg: " . round($avgTime, 4) . " sec\n";

		$this->bot->sendMessage($this->user->destinationID, $message, 'full');
		return;
	}

	/**
	 * Обработчик команды "Изменить группу"
	 *
	 * @return void
	 * @throws Exception
	 */
	protected function changeUserGroup(): void
	{
		if (!is_null($this->command->arguments)){
			$this->inputUserGroup($this->command->arguments[0]);
			return;
		}

		$this->user->update('expected_input', 'group_name');
		$this->bot->sendMessage($this->user->destinationID, static::$answers['send_group_name'], 'cancel');
		return;
	}

	/**
	 * Обработчик команды "Задать вопрос"
	 *
	 * @return void
	 */
	protected function askNewQuestion(): void
	{
		$this->user->update('expected_input', 'question_text');
		$this->bot->sendMessage($this->user->destinationID, static::$answers['send_question_text'], 'cancel');
		return;
	}

	/**
	 * Обработчик ввода группы пользователя
	 *
	 * @param string|null $groupName - группа пользователя
	 * @return void
	 * @throws Exception
	 */
	protected function inputUserGroup(string $groupName = null): void
	{
		if (is_null($groupName))
			$group = Schedule::searchGroup($this->command->original);
		else
			$group = Schedule::searchGroup($groupName);

		if (!$group){
			$this->bot->sendMessage($this->user->destinationID, static::$answers['cannot_find_group'], 'cancel');
			return;
		}

		$this->user->update('group_symbolic', $group['symbolic']);
		$this->user->update('expected_input', null);
		$this->bot->sendMessage($this->user->destinationID, 'Ваша группа была успешно изменена на ' . $group['caption'], 'full');
		return;
	}

	/**
	 * Обработчик ввода текста вопроса
	 *
	 * @return void
	 */
	protected abstract function inputQuestionText(): void;
}