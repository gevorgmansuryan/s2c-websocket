<?php

namespace Gevman\PhpSocket;

use Exception;

/**
 * Class SocketHandler
 * @package Gevman\PhpSocket
 */
abstract class SocketHandler
{
	/**
	 * @var string host
	 */
	protected $host;

	/**
	 * @var int port
	 */
	protected $port;

	/**
	 * @var string - connection url
	 */
	protected $path;

	/**
	 * @var string - default param for identify client
	 */
	protected $identityParam;

	/**
	 * @var bool - if true will be used `wss` protocol, otherwise `ws` protocol
	 */
	protected $ssl;

	/**
	 * @var string - Socket handshake magic string
	 */
	protected $magicString = '258EAFA5-E914-47DA-95CA-C5AB0DC85B11';

	/**
	 * @var null - null reference
	 */
	protected $null = NULL;

	/**
	 * Server socket
	 */
	protected $serverSocket;

	/**
	 * @var array
	 */
	protected $clients;
	/**
	 * @var IdentityManager
	 */
	protected $identityManager;

	/**
	 * @var EventHandler
	 */
	protected $events;

	/**
	 * SocketHandler constructor
	 *
	 * @param string $host
	 * @param int $port
	 * @param string $path
	 * @param string $identityParam
	 * @param bool $ssl
	 */
	public function __construct($host, $port, $path, $identityParam, $ssl = false)
	{
		$this->host = $host;
		$this->port = $port;
		$this->path = $path;
		$this->identityParam = $identityParam;
		$this->ssl = $ssl;
		$this->init();
	}

	/**
	 * Initialize class
	 */
	protected function init()
	{
		$this->events = new EventHandler();
		$this->identityManager = new IdentityManager($this->identityParam);
	}

	/**
	 * Create server socket
	 */
	protected function createSocket()
	{
		$this->serverSocket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		socket_set_option($this->serverSocket, SOL_SOCKET, SO_REUSEADDR, 1);
		socket_bind($this->serverSocket, 0, $this->port);
		socket_listen($this->serverSocket);
	}

	/**
	 * Connect to server socket
	 */
	protected function connectToSererSocket()
	{
		$this->serverSocket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		socket_connect($this->serverSocket, $this->host, $this->port);
	}

	/**
	 * Perform server socket listening
	 */
	protected function performListening()
	{
		try {
			$changed = array_merge([uniqid('server_') => $this->serverSocket], $this->identityManager->getSockets());
			socket_select($changed, $this->null, $this->null, 0, 10);
			if (in_array($this->serverSocket, $changed)) {
				$newSocket = socket_accept($this->serverSocket);
				$header = socket_read($newSocket, 1024);
				$headers = $this->parseHeaders($header);
				if ($headers) {
					if (is_null($headers['params']) || !array_key_exists($this->identityParam, $headers['params'])) {
						return;
					}
					$this->performHandshaking($headers['headers'], $newSocket);
					socket_getpeername($newSocket, $ip);
				} else {
					$this->sendMessage($header);
					return;
				}
				$this->clientConnect($headers['params'], $newSocket);
				$foundSocket = array_search($this->serverSocket, $changed);
				unset($changed[$foundSocket]);
			}
			foreach ($changed as $socket) {
				if (@socket_read($socket, 1024, PHP_NORMAL_READ) === false) {
					$this->clientDisconnect($socket);
				}
			}
		} catch (Exception $e) {
			$this->events->error($e);
		}
	}

	/**
	 * Send message to server socket
	 *
	 * @param $message
	 * @param null $to
	 */
	protected function sendToServer($message, $to = null)
	{
		$message = json_encode([
			'message' => $message,
			'to' => $to
		]);
		socket_write($this->serverSocket, $message, mb_strlen($message));
	}

	/**
	 * Parse socket headers
	 *
	 * @param $header
	 *
	 * @return array|null
	 */
	private function parseHeaders($header)
	{
		$headers = [];
		$params = '';
		foreach(preg_split("/\r\n/", $header) as $line) {
			if (preg_match("|GET \/(.*?) HTTP\/1.1|i", $line, $matches)) {
				$params = $matches[1];
				continue;
			}
			if(preg_match('/\A(\S+): (.*)\z/', chop($line), $matches)) {
				$headers[$matches[1]] = $matches[2];
			}
		}
		$params = $this->parseParams($params);
		return $headers ? compact('headers', 'params') : null;
	}

