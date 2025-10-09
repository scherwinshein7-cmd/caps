<?php
session_start();
include '../includes/db_connection.php';

// ✅ Check if user logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// ✅ Handle bulk actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['selected_ids'])) {
    $ids = array_map('intval', $_POST['selected_ids']);
    $ids_str = implode(',', $ids);

    // Approve incoming
    if (isset($_POST['approve_selected'])) {
        mysqli_begin_transaction($conn);
        try {
            $res = mysqli_query($conn, "
                SELECT * FROM equipment_transfer 
                WHERE id IN ($ids_str)
                AND custody_to = '$user_id'
                AND approved_by_custody = 0
            ");
            while ($r = mysqli_fetch_assoc($res)) {
                mysqli_query($conn, "UPDATE equipment_transfer SET approved_by_custody=1 WHERE id='{$r['id']}'");
                mysqli_query($conn, "UPDATE equipment SET custody='{$r['custody_to']}', room='{$r['room_to']}' WHERE serial='{$r['serial']}'");
            }
            mysqli_commit($conn);
            $_SESSION['success'] = "Selected transfers approved.";
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $_SESSION['error'] = "Approval failed: " . $e->getMessage();
        }
    }

    // Decline incoming
    elseif (isset($_POST['decline_selected'])) {
        $q = "UPDATE equipment_transfer SET approved_by_custody=-1 
              WHERE id IN ($ids_str)
              AND custody_to='$user_id'
              AND approved_by_custody=0";
        mysqli_query($conn, $q);
        $_SESSION['error'] = "Selected transfers declined.";
    }

    // Cancel outgoing
    elseif (isset($_POST['cancel_selected'])) {
        $q = "DELETE FROM equipment_transfer 
              WHERE id IN ($ids_str)
              AND custody_from='$user_id'
              AND approved_by_custody=0";
        mysqli_query($conn, $q);
        $_SESSION['success'] = "Selected transfer requests cancelled.";
    }

    header("Location: transfer.php");
    exit();
}

// ✅ Fetch incoming (to you, pending)
$incoming = mysqli_query($conn, "
    SELECT t.*, 
           CONCAT(cf.firstname,' ',cf.lastname) AS custody_from_name,
           c.category AS category_name
    FROM equipment_transfer t
    LEFT JOIN account cf ON t.custody_from = cf.employee_id
    LEFT JOIN categories c ON t.category_id = c.id
    WHERE t.custody_to = '$user_id'
    AND t.approved_by_custody = 0
    ORDER BY t.transfer_date DESC
");

// ✅ Fetch outgoing (you to others, pending)
$outgoing = mysqli_query($conn, "
    SELECT t.*, 
           CONCAT(ct.firstname,' ',ct.lastname) AS custody_to_name,
           c.category AS category_name
    FROM equipment_transfer t
    LEFT JOIN account ct ON t.custody_to = ct.employee_id
    LEFT JOIN categories c ON t.category_id = c.id
    WHERE t.custody_from = '$user_id'
    AND t.approved_by_custody = 0
    ORDER BY t.transfer_date DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pending Transfers - ITDS</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="../css/style.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="dashboard-page">
<?php include 'header.php'; ?>

<div class="container mt-4">
    <h2 class="mb-4">Pending Equipment Transfers</h2>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php elseif (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <!-- ✅ INCOMING TRANSFERS -->
    <div class="card mb-5">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0"><i class="bi bi-download"></i> Incoming Transfers (To You)</h5>
        </div>
        <div class="card-body">
            <form method="POST">
                <div class="mb-3">
                    <button type="submit" name="approve_selected" class="btn btn-success me-2">
                        <i class="bi bi-check2-circle"></i> Approve Selected
                    </button>
                    <button type="submit" name="decline_selected" class="btn btn-warning">
                        <i class="bi bi-x-circle"></i> Decline Selected
                    </button>
                    <a href="receive_transfer.php" class="btn btn-outline-primary float-end">
                        <i class="bi bi-clock-history"></i> View Received History
                    </a>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped align-middle">
                        <thead class="table-light">
                            <tr>
                                <th><input type="checkbox" id="selectAllIncoming"></th>
                                <th>Serial</th>
                                <th>Description</th>
                                <th>Category</th>
                                <th>From Room</th>
                                <th>To Room</th>
                                <th>Custody From</th>
                                <th>Transfer Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($incoming) > 0): ?>
                                <?php while ($r = mysqli_fetch_assoc($incoming)): ?>
                                    <tr>
                                        <td><input type="checkbox" name="selected_ids[]" value="<?= $r['id'] ?>"></td>
                                        <td><?= $r['serial'] ?></td>
                                        <td><?= $r['description'] ?></td>
                                        <td><?= $r['category_name'] ?></td>
                                        <td><?= $r['room_from'] ?></td>
                                        <td><?= $r['room_to'] ?></td>
                                        <td><?= $r['custody_from_name'] ?></td>
                                        <td><?= $r['transfer_date'] ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="8" class="text-center">No pending incoming transfers.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </form>
        </div>
    </div>

    <!-- ✅ OUTGOING TRANSFERS -->
    <div class="card">
        <div class="card-header bg-secondary text-white">
            <h5 class="mb-0"><i class="bi bi-upload"></i> Ongoing Requests (You Sent)</h5>
        </div>
        <div class="card-body">
            <form method="POST">
                <div class="mb-3">
                    <button type="submit" name="cancel_selected" class="btn btn-danger">
                        <i class="bi bi-x-circle"></i> Cancel Selected
                    </button>
                    <a href="sent_transfer.php" class="btn btn-outline-primary float-end">
                        <i class="bi bi-clock-history"></i> View Sent History
                    </a>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped align-middle">
                        <thead class="table-light">
                            <tr>
                                <th><input type="checkbox" id="selectAllOutgoing"></th>
                                <th>Serial</th>
                                <th>Description</th>
                                <th>Category</th>
                                <th>From Room</th>
                                <th>To Room</th>
                                <th>Custody To</th>
                                <th>Transfer Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($outgoing) > 0): ?>
                                <?php while ($r = mysqli_fetch_assoc($outgoing)): ?>
                                    <tr>
                                        <td><input type="checkbox" name="selected_ids[]" value="<?= $r['id'] ?>"></td>
                                        <td><?= $r['serial'] ?></td>
                                        <td><?= $r['description'] ?></td>
                                        <td><?= $r['category_name'] ?></td>
                                        <td><?= $r['room_from'] ?></td>
                                        <td><?= $r['room_to'] ?></td>
                                        <td><?= $r['custody_to_name'] ?></td>
                                        <td><?= $r['transfer_date'] ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="8" class="text-center">No pending outgoing requests.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ✅ Select/Deselect all checkboxes separately
document.getElementById('selectAllIncoming')?.addEventListener('change', function() {
    document.querySelectorAll('input[name="selected_ids[]"]').forEach(chk => {
        if (chk.closest('.card').querySelector('#selectAllIncoming')) chk.checked = this.checked;
    });
});

document.getElementById('selectAllOutgoing')?.addEventListener('change', function() {
    document.querySelectorAll('input[name="selected_ids[]"]').forEach(chk => {
        if (chk.closest('.card').querySelector('#selectAllOutgoing')) chk.checked = this.checked;
    });
});
</script>
</body>
</html>
