<?php

declare(strict_types=1);

namespace ChatMessenger\Tests\Unit\Server;

use ChatMessenger\Server\UdpServer;
use ChatMessenger\Server\Exception\ServerException;
use ChatMessenger\Common\Utils\Config;
use PHPUnit\Framework\TestCase;

class UdpServerTest extends TestCase
{
    private UdpServer $server;
    private Config $config;

    protected function setUp(): void
    {
        // Use a different port for testing to avoid conflicts
        $this->config = new Config(['udp_port' => 8888]);
        $this->server = new UdpServer($this->config, false); // verbose=false for tests
    }

    protected function tearDown(): void
    {
        if ($this->server->isRunning()) {
            $this->server->stop();
        }
    }

    public function testServerInitialization(): void
    {
        $this->assertFalse($this->server->isRunning());
        $this->assertSame(8888, $this->server->getPort());
        $this->assertSame(0, $this->server->getClientCount());
        $this->assertEmpty($this->server->getClients());
    }

    public function testServerWithDefaultConfig(): void
    {
        $server = new UdpServer(null, false); // verbose=false for tests
        $this->assertSame(8080, $server->getPort());
        $this->assertFalse($server->isRunning());
    }

    public function testCannotStartAlreadyRunningServer(): void
    {
        // This test would require socket operations which are hard to test in unit tests
        // We'll test the logic by mocking the running state
        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('Server is already running');

        // Use reflection to set running state
        $reflection = new \ReflectionClass($this->server);
        $runningProperty = $reflection->getProperty('running');
        $runningProperty->setAccessible(true);
        $runningProperty->setValue($this->server, true);

        $this->server->start();
    }

    public function testStopNonRunningServer(): void
    {
        // Should not throw exception when stopping non-running server
        $this->assertFalse($this->server->isRunning());
        $this->server->stop();
        $this->assertFalse($this->server->isRunning());
    }

    public function testGetters(): void
    {
        $this->assertSame(8888, $this->server->getPort());
        $this->assertFalse($this->server->isRunning());
        $this->assertIsArray($this->server->getClients());
        $this->assertSame(0, $this->server->getClientCount());
    }

    public function testDestructorStopsServer(): void
    {
        // Create a new server instance for this test (verbose=false)
        $testServer = new UdpServer($this->config, false);

        // Use reflection to simulate running state
        $reflection = new \ReflectionClass($testServer);
        $runningProperty = $reflection->getProperty('running');
        $runningProperty->setAccessible(true);
        $runningProperty->setValue($testServer, true);

        // Destructor should stop the server
        unset($testServer);
        $this->assertTrue(true); // If we get here, destructor didn't throw
    }

    public function testClientManagement(): void
    {
        // Test client array structure
        $clients = $this->server->getClients();
        $this->assertIsArray($clients);
        $this->assertEmpty($clients);

        // Test client count
        $this->assertSame(0, $this->server->getClientCount());
    }

    /**
     * Test with different port configurations
     */
    public function testDifferentPortConfigurations(): void
    {
        $testCases = [
            ['udp_port' => 9000],
            ['udp_port' => 9001],
            ['udp_port' => 9002],
        ];

        foreach ($testCases as $configData) {
            $config = new Config($configData);
            $server = new UdpServer($config, false); // verbose=false for tests

            $this->assertSame($configData['udp_port'], $server->getPort());
            $this->assertFalse($server->isRunning());
        }
    }

    /**
     * Test configuration inheritance
     */
    public function testConfigurationInheritance(): void
    {
        $customConfig = new Config([
            'udp_port' => 7777,
            'max_message_size' => 8192,
            'timeout' => 60
        ]);

        $server = new UdpServer($customConfig, false); // verbose=false for tests
        $this->assertSame(7777, $server->getPort());
    }
}
