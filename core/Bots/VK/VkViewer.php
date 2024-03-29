<?php


namespace Core\Bots\VK;


use Core\AViewer;
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

		$message  = '📅 Дата: ' . date('d.m', time());
		if ($week == 'at_numerator')
			$message .= ' (числитель)';
		else
			$message .= ' (знаменатель)';
		$message .= "\n";

		$message .= 'Группа: ' . $schedule['data']['group']['name'] . ' (' . $schedule['data']['group']['city']  . ')';
		$message .= "\n";

		if (date('n') > 8)
			$message .= 'Семестр: 1';
		else
			$message .= 'Семестр: 2';
		$message .= "\n\n ---- ---- \n\n";

		$message .= $this->viewDay($schedule['data']['schedule_days'], date('N'), $week) . "\n";
		$message .= " ---- ---- \n\n";

		$message .= "🏛 Мероприятия:\n";

		if (!is_null($events)) {
			foreach ($events as $eventKey => $event) {
				$message .= $eventKey+1 . '. ';
				$message .= (!empty($event['time']) ? substr($event['time'], 0, 5) . ' - ' : '');
				$message .= "{$event['title']}";
				$message .= (!empty($event['place'])) ? ' - (' . $event['place'] . ')' : '';
				$message .= (!empty($event['href'])) ? ' - ' . $event['href'] : '';
				$message .= "\n";
			}
		} else $message .= "Ничего не запланировано\n\n";
		$message .= " ---- ---- \n\n";

		$message .= 'Для вывода списка команд пришлите "Список команд" или "/help"';

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

		$message  = '📅 Дата: ' . date('d.m', time()+86400);
		if ($week == 'at_numerator')
			$message .= ' (числитель)';
		else
			$message .= ' (знаменатель)';
		$message .= "\n";

		$message .= 'Группа: ' . $schedule['data']['group']['name'] . ' (' . $schedule['data']['group']['city']  . ')';
		$message .= "\n";

		if (date('n') > 8)
			$message .= 'Семестр: 1';
		else
			$message .= 'Семестр: 2';
		$message .= "\n\n ---- ---- \n\n";

		$message .= $this->viewDay($schedule['data']['schedule_days'], date('N', time()+86400), $week) . "\n";
		$message .= " ---- ---- \n\n";

		$message .= "🏛 Мероприятия:\n";

		if (!is_null($events)) {
			foreach ($events as $eventKey => $event) {
				$message .= $eventKey+1 . '. ';
				$message .= (!empty($event['time']) ? substr($event['time'], 0, 5) . ' - ' : '');
				$message .= "{$event['title']}";
				$message .= (!empty($event['place'])) ? ' - (' . $event['place'] . ')' : '';
				$message .= (!empty($event['href'])) ? ' - ' . $event['href'] : '';
				$message .= "\n";
			}
			$message .= "\n";
		} else $message .= "Ничего не запланировано\n\n";
		$message .= " ---- ---- \n\n";

		$message .= 'Для вывода списка команд пришлите "Список команд" или "/help"';

		return $message;
	}

	/**
	 * Возвращает расписание на текущую неделю в подготовленном для вывода виде
	 *
	 * @param array $schedule
	 * @param array|null $events
	 * @return string
	 */
	public function viewThisWeek(array $schedule, ?array $events = null): string
	{
		$week = Schedule::getWeekName($schedule['data']['usual_time']);

		$message  = '📅 Дата: ' . date('d.m', time()-86400*(date('N')-1)) . ' - ' . date('d.m', time()+86400*(7-date('N')));
		if ($week == 'at_numerator')
			$message .= ' (числитель)';
		else
			$message .= ' (знаменатель)';
		$message .= "\n";

		$message .= 'Группа: ' . $schedule['data']['group']['name'] . ' (' . $schedule['data']['group']['city']  . ')';
		$message .= "\n";

		if (date('n') > 8)
			$message .= 'Семестр: 1';
		else
			$message .= 'Семестр: 2';
		$message .= "\n\n ---- ---- \n\n";

		for ($weekDay = 1; $weekDay <= 7; $weekDay++) {
			$message .= $this->viewDay($schedule['data']['schedule_days'], $weekDay, $week);
			$message .= "\n\n";
		}
		$message .= " ---- ---- \n\n";

		$message .= "🏛 Мероприятия:\n";

		if (!is_null($events)) {
			$lastDate = '';
			$eventKey = 1;

			foreach ($events as $event) {
				if ($event['date'] != $lastDate) {
					$time = strtotime($event['date']);
					$message .= "\n 📍 " . date ('d', $time) . ' ' . $this->months[date('n', $time)] . "\n";
					$lastDate = $event['date'];
					$eventKey = 1;
				}

				$message .= $eventKey++ . '. ';
				$message .= (!empty($event['time']) ? substr($event['time'], 0, 5) . ' - ' : '');
				$message .= "{$event['title']}";
				$message .= (!empty($event['place'])) ? ' - (' . $event['place'] . ')' : '';
				$message .= (!empty($event['href'])) ? ' - ' . $event['href'] : '';
				$message .= "\n";
			}
			$message .= "\n";
		} else $message .= "Ничего не запланировано\n\n";
		$message .= " ---- ---- \n\n";

		$message .= 'Для вывода списка команд пришлите "Список команд" или "/help"';

		return $message;
	}

	/**
	 * Возвращает расписание на текущую неделю в подготовленном для вывода виде
	 *
	 * @param array $schedule
	 * @param array|null $events
	 * @return string
	 */
	public function viewNextWeek(array $schedule, ?array $events = null): string
	{
		$week = Schedule::getWeekName($schedule['data']['usual_time'], false, true);

		$message  = '📅 Дата: ' . date('d.m', time()+(7-date('N')+1)*86400) . ' - ' . date('d.m', time()+86400*(7+(7-date('N'))));
		if ($week == 'at_numerator')
			$message .= ' (числитель)';
		else
			$message .= ' (знаменатель)';
		$message .= "\n";

		$message .= 'Группа: ' . $schedule['data']['group']['name'] . ' (' . $schedule['data']['group']['city']  . ')';
		$message .= "\n";

		if (date('n') > 8)
			$message .= 'Семестр: 1';
		else
			$message .= 'Семестр: 2';
		$message .= "\n\n ---- ---- \n\n";

		for ($weekDay = 1; $weekDay <= 7; $weekDay++) {
			$message .= $this->viewDay($schedule['data']['schedule_days'], $weekDay, $week);
			$message .= "\n\n";
		}
		$message .= " ---- ---- \n\n";

		$message .= "🏛 Мероприятия:\n";

		if (!is_null($events)) {
			$lastDate = '';
			$eventKey = 1;

			foreach ($events as $event) {
				if ($event['date'] != $lastDate) {
					$time = strtotime($event['date']);
					$message .= "\n 📍 " . date ('d', $time) . ' ' . $this->months[date('n', $time)] . "\n";
					$lastDate = $event['date'];
					$eventKey = 1;
				}

				$message .= $eventKey++ . '. ';
				$message .= (!empty($event['time']) ? substr($event['time'], 0, 5) . ' - ' : '');
				$message .= "{$event['title']}";
				$message .= (!empty($event['place'])) ? ' - (' . $event['place'] . ')' : '';
				$message .= (!empty($event['href'])) ? ' - ' . $event['href'] : '';
				$message .= "\n";
			}
			$message .= "\n";
		} else $message .= "Ничего не запланировано\n\n";
		$message .= " ---- ---- \n\n";

		$message .= 'Для вывода списка команд пришлите "Список команд" или "/help"';

		return $message;
	}

	/**
	 * Возвращает расписание экзаменов в подготовленном для вывода виде
	 *
	 * @param array $exams - массив с данными об экзаменах
	 * @param string|null $groupName - название группы
	 * @return string
	 */
	public function viewExams(array $exams, string $groupName = null): string
	{
		$message = "Расписание экзаменов:\n\n";

		if (!is_null($groupName))
			$message .= "Группа: {$groupName}\n\n";

		foreach ($exams as $exam) {
			$time = explode('-', $exam['date']);
			$message .= " 📌 {$exam['subject']}\n";
			$message .= " - " . $time[2] . " " . ($this->months[(int)$time[1]] ?? 'числа') . ", {$exam['time']}\n";
			$message .= " - {$exam['person']}";
			$message .= (empty($exam['cabinet']) ? '' : ', ' . $exam['cabinet']) . "\n";
			$message .= "\n";
		}

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
		$return .= '📌 ' . $this->weekDays[$weekDay-1] . "\n";

		if (!isset ($scheduleDays[$weekDay-1][$weekNum]) || empty($scheduleDays[$weekDay-1][$weekNum]))
			return $return . "Выходной день\n";

		foreach ($scheduleDays[$weekDay-1][$weekNum] as $pair) {
			$return .= "[{$pair['time']['string']}]\n";
			$personExists = false;

			if (!empty($pair['subject']))
				$return .= ' - ' . $pair['subject'];

			if (!empty($pair['prefix']))
				$return .= ' ' . $pair['prefix'];

			if (!empty($pair['person'])) {
				$return .= "\n - " . $pair['person'];
				$personExists = true;
			}

			if (!empty($pair['cabinet'])) {
				if ($personExists)
					$return .= ' | ' . $pair['cabinet'];
				else
					$return .= "\n - " . $pair['cabinet'];
			}

			$return .= "\n";
		}

		return $return;
	}
}