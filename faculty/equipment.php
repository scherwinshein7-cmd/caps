<?php
session_start();
include '../includes/db_connection.php';

// ✅ Check login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// ✅ Filters
$where = ["e.custody = '" . mysqli_real_escape_string($conn, $user_id) . "'"];

if (!empty($_GET['serial'])) {
    $serial = mysqli_real_escape_string($conn, $_GET['serial']);
    $where[] = "e.serial LIKE '%$serial%'";
}
if (!empty($_GET['room'])) {
    $room = mysqli_real_escape_string($conn, $_GET['room']);
    $where[] = "e.room LIKE '%$room%'";
}
if (!empty($_GET['category_id'])) {
    $category_id = mysqli_real_escape_string($conn, $_GET['category_id']);
    $where[] = "e.category_id = '$category_id'";
}

$whereSQL = "WHERE " . implode(" AND ", $where);

// ✅ Pagination
$limit = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$countQuery = "SELECT COUNT(*) AS total FROM equipment e $whereSQL";
$totalRows = mysqli_fetch_assoc(mysqli_query($conn, $countQuery))['total'];
$totalPages = ceil($totalRows / $limit);

// ✅ Fetch data
$sql = "SELECT e.*, c.category AS category_name, 
               CONCAT(a.firstname,' ',a.lastname) AS custody_name
        FROM equipment e
        LEFT JOIN categories c ON e.category_id = c.id
        LEFT JOIN account a ON e.custody = a.employee_id
        $whereSQL
        ORDER BY e.serial ASC
        LIMIT $limit OFFSET $offset";
$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Equipment - ITDS</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="../css/style.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="dashboard-page">
<?php include 'header.php'; ?>

<div class="container mt-4">
  <h2 class="mb-3">My Equipment</h2>

  <div class="mb-3 text-end">
      <a href="rpcs_record.php" class="btn btn-success">
          <i class="bi bi-clipboard-data"></i> View My RPCS Records
      </a>
  </div>


  <!-- 🔍 Filter -->
  <div class="card shadow-sm mb-4">
    <div class="card-body">
      <form method="GET" class="row g-2">
        <div class="col-md-4">
          <input type="text" name="serial" value="<?= htmlspecialchars($_GET['serial'] ?? '') ?>" class="form-control" placeholder="Search by Serial">
        </div>
        <div class="col-md-4">
          <input type="text" name="room" value="<?= htmlspecialchars($_GET['room'] ?? '') ?>" class="form-control" placeholder="Search by Room">
        </div>
        <div class="col-md-4">
          <select name="category_id" class="form-control">
            <option value="">All Categories</option>
            <?php
            $catQuery = mysqli_query($conn, "SELECT * FROM categories");
            while ($cat = mysqli_fetch_assoc($catQuery)) {
                $selected = ($_GET['category_id'] ?? '') == $cat['id'] ? "selected" : "";
                echo "<option value='{$cat['id']}' $selected>{$cat['category']}</option>";
            }
            ?>
          </select>
        </div>
        <div class="col-md-12 text-end">
          <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Search</button>
          <a href="equipment.php" class="btn btn-secondary">Reset</a>
        </div>
      </form>
    </div>
  </div>

  <!-- ✅ Transfer Button -->
  <div class="text-end mb-2">
    <button type="button" class="btn btn-warning" id="openTransferModal" disabled data-bs-toggle="modal" data-bs-target="#bulkTransferModal">
      <i class="bi bi-arrow-left-right"></i> Transfer Selected
    </button>
  </div>

  <form method="POST" action="transfer_process.php" id="equipmentForm">
    <div class="table-responsive">
      <table class="table table-bordered table-striped align-middle">
        <thead class="table-light">
          <tr>
            <th><input type="checkbox" id="selectAll"></th>
            <th>Serial</th>
            <th>Description</th>
            <th>Category</th>
            <th>Room</th>
            <th>Custody</th>
            <th>Unit Value</th>
            <th>Date of Issuance</th>
            <th>Remarks</th>
            <th>More Info</th>
          </tr>
        </thead>
        <tbody>
        <?php 
        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $serial = htmlspecialchars($row['serial']);
                $desc = htmlspecialchars($row['description']);
                $category = htmlspecialchars($row['category_name']);
                $room = htmlspecialchars($row['room']);
                $custody = htmlspecialchars($row['custody_name']);
                $unit_value = "₱ " . number_format((float)$row['unit_value'], 2);
                $date = htmlspecialchars($row['date_of_issuance']);
                $remarks = htmlspecialchars($row['remarks']);

                echo "<tr>
                        <td><input type='checkbox' class='selectItem' name='serials[]' value='{$serial}'></td>
                        <td>{$serial}</td>
                        <td>{$desc}</td>
                        <td>{$category}</td>
                        <td>{$room}</td>
                        <td>{$custody}</td>
                        <td>{$unit_value}</td>
                        <td>{$date}</td>
                        <td>{$remarks}</td>
                        <td><a href='equipment_details.php?serial=" . urlencode($serial) . "' class='btn btn-outline-secondary btn-sm'><i class='bi bi-list'></i></a></td>
                      </tr>";
            }
        } else {
            echo "<tr><td colspan='10' class='text-center'>No records found</td></tr>";
        }
        ?>
        </tbody>
      </table>
    </div>

    <!-- ✅ Pagination -->
    <nav class="mt-3" aria-label="Page navigation">
      <ul class="pagination justify-content-center">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
          <li class="page-item <?= $i == $page ? 'active' : '' ?>">
            <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
          </li>
        <?php endfor; ?>
      </ul>
    </nav>

    <!-- ✅ Bulk Transfer Modal -->
    <div class="modal fade" id="bulkTransferModal" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Transfer Selected Equipment</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" name="custody_from" value="<?= $_SESSION['user_id'] ?>">
            <p><strong>Selected items:</strong> <span id="selectedCount">0</span></p>

            <!-- Stay/Change Room -->
            <div class="mb-3 form-check">
              <input type="checkbox" class="form-check-input" id="stayRoomBulk" name="stayRoom" checked onchange="toggleRoomOptionBulk()">
              <label class="form-check-label" for="stayRoomBulk">Remain in current room</label>
            </div>

            <div class="mb-3" id="lockedRoomBulk">
              <label class="form-label">Room</label>
              <input type="text" class="form-control" name="room_to" id="bulkRoomValue" readonly>
            </div>

            <div class="mb-3" id="changeRoomBulk" style="display:none;">
              <label class="form-label">Select New Room</label>
              <select name="room_to" class="form-select">
                <option value="">-- Select Room --</option>
                <?php
                $roomQuery = mysqli_query($conn, "SELECT room_name FROM room ORDER BY room_name ASC");
                while ($r = mysqli_fetch_assoc($roomQuery)) {
                    echo "<option value='{$r['room_name']}'>{$r['room_name']}</option>";
                }
                ?>
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label">Transfer To (Faculty)</label>
              <select name="custody_to" class="form-select" required>
                <option value="">-- Select Faculty --</option>
                <?php
                $facultyQuery = mysqli_query($conn, "SELECT employee_id, firstname, lastname FROM account WHERE role='faculty' ORDER BY firstname ASC");
                while ($faculty = mysqli_fetch_assoc($facultyQuery)) {
                    echo "<option value='{$faculty['employee_id']}'>{$faculty['firstname']} {$faculty['lastname']}</option>";
                }
                ?>
              </select>
            </div>
          </div>
          <div class="modal-footer">
            <button type="submit" class="btn btn-primary">Submit Transfer</button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          </div>
        </div>
      </div>
    </div>
  </form>
