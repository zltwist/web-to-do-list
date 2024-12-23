<?php

use PHPUnit\Framework\TestCase;

class IndexTest extends TestCase
{
    private $mockConn;

    protected function setUp(): void
    {
        // Set up mock database connection
        $this->mockConn = $this->createMock(mysqli::class);

        // Mock statement object
        $mockStmt = $this->createMock(mysqli_stmt::class);
        $mockStmt->method('execute')->willReturn(true);
        $mockStmt->method('get_result')->willReturn($this->createMock(mysqli_result::class));

        $this->mockConn->method('prepare')->willReturn($mockStmt);

        // Set global connection
        $GLOBALS['conn'] = $this->mockConn;
    }

    public function testRenderPage()
    {
        // Simulate rendering output
        $output = $this->getRenderedOutput();

        // Assert that essential HTML elements exist
        $this->assertStringContainsString('<title>To-Do-List</title>', $output);
    }

    public function testPaginationLinks()
    {
        // Mock $_GET for pagination
        $_GET['page'] = 1;

        // Simulate rendering output
        $output = $this->getRenderedOutput();

        // Assert pagination links
        $this->assertStringContainsString('<a class="page-link" href="?page=1">1</a>', $output);
    }

    public function testFormValidation()
    {
        // Mock form submission
        $_POST['task'] = 'Test Task';
        $_POST['deadline'] = '2024-12-25';
        $_POST['category'] = 'harian';
        $_POST['status'] = 'Not Started';

        // Simulate rendering output
        $output = $this->getRenderedOutput();

        // Assert that the task appears in the rendered output
        $this->assertStringContainsString('Test Task', $output);
    }

    private function getRenderedOutput()
    {
        ob_start();
        include __DIR__ . '/Index.php'; // Adjust path if necessary
        return ob_get_clean();
    }
}

