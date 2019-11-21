<?php
/**
 * Created by PhpStorm.
 * User: Bernigend
 * Date: 12.11.2019
 * Time: 18:30
 */

namespace Core\Bots\Telegram;


use Core\AViewer;
use Core\Schedule;

class TelegramViewer extends AViewer
{
	/**
	 * Возвращает расписание на сегодняшний день в подготовленном для вывода виде
	 *
	 * @param array $schedule - массив расписания
	 * @return string
	 */
	public function viewToday(array $schedule): string
	{
		$week = Schedule::getWeekName($schedule['data']['usual_time']);
		if ($week == 'at_numerator')
			$message = 'Вы учитесь по числителю';
		else
			$message = 'Вы учитесь по знаменателю';
		$message .= "\n";

		if (date('n') > 8)
			$message .= 'Семестр: 1';
		else
			$message .= 'Семестр: 2';
		$message .= "\n";

		$message .= 'Группа: ' . $schedule['data']['group']['name'] . ' (' . $schedule['data']['group']['city']  . ')';
		$message .= "\n";

		$message .= 'Дата: *' . date('d.m', time()) . "*\n\n";
		$message .= " ---- ---- \n\n";

		$message .= $this->viewDay($schedule['data']['schedule_days'], date('N'), $week) . "\n";
		$message .= " ---- ---- \n\n";

		return $message;
	}

	/**
	 * Возвращает расписание на завтрашний день в подготовленном для вывода виде
	 *
	 * @param array $schedule - массив расписания
	 * @return string
	 */
	public function viewTomorrow(array $schedule): string
	{
		$week = Schedule::getWeekName($schedule['data']['usual_time'], true);
		if ($week == 'at_numerator')
			$message = 'Завтра вы учитесь по числителю';
		else
			$message = 'Завтра вы учитесь по знаменателю';
		$message .= "\n";

		if (date('n') > 8)
			$message .= 'Семестр: 1';
		else
			$message .= 'Семестр: 2';
		$message .= "\n";

		$message .= 'Группа: ' . $schedule['data']['group']['name'] . ' (' . $schedule['data']['group']['city']  . ')';
		$message .= "\n";

		$message .= 'Дата: *' . date('d.m', time()+86400) . "*\n\n";
		$message .= " ---- ---- \n\n";

		$message .= $this->viewDay($schedule['data']['schedule_days'], date('N', time()+86400), $week) . "\n";
		$message .= " ---- ---- \n\n";

		return $message;
	}

	/**
	 * Возвращает расписание на текущую неделю в подготовленном для вывода виде
	 *
	 * @param array $schedule - массив расписания
	 * @return string
	 */
	public function viewThisWeek(array $schedule): string
	{
		$week = Schedule::getWeekName($schedule['data']['usual_time']);
		if ($week == 'at_numerator')
			$message = 'Вы учитесь по числителю';
		else
			$message = 'Вы учитесь по знаменателю';
		$message .= "\n";

		if (date('n') > 8)
			$message .= 'Семестр: 1';
		else
			$message .= 'Семестр: 2';
		$message .= "\n";

		$message .= 'Группа: ' . $schedule['data']['group']['name'] . ' (' . $schedule['data']['group']['city']  . ')';
		$message .= "\n";

		$message .= 'Дата: *' . date('d.m', time()-86400*(date('N')-1)) . ' - ' . date('d.m', time()+86400*(7-date('N'))) . "*\n\n";
		$message .= " ---- ---- \n\n";

		for ($weekDay = 1; $weekDay <= 7; $weekDay++) {
			$message .= $this->viewDay($schedule['data']['schedule_days'], $weekDay, $week);
			$message .= "\n";
		}
		$message .= " ---- ---- \n\n";

		return $message;
	}

	/**
	 * Возвращает расписание на следующую неделю в подготовленном для вывода виде
	 *
	 * @param array $schedule - массив расписания
	 * @return string
	 */
	public function viewNextWeek(array $schedule): string
	{
		$week = Schedule::getWeekName($schedule['data']['usual_time'], false, true);
		if ($week == 'at_numerator')
			$message = 'Вы будете учиться по числителю';
		else
			$message = 'Вы будете учиться по знаменателю';
		$message .= "\n";

		if (date('n') > 8)
			$message .= 'Семестр: 1';
		else
			$message .= 'Семестр: 2';
		$message .= "\n";

		$message .= 'Группа: ' . $schedule['data']['group']['name'] . ' (' . $schedule['data']['group']['city']  . ')';
		$message .= "\n";

		$message .= 'Дата: *' . date('d.m', time()+(7-date('N')+1)*86400) . ' - ' . date('d.m', time()+86400*(7+(7-date('N')))) . "*\n\n";
		$message .= " ---- ---- \n\n";

		for ($weekDay = 1; $weekDay <= 7; $weekDay++) {
			$message .= $this->viewDay($schedule['data']['schedule_days'], $weekDay, $week);
			$message .= "\n";
		}
		$message .= " ---- ---- \n\n";

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
			$return .= "*[{$pair['time']['string']}]*\n";
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