<?php
/**
 * Created by PhpStorm.
 * User: Bernigend
 * Date: 12.11.2019
 * Time: 19:28
 */

namespace Core\Entities;


class Event
{
	/**
	 * Идентификатор события из БД
	 * @var integer
	 */
	public $id;

	/**
	 * Город
	 * @var string
	 */
	public $city;

	/**
	 * Заголовок события
	 * @var string
	 */
	public $title;

	/**
	 * Место проведения события
	 * @var string|null
	 */
	public $place;

	/**
	 * Дата проведения
	 * @var string
	 */
	public $date;

	/**
	 * Время проведения
	 * @var string|null
	 */
	public $time;

	/**
	 * Ссылка на источник
	 * @var string
	 */
	public $href;

	/**
	 * Event constructor.
	 *
	 * @param array $eventData
	 */
	public function __construct(array $eventData)
	{
		list($this->id, $this->city, $this->title, $this->place, $this->date, $this->time, $this->href) = $eventData;
	}
}