<?php
session_start();
include '../includes/db_connection.php';

// ✅ Check if admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header('Location: login.php');
    exit();
}

$type = $_GET['type'] ?? '';
$value = $_GET['value'] ?? '';

$title = '';
$query = '';

if ($type === 'faculty') {
    // ✅ Faculty listing with correct columns
    $title = "List of Faculty Members";
    $query = "SELECT employee_id, lastname, firstname, middlename, gender, phonenumber, address 
              FROM account 
              WHERE role = 'faculty'";
} 
elseif ($type === 'category' && !empty($value)) {
    // ✅ Equipment listing by category
    $title = "Equipment under Category: $value";
    $query = "SELECT e.serial, e.description, e.unit_value, e.remarks
              FROM equipment e
              JOIN categories c ON e.category_id = c.id
              WHERE c.category = '$value'";
}
elseif ($type === 'remarks' && !empty($value)) {
    // ✅ Equipment listing by remarks
    $title = "Equipment with Remarks: $value";
    $query = "SELECT serial, description, unit_value, remarks 
              FROM equipment 
              WHERE remarks = '$value'";
}
else {
    $title = "Invalid Request";
}

$result = $conn->query($query);
?>
<?php include 'header.php'; ?>
<body>
<div class="container mt-5">
    <h4><?php echo htmlspecialchars($title); ?></h4>
    <a href="admin_dashboard.php" class="btn btn-secondary mb-3">← Back to Dashboard</a>

    <?php if ($result && $result->num_rows > 0): ?>
        <table class="table table-bordered table-hover">
            <thead class="table-dark">
                <tr>
                    <?php if ($type === 'faculty'): ?>
                        <th>Employee ID</th>
                        <th>Full Name</th>
                        <th>Gender</th>
                        <th>Phone Number</th>
                        <th>Address</th>
                    <?php else: ?>
                        <th>Serial</th>
                        <th>Description</th>
                        <th>Unit Value</th>
                        <th>Remarks</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <?php if ($type === 'faculty'): ?>
                            <?php
                                $fullname = htmlspecialchars($row['lastname'] . ', ' . $row['firstname'] . ' ' . $row['middlename']);
                            ?>
                            <td><?php echo htmlspecialchars($row['employee_id']); ?></td>
                            <td><?php echo $fullname; ?></td>
                            <td><?php echo htmlspecialchars($row['gender']); ?></td>
                            <td><?php echo htmlspecialchars($row['phonenumber']); ?></td>
                            <td><?php echo htmlspecialchars($row['address']); ?></td>
                        <?php else: ?>
                            <td><?php echo htmlspecialchars($row['serial']); ?></td>
                            <td><?php echo htmlspecialchars($row['description']); ?></td>
                            <td>₱ <?php echo number_format($row['unit_value'], 2); ?></td>
                            <td><?php echo htmlspecialchars($row['remarks']); ?></td>
                        <?php endif; ?>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="alert alert-warning">No records found.</div>
    <?php endif; ?>
</div>
</body>
</html>
<?php include 'footer.php'; ?>
