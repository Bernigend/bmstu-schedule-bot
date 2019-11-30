<?php


namespace Core\Bots\VK;


use Core\ACommandHandler;
use Core\Entities\Command;
use Core\Entities\VkUser;
use VK\Exceptions\Api\VKApiMessagesCantFwdException;
use VK\Exceptions\Api\VKApiMessagesChatBotFeatureException;
use VK\Exceptions\Api\VKApiMessagesChatUserNoAccessException;
use VK\Exceptions\Api\VKApiMessagesContactNotFoundException;
use VK\Exceptions\Api\VKApiMessagesDenySendException;
use VK\Exceptions\Api\VKApiMessagesKeyboardInvalidException;
use VK\Exceptions\Api\VKApiMessagesPrivacyException;
use VK\Exceptions\Api\VKApiMessagesTooLongForwardsException;
use VK\Exceptions\Api\VKApiMessagesTooLongMessageException;
use VK\Exceptions\Api\VKApiMessagesTooManyPostsException;
use VK\Exceptions\Api\VKApiMessagesUserBlockedException;
use VK\Exceptions\VKApiException;
use VK\Exceptions\VKClientException;

class VkCommandHandler extends ACommandHandler
{
	/**
	 * @var VkBot
	 */
	protected $bot;

	/**
	 * VkCommandHandler constructor.
	 *
	 * @param VkBot $bot
	 * @param Command $command
	 * @param VkUser $user
	 * @param VkViewer $viewer
	 */
	public function __construct(VkBot $bot, Command $command, VkUser $user, VkViewer $viewer)
	{
		parent::__construct($bot, $command, $user, $viewer);
	}

	/**
	 * Обработчик ввода текста вопроса
	 *
	 * @return void
	 * @throws VKApiMessagesCantFwdException
	 * @throws VKApiMessagesChatBotFeatureException
	 * @throws VKApiMessagesChatUserNoAccessException
	 * @throws VKApiMessagesContactNotFoundException
	 * @throws VKApiMessagesDenySendException
	 * @throws VKApiMessagesKeyboardInvalidException
	 * @throws VKApiMessagesPrivacyException
	 * @throws VKApiMessagesTooLongForwardsException
	 * @throws VKApiMessagesTooLongMessageException
	 * @throws VKApiMessagesTooManyPostsException
	 * @throws VKApiMessagesUserBlockedException
	 * @throws VKApiException
	 * @throws VKClientException
	 */
	protected function inputQuestionText(): void
	{
		// Получаем информацию о пользователе
		$userInfo = $this->bot->vkApiClient->users()->get($this->bot->config['access_token'], array (
			'user_ids' => $this->user->destinationID,
			'fields' => 'first_name', 'last_name'
		));

		$message  = "⚠ Новый вопрос от {$userInfo[0]['first_name']} {$userInfo[0]['last_name']} @id{$this->user->destinationID} [VK]\n\n";
		$message .= "Вопрос:\n'{$this->command->original}'";

		// Отправляем уведомление в беседу разработчиков
		$this->bot->sendMessage($this->bot->config['developers_talk_peer_id'], $message, 'hidden');
		$this->user->update('expected_input', null);
		$this->bot->sendMessage($this->user->destinationID, static::$answers['question_successfully_sent'], 'full');
		return;
	}
}