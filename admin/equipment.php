<?php  
session_start();
include '../includes/db_connection.php';

// ✅ Only admin access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header('Location: login.php');
    exit();
}

// ✅ Auto-update remarks to "Outdated" if older than 5 years
$currentYear = date('Y');
$updateSql = "
    UPDATE equipment 
    SET remarks = 'Outdated'
    WHERE remarks != 'Outdated'
    AND YEAR(date_of_issuance) <= ($currentYear - 5)
    AND date_of_issuance IS NOT NULL
";
mysqli_query($conn, $updateSql);




// ✅ Filters
$where = [];
if (!empty($_GET['serial'])) {
    $serial = mysqli_real_escape_string($conn, $_GET['serial']);
    $where[] = "e.serial LIKE '%$serial%'";
}
if (!empty($_GET['description'])) {
    $description = mysqli_real_escape_string($conn, $_GET['description']);
    $where[] = "e.description LIKE '%$description%'";
}
if (!empty($_GET['room'])) {
    $room = mysqli_real_escape_string($conn, $_GET['room']);
    $where[] = "e.room LIKE '%$room%'";
}
if (!empty($_GET['custody'])) {
    $custody = mysqli_real_escape_string($conn, $_GET['custody']);
    $where[] = "e.custody = '$custody'";
}
if (!empty($_GET['category_id'])) {
    $category_id = mysqli_real_escape_string($conn, $_GET['category_id']);
    $where[] = "e.category_id = '$category_id'";
}
if (!empty($_GET['year'])) {
    $year = intval($_GET['year']);
    $where[] = "YEAR(e.date_of_issuance) = $year";
}


$whereSQL = count($where) ? "WHERE " . implode(" AND ", $where) : "";

// ✅ Pagination
$limit = 10; 
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// ✅ Count total
$countSql = "SELECT COUNT(*) AS total 
             FROM equipment e
             LEFT JOIN account a ON e.custody = a.employee_id
             LEFT JOIN categories c ON e.category_id = c.id
             $whereSQL";
$countResult = mysqli_query($conn, $countSql);
$totalRows = mysqli_fetch_assoc($countResult)['total'];
$totalPages = ceil($totalRows / $limit);

// ✅ Fetch data
$sql = "SELECT e.serial, e.description, e.unit_value, e.room, e.custody, 
               e.remarks, e.qrcode, e.date_of_issuance,
               CONCAT(a.firstname, ' ', a.middlename, ' ', a.lastname) AS custody_name,
               c.category AS category_name
        FROM equipment e
        LEFT JOIN account a ON e.custody = a.employee_id
        LEFT JOIN categories c ON e.category_id = c.id
        $whereSQL
        ORDER BY e.date_of_issuance DESC
        LIMIT $offset, $limit";
