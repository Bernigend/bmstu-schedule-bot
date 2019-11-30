<?php
/**
 * Created by PhpStorm.
 * User: Bernigend
 * Date: 13.11.2019
 * Time: 14:23
 */


class ScheduleLoader
{
	protected static $pathToCSVFile = '/var/www/and940/data/www/bernigend.ru/bmstu-schedule-bot/tmp/schedule.csv';
	protected static $from_h = array(1 => 8, 2 => 10, 3 => 12, 4 => 14, 5 => 16, 6 => 18, 7 => 19);
	protected static $from_m = array(1 => 40, 2 => 25, 3 => 50, 4 => 35, 5 => 20, 6 => 0, 7 => 45);
	protected static $to_h = array(1 => 10, 2 => 12, 3 => 14, 4 => 16, 5 => 17, 6 => 19, 7 => 21);
	protected static $to_m = array(1 => 15, 2 => 0, 3 => 25, 4 => 10, 5 => 55, 6 => 35, 7 => 20);

	/**
	 * Загружает информация о группах
	 *
	 * @return array
	 * @throws Exception
	 */
	public static function loadGroups()
	{
		$groupsData = static::request('https://mf.bmstu.ru/rasp/backend/index.php?num=5');
		$groupsData = json_decode($groupsData, true);
		if (json_last_error() !== JSON_ERROR_NONE)
			throw new Exception ('Can`t decode json string of groups: ' . json_last_error_msg() . '; Groups data: ' . print_r($groupsData, true));
		// Разбираем полученное JSON представление списка групп
		$groupsDataToSave = array();
		foreach ($groupsData as $faculty) {
			foreach ($faculty[2] as $group) {
				$groupsDataToSave[$group[0]] = $group[1];
			}
		}
		unset ($groupsData);
		return $groupsDataToSave;
	}

	/**
	 * Загружает расписание группы по её ID
	 *
	 * @param int $groupId - ID группы, расписание короторой следует загрузить
	 * @param int $semester - номер семестра
	 * @param int $startYear - год начала обучения
	 * @param bool $saveToDB - сохранять ли загруженное расписание в базу данных
	 * @return array|bool загруженное расписание группы в виде массива
	 * @throws Exception
	 */
	public static function loadScheduleGroup(int $groupId, int $semester, int $startYear, bool $saveToDB = true)
	{
		// URI для получения JSON представления расписания
		$jsonUri = "https://mf.bmstu.ru/rasp/backend/index.php?num=4&groupid={$groupId}&semestr={$semester}&start={$startYear}";
		// Получаем JSON и декодируем его
		$scheduleData = static::request($jsonUri);
		if (!$scheduleData)
			throw new Exception('Невозможно подключиться к ' . $jsonUri);
		$scheduleData = json_decode($scheduleData, true);
		// Если JSON не смог декодироваться
		if (is_null($scheduleData))
			return false;
		unset($jsonUri);
		// Возвращаемый массив данных расписания
		$scheduleReturn = array(); //array (array ('subject', 'cabinet', 'person', 'day_n', 'is_numerator', 'group', 'from_h', 'from_m', 'to_h', 'to_m'));
		if (!isset($scheduleData['day']))
			return false;
		foreach ($scheduleData['day'] as $dayKey => $day) {
			if ($day['type'] == 1) {
				$scheduleReturn[] = array(
					'Занятия по особому расписанию',
					'',
					'',
					$dayKey + 1,
					1,
					$scheduleData['group'],
					static::$from_h[3],
					static::$from_m[3],
					static::$to_h[3],
					static::$to_m[3]
				);
				$scheduleReturn[] = array(
					'Занятия по особому расписанию',
					'',
					'',
					$dayKey + 1,
					0,
					$scheduleData['group'],
					static::$from_h[3],
					static::$from_m[3],
					static::$to_h[3],
					static::$to_m[3]
				);
				continue;
			}
			if (!isset($day['pair']))
				continue;
			foreach ($day['pair'] as $pairKey => $pair) {
				if (!empty($pair['lesson'][0]['obj']) || !empty($pair['lesson'][0]['aud']) || !empty($pair['lesson'][0]['lector'])) {
					$scheduleReturn[] = array(
						$pair['lesson'][0]['obj'],
						$pair['lesson'][0]['aud'],
						$pair['lesson'][0]['lector'],
						$dayKey + 1,
						1,
						$scheduleData['group'],
						static::$from_h[$pair['num']],
						static::$from_m[$pair['num']],
						static::$to_h[$pair['num']],
						static::$to_m[$pair['num']]
					);
				}
				if ($pair['dbl']) {
					if (!empty($pair['lesson'][1]['obj']) || !empty($pair['lesson'][1]['aud']) || !empty($pair['lesson'][1]['lector'])) {
						$scheduleReturn[] = array(
							$pair['lesson'][1]['obj'],
							$pair['lesson'][1]['aud'],
							$pair['lesson'][1]['lector'],
							$dayKey + 1,
							0,
							$scheduleData['group'],
							static::$from_h[$pair['num']],
							static::$from_m[$pair['num']],
							static::$to_h[$pair['num']],
							static::$to_m[$pair['num']]
						);
					}
				} else {
					if (!empty($pair['lesson'][0]['obj']) || !empty($pair['lesson'][0]['aud']) || !empty($pair['lesson'][0]['lector'])) {
						$scheduleReturn[] = array(
							$pair['lesson'][0]['obj'],
							$pair['lesson'][0]['aud'],
							$pair['lesson'][0]['lector'],
							$dayKey + 1,
							0,
							$scheduleData['group'],
							static::$from_h[$pair['num']],
							static::$from_m[$pair['num']],
							static::$to_h[$pair['num']],
							static::$to_m[$pair['num']]
						);
					}
				}
			}
		}
		return $scheduleReturn;
	}

