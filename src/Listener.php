<?php

namespace Gevman\PhpSocket;

/**
 * Class Listener
 * @package Gevman\PhpSocket
 */
class Listener extends SocketHandler
{
	/**
	 * @var EventsInterface
	 */
	public $events;

	/**
	 * @inheritdoc
	 */
	protected function init()
	{
		parent::init();
		$this->createSocket();
	}

	/**
	 * Start listening
	 */
	public function listen()
	{
		while (true) {
			$this->performListening();
		}
	}
}