<?php

namespace Core\Bot;


use Core\Config;
use Exception;
use Core\Vk\Api as VkApi;
use Core\Queue\Queue;

class Handler
{
	/**
	 * Переданное сервером VK событие
	 *
	 * @var array
	 */
	protected $event;

	/**
	 * Handler constructor
	 *
	 * @param array $receivedEvent - массив данных переданного события
	 * @throws Exception
	 */
	public function __construct ($receivedEvent)
	{
		if (is_null($receivedEvent))
			throw new Exception ('Received Event is null');

		if (!is_array($receivedEvent))
			throw new Exception ('Undefined "type" of event: ' . gettype($receivedEvent) . '; Event: ' . print_r($receivedEvent, true));

		if (!isset($receivedEvent['type']) || !isset($receivedEvent['group_id']))
			throw new Exception ('Undefined "type" or "group_id" of event; Event: ' . print_r($receivedEvent));

		$this->event = $receivedEvent;
		unset ($receivedEvent);
	}

	/**
	 * Запускает работу бота.
	 * Проверяет валидность секретного ключа и ID группы, затем передаёт событие обработчику
	 *
	 * @throws Exception
	 */
	public function start ()
	{
		// Проверяем подлинность события
		VkApi::checkValidity ($this->event);
		// Обработчик события
		$this->eventHandler ();
	}

	/**
	 * Обработчик событий.
	 * Передаёт обработку нужному обработчику, либо добавляет задание в очередь
	 *
	 * @throws Exception
	 */
	protected function eventHandler ()
	{
		// Обрабатываем событие в соответствии с его типом
		switch ($this->event['type']) {

			// Подтверждение сервера
			case 'confirmation':
				VkApi::sendConfirmationToken ();
				break;

			// Новое сообщение
			case 'message_new':
				// Не будем заставлять сервер VK ждать ответ
				VkApi::sendOK ();
				// Если не переданы данные сообщения
				if (!isset($this->event['object']))
					throw new Exception ('Object of event did not pass; Event: ' . print_r($this->event, true));

				if (!Config::QUEUE_ON) {
					// Передаём обработку команды обработчику сообщений
					$messageHandler = new MessageHandler();
					$messageHandler->handle($this->event['object']);
				} else {
					// Отправляем на обработку в очередь
					Queue::addToQueue ($this->event, 'Core\Bot\MessageHandler', 'handle');
				}
				break;

			default:
				VkApi::sendOK ();
		}

		exit;
	}
}