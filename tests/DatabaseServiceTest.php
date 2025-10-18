<?php
declare(strict_types=1);

namespace Tests;

use App\{DatabaseService, Cache};

/**
 * Test cases for DatabaseService
 */
class DatabaseServiceTest extends TestCase
{
    public function testGetContestsWithPagination(): void
    {
        // Create test contests
        $contest1 = $this->createContest(['name' => 'Contest 1', 'start_date' => '2024-01-01']);
        $contest2 = $this->createContest(['name' => 'Contest 2', 'start_date' => '2024-01-02']);
        $contest3 = $this->createContest(['name' => 'Contest 3', 'start_date' => '2024-01-03']);
        
        // Test first page
        $result = DatabaseService::getContests(1, 2);
        
        $this->assertArrayHasKey('items', $result);
        $this->assertArrayHasKey('pagination', $result);
        $this->assertCount(2, $result['items']);
        $this->assertEquals(3, $result['pagination']['total']);
        $this->assertEquals(2, $result['pagination']['total_pages']);
    }

    public function testGetUsersWithRoleFilter(): void
    {
        // Create test users
        $user1 = $this->createUser(['name' => 'Organizer', 'role' => 'organizer']);
        $user2 = $this->createUser(['name' => 'Judge', 'role' => 'judge']);
        $user3 = $this->createUser(['name' => 'Contestant', 'role' => 'contestant']);
        
        // Test filtering by role
        $result = DatabaseService::getUsers(1, 10, 'organizer');
        
        $this->assertCount(1, $result['items']);
        $this->assertEquals('Organizer', $result['items'][0]['name']);
    }

    public function testCacheFunctionality(): void
    {
        // Test cache put and get
        $key = 'test_key';
        $value = ['test' => 'data'];
        
        Cache::put($key, $value, 60);
        $cached = Cache::get($key);
        
        $this->assertEquals($value, $cached);
        
        // Test cache forget
        Cache::forget($key);
        $cached = Cache::get($key);
        
        $this->assertTrue($cached === null);
    }

    public function testCacheRemember(): void
    {
        $key = 'remember_test';
        $callCount = 0;
        
        $callback = function() use (&$callCount) {
            $callCount++;
            return ['computed' => 'value'];
        };
        
        // First call should execute callback
        $result1 = Cache::remember($key, $callback, 60);
        $this->assertEquals(1, $callCount);
        $this->assertEquals(['computed' => 'value'], $result1);
        
        // Second call should use cache
        $result2 = Cache::remember($key, $callback, 60);
        $this->assertEquals(1, $callCount); // Should not increment
        $this->assertEquals(['computed' => 'value'], $result2);
    }
}