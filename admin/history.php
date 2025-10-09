<?php
session_start();
include '../includes/db_connection.php';

$serial = $_GET['serial'] ?? '';

if ($serial == '') {
    echo "<p class='text-danger'>No serial provided.</p>";
    exit;
}

$sql = "SELECT * FROM return_equipment_history WHERE serial = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $serial);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "<table class='table table-bordered'>";
    echo "<thead><tr>
            <th>Room From</th>
            <th>Room To</th>
            <th>Custody From</th>
            <th>Custody To</th>
            <th>Transfer Date</th>
            <th>Approved by Admin</th>
            <th>Approved by Custody</th>
            <th>Completed</th>
          </tr></thead><tbody>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>
                <td>{$row['room_from']}</td>
                <td>{$row['room_to']}</td>
                <td>{$row['custody_from']}</td>
                <td>{$row['custody_to']}</td>
                <td>{$row['transfer_date']}</td>
                <td>" . ($row['approved_by_admin'] ? "Yes" : "No") . "</td>
                <td>" . ($row['approved_by_custody'] ? "Yes" : "No") . "</td>
                <td>" . ($row['completed'] ? "Yes" : "No") . "</td>
              </tr>";
    }
    echo "</tbody></table>";
} else {
    echo "<p>No history found for <b>$serial</b>.</p>";
}
