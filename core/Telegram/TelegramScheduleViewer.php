<?php


namespace Core\Telegram;


use Core\Schedule\ScheduleViewer;

class TelegramScheduleViewer extends ScheduleViewer
{
	protected function getDay (array $schedule, int $weekDay, string $weekNum)
	{
		$weekDays = array ('Понедельник', 'Вторник', 'Среда', 'Четверг', 'Пятница', 'Суббота', 'Воскресенье');
		$return = '';

		$return .= '📌 ' . $weekDays[$weekDay-1] . '<br>';

		if (!isset ($schedule[$weekDay-1][$weekNum]) || empty($schedule[$weekDay-1][$weekNum]))
			return $return . 'Выходной день';

		foreach ($schedule[$weekDay-1][$weekNum] as $pair) {
			$return .= "*[{$pair['time']['string']}]*<br>";
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