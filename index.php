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

    if (!empty($_POST['edit_id'])) {
        $id = $_POST['edit_id'];
        $sql = "UPDATE tasks SET task='$task', deadline='$deadline', category='$category', status='$status' WHERE id=$id";
    } else {
        $sql = "INSERT INTO tasks (task, deadline, category, status) VALUES ('$task', '$deadline', '$category', '$status')";
    }
    $conn->query($sql);
    header('Location: index.php');
}

// Hapus tugas
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $sql = "DELETE FROM tasks WHERE id=$id";
    $conn->query($sql);
    header('Location: index.php');
}

// Ambil semua tugas
$categoryFilter = $_GET['category'] ?? '';
$search = $_GET['search'] ?? '';

if ($categoryFilter) {
    $sql = $search 
        ? "SELECT * FROM tasks WHERE category = '$categoryFilter' AND task LIKE '%$search%' ORDER BY created_at DESC"
        : "SELECT * FROM tasks WHERE category = '$categoryFilter' ORDER BY created_at DESC";
} else {
    $sql = $search 
        ? "SELECT * FROM tasks WHERE task LIKE '%$search%' ORDER BY created_at DESC"
        : "SELECT * FROM tasks ORDER BY created_at DESC LIMIT 10";
}

$result = $conn->query($sql);

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
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --soft-pink: #F3E8E6;
            --light-pink: #F9F4F3;
            --soft-green: #A8C1A1;
            --dark-gray: #8D99AE;
            --accent-blue: #7E9DAA;
            --accent-orange: #D9A78E;
        }

        body {
            background-color: var(--light-pink);
            font-family: 'Roboto', sans-serif;
        }

        .navbar {
            background-color: var(--soft-pink);
        }

        .sidebar {
            background-color: var(--soft-green);
            height: 100vh;
            color: white;
            padding: 15px;
        }

        .sidebar a {
            text-decoration: none;
            color: white;
            display: block;
            padding: 8px 15px;
            margin-bottom: 10px;
            border-radius: 5px;
            transition: background-color 0.3s;
        }

        .sidebar a.active {
            background-color: var(--dark-gray);
            font-weight: bold;
        }

        .sidebar a:hover {
            background-color: var(--dark-gray);
        }

        .content {
            padding: 20px;
        }

        .task-table {
            width: 100%;
            border-collapse: collapse;
        }

        .task-table th, .task-table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .task-table th {
            background-color: var(--soft-pink);
            color: #555;
        }

        .btn-primary {
            background-color: var(--accent-orange);
            border-color: var(--accent-orange);
        }

        .btn-primary:hover {
            background-color: var(--dark-gray);
            border-color: var(--dark-gray);
        }

        .btn-warning {
        background-color: var(--accent-blue);
        border-color: var(--accent-blue);
        color: white;
        }

        .btn-warning:hover {
            background-color: var(--dark-gray);
            border-color: var(--dark-gray);
        }

        .btn-danger {
            background-color: var(--accent-orange);
            border-color: var(--accent-orange);
            color: white;
        }

        .btn-danger:hover {
            background-color: var(--dark-gray);
            border-color: var(--dark-gray);
        }
    </style>
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <div class="sidebar">
            <h4>Categories</h4>
            <a href="?" class="<?php echo ($categoryFilter == '') ? 'active' : ''; ?>">All Tasks</a>
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
            </div>
        </div>
    </div>

    <!-- Add/Edit Task Modal -->
    <div class="modal fade" id="addTaskModal" tabindex="-1" aria-labelledby="addTaskModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addTaskModalLabel">Add/Edit Task</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="edit_id" id="edit_id">
                        <div class="mb-3">
                            <label for="task" class="form-label">Task Name</label>
                            <input type="text" name="task" id="task" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="deadline" class="form-label">Deadline</label>
                            <input type="date" name="deadline" id="deadline" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="category" class="form-label">Category</label>
                            <input type="text" name="category" id="category" class="form-control" placeholder="e.g., Work, Personal" required>
                        </div>
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select name="status" id="status" class="form-control">
                                <option value="Not Started">Not Started</option>
                                <option value="In Progress">In Progress</option>
                                <option value="Done">Done</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Save Task</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editTask(id, task, deadline, category, status) {
            document.getElementById('edit_id').value = id;
            document.getElementById('task').value = task;
            document.getElementById('deadline').value = deadline;
            document.getElementById('category').value = category;
            document.getElementById('status').value = status;
            document.getElementById('addTaskModalLabel').textContent = 'Edit Task';
            var modal = new bootstrap.Modal(document.getElementById('addTaskModal'));
            modal.show();
        }

        function resetForm() {
            document.getElementById('edit_id').value = '';
            document.getElementById('task').value = '';
            document.getElementById('deadline').value = '';
            document.getElementById('category').value = '';
            document.getElementById('status').value = 'Not Started';
            document.getElementById('addTaskModalLabel').textContent = 'Add New Task';
        }
    </script>
</body>
</html>
