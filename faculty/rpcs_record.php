<?php
session_start();
include '../includes/db_connection.php';

// ✅ Faculty-only access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Faculty') {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// ====================
// Fetch distinct years for dropdown for this faculty
$year_result = mysqli_query($conn, "SELECT DISTINCT YEAR(date_of_issuance) AS year 
                                    FROM equipment 
                                    WHERE custody='$user_id' AND date_of_issuance IS NOT NULL 
                                    ORDER BY year DESC");
$years = [];
while($row = mysqli_fetch_assoc($year_result)) {
    $years[] = $row['year'];
}

// ====================
// Fetch categories assigned to this faculty
$categories_res = mysqli_query($conn, "SELECT DISTINCT c.id, c.category 
                                      FROM equipment e 
                                      LEFT JOIN categories c ON e.category_id = c.id 
                                      WHERE e.custody='$user_id' ORDER BY c.category ASC");
$categories = [];
while($row = mysqli_fetch_assoc($categories_res)) {
    $categories[] = $row;
}

// ====================
// Handle search filters (faculty can only see own custody)
$category_filter = $_GET['category'] ?? ($categories[0]['id'] ?? '');
$year_filter = $_GET['year'] ?? '';
$description_filter = $_GET['description'] ?? '';

// ====================
// Build equipment query
$sql = "
SELECT e.*, c.category AS category_name
FROM equipment e
LEFT JOIN categories c ON e.category_id = c.id
WHERE e.custody='$user_id' AND e.date_of_issuance IS NOT NULL
";

$conditions = [];
if($category_filter) $conditions[] = "e.category_id = '".mysqli_real_escape_string($conn, $category_filter)."'";
if($year_filter) $conditions[] = "YEAR(e.date_of_issuance) = '".mysqli_real_escape_string($conn, $year_filter)."'";

if($conditions) $sql .= " AND " . implode(" AND ", $conditions);
$sql .= " ORDER BY c.category, e.date_of_issuance DESC, e.serial ASC";

$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Equipment Records</title>
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
  <h2 class="mb-4 text-primary"><i class="bi bi-clipboard-data"></i> My Equipment Records</h2>

  <!-- ==================== SEARCH FORM ==================== -->
  <form method="get" class="row g-3 mb-4">
    <div class="col-md-4">
      <label for="category" class="form-label">Category</label>
      <select id="category" name="category" class="form-select">
        <?php foreach($categories as $row): ?>
          <option value="<?= $row['id'] ?>" <?= ($row['id']==$category_filter)?'selected':'' ?>><?= htmlspecialchars($row['category']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3">
      <label for="year" class="form-label">Year of Issuance</label>
      <select name="year" id="year" class="form-select">
        <option value="">All Years</option>
        <?php foreach($years as $y): ?>
          <option value="<?= $y ?>" <?= ($y==$year_filter)?'selected':'' ?>><?= $y ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-4">
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
          $key = $row['category_name'] . '||' . $row['date_of_issuance'];
          $groups[$key][] = $row;
      }

      $counter = 1;
      foreach($groups as $key => $items):
          if($description_filter) {
              $match = false;
              foreach($items as $r) {
                  if(stripos($r['description'], $description_filter) !== false) {
                      $match = true;
                      break;
                  }
              }
              if(!$match) continue;
          }
          list($category_name, $date_of_issuance) = explode('||', $key);
          $collapseId = "collapseGroup{$counter}";
          $serials = implode(',', array_column($items, 'serial'));
          $descriptions = $items[0][ 'description'];
          $unit_values = array_column($items, 'unit_value');
          $unit_value = array_sum($unit_values);
  ?>

  <div class="card card-group shadow-sm mb-3">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center" data-bs-toggle="collapse" data-bs-target="#<?= $collapseId ?>">
      <div>
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
            &unit_value=<?= urlencode($unit_value) ?>"
           target="_blank" class="btn btn-outline-light btn-sm border">
          <i class="bi bi-filetype-pdf"></i> PDF
        </a>
      </div>
    </div>

    <div class="collapse" id="<?= $collapseId ?>">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-bordered table-striped custom-table mb-0">
            <thead>
              <tr>
                <th>Serial</th>
                <th>Description</th>
                <th>Category</th>
                <th>Room</th>
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
      echo "<div class='alert alert-info'>No equipment records found.</div>";
  }
  ?>
</div>

<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
