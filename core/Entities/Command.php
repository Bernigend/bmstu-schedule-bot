<?php


namespace Core\Entities;


use Core\AUser;

class Command
{
	/**
	 * Текст необработанной команды
	 * @var string
	 */
	public $original;

	/**
	 * Информация о нажатой кнопке (если существует)
	 * @var ?mixed
	 */
	public $payload = null;

	/**
	 * Название метода обработчика команды в Core\ACommandHandler
	 * @var string
	 */
	public $handlerName;

	/**
	 * Аргументы команды
	 * @var ?string
	 */
	public $arguments = null;

	/**
	 * Список доступных обработчиков команд по ключевым словам
	 * @var array
	 */
	protected $handlerNames = array (
		// Начало использования бота
		'/start' => 'start',
		'начать' => 'start',

		// Список доступных команд
		0 => 'sendHelp',
		'/help'         => 'sendHelp',
		'помощь'        => 'sendHelp',
		'список команд' => 'sendHelp',

		// Расписание на сегодня
		1            => 'sendScheduleForToday',
		'/today'     => 'sendScheduleForToday',
		'на сегодня' => 'sendScheduleForToday',

		// Расписание на завтра
		2           => 'sendScheduleForTomorrow',
		'/tomorrow' => 'sendScheduleForTomorrow',
		'на завтра' => 'sendScheduleForTomorrow',

		// Расписание на текущую неделю
		3               => 'sendScheduleForThisWeek',
		'/currentweek'  => 'sendScheduleForThisWeek',
		'на эту неделю' => 'sendScheduleForThisWeek',

		// Расписание на текущую неделю
		4                     => 'sendScheduleForNextWeek',
		'/nextweek'           => 'sendScheduleForNextWeek',
		'на следующую неделю' => 'sendScheduleForNextWeek',

		// Изменение группы
		5                 => 'changeUserGroup',
		'/changegroup'    => 'changeUserGroup',
		'изменить группу' => 'changeUserGroup',

		// Изменение группы
		6               => 'askNewQuestion',
		'/askquestion'  => 'askNewQuestion',
		'задать вопрос' => 'askNewQuestion',

		// Отмена ввода
		'/cancel' => 'cancelInput',
		'отмена'  => 'cancelInput',

		// Статистика бота (для админов)
		'/stats' => 'sendStatistic'
	);

	/**
	 * Command constructor.
	 *
	 * @param string $original - Текст необработанной команды
	 * @param $payload - Информация о нажатой кнопке (если существует)
	 */
	public function __construct(string $original, $payload = null)
	{
		$this->original = $original;
		$this->payload  = $payload;
	}

	/**
	 * Инициализирует команду, производя поиск обработчика в списке доступных
	 *
	 * @param AUser|null $user - пользователь
	 * @return bool
	 */
	public function init(AUser $user = null)
	{
		// Приводим все пробелы к единичному
		$preparedCommand = preg_replace('/\s+/', ' ', mb_strtolower($this->original, 'UTF-8'));
		$preparedCommand = trim($preparedCommand);

		// Если команда найдена в списке доступных без аргументов, заканчиваем её обработку
		if (array_key_exists($preparedCommand, $this->handlerNames)) {
			$this->handlerName = $this->handlerNames[$preparedCommand];
			return true;
		}

		// Разбиваем строку по пробелам и проверяем наличие команды из первого элемента
		$preparedCommand = explode(' ', $preparedCommand);
		if ($preparedCommand !== false && array_key_exists($preparedCommand[0], $this->handlerNames)) {
			$this->handlerName = array_shift($preparedCommand);
			$this->arguments   = $preparedCommand;
			return true;
		}

		if (!is_null($user) && !is_null($user->expected_input))
			return true;

		return false;
	}
}