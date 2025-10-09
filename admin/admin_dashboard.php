<?php
session_start();
include '../includes/db_connection.php';

// ✅ Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header('Location: login.php');
    exit();
}

// ✅ Total Faculty
$faculty_count = 0;
$result = $conn->query("SELECT COUNT(*) AS count FROM account WHERE role = 'faculty'");
if ($result) {
    $faculty_count = $result->fetch_assoc()['count'];
}

// ✅ Category Totals
$category_counts = [
    '(ICT) Equipment' => 0,
    'Machinery & Equipment' => 0,
    'Furniture & Fixture' => 0
];
$sql = "SELECT c.category, COUNT(*) AS total
        FROM equipment e
        JOIN categories c ON e.category_id = c.id
        GROUP BY c.category";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        if (isset($category_counts[$row['category']])) {
            $category_counts[$row['category']] = $row['total'];
        }
    }
}

// ✅ Remarks Totals
$remarks_counts = [
    'Functional' => 0,
    'Outdated' => 0,
    'Defective' => 0,
    'Condemned' => 0
];
$sql = "SELECT remarks, COUNT(*) AS total FROM equipment GROUP BY remarks";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        if (isset($remarks_counts[$row['remarks']])) {
            $remarks_counts[$row['remarks']] = $row['total'];
        }
    }
}

// ✅ Categories for filter
$categories = [];
$result = $conn->query("SELECT * FROM categories");
while ($row = $result->fetch_assoc()) {
    $categories[$row['id']] = $row['category'];
}

// ✅ Condemned Equipment per Year
$selected_category = isset($_GET['category']) ? intval($_GET['category']) : 0;
$condemn_query = "
    SELECT YEAR(cr.date_approved) AS year, COUNT(*) AS total
    FROM condemn_request cr
    JOIN equipment e ON cr.serial = e.serial
    WHERE cr.request_condemn = 0
    AND cr.date_approved IS NOT NULL
";
if ($selected_category > 0) {
    $condemn_query .= " AND e.category_id = $selected_category";
}
$condemn_query .= " GROUP BY YEAR(cr.date_approved) ORDER BY year ASC";

$yearly_data = [];
$result = $conn->query($condemn_query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $yearly_data[$row['year']] = $row['total'];
    }
}
?>