	/**
	 * Сохраняет массив данных в CSV файл
	 *
	 * @param array $data - сохраняемый массив данных
	 * @param string|null $file - путь к файлу
	 * @param string|null $delimiter - разделитель полей
	 * @return bool
	 */
	public static function saveToCSV(array $data, ?string $file = null, ?string $delimiter = ';')
	{
		if (is_null($file))
			$file = static::$pathToCSVFile;
		if (file_exists($file))
			rename($file, $file . '-old-' . time() . '.csv');
		$file = fopen($file, 'w+');
		if (!$file)
			return false;
		foreach ($data as $dataField) {
			foreach ($dataField as &$item) {
				$item = iconv("utf-8", "windows-1251", $item);
			}
			fputcsv($file, $dataField, $delimiter);
		}
		return true;
	}

	/**
	 * Осуществляет запрос на определённый адрес с помощью cURL
	 *
	 * @param string $URI
	 * @return bool|string
	 * @throws Exception
	 */
	protected static function request(string $URI)
	{
		$curl = curl_init($URI);
		curl_setopt_array($curl, array(
			CURLOPT_POST => TRUE,    // это именно POST запрос
			CURLOPT_RETURNTRANSFER => TRUE,    // вернуть ответ ВК в переменную
			CURLOPT_SSL_VERIFYPEER => FALSE,   // не проверять https сертификаты
			CURLOPT_SSL_VERIFYHOST => FALSE,
			CURLOPT_POSTFIELDS => array()
		));
		$response = curl_exec($curl);
		$curl_error_code = curl_errno($curl);
		$curl_error = curl_error($curl);
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

// Данные для сохранения
$dataToSave = array(array('subject', 'cabinet', 'person', 'day_n', 'is_numerator', 'group', 'from_h', 'from_m', 'to_h', 'to_m'));
// Получаем список всех групп
$groups = ScheduleLoader::loadGroups();
// Обрабатываем данные
foreach ($groups as $groupName => $groupID) {
	$schedule = ScheduleLoader::loadScheduleGroup($groupID, 1, 2019);
	if (!$schedule)
		continue;
	foreach ($schedule as $item) {
		$dataToSave[] = $item;
	}
}
echo '<pre>';
var_dump($dataToSave);
// Сохраняем всё в файл
ScheduleLoader::saveToCSV($dataToSave);