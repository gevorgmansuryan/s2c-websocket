# PhpSocket

One way (server2client) simple WebSocket PHP library

## Installation (using composer)

```bash
composer require gevman/s2c-websocket
```

### Listener methods

##### Constructor(string $host, int $port, string $path, string $identityParam, bool $ssl)
###### Create Listener
- `$host` - server ip or domain
- `$port` - port for server socket
- `$path` - default path for socket connection
- `$identityParam` - parameter to resolve identity
- `$ssl` - if true will be used `wss` protocol, otherwise `ws` protocol

##### void listen()
###### Start listening

##### void onUpdate(function(array $AllIdentities) {/* */})
###### Triggers when user connected or disconnected
- `$AllIdentities` - List of all identities along with params

##### void onClientConnect(function(array $identity) {/* */})
###### Triggers when user connected
- `$identity` - Array of identity params of connected user

##### void onClientDisconnect(function(array $identity) {/* */})
###### Triggers when user disconnected
- `$identity` - Array of identity params of disconnected user

##### void onMessage(function(array $identities, string $message) {/* */})
###### Triggers when server sends message
- `$identities` - List of identities which will receive message
- `$message` - message content

##### void onError(function(Exception $e) {/* */})
###### Triggers on error
- `$host` - Exception object of error

##### Example

###### Client side (JavaScript)
```javascript
var webSocket = new WebSocket('ws://example.com:8080/socket/test/id/1/username/test/some-param/some-value');
```
###### Server side (PHP)
```php
require __DIR__.'/vendor/autoload.php';

$listener = new \Gevman\PhpSocket\Listener('example.com', 8080, '/socket/test', 'id', false);

$listener->events->onError(function(Exception $e) {
    printf('error: %s in %s at line %s', $e->getMessage(), $e->getFile(), $e->getLine());
});

$listener->events->onClientConnect(function($identity) {
    printf("Client connected: %s\n\n", $identity['id']);
});

$listener->events->onClientDisconnect(function($identity) {
    printf("Client disconnected: %s\n\n", $identity['id']);
});

$listener->events->onMessage(function($identities, $message) {
    printf("new message: %s to %s\n\n", $message, implode(',', array_column($identities, 'id')));
});

$listener->events->onUpdate(function($AllIdentities) {
    file_put_contents('online-users.txt', array_column($AllIdentities, 'id'));
});

$listener->listen();
```

### Notifier methods

##### Constructor(string $host, int $port)
###### Create Notifier
- `$host` - server ip or domain
- `$port` - port of server socket

##### bool notify(mixed $message, mixed $to)
###### Send message to server which will be delivered to listed identities
- `$message` - Message to send
- `$to` - Identity or list identities to be notified, if empty will be notified all identities

##### Example

###### Server side (PHP)
```php
require __DIR__.'/vendor/autoload.php';

$notifier = new \Gevman\PhpSocket\Notifier('example.com', 8080);
$notifier->notify(['something' => 'happened', 'new' => 'message'], 1);
```

###### Client side (JavaScript)
```javascript
var webSocket = new WebSocket('ws://example.com:8080/socket/test/id/1/username/test/some-param/some-value');
webSocket.onmessage(function (message) {
    var data = JSON.parse(message.data);
    console.log(data.something);
    console.log(data.new);
});
```

---

For https sites You need use `wss` connection, For this You need pass `true` for last parameter of Listener constructor and use Apache ProxyPass in Your apache config
```apacheconfig
ProxyPass /wss ws://example.com:8080
```
