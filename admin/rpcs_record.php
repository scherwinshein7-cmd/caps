<?php
session_start();
include '../includes/db_connection.php';

// ✅ Admin-only access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header('Location: login.php');
    exit();
}

// ====================
// Fetch distinct years for dropdown
$year_result = mysqli_query($conn, "SELECT DISTINCT YEAR(date_of_issuance) AS year FROM equipment WHERE date_of_issuance IS NOT NULL ORDER BY year DESC");
$years = [];
while($row = mysqli_fetch_assoc($year_result)) {
    $years[] = $row['year'];
}

// ====================
// Handle search filters
$category_filter = $_GET['category'] ?? '';
$custody_filter = $_GET['custody'] ?? '';
$year_filter = $_GET['year'] ?? '';
$description_filter = $_GET['description'] ?? '';

// ====================
// Fetch categories & custody for dropdown
$categories = mysqli_query($conn, "SELECT * FROM categories ORDER BY category ASC");
$custodies = mysqli_query($conn, "SELECT * FROM account WHERE role='Faculty' ORDER BY firstname ASC");

// ====================
// Fetch equipment grouped by custody, category, date_of_issuance
$sql = "
SELECT e.*, 
       CONCAT(a.firstname,' ',a.middlename,' ',a.lastname) AS custody_name,
       c.category AS category_name
FROM equipment e
LEFT JOIN account a ON e.custody = a.employee_id
LEFT JOIN categories c ON e.category_id = c.id
WHERE e.date_of_issuance IS NOT NULL
";

// Apply filters
$conditions = [];
if($category_filter) {
    $conditions[] = "e.category_id = '".mysqli_real_escape_string($conn, $category_filter)."'";
}
if($custody_filter) {
    $conditions[] = "e.custody = '".mysqli_real_escape_string($conn, $custody_filter)."'";
}
if($year_filter) {
    $conditions[] = "YEAR(e.date_of_issuance) = '".mysqli_real_escape_string($conn, $year_filter)."'";
}
if($description_filter) {
    // We will filter groups after grouping, so just flag it
    $description_filter = mysqli_real_escape_string($conn, $description_filter);
}

if($conditions) {
    $sql .= " AND " . implode(" AND ", $conditions);
}

