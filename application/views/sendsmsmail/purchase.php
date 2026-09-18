
<!-- Purchase SMS Credits View -->
<section class="panel">
    <div class="panel-heading">
        <h4 class="panel-title"><i class="fas fa-dollar-sign"></i> <?= translate('Purchase SMS Credits') ?></h4>
    </div>
    <div class="panel-body">
        <?php if (is_superadmin_loggedin()): ?>
            <div class="row mb-lg">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="control-label"><?= translate('select_branch') ?></label>
                        <?php 
                        $arrayBranch = $this->app_lib->getSelectList('branch');
                        echo form_dropdown('branch_id', $arrayBranch, $branch_id, 'class="form-control" data-plugin-selectTwo id="branch_id_selector"');
                        ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-6">
                <div class="well">
                    <h4><i class="fas fa-sms"></i> Your Current Balance <span class="pull-right" id="branch-name"></span></h4>
                    <h1 id="sms-credit-balance">Loading...</h1>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h4 class="panel-title">Top Up Your Balance</h4>
                    </div>
                    <div class="panel-body">
                        <!-- Alert box for messages -->
                        <div id="purchase-alert" style="display:none;"></div>
                        
                        <form id="purchase-form" class="form-horizontal">
                            <!-- CSRF Token for security -->
                            <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>" id="csrf_token_input">
                            
                            <!-- Branch ID for the form -->
                            <input type="hidden" name="branch_id" id="form_branch_id" value="<?= $branch_id ?>">

                            <div class="form-group">
                                <label class="col-md-4 control-label" for="amount">Amount (KES)</label>
                                <div class="col-md-8">
                                    <input type="number" id="amount" name="amount" class="form-control" placeholder="e.g., 100" required min="1" max="70000">
                                    <small class="text-muted">Minimum: KES 1, Maximum: KES 70,000</small>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-md-4 control-label" for="phone">Payment Phone</label>
                                <div class="col-md-8">
                                    <input type="text" id="phone" name="phone" class="form-control" placeholder="Start with 2547... (e.g., 254712345678)" required pattern="254[0-9]{9}">
                                    <small class="text-muted">Format: 2547XXXXXXXX (12 digits)</small>
                                </div>
                            </div>

                            <div class="form-group">
                                <div class="col-md-offset-4 col-md-8">
                                    <button type="submit" class="btn btn-primary" id="purchase-btn">
                                        <i class="fas fa-shopping-cart"></i> Purchase SMS Credits
                                    </button>
                                </div>
                            </div>

                            <div class="form-group">
                                <div class="col-md-offset-4 col-md-8">
                                    <div class="alert alert-info">
                                        <small>
                                            <i class="fas fa-info-circle"></i> 
                                            <strong>Rate:</strong> 1 SMS = KES 1<br>
                                            <strong>Process:</strong> Enter amount → Confirm payment on your phone → SMS credits added automatically
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h4 class="panel-title">Billing History</h4>
                    </div>
                    <div class="panel-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Amount (KES)</th>
                                        <th>SMS Units</th>
                                        <th>Receipt No.</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody id="billing-history-table">
                                    <tr><td colspan="5">Loading history...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SMS Rates Information Panel -->
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-info">
                    <div class="panel-heading">
                        <h4 class="panel-title"><i class="fas fa-info-circle"></i> SMS Pricing Information</h4>
                    </div>
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-3 text-center">
                                <div class="well well-sm">
                                    <h4 class="text-success">KES 10</h4>
                                    <p>10 SMS Units</p>
                                </div>
                            </div>
                            <div class="col-md-3 text-center">
                                <div class="well well-sm">
                                    <h4 class="text-success">KES 50</h4>
                                    <p>50 SMS Units</p>
                                </div>
                            </div>
                            <div class="col-md-3 text-center">
                                <div class="well well-sm">
                                    <h4 class="text-success">KES 100</h4>
                                    <p>100 SMS Units</p>
                                </div>
                            </div>
                            <div class="col-md-3 text-center">
                                <div class="well well-sm">
                                    <h4 class="text-success">KES 500</h4>
                                    <p>500 SMS Units</p>
                                </div>
                            </div>
                        </div>
                        <p class="text-center text-muted"><small>Custom amounts are also accepted. 1 Kenyan Shilling = 1 SMS Unit</small></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script type="text/javascript">
