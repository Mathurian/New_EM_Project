<?php
declare(strict_types=1);

namespace Tests;

use App\SecurityService;

/**
 * Test cases for SecurityService
 */
class SecurityServiceTest extends TestCase
{
    public function testSanitizeInput(): void
    {
        $input = [
            'name' => '  Test User  ',
            'email' => 'test@example.com',
            'html' => '<script>alert("xss")</script>',
            'number' => '123abc'
        ];
        
        $rules = [
            'name' => ['max_length' => 10],
            'html' => ['html' => false],
            'number' => ['type' => 'int']
        ];
        
        $sanitized = SecurityService::sanitizeInput($input, $rules);
        
        $this->assertEquals('Test User', $sanitized['name']); // Trimmed
        $this->assertEquals('test@example.com', $sanitized['email']);
        $this->assertEquals('alert("xss")', $sanitized['html']); // HTML stripped
        $this->assertEquals('123', $sanitized['number']); // Only numbers
    }

    public function testCsrfTokenGeneration(): void
    {
        $token1 = SecurityService::generateCsrfToken();
        $token2 = SecurityService::generateCsrfToken();
        
        $this->assertTrue(strlen($token1) === 64); // 32 bytes = 64 hex chars
        $this->assertEquals($token1, $token2); // Should be same in same session
    }

    public function testCsrfTokenVerification(): void
    {
        $token = SecurityService::generateCsrfToken();
        
        $this->assertTrue(SecurityService::verifyCsrfToken($token));
        $this->assertFalse(SecurityService::verifyCsrfToken('invalid_token'));
    }

    public function testRateLimiting(): void
    {
        $key = 'test_rate_limit';
        
        // Should allow first 10 attempts
        for ($i = 0; $i < 10; $i++) {
            $this->assertTrue(SecurityService::checkRateLimit($key, 10, 60));
        }
        
        // 11th attempt should be blocked
        $this->assertFalse(SecurityService::checkRateLimit($key, 10, 60));
    }

    public function testFileUploadValidation(): void
    {
        // Test valid image upload
        $validFile = [
            'name' => 'test.jpg',
            'type' => 'image/jpeg',
            'size' => 1024,
            'tmp_name' => '/tmp/test.jpg',
            'error' => UPLOAD_ERR_OK
        ];
        
        $result = SecurityService::validateFileUpload($validFile, ['image/jpeg'], 2048);
        
        $this->assertTrue($result['success']);
        $this->assertEmpty($result['errors']);
        
        // Test file too large
        $largeFile = array_merge($validFile, ['size' => 10240]);
        $result = SecurityService::validateFileUpload($largeFile, ['image/jpeg'], 2048);
        
        $this->assertFalse($result['success']);
        $this->assertNotEmpty($result['errors']);
    }

    public function testGenerateSecurePassword(): void
    {
        $password = SecurityService::generateSecurePassword(12);
        
        $this->assertEquals(12, strlen($password));
        $this->assertTrue(ctype_alnum($password) || preg_match('/[!@#$%^&*]/', $password));
    }
}