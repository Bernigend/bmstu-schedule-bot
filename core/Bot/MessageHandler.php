<?php

namespace Core\Bot;


use Core\Schedule\Loader as ScheduleLoader;
use Core\Schedule\Schedule;
use Core\Users\User;
use Core\Vk\Api as VkApi;
use Exception;

class MessageHandler
{
	/**
	 * Пользователь из базы данных
	 *
	 * @var User
	 */
	protected $user;

	/**
	 * Данные переданного события
	 *
	 * @var array
	 */
	protected $eventObject;

	/**
	 * Допустимые команды пользователя и вызываемый метод
	 *
	 * @var array
	 */
	protected $commands = array (
		'1' => 'sendScheduleForToday',
		'насегодня' => 'sendScheduleForToday',

		'2'  => 'sendScheduleForTomorrow',
		'назавтра'  => 'sendScheduleForTomorrow',

		'3'  => 'sendScheduleForWeek',
		'нанеделю'  => 'sendScheduleForWeek',
		'наэтунеделю' => 'sendScheduleForWeek',

		'4' => 'sendScheduleForNextWeek',
		'наследующуюнеделю' => 'sendScheduleForNextWeek',

		'5' => 'changeGroup',
		'изменитьгруппу' => 'changeGroup'
	);

	/**
	 * Методы, которые следует вызывать, если от пользователя ожидается какой-то определённый ввод
	 *
	 * @var array
	 */
	protected $waiting = array (
		'group_name' => 'changeGroup'
	);

	/**
	 * Ответные сообщения
	 *
	 * @var array
	 */
	protected $answers = array (
		'first_message' => 'Вы успешно зарегестрированы в системе. Теперь пришлите код своей группы.<br>Например: К3-12Б, ЛТ1-11Б',
		'available_commands' => '<br><br> ---- ---- <br><br>Доступные команды:<br>1. На сегодня<br>2. На завтра<br>3. На эту неделю<br>4. На следующую неделю<br>5. Изменить группу<br>Можно присылать как цифрами, так и текстом',

		'send_group_name' => 'Пришлите код своей группы.<br>Например: К3-12Б, ЛТ4-11Б и др.',

		'undefined_command' => 'Неизвестная команда, попробуйте изменить свой запрос :)',
		'waiting_undefined_input' => 'Произошла ошибка... От вас ожидается непонятный системе ввод. Сообщите об этом разработчику',
		'group_not_found' => 'Группа не найдена. Проверьте её написание и попробуйте снова.<br>Например: К3-12Б, ЛТ4-11Б и др.'
	);

	/**
	 * Обработчик команд пользователя
	 *
	 * @param string|array $eventObject
	 * @throws Exception
	 */
	public function handle ($eventObject)
	{
		// Извлекаем данные о событии
		if (is_string($eventObject))
			$eventObject = json_decode ($eventObject, true);

		// Проверяем наличие данных
		if (!isset($eventObject['peer_id']))
			throw new Exception ('Peer id is not exists; Event object: ' . print_r($eventObject, true));
		elseif (!isset($eventObject['text']))
			throw new Exception ('The text of message didn`t pass; Event object: ' . print_r($eventObject, true));

		$this->eventObject = $eventObject;

		// Проверяем наличие пользователя в системе, если его нет - регистрируем
		$userId = User::findByPeerId($eventObject['peer_id']);
		if (!$userId) {
			User::register($eventObject['peer_id']);
			VkApi::sendMessage($eventObject['peer_id'], $this->answers['first_message'], array (
				'keyboard' => static::getKeyboard ()
			));
			return;
		}
		$this->user = new User($userId, $eventObject['peer_id']);
		$this->user->loadData();

		if ($this->user->data['waiting'] == '0') {
			// Ищем возможную команду пользователя
			$commandKey = static::filterCommand($eventObject['text']);
			if (array_key_exists($commandKey, $this->commands))
				$message = $this->{$this->commands[$commandKey]}();
			else
				$message = $this->answers['undefined_command'];
		} else {
			if (array_key_exists($this->user->data['waiting'], $this->waiting))
				$message = $this->{$this->waiting[$this->user->data['waiting']]}($this->user->data['waiting']);
			else
				$message = $this->answers['waiting_undefined_input'];
		}

		if ($message != $this->answers['group_not_found'] && $message != $this->answers['send_group_name'])
			$message .= $this->answers['available_commands'];

		VkApi::sendMessage($this->user->peerId, $message, array (
			'keyboard' => static::getKeyboard ()
		));
	}

	/**
	 * Изменяет группу пользователя
	 *
	 * @param string|null $waiting
	 * @return void|string
	 * @throws Exception
	 */
	protected function changeGroup (string $waiting = null)
	{
		// Если мы ждём от пользователя название группы
		if (!is_null($waiting)) {

			// Загружаем данные о группах
			$groups = ScheduleLoader::getGroups();
			$command = static::filterCommand($this->eventObject['text'], false);

			// Если группа найдена - запоминаем, иначе сообщаем пользователю
			$command = mb_strtoupper($command, 'UTF-8');
			if (array_key_exists($command, $groups)) {
				$this->user->update('group_id', $groups[$command]);
				$this->user->update('group_name', mb_strtoupper($command, 'UTF-8'));
				$this->user->update('waiting', 0);
				$message = 'Ваша группа была успешно изменена на ' . $command;
			} else $message = $this->answers['group_not_found'];

			return $message;
		}

		$this->user->update('waiting', 'group_name');
		$message = $this->answers['send_group_name'];
		return $message;
	}

