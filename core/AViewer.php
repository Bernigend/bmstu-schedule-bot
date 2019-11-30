<?php


namespace Core;


abstract class AViewer
{
	/**
	 * Дни недели
	 * @var array
	 */
	protected $weekDays = array ('Понедельник', 'Вторник', 'Среда', 'Четверг', 'Пятница', 'Суббота', 'Воскресенье');

	/**
	 * Месяцы
	 * @var array
	 */
	protected $months = array (1 => 'января', 2 => 'февраля', 3 => 'марта', 4 => 'апреля', 5 => 'мая', 6 => 'июня', 7 => 'июля', 8 => 'августа', 9 => 'сентября', 10 => 'октября', 11 => 'ноября', 12 => 'декабря');

	/**
	 * Возвращает расписание на сегодняшний день в подготовленном для вывода виде
	 *
	 * @param array $schedule - массив расписания
	 * @param array|null $events - массив данных о событиях из БД
	 * @return string
	 */
	public abstract function viewToday(array $schedule, ?array $events = null): string;

	/**
	 * Возвращает расписание на завтрашний день в подготовленном для вывода виде
	 *
	 * @param array $schedule - массив расписания
	 * @param array|null $events
	 * @return string
	 */
	public abstract function viewTomorrow(array $schedule, ?array $events = null): string;

	/**
	 * Возвращает расписание на текущую неделю в подготовленном для вывода виде
	 *
	 * @param array $schedule - массив расписания
	 * @param array|null $events
	 * @return string
	 */
	public abstract function viewThisWeek(array $schedule, ?array $events = null): string;

	/**
	 * Возвращает расписание на следующую неделю в подготовленном для вывода виде
	 *
	 * @param array $schedule - массив расписания
	 * @param array|null $events
	 * @return string
	 */
	public abstract function viewNextWeek(array $schedule, ?array $events = null): string;
}