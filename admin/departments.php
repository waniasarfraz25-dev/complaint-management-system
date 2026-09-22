<?php
require_once '../db.php';
// Handle Add
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    if ($name !== '') {
        $stmt = $conn->prepare("INSERT INTO departments (name, description) VALUES (?, ?)");
        $stmt->bind_param("ss", $name, $description);
        $stmt->execute();
    }
    header("Location: departments.php");
    exit();
}

// Handle Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit'])) {
    $id = intval($_POST['id']);
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $stmt = $conn->prepare("UPDATE departments SET name = ?, description = ? WHERE id = ?");
    $stmt->bind_param("ssi", $name, $description, $id);
    $stmt->execute();
    header("Location: departments.php");
    exit();
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM departments WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: departments.php");
    exit();
}
require_once 'includes/header.php';

$result = $conn->query("SELECT * FROM departments ORDER BY id DESC");
?>

<div class="card p-4 mb-3">
    <h5 class="mb-3">Add Department</h5>
    <form method="POST" class="row g-2">
        <div class="col-md-4">
            <input type="text" name="name" class="form-control" placeholder="Department name" required>
        </div>
        <div class="col-md-6">
            <input type="text" name="description" class="form-control" placeholder="Description">
        </div>
        <div class="col-md-2">
            <button type="submit" name="add" class="btn btn-primary w-100">Add</button>
        </div>
    </form>
</div>

<div class="card p-4">
    <h5 class="mb-3">All Departments</h5>
    <table class="table table-bordered" id="deptTable">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Description</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td>
                    <span class="view-mode"><?= htmlspecialchars($row['name']) ?></span>
                    <form method="POST" class="edit-mode d-none">
                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                        <input type="text" name="name" class="form-control form-control-sm mb-1" value="<?= htmlspecialchars($row['name']) ?>" required>
                </td>
                <td>
                    <span class="view-mode-desc"><?= htmlspecialchars($row['description']) ?></span>
                        <input type="text" name="description" class="form-control form-control-sm mb-1 edit-mode-desc d-none" value="<?= htmlspecialchars($row['description']) ?>">
                </td>
                <td>
                    <button type="button" class="btn btn-sm btn-warning edit-btn">Edit</button>
                    <button type="submit" name="edit" class="btn btn-sm btn-success save-btn d-none">Save</button>
                    </form>
                    <a href="departments.php?delete=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this department?')">Delete</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<script>
document.querySelectorAll('.edit-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const row = this.closest('tr').parentElement.closest('tr') || this.closest('tr');
        const tr = this.closest('tr');
        tr.querySelectorAll('.view-mode, .view-mode-desc').forEach(el => el.classList.add('d-none'));
        tr.querySelectorAll('input[type=text]').forEach(el => el.classList.remove('d-none'));
        this.classList.add('d-none');
        tr.querySelector('.save-btn').classList.remove('d-none');
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>