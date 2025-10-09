<?php
// search_equipment.php
include 'includes/db_connection.php';

if (isset($_GET['serial'])) {
    $serial = trim($_GET['serial']);

    $stmt = $conn->prepare("SELECT * FROM equipment WHERE serial = ?");
    $stmt->bind_param("s", $serial);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        echo "<h3>Equipment Details</h3>";
        echo "<p><strong>Serial:</strong> " . htmlspecialchars($row['serial']) . "</p>";
        echo "<p><strong>PC No:</strong> " . htmlspecialchars($row['pc_no']) . "</p>";
        echo "<p><strong>Specification:</strong> " . htmlspecialchars($row['specification']) . "</p>";
        echo "<p><strong>Room:</strong> " . htmlspecialchars($row['room']) . "</p>";
        echo "<p><strong>Custody:</strong> " . htmlspecialchars($row['custody']) . "</p>";
        echo "<p><strong>Remarks:</strong> " . htmlspecialchars($row['remarks']) . "</p>";
        echo "<p><strong>Cause:</strong> " . htmlspecialchars($row['cause']) . "</p>";
        echo "<p><img src='" . htmlspecialchars($row['qrcode']) . "' alt='QR Code'></p>";
    } else {
        echo "<p style='color:red;'>No equipment found for serial: " . htmlspecialchars($serial) . "</p>";
    }
    $stmt->close();
}
?>
