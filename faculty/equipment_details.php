<?php
session_start();
include '../includes/db_connection.php';

// ✅ Check login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if (empty($_GET['serial'])) {
    die("No equipment serial provided.");
}

$serial = mysqli_real_escape_string($conn, $_GET['serial']);

// ✅ Fetch equipment details
$eqSql = "SELECT e.*, c.category AS category_name, 
                 CONCAT(a.firstname,' ',a.lastname) AS custody_name
          FROM equipment e
          LEFT JOIN categories c ON e.category_id = c.id
          LEFT JOIN account a ON e.custody = a.employee_id
          WHERE e.serial = '$serial' LIMIT 1";

$eqResult = mysqli_query($conn, $eqSql);
$equipment = mysqli_fetch_assoc($eqResult);

if (!$equipment) {
    die("Equipment not found.");
}

// ✅ Fetch transfer history
$transferSql = "SELECT t.*, 
                       cf.firstname AS from_first, cf.lastname AS from_last,
                       ct.firstname AS to_first, ct.lastname AS to_last,
                       cat.category AS category_name
                FROM equipment_transfer t
                LEFT JOIN account cf ON t.custody_from = cf.employee_id
                LEFT JOIN account ct ON t.custody_to = ct.employee_id
                LEFT JOIN categories cat ON t.category_id = cat.id
                WHERE t.serial = '$serial'
                ORDER BY t.transfer_date DESC";

$transferResult = mysqli_query($conn, $transferSql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Equipment Details - <?= htmlspecialchars($serial) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-4">
  <a href="equipment.php" class="btn btn-secondary mb-3"><i class="bi bi-arrow-left"></i> Back</a>

  <div class="card shadow-sm mb-4">
    <div class="card-header bg-primary text-white">
      <h4 class="mb-0">Equipment Details</h4>
    </div>
    <div class="card-body">
      <div class="row">
        <div class="col-md-6">
          <p><strong>Serial:</strong> <?= htmlspecialchars($equipment['serial']) ?></p>
          <p><strong>Description:</strong> <?= htmlspecialchars($equipment['description']) ?></p>
          <p><strong>Category:</strong> <?= htmlspecialchars($equipment['category_name']) ?></p>
          <p><strong>Room:</strong> <?= htmlspecialchars($equipment['room']) ?></p>
        </div>
        <div class="col-md-6">
          <p><strong>Custody:</strong> <?= htmlspecialchars($equipment['custody_name']) ?></p>
          <p><strong>Unit Value:</strong> ₱<?= number_format($equipment['unit_value'], 2) ?></p>
          <p><strong>Date of Issuance:</strong> <?= htmlspecialchars($equipment['date_of_issuance']) ?></p>
          <p><strong>Remarks:</strong> <?= htmlspecialchars($equipment['remarks']) ?></p>
        </div>
      </div>
      <hr>
      <?php 
      $qrPath = "../qrcode/{$serial}.png";
      if (file_exists($qrPath)) {
          echo "<div class='text-center'><img src='{$qrPath}' width='150'></div>";
      }
      ?>
    </div>
  </div>

  <!-- 🔹 Transfer History Table -->
  <div class="card shadow-sm">
    <div class="card-header bg-secondary text-white">
      <h5 class="mb-0">Transfer History</h5>
    </div>
    <div class="card-body">
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
              <th>Custody To</th>
              <th>Transfer Date</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
          <?php 
          if (mysqli_num_rows($transferResult) > 0) {
              while ($t = mysqli_fetch_assoc($transferResult)) {
                  // ✅ Status logic based on approved_by_custody
                  if ($t['approved_by_custody'] == 1) {
                      $status = "<span class='badge bg-success'>Approved</span>";
                  } else {
                      $status = "<span class='badge bg-danger'>Declined</span>";
                  }

                  echo "<tr>
                          <td>{$t['serial']}</td>
                          <td>{$equipment['description']}</td>
                          <td>{$t['category_name']}</td>
                          <td>{$t['room_from']}</td>
                          <td>{$t['room_to']}</td>
                          <td>{$t['from_first']} {$t['from_last']}</td>
                          <td>{$t['to_first']} {$t['to_last']}</td>
                          <td>{$t['transfer_date']}</td>
                          <td>{$status}</td>
                        </tr>";
              }
          } else {
              echo "<tr><td colspan='9' class='text-center'>No transfer history found</td></tr>";
          }
          ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

</body>
</html>
