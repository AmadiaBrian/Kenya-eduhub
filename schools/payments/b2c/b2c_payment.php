<?php
/**
 * B2C Payment Interface
 * Business to Customer payment form
 */

require_once '../dashboard/header.php';

// Get current user info
$username = $_SESSION['username'] ?? '';
$userData = null;

if (!empty($username)) {
    $userQuery = $db->prepare("SELECT * FROM users WHERE username = ?");
    $userQuery->bind_param("s", $username);
    $userQuery->execute();
    $userData = $userQuery->get_result()->fetch_assoc();
    $userQuery->close();
}

// Get business balance from database
$businessBalance = 0;
// For now, we'll use the same user_balances table but look for a business account
// You might want to create a separate business_balances table
$businessQuery = $db->prepare("SELECT balance FROM user_balances WHERE user_id = 1 LIMIT 1");
$businessQuery->execute();
$businessResult = $businessQuery->get_result();
if ($businessRow = $businessResult->fetch_assoc()) {
    $businessBalance = $businessRow['balance'];
}
$businessQuery->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>B2C Payment - Send Money to Customers</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: #000000;
            color: #ffffff;
            line-height: 1.6;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            color: #ffffff;
        }
        
        .header p {
            color: #cccccc;
            font-size: 1.1rem;
        }
        
        .balance-card {
            background: #000000;
            border: 1px solid #333333;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .balance-amount {
            font-size: 2.5rem;
            font-weight: bold;
            color: #28a745;
            margin-bottom: 5px;
        }
        
        .balance-label {
            color: #cccccc;
            font-size: 1rem;
        }
        
        .toggle-payment-btn {
            background: #000000;
            color: #ffffff;
            border: 1px solid #333333;
            padding: 14px 28px;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 20px;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .toggle-payment-btn:hover {
            background: #333333;
            border-color: #555555;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(255, 255, 255, 0.1);
        }
        
        .toggle-payment-btn.active {
            background: #333333;
            border-color: #555555;
        }
        
        .payment-form {
            background: #000000;
            border: 1px solid #333333;
            border-radius: 8px;
            padding: 30px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #ffffff;
            font-weight: 500;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #333333;
            border-radius: 6px;
            background: #000000;
            color: #ffffff;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #0078d4;
        }
        
        .form-group input::placeholder {
            color: #666666;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .btn {
            background: #000000;
            color: #ffffff;
            border: 1px solid #333333;
            padding: 14px 28px;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        
        .btn:hover {
            background: #1a1a1a;
            border-color: #555555;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(255, 255, 255, 0.1);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #0078d4, #106ebe);
            border-color: #0078d4;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #106ebe, #005a9e);
            border-color: #005a9e;
        }
        
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        .btn-group {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 6px;
            margin-bottom: 20px;
            border: 1px solid;
        }
        
        .alert-success {
            background: rgba(40, 167, 69, 0.1);
            border-color: #28a745;
            color: #28a745;
        }
        
        .alert-error {
            background: rgba(220, 53, 69, 0.1);
            border-color: #dc3545;
            color: #dc3545;
        }
        
        .alert-info {
            background: rgba(0, 123, 255, 0.1);
            border-color: #007bff;
            color: #007bff;
        }
        
        .loading {
            display: none;
            text-align: center;
            padding: 20px;
        }
        
        .spinner {
            border: 3px solid #333333;
            border-top: 3px solid #0078d4;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 15px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .recent-b2c {
            margin-top: 40px;
        }
        
        .recent-b2c h3 {
            margin-bottom: 20px;
            color: #ffffff;
        }
        
        .transaction-list {
            background: #000000;
            border: 1px solid #333333;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .transaction-item {
            padding: 15px 20px;
            border-bottom: 1px solid #333333;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .transaction-item:last-child {
            border-bottom: none;
        }
        
        .transaction-info {
            flex: 1;
        }
        
        .transaction-amount {
            font-weight: bold;
            color: #28a745;
        }
        
        .transaction-phone {
            color: #cccccc;
            font-family: monospace;
        }
        
        .transaction-status {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-success {
            background: rgba(40, 167, 69, 0.2);
            color: #28a745;
        }
        
        .status-pending {
            background: rgba(255, 193, 7, 0.2);
            color: #ffc107;
        }
        
        .status-failed {
            background: rgba(220, 53, 69, 0.2);
            color: #dc3545;
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .btn-group {
                flex-direction: column;
            }
            
            .transaction-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>💸 B2C Payment</h1>
            <p>Send money from your business account to customer M-Pesa wallets</p>
        </div>
        
        <div class="balance-card">
            <div class="balance-amount">Ksh <?php echo number_format($businessBalance); ?></div>
            <div class="balance-label">Available Business Balance</div>
        </div>
        
        <button type="button" class="toggle-payment-btn" onclick="togglePaymentSection()">
            <i class="fas fa-plus-circle"></i> Send Money
        </button>
        
        <div class="payment-form" id="paymentSection" style="display: none;">
            <form id="b2cForm">
                <div class="form-row">
                    <div class="form-group">
                        <label for="phone">Customer Phone Number</label>
                        <input type="tel" id="phone" name="phone" placeholder="07XXXXXXXX or 254XXXXXXXXX" required>
                    </div>
                    <div class="form-group">
                        <label for="amount">Amount (Ksh)</label>
                        <input type="number" id="amount" name="amount" placeholder="1000" min="10" max="<?php echo $businessBalance; ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="remarks">Payment Remarks</label>
                    <textarea id="remarks" name="remarks" rows="3" placeholder="Enter payment description (e.g., Salary payment, Refund, Commission)"></textarea>
                </div>
                
                <div class="alert alert-info">
                    <strong>Note:</strong> B2C payments are instant and don't require customer PIN confirmation. The customer will receive an SMS notification.
                </div>
                
                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i>
                        Send Money
                    </button>
                    <button type="button" class="btn" onclick="resetForm()">
                        <i class="fas fa-redo"></i>
                        Clear
                    </button>
                </div>
            </form>
            
            <div class="loading" id="loading">
                <div class="spinner"></div>
                <p>Processing B2C payment...</p>
            </div>
            
            <div id="result"></div>
        </div>
        
        <div class="recent-b2c">
            <h3>Recent B2C Transactions</h3>
            <div class="transaction-list" id="recentTransactions">
                <?php
                // Get recent B2C transactions (we'll identify B2C by looking for transactions that are outgoing)
                // Since there's no TransactionType or remarks column, we'll use CheckoutRequestID pattern only
                $b2cQuery = $db->prepare("SELECT * FROM transactions WHERE CheckoutRequestID LIKE 'B2C_%' ORDER BY ID DESC LIMIT 5");
                $b2cQuery->execute();
                $b2cResult = $b2cQuery->get_result();
                
                if ($b2cResult->num_rows > 0):
                    while ($transaction = $b2cResult->fetch_assoc()):
                        $status = 'pending';
                        $statusClass = 'status-pending';
                        
                        if ($transaction['ResultCode'] === '0' || $transaction['ResultCode'] === 0) {
                            $status = 'success';
                            $statusClass = 'status-success';
                        } elseif ($transaction['ResultCode'] !== '' && $transaction['ResultCode'] !== 'pending') {
                            $status = 'failed';
                            $statusClass = 'status-failed';
                        }
                ?>
                    <div class="transaction-item">
                        <div class="transaction-info">
                            <div class="transaction-amount">Ksh <?php echo number_format($transaction['Amount']); ?></div>
                            <div class="transaction-phone"><?php echo $transaction['PhoneNumber']; ?></div>
                        </div>
                        <div class="transaction-status <?php echo $statusClass; ?>"><?php echo $status; ?></div>
                    </div>
                <?php
                    endwhile;
                else:
                ?>
                    <div class="transaction-item">
                        <div class="transaction-info">
                            <div style="color: #666;">No B2C transactions yet</div>
                        </div>
                    </div>
                <?php
                endif;
                $b2cQuery->close();
                ?>
            </div>
        </div>
    </div>
    
    <script>
        // Toggle payment section visibility
        function togglePaymentSection() {
            const paymentSection = document.getElementById('paymentSection');
            const toggleBtn = document.querySelector('.toggle-payment-btn');
            
            if (paymentSection.style.display === 'none') {
                paymentSection.style.display = 'block';
                toggleBtn.innerHTML = '<i class="fas fa-minus-circle"></i> Hide Send Money';
                toggleBtn.classList.add('active');
            } else {
                paymentSection.style.display = 'none';
                toggleBtn.innerHTML = '<i class="fas fa-plus-circle"></i> Send Money';
                toggleBtn.classList.remove('active');
            }
        }
        
        document.getElementById('b2cForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const loading = document.getElementById('loading');
            const result = document.getElementById('result');
            const submitBtn = e.target.querySelector('button[type="submit"]');
            
            // Show loading
            loading.style.display = 'block';
            result.innerHTML = '';
            submitBtn.disabled = true;
            
            try {
                const formData = new FormData(e.target);
                const response = await fetch('b2c_process.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    result.innerHTML = `
                        <div class="alert alert-success">
                            <strong>Success!</strong> ${data.message}
                            <br>Conversation ID: ${data.ConversationID || 'N/A'}
                        </div>
                    `;
                    
                    // Reset form
                    e.target.reset();
                    
                    // Refresh recent transactions after 2 seconds
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                } else {
                    result.innerHTML = `
                        <div class="alert alert-error">
                            <strong>Error:</strong> ${data.message}
                        </div>
                    `;
                }
            } catch (error) {
                result.innerHTML = `
                    <div class="alert alert-error">
                        <strong>Error:</strong> Failed to process payment. Please try again.
                    </div>
                `;
            } finally {
                loading.style.display = 'none';
                submitBtn.disabled = false;
            }
        });
        
        function resetForm() {
            document.getElementById('b2cForm').reset();
            document.getElementById('result').innerHTML = '';
        }
        
        // Format phone number as user types
        document.getElementById('phone').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length === 9 && !value.startsWith('254')) {
                value = '254' + value;
            }
            e.target.value = value;
        });
    </script>
</body>
</html>
