<?php


namespace Core\Bots\VK;


use Core\AViewer;
use Core\Entities\Event;
use Core\Schedule;

class VkViewer extends AViewer
{
	/**
	 * Возвращает расписание на сегодняшний день в подготовленном для вывода виде
	 *
	 * @param array $schedule
	 * @param array|null $events
	 * @return string
	 */
	public function viewToday(array $schedule, ?array $events = null): string
	{
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

		$message .= 'Группа: ' . $schedule['data']['group']['name'] . ' (' . $schedule['data']['group']['city']  . ')';
		$message .= '<br>';

		$message .= 'Дата: ' . date('d.m', time()) . '<br><br>';
		$message .= ' ---- ---- <br><br>';

		$message .= $this->viewDay($schedule['data']['schedule_days'], date('N'), $week) . '<br>';
		$message .= ' ---- ---- <br><br>';

		$message .= '🏛 Мероприятия:<br>';

		if (!is_null($events)) {
			foreach ($events as $eventKey => $event) {
				$message .= $eventKey+1 . '. ';
				$message .= (!empty($event['time']) ? substr($event['time'], 0, 5) . ' - ' : '');
				$message .= "{$event['title']}";
				$message .= (!empty($event['place'])) ? ' - (' . $event['place'] . ')' : '';
				$message .= (!empty($event['href'])) ? ' - ' . $event['href'] : '';
				$message .= '<br>';
			}
		} else $message .= 'Ничего не запланировано<br><br>';
		$message .= ' ---- ---- <br><br>';

		$message .= 'Для вывода списка команд, пришлите "Список команд" или "/help"';

		return $message;
	}

	/**
	 * Возвращает расписание на завтрашний день в подготовленном для вывода виде
	 *
	 * @param array $schedule
	 * @param array|null $events
	 * @return string
	 */
	public function viewTomorrow(array $schedule, ?array $events = null): string
	{
		$week = Schedule::getWeekName($schedule['data']['usual_time'], true);
		if ($week == 'at_numerator')
			$message = 'Завтра вы учитесь по числителю';
		else
			$message = 'Завтра вы учитесь по знаменателю';
		$message .= '<br>';

		if (date('n') > 8)
			$message .= 'Семестр: 1';
		else
			$message .= 'Семестр: 2';
		$message .= '<br>';

		$message .= 'Группа: ' . $schedule['data']['group']['name'] . ' (' . $schedule['data']['group']['city']  . ')';
		$message .= '<br>';

		$message .= 'Дата: ' . date('d.m', time()+86400) . '<br><br>';
		$message .= ' ---- ---- <br><br>';

		$message .= $this->viewDay($schedule['data']['schedule_days'], date('N', time()+86400), $week) . '<br>';
		$message .= ' ---- ---- <br><br>';

		$message .= '🏛 Мероприятия:<br>';

		if (!is_null($events)) {
			foreach ($events as $eventKey => $event) {
				$message .= $eventKey+1 . '. ';
				$message .= (!empty($event['time']) ? substr($event['time'], 0, 5) . ' - ' : '');
				$message .= "{$event['title']}";
				$message .= (!empty($event['place'])) ? ' - (' . $event['place'] . ')' : '';
				$message .= (!empty($event['href'])) ? ' - ' . $event['href'] : '';
				$message .= '<br>';
			}
			$message .= '<br>';
		} else $message .= 'Ничего не запланировано<br><br>';
		$message .= ' ---- ---- <br><br>';

		$message .= 'Для вывода списка команд, пришлите "Список команд" или "/help"';

		return $message;
	}

	/**
	 * Возвращает расписание на текущую неделю в подготовленном для вывода виде
	 *
	 * @param array $schedule
	 * @return string
	 */
	public function viewThisWeek(array $schedule, ?array $events = null): string
	{
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

		$message .= 'Группа: ' . $schedule['data']['group']['name'] . ' (' . $schedule['data']['group']['city']  . ')';
		$message .= '<br>';

		$message .= 'Дата: ' . date('d.m', time()-86400*(date('N')-1)) . ' - ' . date('d.m', time()+86400*(7-date('N'))) . '<br><br>';
		$message .= ' ---- ---- <br><br>';

		for ($weekDay = 1; $weekDay <= 7; $weekDay++) {
			$message .= $this->viewDay($schedule['data']['schedule_days'], $weekDay, $week);
			$message .= '<br><br>';
		}
		$message .= ' ---- ---- <br><br>';

		$message .= '🏛 Мероприятия:<br>';

		if (!is_null($events)) {
			$lastDate = '';
			$eventKey = 1;

			foreach ($events as $event) {
				if ($event['date'] != $lastDate) {
					$time = strtotime($event['date']);
					$message .= '<br> 📍 ' . date ('d', $time) . ' ' . $this->months[date('n', $time)] . '<br>';
					$lastDate = $event['date'];
					$eventKey = 1;
				}

				$message .= $eventKey++ . '. ';
				$message .= (!empty($event['time']) ? substr($event['time'], 0, 5) . ' - ' : '');
				$message .= "{$event['title']}";
				$message .= (!empty($event['place'])) ? ' - (' . $event['place'] . ')' : '';
				$message .= (!empty($event['href'])) ? ' - ' . $event['href'] : '';
				$message .= '<br>';
			}
			$message .= '<br>';
		} else $message .= 'Ничего не запланировано<br><br>';
		$message .= ' ---- ---- <br><br>';

		$message .= 'Для вывода списка команд, пришлите "Список команд" или "/help"';

		return $message;
	}

