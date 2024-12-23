<?php
use PHPUnit\Framework\TestCase;

class IndexTest extends TestCase {
    public function testRenderPage() {
        ob_start(); // Start output buffering
        include 'index.php';
        $output = ob_get_clean(); // Capture the output
        $this->assertStringContainsString('<title>My App</title>', $output); // Cek apakah ada elemen tertentu
    }
}
