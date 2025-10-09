<?php
session_start();
include '../includes/db_connection.php';

// ✅ Admin-only access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// ✅ Fetch admin full name for requested_by
$nameQuery = mysqli_query($conn, "SELECT CONCAT(firstname,' ',lastname) AS fullname FROM account WHERE employee_id='$user_id'");
$userData = mysqli_fetch_assoc($nameQuery);
$admin_name = $userData['fullname'] ?? 'Unknown Admin';

// ✅ Handle Condemn Request Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['serials'])) {
    foreach ($_POST['serials'] as $serial) {
        $serial = mysqli_real_escape_string($conn, $serial);

        // Get custody name of this serial from equipment table
        $getCustody = mysqli_query($conn, "
            SELECT CONCAT(a.firstname,' ',a.lastname) AS custody_name
            FROM equipment e
            LEFT JOIN account a ON e.custody = a.employee_id
            WHERE e.serial = '$serial'
        ");
        $custodyData = mysqli_fetch_assoc($getCustody);
        $custody_name = $custodyData['custody_name'] ?? 'Unknown Custody';

        // Avoid duplicate requests (pending only)
        $check = mysqli_query($conn, "SELECT * FROM condemn_request WHERE serial='$serial' AND request_condemn=1");
        if (mysqli_num_rows($check) == 0) {
            mysqli_query($conn, "
                INSERT INTO condemn_request (serial, request_condemn, requested_by)
                VALUES ('$serial', 1, '$custody_name')
            ");
        }
    }
    $_SESSION['success'] = "Condemn request successfully submitted!";
    header("Location: condemn.php");
    exit();
}

// ✅ Fetch all outdated or defective equipment
$sql = "
SELECT e.*, 
       c.category AS category_name, 
       CONCAT(a.firstname,' ',a.lastname) AS custody_name
FROM equipment e
LEFT JOIN categories c ON e.category_id = c.id
LEFT JOIN account a ON e.custody = a.employee_id
WHERE e.remarks IN ('Outdated', 'Defective')
ORDER BY a.firstname, e.serial ASC
";
$result = mysqli_query($conn, $sql);

// ✅ Group data by custody
$grouped = [];
while ($row = mysqli_fetch_assoc($result)) {
    $custody = $row['custody_name'] ?: 'Unknown Custody';
    $grouped[$custody][] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Condemn Equipment (Admin) - ITDS</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="../css/style.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="dashboard-page">
<?php include 'header.php'; ?>

<div class="container mt-4">
  <h2 class="mb-3"><i class="bi bi-exclamation-triangle"></i> Condemn Equipment (Admin)</h2>

  <?php if (!empty($_SESSION['success'])): ?>
    <div class="alert alert-success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
  <?php endif; ?>

  <div class="text-end mb-3">
    <a href="admin_condemned_history.php" class="btn btn-outline-primary">
      <i class="bi bi-clock-history"></i> View Condemned History
    </a>
  </div>

  <?php if (empty($grouped)): ?>
    <div class="alert alert-info">No outdated or defective equipment found.</div>
  <?php else: ?>
    <?php foreach ($grouped as $custody => $items): ?>
      <div class="card mb-4 shadow-sm">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
          <strong>Custody:</strong> <?= htmlspecialchars($custody) ?>
          <a href="rpcs_pdf.php?custody=<?= urlencode($custody) ?>" target="_blank" class="btn btn-outline-light btn-sm">
              <i class="bi bi-filetype-pdf"></i> PDF
          </a>
        </div>

        <!-- ✅ Each custody group has its own form -->
        <form method="POST" action="" class="group-form">
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-bordered table-striped align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th><input type="checkbox" class="selectAllGroup"></th>
                    <th>Serial</th>
                    <th>Description</th>
                    <th>Category</th>
                    <th>Room</th>
                    <th>Unit Value</th>
                    <th>Date of Issuance</th>
                    <th>Remarks</th>
                    <th>Status</th>
                    <th>Approved by (MIS)</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($items as $row): 
                      $serial = htmlspecialchars($row['serial']);
                      $desc = htmlspecialchars($row['description']);
                      $category = htmlspecialchars($row['category_name']);
                      $room = htmlspecialchars($row['room']);
                      $unit_value = "₱ " . number_format((float)$row['unit_value'], 2);
                      $date = htmlspecialchars($row['date_of_issuance']);
                      $remarks = htmlspecialchars($row['remarks']);
                      
                      // ✅ Fetch condemn info
                      $condemn = mysqli_query($conn, "SELECT * FROM condemn_request WHERE serial='$serial' ORDER BY id DESC LIMIT 1");
                      $cinfo = mysqli_fetch_assoc($condemn);

                      $isRequested = $cinfo ? true : false;
                      $isApproved = ($cinfo && $cinfo['request_condemn'] == 0);

                      $status = !$isRequested 
                        ? "<span class='badge bg-secondary'>Not Requested</span>"
                        : ($isApproved 
                            ? "<span class='badge bg-success'>Accepted</span>"
                            : "<span class='badge bg-warning text-dark'>Requested</span>");

                      $approved_by = $isApproved 
                        ? htmlspecialchars($cinfo['approved_by_mis_main'] ?? 'MIS') 
                        : ($isRequested ? 'Pending' : '-');

                      $disabled = $isRequested ? "disabled" : "";
                  ?>
                    <tr>
                      <td><input type="checkbox" class="selectItem" name="serials[]" value="<?= $serial ?>" <?= $disabled ?>></td>
                      <td><?= $serial ?></td>
                      <td><?= $desc ?></td>
                      <td><?= $category ?></td>
                      <td><?= $room ?></td>
                      <td><?= $unit_value ?></td>
                      <td><?= $date ?></td>
                      <td><?= $remarks ?></td>
                      <td><?= $status ?></td>
                      <td><?= $approved_by ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>

          <!-- ✅ Condemn button only for this group -->
          <div class="card-footer text-end">
            <button type="submit" class="btn btn-danger requestCondemnBtn" disabled>
              <i class="bi bi-exclamation-triangle"></i> Request Condemn
            </button>
          </div>
        </form>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
// ✅ Handle group select checkboxes
document.querySelectorAll('.group-form').forEach(form => {
  const selectAll = form.querySelector('.selectAllGroup');
  const checkboxes = form.querySelectorAll('.selectItem');
  const condemnBtn = form.querySelector('.requestCondemnBtn');

  selectAll.addEventListener('change', () => {
    checkboxes.forEach(cb => {
      if (!cb.disabled) cb.checked = selectAll.checked;
    });
    updateButtonState();
  });

  checkboxes.forEach(cb => cb.addEventListener('change', updateButtonState));

  function updateButtonState() {
    const checked = Array.from(checkboxes).some(cb => cb.checked);
    condemnBtn.disabled = !checked;
  }

  form.addEventListener('submit', e => {
    const selected = Array.from(checkboxes).filter(cb => cb.checked);
    if (selected.length === 0) {
      e.preventDefault();
      alert('Please select at least one equipment to request condemn.');
    }
  });
});
</script>

</body>
</html>
