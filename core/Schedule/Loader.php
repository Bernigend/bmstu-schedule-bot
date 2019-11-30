<?php


namespace Core\Schedule;


use Core\DataBase\DB;
use Exception;

class Loader
{
	/**
	 * Дни недели в русском переводе
	 *
	 * @var array
	 */
	protected static $weekdays = array ('Понедельник', 'Вторник', 'Среда', 'Четверг', 'Пятница', 'Суббота', 'Воскресенье');

	/**
	 * Возвращает массив групп в виде array[<название_группы>] = <ID_группы>
	 *
	 * @return array|bool|mixed
	 * @throws Exception
	 */
	public static function getGroups ()
	{
		$groups = DB::getCol ('SELECT `groups_json` FROM `groups` ORDER BY `id` DESC LIMIT 1');
		if (!$groups)
			$groups = static::loadGroups();
		else
			$groups = json_decode($groups, true);

		return $groups;
	}

	/**
	 * Загружает информация о группах
	 *
	 * @param bool $saveToDB - нужно ли сразу сохранять в базу данных
	 * @return array
	 * @throws Exception
	 */
	protected static function loadGroups (bool $saveToDB = true)
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

		// Сохранение в базу данных
		if ($saveToDB) {
			$saveToDB = json_encode($groupsDataToSave, JSON_UNESCAPED_UNICODE);

			if (json_last_error() !== JSON_ERROR_NONE)
				throw new Exception ('Can`t encode json string of groups: ' . json_last_error_msg() . '; Groups data: ' . print_r($groupsDataToSave, true));

			DB::query('INSERT INTO `groups` SET `groups_json` = ?', array($saveToDB));
			unset ($saveToDB);
		}

