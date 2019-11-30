<?php

namespace Core\Vk;

use Core\Config;
use Exception;

class Api
{
	/**
	 * Конфигурация доступа к VK API
	 */
	private const VK_API_ACCESS_TOKEN = Config::VK_API_ACCESS_TOKEN;
	private const VK_API_SECRET_KEY   = Config::VK_API_SECRET_KEY;
	private const VK_API_GROUP_ID     = Config::VK_API_GROUP_ID;
	private const VK_API_ENDPOINT     = Config::VK_API_ENDPOINT;
	private const VK_API_VERSION      = Config::VK_API_VERSION;
	private const VK_API_CONFIRMATION_TOKEN = Config::VK_API_CONFIRMATION_TOKEN;

	/**
	 * Проверяет валидность переданных данных
	 *
	 * @param array $event - массив данных о событии
	 * @throws Exception
	 * @return true
	 */
	public static function checkValidity (array $event)
	{
		// Если установлен секретный ключ, проверяем его
		if (!is_null(static::VK_API_SECRET_KEY))
			if (!isset($event['secret']))
				throw new Exception ('Secret key is not passed...');
			elseif (strcmp($event['secret'], static::VK_API_SECRET_KEY) !== 0)
				throw new Exception ('Incorrect secret key of event');

		// Если не передан ID группы
		if (!isset($event['group_id']))
			throw new Exception ('Group id is not passed...');
		elseif ($event['group_id'] !== static::VK_API_GROUP_ID)
			throw new Exception ('Group id is invalid: ' . print_r($event['group_id']));

		return true;
	}

	/**
	 * Отправляет серверу VK уведомление об успешном принятии события и закрывает соединение
	 */
	public static function sendOK ()
	{
		ob_end_clean();
		echo 'ok';
	}

	/**
	 * Выводит подтверждающий владение сервером токен
	 */
	public static function sendConfirmationToken ()
	{
		ob_end_clean();
		die (static::VK_API_CONFIRMATION_TOKEN);
	}

	/**
	 * Отправка сообщения пользователю VK
	 *
	 * @param int $peerId
	 * @param string $message
	 * @param array $params
	 * @throws Exception
	 */
	public static function sendMessage (int $peerId, string $message, array $params = array())
	{
		$params = array_merge(
			array (
				'peer_id' => $peerId,
				'message' => $message,
				'dont_parse_links' => 1,
				'random_id' => random_int(1, 999999999999)
			),
			$params
		);

		static::request('messages.send', $params);
	}

	/**
	 * Выполняет POST запрос к VK API, если ответ сервера VK сообщает об ошибке - вызывает исключение
	 *
	 * @param string $method
	 * @param array $params
	 * @return bool|mixed|string
	 * @throws Exception
	 */
	public static function request (string $method, array $params = array())
	{
		$curl = curl_init(static::VK_API_ENDPOINT . $method);
		curl_setopt_array($curl, array (
			CURLOPT_POST            => TRUE,    // это именно POST запрос
			CURLOPT_RETURNTRANSFER  => TRUE,    // вернуть ответ ВК в переменную
			CURLOPT_SSL_VERIFYPEER  => FALSE,   // не проверять https сертификаты
			CURLOPT_SSL_VERIFYHOST  => FALSE,
			CURLOPT_POSTFIELDS      => array_merge (
				$params,
				array (
					'access_token' => static::VK_API_ACCESS_TOKEN,
					'v'            => static::VK_API_VERSION
				)
			)
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

		$response = json_decode($response, true);
		if (json_last_error() != JSON_ERROR_NONE)
			throw new Exception('Can`t decode response from VK server: ' . json_last_error_msg());

		if (isset($response['error']) || !isset($response['response']))
			throw new Exception($response['error']['error_msg'] . '; Response: ' . print_r($response, true));

		return $response;
	}
}