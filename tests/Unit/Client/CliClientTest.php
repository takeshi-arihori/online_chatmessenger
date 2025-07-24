<?php

declare(strict_types=1);

namespace ChatMessenger\Tests\Unit\Client;

use ChatMessenger\Client\CliClient;
use ChatMessenger\Client\Exception\ClientException;
use ChatMessenger\Common\Utils\Config;
use PHPUnit\Framework\TestCase;

class CliClientTest extends TestCase
{
    private CliClient $client;
    private Config $config;

    protected function setUp(): void
    {
        $this->config = new Config(['udp_port' => 8888]);
        $this->client = new CliClient('testuser', $this->config, false);
    }

    protected function tearDown(): void
    {
        if ($this->client->isConnected()) {
            $this->client->disconnect();
        }
    }

    public function testClientInitialization(): void
    {
        $this->assertFalse($this->client->isConnected());
        $this->assertSame('testuser', $this->client->getUsername());

        $serverInfo = $this->client->getServerInfo();
        $this->assertFalse($serverInfo['connected']);
        $this->assertEmpty($serverInfo['host']);
        $this->assertSame(0, $serverInfo['port']);
    }

    public function testClientWithDefaultConfig(): void
    {
        $client = new CliClient('defaultuser', null, false);
        $this->assertSame('defaultuser', $client->getUsername());
        $this->assertFalse($client->isConnected());
    }

    public function testInvalidUsername(): void
    {
        $this->expectException(ClientException::class);
        $this->expectExceptionMessage('Username cannot be empty');

        new CliClient('', $this->config, false);
    }

    public function testUsernameTooLong(): void
    {
        $this->expectException(ClientException::class);
        $this->expectExceptionMessage('Username too long');

        $longUsername = str_repeat('a', 256);
        new CliClient($longUsername, $this->config, false);
    }

    public function testUsernameWithWhitespace(): void
    {
        $client = new CliClient('  testuser  ', $this->config, false);
        $this->assertSame('testuser', $client->getUsername());
    }

    public function testDisconnectWhenNotConnected(): void
    {
        // Should not throw exception when disconnecting non-connected client
        $this->assertFalse($this->client->isConnected());
        $this->client->disconnect();
        $this->assertFalse($this->client->isConnected());
    }

    public function testSendMessageWhenNotConnected(): void
    {
        $this->expectException(ClientException::class);
        $this->expectExceptionMessage('Client is not connected');

        $this->client->sendMessage('test message');
    }

    public function testStartWhenNotConnected(): void
    {
        $this->expectException(ClientException::class);
        $this->expectExceptionMessage('Client must be connected before starting');

        $this->client->start();
    }

    public function testStopClient(): void
    {
        $this->client->stop();
        $this->assertFalse($this->client->isConnected());
    }

    public function testGetServerInfo(): void
    {
        $serverInfo = $this->client->getServerInfo();

        $this->assertIsArray($serverInfo);
        $this->assertArrayHasKey('host', $serverInfo);
        $this->assertArrayHasKey('port', $serverInfo);
        $this->assertArrayHasKey('connected', $serverInfo);
        $this->assertFalse($serverInfo['connected']);
    }

    public function testClientConfiguration(): void
    {
        $customConfig = new Config([
            'udp_port' => 9999,
            'max_message_size' => 8192
        ]);

        $client = new CliClient('configtest', $customConfig, false);
        $this->assertSame('configtest', $client->getUsername());
        $this->assertFalse($client->isConnected());
    }

    public function testValidUsernameLength(): void
    {
        // Test maximum valid username length (255 characters)
        $maxUsername = str_repeat('a', 255);
        $client = new CliClient($maxUsername, $this->config, false);
        $this->assertSame($maxUsername, $client->getUsername());
    }

    public function testDestructorCleanup(): void
    {
        $testClient = new CliClient('testdestructor', $this->config, false);

        // Destructor should clean up resources without throwing
        unset($testClient);
        $this->assertTrue(true); // If we get here, destructor didn't throw
    }

    public function testClientInstanceIsolation(): void
    {
        $client1 = new CliClient('user1', $this->config, false);
        $client2 = new CliClient('user2', $this->config, false);

        $this->assertSame('user1', $client1->getUsername());
        $this->assertSame('user2', $client2->getUsername());
        $this->assertNotSame($client1->getUsername(), $client2->getUsername());
    }

    public function testVerboseMode(): void
    {
        $verboseClient = new CliClient('verbose', $this->config, true);
        $quietClient = new CliClient('quiet', $this->config, false);

        $this->assertSame('verbose', $verboseClient->getUsername());
        $this->assertSame('quiet', $quietClient->getUsername());
    }
}
