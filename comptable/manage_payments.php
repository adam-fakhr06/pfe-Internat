<?php
session_start();
include '../db.php';

/* Redirect to login page if no session exists
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'comptable') {
    header("Location: ../login/login.php");
    exit();
}
*/

// Fetch all payments
$payments_query = "
    SELECT p.id, p.student_cin, s.name, s.email, s.phone, p.amount, p.payment_date, p.status 
    FROM payments p
    LEFT JOIN students s ON p.student_cin = s.cin
    ORDER BY p.payment_date DESC";
$payments_result = $conn->query($payments_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../admin/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <title>Manage Payments</title>
    <style>
        .main-content {
            margin-left: 0; /* Remove sidebar margin if needed */
            padding: 20px;
            width: 100%; /* Make it occupy full width */
        }

        .search-bar {
            margin: 20px auto; /* Adjusted margin for consistency */
            text-align: center;
        }

        .payments-table {
            width: 95%; /* Increase table width */
            max-width: 1100px; /* Adjust max-width */
            margin: 20px auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.2);
            overflow-x: auto;
        }

        .payments-table h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #141460;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 16px;
        }

        thead {
            background: #141460;
            color: white;
        }

        th, td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
            text-align: center;
            vertical-align: middle;
        }

        table tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        table tr:hover {
            background-color: #ddd;
        }

        .not-paid {
            color: red;
            font-weight: bold;
        }

        .paid {
            color: green;
            font-weight: bold;
        }

        .edit-btn {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 5px;
            cursor: pointer;
        }

        .edit-btn:hover {
            background-color: #45a049;
        }

        .contact-btn {
            background-color: #007BFF;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 5px;
            cursor: pointer;
        }

        .contact-btn:hover {
            background-color: #0056b3;
        }

        .modal {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.2);
            z-index: 1000;
            width: 400px;
        }

        .modal-header {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .modal-footer {
            margin-top: 20px;
            text-align: right;
        }

        .modal-footer button {
            padding: 5px 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .modal-footer .save-btn {
            background-color: #4CAF50;
            color: white;
        }

        .modal-footer .cancel-btn {
            background-color: #f44336;
            color: white;
        }

        .modal-footer .send-btn {
            background-color: #4CAF50;
            color: white;
        }

        .overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }
    </style>
</head>
<body>
    <?php include '../admin/header.php'; ?>
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <div class="payments-table">
            <h2>Manage Payments</h2>
            <div class="search-bar">
                <input type="text" id="searchInput" placeholder="Search payments by student CIN or name...">
                <button type="button" id="searchButton"><i class="fa fa-search"></i></button>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Student CIN</th>
                        <th>Student Name</th>
                        <th>Amount</th>
                        <th>Payment Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <?php if ($payments_result->num_rows > 0): ?>
                        <?php while ($payment = $payments_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($payment['student_cin']); ?></td>
                                <td><?php echo htmlspecialchars($payment['name']); ?></td>
                                <td><?php echo htmlspecialchars($payment['amount']); ?> MAD</td>
                                <td><?php echo htmlspecialchars($payment['payment_date']); ?></td>
                                <td class="<?php echo $payment['status'] === 'paid' ? 'paid' : 'not-paid'; ?>">
                                    <?php echo htmlspecialchars($payment['status']); ?>
                                </td>
                                <td>
                                    <button class="edit-btn" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($payment)); ?>)">Edit</button>
                                    <?php if ($payment['status'] === 'not paid'): ?>
                                        <button class="contact-btn" onclick="openContactModal(<?php echo htmlspecialchars(json_encode($payment)); ?>)">Contact</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6">No data available</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Edit Payment Modal -->
    <div class="modal" id="editModal">
        <div class="modal-header">Edit Payment</div>
        <form id="editForm" method="POST" action="update_payment.php">
            <input type="hidden" name="payment_id" id="paymentId">
            <div class="form-group">
                <label for="editAmount">Amount:</label>
                <input type="number" name="amount" id="editAmount" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="editStatus">Status:</label>
                <select name="status" id="editStatus" class="form-control" required>
                    <option value="paid">Paid</option>
                    <option value="not paid">Not Paid</option>
                    <option value="pending">Pending</option>
                </select>
            </div>
            <div class="modal-footer">
                <button type="button" class="cancel-btn" onclick="closeEditModal()">Cancel</button>
                <button type="submit" class="save-btn">Save</button>
            </div>
        </form>
    </div>

    <!-- Contact Modal -->
    <div class="modal" id="contactModal">
        <div class="modal-header">Contact Student</div>
        <form id="contactForm" method="POST" action="contact_student.php">
            <input type="hidden" name="student_cin" id="contactStudentCin">
            <div class="form-group">
                <label for="contactMethod">Choose Contact Method:</label>
                <select name="contact_method" id="contactMethod" class="form-control" required>
                    <option value="email">Email</option>
                    <option value="phone">Phone</option>
                </select>
            </div>
            <div class="form-group">
                <label for="contactMessage">Message:</label>
                <textarea name="message" id="contactMessage" class="form-control" rows="4" required>
Dear [Student Name],
This is a reminder that your payment of [Amount] MAD is still pending. Please make the payment at your earliest convenience.
                </textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="cancel-btn" onclick="closeContactModal()">Cancel</button>
                <button type="submit" class="send-btn">Send</button>
            </div>
        </form>
    </div>
    <div class="overlay" id="overlay" onclick="closeContactModal()"></div>

    <script>
        function openEditModal(payment) {
            document.getElementById('paymentId').value = payment.id;
            document.getElementById('editAmount').value = payment.amount;
            document.getElementById('editStatus').value = payment.status.toLowerCase(); // Ensure lowercase for consistency
            document.getElementById('editModal').style.display = 'block';
            document.getElementById('overlay').style.display = 'block';
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
            document.getElementById('overlay').style.display = 'none';
        }

        function openContactModal(payment) {
            document.getElementById('contactStudentCin').value = payment.student_cin;
            document.getElementById('contactMessage').value = `Dear ${payment.name},\nThis is a reminder that your payment of ${payment.amount} MAD is still pending. Please make the payment at your earliest convenience.`;
            document.getElementById('contactModal').style.display = 'block';
            document.getElementById('overlay').style.display = 'block';
        }

        function closeContactModal() {
            document.getElementById('contactModal').style.display = 'none';
            document.getElementById('overlay').style.display = 'none';
        }

        document.getElementById('searchInput').addEventListener('input', function () {
            const filter = this.value.toLowerCase();
            const rows = document.querySelectorAll('#tableBody tr');
            rows.forEach(row => {
                const cin = row.cells[0].textContent.toLowerCase();
                const name = row.cells[1].textContent.toLowerCase();
                if (cin.includes(filter) || name.includes(filter)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>
