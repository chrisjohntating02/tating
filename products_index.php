<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

checkAuthentication();

$pageTitle = "Products";
include '../../includes/header.php';

// Get all products
$products = $pdo->query("
    SELECT p.*, s.name as supplier_name 
    FROM products p
    LEFT JOIN suppliers s ON p.supplier_id = s.id
    ORDER BY p.name
")->fetchAll();

// Get suppliers for filter
$suppliers = $pdo->query("SELECT id, name FROM suppliers ORDER BY name")->fetchAll();
?>

<div class="products-container">
    <div class="page-header">
        <h1>Products</h1>
        <div class="header-actions">
            <a href="add.php" class="add-button">Add Product</a>
        </div>
    </div>
    
    <div class="toolbar">
        <div class="search-bar">
            <input type="text" id="productSearch" placeholder="Search products...">
        </div>
        <div class="filters">
            <select id="supplierFilter">
                <option value="">All Suppliers</option>
                <?php foreach ($suppliers as $supplier): ?>
                    <option value="<?php echo $supplier['id']; ?>"><?php echo htmlspecialchars($supplier['name']); ?></option>
                <?php endforeach; ?>
            </select>
            <select id="stockFilter">
                <option value="">All Stock</option>
                <option value="low">Low Stock</option>
                <option value="out">Out of Stock</option>
            </select>
        </div>
    </div>
    
    <div class="table-responsive">
        <table id="productsTable" class="data-table">
            <thead>
                <tr>
                    <th>Barcode</th>
                    <th>Product Name</th>
                    <th>Brand</th>
                    <th>Category</th>
                    <th>Supplier</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                    <tr data-supplier="<?php echo $product['supplier_id']; ?>" data-stock="<?php echo $product['quantity']; ?>">
                        <td><?php echo htmlspecialchars($product['barcode']); ?></td>
                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                        <td><?php echo htmlspecialchars($product['brand']); ?></td>
                        <td><?php echo htmlspecialchars($product['category']); ?></td>
                        <td><?php echo htmlspecialchars($product['supplier_name']); ?></td>
                        <td>₱<?php echo number_format($product['selling_price'], 2); ?></td>
                        <td><?php echo $product['quantity']; ?></td>
                        <td>
                            <?php if ($product['quantity'] <= 0): ?>
                                <span class="status-badge danger">Out of Stock</span>
                            <?php elseif ($product['quantity'] <= $product['reorder_level']): ?>
                                <span class="status-badge warning">Low Stock</span>
                            <?php else: ?>
                                <span class="status-badge success">In Stock</span>
                            <?php endif; ?>
                        </td>
                        <td class="actions">
                            <a href="edit.php?id=<?php echo $product['id']; ?>" class="edit-btn">Edit</a>
                            <a href="#" class="delete-btn" data-id="<?php echo $product['id']; ?>">Delete</a>
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
        document.getElementById('productSearch').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('#productsTable tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
        
        // Filter by supplier
        document.getElementById('supplierFilter').addEventListener('change', function() {
            const supplierId = this.value;
            const stockFilter = document.getElementById('stockFilter').value;
            filterProducts(supplierId, stockFilter);
        });
        
        // Filter by stock
        document.getElementById('stockFilter').addEventListener('change', function() {
            const stockFilter = this.value;
            const supplierId = document.getElementById('supplierFilter').value;
            filterProducts(supplierId, stockFilter);
        });
        
        function filterProducts(supplierId, stockFilter) {
            const rows = document.querySelectorAll('#productsTable tbody tr');
            
            rows.forEach(row => {
                const rowSupplier = row.getAttribute('data-supplier');
                const rowStock = parseInt(row.getAttribute('data-stock'));
                let show = true;
                
                // Apply supplier filter
                if (supplierId && rowSupplier !== supplierId) {
                    show = false;
                }
                
                // Apply stock filter
                if (stockFilter === 'low' && rowStock > row.dataset.reorderLevel) {
                    show = false;
                } else if (stockFilter === 'out' && rowStock > 0) {
                    show = false;
                }
                
                row.style.display = show ? '' : 'none';
            });
        }
        
        // Delete product
        document.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const productId = this.getAttribute('data-id');
                
                if (confirm('Are you sure you want to delete this product?')) {
                    fetch(`delete.php?id=${productId}`, {
                        method: 'DELETE'
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            this.closest('tr').remove();
                        } else {
                            alert('Error deleting product: ' + data.message);
                        }
                    })
                    .catch(error => {
                        alert('Error deleting product: ' + error);
                    });
                }
            });
        });
    });
</script>

<?php include '../../includes/footer.php'; ?>a