<?php

namespace Core\Queue;



use Core\Config;
use Core\DataBase\DB;
use Exception;

class Queue
{
	/**
	 * Добавляет задачу в очередь и уведомляет об этом работника
	 *
	 * @param array $event
	 * @param string $className
	 * @param string $methodName
	 * @throws Exception
	 */
	public static function addToQueue (array $event, string $className, string $methodName)
	{
		// Подключение к базе данных
		DB::connect ();
		// Подготавливаем данные к сохранению
		$event['object'] = json_encode ($event['object'], JSON_UNESCAPED_UNICODE);
		if (json_last_error() !== JSON_ERROR_NONE)
			throw new Exception('Can`t encode event object to JSON: ' . json_last_error() . '; Event object: ' . print_r($event['object'], true));
		// Добавляем в очередь на исполнение
		DB::query ('INSERT INTO `queue` SET `event_type` = ?, `event_data` = ?, `handler_class_name` = ?, `handler_method_name` = ?, `created` = current_timestamp()', array($event['type'], $event['object'], $className, $methodName));
		// Уведомляем работника о новой задаче
		static::sendRequestToWorker();
		return;
	}

	/**
	 * Отправляет уведомление работнику о новой задаче
	 *
	 * @throws Exception
	 */
	public static function sendRequestToWorker ()
	{
		$curl = curl_init();
		if (!$curl)
			throw new Exception('Can`t init cURL');

		curl_setopt($curl, CURLOPT_URL, Config::WORKER_HANDLER_URI);
		curl_setopt($curl, CURLOPT_POST, TRUE);
		curl_setopt($curl, CURLOPT_POSTFIELDS, array (
			'requestToWorker' => 1
		));

		curl_setopt($curl, CURLOPT_USERAGENT, 'api');

		curl_setopt($curl, CURLOPT_TIMEOUT, 1);
		curl_setopt($curl, CURLOPT_HEADER, 0);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, false);
		curl_setopt($curl, CURLOPT_FORBID_REUSE, true);
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 1);
		curl_setopt($curl, CURLOPT_DNS_CACHE_TIMEOUT, 10);

		curl_setopt($curl, CURLOPT_FRESH_CONNECT, true);

		if (!curl_exec($curl))
			throw new Exception('Curl error: ' . curl_error($curl));

		curl_close($curl);

		return;
	}
}