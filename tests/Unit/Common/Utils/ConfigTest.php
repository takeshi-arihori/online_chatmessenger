<?php

declare(strict_types=1);

namespace ChatMessenger\Tests\Unit\Common\Utils;

use ChatMessenger\Common\Utils\Config;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    public function testDefaultConfig(): void
    {
        $config = new Config();
        
        $this->assertSame(8080, $config->getUdpPort());
        $this->assertSame(8081, $config->getTcpPort());
        $this->assertSame(4096, $config->getMaxMessageSize());
        $this->assertSame(255, $config->getMaxUsernameLength());
        $this->assertSame('UTF-8', $config->get('encoding'));
    }
    
    public function testCustomConfig(): void
    {
        $customConfig = [
            'udp_port' => 9080,
            'tcp_port' => 9081,
            'max_message_size' => 8192
        ];
        
        $config = new Config($customConfig);
        
        $this->assertSame(9080, $config->getUdpPort());
        $this->assertSame(9081, $config->getTcpPort());
        $this->assertSame(8192, $config->getMaxMessageSize());
        $this->assertSame(255, $config->getMaxUsernameLength()); // default value
    }
    
    public function testGetWithDefault(): void
    {
        $config = new Config();
        
        $this->assertSame('default_value', $config->get('non_existent_key', 'default_value'));
        $this->assertNull($config->get('non_existent_key'));
    }
}