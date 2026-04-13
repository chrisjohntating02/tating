<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

checkAuthentication();

$pageTitle = "New Sale";
include '../../includes/header.php';

// Get products
$products = $pdo->query("SELECT id, name, selling_price, quantity FROM products WHERE quantity > 0 ORDER BY name")->fetchAll();

// Get customers
$customers = $pdo->query("SELECT id, name, customer_type FROM customers ORDER BY name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customerId = isset($_POST['customer_id']) ? (int)$_POST['customer_id'] : null;
    $customerName = isset($_POST['customer_name']) ? trim($_POST['customer_name']) : 'Walk-in Customer';
    $customerType = isset($_POST['customer_type']) ? $_POST['customer_type'] : 'regular';
    $paymentMethod = $_POST['payment_method'];
    $paymentAmount = (float)$_POST['payment_amount'];
    $items = $_POST['items'];
    $discountType = $_POST['discount_type'] ?? 'none'; // Get discount type from form
    
    // Calculate totals
    $subtotal = 0;
    $discountRate = 0;
    
    // Determine discount rate based on discount type and customer type
    if ($customerType === 'senior' || $customerType === 'pwd') {
        $discountRate = 0.20; // 20% for senior/PWD (mandatory)
    } else {
        // Apply personal discount only if not PWD/Senior
        switch ($discountType) {
            case '5':
                $discountRate = 0.05;
                break;
            case '10':
                $discountRate = 0.10;
                break;
            case 'none':
            default:
                $discountRate = 0;
                break;
        }
    }
    
    // Process items
    $saleItems = [];
    foreach ($items as $item) {
        $productId = (int)$item['product_id'];
        $quantity = (int)$item['quantity'];
        
        $product = $pdo->query("SELECT * FROM products WHERE id = $productId")->fetch();
        
        if ($product) {
            $price = $product['selling_price'];
            $total = $price * $quantity;
            $subtotal += $total;
            
            $saleItems[] = [
                'product_id' => $productId,
                'product_name' => $product['name'],
                'quantity' => $quantity,
                'price' => $price,
                'total' => $total
            ];
        }
    }
    
    // Calculate discount and VAT
    $discountAmount = $subtotal * $discountRate;
    $vatableAmount = $subtotal - $discountAmount;
    $vatAmount = $vatableAmount * 0.12; // 12% VAT
    $totalAmount = $vatableAmount + $vatAmount;
    $changeAmount = $paymentAmount - $totalAmount;
    
    // Generate invoice number
    $invoiceNumber = 'INV-' . date('Ymd') . '-' . strtoupper(uniqid());
    
    try {
        $pdo->beginTransaction();
        
        // Create sale record
        $stmt = $pdo->prepare("
            INSERT INTO sales (
                invoice_number, customer_id, customer_name, customer_type, 
                subtotal, discount_amount, vat_amount, total_amount, 
                payment_method, payment_amount, change_amount, user_id, discount_type
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $invoiceNumber,
            $customerId,
            $customerName,
            $customerType,
            $subtotal,
            $discountAmount,
            $vatAmount,
            $totalAmount,
            $paymentMethod,
            $paymentAmount,
            $changeAmount,
            $_SESSION['user_id'],
            $discountType
        ]);
        
        $saleId = $pdo->lastInsertId();
        
        // Create sale items
        foreach ($saleItems as $item) {
            $stmt = $pdo->prepare("
                INSERT INTO sale_items (
                    sale_id, product_id, product_name, quantity, price, total
                ) VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $saleId,
                $item['product_id'],
                $item['product_name'],
                $item['quantity'],
                $item['price'],
                $item['total']
            ]);
            
            // Update product quantity
            $stmt = $pdo->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ?");
            $stmt->execute([$item['quantity'], $item['product_id']]);
            
            // Log inventory change
            logInventoryChange($item['product_id'], -$item['quantity'], 'sale', $saleId, "Sold in sale #$saleId", $_SESSION['user_id']);
        }
        
        // Log activity
        logActivity($_SESSION['user_id'], 'sale', "Created new sale #$saleId with total ₱" . number_format($totalAmount, 2));
        
        $pdo->commit();
        
        // Redirect to receipt
        header("Location: receipt.php?id=$saleId");
        exit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "Error processing sale: " . $e->getMessage();
    }
}
?>

