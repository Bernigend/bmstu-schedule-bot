<?php
/**
 * Created by PhpStorm.
 * User: Bernigend
 * Date: 12.11.2019
 * Time: 15:45
 */

namespace Core\Bots\Telegram;


use Core\ACommandHandler;
use Core\Entities\Command;
use Core\Entities\TelegramUser;

class TelegramCommandHandler extends ACommandHandler
{
	/**
	 * @var TelegramBot
	 */
	protected $bot;

	/**
	 * VkCommandHandler constructor.
	 *
	 * @param TelegramBot $bot
	 * @param Command $command
	 * @param TelegramUser $user
	 * @param TelegramViewer $viewer
	 */
	public function __construct(TelegramBot $bot, Command $command, TelegramUser $user, TelegramViewer $viewer)
	{
		parent::__construct($bot, $command, $user, $viewer);
	}

	/**
	 * Обработчик команды "Задать вопрос"
	 *
	 * @return void
	 */
	protected function askNewQuestion(): void
	{
		$this->user->update('expected_input', null);
		$this->bot->sendMessage($this->user->destinationID, static::$answers['telegram_answer_to_question'], 'full');
		return;
	}

	/**
	 * Обработчик ввода текста вопроса
	 *
	 * @return void
	 */
	protected function inputQuestionText(): void
	{
		$this->user->update('expected_input', null);
		$this->bot->sendMessage($this->user->destinationID, static::$answers['telegram_answer_to_question'], 'full');
		return;
	}
}