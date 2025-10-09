<?php
include '../includes/db_connection.php';

if (isset($_POST['id'])) {
    $id = $_POST['id'];

    // Move account to archive
    $query_archive = "INSERT INTO account_archive SELECT *, NOW() FROM account WHERE register_id = ?";
    $stmt1 = $conn->prepare($query_archive);
    $stmt1->bind_param("s", $id);

    // Remove from main table
    $query_delete = "DELETE FROM account WHERE register_id = ?";
    $stmt2 = $conn->prepare($query_delete);
    $stmt2->bind_param("s", $id);

    if ($stmt1->execute() && $stmt2->execute()) {
        echo "Account moved to archive successfully.";
    } else {
        echo "Error deleting account.";
    }
} else {
    echo "No account ID provided.";
}
?>
