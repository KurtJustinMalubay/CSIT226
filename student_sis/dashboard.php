<?php
session_start();
if (!isset($_SESSION['username'])) { header('Location: login.php'); exit; }
include 'connect.php';
$title = 'Dashboard';

// Stats
$total = mysqli_fetch_assoc(mysqli_query($connection,"SELECT COUNT(*) as c FROM student"))['c'] ?? 0;

// Search/filter
$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$filter_course = isset($_GET['course']) ? trim($_GET['course']) : '';

$where = "WHERE 1=1";
$params = []; $types = "";
if ($search !== '') {
    $where .= " AND (studId LIKE ? OR course LIKE ?)";
    $like = "%$search%";
    $params = array_merge($params, [$like, $like]);
    $types .= "ss";
}
if ($filter_course !== '') {
    $where .= " AND course = ?";
    $params[] = $filter_course;
    $types .= "s";
}

$stmt = $connection->prepare("SELECT * FROM student $where ORDER BY studId DESC");
if (!empty($params)) { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$result = $stmt->get_result();

// Courses for filter
$course_list = mysqli_query($connection,"SELECT DISTINCT course FROM student ORDER BY course");

// Flash messages
$flash = isset($_SESSION['flash']) ? $_SESSION['flash'] : null;
unset($_SESSION['flash']);

require_once 'includes/header.php';
?>

<div class="page-header">
    <div class="page-title">
        <div class="breadcrumb">
            <a href="index.php">Home</a>
            <span class="breadcrumb-sep">/</span>
            <span>Dashboard</span>
        </div>
        <h1>Student Records</h1>
        <p>Welcome back, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></p>
    </div>
    <a href="addrecord.php" class="btn btn-primary">
        <i class="fas fa-user-plus"></i> Add New Student
    </a>
</div>

<!-- Stats row -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:16px;margin-bottom:28px;">
    <div class="card card-sm" style="display:flex;align-items:center;gap:16px;">
        <div class="feature-icon" style="margin:0;flex-shrink:0;"><i class="fas fa-users"></i></div>
        <div><div style="font-size:26px;font-weight:800;"><?php echo $total; ?></div><div style="font-size:12px;color:var(--text-muted)">Total Students</div></div>
    </div>
</div>

<?php if ($flash): ?>
<div class="alert alert-<?php echo $flash['type']; ?>">
    <i class="fas fa-<?php echo $flash['type']==='success'?'circle-check':'circle-exclamation'; ?>"></i>
    <?php echo htmlspecialchars($flash['msg']); ?>
</div>
<?php endif; ?>

<div class="table-wrap">
    <div class="table-toolbar">
        <form method="get" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
            <div class="table-search">
                <i class="fas fa-magnifying-glass"></i>
                <input type="text" name="q" placeholder="Search students..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
        </form>
        <span style="font-size:13px;color:var(--text-muted);"><?php echo $result->num_rows; ?> records found</span>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Student ID</th>
                <th>Course</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($result->num_rows === 0): ?>
            <tr><td colspan="4" class="table-empty"><i class="fas fa-users-slash"></i>No student records found.</td></tr>
        <?php else: $i=1; while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td style="color:var(--text-muted);"><?php echo $i++; ?></td>
                <td class="td-bold"><?php echo htmlspecialchars($row['studId']); ?></td>
                <td><span class="badge badge-program"><?php echo htmlspecialchars($row['course'] ?? '—'); ?></span></td>
                <td>
                    <div class="td-actions">
                        <a href="update.php?id=<?php echo $row['studId']; ?>" class="btn btn-outline btn-sm"><i class="fas fa-pen"></i> Edit</a>
                        <a href="delete.php?id=<?php echo $row['studId']; ?>" class="btn btn-danger btn-sm"
                           onclick="return confirm('Delete this student record?')">
                           <i class="fas fa-trash"></i>
                        </a>
                    </div>
                </td>
            </tr>
        <?php endwhile; endif; ?>
        </tbody>
    </table>
</div>

<?php require_once 'includes/footer.php'; ?>
