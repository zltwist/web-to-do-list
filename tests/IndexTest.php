<?php

use PHPUnit\Framework\TestCase;

class IndexTest extends TestCase
{
    /**
     * Test rendering halaman untuk memeriksa elemen-elemen HTML.
     */
    public function testRenderPage()
    {
        // Mock database connection
        $mockConn = $this->createMock(mysqli::class);

        // Mock result set for tasks
        $mockResult = $this->createMock(mysqli_result::class);
        $mockResult->method('fetch_assoc')->willReturn([
            'task' => 'mencuci',
            'deadline' => '2024-12-25',
            'category' => 'harian',
            'status' => 'Not Started'
        ]);
        $mockConn->method('prepare')->willReturn($mockResult);

        // Inject mocked connection into the application (or modify Index.php to allow injection)
        $GLOBALS['conn'] = $mockConn;

        // Simulasi render output dari Index.php
        $output = $this->getRenderedOutput();

        // Verifikasi elemen-elemen HTML yang penting
        $this->assertStringContainsString('<title>To-Do-List</title>', $output);
        $this->assertStringContainsString('<a href="?category=harian"', $output);  // Kategori "harian"
        $this->assertStringContainsString('<td>mencuci</td>', $output);           // Task "mencuci"
        $this->assertStringContainsString('<th>Task</th>', $output);              // Kolom "Task"
        $this->assertStringContainsString('<th>Deadline</th>', $output);          // Kolom "Deadline"
        $this->assertStringContainsString('<th>Category</th>', $output);          // Kolom "Category"
        $this->assertStringContainsString('<th>Status</th>', $output);            // Kolom "Status"
        $this->assertStringContainsString('<th>Actions</th>', $output);           // Kolom "Actions"
    }

    /**
     * Test pagination links.
     */
    public function testPaginationLinks()
    {
        // Simulasi render output dari Index.php
        $output = $this->getRenderedOutput();

        // Verifikasi apakah link pagination muncul dengan benar
        $this->assertStringContainsString('<a class="page-link" href="?page=1">1</a>', $output);
        $this->assertStringContainsString('<a class="page-link" href="?page=2">2</a>', $output);
    }

    /**
     * Test form submission validation (mocking the database).
     */
    public function testFormValidation()
    {
        // Mocking form submission logic and validation
        $_POST['task'] = 'Test Task';
        $_POST['deadline'] = '2024-12-25';
        $_POST['category'] = 'harian';
        $_POST['status'] = 'Not Started';

        // Validasi deadline format (yyyy-mm-dd)
        $this->assertMatchesRegularExpression("/^\d{4}-\d{2}-\d{2}$/", $_POST['deadline']);
        $this->assertNotEmpty($_POST['task']);
        $this->assertLessThanOrEqual(255, strlen($_POST['task']));

        // Mocking the insert or update query
        $mockConn = $this->createMock(mysqli::class);
        $mockStmt = $this->createMock(mysqli_stmt::class);
        $mockStmt->method('execute')->willReturn(true);
        $mockConn->method('prepare')->willReturn($mockStmt);
        $GLOBALS['conn'] = $mockConn;

        // Simulasi render output dari Index.php
        $output = $this->getRenderedOutput();
        
        // Verifikasi apakah task yang baru ditambahkan terlihat di halaman
        $this->assertStringContainsString('Test Task', $output);
    }

    /**
     * Test delete action.
     */
    public function testDeleteAction()
    {
        // Simulasi parameter delete
        $_GET['delete'] = 1;

        // Mocking database delete query
        $mockConn = $this->createMock(mysqli::class);
        $mockStmt = $this->createMock(mysqli_stmt::class);
        $mockStmt->method('execute')->willReturn(true);
        $mockConn->method('prepare')->willReturn($mockStmt);
        $GLOBALS['conn'] = $mockConn;

        // Simulasi render output setelah delete
        $output = $this->getRenderedOutput();

        // Verifikasi apakah task telah dihapus
        $this->assertStringNotContainsString('<td>mencuci</td>', $output); // Pastikan task yang dihapus tidak ada lagi
    }

    /**
     * Test search functionality.
     */
    public function testSearchFunctionality()
    {
        // Simulasi pencarian
        $_GET['search'] = 'mencuci';

        // Mock database result for search
        $mockConn = $this->createMock(mysqli::class);
        $mockResult = $this->createMock(mysqli_result::class);
        $mockResult->method('fetch_assoc')->willReturn([
            'task' => 'mencuci',
            'deadline' => '2024-12-25',
            'category' => 'harian',
            'status' => 'Not Started'
        ]);
        $mockConn->method('prepare')->willReturn($mockResult);
        $GLOBALS['conn'] = $mockConn;

        // Simulasi render output dari Index.php
        $output = $this->getRenderedOutput();

        // Verifikasi hasil pencarian
        $this->assertStringContainsString('mencuci', $output);
    }

    /**
     * Fungsi untuk mendapatkan output HTML dari halaman Index.php.
     */
    private function getRenderedOutput()
    {
        ob_start();
        include __DIR__ . '/Index.php'; // Ganti dengan path sebenarnya jika diperlukan
        return ob_get_clean();
    }
}

