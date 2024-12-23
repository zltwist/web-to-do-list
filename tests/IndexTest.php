<?php

use PHPUnit\Framework\TestCase;

class IndexTest extends TestCase
{
    public function testRenderPage()
    {
        // Simulasi render output dari Index.php
        $output = $this->getRenderedOutput();

        // Periksa apakah judul halaman sesuai
        $this->assertStringContainsString('<title>To-Do-List</title>', $output);

        // Periksa apakah sidebar memiliki kategori "harian"
        $this->assertStringContainsString('<a href="?category=harian"', $output);

        // Periksa apakah tabel tugas memiliki kolom yang benar
        $this->assertStringContainsString('<th>Task</th>', $output);
        $this->assertStringContainsString('<th>Deadline</th>', $output);
        $this->assertStringContainsString('<th>Category</th>', $output);
        $this->assertStringContainsString('<th>Status</th>', $output);
        $this->assertStringContainsString('<th>Actions</th>', $output);

        // Periksa apakah ada task "mencuci" di daftar tugas
        $this->assertStringContainsString('<td>mencuci</td>', $output);

        // Periksa apakah elemen pagination ada
        $this->assertStringContainsString('<ul class="pagination">', $output);

        // Periksa apakah tombol "Add Task" ada
        $this->assertStringContainsString('<button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTaskModal"', $output);
    }

    // Fungsi untuk mendapatkan output HTML dari halaman
    private function getRenderedOutput()
    {
        ob_start();
        include 'path/to/Index.php'; // Ganti dengan path sebenarnya ke Index.php
        return ob_get_clean();
    }
}

