<?php
require_once '../db.php';

// Handle Add
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $name = trim($_POST['name']);
    $department_id = intval($_POST['department_id']);
    if ($name !== '') {
        $stmt = $conn->prepare("INSERT INTO categories (name, department_id) VALUES (?, ?)");
        $stmt->bind_param("si", $name, $department_id);
        $stmt->execute();
    }
    header("Location: categories.php");
    exit();
}

// Handle Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit'])) {
    $id = intval($_POST['id']);
    $name = trim($_POST['name']);
    $department_id = intval($_POST['department_id']);
    $stmt = $conn->prepare("UPDATE categories SET name = ?, department_id = ? WHERE id = ?");
    $stmt->bind_param("sii", $name, $department_id, $id);
    $stmt->execute();
    header("Location: categories.php");
    exit();
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: categories.php");
    exit();
}

require_once 'includes/header.php';

// Get all departments for dropdown
$departments = $conn->query("SELECT id, name FROM departments ORDER BY name");
$deptList = [];
while ($d = $departments->fetch_assoc()) {
    $deptList[$d['id']] = $d['name'];
}

// Get all categories with department name
$result = $conn->query("
    SELECT c.id, c.name, c.department_id, d.name AS dept_name
    FROM categories c
    LEFT JOIN departments d ON d.id = c.department_id
    ORDER BY c.id DESC
");
?>

<div class="card p-4 mb-3">
    <h5 class="mb-3">Add Category</h5>
    <form method="POST" class="row g-2">
        <div class="col-md-4">
            <input type="text" name="name" class="form-control" placeholder="Category name" required>
        </div>
        <div class="col-md-6">
            <select name="department_id" class="form-select" required>
                <option value="">-- Select Department --</option>
                <?php foreach ($deptList as $id => $name): ?>
                    <option value="<?= $id ?>"><?= htmlspecialchars($name) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" name="add" class="btn btn-primary w-100">Add</button>
        </div>
    </form>
</div>

<div class="card p-4">
    <h5 class="mb-3">All Categories</h5>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Department</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
<tr>
    <form method="POST" id="editForm<?= $row['id'] ?>" class="d-none"></form>

    <td><?= $row['id'] ?></td>
    <td>
        <span class="view-mode"><?= htmlspecialchars($row['name']) ?></span>
        <input type="hidden" name="id" value="<?= $row['id'] ?>" form="editForm<?= $row['id'] ?>">
        <input type="text" name="name" class="form-control form-control-sm mb-1 edit-mode d-none"
               value="<?= htmlspecialchars($row['name']) ?>" required
               form="editForm<?= $row['id'] ?>">
    </td>
    <td>
        <span class="view-mode-dept"><?= htmlspecialchars($row['dept_name'] ?? '—') ?></span>
        <select name="department_id" class="form-select form-select-sm edit-mode-dept d-none"
                form="editForm<?= $row['id'] ?>">
            <?php foreach ($deptList as $id => $name): ?>
                <option value="<?= $id ?>" <?= $id == $row['department_id'] ? 'selected' : '' ?>><?= htmlspecialchars($name) ?></option>
            <?php endforeach; ?>
        </select>
    </td>
    <td>
        <button type="button" class="btn btn-sm btn-warning edit-btn">Edit</button>
        <button type="submit" name="edit" class="btn btn-sm btn-success save-btn d-none"
                form="editForm<?= $row['id'] ?>">Save</button>
        <a href="categories.php?delete=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this category?')">Delete</a>
    </td>
</tr>
<?php endwhile; ?>
        </tbody>
    </table>
</div>

<script>
document.querySelectorAll('.edit-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const tr = this.closest('tr');
        tr.querySelectorAll('.view-mode, .view-mode-dept').forEach(el => el.classList.add('d-none'));
        tr.querySelectorAll('.edit-mode, .edit-mode-dept').forEach(el => {
            el.classList.remove('d-none');
            el.classList.add('d-flex');
        });
        this.classList.add('d-none');
        tr.querySelector('.save-btn').classList.remove('d-none');
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>