	/**
	 * Отправляет пользователю расписание на сегодняшний день
	 *
	 * @return string
	 * @throws Exception
	 */
	protected function sendScheduleForToday ()
	{
		if (date('n') > 8) {
			$semester  = 1;
			$startYear = date('Y');
		} else {
			$semester  = 2;
			$startYear = (int)date('Y') - 1;
		}

		$schedule = ScheduleLoader::getScheduleGroup($this->user->data['group_id'], $semester, $startYear);

		$message  = 'Вы учитесь по ' . ((int)!(date('W')%2) + 1) . ' неделе.<br>';
		$message .= 'Семестр: ' . $semester . '<br>';
		$message .= 'Группа: ' . $this->user->data['group_name'] . '<br><br>';

		$message .= ' ---- ---- <br><br>';

		$message .= Schedule::getDay($schedule, date('N')) . '<br><br>';

		$message .= ' ---- ---- <br><br>';

		$message .= '🏛 Мероприятия:<br>';
		$message .= Schedule::getEventsForDay(date('Y.m.d'));

		return $message;
	}

	/**
	 * Отправляет пользователю расписание на завтрашний день
	 *
	 * @return string
	 * @throws Exception
	 */
	protected function sendScheduleForTomorrow ()
	{
		if (date('n', time()+86400) > 8) {
			$semester  = 1;
			$startYear = date('Y', time()+86400);
		} else {
			$semester  = 2;
			$startYear = (int)date('Y', time()+86400) - 1;
		}

		$schedule = ScheduleLoader::getScheduleGroup($this->user->data['group_id'], $semester, $startYear);

		$message  = 'Вы будете учиться по ' . ((int)!(date('W', time()+86400)%2) + 1) . ' неделе.<br>';
		$message .= 'Семестр: ' . $semester . '<br>';
		$message .= 'Группа: ' . $this->user->data['group_name'] . '<br><br>';

		$message .= ' ---- ---- <br><br>';

		$message .= Schedule::getDay($schedule, date('N', time()+86400)) . '<br><br>';

		$message .= ' ---- ---- <br><br>';

		$message .= '🏛 Мероприятия:<br>';
		$message .= Schedule::getEventsForDay(date('Y.m.d', time()+86400));

		return $message;
	}

	/**
	 * Отправляет пользователю расписание на текущую неделю
	 *
	 * @return string
	 * @throws Exception
	 */
	protected function sendScheduleForWeek ()
	{
		if (date('n') > 8) {
			$semester  = 1;
			$startYear = date('Y');
		} else {
			$semester  = 2;
			$startYear = (int)date('Y') - 1;
		}

		$schedule = ScheduleLoader::getScheduleGroup($this->user->data['group_id'], $semester, $startYear);

		$message  = 'Вы учитесь по ' . ((int)!(date('W')%2) + 1) . ' неделе.<br>';
		$message .= 'Семестр: ' . $semester . '<br>';
		$message .= 'Группа: ' . $this->user->data['group_name'] . '<br><br>';

		$message .= ' ---- ---- <br><br>';

		for ($weekDay = 1; $weekDay <= 7; $weekDay++) {
			$message .= Schedule::getDay($schedule, $weekDay) . '<br><br>';
		}

		$message .= ' ---- ---- <br><br>';

		$message .= '🏛 Мероприятия: <br>';
		$message .= Schedule::getEventsForWeek();

		return $message;
	}

	/**
	 * Отправляет пользователю расписание на текущую неделю
	 *
	 * @return string
	 * @throws Exception
	 */
	protected function sendScheduleForNextWeek ()
	{
		if (date('n') > 8) {
			$semester  = 1;
			$startYear = date('Y');
		} else {
			$semester  = 2;
			$startYear = (int)date('Y') - 1;
		}

		$schedule = ScheduleLoader::getScheduleGroup($this->user->data['group_id'], $semester, $startYear);

		$message  = 'Вы будете учиться по ' . ((int)!(date('W', time()+86400*7)%2) + 1) . ' неделе.<br>';
		$message .= 'Семестр: ' . $semester . '<br>';
		$message .= 'Группа: ' . $this->user->data['group_name'] . '<br><br>';

		$message .= ' ---- ---- <br><br>';

		for ($weekDay = 1; $weekDay <= 7; $weekDay++) {
			$message .= Schedule::getDay($schedule, $weekDay, true) . '<br><br>';
		}

		$message .= ' ---- ---- <br><br>';

		$message .= '🏛 Мероприятия: <br>';
		$message .= Schedule::getEventsForWeek(true);

		return $message;
	}

	/**
	 * Фильтрует комманды пользователя от посторонних символов
	 *
	 * @param string $command
	 * @param bool $strToLower
	 * @return string|string[]|null
	 */
	public static function filterCommand (string $command, bool $strToLower = true)
	{
		if ($strToLower) $command = mb_strtolower($command, 'UTF-8');
		return preg_replace('/\s+/', '', $command);
	}

	/**
	 * Возвращает клавиатуру бота в виде JSON строки
	 *
	 * @return false|string
	 */
	protected static function getKeyboard ()
	{
		return json_encode(
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
					)
				)
			),
			JSON_UNESCAPED_UNICODE
		);
	}
}