<?php


namespace Core\Schedule;


use Core\DataBase\DB;
use Exception;

class Schedule
{
	/**
	 * Возвращает форматированное расписание на день
	 *
	 * @param array $schedule
	 * @param int $weekDay
	 * @param bool $nextWeek
	 * @return string
	 * @throws Exception
	 */
	public static function getDay (array $schedule, int $weekDay, bool $nextWeek = false)
	{
		$times = array ('8:40 - 10:15', '10:25 - 12:00', '12:50 - 14:25', '14:35 - 16:10', '16:20 - 17:55', '18:00 - 19:35', '19:45 - 21:20');
		$returnDay = '';

		// Определяем номер недели по её чётности
		if ($nextWeek)
			$week = (int)!(date('W', time()+86400*7)%2) + 1;
		elseif (!$nextWeek && $weekDay < date('N'))
			$week = (int)!(date('W', time()+86400)%2) + 1;
		else
			$week = (int)!(date('W')%2) + 1;

		if (!isset($schedule[$week]))
			throw new Exception('В расписании отсутствуют данные о ' . $week . ' неделе! Расписание: ' . print_r($schedule, true));

		if (!isset($schedule[$week][$weekDay]))
			throw new Exception('В расписании отсутствуют данные о ' . $weekDay . ' дне недели по ' . $week . ' неделе! Расписание: ' . print_r($schedule, true));

		$returnDay .= " 📌 {$schedule[$week][$weekDay]['name']}<br>";
		if ($schedule[$week][$weekDay]['type'] == 0) {
			if (isset($schedule[$week][$weekDay]['lessons']) && !empty($schedule[$week][$weekDay]['lessons'])) {
				foreach ($schedule[$week][$weekDay]['lessons'] as $pairKey => $pair) {
					$returnDay .= "[{$pairKey}. {$times[$pairKey-1]}]<br>";
					$returnDay .= "{$pair['name']} | {$pair['lector']} | {$pair['auditory']}<br>";
				}
			} else $returnDay .= 'Выходной день';
		} else $returnDay .= 'Занятия по особому расписанию';

		return $returnDay;
	}

	/**
	 * Возвращает расписание мероприятий на день по дате
	 *
	 * @param string $date
	 * @return string
	 * @throws Exception
	 */
	public static function getEventsForDay (string $date)
	{
		$events = DB::getAll('SELECT * FROM `events` WHERE `date` = ? ORDER BY `time`', array($date));
		$return = '';
		if ($events) {
			foreach ($events as $eventKey => $event) {
				$return .= $eventKey+1 . '. ';
				$return .= substr($event['time'], 0, -3) . ' - ';
				$return .= "{$event['title']} - {$event['href']} <br>";
			}
			return $return;
		} else return 'Ничего не запланировано';
	}

	/**
	 * Возвращает расписание мероприятий на текущую/следующую неделю
	 *
	 * @param bool $nextWeek
	 * @return string
	 * @throws Exception
	 */
	public static function getEventsForWeek (bool $nextWeek = false)
	{
		if (!$nextWeek)
			$dateStart = date('Y.m.d', time()-86400*(date('N')-1));
		else
			$dateStart = date('Y.m.d', time()+(7-date('N')+1)*86400);

		if (!$nextWeek)
			$dateEnd = date('Y.m.d', time()+86400*(7-date('N')));
		else
			$dateEnd = date('Y.m.d', time()+86400*(7+(7-date('N'))));

		$events = DB::getAll('SELECT * FROM `events` WHERE `date` >= ? AND `date` <= ? ORDER BY `date`, `time`', array ($dateStart, $dateEnd));
		if (!$events)
			return 'Ничего не запланировано';

		$moths = array (1 => 'января', 2 => 'февраля', 3 => 'марта', 4 => 'апреля', 5 => 'мая', 6 => 'июня', 7 => 'июля', 8 => 'августа', 9 => 'сентября', 10 => 'октября', 11 => 'ноября', 12 => 'декабря');

		$return = '';
		$lastDate = '';
		$eventKey = 1;

		foreach ($events as $event) {
			if ($event['date'] != $lastDate) {
				$time = strtotime($event['date']);
				$return .= '<br> -- ' . date ('d', $time) . ' ' . $moths[date('n', $time)] . ' --<br>';
				$lastDate = $event['date'];
				$eventKey = 1;
			}

			$return .= $eventKey++ . '. ';
			$return .= substr($event['time'], 0, -3) . ' - ';
			$return .= "{$event['title']} - {$event['href']} <br>";
		}

		return $return;
	}
}