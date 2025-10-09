<?php
session_start();
include '../includes/db_connection.php';

// ✅ Only logged-in users
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// ✅ Dropdown data
$custodyQuery = mysqli_query($conn, "SELECT employee_id, CONCAT(firstname, ' ', lastname) AS name FROM account ORDER BY name");
$categoryQuery = mysqli_query($conn, "SELECT id, category FROM categories ORDER BY category");

// ✅ Filters
$where = [];
$filter_custody = $_GET['custody'] ?? '';
$filter_type = $_GET['type'] ?? '';
$filter_category = $_GET['category'] ?? '';

if (!empty($filter_custody) && !empty($filter_type)) {
    if ($filter_type == 'accepted') {
        $where[] = "t.custody_to = '" . mysqli_real_escape_string($conn, $filter_custody) . "'";
    } elseif ($filter_type == 'request') {
        $where[] = "t.custody_from = '" . mysqli_real_escape_string($conn, $filter_custody) . "'";
    }
}

if (!empty($filter_category)) {
    $where[] = "t.category_id = '" . mysqli_real_escape_string($conn, $filter_category) . "'";
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// ✅ Pagination Setup
$limit = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// ✅ Count total rows with filter
$countSql = "SELECT COUNT(*) AS total FROM equipment_transfer t $whereSql";
$countResult = mysqli_query($conn, $countSql);
$totalRows = mysqli_fetch_assoc($countResult)['total'];
$totalPages = ceil($totalRows / $limit);

// ✅ Fetch filtered results
$sql = "SELECT t.*, 
            CONCAT(cf.firstname,' ',cf.lastname) AS custody_from_name,
            CONCAT(ct.firstname,' ',ct.lastname) AS custody_to_name,
            c.category AS category_name
        FROM equipment_transfer t
        LEFT JOIN account cf ON t.custody_from = cf.employee_id
        LEFT JOIN account ct ON t.custody_to = ct.employee_id
        LEFT JOIN categories c ON t.category_id = c.id
        $whereSql
        ORDER BY t.transfer_date DESC
        LIMIT $offset, $limit";

$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Custody Transfer Requests</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
<?php include 'header.php'; ?>

<div class="container mt-4">

    <!-- ✅ Filter Form -->
    <form method="get" class="row g-3 mb-4">
        <div class="col-md-4">
            <label class="form-label fw-bold">Custody</label>
            <select name="custody" class="form-select">
                <option value="">-- Select Custody --</option>
                <?php while ($c = mysqli_fetch_assoc($custodyQuery)): ?>
                    <option value="<?= $c['employee_id']; ?>" <?= ($filter_custody == $c['employee_id']) ? 'selected' : ''; ?>>
                        <?= htmlspecialchars($c['name']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="col-md-4">
            <label class="form-label fw-bold">Request Type</label>
            <select name="type" class="form-select">
                <option value="">-- Select Type --</option>
                <option value="accepted" <?= ($filter_type == 'accepted') ? 'selected' : ''; ?>>Accepted</option>
                <option value="request" <?= ($filter_type == 'request') ? 'selected' : ''; ?>>Request</option>
            </select>
        </div>

        <div class="col-md-4">
            <label class="form-label fw-bold">Category</label>
            <select name="category" class="form-select">
                <option value="">-- Select Category --</option>
                <?php while ($cat = mysqli_fetch_assoc($categoryQuery)): ?>
                    <option value="<?= $cat['id']; ?>" <?= ($filter_category == $cat['id']) ? 'selected' : ''; ?>>
                        <?= htmlspecialchars($cat['category']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="col-12 text-end">
            <button type="submit" class="btn btn-primary"><i class="bi bi-funnel"></i> Apply Filter</button>
            <a href="<?= basename(__FILE__) ?>" class="btn btn-secondary"><i class="bi bi-arrow-clockwise"></i> Reset</a>
        </div>
    </form>

    <!-- ✅ Alerts -->
    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']); ?></div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']); ?></div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <!-- ✅ Table -->
    <div class="table-responsive">
        <table class="table table-bordered table-striped align-middle">
            <thead class="table-dark">
                <tr>
                    <th>Serial</th>
                    <th>Description</th>
                    <th>Category</th>
                    <th>From Room</th>
                    <th>To Room</th>
                    <th>Custody From</th>
                    <th>Custody To</th>
                    <th>Transfer Date</th>
                    <th>Custody Approval</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <?php
                        $custodyStatus = $row['approved_by_custody'] == 1 ? "<span class='badge bg-success'>Approved</span>" :
                                         ($row['approved_by_custody'] == -1 ? "<span class='badge bg-danger'>Declined</span>" :
                                         "<span class='badge bg-warning text-dark'>Pending</span>");
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($row['serial']); ?></td>
                            <td><?= htmlspecialchars($row['description']); ?></td>
                            <td><?= htmlspecialchars($row['category_name']); ?></td>
                            <td><?= htmlspecialchars($row['room_from']); ?></td>
                            <td><?= htmlspecialchars($row['room_to']); ?></td>
                            <td><?= htmlspecialchars($row['custody_from_name']); ?></td>
                            <td><?= htmlspecialchars($row['custody_to_name']); ?></td>
                            <td><?= htmlspecialchars($row['transfer_date']); ?></td>
                            <td><?= $custodyStatus; ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="9" class="text-center">No transfer requests found</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- ✅ Pagination -->
    <?php if ($totalPages > 1): ?>
        <nav>
            <ul class="pagination justify-content-center">
                <?php for ($i=1; $i<=$totalPages; $i++): ?>
                    <li class="page-item <?= ($i==$page)?'active':'' ?>">
                        <a class="page-link" href="?page=<?= $i ?>&custody=<?= urlencode($filter_custody) ?>&type=<?= urlencode($filter_type) ?>&category=<?= urlencode($filter_category) ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
</body>
</html>