		return $groupsDataToSave;
	}

	/**
	 * Возвращает расписание группы
	 *
	 * @param int $groupId
	 * @param int $semester
	 * @param int $startYear
	 * @return array|bool|mixed
	 * @throws Exception
	 */
	public static function getScheduleGroup (int $groupId, int $semester, int $startYear)
	{
		$schedule = DB::getCol('SELECT `schedule_json` FROM `group_schedule` WHERE `group_id` = ? AND `semester` = ? AND `start_year` = ? ORDER BY `id` DESC LIMIT 1', array ($groupId, $semester, $startYear));
		if (!$schedule)
			$schedule = static::loadScheduleGroup($groupId, $semester, $startYear);
		else
			$schedule = json_decode($schedule, true);

		return $schedule;
	}

	/**
	 * Загружает расписание группы по её ID
	 *
	 * @param int $groupId - ID группы, расписание короторой следует загрузить
	 * @param int $semester - номер семестра
	 * @param int $startYear - год начала обучения
	 * @param bool $saveToDB - сохранять ли загруженное расписание в базу данных
	 * @return array загруженное расписание группы в виде массива
	 * @throws Exception
	 */
	protected static function loadScheduleGroup (int $groupId, int $semester, int $startYear, bool $saveToDB = true)
	{
		// URI для получения JSON представления расписания
		$jsonUri = "https://mf.bmstu.ru/rasp/backend/index.php?num=4&groupid={$groupId}&semestr={$semester}&start={$startYear}";

		// Получаем JSON и декодируем его
		$scheduleData = static::request($jsonUri);
		if (!$scheduleData)
			throw new Exception('Невозможно подключиться к ' . $jsonUri);

		$scheduleData = json_decode($scheduleData);

		// Если JSON не смог декодироваться
		if (is_null($scheduleData))
			throw new Exception ('Cant`t decode JSON of schedule group: ' . json_last_error() .'; JSON URI: ' . $jsonUri);

		unset($jsonUri);

		// Возвращаемый массив данных расписания
		$scheduleReturn = array(
			1 => array(), // расписание по первой неделе
			2 => array()  // расписание по второй неделе
		);

		$scheduleDays = $scheduleData->day ?? array();

		// Проходим по дням недели
		foreach ($scheduleDays as $dayKey => $day) {
			$scheduleReturn[1][$dayKey+1] = static::createDay($dayKey);
			$scheduleReturn[2][$dayKey+1] = static::createDay($dayKey);

			// Если раписание на день не особое
			if ($day->type == 0 && isset($day->pair)) {
				// Краткая запись
				$lessonsWeek1 = &$scheduleReturn[1][$dayKey+1]['lessons'];
				$lessonsWeek2 = &$scheduleReturn[2][$dayKey+1]['lessons'];

				foreach ($day->pair as $pairKey => $pair) {
					// Если есть расписание по первой неделе, добавляем в первую неделю
					if (isset($pair->lesson[0]) && static::checkPair($pair, 0))
						$lessonsWeek1[$pairKey+1] = static::getPair($pair, 0);

					// Если есть расписание по второй неделе
					if (isset($pair->lesson[1])) {
						// Если оно не пустое, добавляем во вторую неделю
						if (static::checkPair($pair, 1))
							$lessonsWeek2[$pairKey+1] = static::getPair($pair, 1);
						else
							continue;
					} else {
						// Если нет расписания по второй неделе, но есть по первой - добавляем во вторую неделю
						if (isset($pair->lesson[0]) && static::checkPair($pair, 0))
							$lessonsWeek2[$pairKey+1] = static::getPair($pair, 0);
					}
				}
			} else $scheduleReturn[1][$dayKey+1]['type'] = $scheduleReturn[2][$dayKey+1]['type'] = 1;
		}

		// Если количество дней меньше 7 (т.е. заполнена не вся неделя)
		if (count($scheduleReturn[1]) < 7) {
			for ($i = count($scheduleReturn[1]) + 1; $i <= 7; $i++) {
				$scheduleReturn[1][$i] = static::createDay($i - 1);
			}
		}

		// Если количество дней меньше 7 (т.е. заполнена не вся неделя)
		if (count($scheduleReturn[2]) < 7) {
			for ($i = count($scheduleReturn[2]) + 1; $i <= 7; $i++) {
				$scheduleReturn[2][$i] = static::createDay($i - 1);
			}
		}

		// Сохраняем в базу данных
		if ($saveToDB) {
			$saveToDB = json_encode($scheduleReturn, JSON_UNESCAPED_UNICODE);

			if (json_last_error() !== JSON_ERROR_NONE)
				throw new Exception ('Can`t encode json string of schedule: ' . json_last_error_msg() . '; Schedule data: ' . print_r($scheduleReturn, true));

			DB::query ('INSERT INTO `group_schedule` SET `group_id` = ?, `semester` = ?, `start_year` = ?, `schedule_json` = ?', array($groupId, $semester, $startYear, $saveToDB));
			unset ($saveToDB);
		}

		return $scheduleReturn;
	}

	/**
	 * Проверяет данные пары, если она существует и у неё есть не пустые:
	 * название, лектор или кабинет, то возвращает true, иначе false
	 *
	 * @param object $pair - объект пары
	 * @param int $lessonWeek - номер недели
	 * @return true|false - читай описание
	 */
	protected static function checkPair (object $pair, int $lessonWeek) {
		if (!empty($pair->lesson[$lessonWeek]->obj ?? '') || !empty($pair->lesson[$lessonWeek]->lector ?? '') || !empty($pair->lesson[$lessonWeek]->aud ?? ''))
			return true;
		else
			return false;
	}

	/**
	 * Возвращает данные об учебном предмете пары в зависимости от переданной недели
	 * @param object $pair
	 * @param int $lessonWeek
	 * @return array
	 */
	protected static function getPair (object $pair, int $lessonWeek) {
		return array (
			'name'     => ((isset($pair->lesson[$lessonWeek]->obj) && !empty($pair->lesson[$lessonWeek]->obj)) ? $pair->lesson[$lessonWeek]->obj : 'н/д'),
			'lector'   => ((isset($pair->lesson[$lessonWeek]->lector) && !empty($pair->lesson[$lessonWeek]->lector)) ? $pair->lesson[$lessonWeek]->lector : 'н/д'),
			'auditory' => ((isset($pair->lesson[$lessonWeek]->aud) && !empty($pair->lesson[$lessonWeek]->aud)) ? $pair->lesson[$lessonWeek]->aud : 'н/д')
		);
	}

	/**
	 * Возвращает массив данных для нового дня
	 * @param int $weekday - номер дня недели
	 * @return array
	 */
	protected static function createDay (int $weekday)
	{
		return array (
			'lessons' => array(),
			'type'    => 0,
			'name'    => static::$weekdays[$weekday] ?? 'н/д'
		);
	}

	/**
	 * Осуществляет запрос на определённый адрес с помощью cURL
	 *
	 * @param string $URI
	 * @return bool|string
	 * @throws Exception
	 */
	protected static function request (string $URI)
	{
		$curl = curl_init($URI);
		curl_setopt_array($curl, array (
			CURLOPT_POST            => TRUE,    // это именно POST запрос
			CURLOPT_RETURNTRANSFER  => TRUE,    // вернуть ответ ВК в переменную
			CURLOPT_SSL_VERIFYPEER  => FALSE,   // не проверять https сертификаты
			CURLOPT_SSL_VERIFYHOST  => FALSE,
			CURLOPT_POSTFIELDS      => array()
		));

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