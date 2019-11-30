<?php


namespace Core\Queue;



use Core\DataBase\DB;
use Exception;

class Worker
{
	/**
	 * Количество задач на одного работника
	 *
	 * @var int
	 */
	protected static $limit = 10;

	/**
	 * Запускает работника.
	 * Получает из очереди задания на обработку и выполняет их, передав соответствующим обработчикам
	 *
	 * @throws Exception
	 */
	public static function start ()
	{
		// Подключаемся к базе данных
		DB::connect ();

		// Берём задачи на выполнение (максимум 10)
		$tasks = DB::getColsInArray ('SELECT `id` FROM `queue` WHERE `status` = 0 LIMIT ' . static::$limit);
		if (!$tasks)
			return;

		$tasksId = array (
			'array'  => $tasks,
			'string' => implode (',', $tasks)
		);

		try {
			// Ставим блокировку на выбранные задачи
			DB::query ('UPDATE `queue` SET `status` = 1 WHERE `id` IN (' . $tasksId['string'] . ')');

			// Проходим по каждой задаче
			$tasks = DB::query ('SELECT * FROM `queue` WHERE `id` IN ('. $tasksId['string'] .') ');
			while ($task = $tasks->fetch()) {
				if (!class_exists($task['handler_class_name']))
					throw new Exception ('Handler class is not exists: ' . $task['handler_class_name']);

				// Инициализируем обработчик
				$handler = new $task['handler_class_name']();

				if (!method_exists($handler, $task['handler_method_name']))
					throw new Exception ('Method of handler class is not exists: ' . $task['handler_class_name'] . '->' . $task['handler_method_name'] . '()');

				// Передаём управление обработчику
				$handler->{$task['handler_method_name']}($task['event_data']);

				// Удаляем задачу из очереди
				DB::query('DELETE FROM `queue` WHERE `id` = ' . $task['id']);
			}

		} catch (Exception $exception) {

			// Если существует переменная с заданием, то ошибка произошла во время её выполнения
			// Поэтому отмечаем её статусом ошибки
			if (isset($task)) {
				DB::query ('UPDATE `queue` SET `status` = 2 WHERE `id` = ' . $task['id']);
				unset ($tasksId['array'][array_search($task['id'], $tasksId['array'])]);
				$tasksId['string'] = implode(',', $tasksId['array']);
			}
			DB::query ('UPDATE `queue` SET `status` = 0 WHERE `id` IN ('. $tasksId['string'] .')');
			// Перебрасываем ошибку дальше
			throw new Exception ($exception->getMessage());
		}

		// Отправляем запрос на обработку остальных задач, если они есть
		if (DB::getCol('SELECT COUNT(*) FROM `queue` LIMIT 1') > 0)
			Queue::sendRequestToWorker();
	}
}