	/**
	 * Parse connection GET params
	 *
	 * @param $params
	 *
	 * @return array|null
	 */
	private function parseParams($params)
	{
		if (strpos($params, $this->path) === 0) {
			$params = substr($params, mb_strlen($this->path));
		}
		$all = explode('/', $params);
		$keys = [];
		$values = [];
		for ($i = 0; $i < count($all); $i++) {
			if ($i%2 == 0) {
				$keys[] = $all[$i];
			} else {
				$values[] = $all[$i];
			}
		}

		if (count($keys) == count($values)) {
			return array_combine($keys, $values);
		}
		return null;
	}

	/**
	 * Perform incoming socket connection handshaking
	 *
	 * @param $headers
	 * @param $client
	 */
	private function performHandshaking($headers, $client)
	{
		$secKey = $headers['Sec-WebSocket-Key'];
		$secAccept = base64_encode(pack('H*', sha1($secKey . $this->magicString)));
		$upgrade = [];
		$upgrade[] = 'HTTP/1.1 101 Web Socket Protocol Handshake';
		$upgrade[] = 'Upgrade: websocket';
		$upgrade[] = 'Connection: Upgrade';
		$upgrade[] = sprintf('WebSocket-Origin: %s://%s', $this->ssl ? 'https' : 'http', $this->host);
		$upgrade[] = sprintf('WebSocket-Location: %s://%s%s', $this->ssl ? 'wss' : 'ws', $this->host, $this->path);
		$upgrade[] = sprintf('Sec-WebSocket-Accept: %s', $secAccept);
		$upgrade = implode("\r\n", $upgrade)."\r\n\r\n";
		socket_write($client, $upgrade, mb_strlen($upgrade));
	}

	/**
	 * Send message to client sockets
	 *
	 * @param $data
	 */
	private function sendMessage($data)
	{
		$data = json_decode($data, true);
		if (empty($data)) {
			return;
		}
		$message = json_encode($data['message']);
		$to = $data['to'];
		$b1 = 0x80 | (0x1 & 0x0f);
		$length = mb_strlen($message);
		if($length <= 125) {
			$header = pack('CC', $b1, $length);
		} elseif($length > 125 && $length < 65536) {
			$header = pack('CCn', $b1, 126, $length);
		} elseif($length >= 65536) {
			$header = pack('CCNN', $b1, 127, $length);
		} else {
			$header = '';
		}
		$fullMessage = $header.$message;
		$sockets = [];
		$identityParams = [];
		if (!$to) {
			$sockets = $this->identityManager->getSockets();
			$identityParams = $this->identityManager->getIdentities();

		} else {
			$identities = is_array($to) ? $to : [$to];
			foreach ($identities as $identity) {
				$sockets = array_merge($sockets, $this->identityManager->getSocketsByIdentity($identity));
				$identityParams = array_merge($identityParams, $this->identityManager->getParamsByIdentity($identity));
			}
		}
		$this->events->message($identityParams, $message);
		foreach ($sockets as $socket) {
			socket_write($socket, $fullMessage, mb_strlen($fullMessage));
		}
	}

	/**
	 * Add new client socket connection
	 *
	 * @param $params
	 * @param $socket
	 */
	private function clientConnect($params, $socket)
	{
		if (is_resource($socket)) {
			$newIdentity = $this->identityManager->addClient($params, $socket);
		 	$this->events->clientConnected($this->identityManager->getIdentities(), $newIdentity);
		}
	}

	/**
	 * Remove existing client socket connection if socket is closed
	 *
	 * @param $socket
	 */
	private function clientDisconnect($socket)
	{
		$deletedIdentity = $this->identityManager->removeClient($socket);
		$this->events->clientDisconnected($this->identityManager->getIdentities(), $deletedIdentity);
	}

}