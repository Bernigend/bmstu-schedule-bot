<?php
/**
 * Created by PhpStorm.
 * User: Bernigend
 * Date: 15.12.2019
 * Time: 17:41
 */

use Core\Config;
use Core\DataBase;
use VK\Client\VKApiClient;

require_once '../vendor/autoload.php';

// Получаем нужные идентификаторы назначения
$users = DataBase::getAll("SELECT `peer_id` FROM `users_vk` WHERE `group_symbolic` LIKE 'k%'");
$allPeerIds = array(
	0 => array()
);

// Разбиваем их на группы по 100 единиц
$count = 100;
foreach ($users as $user) {
	if ($count == 0) {
		$count = 100;
		$allPeerIds[count($allPeerIds)] = array();
	}

	array_push($allPeerIds[count($allPeerIds)-1], $user['peer_id']);
	$count--;
}
unset($users);

$message = "Исправил вывод даты, теперь это не 15 декабря. Всем успешно закрытой сессии :D";
$vkApiClient = new VKApiClient(Config::VK_API_DEFAULT_VERSION);

// Отправляем сообщения группами по 100
foreach ($allPeerIds as $peerIds) {
	$ids = implode(',', $peerIds);
//	$vkApiClient->messages()->send(Config::VK_GROUPS['id186394025']['access_token'], array (
//		'message' => $message,
//		'dont_parse_links' => 1,
//		'random_id' => random_int(1, 999999999999),
//		'user_ids' => $ids
//	));
}