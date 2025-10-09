<?php
session_start();
include '../includes/db_connection.php';

if (isset($_POST['condemn'])) {
    $serial = $_POST['serial'] ?? '';
    if ($serial!='') {
        $conn->begin_transaction();
        try {
            // Copy equipment
            $conn->query("INSERT INTO return_equipment (serial,specification,category_id,room,custody,status,cause,qrcode,record_date)
                          SELECT serial,specification,category_id,room,custody,status,cause,qrcode,record_date
                          FROM equipment WHERE serial='$serial'");
            if ($conn->affected_rows===0) throw new Exception("No equipment copied");

            // Copy history (if exists)
            $conn->query("INSERT INTO return_equipment_history (transfer_id,serial,specification,category_id,room_from,room_to,custody_from,custody_to,transfer_date,approved_by_admin,completed,approved_by_custody)
                          SELECT id,serial,specification,category_id,room_from,room_to,custody_from,custody_to,transfer_date,approved_by_admin,completed,approved_by_custody
                          FROM equipment_transfer WHERE serial='$serial'");

            // Verify
            $check=$conn->query("SELECT 1 FROM return_equipment WHERE serial='$serial' LIMIT 1");
            if ($check->num_rows===0) throw new Exception("Verification failed");

            // Delete originals
            $conn->query("DELETE FROM equipment WHERE serial='$serial'");
            $conn->query("DELETE FROM equipment_transfer WHERE serial='$serial'");

            $conn->commit();
            header("Location: return.php");
            exit;
        } catch(Exception $e) {
            $conn->rollback();
            echo "❌ Error: ".$e->getMessage();
        }
    }
}
