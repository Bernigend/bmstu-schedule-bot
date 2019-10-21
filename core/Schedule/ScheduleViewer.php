<?php


namespace Core\Schedule;


use Core\Config;
use Core\DataBase as DB;
use Exception;

class ScheduleViewer
{
	/**
	 * Возвращает расписание на сегодня
	 *
	 * @param array $schedule
	 * @return string
	 */
	public function getToday (array $schedule)
	{
		$week = Schedule::getWeekName($schedule['data']['usual_time']);
		return $this->getDay($schedule['data']['schedule_days'], date('N'), $week);
	}

	/**
	 * Возвращает расписание на завтра
	 *
	 * @param array $schedule
	 * @return string
	 */
	public function getTomorrow (array $schedule)
	{
		$week = Schedule::getWeekName($schedule['data']['usual_time']);
		return $this->getDay($schedule['data']['schedule_days'], date('N', time()+86400), $week);
	}

	/**
	 * Возвращает расписание на текущую/следующую неделю
	 *
	 * @param array $schedule
	 * @param bool $nextWeek
	 * @return string
	 */
	public function getWeek (array $schedule, bool $nextWeek = false)
	{
		$week = Schedule::getWeekName($schedule['data']['usual_time'], $nextWeek);

		$return = '';

		for ($weekDay = 1; $weekDay <= 7; $weekDay++) {
			$return .= $this->getDay($schedule['data']['schedule_days'], $weekDay, $week);
			$return .= '<br><br>';
		}

		return $return;
	}

	/**
	 * Возвращает расписание на день
	 *
	 * @param array $schedule
	 * @param int $weekDay
	 * @param string $weekNum
	 * @return string
	 */
	protected function getDay (array $schedule, int $weekDay, string $weekNum)
	{
		$weekDays = array ('Понедельник', 'Вторник', 'Среда', 'Четверг', 'Пятница', 'Суббота', 'Воскресенье');
		$return = '';

		$return .= '📌 ' . $weekDays[$weekDay-1] . '<br>';

		if (!isset ($schedule[$weekDay-1][$weekNum]) || empty($schedule[$weekDay-1][$weekNum]))
			return $return . 'Выходной день';

		foreach ($schedule[$weekDay-1][$weekNum] as $pair) {
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

	/**
	 * Возвращает расписание мероприятий на день по дате
	 *
	 * @param string $date
	 * @param string $city
	 * @return string
	 * @throws Exception
	 */
	public function getEventsForDay (string $date, string $city = 'Moscow') : string
	{
		$events = DB::getAll('SELECT * FROM `' . Config::DB_PREFIX . 'events` WHERE `city` = ? AND `date` = ? ORDER BY `time`', array($city, $date));
		$return = '<br>';
		if ($events) {
			foreach ($events as $eventKey => $event) {
				$return .= $eventKey+1 . '. ';
				$return .= (!empty($event['time']) ? substr($event['time'], 0, 5) . ' - ' : '');
				$return .= "{$event['title']}";
				$return .= (!empty($event['place'])) ? ' - (' . $event['place'] . ')' : '';
				//$return .= (!empty($event['href'])) ? ' - ' . $event['href'] : '';
				$return .= '<br>';
			}
			return $return;
		} else return 'Ничего не запланировано';
	}

	/**
	 * Возвращает расписание мероприятий на текущую/следующую неделю
	 *
	 * @param bool $nextWeek
	 * @param string $city
	 * @return string
	 * @throws Exception
	 */
	public function getEventsForWeek (bool $nextWeek = false, string $city = 'Moscow') : string
	{
		if (!$nextWeek)
			$dateStart = date('Y.m.d', time()-86400*(date('N')-1));
		else
			$dateStart = date('Y.m.d', time()+(7-date('N')+1)*86400);

		if (!$nextWeek)
			$dateEnd = date('Y.m.d', time()+86400*(7-date('N')));
		else
			$dateEnd = date('Y.m.d', time()+86400*(7+(7-date('N'))));

		$events = DB::getAll('SELECT * FROM `' . Config::DB_PREFIX . 'events` WHERE `city` = ? AND `date` >= ? AND `date` <= ? ORDER BY `date`, `time`', array ($city, $dateStart, $dateEnd));
		if (!$events)
			return 'Ничего не запланировано';

		$moths = array (1 => 'января', 2 => 'февраля', 3 => 'марта', 4 => 'апреля', 5 => 'мая', 6 => 'июня', 7 => 'июля', 8 => 'августа', 9 => 'сентября', 10 => 'октября', 11 => 'ноября', 12 => 'декабря');
		$return = '';
		$lastDate = '';
		$eventKey = 1;

		foreach ($events as $event) {
			if ($event['date'] != $lastDate) {
				$time = strtotime($event['date']);
				$return .= '<br> 📍 ' . date ('d', $time) . ' ' . $moths[date('n', $time)] . '<br>';
				$lastDate = $event['date'];
				$eventKey = 1;
			}
			$return .= $eventKey++ . '. ';
			$return .= (!empty($event['time']) ? substr($event['time'], 0, 5) . ' - ' : '');
			$return .= "{$event['title']}";
			$return .= (!empty($event['place'])) ? ' - (' . $event['place'] . ')' : '';
			$return .= (!empty($event['href'])) ? ' - ' . $event['href'] : '';
			$return .= '<br>';
		}

		return $return;
	}
}