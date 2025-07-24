<?php

declare(strict_types=1);

namespace ChatMessenger\Server;

use ChatMessenger\Common\Protocol\UdpProtocol;
use ChatMessenger\Common\Utils\Config;
use ChatMessenger\Server\Exception\ServerException;

class UdpServer implements ServerInterface
{
    private ?\Socket $socket = null;
    private bool $running = false;
    /**
     * @var array<string, array{address: string, port: int, last_seen: int, username: string}>
     */
    private array $clients = [];
    private UdpProtocol $protocol;
    private Config $config;
    private bool $verbose;

    public function __construct(?Config $config = null, bool $verbose = true)
    {
        $this->config = $config ?? new Config();
        $this->protocol = new UdpProtocol();
        $this->verbose = $verbose;
    }

    public function start(): void
    {
        if ($this->running) {
            throw new ServerException('Server is already running');
        }

        $this->createSocket();
        $this->bindSocket();
        $this->running = true;

        if ($this->verbose) {
            echo "UDP Server started on port {$this->getPort()}\n";
            echo "Waiting for clients...\n";
        }

        $this->listenLoop();
    }

    public function stop(): void
    {
        if (!$this->running) {
            return;
        }

        $this->running = false;

        if ($this->socket !== null) {
            socket_close($this->socket);
            $this->socket = null;
        }

        $this->clients = [];
        if ($this->verbose) {
            echo "UDP Server stopped\n";
        }
    }

    public function isRunning(): bool
    {
        return $this->running;
    }

    public function getPort(): int
    {
        return $this->config->getUdpPort();
    }

    /**
     * @return array<string, array{address: string, port: int, last_seen: int}>
     */
    public function getClients(): array
    {
        return $this->clients;
    }

    public function getClientCount(): int
    {
        return count($this->clients);
    }

    private function createSocket(): void
    {
        $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        if ($socket === false) {
            throw new ServerException('Failed to create socket: ' . socket_strerror(socket_last_error()));
        }

        // Set socket options
        if (!socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1)) {
            socket_close($socket);
            throw new ServerException('Failed to set socket option: ' . socket_strerror(socket_last_error()));
        }

        $this->socket = $socket;
    }

    private function bindSocket(): void
    {
        if ($this->socket === null) {
            throw new ServerException('Socket not created');
        }

        $port = $this->getPort();
        if (!socket_bind($this->socket, '0.0.0.0', $port)) {
            socket_close($this->socket);
            $this->socket = null;
            throw new ServerException("Failed to bind socket to port {$port}: " . socket_strerror(socket_last_error()));
        }
    }

    private function listenLoop(): void
    {
        if ($this->socket === null) {
            throw new ServerException('Socket not initialized');
        }

        while ($this->running) {
            $buffer = '';
            $clientAddress = '';
            $clientPort = 0;

            // Receive message from client
            assert($this->socket !== null); // For PHPStan
            $bytesReceived = socket_recvfrom(
                $this->socket,
                $buffer,
                $this->config->getMaxMessageSize(),
                0,
                $clientAddress,
                $clientPort
            );

            if ($bytesReceived === false) {
                $error = socket_last_error($this->socket);
                if ($error !== SOCKET_EINTR) { // Ignore interrupted system calls
                    if ($this->verbose) {
                        echo "Error receiving data: " . socket_strerror($error) . "\n";
                    }
                }
                continue;
            }

            if ($bytesReceived === 0) {
                continue;
            }

            $this->handleMessage($buffer, $clientAddress, $clientPort);
        }
    }

    private function handleMessage(string $data, string $clientAddress, int $clientPort): void
    {
        try {
            // Validate and decode the message
            if (!$this->protocol->validate($data)) {
                if ($this->verbose) {
                    echo "Invalid message from {$clientAddress}:{$clientPort}\n";
                }
                return;
            }

            $decoded = $this->protocol->decode($data);
            $username = $decoded['username'];
            $message = $decoded['message'];

            // Update client registry
            $clientKey = "{$clientAddress}:{$clientPort}";
            $this->clients[$clientKey] = [
                'address' => $clientAddress,
                'port' => $clientPort,
                'last_seen' => time(),
                'username' => $username
            ];

            if ($this->verbose) {
                echo "[{$decoded['timestamp']}] {$username}: {$message}\n";
            }

            // Broadcast message to all connected clients
            $this->broadcastMessage($data, $clientAddress, $clientPort);
        } catch (\Exception $e) {
            if ($this->verbose) {
                echo "Error handling message from {$clientAddress}:{$clientPort}: {$e->getMessage()}\n";
            }
        }
    }

    private function broadcastMessage(string $data, string $senderAddress, int $senderPort): void
    {
        if ($this->socket === null) {
            return;
        }

        $currentTime = time();
        $timeout = 300; // 5 minutes timeout for inactive clients

        foreach ($this->clients as $clientKey => $client) {
            // Skip sender
            if ($client['address'] === $senderAddress && $client['port'] === $senderPort) {
                continue;
            }

            // Remove inactive clients
            if ($currentTime - $client['last_seen'] > $timeout) {
                unset($this->clients[$clientKey]);
                continue;
            }

            // Send message to client
            $bytesSent = socket_sendto(
                $this->socket,
                $data,
                strlen($data),
                0,
                $client['address'],
                $client['port']
            );

            if ($bytesSent === false) {
                if ($this->verbose) {
                    echo "Failed to send message to {$client['address']}:{$client['port']}: " .
                         socket_strerror(socket_last_error()) . "\n";
                }
            }
        }
    }

    public function __destruct()
    {
        $this->stop();
    }
}
