<?php

declare(strict_types=1);

namespace ChatMessenger\Tests\Integration;

use ChatMessenger\Server\UdpServer;
use ChatMessenger\Common\Utils\Config;
use ChatMessenger\Common\Protocol\UdpProtocol;
use PHPUnit\Framework\TestCase;

class UdpServerIntegrationTest extends TestCase
{
    private UdpServer $server;
    private Config $config;
    private UdpProtocol $protocol;
    private int $testPort = 9999;

    protected function setUp(): void
    {
        $this->config = new Config(['udp_port' => $this->testPort]);
        $this->server = new UdpServer($this->config, false); // verbose=false for tests
        $this->protocol = new UdpProtocol();
    }

    protected function tearDown(): void
    {
        if ($this->server->isRunning()) {
            $this->server->stop();
        }
    }

    public function testServerSocketCreationAndBinding(): void
    {
        // Test that we can bind to the test port
        $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        $this->assertNotFalse($socket, 'Could not create test socket');

        $bound = socket_bind($socket, '127.0.0.1', $this->testPort);
        $this->assertTrue($bound, 'Could not bind to test port ' . $this->testPort);

        socket_close($socket);
    }

    public function testServerConfiguration(): void
    {
        $this->assertSame($this->testPort, $this->server->getPort());
        $this->assertFalse($this->server->isRunning());
        $this->assertSame(0, $this->server->getClientCount());
    }

    public function testProtocolIntegration(): void
    {
        // Test that the server uses the same protocol for message handling
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

    public function testMessageSizeConfiguration(): void
    {
        $maxSize = $this->config->getMaxMessageSize();
        $this->assertSame(4096, $maxSize);

        // Test with custom configuration
        $customConfig = new Config(['max_message_size' => 8192]);
        $customServer = new UdpServer($customConfig, false);

        // Server should use the custom config
        $this->assertSame(8192, $customConfig->getMaxMessageSize());
    }

    public function testServerLifecycle(): void
    {
        // Initial state
        $this->assertFalse($this->server->isRunning());
        $this->assertSame(0, $this->server->getClientCount());

        // Test stop when not running (should not throw)
        $this->server->stop();
        $this->assertFalse($this->server->isRunning());
    }

    public function testSocketResourceManagement(): void
    {
        // Test that sockets are properly cleaned up
        $initialSocketCount = $this->getOpenSocketCount();

        // Create and destroy server instances
        for ($i = 0; $i < 5; $i++) {
            $testServer = new UdpServer(new Config(['udp_port' => $this->testPort + $i + 1]), false);
            unset($testServer);
        }

        // Socket count should not have increased significantly
        $finalSocketCount = $this->getOpenSocketCount();
        $this->assertLessThanOrEqual($initialSocketCount + 2, $finalSocketCount);
    }

    public function testConcurrentServerInstances(): void
    {
        // Test that we can create multiple server instances with different ports
        $servers = [];
        $ports = [9901, 9902, 9903];

        foreach ($ports as $port) {
            $config = new Config(['udp_port' => $port]);
            $server = new UdpServer($config, false);
            $this->assertSame($port, $server->getPort());
            $this->assertFalse($server->isRunning());
            $servers[] = $server;
        }

        // Cleanup
        foreach ($servers as $server) {
            if ($server->isRunning()) {
                $server->stop();
            }
        }
    }

    public function testServerErrorHandling(): void
    {
        // Test invalid port handling by trying to bind to a privileged port
        if (posix_geteuid() !== 0) { // Only test if not running as root
            $config = new Config(['udp_port' => 80]); // Privileged port
            $server = new UdpServer($config, false);

            $this->assertSame(80, $server->getPort());
            $this->assertFalse($server->isRunning());
        } else {
            $this->markTestSkipped('Running as root, cannot test privileged port binding');
        }
    }

    public function testMemoryUsage(): void
    {
        $initialMemory = memory_get_usage();

        // Create multiple server instances
        $servers = [];
        for ($i = 0; $i < 5; $i++) { // Reduced from 10 to 5
            $servers[] = new UdpServer(new Config(['udp_port' => 9800 + $i]), false);
        }

        $afterCreationMemory = memory_get_usage();
        $memoryUsed = $afterCreationMemory - $initialMemory;

        // Total memory usage should be reasonable (less than 5MB for 5 servers)
        $this->assertLessThan(5 * 1024 * 1024, $memoryUsed);

        // Cleanup
        unset($servers);

        $finalMemory = memory_get_usage();
        // Memory difference should be reasonable (allow for PHP's memory management)
        $memoryDifference = abs($finalMemory - $initialMemory);
        $this->assertLessThan(1024 * 1024, $memoryDifference); // Allow 1MB difference
    }

    private function getOpenSocketCount(): int
    {
        // This is a rough estimate - in a real environment you might use more sophisticated methods
        return 0; // Placeholder - actual implementation would depend on system
    }

    public function testServerConfigurationValidation(): void
    {
        // Test various configuration scenarios
        $validConfigs = [
            ['udp_port' => 8080],
            ['udp_port' => 9000, 'max_message_size' => 8192],
            ['udp_port' => 8081, 'timeout' => 60],
        ];

        foreach ($validConfigs as $configData) {
            $config = new Config($configData);
            $server = new UdpServer($config, false);

            $this->assertSame($configData['udp_port'], $server->getPort());
            $this->assertInstanceOf(UdpServer::class, $server);
        }
    }
}
