<?php 
session_start();
include '../includes/db_connection.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header('Location: login.php');
    exit();
}

// Fetch data from account table
$sql = "SELECT * FROM account";
$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - ITDS Equipment Monitoring</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        .custom-table {
            background-color: #ffffff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0px 4px 10px rgba(0,0,0,0.1);
        }
        .custom-table th {
            background-color: #343a40;
            color: white;
            font-weight: bold;
            text-align: center;
        }
        .custom-table td {
            vertical-align: middle;
            text-align: center;
        }
        .custom-table tr:hover {
            background-color: #f8f9fa;
            transition: background-color 0.2s ease-in-out;
        }
        .table-responsive {
            overflow-x: auto;
        }
    </style>
</head>
<body class="dashboard-page">
    <?php include 'header.php'; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2>Account Table</h2>
            <div>
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addAccountModal">
                    <i class="bi bi-plus-circle"></i> Add Account
                </button>
                <a href="archive_account.php" class="btn btn-warning">
                <i class="bi bi-archive"></i> Archive
                </a>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-striped custom-table">
                <thead>
                    <tr>
                        <th>Employee ID</th>
                        <th>Password</th>
                        <th>Role</th>
                        <th>Department</th>
                        <th>Last Name</th>
                        <th>First Name</th>
                        <th>Middle Name</th>
                        <th>Gender</th>
                        <th>Address</th>
                        <th>Phone Number</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if (mysqli_num_rows($result) > 0) {
                        while ($row = mysqli_fetch_assoc($result)) {
                            echo "<tr>
                                    <td>{$row['employee_id']}</td>
                                    <td>{$row['password']}</td>
                                    <td>{$row['role']}</td>
                                    <td>{$row['department']}</td>
                                    <td>{$row['lastname']}</td>
                                    <td>{$row['firstname']}</td>
                                    <td>{$row['middlename']}</td>
                                    <td>{$row['gender']}</td>
                                    <td>{$row['address']}</td>
                                    <td>{$row['phonenumber']}</td>
                                    <td>
                                        <button 
                                            class='btn btn-primary btn-sm editBtn' 
                                            data-bs-toggle='modal' 
                                            data-bs-target='#editAccountModal'
                                            data-id='{$row['employee_id']}'
                                            data-password='{$row['password']}'
                                            data-role='{$row['role']}'
                                            data-department='{$row['department']}'
                                            data-lastname='{$row['lastname']}'
                                            data-firstname='{$row['firstname']}'
                                            data-middlename='{$row['middlename']}'
                                            data-gender='{$row['gender']}'
                                            data-address='{$row['address']}'
                                            data-phonenumber='{$row['phonenumber']}'>
                                            Edit
                                        </button>
                                        <button 
                                            class='btn btn-danger btn-sm removeBtn' 
                                            data-id='{$row['employee_id']}'>
                                            Remove
                                        </button>
                                    </td>
                                  </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='11' class='text-center'>No records found</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add Account Modal -->
    <div class="modal fade" id="addAccountModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="save_account.php" method="POST" onsubmit="return validateAddAccountForm();">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Account</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body row g-3">
                        <div class="col-md-6">
                            <label>Register ID</label>
                            <input type="text" name="employee_id" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label>Password</label>
                            <input type="text" name="password" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label>Role</label>
                            <select name="role" class="form-control" required>
                                <option value="">Select Role</option>
                                <option value="Admin">Admin</option>
                                <option value="Faculty">Faculty</option>
                                <option value="MIS">MIS</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label>Department</label>
                            <select name="department" class="form-control" required>
                                <option value="ITDS">ITDS</option>
                                <!--<option value="">Select Department</option>
                                <option value="cs">CS</option>
                                <option value="bit">BIT</option>-->
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label>Last Name</label>
                            <input type="text" name="lastname" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label>First Name</label>
                            <input type="text" name="firstname" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label>Middle Name</label>
                            <input type="text" name="middlename" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label>Gender</label>
                            <select name="gender" class="form-control" required>
                                <option value="">Select Gender</option>
                                <option value="Female">Female</option>
                                <option value="Male">Male</option>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label>Address</label>
                            <input type="text" name="address" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label>Phone Number</label>
                            <input type="text" name="phonenumber" class="form-control" maxlength="11" pattern="\d{11}" required title="Phone number must be exactly 11 digits">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success">Save</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Account Modal -->
    <div class="modal fade" id="editAccountModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="update_account.php" method="POST" onsubmit="return validateEditAccountForm();">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Account</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body row g-3">
                        <div class="col-md-6">
                            <label>Employee ID</label>
                            <input type="text" name="employee_id" id="edit_employee_id" class="form-control" readonly>
                        </div>
                        <div class="col-md-6">
                            <label>Password</label>
                            <input type="text" name="password" id="edit_password" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label>Role</label>
                            <select name="role" id="edit_role" class="form-control" required>
                                <option value="">Select Role</option>
                                <option value="admin">Admin</option>
                                <option value="faculty">Faculty</option>
                                <option value="mis">MIS</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label>Department</label>
                            <select name="department" id="edit_department" class="form-control" required>
                                <option value="">Select Department</option>
                                <option value="itds">ITDS</option>
                                <option value="cs">CS</option>
                                <option value="bit">BIT</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label>Last Name</label>
                            <input type="text" name="lastname" id="edit_lastname" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label>First Name</label>
                            <input type="text" name="firstname" id="edit_firstname" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label>Middle Name</label>
                            <input type="text" name="middlename" id="edit_middlename" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label>Gender</label>
                            <select name="gender" id="edit_gender" class="form-control" required>
                                <option value="">Select Gender</option>
                                <option value="female">Female</option>
                                <option value="male">Male</option>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label>Address</label>
                            <input type="text" name="address" id="edit_address" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label>Phone Number</label>
                            <input type="text" name="phonenumber" id="edit_phonenumber" class="form-control" maxlength="11" pattern="\d{11}" required title="Phone number must be exactly 11 digits">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Update</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function validateAddAccountForm() {
        const phone = document.querySelector('input[name="phonenumber"]').value;
        if (!/^\d{11}$/.test(phone)) {
            alert('Phone number must be exactly 11 digits.');
            return false;
        }
        return true;
    }
    </script>
    <script>
    function validateEditAccountForm() {
        const phone = document.getElementById('edit_phonenumber').value;
        if (!/^\d{11}$/.test(phone)) {
            alert('Phone number must be exactly 11 digits.');
            return false;
        }
        return true;
    }

    // Set selected options when opening Edit Modal
    document.getElementById('editAccountModal').addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        document.getElementById('edit_employee_id').value = button.getAttribute('data-id');
        document.getElementById('edit_password').value = button.getAttribute('data-password');
        document.getElementById('edit_lastname').value = button.getAttribute('data-lastname');
        document.getElementById('edit_firstname').value = button.getAttribute('data-firstname');
        document.getElementById('edit_middlename').value = button.getAttribute('data-middlename');
        document.getElementById('edit_address').value = button.getAttribute('data-address');
        document.getElementById('edit_phonenumber').value = button.getAttribute('data-phonenumber');

        // Set selected Role
        document.getElementById('edit_role').value = button.getAttribute('data-role');
        // Set selected Department
        document.getElementById('edit_department').value = button.getAttribute('data-department');
        // Set selected Gender
        document.getElementById('edit_gender').value = button.getAttribute('data-gender');
    });
    </script>
    <script>
        document.querySelectorAll('.editBtn').forEach(btn => {
            btn.addEventListener('click', function() {
                let accountId = this.getAttribute('data-id');
                document.getElementById('removeAccountBtn').setAttribute('data-id', accountId);
            });
        });

            document.querySelectorAll('.removeBtn').forEach(btn => {
        btn.addEventListener('click', function() {
            let accountId = this.getAttribute('data-id');
            if (!accountId) {
                alert('Account ID not found.');
                return;
            }
            if (confirm('Are you sure you want to remove this account?')) {
                fetch('remove_account.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'id=' + encodeURIComponent(accountId)
                })
                .then(response => response.text())
                .then(data => {
                    alert(data);
                    location.reload();
                })
                .catch(err => console.error(err));
            }
        });
    });
    </script>


</body>
</html>
