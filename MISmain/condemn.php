<?php
session_start();
include '../includes/db_connection.php';

// ✅ MIS(Main)-only access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'MIS(Main)') {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// ✅ Get MIS(Main) full name
$nameQuery = mysqli_query($conn, "SELECT CONCAT(firstname, ' ', lastname) AS fullname FROM account WHERE employee_id='$user_id'");
$userData = mysqli_fetch_assoc($nameQuery);
$mis_name = $userData['fullname'] ?? 'Unknown MIS';

// ✅ Handle Approve Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_group']) && !empty($_POST['serials'])) {
    foreach ($_POST['serials'] as $serial) {
        $serial = mysqli_real_escape_string($conn, $serial);

        // ✅ Only update approved_by_mis_main
        mysqli_query($conn, "
            UPDATE condemn_request
            SET approved_by_mis_main = '$mis_name'
            WHERE serial = '$serial'
        ");
    }

    $_SESSION['success'] = "Selected equipment successfully approved by MIS(Main).";
    header("Location: condemn.php");
    exit();
}

// ✅ Fetch requests (regardless of request_condemn)
$sql = "
SELECT cr.*, e.description, e.unit_value, e.room, e.remarks, 
       c.category AS category_name
FROM condemn_request cr
LEFT JOIN equipment e ON cr.serial = e.serial
LEFT JOIN categories c ON e.category_id = c.id
ORDER BY cr.requested_by ASC, cr.date_requested DESC
";
$result = mysqli_query($conn, $sql);

// ✅ Group data by requested_by
$grouped = [];
while ($row = mysqli_fetch_assoc($result)) {
    $requested_by = $row['requested_by'] ?: 'Unknown Admin';
    $grouped[$requested_by][] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Condemn Requests (MIS Main)</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="../css/style.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="dashboard-page">

<?php include 'header.php'; ?>

<div class="container mt-4">
  <h2 class="mb-3"><i class="bi bi-tools"></i> Condemn Requests (MIS Main)</h2>

  <?php if (!empty($_SESSION['success'])): ?>
    <div class="alert alert-success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
  <?php endif; ?>

  <?php if (empty($grouped)): ?>
    <div class="alert alert-info">No condemnation requests found.</div>
  <?php else: ?>
    <?php foreach ($grouped as $requested_by => $items): ?>
      <div class="card mb-4 shadow-sm">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
          <strong>Requested by Admin:</strong> <?= htmlspecialchars($requested_by) ?>
          <span><i class="bi bi-calendar3"></i> Latest Request: <?= htmlspecialchars($items[0]['date_requested']) ?></span>
        </div>

        <!-- ✅ Each group has its own approve form -->
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
                    <th>Remarks</th>
                    <th>Approved By MIS(Main)</th>
                    <th>Date Requested</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($items as $row): ?>
                    <tr>
                      <td><input type="checkbox" class="selectItem" name="serials[]" value="<?= htmlspecialchars($row['serial']) ?>"></td>
                      <td><?= htmlspecialchars($row['serial']) ?></td>
                      <td><?= htmlspecialchars($row['description']) ?></td>
                      <td><?= htmlspecialchars($row['category_name']) ?></td>
                      <td><?= htmlspecialchars($row['room']) ?></td>
                      <td>₱ <?= number_format((float)$row['unit_value'], 2) ?></td>
                      <td><?= htmlspecialchars($row['remarks']) ?></td>
                      <td><?= htmlspecialchars($row['approved_by_mis_main'] ?? 'Pending') ?></td>
                      <td><?= htmlspecialchars($row['date_requested']) ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>

          <!-- ✅ Approve button for this group -->
          <div class="card-footer text-end">
            <button type="submit" name="approve_group" class="btn btn-success approveBtn" disabled>
              <i class="bi bi-check-circle"></i> Approve Selected
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
// ✅ Handle group select checkboxes per table
document.querySelectorAll('.group-form').forEach(form => {
  const selectAll = form.querySelector('.selectAllGroup');
  const checkboxes = form.querySelectorAll('.selectItem');
  const approveBtn = form.querySelector('.approveBtn');

  selectAll.addEventListener('change', () => {
    checkboxes.forEach(cb => cb.checked = selectAll.checked);
    updateButtonState();
  });

  checkboxes.forEach(cb => cb.addEventListener('change', updateButtonState));

  function updateButtonState() {
    const checked = Array.from(checkboxes).some(cb => cb.checked);
    approveBtn.disabled = !checked;
  }

  form.addEventListener('submit', (e) => {
    const selected = Array.from(checkboxes).filter(cb => cb.checked);
    if (selected.length === 0) {
      e.preventDefault();
      alert('Please select at least one equipment to approve.');
    } else if (!confirm('Are you sure you want to approve the selected items?')) {
      e.preventDefault();
    }
  });
});
</script>

</body>
</html>
