<?php
session_start();
include '../includes/db_connection.php';

// ✅ MIS-only access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header('Location: login.php');
    exit();
}

// ✅ Fetch all condemned requests (accepted ones only)
$query = "
SELECT 
    cr.requested_by,
    cr.date_requested,
    GROUP_CONCAT(cr.id) AS request_ids
FROM condemn_request cr
JOIN equipment e ON cr.serial = e.serial
WHERE cr.request_condemn = 0
GROUP BY cr.requested_by, cr.date_requested
ORDER BY cr.date_requested DESC
";
$groups = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="icon" type="image/png" href="logo.png">
<title>Condemned Equipment - Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="../css/style.css" rel="stylesheet">
<link href="../css/dashboard.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
<?php include 'header.php'; ?>

<div class="container mt-4">
    <h2 class="mb-3"><i class="bi bi-archive"></i> Condemned Equipment (Admin View)</h2>

    <?php if (mysqli_num_rows($groups) > 0): ?>
        <?php while ($group = mysqli_fetch_assoc($groups)): ?>
            <?php 
            $requested_by = htmlspecialchars($group['requested_by']);
            $date_requested = htmlspecialchars($group['date_requested']);
            $request_ids = explode(',', $group['request_ids']);

            // ✅ Fetch condemned equipment for this group
            $equipmentQuery = "
                SELECT e.serial, e.description, e.room, e.unit_value, e.remarks, c.category AS category_name
                FROM condemn_request cr
                JOIN equipment e ON cr.serial = e.serial
                LEFT JOIN categories c ON e.category_id = c.id
                WHERE cr.id IN (" . implode(',', $request_ids) . ")
            ";
            $equipmentResult = mysqli_query($conn, $equipmentQuery);
            ?>

            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                    <div>
                        <strong>Custody:</strong> <?= $requested_by ?><br>
                        <small>
                            Condemned on: <?= date("F d, Y h:i A", strtotime($date_requested)) ?>
                        </small>
                    </div>

                    <!-- ✅ Generate PDF button -->
                    <div>
                        <a href="rssp.php?requested_by=<?= urlencode($requested_by) ?>&date_requested=<?= urlencode($date_requested) ?>" 
                           target="_blank" class="btn btn-outline-light btn-sm border">
                            <i class="bi bi-filetype-pdf"></i> Generate PDF
                        </a>
                    </div>
                </div>

                <!-- ✅ Equipment Table -->
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Serial</th>
                                    <th>Description</th>
                                    <th>Category</th>
                                    <th>Room</th>
                                    <th>Unit Value</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = mysqli_fetch_assoc($equipmentResult)): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['serial']) ?></td>
                                        <td><?= htmlspecialchars($row['description']) ?></td>
                                        <td><?= htmlspecialchars($row['category_name'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($row['room']) ?></td>
                                        <td>₱ <?= number_format($row['unit_value'], 2) ?></td>
                                        <td>
                                            <?php 
                                                $badge = match(strtolower($row['remarks'])) {
                                                    'functional' => 'success',
                                                    'outdated' => 'warning',
                                                    'defective' => 'danger',
                                                    'condemned' => 'dark',
                                                    default => 'secondary',
                                                };
                                            ?>
                                            <span class="badge bg-<?= $badge ?>"><?= ucfirst($row['remarks']) ?></span>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="alert alert-info">No condemned equipment records found.</div>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
