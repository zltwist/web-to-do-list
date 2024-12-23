<?php
include 'database.php';

// Tambah atau Update tugas
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $task = $_POST['task'];
    $deadline = $_POST['deadline'];
    $category = $_POST['category'];
    $status = $_POST['status'];

    // Validasi input deadline
    if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $deadline)) {
        die('Invalid date format for deadline. Please use YYYY-MM-DD.');
    }

    // Validasi input lainnya
    if (empty($task) || strlen($task) > 255) {
        die('Task name cannot be empty or exceed 255 characters.');
    }

    if (!empty($_POST['edit_id'])) {
        // Update tugas menggunakan prepared statement
        $id = $_POST['edit_id'];
        $stmt = $conn->prepare("UPDATE tasks SET task=?, deadline=?, category=?, status=? WHERE id=?");
        $stmt->bind_param("ssssi", $task, $deadline, $category, $status, $id);
        $stmt->execute();
        if ($stmt->error) {
            die("Error: " . $stmt->error);
        }
        $stmt->close();
    } else {
        // Tambah tugas menggunakan prepared statement
        $stmt = $conn->prepare("INSERT INTO tasks (task, deadline, category, status) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $task, $deadline, $category, $status);
        $stmt->execute();
        if ($stmt->error) {
            die("Error: " . $stmt->error);
        }
        $stmt->close();
    }
    header('Location: index.php');
}

// Hapus tugas
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM tasks WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    if ($stmt->error) {
        die("Error: " . $stmt->error);
    }
    $stmt->close();
    header('Location: index.php');
}

// Pagination dan Filter
$page = $_GET['page'] ?? 1;
$statusFilter = $_GET['status'] ?? '';
$categoryFilter = $_GET['category'] ?? '';
$search = $_GET['search'] ?? '';
$limit = 10;
$offset = ($page - 1) * $limit;

// Query tugas dengan filter dan pagination
$sql = "SELECT * FROM tasks WHERE 1=1";
$params = [];
$types = "";

if ($statusFilter) {
    $sql .= " AND status=?";
    $params[] = $statusFilter;
    $types .= "s";
}

if ($categoryFilter) {
    $sql .= " AND category=?";
    $params[] = $categoryFilter;
    $types .= "s";
}

if ($search) {
    $sql .= " AND task LIKE ?";
    $params[] = "%$search%";
    $types .= "s";
}

$sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Hitung total data untuk pagination
$totalSql = "SELECT COUNT(*) as total FROM tasks WHERE 1=1";
$stmt = $conn->prepare($totalSql);
$stmt->execute();
$totalResult = $stmt->get_result()->fetch_assoc();
$totalPages = ceil($totalResult['total'] / $limit);

// Ambil semua kategori
$categories = $conn->query("SELECT DISTINCT category FROM tasks ORDER BY category ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>To-Do-List</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Styles (sama seperti sebelumnya) -->
    <style>
        /* Tambahkan CSS di sini */
    </style>
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <div class="sidebar">
            <h4>Categories</h4>
            <a href="?" class="<?php echo ($categoryFilter == '') ? 'active' : ''; ?>">All Task</a>
            <?php while ($cat = $categories->fetch_assoc()): ?>
                <a href="?category=<?php echo htmlspecialchars($cat['category']); ?>" 
                   class="<?php echo ($categoryFilter == $cat['category']) ? 'active' : ''; ?>">
                   <?php echo htmlspecialchars($cat['category']); ?>
                </a>
            <?php endwhile; ?>
        </div>

        <!-- Main Content -->
        <div class="flex-grow-1">
            <!-- Navbar -->
            <nav class="navbar navbar-light">
                <div class="container-fluid">
                    <form class="d-flex w-50" method="GET">
                        <input class="form-control me-2" type="search" name="search" placeholder="Search tasks..." value="<?php echo htmlspecialchars($search); ?>">
                        <button class="btn btn-primary" type="submit">Search</button>
                    </form>
                    <select class="form-select w-25 ms-3" name="status" onchange="this.form.submit()">
                        <option value="" <?php echo ($statusFilter == '') ? 'selected' : ''; ?>>All Status</option>
                        <option value="Not Started" <?php echo ($statusFilter == 'Not Started') ? 'selected' : ''; ?>>Not Started</option>
                        <option value="In Progress" <?php echo ($statusFilter == 'In Progress') ? 'selected' : ''; ?>>In Progress</option>
                        <option value="Done" <?php echo ($statusFilter == 'Done') ? 'selected' : ''; ?>>Done</option>
                    </select>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTaskModal" onclick="resetForm()">+ Add Task</button>
                </div>
            </nav>

            <!-- Task List -->
            <div class="content">
                <h4>Task List</h4>
                <table class="task-table">
                    <thead>
                        <tr>
                            <th>Task</th>
                            <th>Deadline</th>
                            <th>Category</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['task']); ?></td>
                                <td><?php echo $row['deadline']; ?></td>
                                <td><?php echo htmlspecialchars($row['category']); ?></td>
                                <td><?php echo htmlspecialchars($row['status']); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-warning" onclick="editTask(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['task']); ?>', '<?php echo $row['deadline']; ?>', '<?php echo htmlspecialchars($row['category']); ?>', '<?php echo htmlspecialchars($row['status']); ?>')">Edit</button>
                                    <a href="?delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this task?')">Delete</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>

                <!-- Pagination -->
                <nav>
                    <ul class="pagination">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&category=<?php echo htmlspecialchars($categoryFilter); ?>&status=<?php echo htmlspecialchars($statusFilter); ?>&search=<?php echo htmlspecialchars($search); ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            </div>
        </div>
    </div>

    <!-- Modal (sama seperti sebelumnya) -->
</body>
</html>

