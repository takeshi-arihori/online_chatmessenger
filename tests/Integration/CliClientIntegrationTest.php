<?php

declare(strict_types=1);

namespace ChatMessenger\Tests\Integration;

use ChatMessenger\Client\CliClient;
use ChatMessenger\Client\Exception\ClientException;
use ChatMessenger\Server\UdpServer;
use ChatMessenger\Common\Utils\Config;
use ChatMessenger\Common\Protocol\UdpProtocol;
use PHPUnit\Framework\TestCase;

class CliClientIntegrationTest extends TestCase
{
    private int $testPort = 9998;
    private Config $config;
    private UdpProtocol $protocol;

    protected function setUp(): void
    {
        $this->config = new Config(['udp_port' => $this->testPort]);
        $this->protocol = new UdpProtocol();
    }

    public function testClientSocketCreation(): void
    {
        $client = new CliClient('testuser', $this->config, false);

        // Test that client can be created without throwing exceptions
        $this->assertSame('testuser', $client->getUsername());
        $this->assertFalse($client->isConnected());
    }

    public function testClientConnectionToNonExistentServer(): void
    {
        $client = new CliClient('testuser', $this->config, false);

        // Connecting to a non-existent server should not throw during connect
        // (UDP is connectionless, so the "connection" always succeeds)
        $result = $client->connect('127.0.0.1', $this->testPort);
        $this->assertTrue($result);
        $this->assertTrue($client->isConnected());

        $client->disconnect();
    }

    public function testClientDisconnection(): void
    {
        $client = new CliClient('testuser', $this->config, false);

        $client->connect('127.0.0.1', $this->testPort);
        $this->assertTrue($client->isConnected());

        $client->disconnect();
        $this->assertFalse($client->isConnected());
    }

    public function testMultipleClientInstances(): void
    {
        $clients = [];
        $ports = [9990, 9991, 9992];

        foreach ($ports as $i => $port) {
            $config = new Config(['udp_port' => $port]);
            $client = new CliClient("user{$i}", $config, false);
            $client->connect('127.0.0.1', $port);

            $this->assertTrue($client->isConnected());
            $this->assertSame("user{$i}", $client->getUsername());

            $clients[] = $client;
        }

        // Clean up
        foreach ($clients as $client) {
            $client->disconnect();
            $this->assertFalse($client->isConnected());
        }
    }

    public function testClientAlreadyConnectedError(): void
    {
        $client = new CliClient('testuser', $this->config, false);
        $client->connect('127.0.0.1', $this->testPort);

        $this->expectException(ClientException::class);
        $this->expectExceptionMessage('Client is already connected');

        $client->connect('127.0.0.1', $this->testPort);
    }

    public function testProtocolIntegration(): void
    {
        // Test that client uses the same protocol as server
        $testMessage = [
            'username' => 'testuser',
            'message' => 'Hello, integration test!'
        ];

        $encoded = $this->protocol->encode($testMessage);
        $decoded = $this->protocol->decode($encoded);

        $this->assertSame($testMessage['username'], $decoded['username']);
        $this->assertSame($testMessage['message'], $decoded['message']);
        $this->assertArrayHasKey('timestamp', $decoded);
    }

    public function testClientConfigurationValidation(): void
    {
        $validConfigs = [
            ['udp_port' => 8080],
            ['udp_port' => 9000, 'max_message_size' => 8192],
            ['udp_port' => 8081, 'timeout' => 60],
        ];

        foreach ($validConfigs as $configData) {
            $config = new Config($configData);
            $client = new CliClient('testuser', $config, false);

            $this->assertInstanceOf(CliClient::class, $client);
            $this->assertSame('testuser', $client->getUsername());
        }
    }

    public function testClientResourceManagement(): void
    {
        $initialMemory = memory_get_usage();

        // Create multiple client instances
        $clients = [];
        for ($i = 0; $i < 10; $i++) {
            $client = new CliClient("user{$i}", $this->config, false);
            $client->connect('127.0.0.1', $this->testPort + $i);
            $clients[] = $client;
        }

        $afterCreationMemory = memory_get_usage();
        $memoryUsed = $afterCreationMemory - $initialMemory;

        // Memory usage should be reasonable (less than 10MB for 10 clients)
        $this->assertLessThan(10 * 1024 * 1024, $memoryUsed);

        // Clean up
        foreach ($clients as $client) {
            $client->disconnect();
        }
        unset($clients);

        $finalMemory = memory_get_usage();
        // Memory difference should be reasonable
        $memoryDifference = abs($finalMemory - $initialMemory);
        $this->assertLessThan(2 * 1024 * 1024, $memoryDifference); // Allow 2MB difference
    }

    public function testClientServerInfoAfterConnection(): void
    {
        $client = new CliClient('testuser', $this->config, false);
        $host = '127.0.0.1';
        $port = $this->testPort;

        $client->connect($host, $port);

        $serverInfo = $client->getServerInfo();
        $this->assertSame($host, $serverInfo['host']);
        $this->assertSame($port, $serverInfo['port']);
        $this->assertTrue($serverInfo['connected']);

        $client->disconnect();

        $serverInfo = $client->getServerInfo();
        $this->assertFalse($serverInfo['connected']);
    }

    public function testInvalidPortHandling(): void
    {
        $client = new CliClient('testuser', $this->config, false);

        // Test with out-of-range port (should still "connect" since UDP is connectionless)
        $result = $client->connect('127.0.0.1', 70000); // Invalid port
        $this->assertTrue($result); // UDP connect always succeeds locally

        $client->disconnect();
    }

    public function testClientErrorHandling(): void
    {
        $client = new CliClient('testuser', $this->config, false);
        $client->connect('127.0.0.1', $this->testPort);

        // Test sending empty message
        $result = $client->sendMessage('');
        $this->assertFalse($result);

        $result = $client->sendMessage('   ');
        $this->assertFalse($result);

        // Test sending valid message (should not throw)
        $result = $client->sendMessage('test message');
        $this->assertTrue($result);

        $client->disconnect();
    }

    public function testClientLifecycle(): void
    {
        $client = new CliClient('testuser', $this->config, false);

        // Initial state
        $this->assertFalse($client->isConnected());

        // Connect
        $client->connect('127.0.0.1', $this->testPort);
        $this->assertTrue($client->isConnected());

        // Stop (which calls disconnect)
        $client->stop();
        $this->assertFalse($client->isConnected());
    }
}
