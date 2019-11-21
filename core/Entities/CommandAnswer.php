<?php


namespace Core\Entities;


class CommandAnswer
{
	/**
	 * Текст ответного сообщения
	 * @var string
	 */
	public $text;

	/**
	 * Тип клавиатуры ответного сообщения
	 * @var string
	 */
	public $keyboardType;

	/**
	 * CommandAnswer constructor.
	 * @param string $text - текст ответного сообшения
	 * @param string|null $keyboardType - тип клавиатуры ответного сообщения
	 */
	public function __construct(string $text, ?string $keyboardType = 'full')
	{
		$this->text         = $text;
		$this->keyboardType = $keyboardType;
	}
}