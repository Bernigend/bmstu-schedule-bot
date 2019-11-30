<?php

require_once '../vendor/autoload.php';
use \Core\Vk\Api as VkApi;
$ids = \Core\DataBase\DB::getColsInArray('SELECT `peer_id` FROM `users`');
$idArray = array(
	1 => array()
);

$count = 90;
foreach ($ids as $id) {
	if ($count == 0) {
		$count = 90;
		$idArray[count($idArray)+1] = array ();
	}

	array_push($idArray[count($idArray)], $id);
	$count--;
}
//echo '<pre>';
//var_dump($idArray);

$message = 'Приветствую. <br> Сегодня ночью будет проведено обновление бота, после которого будут утеряны ваши текущие данные. <br><br> ❗ Для повторной регистрации Вам просто нужно будет прислать любое сообщение, а затем следовать инструкциям. <br><br> Новая версия - новые ошибки и баги, поэтому прошу проявить терпение. Все они будут исправляться. <br> С уважением, разработчик.';

foreach ($idArray as $idArrayPart) {
	$ids = implode(',', $idArrayPart);
//	VkApi::request('messages.send', array (
//		'message' => $message,
//		'dont_parse_links' => 1,
//		'random_id' => random_int(1, 999999999999),
//		'user_ids' => $ids
//	));
//	var_dump($ids);
}