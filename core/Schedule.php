<?php


namespace Core;


use Core\DataBase as DB;
use Exception;

class Schedule
{
	/**
	 * Делает запрос к API поиска группы
	 * Возвращает массив данных в виде:
	 * ['symbolic'] - символическое написание группы для дальнейшего запроса к API расписания
	 * ['caption']  - кирилическое написание название группы
	 * либо false, если группа не была найдена
	 *
	 * @param string $groupName - название группы по русски
	 * @return array|bool
	 * @throws Exception
	 */
	public static function searchGroup(string $groupName)
	{
		global $BOT_LOG;
		$start_time = microtime(true);

		// Делаем запрос к API
		$URI = 'https://b.bmstu.ru/api/search/' . urlencode($groupName);
		$searchJSON = static::loadData($URI);
		$searchData = json_decode($searchJSON);

		if (!$searchData)
			throw new Exception ('Cant`d decode JSON of search group data: ' . print_r($searchJSON, true));

		$searchData = $searchData->data;
		if (!isset ($searchData->count) || $searchData->count < 1)
			return false;

		foreach ($searchData->items as $item) {
			if ($item->caption == mb_strtoupper($groupName) && $item->action == 'open')
				return array (
					'symbolic' => $item->value,
					'caption'  => $item->caption
				);
		}

		if (isset($BOT_LOG)) $BOT_LOG->addToLog(" - Search group finished in " . round(microtime(true) - $start_time, 4) . " sec;\n");

		return false;
	}

	/**
	 * Загружает расписание группы с помощью API
	 *
	 * @param string $groupSymbolic - символическое название группы
	 * @return mixed
	 * @throws Exception
	 */
	public static function loadSchedule(string $groupSymbolic)
	{
		global $BOT_LOG;
		$start_time = microtime(true);

		$scheduleJSON = static::loadData('https://b.bmstu.ru/api/schedule/' . urlencode($groupSymbolic));
		$scheduleData = json_decode($scheduleJSON, true);
		if (!$scheduleData)
			throw new Exception ('Cant`d decode JSON of search group data: ' . print_r($scheduleJSON, true));

		if (isset($BOT_LOG)) $BOT_LOG->addToLog(" - Load schedule finished in " . round(microtime(true) - $start_time, 4) . " sec;\n");

		return $scheduleData;
	}

	/**
	 * Возвращает at_denominator/at_numerator, для определения по какой неделе выводить расписание
	 *
	 * @param bool $usualTime
	 * @param bool $tomorrow
	 * @param bool $nextWeek
	 * @return string
	 */
	public static function getWeekName(bool $usualTime, bool $tomorrow = false, bool $nextWeek = false): string
	{
		$year = (date('n') > 8) ? date('Y') : date('Y')-1;
		$week = strtotime("first monday of September {$year}");

		if ($tomorrow)
			$week = date('W', time()+86400) - date('W', $week) + 1;
		elseif ($nextWeek)
			$week = date('W', time()+86400*7) - date('W', $week) + 1;
		else
			$week = date('W', time()) - date('W', $week) + 1;

		// определяем чётность недели
		$week = $week % 2;
		if (!$usualTime)
			$week = (int)!$week; // меняем значение на обратное

		// определяем по какой неделе выводить расписание с помощью её чётности
		// 1 - по числителю, 2 - по знаменателю
		$week = (int)!$week + 1;

		if ($week == 1)
			$week = 'at_numerator';
		else
			$week = 'at_denominator';

		return $week;
	}

	/**
	 * Возвращает данные о событии на определённый день
	 *
	 * @param string $date - дата проведения формата YYYY-MM-DD
	 * @param string $city - город проведения
	 * @return array|null
	 * @throws Exception
	 */
	public static function getEventsForDay(string $date, string $city): ?array
	{
		$events = DB::getAll('SELECT * FROM `' . Config::DB_PREFIX . 'events` WHERE `city` = ? AND `date` = ? ORDER BY `time`', array($city, $date));
		if (!$events)
			return null;

		return $events;
	}

	/**
	 * Возвращает данные о событиях на текущую/следующую неделю
	 *
	 * @param bool $nextWeek
	 * @param string $city - город проведения
	 * @return array|null
	 * @throws Exception
	 */
	public static function getEventsForWeek(bool $nextWeek, string $city): ?array
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
			return null;

		return $events;
	}

	/**
	 * Возвращает ответ от URI
	 *
	 * @param string $URI
	 * @return bool|string
	 * @throws Exception
	 */
	protected static function loadData (string $URI)
	{
		$curl = curl_init($URI);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE); // вернуть ответ в переменную
		curl_setopt($curl, CURLOPT_TIMEOUT, 2);

		$response = curl_exec($curl);

		$curl_error_code = curl_errno($curl);
		$curl_error      = curl_error($curl);
		curl_close($curl);

		if ($curl_error || $curl_error_code) {
			$error_msg = "Failed curl request. Curl error {$curl_error_code}";
			if ($curl_error) {
				$error_msg .= ": {$curl_error}";
			}
			throw new Exception($error_msg);
		}

		return $response;
	}


}