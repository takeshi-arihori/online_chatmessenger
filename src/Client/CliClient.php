<?php

declare(strict_types=1);

namespace ChatMessenger\Client;

use ChatMessenger\Common\Protocol\UdpProtocol;
use ChatMessenger\Common\Utils\Config;
use ChatMessenger\Client\Exception\ClientException;

class CliClient implements ClientInterface
{
    private ?\Socket $socket = null;
    private bool $connected = false;
    private string $username;
    private string $serverHost = '';
    private int $serverPort = 0;
    private UdpProtocol $protocol;
    private Config $config;
    private bool $verbose;
    private bool $shouldStop = false;

    public function __construct(string $username, ?Config $config = null, bool $verbose = false)
    {
        $this->username = trim($username);
        $this->config = $config ?? new Config();
        $this->protocol = new UdpProtocol();
        $this->verbose = $verbose;

        if (empty($this->username)) {
            throw new ClientException('Username cannot be empty');
        }

        if (strlen($this->username) > 255) {
            throw new ClientException('Username too long (max 255 characters)');
        }

        $this->setupSignalHandlers();
    }

    public function connect(string $host, int $port): bool
    {
        if ($this->connected) {
            throw new ClientException('Client is already connected');
        }

        try {
            $this->createSocket();
            $this->serverHost = $host;
            $this->serverPort = $port;

            // Send initial registration message to server
            $this->sendInitialMessage();
            $this->connected = true;

            if ($this->verbose) {
                echo "Connected to {$host}:{$port} as '{$this->username}'\n";
            }

            return true;
        } catch (ClientException $e) {
            $this->cleanup();
            throw $e;
        }
    }

    public function disconnect(): void
    {
        if (!$this->connected) {
            return;
        }

        $this->connected = false;
        $this->cleanup();

        if ($this->verbose) {
            echo "Disconnected from server\n";
        }
    }

    public function isConnected(): bool
    {
        return $this->connected;
    }

    public function sendMessage(string $message): bool
    {
        if (!$this->connected || $this->socket === null) {
            throw new ClientException('Client is not connected');
        }

        $message = trim($message);
        if (empty($message)) {
            return false;
        }

        try {
            $encodedMessage = $this->protocol->encode([
                'username' => $this->username,
                'message' => $message
            ]);

            $bytesSent = socket_sendto(
                $this->socket,
                $encodedMessage,
                strlen($encodedMessage),
                0,
                $this->serverHost,
                $this->serverPort
            );

            if ($bytesSent === false) {
                throw new ClientException('Failed to send message: ' . socket_strerror(socket_last_error()));
            }

            // Display own message with timestamp
            $this->displayOwnMessage($message);
            return true;
        } catch (\Exception $e) {
            if ($this->verbose) {
                echo "Error sending message: {$e->getMessage()}\n";
            }
            return false;
        }
    }

    public function start(): void
    {
        if (!$this->connected) {
            throw new ClientException('Client must be connected before starting');
        }

        $this->displayWelcome();
        $this->runEventLoop();
    }

