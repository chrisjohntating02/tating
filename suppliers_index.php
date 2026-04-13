    <?php
    require_once '../../includes/config.php';
    require_once '../../includes/auth.php';

    checkAuthentication();

    $pageTitle = "Suppliers";
    include '../../includes/header.php';

    // Get all suppliers
    $suppliers = $pdo->query("SELECT * FROM suppliers ORDER BY name")->fetchAll();
    ?>

    <div class="suppliers-container">
        <div class="page-header">
            <h1>Suppliers</h1>
            <div class="header-actions">
                <a href="add.php" class="add-button">Add Supplier</a>
            </div>
        </div>
        
        <div class="toolbar">
            <div class="search-bar">
                <input type="text" id="supplierSearch" placeholder="Search suppliers...">
            </div>
        </div>
        
        <div class="table-responsive">
            <table id="suppliersTable" class="data-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Contact Person</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($suppliers as $supplier): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($supplier['name']); ?></td>
                            <td><?php echo htmlspecialchars($supplier['contact_person']); ?></td>
                            <td><?php echo htmlspecialchars($supplier['phone']); ?></td>
                            <td><?php echo htmlspecialchars($supplier['email']); ?></td>
                            <td class="actions">
                                <a href="edit.php?id=<?php echo $supplier['id']; ?>" class="edit-btn">Edit</a>
                                <a href="#" class="delete-btn" data-id="<?php echo $supplier['id']; ?>">Delete</a>
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
            document.getElementById('supplierSearch').addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                const rows = document.querySelectorAll('#suppliersTable tbody tr');
                
                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    if (text.includes(searchTerm)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
            
            // Delete supplier
            document.querySelectorAll('.delete-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const supplierId = this.getAttribute('data-id');
                    
                    if (confirm('Are you sure you want to delete this supplier?')) {
                        fetch(`delete.php?id=${supplierId}`, {
                            method: 'DELETE'
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                this.closest('tr').remove();
                            } else {
                                alert('Error deleting supplier: ' + data.message);
                            }
                        })
                        .catch(error => {
                            alert('Error deleting supplier: ' + error);
                        });
                    }
                });
            });
        });
    </script>

    <?php include '../../includes/footer.php'; ?>