</div>

<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ✅ Persistent checkbox tracking (localStorage)
const checkboxes = document.querySelectorAll('.selectItem');
const selectAll = document.getElementById('selectAll');
const transferBtn = document.getElementById('openTransferModal');
const selectedCount = document.getElementById('selectedCount');
const bulkRoomValue = document.getElementById('bulkRoomValue');

let selectedItems = JSON.parse(localStorage.getItem('selectedEquipment') || '[]');

// Update UI
function updateUI() {
  checkboxes.forEach(cb => cb.checked = selectedItems.includes(cb.value));
  transferBtn.disabled = selectedItems.length === 0;
  selectedCount.textContent = selectedItems.length;
}

// ✅ Track checkbox changes
checkboxes.forEach(cb => {
  cb.addEventListener('change', () => {
    if (cb.checked) selectedItems.push(cb.value);
    else selectedItems = selectedItems.filter(v => v !== cb.value);
    localStorage.setItem('selectedEquipment', JSON.stringify(selectedItems));
    updateUI();
  });
});

// ✅ Select All toggle
selectAll.addEventListener('change', () => {
  checkboxes.forEach(cb => cb.checked = selectAll.checked);
  if (selectAll.checked) {
    selectedItems = Array.from(new Set([...selectedItems, ...Array.from(checkboxes).map(cb => cb.value)]));
  } else {
    selectedItems = selectedItems.filter(v => !Array.from(checkboxes).map(cb => cb.value).includes(v));
  }
  localStorage.setItem('selectedEquipment', JSON.stringify(selectedItems));
  updateUI();
});

// ✅ Initialize
updateUI();

// ✅ Clear storage after submit
document.getElementById('equipmentForm').addEventListener('submit', () => {
  localStorage.removeItem('selectedEquipment');
});

// ✅ Toggle room change UI
function toggleRoomOptionBulk() {
  const stay = document.getElementById('stayRoomBulk');
  document.getElementById('lockedRoomBulk').style.display = stay.checked ? 'block' : 'none';
  document.getElementById('changeRoomBulk').style.display = stay.checked ? 'none' : 'block';
}
</script>
</body>
</html>