$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php include 'header.php'; ?>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Equipment Management - ITDS</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="../css/style.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    .dashboard-page { background-color: #f9fafb; min-height: 100vh; display: flex; flex-direction: column; }
    h2 { font-weight: 600; color: #1e3a8a; }
    .custom-table th { background-color: #1e3a8a; color: white; text-align: center; }
    .custom-table td { vertical-align: middle; }
    .pagination .page-link { color: #1e3a8a; border-radius: 6px; }
    .pagination .page-item.active .page-link { background-color: #1e3a8a; border-color: #1e3a8a; color: white; }
    .btn { border-radius: 8px; }
  </style>
</head>
<body class="dashboard-page">
<div class="container mt-4 flex-grow-1">
  
  <!-- Header -->
  <div class="mt-2 mt-md-0">
    <button class="btn btn-primary me-2" onclick="window.location.href='qrcode_file.php'">
      <i class="bi bi-qr-code"></i> QRCode File
    </button>
    <button class="btn btn-warning me-2" onclick="window.location.href='rpcs_record.php'">
      <i class="bi bi-clipboard-data"></i> RPCS Record
    </button>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addEquipmentModal">
      <i class="bi bi-plus-circle"></i> Add Equipment
    </button>
  </div>


  <!-- Filters -->
  <div class="card shadow-sm mb-4">
    <div class="card-body">
      <form method="GET" class="row g-3">
        <!-- Category -->
        <div class="col-md-3">
          <select name="category_id" class="form-select">
            <option value="">All Categories</option>
            <?php
            $catQuery = mysqli_query($conn, "SELECT * FROM categories");
            while ($cat = mysqli_fetch_assoc($catQuery)) {
                $selected = ($_GET['category_id'] ?? '') == $cat['id'] ? 'selected' : '';
                echo "<option value='{$cat['id']}' $selected>{$cat['category']}</option>";
            }
            ?>
          </select>
        </div>

        <!-- Custody -->
        <div class="col-md-3">
          <select name="custody" class="form-select">
            <option value="">All Custody</option>
            <?php
            $custodyQuery = mysqli_query($conn, "SELECT employee_id, firstname, middlename, lastname FROM account WHERE role='faculty'");
            while ($acc = mysqli_fetch_assoc($custodyQuery)) {
                $fullname = trim($acc['firstname'].' '.$acc['middlename'].' '.$acc['lastname']);
                $selected = ($_GET['custody'] ?? '') == $acc['employee_id'] ? 'selected' : '';
                echo "<option value='{$acc['employee_id']}' $selected>$fullname</option>";
            }
            ?>
          </select>
        </div>

        <!-- Room -->
        <div class="col-md-3">
          <select name="room" class="form-select">
            <option value="">All Rooms</option>
            <?php
            $roomQuery = mysqli_query($conn, "SELECT * FROM room ORDER BY room_name ASC");
            while ($r = mysqli_fetch_assoc($roomQuery)) {
                $selected = ($_GET['room'] ?? '') == $r['room_name'] ? 'selected' : '';
                echo "<option value='{$r['room_name']}' $selected>{$r['room_name']}</option>";
            }
            ?>
          </select>
        </div>

        <!-- Description Search -->
        <div class="col-md-3">
          <input type="text" name="description" class="form-control" placeholder="Search Description..."
                 value="<?= htmlspecialchars($_GET['description'] ?? '') ?>">
        </div>
        <!-- Year of Issuance -->
        <div class="col-md-3">
          <select name="year" class="form-select">
            <option value="">All Years</option>
            <?php
            $yearQuery = mysqli_query($conn, "SELECT DISTINCT YEAR(date_of_issuance) AS year FROM equipment WHERE date_of_issuance IS NOT NULL ORDER BY year DESC");
            while ($y = mysqli_fetch_assoc($yearQuery)) {
                $selected = ($_GET['year'] ?? '') == $y['year'] ? 'selected' : '';
                echo "<option value='{$y['year']}' $selected>{$y['year']}</option>";
            }
            ?>
          </select>
        </div>


        <!-- Search / Reset -->
        <div class="col-md-12 text-end">
          <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Search</button>
          <a href="equipment.php" class="btn btn-secondary"><i class="bi bi-arrow-clockwise"></i> Reset</a>
        </div>
      </form>
    </div>
  </div>

  <!-- Equipment Table -->
  <div class="table-responsive">
    <table class="table table-bordered table-striped custom-table">
      <thead>
        <tr>
          <th>Serial</th>
          <th>Description</th>
          <th>Unit Value (₱)</th>
          <th>Category</th>
          <th>Room</th>
          <th>Custody</th>
          <th>Remarks</th>
          <th>Date of Issuance</th>
          <th>QR Code</th>
          <th>More Info</th>
        </tr>
      </thead>
      <tbody>
        <?php
          if (mysqli_num_rows($result) > 0) {
              while ($row = mysqli_fetch_assoc($result)) {
                  $serial = htmlspecialchars($row['serial']);
                  echo "<tr>
                          <td>{$serial}</td>
                          <td>" . htmlspecialchars($row['description']) . "</td>
                          <td>₱ " . number_format($row['unit_value'], 2) . "</td>
                          <td>" . htmlspecialchars($row['category_name']) . "</td>
                          <td>" . htmlspecialchars($row['room']) . "</td>
                          <td>" . htmlspecialchars($row['custody_name']) . "</td>
                          <td>" . htmlspecialchars($row['remarks']) . "</td>
                          <td>" . htmlspecialchars($row['date_of_issuance']) . "</td>
                          <td>";

                  // ✅ Display QR code if it exists
                  if (!empty($row['qrcode']) && file_exists("qrcode/{$serial}.png")) {
                      echo "<img src='qrcode/{$serial}.png' width='70' alt='QR Code'>";
                  } else {
                      echo "<span class='text-muted'>No QR</span>";
                  }

                  // ✅ Info button column
                  echo "</td>
                        <td class='text-center'>
                          <a href='equipment_info.php?serial={$serial}' class='btn btn-outline-primary btn-sm'>
                            <i class='bi bi-three-dots'></i>
                          </a>
                        </td>
                      </tr>";
              }
          } else {
              echo "<tr><td colspan='10' class='text-center text-muted'>No equipment found</td></tr>";
          }
          ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination -->
  <nav>
    <ul class="pagination justify-content-center">
      <?php if ($page > 1): ?>
        <li class="page-item"><a class="page-link" href="?<?= http_build_query(array_merge($_GET, ["page" => $page - 1])) ?>">Previous</a></li>
      <?php endif; ?>
      <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
          <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ["page" => $i])) ?>"><?= $i ?></a>
        </li>
      <?php endfor; ?>
      <?php if ($page < $totalPages): ?>
        <li class="page-item"><a class="page-link" href="?<?= http_build_query(array_merge($_GET, ["page" => $page + 1])) ?>">Next</a></li>
      <?php endif; ?>
    </ul>
  </nav>
</div>


<?php
// ✅ Fetch dropdown data for modal
$custodyQuery = mysqli_query($conn, "SELECT employee_id, firstname, middlename, lastname FROM account");
$catQuery = mysqli_query($conn, "SELECT id, category FROM categories");
?>

<!-- Add Equipment Modal -->
<div class="modal fade" id="addEquipmentModal" tabindex="-1" aria-labelledby="addEquipmentModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">

      <form method="POST" action="save_equipment.php" onsubmit="window.open('about:blank','qrWindow');">
        <div class="modal-header">
          <h5 class="modal-title" id="addEquipmentModalLabel">Add Equipment (Multiple Serials)</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body">

          <?php if (isset($_SESSION['modal_error'])): ?>
            <div class="alert alert-danger">
                <?= $_SESSION['modal_error']; ?>
            </div>
          <?php unset($_SESSION['modal_error']); endif; ?>

          <div class="mb-3">
            <label for="serials" class="form-label">Serials (comma-separated)</label>
            <input type="text" class="form-control" name="serials" id="serials" 
                   placeholder="e.g. 1001,1002,1003"
                   value="<?= htmlspecialchars($_SESSION['old']['serials'] ?? '') ?>" required>
          </div>

          <div class="mb-3">
            <label for="description" class="form-label">Description</label>
            <input type="text" class="form-control" name="description" id="description" 
                   value="<?= htmlspecialchars($_SESSION['old']['description'] ?? '') ?>" required>
          </div>

          <div class="mb-3">
            <label for="unit_value" class="form-label">Unit Value (₱)</label>
            <input type="number" step="0.01" class="form-control" name="unit_value" id="unit_value" 
                   value="<?= htmlspecialchars($_SESSION['old']['unit_value'] ?? '') ?>" required>
          </div>

          <div class="mb-3">
            <label for="category_id" class="form-label">Category</label>
            <select class="form-select" name="category_id" id="category_id" required>
              <option value="">-- Select Category --</option>
              <?php
              $catQuery = mysqli_query($conn, "SELECT id, category FROM categories");
              while ($cat = mysqli_fetch_assoc($catQuery)):
                $selected = (($_SESSION['old']['category_id'] ?? '') == $cat['id']) ? "selected" : "";
              ?>
                <option value="<?= $cat['id'] ?>" <?= $selected ?>><?= htmlspecialchars($cat['category']) ?></option>
              <?php endwhile; ?>
            </select>
          </div>

          <div class="mb-3">
          <label for="room" class="form-label">Room</label>
          <select name="room" id="room" class="form-control" required>
            <option value="">-- Select Room --</option>
            <?php
            $roomQuery = mysqli_query($conn, "SELECT * FROM room ORDER BY room_name ASC");
            while ($r = mysqli_fetch_assoc($roomQuery)) {
                echo "<option value='{$r['room_name']}'>{$r['room_name']}</option>";
            }
            ?>
          </select>
        </div>


          <div class="mb-3">
            <label for="custody" class="form-label">Custody</label>
            <select class="form-select" name="custody" id="custody" required>
              <option value="">-- Select Custody --</option>
              <?php 
              $accQuery = mysqli_query($conn, "SELECT employee_id, firstname, middlename, lastname FROM account");
              while ($acc = mysqli_fetch_assoc($accQuery)): 
                $fullname = $acc['firstname']." ".$acc['middlename']." ".$acc['lastname'];
                $selected = (($_SESSION['old']['custody'] ?? '') == $acc['employee_id']) ? "selected" : "";
              ?>
                <option value="<?= $acc['employee_id'] ?>" <?= $selected ?>><?= htmlspecialchars($fullname) ?></option>
              <?php endwhile; ?>
            </select>
          </div>

          <div class="mb-3">
            <label for="remarks" class="form-label">Remarks</label>
            <input type="text" class="form-control" name="remarks" id="remarks" 
                   value="<?= htmlspecialchars($_SESSION['old']['remarks'] ?? 'Functional') ?>" readonly>
          </div>

        </div>
        
       <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Save Equipment</button>
          <button type="button" class="btn btn-info" onclick="openRPCS()">Generate RPCS</button>
          <script>
            function openRPCS() {
                // Collect modal form values
                let serials = document.getElementById("serials").value;
                let description = document.getElementById("description").value;
                let unit_value = document.getElementById("unit_value").value;
                let category_id = document.getElementById("category_id").value;
                let custody = document.getElementById("custody").value;

                // Open RPCS PHP generator
                window.open(
                    "rpcs_pdf.php?serials=" + encodeURIComponent(serials) +
                    "&description=" + encodeURIComponent(description) +
                    "&unit_value=" + encodeURIComponent(unit_value) +
                    "&category_id=" + encodeURIComponent(category_id) +
                    "&custody=" + encodeURIComponent(custody),
                    "_blank"
                );
            }
            </script>

          
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>

      </form>
    </div>
  </div>
</div>

<?php unset($_SESSION['old']); ?>

<?php if (isset($_SESSION['error'])): ?>
<div class="modal fade" id="errorModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-sm modal-dialog-centered">
    <div class="modal-content text-bg-danger">
      <div class="modal-header">
        <h5 class="modal-title">Error</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <?= $_SESSION['error']; ?>
      </div>
    </div>
  </div>
</div>
<script>
document.addEventListener("DOMContentLoaded", () => {
    let errorModal = new bootstrap.Modal(document.getElementById("errorModal"));
    errorModal.show();
});
</script>
<?php unset($_SESSION['error']); endif; ?>


<?php if (isset($_SESSION['success'])): ?>
<div class="modal fade" id="successModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-sm modal-dialog-centered">
    <div class="modal-content text-bg-success">
      <div class="modal-header">
        <h5 class="modal-title">Success</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <?= $_SESSION['success']; ?>
      </div>
    </div>
  </div>
</div>
<script>
document.addEventListener("DOMContentLoaded", () => {
    let successModal = new bootstrap.Modal(document.getElementById("successModal"));
    successModal.show();
});
</script>
<?php unset($_SESSION['success']); endif; ?>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= $_SESSION['success']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php unset($_SESSION['success']); endif; ?>



<?php if (isset($_SESSION['modal_open']) && $_SESSION['modal_open']): ?>
<script>
document.addEventListener("DOMContentLoaded", () => {
    let addModal = new bootstrap.Modal(document.getElementById("addEquipmentModal"));
    addModal.show();
});
</script>
<?php unset($_SESSION['modal_open']); endif; ?>

<!-- ✅ QR Popup Handler -->
<?php if (isset($_GET['open_qr']) && isset($_SESSION['last_serials'])): ?>
<script>
document.addEventListener("DOMContentLoaded", () => {
  const serials = "<?= implode(',', $_SESSION['last_serials']); ?>";
  const url = "qrcode_pdf.php?serials=" + encodeURIComponent(serials);

  // Reuse window if exists
  let win = window.open('', 'qrWindow');
  if (win && !win.closed) {
    win.location.href = url;
    win.focus();
  } else {
    let newWin = window.open(url, 'qrWindow');
    if (!newWin) {
      alert("⚠️ Popup blocked! Please allow popups or click manually.");
      window.location.href = url;
    }
  }
});
</script>
<?php unset($_SESSION['last_serials']); endif; ?>

<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
