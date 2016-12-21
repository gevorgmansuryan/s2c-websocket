<?php

namespace Gevman\PhpSocket;

use Closure;

/**
 * Interface EventsInterface
 * @package Gevman\PhpSocket
 */
interface EventsInterface
{
	/**
	 * Triggers when user connected or disconnected
	 * @param Closure $callBack
	 */
	public function onUpdate(Closure $callBack);

	/**
	 * Triggers when user connected
	 * @param Closure $callBack
	 */
	public function onClientConnect(Closure $callBack);

	/**
	 * Triggers when user disconnected
	 * @param Closure $callBack
	 */
	public function onClientDisconnect(Closure $callBack);

	/**
	 * Triggers when server sends message
	 * @param Closure $callBack
	 */
	public function onMessage(Closure $callBack);

	/**
	 * Triggers on error
	 * @param Closure $callBack
	 */
	public function onError(Closure $callBack);
}