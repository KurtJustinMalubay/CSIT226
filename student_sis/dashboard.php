<?php
session_start();
if (!isset($_SESSION['username'])) { header('Location: login.php'); exit; }
include 'connect.php';
$title = 'Dashboard';

// Stats
$total = mysqli_fetch_assoc(mysqli_query($connection,"SELECT COUNT(*) as c FROM tblstudent"))['c'] ?? 0;
$male  = mysqli_fetch_assoc(mysqli_query($connection,"SELECT COUNT(*) as c FROM tblstudent WHERE gender='Male'"))['c'] ?? 0;
$female= mysqli_fetch_assoc(mysqli_query($connection,"SELECT COUNT(*) as c FROM tblstudent WHERE gender='Female'"))['c'] ?? 0;

// Search/filter
$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$filter_program = isset($_GET['program']) ? trim($_GET['program']) : '';

$where = "WHERE 1=1";
$params = []; $types = "";
if ($search !== '') {
    $where .= " AND (firstname LIKE ? OR lastname LIKE ? OR idnumber LIKE ?)";
    $like = "%$search%";
    $params = array_merge($params, [$like, $like, $like]);
    $types .= "sss";
}
if ($filter_program !== '') {
    $where .= " AND program = ?";
    $params[] = $filter_program;
    $types .= "s";
}

$stmt = $connection->prepare("SELECT * FROM tblstudent $where ORDER BY id DESC");
if (!empty($params)) { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$result = $stmt->get_result();

// Programs for filter
$prog_list = mysqli_query($connection,"SELECT DISTINCT program FROM tblstudent ORDER BY program");

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
    <div class="card card-sm" style="display:flex;align-items:center;gap:16px;">
        <div class="feature-icon" style="margin:0;flex-shrink:0;background:rgba(59,130,246,0.15);border-color:rgba(59,130,246,0.3);color:#60A5FA;"><i class="fas fa-mars"></i></div>
        <div><div style="font-size:26px;font-weight:800;"><?php echo $male; ?></div><div style="font-size:12px;color:var(--text-muted)">Male</div></div>
    </div>
    <div class="card card-sm" style="display:flex;align-items:center;gap:16px;">
        <div class="feature-icon" style="margin:0;flex-shrink:0;background:rgba(236,72,153,0.15);border-color:rgba(236,72,153,0.3);color:#F472B6;"><i class="fas fa-venus"></i></div>
        <div><div style="font-size:26px;font-weight:800;"><?php echo $female; ?></div><div style="font-size:12px;color:var(--text-muted)">Female</div></div>
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
            <select name="program" style="background:var(--bg3);border:1px solid var(--border);border-radius:10px;padding:9px 14px;color:var(--text);font-size:14px;outline:none;appearance:none;">
                <option value="">All Programs</option>
                <?php while($p = mysqli_fetch_assoc($prog_list)): ?>
                <option value="<?php echo htmlspecialchars($p['program']); ?>" <?php echo $filter_program===$p['program']?'selected':''; ?>>
                    <?php echo htmlspecialchars($p['program']); ?>
                </option>
                <?php endwhile; ?>
            </select>
            <button type="submit" class="btn btn-outline btn-sm"><i class="fas fa-filter"></i> Filter</button>
            <?php if ($search||$filter_program): ?>
            <a href="dashboard.php" class="btn btn-outline btn-sm"><i class="fas fa-xmark"></i> Clear</a>
            <?php endif; ?>
        </form>
        <span style="font-size:13px;color:var(--text-muted);"><?php echo $result->num_rows; ?> records found</span>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>ID Number</th>
                <th>Full Name</th>
                <th>Gender</th>
                <th>Program</th>
                <th>Contact</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($result->num_rows === 0): ?>
            <tr><td colspan="7" class="table-empty"><i class="fas fa-users-slash"></i>No student records found.</td></tr>
        <?php else: $i=1; while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td style="color:var(--text-muted);"><?php echo $i++; ?></td>
                <td class="td-bold"><?php echo htmlspecialchars($row['idnumber'] ?? '—'); ?></td>
                <td class="td-bold"><?php echo htmlspecialchars($row['firstname'].' '.$row['lastname']); ?></td>
                <td>
                    <?php if ($row['gender']==='Male'): ?>
                        <span class="badge badge-male"><i class="fas fa-mars"></i> Male</span>
                    <?php elseif ($row['gender']==='Female'): ?>
                        <span class="badge badge-female"><i class="fas fa-venus"></i> Female</span>
                    <?php else: echo '—'; endif; ?>
                </td>
                <td><span class="badge badge-program"><?php echo htmlspecialchars($row['program'] ?? '—'); ?></span></td>
                <td style="color:var(--text-muted);"><?php echo htmlspecialchars($row['contactno'] ?? '—'); ?></td>
                <td>
                    <div class="td-actions">
                        <a href="update.php?id=<?php echo $row['id']; ?>" class="btn btn-outline btn-sm"><i class="fas fa-pen"></i> Edit</a>
                        <a href="delete.php?id=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm"
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
