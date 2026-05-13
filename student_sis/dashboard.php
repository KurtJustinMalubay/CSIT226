<?php
session_start();
if (!isset($_SESSION['username'])) { header('Location: login.php'); exit; }
include 'connect.php';
$title = 'Dashboard';

function lf_student_pick_column(mysqli $connection, string $table, array $candidates): ?string {
    $schema = mysqli_fetch_assoc(mysqli_query($connection, "SELECT DATABASE() AS db"))['db'] ?? null;
    if (!$schema) return null;

    $stmt = $connection->prepare(
        "SELECT COUNT(*) AS c
         FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?"
    );

    foreach ($candidates as $col) {
        $stmt->bind_param("sss", $schema, $table, $col);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        if (($row['c'] ?? 0) > 0) { $stmt->close(); return $col; }
    }
    $stmt->close();
    return null;
}

$studentTable = "student";
$userTable = "user";
$colStudentUserId = lf_student_pick_column($connection, $studentTable, ["studId","studentId","uid","userId"]);
$colCourse  = lf_student_pick_column($connection, $studentTable, ["course","program"]);
$colYear    = lf_student_pick_column($connection, $studentTable, ["yearLevel","yearlvl","year_level","year"]);
$colUserId      = lf_student_pick_column($connection, $userTable, ["uid","id"]);
$colUserFname   = lf_student_pick_column($connection, $userTable, ["fname","firstname","first_name"]);
$colUserLname   = lf_student_pick_column($connection, $userTable, ["lname","lastname","last_name"]);
$colUserUniId   = lf_student_pick_column($connection, $userTable, ["universityId","studId","idnumber"]);

if (!$colStudentUserId || !$colCourse || !$colYear || !$colUserId) {
    die("Missing required schema columns in `student`/`user` tables.");
}

// Stats
$total = mysqli_fetch_assoc(mysqli_query($connection,"SELECT COUNT(*) as c FROM student"))['c'] ?? 0;

// Search/filter
$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$filter_program = isset($_GET['course']) ? trim($_GET['course']) : '';

$where = "WHERE 1=1";
$params = []; $types = "";
if ($search !== '') {
    $where .= " AND (s.`$colCourse` LIKE ? OR s.`$colYear` LIKE ?)";
    $like = "%$search%";
    $params = array_merge($params, [$like, $like]);
    $types .= "ss";
}
if ($filter_program !== '') {
    $where .= " AND s.`$colCourse` = ?";
    $params[] = $filter_program;
    $types .= "s";
}

$nameExpr = "''";
if ($colUserFname && $colUserLname) {
    $nameExpr = "CONCAT(u.`$colUserFname`, ' ', u.`$colUserLname`)";
}
$uniExpr = $colUserUniId ? "u.`$colUserUniId`" : "s.`$colStudentUserId`";

$stmt = $connection->prepare(
    "SELECT s.*, s.`$colStudentUserId` AS __student_key, $nameExpr AS full_name, $uniExpr AS student_code
     FROM `$studentTable` s
     LEFT JOIN `$userTable` u ON s.`$colStudentUserId` = u.`$colUserId`
     $where
     ORDER BY s.`$colStudentUserId` DESC"
);
if (!empty($params)) { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$result = $stmt->get_result();

// Programs for filter
$course_list = mysqli_query($connection,"SELECT DISTINCT `$colCourse` AS course_name FROM `$studentTable` ORDER BY `$colCourse`");

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
                <th>Program</th>
                <th>Year Level</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($result->num_rows === 0): ?>
            <tr><td colspan="5" class="table-empty"><i class="fas fa-users-slash"></i>No student records found.</td></tr>
        <?php else: $i=1; while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td style="color:var(--text-muted);"><?php echo $i++; ?></td>
                <td class="td-bold"><?php echo htmlspecialchars($row['student_code'] ?? $row['__student_key']); ?></td>
                <td><span class="badge badge-program"><?php echo htmlspecialchars($row[$colCourse] ?? '—'); ?></span></td>
                <td><?php echo htmlspecialchars($row[$colYear] ?? '—'); ?></td>
                <td>
                    <div class="td-actions">
                        <a href="update.php?id=<?php echo $row['__student_key']; ?>" class="btn btn-outline btn-sm"><i class="fas fa-pen"></i> Edit</a>
                        <a href="delete.php?id=<?php echo $row['__student_key']; ?>" class="btn btn-danger btn-sm"
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
