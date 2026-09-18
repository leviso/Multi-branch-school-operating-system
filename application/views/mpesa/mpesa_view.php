<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMS Credit Purchase</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            background-color: #f0f2f5;
        }
        .card {
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .btn-primary {
            background-color: #1a73e8;
            color: white;
        }
        .btn-primary:hover {
            background-color: #1765cc;
        }
        .loader {
            border: 4px solid #f3f3f3;
            border-radius: 50%;
            border-top: 4px solid #1a73e8;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body class="font-sans antialiased text-gray-800">

    <div class="container mx-auto p-4 md:p-8 max-w-4xl">
        <h1 class="text-3xl md:text-4xl font-bold text-center mb-8 text-gray-700">SMS Marketing Portal</h1>

        <!-- SMS Credit Balance -->
        <div class="card p-6 mb-8">
            <h2 class="text-2xl font-semibold mb-3">Your Balance</h2>
            <p class="text-5xl font-bold text-blue-600" id="sms-credit-display"><?= $user['sms_credit'] ?? 0; ?> <span class="text-2xl font-normal text-gray-500">credits</span></p>
        </div>

        <div class="grid md:grid-cols-2 gap-8">
            <!-- Purchase SMS Credits -->
            <div class="card p-6">
                <h2 class="text-2xl font-semibold mb-4">Purchase SMS Credits</h2>
                <form id="purchase-form">
                    <div class="mb-4">
                        <label for="phone" class="block text-sm font-medium text-gray-600 mb-1">M-Pesa Phone Number</label>
                        <input type="tel" id="phone" name="phone" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="e.g., 0712345678" required>
                    </div>
                    <div class="mb-6">
                        <label for="amount" class="block text-sm font-medium text-gray-600 mb-1">Amount (KES)</label>
                        <input type="number" id="amount" name="amount" min="1" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="1 KES = 1 Credit" required>
                    </div>
                    <button type="submit" id="purchase-btn" class="w-full btn-primary font-bold py-3 px-4 rounded-lg focus:outline-none focus:shadow-outline flex items-center justify-center">
                        <span id="btn-text">Purchase Now</span>
                        <div id="loader" class="loader hidden ml-3"></div>
                    </button>
                </form>
            </div>

            <!-- Send SMS -->
            <div class="card p-6">
                <h2 class="text-2xl font-semibold mb-4">Send an SMS</h2>
                 <form id="send-sms-form">
                    <div class="mb-4">
                        <label for="sms_phone" class="block text-sm font-medium text-gray-600 mb-1">Recipient's Phone</label>
                        <input type="tel" id="sms_phone" name="sms_phone" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="e.g., 07XXXXXXXX" required>
                    </div>
                    <div class="mb-6">
                        <label for="sms_message" class="block text-sm font-medium text-gray-600 mb-1">Message</label>
                        <textarea id="sms_message" name="sms_message" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required></textarea>
                    </div>
                    <button type="submit" id="send-sms-btn" class="w-full bg-green-500 hover:bg-green-600 text-white font-bold py-3 px-4 rounded-lg focus:outline-none focus:shadow-outline">
                        Send SMS
                    </button>
                </form>
            </div>
        </div>

        <!-- Billing History -->
        <div class="card p-6 mt-8">
            <h2 class="text-2xl font-semibold mb-4">Billing History</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200" id="billing-history-table">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">M-Pesa Receipt</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount (KES)</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                       <!-- Rows will be populated by JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const purchaseForm = document.getElementById('purchase-form');
        const purchaseBtn = document.getElementById('purchase-btn');
        const btnText = document.getElementById('btn-text');
        const loader = document.getElementById('loader');
        const smsCreditDisplay = document.getElementById('sms-credit-display');
        const billingTableBody = document.querySelector('#billing-history-table tbody');
        const sendSmsForm = document.getElementById('send-sms-form');

        const BASE_URL = '<?= site_url(); ?>';
        let checkoutRequestId = null;
        let pollingInterval = null;

        // Fetch initial data
        updateDashboard();

        purchaseForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            toggleLoading(true, 'Initiating...');

            const formData = new FormData(this);
            let response;
            try {
                response = await fetch(`${BASE_URL}mpesa/purchase`, {
                    method: 'POST',
                    body: formData
                });
            } catch (error) {
                console.error("Network or fetch error:", error);
                Swal.fire('Network Error', 'Could not connect to the server. Please check your connection and try again.', 'error');
                toggleLoading(false);
                return;
            }

            const result = await response.json();

            if (result.success) {
                checkoutRequestId = result.checkout_request_id;
                toggleLoading(true, 'Waiting for confirmation...');

                Swal.fire({
                    title: 'Awaiting Confirmation',
                    html: 'An STK push has been sent to your phone.<br/>Please enter your M-Pesa PIN to complete the transaction.',
                    icon: 'info',
                    timer: 120000,
                    timerProgressBar: true,
                    allowOutsideClick: false,
                    showCancelButton: true,
                    cancelButtonText: 'Cancel Payment',
                    didOpen: () => {
                        // =================================================================
                        // THE FIX: Wait 7 seconds before starting to poll.
                        // This gives the M-Pesa network time to process the initial request
                        // and prevents the immediate "cancelled" error race condition.
                        setTimeout(startPolling, 7000); // 7000 milliseconds = 7 seconds
                        // =================================================================
                    }
                }).then((dialogResult) => {
                    if (dialogResult.isDismissed) {
                        stopPolling();
                        Swal.fire('Cancelled', 'The transaction was cancelled or it timed out.', 'warning');
                        toggleLoading(false);
                    }
                });

            } else {
                Swal.fire('Error', result.message || 'An unknown error occurred.', 'error');
                toggleLoading(false);
            }
        });
        
        sendSmsForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const btn = document.getElementById('send-sms-btn');
            btn.disabled = true;
            btn.textContent = 'Sending...';

            const formData = new FormData(this);
            const response = await fetch(`${BASE_URL}mpesa/send_sms`, {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if(result.success) {
                Swal.fire('Success', result.message, 'success');
                this.reset();
                updateDashboard();
            } else {
                Swal.fire('Error', result.message, 'error');
            }
            btn.disabled = false;
            btn.textContent = 'Send SMS';
        });

        function startPolling() {
            if (pollingInterval) clearInterval(pollingInterval);
            
            // Define the polling logic as a function to call it immediately and then in intervals
            const poll = async () => {
                if (!checkoutRequestId) {
                    stopPolling();
                    return;
                }
                
                try {
                    const formData = new FormData();
                    formData.append('checkout_request_id', checkoutRequestId);

                    const response = await fetch(`${BASE_URL}mpesa/check_payment_status`, {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();
                    
                    if (result.status === 'completed') {
                        stopPolling();
                        Swal.fire('Payment Successful!', 'Your SMS credits have been updated.', 'success');
                        toggleLoading(false);
                        updateDashboard();
                    } else if (result.status === 'failed') {
                        stopPolling();
                        Swal.fire('Payment Failed', result.message || 'The transaction failed or was cancelled by the user.', 'error');
                        toggleLoading(false);
                    }
                } catch (error) {
                    console.error("Polling error:", error);
                }
            };

            // Start the interval
            pollingInterval = setInterval(poll, 5000); // Poll every 5 seconds is safer
        }

        function stopPolling() {
            clearInterval(pollingInterval);
            pollingInterval = null;
            checkoutRequestId = null;
        }

        async function updateDashboard() {
            // ... (rest of the function is unchanged)
            const response = await fetch(`${BASE_URL}mpesa/get_user_data`);
            const data = await response.json();

            if (data.success) {
                const currentCredit = parseInt(smsCreditDisplay.innerText) || 0;
                const newCredit = data.sms_credit;
                animateValue(smsCreditDisplay, currentCredit, newCredit, 500);
                
                let historyHtml = '';
                if (data.billing_history && data.billing_history.length > 0) {
                    data.billing_history.forEach(tx => {
                        historyHtml += `
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${new Date(tx.transaction_date).toLocaleString()}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${tx.receipt_number || 'N/A'}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${parseFloat(tx.amount).toFixed(2)}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${tx.status === 'completed' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                                        ${tx.status}
                                    </span>
                                </td>
                            </tr>`;
                    });
                } else {
                    historyHtml = '<tr><td colspan="4" class="text-center py-4 text-gray-500">No transactions yet.</td></tr>';
                }
                billingTableBody.innerHTML = historyHtml;
            }
        }
        
        function animateValue(obj, start, end, duration) {
            // ... (rest of the function is unchanged)
            let startTimestamp = null;
            const step = (timestamp) => {
                if (!startTimestamp) startTimestamp = timestamp;
                const progress = Math.min((timestamp - startTimestamp) / duration, 1);
                obj.innerHTML = `${Math.floor(progress * (end - start) + start)} <span class="text-2xl font-normal text-gray-500">credits</span>`;
                if (progress < 1) {
                    window.requestAnimationFrame(step);
                }
            };
            window.requestAnimationFrame(step);
        }

        function toggleLoading(isLoading, text = 'Purchase Now') {
            // ... (rest of the function is unchanged)
            purchaseBtn.disabled = isLoading;
            if (isLoading) {
                btnText.textContent = text;
                loader.classList.remove('hidden');
            } else {
                btnText.textContent = 'Purchase Now';
                loader.classList.add('hidden');
            }
        }
    });
</script>
</body>
</html>

