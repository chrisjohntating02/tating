<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

checkAuthentication();

$pageTitle = "Customers";
include '../../includes/header.php';

// Get all customers
$customers = $pdo->query("SELECT * FROM customers ORDER BY name")->fetchAll();
?>

<div class="customers-container">
    <div class="page-header">
        <h1>Customers</h1>
        <div class="header-actions">
            <a href="add.php" class="add-button">Add Customer</a>
        </div>
    </div>
    
    <div class="toolbar">
        <div class="search-bar">
            <input type="text" id="customerSearch" placeholder="Search customers...">
        </div>
        <div class="filters">
            <select id="typeFilter">
                <option value="">All Types</option>
                <option value="regular">Regular</option>
                <option value="senior">Senior Citizen</option>
                <option value="pwd">PWD</option>
            </select>
        </div>
    </div>
    
    <div class="table-responsive">
        <table id="customersTable" class="data-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Type</th>
                    <th>Discount</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($customers as $customer): ?>
                    <tr data-type="<?php echo $customer['customer_type']; ?>">
                        <td><?php echo htmlspecialchars($customer['name']); ?></td>
                        <td><?php echo htmlspecialchars($customer['phone']); ?></td>
                        <td><?php echo htmlspecialchars($customer['email']); ?></td>
                        <td>
                            <?php 
                                $type = ucfirst($customer['customer_type']);
                                if ($customer['customer_type'] === 'senior') $type = 'Senior Citizen';
                                elseif ($customer['customer_type'] === 'pwd') $type = 'PWD';
                                echo $type;
                            ?>
                        </td>
                        <td><?php echo $customer['customer_type'] === 'regular' ? $customer['discount_rate'] . '%' : '20%'; ?></td>
                        <td class="actions">
                            <a href="edit.php?id=<?php echo $customer['id']; ?>" class="edit-btn">Edit</a>
                            <a href="#" class="delete-btn" data-id="<?php echo $customer['id']; ?>">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Search functionality
        document.getElementById('customerSearch').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('#customersTable tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
        
        // Filter by type
        document.getElementById('typeFilter').addEventListener('change', function() {
            const type = this.value;
            const rows = document.querySelectorAll('#customersTable tbody tr');
            
            rows.forEach(row => {
                if (!type || row.getAttribute('data-type') === type) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
        
        // Delete customer
        document.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const customerId = this.getAttribute('data-id');
                
                if (confirm('Are you sure you want to delete this customer?')) {
                    fetch(`delete.php?id=${customerId}`, {
                        method: 'DELETE'
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            this.closest('tr').remove();
                        } else {
                            alert('Error deleting customer: ' + data.message);
                        }
                    })
                    .catch(error => {
                        alert('Error deleting customer: ' + error);
                    });
                }
            });
        });
    });
</script>

<?php include '../../includes/footer.php'; ?>