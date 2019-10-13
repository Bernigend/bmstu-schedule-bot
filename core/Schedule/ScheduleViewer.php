<?php


namespace Core\Schedule;


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
		$week = (date('W')%2) ? 'at_denominator' : 'at_numerator';
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
		$week = (date('W', time()+86400)%2) ? 'at_denominator' : 'at_numerator';
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
		if ($nextWeek)
			$week = (date('W', time()+86400*7)%2) ? 'at_denominator' : 'at_numerator';
		else
			$week = (date('W')%2) ? 'at_denominator' : 'at_numerator';

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
				$return .= $pair['subject'];

			if (!empty($pair['prefix']))
				$return .= ' ' . $pair['prefix'];

			if (!empty($pair['person'])) {
				$return .= '<br>' . $pair['person'];
				$personExists = true;
			}

			if (!empty($pair['cabinet'])) {
				if ($personExists)
					$return .= ' | ' . $pair['cabinet'];
				else
					$return .= '<br>' . $pair['cabinet'];
			}

			$return .= '<br>';
		}

		return $return;
	}
}