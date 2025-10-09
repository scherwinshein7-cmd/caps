<?php
session_start();
include '../includes/db_connection.php';

// ✅ Check if user is MIS
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'MIS') {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

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
?>

<?php include 'header.php'; ?>
<body class="dashboard-page">
<div class="container-fluid">
    <div class="row">

        <!-- ✅ Left side (Stats Section - 1/2 width) -->
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

        <!-- ✅ Right side vacant -->
        <!-- ✅ Right side (Recommendation Board) -->
        <div class="col-md-6">
            <div class="recommendation-board p-4 rounded shadow-sm">
                <h4 class="fw-bold text-center mb-4 text-primary">
                    <i class="fas fa-lightbulb"></i> Recommendation Board
                </h4>

                <?php
                // ✅ Fetch equipment that should be recommended for condemnation
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

<style>
.stat-card, .stat-card-green, .stat-card-orange {
    border-radius: 12px;
    padding: 20px;
    color: white;
    transition: transform 0.2s ease;
    box-shadow: 0 3px 6px rgba(0,0,0,0.2);
}
.stat-card:hover, .stat-card-green:hover, .stat-card-orange:hover {
    transform: scale(1.05);
}
.stat-card { background-color: #091c69ff; }
.stat-card-green { background-color: #4260ceff; }
.stat-card-orange { background-color: #6E8CFB; }
.stat-number {
    font-size: 2rem;
    font-weight: bold;
}
.stat-label {
    font-size: 1rem;
    margin-top: 5px;
}

.recommendation-board {
    background-color: #f8f9fa;
    border: 1px solid #dce3f0;
    border-radius: 12px;
    max-height: 80vh;
    overflow-y: auto;
}
.recommendation-board h4 {
    color: #091c69ff;
}
.table thead th {
    background-color: #4260ceff !important;
    color: white;
}
.table tbody tr:hover {
    background-color: #eef2ff;
}

</style>
</body>
</html>
<?php include 'footer.php'; ?>
