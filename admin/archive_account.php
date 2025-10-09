<?php
session_start();
include '../includes/db_connection.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header('Location: login.php');
    exit();
}

// Fetch archived accounts
$sql = "SELECT * FROM account_archive";
$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archived Accounts</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="p-4">

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2><i class="bi bi-archive"></i> Archived Accounts</h2>
        <a href="account.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to Accounts
        </a>
    </div>

    <div class="table-responsive">
        <table class="table table-hover table-bordered align-middle text-center">
            <thead class="table-warning">
                <tr>
                    <th>Employee ID</th>
                    <th>Role</th>
                    <th>Department</th>
                    <th>Last Name</th>
                    <th>First Name</th>
                    <th>Middle Name</th>
                    <th>Gender</th>
                    <th>Address</th>
                    <th>Phone Number</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?= $row['employee_id'] ?></td>
                            <td><?= $row['role'] ?></td>
                            <td><?= $row['department'] ?></td>
                            <td><?= $row['lastname'] ?></td>
                            <td><?= $row['firstname'] ?></td>
                            <td><?= $row['middlename'] ?></td>
                            <td><?= $row['gender'] ?></td>
                            <td><?= $row['address'] ?></td>
                            <td><?= $row['phonenumber'] ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="9">No archived accounts found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