<div class="sales-container">
    <div class="page-header">
        <h1>New Sale</h1>
        <a href="index.php" class="back-button">Back to Sales</a>
    </div>
    
    <?php if (isset($error)): ?>
        <div class="alert error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <div class="sale-form-container">
        <form id="saleForm" method="POST">
            <div class="form-row">
                <div class="form-group customer-info">
                    <label for="customer_search">Customer</label>
                    <div class="customer-search-container">
                        <input type="text" id="customer_search" placeholder="Search customer...">
                        <select id="customer_id" name="customer_id" class="hidden">
                            <option value="">Walk-in Customer</option>
                            <?php foreach ($customers as $customer): ?>
                                <option value="<?php echo $customer['id']; ?>" 
                                    data-type="<?php echo $customer['customer_type']; ?>">
                                    <?php echo htmlspecialchars($customer['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="button" id="newCustomerBtn" class="small-button">New Customer</button>
                    </div>
                    <input type="hidden" id="customer_type" name="customer_type" value="regular">
                    <input type="hidden" id="customer_name" name="customer_name" value="Walk-in Customer">
                </div>
                
                <div class="form-group discount-info">
                    <label>Discount</label>
                    <div class="discount-options">
                        <div class="discount-option">
                            <input type="radio" id="discount_none" name="discount_type" value="none" checked>
                            <label for="discount_none">None</label>
                        </div>
                        <div class="discount-option">
                            <input type="radio" id="discount_5" name="discount_type" value="5">
                            <label for="discount_5">5%</label>
                        </div>
                        <div class="discount-option">
                            <input type="radio" id="discount_10" name="discount_type" value="10">
                            <label for="discount_10">10%</label>
                        </div>
                        <div class="discount-option">
                            <input type="radio" id="discount_20" name="discount_type" value="20" disabled>
                            <label for="discount_20">20% (PWD/Senior)</label>
                        </div>
                    </div>
                    <input type="hidden" id="discount_rate" name="discount_rate" value="0">
                </div>
            </div>
            
            <div class="form-row">
                <div class="product-selection">
                    <h3>Products</h3>
                    <div class="product-search-container">
                        <input type="text" id="product_search" placeholder="Search products...">
                        <select id="product_select" class="hidden">
                            <?php foreach ($products as $product): ?>
                                <option value="<?php echo $product['id']; ?>" 
                                    data-price="<?php echo $product['selling_price']; ?>"
                                    data-quantity="<?php echo $product['quantity']; ?>">
                                    <?php echo htmlspecialchars($product['name']); ?> (₱<?php echo number_format($product['selling_price'], 2); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <table id="productTable" class="product-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Total</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Products will be added here dynamically -->
                        </tbody>
                    </table>
                </div>
                
                <div class="payment-summary">
                    <h3>Payment Summary</h3>
                    <div class="summary-item">
                        <span>Subtotal:</span>
                        <span id="subtotal">₱0.00</span>
                    </div>
                    <div class="summary-item">
                        <span>Discount:</span>
                        <span id="discount">₱0.00 (0%)</span>
                    </div>
                    <div class="summary-item">
                        <span>VATable Amount:</span>
                        <span id="vatable">₱0.00</span>
                    </div>
                    <div class="summary-item">
                        <span>12% VAT:</span>
                        <span id="vat">₱0.00</span>
                    </div>
                    <div class="summary-item total">
                        <span>Total Amount:</span>
                        <span id="total">₱0.00</span>
                    </div>
                    
                    <div class="payment-method">
                        <label for="payment_method">Payment Method</label>
                        <select id="payment_method" name="payment_method" required>
                            <option value="cash">Cash</option>
                            <option value="card">Credit/Debit Card</option>
                            <option value="gcash">GCash</option>
                        </select>
                    </div>
                    
                    <div class="payment-amount">
                        <label for="payment_amount">Amount Received</label>
                        <input type="number" id="payment_amount" name="payment_amount" min="0" step="0.01" required>
                    </div>
                    
                    <div class="summary-item change">
                        <span>Change:</span>
                        <span id="change">₱0.00</span>
                    </div>
                    
                    <button type="submit" class="submit-button">Complete Sale</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- New Customer Modal -->
<div id="customerModal" class="modal">
    <div class="modal-content">
        <span class="close-modal">&times;</span>
        <h2>Add New Customer</h2>
        <form id="customerForm">
            <div class="form-group">
                <label for="new_customer_name">Full Name</label>
                <input type="text" id="new_customer_name" required>
            </div>
            <div class="form-group">
                <label for="new_customer_phone">Phone</label>
                <input type="text" id="new_customer_phone">
            </div>
            <div class="form-group">
                <label for="new_customer_type">Customer Type</label>
                <select id="new_customer_type">
                    <option value="regular">Regular</option>
                    <option value="senior">Senior Citizen</option>
                    <option value="pwd">PWD</option>
                </select>
            </div>
            <button type="submit" class="submit-button">Save Customer</button>
        </form>
    </div>
</div>

<script src="../../assets/js/script.js"></script>
<script>
    // Sale form JavaScript
    document.addEventListener('DOMContentLoaded', function() {
        // Customer selection
        const customerSearch = document.getElementById('customer_search');
        const customerSelect = document.getElementById('customer_id');
        const customerTypeInput = document.getElementById('customer_type');
        const customerNameInput = document.getElementById('customer_name');
        const discount20Radio = document.getElementById('discount_20');
        
        customerSearch.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const options = customerSelect.options;
            
            for (let i = 0; i < options.length; i++) {
                const option = options[i];
                if (option.text.toLowerCase().includes(searchTerm)) {
                    option.style.display = '';
                } else {
                    option.style.display = 'none';
                }
            }
        });
        
        customerSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption.value === '') {
                customerNameInput.value = 'Walk-in Customer';
                customerTypeInput.value = 'regular';
                discount20Radio.disabled = true;
                if (discount20Radio.checked) {
                    document.getElementById('discount_none').checked = true;
                    updateDiscountRate(0);
                }
            } else {
                customerNameInput.value = selectedOption.text;
                customerTypeInput.value = selectedOption.dataset.type;
                
                if (selectedOption.dataset.type === 'senior' || selectedOption.dataset.type === 'pwd') {
                    // Automatically apply 20% discount for PWD/Senior and disable other options
                    discount20Radio.disabled = false;
                    discount20Radio.checked = true;
                    updateDiscountRate(20);
                    
                    // Disable other discount options
                    document.getElementById('discount_none').disabled = true;
                    document.getElementById('discount_5').disabled = true;
                    document.getElementById('discount_10').disabled = true;
                } else {
                    // Regular customer - enable all options except PWD/Senior
                    discount20Radio.disabled = true;
                    document.getElementById('discount_none').disabled = false;
                    document.getElementById('discount_5').disabled = false;
                    document.getElementById('discount_10').disabled = false;
                    
                    if (discount20Radio.checked) {
                        document.getElementById('discount_none').checked = true;
                        updateDiscountRate(0);
                    }
                }
            }
            calculateTotals();
        });
        
        // Discount selection
        const discountRadios = document.querySelectorAll('input[name="discount_type"]');
        discountRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                if (this.checked) {
                    let rate = 0;
                    switch(this.value) {
                        case '5': rate = 5; break;
                        case '10': rate = 10; break;
                        case '20': rate = 20; break;
                        default: rate = 0;
                    }
                    updateDiscountRate(rate);
                }
            });
        });
        
        function updateDiscountRate(rate) {
            document.getElementById('discount_rate').value = rate;
            calculateTotals();
        }
        
        // Product selection
        const productSearch = document.getElementById('product_search');
        const productSelect = document.getElementById('product_select');
        const productTable = document.getElementById('productTable').getElementsByTagName('tbody')[0];
        
        productSearch.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const options = productSelect.options;
            
            for (let i = 0; i < options.length; i++) {
                const option = options[i];
                if (option.text.toLowerCase().includes(searchTerm)) {
                    option.style.display = '';
                } else {
                    option.style.display = 'none';
                }
            }
        });
        
        productSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption.value) {
                addProductToTable(
                    selectedOption.value,
                    selectedOption.text.split(' (₱')[0],
                    parseFloat(selectedOption.dataset.price),
                    parseInt(selectedOption.dataset.quantity)
                );
                this.selectedIndex = 0;
                productSearch.value = '';
            }
        });
        
        function addProductToTable(id, name, price, available) {
            // Check if product already exists in table
            const existingRow = productTable.querySelector(`tr[data-product-id="${id}"]`);
            if (existingRow) {
                const qtyInput = existingRow.querySelector('.quantity-input');
                const newQty = parseInt(qtyInput.value) + 1;
                if (newQty <= available) {
                    qtyInput.value = newQty;
                    updateProductTotal(existingRow);
                } else {
                    alert(`Only ${available} units available in stock.`);
                }
                return;
            }
            
            const row = document.createElement('tr');
            row.setAttribute('data-product-id', id);
            row.innerHTML = `
                <td>${name}</td>
                <td class="price">₱${price.toFixed(2)}</td>
                <td>
                    <input type="number" class="quantity-input" value="1" min="1" max="${available}" data-price="${price}">
                </td>
                <td class="total">₱${price.toFixed(2)}</td>
                <td><button type="button" class="remove-product">Remove</button></td>
            `;
            
            productTable.appendChild(row);
            
            // Add event listeners
            row.querySelector('.quantity-input').addEventListener('change', function() {
                updateProductTotal(row);
            });
            
            row.querySelector('.remove-product').addEventListener('click', function() {
                row.remove();
                calculateTotals();
            });
            
            calculateTotals();
        }
        
        function updateProductTotal(row) {
            const qty = parseInt(row.querySelector('.quantity-input').value);
            const price = parseFloat(row.querySelector('.quantity-input').dataset.price);
            const total = qty * price;
            row.querySelector('.total').textContent = `₱${total.toFixed(2)}`;
            calculateTotals();
        }
        
        // Calculate all totals
        function calculateTotals() {
            let subtotal = 0;
            const rows = productTable.querySelectorAll('tr');
            
            rows.forEach(row => {
                subtotal += parseFloat(row.querySelector('.total').textContent.replace('₱', ''));
            });
            
            const discountRate = parseFloat(document.getElementById('discount_rate').value) / 100;
            const discountAmount = subtotal * discountRate;
            const vatableAmount = subtotal - discountAmount;
            const vatAmount = vatableAmount * 0.12;
            const totalAmount = vatableAmount + vatAmount;
            
            document.getElementById('subtotal').textContent = `₱${subtotal.toFixed(2)}`;
            document.getElementById('discount').textContent = `₱${discountAmount.toFixed(2)} (${(discountRate * 100)}%)`;
            document.getElementById('vatable').textContent = `₱${vatableAmount.toFixed(2)}`;
            document.getElementById('vat').textContent = `₱${vatAmount.toFixed(2)}`;
            document.getElementById('total').textContent = `₱${totalAmount.toFixed(2)}`;
            
            // Update change
            const paymentAmount = parseFloat(document.getElementById('payment_amount').value) || 0;
            const change = paymentAmount - totalAmount;
            document.getElementById('change').textContent = `₱${change.toFixed(2)}`;
            
            // Update hidden fields for form submission
            updateHiddenFormFields();
        }
        
        // Payment amount change
        document.getElementById('payment_amount').addEventListener('input', function() {
            calculateTotals();
        });
        
        // Update hidden form fields for submission
        function updateHiddenFormFields() {
            const items = [];
            const rows = productTable.querySelectorAll('tr');
            
            rows.forEach(row => {
                items.push({
                    product_id: row.getAttribute('data-product-id'),
                    quantity: parseInt(row.querySelector('.quantity-input').value)
                });
            });
            
            // Create a hidden input for items
            let itemsInput = document.querySelector('input[name="items"]');
            if (!itemsInput) {
                itemsInput = document.createElement('input');
                itemsInput.type = 'hidden';
                itemsInput.name = 'items';
                document.getElementById('saleForm').appendChild(itemsInput);
            }
            itemsInput.value = JSON.stringify(items);
        }
        
        // New customer modal
        const modal = document.getElementById('customerModal');
        const newCustomerBtn = document.getElementById('newCustomerBtn');
        const closeModal = document.querySelector('.close-modal');
        
        newCustomerBtn.addEventListener('click', function() {
            modal.style.display = 'block';
        });
        
        closeModal.addEventListener('click', function() {
            modal.style.display = 'none';
        });
        
        window.addEventListener('click', function(event) {
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        });
        
        // Save new customer
        document.getElementById('customerForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const name = document.getElementById('new_customer_name').value;
            const phone = document.getElementById('new_customer_phone').value;
            const type = document.getElementById('new_customer_type').value;
            
            // In a real application, you would send this to the server via AJAX
            // For this example, we'll just add it to the select
            const newOption = document.createElement('option');
            newOption.value = 'new'; // In real app, this would be the ID from the server
            newOption.text = name;
            newOption.dataset.type = type;
            customerSelect.appendChild(newOption);
            customerSelect.selectedIndex = customerSelect.options.length - 1;
            customerSelect.dispatchEvent(new Event('change'));
            
            // Clear and close modal
            this.reset();
            modal.style.display = 'none';
        });
    });
</script>

<?php include '../../includes/footer.php'; ?>