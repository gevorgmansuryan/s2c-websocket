<?php

namespace Gevman\PhpSocket;

/**
 * Class IdentityManager
 * @package Gevman\PhpSocket
 */
class IdentityManager
{
	/**
	 * @var string
	 */
	private $identityParam;

	/**
	 * @var array
	 */
	private $identities = [];

	/**
	 * IdentityManager constructor
	 *
	 * @param string $identityParam - parameter to resolve identity
	 */
	public function __construct($identityParam)
	{
		$this->identityParam = $identityParam;
	}

	/**
	 * Add new client connection
	 *
	 * @param array $params
	 * @param $socket
	 *
	 * @return mixed
	 */
	public function addClient($params, $socket)
	{
		$this->identities[uniqid('client_')] = [
			'identity' => $params,
			'socket' => $socket
		];
		return $params;
	}

	/**
	 * Remove client connection
	 *
	 * @param $socket
	 *
	 * @return mixed
	 */
	public function removeClient($socket)
	{
		$socketId = array_search($socket, array_combine(array_keys($this->identities), array_column($this->identities, 'socket')));
		$params = $this->identities[$socketId]['identity'];
		unset($this->identities[$socketId]);
		return $params;
	}

	/**
	 * Get list of all sockets
	 *
	 * @return array
	 */
	public function getSockets()
	{
		return array_combine(array_keys($this->identities), array_column($this->identities, 'socket'));
	}

	/**
	 * Get sockets of specific identity
	 *
	 * @param string $identity
	 *
	 * @return array
	 */
	public function getSocketsByIdentity($identity)
	{
		return array_intersect_key(array_column($this->identities, 'socket'), array_flip(array_keys(array_column(array_column($this->identities, 'identity'), $this->identityParam), $identity)));
	}

	/**
	 * Get params of specific identity
	 *
	 * @param string $identity
	 *
	 * @return array
	 */
	public function getParamsByIdentity($identity)
	{
		$identities = array_column($this->identities, 'identity');
		return  array_intersect_key($identities, array_flip(array_keys(array_column($identities, $this->identityParam), $identity)));
	}

	/**
	 * Get list of all identities
	 *
	 * @return array
	 */
	public function getIdentities()
	{
		return array_column($this->identities, 'identity');
	}
}