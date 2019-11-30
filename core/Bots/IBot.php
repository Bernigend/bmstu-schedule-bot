<?php
/**
 * Created by PhpStorm.
 * User: Bernigend
 * Date: 25.11.2019
 * Time: 12:49
 */

namespace Core\Bots;


interface IBot
{
	/**
	 * Отправляет ответное сообщение пользователю
	 *
	 * @param $destinationID - идентификатор назначения (куда/кому отправлять ответное сообщение)
	 * @param $message - текст сообщения
	 * @param $keyboardType - тип клавиатуры
	 * @return bool
	 */
	public function sendMessage($destinationID, $message, $keyboardType = null): bool;
}