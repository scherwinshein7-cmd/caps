<?php
session_start();
include '../includes/db_connection.php';

// ✅ MIS-only access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'MIS') {
    header('Location: login.php');
    exit();
}

$mis_name = $_SESSION['firstname'] . ' ' . $_SESSION['lastname'];

// ✅ Handle Accept Group (commit final condemnation)
if (isset($_POST['accept_group'])) {
    $requested_by = mysqli_real_escape_string($conn, $_POST['requested_by']);
    $date_requested = mysqli_real_escape_string($conn, $_POST['date_requested']);

    // 🔍 Get all serials under this group that are approved by MIS(Main)
    $fetchQuery = "
        SELECT serial 
        FROM condemn_request 
        WHERE requested_by = '$requested_by' 
          AND date_requested = '$date_requested'
          AND approved_by_mis_main IS NOT NULL
          AND request_condemn = 1
    ";
    $result = mysqli_query($conn, $fetchQuery);

    $serials = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $serials[] = $row['serial'];
    }

    if (!empty($serials)) {
        $serialList = "'" . implode("','", $serials) . "'";

        // ✅ Update condemn_request (finalize)
        $updateRequest = "
            UPDATE condemn_request 
            SET 
                request_condemn = 0,
                date_approved = NOW()
            WHERE serial IN ($serialList)
        ";
        mysqli_query($conn, $updateRequest);

        // ✅ Update equipment remarks
        $updateEquipment = "
            UPDATE equipment 
            SET remarks = 'Condemned'
            WHERE serial IN ($serialList)
        ";
        mysqli_query($conn, $updateEquipment);

        $_SESSION['success'] = "Condemnation requests finalized and equipment marked as 'Condemned'.";
    } else {
        $_SESSION['error'] = "No valid items to accept for this group.";
    }

    header("Location: condemn_main.php");
    exit();
}

// ✅ Fetch grouped requests that already have approved_by_mis_main
$query = "
SELECT 
    cr.requested_by, 
    cr.date_requested,
    GROUP_CONCAT(cr.serial) AS serials,
    MAX(cr.approved_by_mis_main) AS approved_by_mis_main,
    MIN(cr.request_condemn) AS request_status
FROM condemn_request cr
WHERE cr.approved_by_mis_main IS NOT NULL
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
<title>Condemnation Requests - MIS</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="../css/style.css" rel="stylesheet">
<link href="../css/dashboard.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
<?php include 'header.php'; ?>

<div class="container mt-4">
    <h2 class="mb-3"><i class="bi bi-hammer"></i> Condemnation Requests (Approved by MIS Main)</h2>

    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php elseif (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <?php if (mysqli_num_rows($groups) > 0): ?>
        <?php while ($group = mysqli_fetch_assoc($groups)): ?>
            <?php 
            $requested_by = htmlspecialchars($group['requested_by']);
            $date_requested = htmlspecialchars($group['date_requested']);
            $approved_by = htmlspecialchars($group['approved_by_mis_main']);
            $serials = explode(',', $group['serials']);
            $isAccepted = ($group['request_status'] == 0); // means already accepted/finalized

            // ✅ Fetch equipment for this group
            $equipQuery = "
                SELECT e.serial, e.description, e.room, e.unit_value, e.remarks, c.category AS category_name
                FROM equipment e
                LEFT JOIN categories c ON e.category_id = c.id
                WHERE e.serial IN ('" . implode("','", $serials) . "')
            ";
            $equipResult = mysqli_query($conn, $equipQuery);
            ?>

            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <div>
                        <strong>Requested by:</strong> <?= $requested_by ?><br>
                        <small>Requested on: <?= date("F d, Y h:i A", strtotime($date_requested)) ?></small><br>
                        <small><i class="bi bi-person-check"></i> Approved by MIS(Main): <?= $approved_by ?></small>
                    </div>

                    <div class="d-flex gap-2">
                        <?php if (!$isAccepted): ?>
                            <form method="POST" onsubmit="return confirm('Accept all approved items for <?= $requested_by ?>?');">
                                <input type="hidden" name="requested_by" value="<?= $requested_by ?>">
                                <input type="hidden" name="date_requested" value="<?= $date_requested ?>">
                                <button type="submit" name="accept_group" class="btn btn-success btn-sm">
                                    <i class="bi bi-check-circle"></i> Accept All
                                </button>
                            </form>
                        <?php else: ?>
                            <span class="badge bg-success"><i class="bi bi-check-circle"></i> Accepted</span>
                            <a href="rssp.php?requested_by=<?= urlencode($requested_by) ?>&date_requested=<?= urlencode($date_requested) ?>" 
                               target="_blank" class="btn btn-outline-light btn-sm border">
                                <i class="bi bi-filetype-pdf"></i> Generate RSSP
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped mb-0">
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
                                <?php while ($row = mysqli_fetch_assoc($equipResult)): ?>
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
                                                default => 'secondary'
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
        <div class="alert alert-info">No records approved by MIS(Main) yet.</div>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
