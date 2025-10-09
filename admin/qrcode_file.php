<?php 
session_start();

// Directory containing QR codes
$qrcodeDir = __DIR__ . "/qrcode";
$webPath   = "qrcode"; // relative path for displaying

// Handle Delete
if (isset($_GET['delete'])) {
    $fileToDelete = basename($_GET['delete']); 
    $fullPath = $qrcodeDir . "/" . $fileToDelete;
    if (file_exists($fullPath)) {
        unlink($fullPath);
        header("Location: qrcode_manager.php?msg=deleted");
        exit;
    }
}

// Handle Search
$search = isset($_GET['search']) ? trim($_GET['search']) : "";

// Get all QR code files
$files = glob($qrcodeDir . "/*.png");

// Filter if searching
if (!empty($search)) {
    $files = array_filter($files, function($file) use ($search) {
        $filename = pathinfo($file, PATHINFO_FILENAME); // no .png
        return stripos($filename, $search) !== false;
    });
}

// Handle Multiple Download (ZIP)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['selected_files'])) {
    $zipName = "qrcodes_" . date("Ymd_His") . ".zip";
    $zipPath = $qrcodeDir . "/" . $zipName;

    $zip = new ZipArchive();
    if ($zip->open($zipPath, ZipArchive::CREATE) === TRUE) {
        foreach ($_POST['selected_files'] as $file) {
            $filePath = $qrcodeDir . "/" . basename($file);
            if (file_exists($filePath)) {
                $zip->addFile($filePath, basename($filePath));
            }
        }
        $zip->close();

        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $zipName . '"');
        header('Content-Length: ' . filesize($zipPath));
        readfile($zipPath);
        unlink($zipPath); // delete after download
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>QR Code Manager</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="logo.png">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body { background: #f8f9fa; }
        .card { border-radius: 12px; transition: transform 0.2s ease-in-out; }
        .card:hover { transform: scale(1.03); }
        .img-fluid { border-radius: 8px; object-fit: contain; width: 100%; }
    </style>
</head>

<body class="p-3">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2>📂 QR Code Manager</h2>
            <a href="equipment.php" class="btn btn-secondary">⬅ Go Back</a>
        </div>

        <!-- Deleted Alert -->
        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
            <div class="alert alert-success">File deleted successfully!</div>
        <?php endif; ?>

        <!-- Search Form -->
        <form method="GET" class="mb-4">
            <div class="input-group">
                <input type="text" name="search" class="form-control" placeholder="Search by serial (without .png)" value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="btn btn-primary">Search</button>
                <a href="qrcode_file.php" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>

        <!-- QR Code List -->
        <?php if (!empty($files)): ?>
            <form method="POST">
                <div class="mb-3 d-flex gap-2">
                    <button type="submit" class="btn btn-success">⬇ Download Selected</button>
                    <button type="button" class="btn btn-outline-secondary" onclick="toggleCheckboxes(true)">Select All</button>
                    <button type="button" class="btn btn-outline-secondary" onclick="toggleCheckboxes(false)">Unselect All</button>
                </div>

                <div class="row g-3">
                    <?php foreach ($files as $file): 
                        $filename = basename($file); 
                        $serial   = pathinfo($filename, PATHINFO_FILENAME); ?>
                        <div class="col-6 col-sm-4 col-md-3 col-lg-2 text-center">
                            <div class="card shadow-sm h-100">
                                <div class="card-body d-flex flex-column align-items-center">
                                    <input type="checkbox" name="selected_files[]" value="<?= $filename ?>" class="form-check-input mb-2">
                                    <img src="<?= $webPath . '/' . $filename ?>" 
                                        alt="QR Code" 
                                        class="img-fluid mb-2" style="max-height:150px;">
                                    <p class="small text-truncate w-100"><b><?= $serial ?></b></p>
                                    <div class="d-flex gap-2 mt-auto">
                                        <a href="<?= $webPath . '/' . $filename ?>" download 
                                        class="btn btn-sm btn-success">Download</a>
                                        <a href="?delete=<?= urlencode($filename) ?>" 
                                        class="btn btn-sm btn-danger"
                                        onclick="return confirm('Delete this QR code?');">
                                            Delete
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </form>
        <?php else: ?>
            <div class="alert alert-info">No QR code found<?= !empty($search) ? " for '<b>" . htmlspecialchars($search) . "</b>'" : "" ?>.</div>
        <?php endif; ?>
    </div>

    <script>
        function toggleCheckboxes(selectAll) {
            document.querySelectorAll('input[type="checkbox"][name="selected_files[]"]').forEach(cb => {
                cb.checked = selectAll;
            });
        }
    </script>
</body>
</html>
