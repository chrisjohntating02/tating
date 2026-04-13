<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

checkAuthentication();

$saleId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get sale data
$stmt = $pdo->prepare("
    SELECT s.*, u.username as cashier 
    FROM sales s
    JOIN admins u ON s.user_id = u.id
    WHERE s.id = ?
");
$stmt->execute([$saleId]);
$sale = $stmt->fetch();

if (!$sale) {
    header('Location: index.php');
    exit();
}

// Get sale items
$items = $pdo->prepare("SELECT * FROM sale_items WHERE sale_id = ?");
$items->execute([$saleId]);
$saleItems = $items->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt - Sale #<?php echo $saleId; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #f5f5f5;
            margin: 0;
            padding: 20px;
        }
        
        .receipt-container {
            max-width: 400px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            padding: 25px;
        }
        
        .receipt-header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 1px dashed #ddd;
            padding-bottom: 15px;
        }
        
        .receipt-header h1 {
            margin: 0;
            font-size: 24px;
            color: #333;
        }
        
        .receipt-header p {
            margin: 5px 0;
            color: #666;
            font-size: 14px;
        }
        
        .receipt-details {
            margin-bottom: 20px;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .detail-label {
            font-weight: 500;
            color: #555;
        }
        
        .detail-value {
            color: #333;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .items-table th {
            text-align: left;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
            font-size: 14px;
            color: #666;
        }
        
        .items-table td {
            padding: 8px 0;
            border-bottom: 1px solid #eee;
            font-size: 14px;
        }
        
        .items-table .item-name {
            width: 50%;
        }
        
        .items-table .item-price, 
        .items-table .item-qty,
        .items-table .item-total {
            width: 16.66%;
            text-align: right;
        }
        
        .receipt-totals {
            border-top: 1px dashed #ddd;
            padding-top: 15px;
            margin-top: 15px;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 15px;
        }
        
        .total-label {
            font-weight: 500;
        }
        
        .total-amount {
            font-weight: 600;
        }
        
        .grand-total {
            font-size: 18px;
            font-weight: 700;
            color: #2c3e50;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 2px solid #eee;
        }
        
        .receipt-footer {
            text-align: center;
            margin-top: 25px;
            font-size: 12px;
            color: #888;
            border-top: 1px dashed #ddd;
            padding-top: 15px;
        }
        
        .button-group {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
        }
        
        .print-btn, .back-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .print-btn {
            background: #3498db;
            color: white;
        }
        
        .back-btn {
            background: #e74c3c;
            color: white;
        }
        
        .print-btn:hover {
            background: #2980b9;
        }
        
        .back-btn:hover {
            background: #c0392b;
        }
        
        @media print {
            body {
                background: white;
                padding: 0;
            }
            
            .button-group {
                display: none;
            }
            
            .receipt-container {
                box-shadow: none;
                max-width: 100%;
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <div class="receipt-header">
            <h1>Botika Generics</h1>
            <p>123 Pharmacy Street, Medicine City</p>
            <p>Tel: (02) 1234-5678</p>
            <p>VAT Reg. TIN: 123-456-789-0000</p>
        </div>
        
        <div class="receipt-details">
            <div class="detail-row">
                <span class="detail-label">Invoice #:</span>
                <span class="detail-value"><?php echo htmlspecialchars($sale['invoice_number']); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Date:</span>
                <span class="detail-value"><?php echo date('M j, Y h:i A', strtotime($sale['created_at'])); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Customer:</span>
                <span class="detail-value"><?php echo htmlspecialchars($sale['customer_name']); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Cashier:</span>
                <span class="detail-value"><?php echo htmlspecialchars($sale['cashier']); ?></span>
            </div>
        </div>
        
        <table class="items-table">
            <thead>
                <tr>
                    <th class="item-name">Item</th>
                    <th class="item-price">Price</th>
                    <th class="item-qty">Qty</th>
                    <th class="item-total">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($saleItems as $item): ?>
                    <tr>
                        <td class="item-name"><?php echo htmlspecialchars($item['product_name']); ?></td>
                        <td class="item-price">₱<?php echo number_format($item['price'], 2); ?></td>
                        <td class="item-qty"><?php echo $item['quantity']; ?></td>
                        <td class="item-total">₱<?php echo number_format($item['total'], 2); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div class="receipt-totals">
            <div class="total-row">
                <span class="total-label">Subtotal:</span>
                <span class="total-amount">₱<?php echo number_format($sale['subtotal'], 2); ?></span>
            </div>
            
            <?php if ($sale['discount_amount'] > 0): ?>
                <div class="total-row">
                    <span class="total-label">Discount (<?php echo ($sale['discount_amount'] / $sale['subtotal'] * 100); ?>%):</span>
                    <span class="total-amount">-₱<?php echo number_format($sale['discount_amount'], 2); ?></span>
                </div>
                
                <div class="total-row">
                    <span class="total-label">VATable Amount:</span>
                    <span class="total-amount">₱<?php echo number_format($sale['subtotal'] - $sale['discount_amount'], 2); ?></span>
                </div>
            <?php endif; ?>
            
            <div class="total-row">
                <span class="total-label">12% VAT:</span>
                <span class="total-amount">₱<?php echo number_format($sale['vat_amount'], 2); ?></span>
            </div>
            
            <div class="total-row grand-total">
                <span>Total Amount:</span>
                <span>₱<?php echo number_format($sale['total_amount'], 2); ?></span>
            </div>
            
            <div class="total-row">
                <span class="total-label">Payment Method:</span>
                <span class="total-amount"><?php echo ucfirst($sale['payment_method']); ?></span>
            </div>
            
            <div class="total-row">
                <span class="total-label">Amount Received:</span>
                <span class="total-amount">₱<?php echo number_format($sale['payment_amount'], 2); ?></span>
            </div>
            
            <div class="total-row">
                <span class="total-label">Change:</span>
                <span class="total-amount">₱<?php echo number_format($sale['change_amount'], 2); ?></span>
            </div>
        </div>
        
        <div class="receipt-footer">
            <p>Thank you for your purchase!</p>
            <p>This receipt serves as your official invoice</p>
            <p>Please present this receipt for returns or exchanges within 7 days</p>
        </div>
        
        <div class="button-group">
            <button class="print-btn" onclick="window.print()">Print Receipt</button>
            <button class="back-btn" onclick="window.location.href='index.php'">Back to Sales</button>
        </div>
    </div>
</body>
</html>