$(document).ready(function() {
    let pollingInterval;
    const isSuperadmin = <?= is_superadmin_loggedin() ? 'true' : 'false' ?>;
    
    // --- CSRF Token Handling ---
    let csrfTokenName = '<?= $this->security->get_csrf_token_name(); ?>';
    let csrfTokenHash = '<?= $this->security->get_csrf_hash(); ?>';

    // Function to get the currently selected or logged-in branch ID
    function getCurrentBranchId() {
        if (isSuperadmin) {
            return $('#branch_id_selector').val();
        }
        return $('#form_branch_id').val();
    }

    // Function to update CSRF token after an AJAX call
    function updateCsrfToken(newHash) {
        if (newHash) {
            csrfTokenHash = newHash;
            $('#csrf_token_input').val(newHash);
            console.log('CSRF token updated:', newHash);
        }
    }

    // Function to get the latest CSRF token hash
    function getCsrfToken() {
        return $('#csrf_token_input').val();
    }

    // Function to get CSRF data for AJAX requests
    function getCsrfData(additionalData = {}) {
        return {
            ...additionalData,
            [csrfTokenName]: getCsrfToken()
        };
    }

    // Function to validate phone number format
    function validatePhoneNumber(phone) {
        const phoneRegex = /^254[0-9]{9}$/;
        return phoneRegex.test(phone);
    }

    // Function to format phone number as user types
    function formatPhoneNumber(input) {
        let value = input.value.replace(/\D/g, '');
        
        if (value.startsWith('0') && value.length === 10) {
            value = '254' + value.substring(1);
        } else if (value.length === 9 && value.startsWith('7')) {
            value = '254' + value;
        }
        
        input.value = value;
        return value;
    }

    // Function to update the dashboard (balance and history)
    function updateDashboard() {
        const branchId = getCurrentBranchId();
        
        if (!branchId) {
            showAlert('Please select a branch to view SMS credits.', 'info');
            $('#sms-credit-balance').text('N/A');
            $('#billing-history-table').html('<tr><td colspan="5" class="text-center">Select a branch.</td></tr>');
            return;
        }

        $('#form_branch_id').val(branchId);

        if (isSuperadmin) {
            const branchName = $('#branch_id_selector option:selected').text();
            $('#branch-name').text(`[${branchName}]`);
        } else {
            $('#branch-name').text('');
        }

        $.ajax({
            url: "<?= base_url('sendsmsmail/get_user_data') ?>",
            type: 'POST',
            data: getCsrfData({
                'branch_id': branchId
            }),
            dataType: 'json',
            success: function(res, status, xhr) {
                // Update CSRF token if it was regenerated
                const newCsrfToken = xhr.getResponseHeader('X-CSRF-TOKEN');
                if (newCsrfToken) {
                    updateCsrfToken(newCsrfToken);
                }
                
                if (res.success) {
                    // Format SMS credit with commas
                    const formattedCredit = Number(res.sms_credit).toLocaleString();
                    $('#sms-credit-balance').text(formattedCredit + ' SMS');
                    
                    let historyHtml = '';
                    if (res.billing_history && res.billing_history.length > 0) {
                        res.billing_history.forEach(function(item) {
                            let receipt = item.receipt_number ? item.receipt_number : 'Pending';
                            let statusClass = '';
                            let statusText = item.status;
                            
                            switch(item.status) {
                                case 'completed':
                                    statusClass = 'success';
                                    statusText = 'Completed';
                                    break;
                                case 'pending':
                                    statusClass = 'warning';
                                    statusText = 'Pending';
                                    break;
                                case 'failed':
                                    statusClass = 'danger';
                                    statusText = 'Failed';
                                    break;
                                case 'cancelled':
                                    statusClass = 'default';
                                    statusText = 'Cancelled';
                                    break;
                                default:
                                    statusClass = 'info';
                            }
                            
                            historyHtml += `<tr>
                                <td>${new Date(item.created_at).toLocaleString()}</td>
                                <td>KES ${Number(item.amount).toLocaleString()}</td>
                                <td>${Number(item.sms_units).toLocaleString()}</td>
                                <td>${receipt}</td>
                                <td><span class="label label-${statusClass}">${statusText}</span></td>
                            </tr>`;
                        });
                    } else {
                        historyHtml = '<tr><td colspan="5" class="text-center">No billing history found.</td></tr>';
                    }
                    $('#billing-history-table').html(historyHtml);
                } else {
                    showAlert(res.message, 'danger');
                    $('#sms-credit-balance').text('Error');
                    $('#billing-history-table').html('<tr><td colspan="5" class="text-center">Error loading history.</td></tr>');
                }
            },
            error: function(xhr, status, error) {
                const newCsrfToken = xhr.getResponseHeader('X-CSRF-TOKEN');
                if (newCsrfToken) {
                    updateCsrfToken(newCsrfToken);
                }
                showAlert(`Could not connect to the server: ${error}`, 'danger');
                $('#sms-credit-balance').text('Error');
                $('#billing-history-table').html('<tr><td colspan="5" class="text-center">Error loading history.</td></tr>');
            }
        });
    }

    // Function to show alert messages
    function showAlert(message, type) {
        const alertDiv = $('#purchase-alert');
        alertDiv.hide().html('').append(`<div class="alert alert-${type} alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">×</button>
            ${message}
        </div>`).slideDown();
        
        // Auto-hide success messages after 5 seconds
        if (type === 'success') {
            setTimeout(function() {
                alertDiv.slideUp();
            }, 5000);
        }
    }

    // Function to calculate SMS units from amount
    function calculateSmsUnits(amount) {
        return Math.floor(amount / 1); // 1 KES = 1 SMS unit
    }

    // Function to update SMS units display
    function updateSmsUnitsDisplay() {
        const amount = $('#amount').val();
        if (amount && amount >= 1) {
            const smsUnits = calculateSmsUnits(amount);
            $('#sms-units-display').text(`You will get ${smsUnits.toLocaleString()} SMS units`);
        } else {
            $('#sms-units-display').text('');
        }
    }

    // --- Event Listeners ---

    // 1. Superadmin branch change listener
    <?php if (is_superadmin_loggedin()): ?>
    $('#branch_id_selector').on('change', function() {
        updateDashboard();
        $('#purchase-alert').slideUp().html('');
    });
    <?php endif; ?>

    // 2. Phone number formatting as user types
    $('#phone').on('input', function() {
        formatPhoneNumber(this);
    });

    // 3. Amount input change - update SMS units display
    $('#amount').on('input', function() {
        updateSmsUnitsDisplay();
        
        // Validate amount range
        const amount = $(this).val();
        if (amount > 70000) {
            $(this).val(70000);
            showAlert('Maximum amount is KES 70,000', 'warning');
        }
    });

    // 4. Handle the purchase form submission
    $('#purchase-form').on('submit', function(e) {
        e.preventDefault();
        
        const amount = $('#amount').val();
        const phone = $('#phone').val();
        const branchId = getCurrentBranchId();
        
        // Validate inputs
        if (!amount || amount < 1) {
            showAlert('Please enter a valid amount (minimum KES 1)', 'danger');
            return;
        }
        
        if (!phone || !validatePhoneNumber(phone)) {
            showAlert('Please enter a valid phone number in format 2547XXXXXXXX', 'danger');
            return;
        }
        
        if (!branchId) {
            showAlert('Please select a branch', 'danger');
            return;
        }
        
        const purchaseBtn = $('#purchase-btn');
        const originalBtnText = purchaseBtn.html();
        
        purchaseBtn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Processing...');
        showAlert('Initializing payment, please wait...', 'info');

        // Use form data with proper CSRF handling
        const formData = $(this).serialize();
        console.log('Submitting form data:', formData);

        $.ajax({
            url: "<?= base_url('sendsmsmail/initiate_payment') ?>",
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(res, status, xhr) {
                const newCsrfToken = xhr.getResponseHeader('X-CSRF-TOKEN');
                if (newCsrfToken) {
                    updateCsrfToken(newCsrfToken);
                }
                
                if (res.success) {
                    showAlert(res.message, 'info');
                    pollPaymentStatus(res.checkout_request_id);
                } else {
                    showAlert(res.message, 'danger');
                    purchaseBtn.prop('disabled', false).html(originalBtnText);
                }
            },
            error: function(xhr, status, error) {
                const newCsrfToken = xhr.getResponseHeader('X-CSRF-TOKEN');
                if (newCsrfToken) {
                    updateCsrfToken(newCsrfToken);
                }
                
                let errorMessage = 'An unexpected network error occurred. Please try again.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                
                showAlert(errorMessage, 'danger');
                purchaseBtn.prop('disabled', false).html(originalBtnText);
            }
        });
    });

   // 5. Polling function with very slow intervals to avoid rate limiting
function pollPaymentStatus(checkoutRequestId) {
    if (pollingInterval) clearInterval(pollingInterval);
    
    let pollCount = 0;
    const maxPolls = 20; // Reduced to 20 polls total
    let pollInterval = 15000; // Start with 15 seconds (increased from 10)
    
    pollingInterval = setInterval(function() {
        pollCount++;
        
        if (pollCount > maxPolls) {
            clearInterval(pollingInterval);
            showAlert('Payment check completed. Please check your transaction history for final status.', 'info');
            $('#purchase-btn').prop('disabled', false).html('<i class="fas fa-shopping-cart"></i> Purchase SMS Credits');
            updateDashboard(); // Refresh to show final status
            return;
        }
        
        $.ajax({
            url: "<?= base_url('sendsmsmail/check_payment_status') ?>",
            type: 'POST',
            data: getCsrfData({
                'checkout_request_id': checkoutRequestId,
                'poll_count': pollCount
            }),
            dataType: 'json',
            timeout: 30000, // 30 second timeout
            success: function(res, status, xhr) {
                const newCsrfToken = xhr.getResponseHeader('X-CSRF-TOKEN');
                if (newCsrfToken) {
                    updateCsrfToken(newCsrfToken);
                }

                // In the polling success function, add:
            if (res.success) {
                if (res.status === 'completed') {
                    // Check if we have a real receipt
                    if (res.has_real_receipt) {
                        showAlert('✅ Payment completed successfully! Real M-Pesa receipt received.', 'success');
                    } else {
                        showAlert('✅ Payment completed! Using temporary reference.', 'success');
                    }
                    
                    clearInterval(pollingInterval);
                    $('#purchase-btn').prop('disabled', false).html('<i class="fas fa-shopping-cart"></i> Purchase SMS Credits');
                    updateDashboard();
                    $('#purchase-form')[0].reset();
                    $('#sms-units-display').text('');
                    
                }
            }                
                if (res.success) {
                    if (res.status === 'completed') {
                        clearInterval(pollingInterval);
                        showAlert('✅ ' + res.message, 'success');
                        $('#purchase-btn').prop('disabled', false).html('<i class="fas fa-shopping-cart"></i> Purchase SMS Credits');
                        updateDashboard();
                        $('#purchase-form')[0].reset();
                        $('#sms-units-display').text('');
                        
                    } else if (res.status === 'failed' || res.status === 'cancelled' || res.status === 'timeout') {
                        clearInterval(pollingInterval);
                        showAlert('❌ ' + res.message, 'danger');
                        $('#purchase-btn').prop('disabled', false).html('<i class="fas fa-shopping-cart"></i> Purchase SMS Credits');
                        updateDashboard();
                        
                    } else if (res.status === 'pending') {
                        // Very slow polling progression
                        if (pollCount === 3 && pollInterval < 30000) {
                            pollInterval = 30000; // 30 seconds after 3 polls
                            clearInterval(pollingInterval);
                            pollingInterval = setInterval(arguments.callee, pollInterval);
                        } else if (pollCount === 6 && pollInterval < 60000) {
                            pollInterval = 60000; // 60 seconds after 6 polls
                            clearInterval(pollingInterval);
                            pollingInterval = setInterval(arguments.callee, pollInterval);
                        } else if (pollCount === 10 && pollInterval < 120000) {
                            pollInterval = 120000; // 120 seconds after 10 polls
                            clearInterval(pollingInterval);
                            pollingInterval = setInterval(arguments.callee, pollInterval);
                        }
                        
                        let message = res.message;
                        if (pollCount <= 2) {
                            message += ' - Please check your phone and enter PIN now';
                        } else if (pollCount <= 5) {
                            message += ' - Checking status (this may take a moment)';
                        } else {
                            message += ' - Still processing, please be patient';
                        }
                        
                        showAlert('⏳ ' + message + ` (${pollCount}/${maxPolls})`, 'info');
                    }
                } else {
                    // For rate limiting errors, slow down dramatically
                    if (res.message.includes('rate') || res.message.includes('busy') || res.message.includes('Temporarily')) {
                        pollInterval = Math.min(pollInterval * 2, 300000); // Max 5 minutes
                        clearInterval(pollingInterval);
                        pollingInterval = setInterval(arguments.callee, pollInterval);
                        showAlert('⚠️ System is busy. Checking less frequently...', 'warning');
                    } else {
                        showAlert('❌ ' + res.message, 'danger');
                        clearInterval(pollingInterval);
                        $('#purchase-btn').prop('disabled', false).html('<i class="fas fa-shopping-cart"></i> Purchase SMS Credits');
                        updateDashboard();
                    }
                }
            },
            error: function(xhr, status, error) {
                const newCsrfToken = xhr.getResponseHeader('X-CSRF-TOKEN');
                if (newCsrfToken) {
                    updateCsrfToken(newCsrfToken);
                }
                
                // On network errors, slow down dramatically
                pollInterval = Math.min(pollInterval * 1.5, 300000); // Max 5 minutes
                clearInterval(pollingInterval);
                pollingInterval = setInterval(arguments.callee, pollInterval);
                
                if (pollCount < 5) {
                    showAlert('🌐 Network issue - checking less frequently...', 'warning');
                } else {
                    clearInterval(pollingInterval);
                    showAlert('❌ Could not verify payment status. Please check your transaction history.', 'warning');
                    $('#purchase-btn').prop('disabled', false).html('<i class="fas fa-shopping-cart"></i> Purchase SMS Credits');
                    updateDashboard();
                }
            }
        });
    }, pollInterval);
}

    // 6. Clean up interval on page exit
    $(window).on('beforeunload', function() {
        if (pollingInterval) {
            clearInterval(pollingInterval);
        }
    });

    // 7. Add SMS units display element dynamically
    $('<div class="form-group" id="sms-units-display-group" style="display: none;">' +
        '<label class="col-md-4 control-label">SMS Units</label>' +
        '<div class="col-md-8">' +
            '<p class="form-control-static text-success" id="sms-units-display"></p>' +
        '</div>' +
    '</div>').insertAfter('#purchase-form .form-group:first');

    // Show SMS units display when amount is entered
    $('#amount').on('focus', function() {
        $('#sms-units-display-group').show();
    });

    // Initial load of dashboard data
    updateDashboard();
    
    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();
});
</script>
<!-- Add this to your purchase.php view -->
<div class="alert alert-warning" id="rate-limit-warning" style="display: none;">
    <h4><i class="fas fa-clock"></i> Payment Status Checking</h4>
    <p>To avoid system limits, status checks are performed gradually. This may take a few minutes.</p>
    <ul>
        <li>First 2 minutes: Check every 15 seconds</li>
        <li>Next 3 minutes: Check every 30 seconds</li>
        <li>After 5 minutes: Check every 1-2 minutes</li>
    </ul>
    <p><strong>Your payment is safe</strong> - the system will complete even if checking is slow.</p>
