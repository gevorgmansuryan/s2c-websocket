<?php

namespace Gevman\PhpSocket;

use Exception;

class Notifier extends SocketHandler
{
	/**
	 * Notifier constructor.
	 *
	 * @param string $host
	 * @param int $port
	 */
	public function __construct($host, $port)
	{
		parent::__construct($host, $port, null, null, null);
	}

	/**
	 * @inheritdoc
	 */
	protected function init()
	{
		$this->connectToSererSocket();
	}

	/**
	 * Send message to server which will be delivered to listed identities
	 *
	 * @param $message - Message to send
	 * @param array $to - identities to be notified, if empty will be notified all identities
	 *
	 * @return bool
	 */
	public function notify($message, $to = null)
	{
		try {
			$this->sendToServer($message, $to);
			return true;
		} catch (Exception $e) {
			return false;
		}
	}
}