<?php
use PHPUnit\Framework\TestCase;

class DatabaseTest extends TestCase {
    public function testDatabaseConnection() {
        require 'databases.php'; // Pastikan file ini sudah sesuai
        $this->assertNotNull($conn); // Cek koneksi tidak null
    }

    public function testQueryExecution() {
        require 'database.php';
        $result = $conn->query('SELECT * FROM users');
        $this->assertGreaterThan(0, $result->num_rows); // Pastikan ada hasil dari query
    }
}