	/**
	 * Возвращает расписание на текущую неделю в подготовленном для вывода виде
	 *
	 * @param array $schedule
	 * @return string
	 */
	public function viewNextWeek(array $schedule, ?array $events = null): string
	{
		$week = Schedule::getWeekName($schedule['data']['usual_time'], false, true);
		if ($week == 'at_numerator')
			$message = 'Вы будете учиться по числителю';
		else
			$message = 'Вы будете учиться по знаменателю';
		$message .= '<br>';

		if (date('n') > 8)
			$message .= 'Семестр: 1';
		else
			$message .= 'Семестр: 2';
		$message .= '<br>';

		$message .= 'Группа: ' . $schedule['data']['group']['name'] . ' (' . $schedule['data']['group']['city']  . ')';
		$message .= '<br>';

		$message .= 'Дата: ' . date('d.m', time()+(7-date('N')+1)*86400) . ' - ' . date('d.m', time()+86400*(7+(7-date('N')))) . '<br><br>';
		$message .= ' ---- ---- <br><br>';

		for ($weekDay = 1; $weekDay <= 7; $weekDay++) {
			$message .= $this->viewDay($schedule['data']['schedule_days'], $weekDay, $week);
			$message .= '<br><br>';
		}
		$message .= ' ---- ---- <br><br>';

		$message .= '🏛 Мероприятия:<br>';

		if (!is_null($events)) {
			$lastDate = '';
			$eventKey = 1;

			foreach ($events as $event) {
				if ($event['date'] != $lastDate) {
					$time = strtotime($event['date']);
					$message .= '<br> 📍 ' . date ('d', $time) . ' ' . $this->months[date('n', $time)] . '<br>';
					$lastDate = $event['date'];
					$eventKey = 1;
				}

				$message .= $eventKey++ . '. ';
				$message .= (!empty($event['time']) ? substr($event['time'], 0, 5) . ' - ' : '');
				$message .= "{$event['title']}";
				$message .= (!empty($event['place'])) ? ' - (' . $event['place'] . ')' : '';
				$message .= (!empty($event['href'])) ? ' - ' . $event['href'] : '';
				$message .= '<br>';
			}
			$message .= '<br>';
		} else $message .= 'Ничего не запланировано<br><br>';
		$message .= ' ---- ---- <br><br>';

		$message .= 'Для вывода списка команд, пришлите "Список команд" или "/help"';

		return $message;
	}

	/**
	 * Возвращает расписание на день в подготовленном для вывода виде
	 *
	 * @param array $scheduleDays - массив расписания
	 * @param int $weekDay - день недели
	 * @param string $weekNum - неделя, которой выводить расписание
	 * @return string
	 */
	protected function viewDay(array $scheduleDays, int $weekDay, string $weekNum): string
	{
		$return = '';
		$return .= '📌 ' . $this->weekDays[$weekDay-1] . '<br>';

		if (!isset ($scheduleDays[$weekDay-1][$weekNum]) || empty($scheduleDays[$weekDay-1][$weekNum]))
			return $return . 'Выходной день';

		foreach ($scheduleDays[$weekDay-1][$weekNum] as $pair) {
			$return .= "[{$pair['time']['string']}]<br>";
			$personExists = false;

			if (!empty($pair['subject']))
				$return .= ' - ' . $pair['subject'];

			if (!empty($pair['prefix']))
				$return .= ' ' . $pair['prefix'];

			if (!empty($pair['person'])) {
				$return .= '<br> - ' . $pair['person'];
				$personExists = true;
			}

			if (!empty($pair['cabinet'])) {
				if ($personExists)
					$return .= ' | ' . $pair['cabinet'];
				else
					$return .= '<br> - ' . $pair['cabinet'];
			}

			$return .= '<br>';
		}

		return $return;
	}
}