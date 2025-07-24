<?php

declare(strict_types=1);

namespace ChatMessenger\Tests\Integration;

use ChatMessenger\Common\Protocol\UdpProtocol;
use PHPUnit\Framework\TestCase;

class UdpProtocolPerformanceTest extends TestCase
{
    private UdpProtocol $protocol;

    protected function setUp(): void
    {
        $this->protocol = new UdpProtocol();
    }

    public function testEncodePerformance(): void
    {
        $data = [
            'username' => 'testuser',
            'message' => 'This is a performance test message for UDP protocol encoding.'
        ];

        $startTime = microtime(true);
        $iterations = 10000;

        for ($i = 0; $i < $iterations; $i++) {
            $this->protocol->encode($data);
        }

        $endTime = microtime(true);
        $totalTime = $endTime - $startTime;
        $avgTime = ($totalTime / $iterations) * 1000; // ms per operation

        // Should be able to encode 10,000 messages in reasonable time (< 1 second)
        $this->assertLessThan(1.0, $totalTime);

        // Average time per encoding should be < 0.1ms
        $this->assertLessThan(0.1, $avgTime);

        // echo "\nEncode Performance: {$iterations} operations in " . round($totalTime * 1000, 2) . "ms";
        // echo "\nAverage time per encode: " . round($avgTime, 4) . "ms\n";
    }

    public function testDecodePerformance(): void
    {
        // Pre-create test packet
        $packet = pack('C', 8) . 'testuser' . 'This is a performance test message.';

        $startTime = microtime(true);
        $iterations = 10000;

        for ($i = 0; $i < $iterations; $i++) {
            $this->protocol->decode($packet);
        }

        $endTime = microtime(true);
        $totalTime = $endTime - $startTime;
        $avgTime = ($totalTime / $iterations) * 1000; // ms per operation

        // Should be able to decode 10,000 messages in reasonable time
        $this->assertLessThan(1.0, $totalTime);

        // Average time per decoding should be < 0.1ms
        $this->assertLessThan(0.1, $avgTime);

        // echo "\nDecode Performance: {$iterations} operations in " . round($totalTime * 1000, 2) . "ms";
        // echo "\nAverage time per decode: " . round($avgTime, 4) . "ms\n";
    }

    public function testRoundTripPerformance(): void
    {
        $data = [
            'username' => 'user123',
            'message' => 'Round trip performance test message with some content.'
        ];

        $startTime = microtime(true);
        $iterations = 5000;

        for ($i = 0; $i < $iterations; $i++) {
            $encoded = $this->protocol->encode($data);
            $decoded = $this->protocol->decode($encoded);

            // Verify data integrity during performance test
            $this->assertSame($data['username'], $decoded['username']);
            $this->assertSame($data['message'], $decoded['message']);
        }

        $endTime = microtime(true);
        $totalTime = $endTime - $startTime;
        $avgTime = ($totalTime / $iterations) * 1000; // ms per operation

        // Should be able to do 5,000 round trips in reasonable time
        $this->assertLessThan(2.0, $totalTime);

        // echo "\nRound Trip Performance: {$iterations} operations in " . round($totalTime * 1000, 2) . "ms";
        // echo "\nAverage time per round trip: " . round($avgTime, 4) . "ms\n";
    }

    public function testMemoryUsage(): void
    {
        $initialMemory = memory_get_usage();

        $data = [
            'username' => 'memorytest',
            'message' => str_repeat('x', 1000) // 1KB message
        ];

        $packets = [];

        // Create 1000 encoded packets
        for ($i = 0; $i < 1000; $i++) {
            $packets[] = $this->protocol->encode($data);
        }

        $afterEncodeMemory = memory_get_usage();
        $encodeMemoryUsed = $afterEncodeMemory - $initialMemory;

        // Decode all packets
        $decoded = [];
        for ($i = 0; $i < 1000; $i++) {
            $decoded[] = $this->protocol->decode($packets[$i]);
        }

        $finalMemory = memory_get_usage();
        $totalMemoryUsed = $finalMemory - $initialMemory;

        // Memory usage should be reasonable (< 5MB for 1000 1KB messages)
        $this->assertLessThan(5 * 1024 * 1024, $totalMemoryUsed);

        // echo "\nMemory Usage Test:";
        // echo "\nEncode memory used: " . round($encodeMemoryUsed / 1024, 2) . "KB";
        // echo "\nTotal memory used: " . round($totalMemoryUsed / 1024, 2) . "KB\n";

        // Clean up
        unset($packets, $decoded);
    }
}