</div>

<script>
// Show the rate limit warning when polling starts
function pollPaymentStatus(checkoutRequestId) {
    $('#rate-limit-warning').slideDown();
    // ... rest of your existing polling function
}
</script>

<style>
.well {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 10px;
}
.well h1 {
    font-size: 2.5em;
    font-weight: bold;
    margin: 0;
    text-shadow: 1px 1px 3px rgba(0,0,0,0.3);
}
.well h4 {
    margin-bottom: 10px;
    opacity: 0.9;
}
.panel-default .panel-heading {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white;
    border: none;
}
.panel-info .panel-heading {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    color: white;
    border: none;
}
.label-success { background-color: #28a745; }
.label-warning { background-color: #ffc107; color: #212529; }
.label-danger { background-color: #dc3545; }
.label-default { background-color: #6c757d; }
.alert { border: none; border-radius: 8px; }
.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    border-radius: 25px;
    padding: 10px 25px;
    font-weight: bold;
}
.btn-primary:hover {
    background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}
.form-control {
    border-radius: 8px;
    border: 2px solid #e9ecef;
    transition: all 0.3s;
}
.form-control:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}
.well.well-sm {
    background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
    color: #495057;
    border: none;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 0;
}
.well.well-sm h4 {
    margin: 0;
    font-weight: bold;
}
</style>