    public function stop(): void
    {
        $this->shouldStop = true;
        $this->disconnect();
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @return array{host: string, port: int, connected: bool}
     */
    public function getServerInfo(): array
    {
        return [
            'host' => $this->serverHost,
            'port' => $this->serverPort,
            'connected' => $this->connected
        ];
    }

    private function createSocket(): void
    {
        $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        if ($socket === false) {
            throw new ClientException('Failed to create socket: ' . socket_strerror(socket_last_error()));
        }

        // Set socket to non-blocking mode for receiving
        if (!socket_set_nonblock($socket)) {
            socket_close($socket);
            throw new ClientException('Failed to set non-blocking mode: ' . socket_strerror(socket_last_error()));
        }

        $this->socket = $socket;
    }

    private function sendInitialMessage(): void
    {
        if ($this->socket === null) {
            throw new ClientException('Socket not created');
        }

        // Send a hello message to register with the server
        $helloMessage = $this->protocol->encode([
            'username' => $this->username,
            'message' => "has joined the chat"
        ]);

        $bytesSent = socket_sendto(
            $this->socket,
            $helloMessage,
            strlen($helloMessage),
            0,
            $this->serverHost,
            $this->serverPort
        );

        if ($bytesSent === false) {
            throw new ClientException('Failed to send initial message: ' . socket_strerror(socket_last_error()));
        }
    }

    private function runEventLoop(): void
    {
        while (!$this->shouldStop && $this->connected) {
            // Handle incoming messages
            $this->handleIncomingMessages();

            // Handle user input
            $this->handleUserInput();

            // Small delay to prevent busy waiting
            usleep(10000); // 10ms
        }
    }

    private function handleIncomingMessages(): void
    {
        if ($this->socket === null) {
            return;
        }

        $buffer = '';
        $senderAddress = '';
        $senderPort = 0;

        $bytesReceived = socket_recvfrom(
            $this->socket,
            $buffer,
            $this->config->getMaxMessageSize(),
            MSG_DONTWAIT,
            $senderAddress,
            $senderPort
        );

        if ($bytesReceived > 0) {
            $this->handleIncomingMessage($buffer);
        }
    }

    private function handleIncomingMessage(string $data): void
    {
        try {
            if (!$this->protocol->validate($data)) {
                if ($this->verbose) {
                    echo "Received invalid message\n";
                }
                return;
            }

            $decoded = $this->protocol->decode($data);

            // Don't display our own messages (already shown when sent)
            if ($decoded['username'] === $this->username) {
                return;
            }

            $this->displayIncomingMessage($decoded);
        } catch (\Exception $e) {
            if ($this->verbose) {
                echo "Error handling incoming message: {$e->getMessage()}\n";
            }
        }
    }

    private function handleUserInput(): void
    {
        $input = $this->readUserInput();

        if ($input === null) {
            return;
        }

        $input = trim($input);

        if (empty($input)) {
            return;
        }

        // Handle commands
        if ($this->handleCommand($input)) {
            return;
        }

        // Send regular message
        $this->sendMessage($input);
    }

    private function readUserInput(): ?string
    {
        // Use stream_select for non-blocking input reading
        $stdin = [STDIN];
        $write = $except = [];

        if (stream_select($stdin, $write, $except, 0, 100000) > 0) {
            $input = fgets(STDIN);
            return $input !== false ? rtrim($input, "\n\r") : null;
        }

        return null;
    }

    private function handleCommand(string $input): bool
    {
        if (!str_starts_with($input, '/')) {
            return false;
        }

        $command = strtolower($input);

        switch ($command) {
            case '/quit':
            case '/exit':
                echo "Goodbye!\n";
                $this->stop();
                return true;

            case '/help':
                $this->displayHelp();
                return true;

            case '/clear':
                system('clear');
                $this->displayWelcome();
                echo "({$this->username}) > ";
                return true;

            case '/info':
                $this->displayConnectionInfo();
                return true;

            default:
                echo "Unknown command: {$input}. Type /help for available commands.\n";
                echo "({$this->username}) > ";
                return true;
        }
    }

    private function displayWelcome(): void
    {
        echo "\n=== Welcome to Chat Messenger ===\n";
        echo "Connected as: {$this->username}\n";
        echo "Server: {$this->serverHost}:{$this->serverPort}\n";
        echo "Type your message and press Enter to send.\n";
        echo "Commands: /help, /quit, /clear, /info\n";
        echo "================================\n\n";
        echo "({$this->username}) > ";
        flush();
    }

    private function displayHelp(): void
    {
        echo "\nAvailable commands:\n";
        echo "/help    - Show this help message\n";
        echo "/quit    - Exit the chat\n";
        echo "/exit    - Exit the chat\n";
        echo "/clear   - Clear the screen\n";
        echo "/info    - Show connection information\n";
        echo "\n({$this->username}) > ";
        flush();
    }

    private function displayConnectionInfo(): void
    {
        echo "\nConnection Information:\n";
        echo "Username: {$this->username}\n";
        echo "Server: {$this->serverHost}:{$this->serverPort}\n";
        echo "Status: " . ($this->connected ? 'Connected' : 'Disconnected') . "\n";
        echo "\n({$this->username}) > ";
        flush();
    }

    private function displayOwnMessage(string $message): void
    {
        if (!$this->verbose) {
            return;
        }

        echo "\r\033[K"; // Clear current line
        echo "[" . date('H:i:s') . "] {$this->username}: {$message}\n";
        echo "({$this->username}) > ";
        flush();
    }

    /**
     * @param array{username: string, message: string, timestamp: string} $decoded
     */
    private function displayIncomingMessage(array $decoded): void
    {
        echo "\r\033[K"; // Clear current line
        echo "[{$decoded['timestamp']}] {$decoded['username']}: {$decoded['message']}\n";
        echo "({$this->username}) > ";
        flush();
    }

    private function setupSignalHandlers(): void
    {
        if (function_exists('pcntl_signal')) {
            pcntl_signal(SIGINT, [$this, 'handleShutdownSignal']);
            pcntl_signal(SIGTERM, [$this, 'handleShutdownSignal']);
        }
    }

    public function handleShutdownSignal(int $signal): void
    {
        echo "\nReceived shutdown signal, disconnecting...\n";
        $this->stop();
    }

    private function cleanup(): void
    {
        if ($this->socket !== null) {
            socket_close($this->socket);
            $this->socket = null;
        }
        $this->serverHost = '';
        $this->serverPort = 0;
    }

    public function __destruct()
    {
        $this->cleanup();
    }
}
