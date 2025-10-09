<?php
session_start();
include '../includes/db_connection.php';

// ✅ Check login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// ✅ Fetch transfers you received (approved or declined)
$sql = "
    SELECT t.*, 
           CONCAT(cf.firstname, ' ', cf.lastname) AS custody_from_name,
           c.category AS category_name
    FROM equipment_transfer t
    LEFT JOIN account cf ON t.custody_from = cf.employee_id
    LEFT JOIN categories c ON t.category_id = c.id
    WHERE t.custody_to = '$user_id'
    AND t.approved_by_custody != 0
    ORDER BY t.transfer_date DESC
";
$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Received Transfers - ITDS</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="../css/style.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="dashboard-page">
<?php include 'header.php'; ?>

<div class="container mt-4">
    <h2><i class="bi bi-download"></i> Transfer History (Received)</h2>
    <a href="transfer.php" class="btn btn-outline-secondary mb-3">
        <i class="bi bi-arrow-left"></i> Back to Pending Transfers
    </a>

    <div class="table-responsive">
        <table class="table table-bordered table-striped align-middle">
            <thead class="table-light">
                <tr>
                    <th>Serial</th>
                    <th>Description</th>
                    <th>Category</th>
                    <th>From Room</th>
                    <th>To Room</th>
                    <th>Custody From</th>
                    <th>Transfer Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <?php
                            $status = $row['approved_by_custody'] == 1
                                ? "<span class='badge bg-success'>Approved</span>"
                                : "<span class='badge bg-danger'>Declined</span>";
                        ?>
                        <tr>
                            <td><?= $row['serial'] ?></td>
                            <td><?= $row['description'] ?></td>
                            <td><?= $row['category_name'] ?></td>
                            <td><?= $row['room_from'] ?></td>
                            <td><?= $row['room_to'] ?></td>
                            <td><?= $row['custody_from_name'] ?></td>
                            <td><?= $row['transfer_date'] ?></td>
                            <td><?= $status ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="8" class="text-center">No received transfers found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