<?php include 'header.php'; ?>
<body class="dashboard-page">
<div class="container-fluid">
    <div class="row">

        <!-- ✅ Left Section -->
        <div class="col-md-6">

            <div class="breadcrumb-section mb-3">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="#"><i class="fas fa-home"></i> Home</a></li>
                    </ol>
                </nav>
            </div>

            <div class="welcome-section mb-4">
                <h4>Welcome back, Admin!</h4>
                <div class="user-avatar"><i class="fas fa-user-circle fa-2x"></i></div>
            </div>

            <!-- ✅ Faculty -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <a href="stats.php?type=faculty" class="text-decoration-none">
                        <div class="stat-card text-center">
                            <div class="stat-number"><?php echo $faculty_count; ?></div>
                            <div class="stat-label">Total Faculty Members</div>
                        </div>
                    </a>
                </div>
            </div>

            <!-- ✅ Category -->
            <div class="row mb-4">
                <div class="col-md-12 text-center mb-3 fw-bold text-uppercase">Category</div>

                <div class="col-md-12 mb-3">
                    <a href="stats.php?type=category&value=<?php echo urlencode('(ICT) Equipment'); ?>" class="text-decoration-none">
                        <div class="stat-card-green text-center">
                            <div class="stat-number"><?php echo $category_counts['(ICT) Equipment']; ?></div>
                            <div class="stat-label">(ICT) Equipment</div>
                        </div>
                    </a>
                </div>

                <div class="col-md-6 mb-3">
                    <a href="stats.php?type=category&value=<?php echo urlencode('Machinery & Equipment'); ?>" class="text-decoration-none">
                        <div class="stat-card-green text-center">
                            <div class="stat-number"><?php echo $category_counts['Machinery & Equipment']; ?></div>
                            <div class="stat-label">Machinery & Equipment</div>
                        </div>
                    </a>
                </div>

                <div class="col-md-6 mb-3">
                    <a href="stats.php?type=category&value=<?php echo urlencode('Furniture & Fixture'); ?>" class="text-decoration-none">
                        <div class="stat-card-green text-center">
                            <div class="stat-number"><?php echo $category_counts['Furniture & Fixture']; ?></div>
                            <div class="stat-label">Furniture & Fixture</div>
                        </div>
                    </a>
                </div>
            </div>

            <!-- ✅ Remarks -->
            <div class="row mb-4">
                <div class="col-md-12 text-center mb-3 fw-bold text-uppercase">Remarks</div>

                <div class="col-md-6 mb-3">
                    <a href="stats.php?type=remarks&value=Functional" class="text-decoration-none">
                        <div class="stat-card-orange text-center">
                            <div class="stat-number"><?php echo $remarks_counts['Functional']; ?></div>
                            <div class="stat-label">Functional</div>
                        </div>
                    </a>
                </div>

                <div class="col-md-6 mb-3">
                    <a href="stats.php?type=remarks&value=Outdated" class="text-decoration-none">
                        <div class="stat-card-orange text-center">
                            <div class="stat-number"><?php echo $remarks_counts['Outdated']; ?></div>
                            <div class="stat-label">Outdated</div>
                        </div>
                    </a>
                </div>

                <div class="col-md-6 mb-3">
                    <a href="stats.php?type=remarks&value=Defective" class="text-decoration-none">
                        <div class="stat-card-orange text-center">
                            <div class="stat-number"><?php echo $remarks_counts['Defective']; ?></div>
                            <div class="stat-label">Defective</div>
                        </div>
                    </a>
                </div>

                <div class="col-md-6 mb-3">
                    <a href="stats.php?type=remarks&value=Condemned" class="text-decoration-none">
                        <div class="stat-card-orange text-center">
                            <div class="stat-number"><?php echo $remarks_counts['Condemned']; ?></div>
                            <div class="stat-label">Condemned</div>
                        </div>
                    </a>
                </div>
            </div>
        </div>

        <!-- ✅ Right Section -->
        <div class="col-md-6">

            <!-- 📊 Condemned Equipment Chart -->
            <div class="card mb-4 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold text-primary m-0"><i class="bi bi-bar-chart-fill"></i> Condemned Equipment per Year</h5>

                        <form method="GET" class="d-flex align-items-center">
                            <select name="category" class="form-select form-select-sm" onchange="this.form.submit()">
                                <?php foreach ($categories as $id => $cat): ?>
                                    <option value="<?php echo $id; ?>" <?php echo ($selected_category == $id) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </div>

                    <canvas id="condemnChart" height="140"></canvas>
                </div>
            </div>

            <!-- 🧠 Recommendation Board -->
            <div class="recommendation-board p-4 rounded shadow-sm">
                <h4 class="fw-bold text-center mb-4 text-primary">
                    <i class="fas fa-lightbulb"></i> Recommendation Board
                </h4>

                <?php
                $recommend_sql = "
                    SELECT e.serial, e.description, c.category, e.remarks
                    FROM equipment e
                    JOIN categories c ON e.category_id = c.id
                    WHERE e.remarks IN ('Outdated', 'Defective')
                    ORDER BY e.remarks ASC
                ";
                $recommend_result = $conn->query($recommend_sql);
                ?>

                <?php if ($recommend_result && $recommend_result->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle">
                            <thead class="table-primary text-center">
                                <tr>
                                    <th>Serial</th>
                                    <th>Description</th>
                                    <th>Category</th>
                                    <th>Remarks</th>
                                    <th>Recommendation</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($rec = $recommend_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($rec['serial']); ?></td>
                                        <td><?php echo htmlspecialchars($rec['description']); ?></td>
                                        <td><?php echo htmlspecialchars($rec['category']); ?></td>
                                        <td class="<?php echo $rec['remarks'] == 'Defective' ? 'text-danger fw-bold' : 'text-warning fw-bold'; ?>">
                                            <?php echo htmlspecialchars($rec['remarks']); ?>
                                        </td>
                                        <td><span class="badge bg-danger text-white">Recommend to Condemn</span></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-success text-center">
                        <i class="fas fa-check-circle"></i> All equipment are functional and up-to-date!
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<!-- 📊 Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('condemnChart').getContext('2d');
const chartData = {
    labels: <?php echo json_encode(array_keys($yearly_data)); ?>,
    datasets: [{
        label: 'Total Condemned Equipment',
        data: <?php echo json_encode(array_values($yearly_data)); ?>,
        backgroundColor: '#3b82f6',
        borderRadius: 6
    }]
};

new Chart(ctx, {
    type: 'bar',
    data: chartData,
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true,
                ticks: { stepSize: 1 }
            }
        },
        plugins: {
            legend: { display: false },
            tooltip: { enabled: true }
        }
    }
});
</script>

<style>
.card {
    border-radius: 12px;
    border: 1px solid #dce3f0;
}
.recommendation-board {
    background-color: #f8f9fa;
    border: 1px solid #dce3f0;
    border-radius: 12px;
    max-height: 80vh;
    overflow-y: auto;
}
.stat-card, .stat-card-green, .stat-card-orange {
    border-radius: 12px;
    padding: 20px;
    color: white;
    transition: transform 0.2s ease;
    box-shadow: 0 3px 6px rgba(0,0,0,0.2);
}
.stat-card { background-color: #091c69ff; }
.stat-card-green { background-color: #4260ceff; }
.stat-card-orange { background-color: #6E8CFB; }
</style>
</body>
</html>
<?php include 'footer.php'; ?>