$sql .= " ORDER BY e.custody, c.category, e.date_of_issuance DESC, e.serial ASC";
$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>RPCS Records</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="../css/style.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<style>
.card-group { margin-bottom: 1rem; }
.card-header { cursor: pointer; }
.custom-table th { background-color: #1e3a8a; color: white; text-align: center; }
.custom-table td { vertical-align: middle; }
.badge { font-size: 0.85em; }
</style>
</head>
<body>
<?php include 'header.php'; ?>

<div class="container mt-4 flex-grow-1">
  <h2 class="mb-4 text-primary"><i class="bi bi-clipboard-data"></i> RPCS Records</h2>

  <!-- ==================== SEARCH FORM ==================== -->
  <form method="get" class="row g-3 mb-4">
    <div class="col-md-3">
      <label for="category" class="form-label">Category</label>
      <select id="category" name="category" class="form-select">
        <option value="">All Categories</option>
        <?php while($row = mysqli_fetch_assoc($categories)): ?>
          <option value="<?= $row['id'] ?>" <?= ($row['id']==$category_filter)?'selected':'' ?>><?= htmlspecialchars($row['category']) ?></option>
        <?php endwhile; ?>
      </select>
    </div>
    <div class="col-md-3">
      <label for="custody" class="form-label">Custody</label>
      <select id="custody" name="custody" class="form-select">
        <option value="">All Custody</option>
        <?php while($row = mysqli_fetch_assoc($custodies)): ?>
          <option value="<?= $row['employee_id'] ?>" <?= ($row['employee_id']==$custody_filter)?'selected':'' ?>>
            <?= htmlspecialchars($row['firstname'].' '.$row['middlename'].' '.$row['lastname']) ?>
          </option>
        <?php endwhile; ?>
      </select>
    </div>
    <div class="col-md-2">
      <label for="year" class="form-label">Year of Issuance</label>
      <select name="year" id="year" class="form-select">
        <option value="">All Years</option>
        <?php foreach($years as $y): ?>
          <option value="<?= $y ?>" <?= ($y==$year_filter)?'selected':'' ?>><?= $y ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3">
      <label for="description" class="form-label">Description Keyword</label>
      <input type="text" name="description" id="description" class="form-control" value="<?= htmlspecialchars($description_filter) ?>" placeholder="Optional">
    </div>
    <div class="col-md-1 d-flex align-items-end">
      <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search"></i> Search</button>
    </div>
  </form>

  <?php
  if(mysqli_num_rows($result) > 0) {
      $groups = [];
      while($row = mysqli_fetch_assoc($result)) {
          // Group key: custody || category || date_of_issuance
          $key = $row['custody_name'] . '||' . $row['category_name'] . '||' . $row['date_of_issuance'];
          $groups[$key][] = $row;
      }

      $counter = 1;
      foreach($groups as $key => $items):
          // ================================
          // Apply description filter to the group
          if($description_filter) {
              $match = false;
              foreach($items as $r) {
                  if(stripos($r['description'], $description_filter) !== false) {
                      $match = true;
                      break;
                  }
              }
              if(!$match) continue; // skip this group
          }
          list($custody_name, $category_name, $date_of_issuance) = explode('||', $key);
          $collapseId = "collapseGroup{$counter}";

          // Prepare GET parameters for PDF
          $serials = implode(',', array_column($items, 'serial'));
          $descriptions = $items[0][ 'description'];
          $unit_values = array_column($items, 'unit_value');
          $unit_value = array_sum($unit_values);
          $category_id = $items[0]['category_id'];
          $custody_id = $items[0]['custody'];
  ?>

  <div class="card card-group shadow-sm mb-3">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center" data-bs-toggle="collapse" data-bs-target="#<?= $collapseId ?>">
      <div>
        <strong>Custody:</strong> <?= htmlspecialchars($custody_name) ?> | 
        <strong>Category:</strong> <?= htmlspecialchars($category_name) ?> | 
        <strong>Date:</strong> <?= htmlspecialchars($date_of_issuance) ?>
      </div>
      <div>
        <button class="btn btn-light btn-sm me-1">
          <i class="bi bi-arrows-collapse"></i> Toggle
        </button>
        <a href="rpcs_pdf.php?
            serials=<?= urlencode($serials) ?>
            &description=<?= urlencode($descriptions) ?>
            &unit_value=<?= urlencode($unit_value) ?>
            &category_id=<?= urlencode($category_id) ?>
            &custody=<?= urlencode($custody_id) ?> "
           target="_blank" class="btn btn-outline-light btn-sm border">
          <i class="bi bi-filetype-pdf"></i> PDF
        </a>
      </div>
    </div>

    <div class="collapse show" id="<?= $collapseId ?>">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-bordered table-striped custom-table mb-0">
            <thead>
              <tr>
                <th>Serial</th>
                <th>Description</th>
                <th>Category</th>
                <th>Room</th>
                <th>Custody</th>
                <th>Unit Value (₱)</th>
                <th>Remarks</th>
                <th>Date of Issuance</th>
                <th>QR Code</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach($items as $row):
                  $badge = match(strtolower($row['remarks'])) {
                      'functional' => 'success',
                      'outdated' => 'warning',
                      'defective' => 'danger',
                      'condemned' => 'dark',
                      default => 'secondary',
                  };
              ?>
              <tr>
                <td><?= htmlspecialchars($row['serial']) ?></td>
                <td><?= htmlspecialchars($row['description']) ?></td>
                <td><?= htmlspecialchars($row['category_name']) ?></td>
                <td><?= htmlspecialchars($row['room']) ?></td>
                <td><?= htmlspecialchars($row['custody_name']) ?></td>
                <td>₱ <?= number_format($row['unit_value'],2) ?></td>
                <td><span class="badge bg-<?= $badge ?>"><?= ucfirst($row['remarks']) ?></span></td>
                <td><?= htmlspecialchars($row['date_of_issuance']) ?></td>
                <td>
                  <?php 
                  if(!empty($row['qrcode']) && file_exists("qrcode/{$row['serial']}.png")){
                      echo "<img src='qrcode/{$row['serial']}.png' width='70' alt='QR Code'>";
                  } else {
                      echo "<span class='text-muted'>No QR</span>";
                  }
                  ?>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <?php
      $counter++;
      endforeach;
  } else {
      echo "<div class='alert alert-info'>No RPCS records found.</div>";
  }
  ?>
</div>

<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
