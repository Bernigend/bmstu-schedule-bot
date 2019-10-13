<?php


namespace Core\Schedule;


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
	public static function searchGroup (string $groupName)
	{
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

		return false;
	}

	/**
	 * Загружает расписание группы с помощью API
	 *
	 * @param string $groupSymbolic - символическое название группы
	 * @return mixed
	 * @throws Exception
	 */
	public static function loadSchedule (string $groupSymbolic)
	{
		$scheduleJSON = static::loadData('https://b.bmstu.ru/api/schedule/' . urlencode($groupSymbolic));
		$scheduleData = json_decode($scheduleJSON, true);

		if (!$scheduleData)
			throw new Exception ('Cant`d decode JSON of search group data: ' . print_r($scheduleJSON, true));

		return $scheduleData;
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