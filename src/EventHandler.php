<?php

namespace Gevman\PhpSocket;

use Closure;
use Exception;

/**
 * Class EventHandler
 * @package Gevman\PhpSocket
 */
class EventHandler implements EventsInterface
{
	/**
	 * @var array - array of callbacks
	 */
	private $callBacks = [
		'update' => [],
		'connect' => [],
		'disconnect' => [],
		'error' => [],
		'message' => []
	];

	/**
	 * @inheritdoc
	 */
	public function onUpdate(Closure $callBack)
	{
		$this->callBacks['update'][] = $callBack;
	}

	/**
	 * @inheritdoc
	 */
	public function onClientConnect(Closure $callBack)
	{
		$this->callBacks['connect'][] = $callBack;
	}

	/**
	 * @inheritdoc
	 */
	public function onClientDisconnect(Closure $callBack)
	{
		$this->callBacks['disconnect'][] = $callBack;
	}

	/**
	 * @inheritdoc
	 */
	public function onMessage(Closure $callBack)
	{
		$this->callBacks['message'][] = $callBack;
	}

	/**
	 * @inheritdoc
	 */
	public function onError(Closure $callBack)
	{
		$this->callBacks['error'][] = $callBack;
	}

	/**
	 * Trigger onMessage
	 *
	 * @param $identities
	 * @param $message
	 */
	public function message($identities, $message)
	{
		foreach ($this->callBacks['message'] as $callBack) {
			$callBack($identities, $message);
		}
	}

	/**
	 * Triggers onUpdate
	 *
	 * @param $identities
	 */
	public function updated($identities)
	{
		foreach ($this->callBacks['update'] as $callBack) {
			$callBack($identities);
		}
	}

	/**
	 * Triggers onClientConnect
	 *
	 * @param $identities
	 * @param $identity
	 */
	public function clientConnected($identities, $identity)
	{
		foreach ($this->callBacks['connect'] as $callBack) {
			$callBack($identity);
		}
		$this->updated($identities);
	}

	/**
	 * Triggers onClientDisconnect
	 *
	 * @param $identities
	 * @param $identity
	 */
	public function clientDisconnected($identities, $identity)
	{
		foreach ($this->callBacks['disconnect'] as $callBack) {
			$callBack($identity);
		}
		$this->updated($identities);
	}

	/**
	 * Triggers onError
	 *
	 * @param Exception $error
	 */
	public function error(Exception $error)
	{
		foreach ($this->callBacks['error'] as $callBack) {
			$callBack($error);
		}
	}
}