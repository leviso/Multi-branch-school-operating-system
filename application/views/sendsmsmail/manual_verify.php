<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<!-- Ensure toastr is loaded -->
<?php if (!isset($this->data['toastr_loaded'])): ?>
<?php endif; ?>

<div class="row">
    <div class="col-md-12">
        <div class="panel <?php echo (is_superadmin_loggedin()) ? 'panel-custom' : 'panel-default'; ?>">
            <div class="panel-heading">
                <div class="panel-title">
                    <i class="fa fa-search"></i> Manual Transaction Verification
                </div>
            </div>
            
            <div class="panel-body">
                <?php if (is_superadmin_loggedin()): ?>
                <div class="row mb-lg">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="control-label">Filter by Branch</label>
                            <select class="form-control" id="branch_filter">
                                <option value="">All Branches</option>
                                <?php foreach ($branches as $branch): ?>
                                <option value="<?php echo $branch['id']; ?>">
                                    <?php echo $branch['name']; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Search Form -->
                <div class="row">
                    <div class="col-md-8 col-md-offset-2">
                        <div class="panel panel-info">
                            <div class="panel-heading">
                                <h4 class="panel-title">Enter Receipt Number or Checkout ID</h4>
                            </div>
                            <div class="panel-body">
                                <form id="verifyForm" method="post">
                                    <div class="form-group">
                                        <div class="input-group input-group-lg">
                                            <span class="input-group-addon">
                                                <i class="fa fa-receipt"></i>
                                            </span>
                                            <input type="text" 
                                                   class="form-control" 
                                                   id="identifier" 
                                                   name="identifier"
                                                   placeholder="e.g. QHJ7S8XXX or checkout_request_id"
                                                   autocomplete="off"
                                                   required>
                                            <span class="input-group-btn">
                                                <button class="btn btn-primary" type="submit" id="searchBtn">
                                                    <i class="fa fa-search"></i> Search
                                                </button>
                                            </span>
                                        </div>
                                        <span class="help-block">
                                            Enter M-Pesa receipt number or checkout request ID
                                        </span>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Transaction Details (Hidden by default) -->
                <div id="transactionDetails" style="display: none;">
                    <div class="row">
                        <div class="col-md-8 col-md-offset-2">
                            <div class="panel panel-primary">
                                <div class="panel-heading">
                                    <h3 class="panel-title">
                                        <i class="fa fa-file-text-o"></i> Transaction Details
                                    </h3>
                                </div>
                                <div class="panel-body">
                                    <table class="table table-bordered">
                                        <tr>
                                            <th style="width: 30%;">Transaction ID</th>
                                            <td id="tx_id"></td>
                                        </tr>
                                        <tr>
                                            <th>Branch</th>
                                            <td id="tx_branch"></td>
                                        </tr>
                                        <tr>
                                            <th>Phone Number</th>
                                            <td id="tx_phone"></td>
                                        </tr>
                                        <tr>
                                            <th>Amount</th>
                                            <td id="tx_amount"></td>
                                        </tr>
                                        <tr>
                                            <th>SMS Units</th>
                                            <td id="tx_sms_units"></td>
                                        </tr>
                                        <tr>
                                            <th>Receipt Number</th>
                                            <td id="tx_receipt"></td>
                                        </tr>
                                        <tr>
                                            <th>Checkout ID</th>
                                            <td id="tx_checkout"></td>
                                        </tr>
                                        <tr>
                                            <th>Status</th>
                                            <td id="tx_status"></td>
                                        </tr>
                                        <tr>
                                            <th>Credited</th>
                                            <td id="tx_credited"></td>
                                        </tr>
                                        <tr>
                                            <th>Date</th>
                                            <td id="tx_date"></td>
                                        </tr>
                                    </table>
                                    
                                    <div class="alert alert-warning" id="verifyWarning" style="display: none;">
                                        <i class="fa fa-exclamation-triangle"></i>
                                        <span id="warningMessage"></span>
                                    </div>
                                    
                                    <div class="text-center">
                                        <button class="btn btn-success btn-lg" id="verifyBtn" disabled>
                                            <i class="fa fa-check-circle"></i> Verify & Credit SMS
                                        </button>
                                        <button class="btn btn-default btn-lg" id="resetBtn">
                                            <i class="fa fa-undo"></i> New Search
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Pending Transactions -->
                <div class="row mt-4">
                    <div class="col-md-12">
                        <div class="panel panel-warning">
                            <div class="panel-heading">
                                <h4 class="panel-title">
                                    <i class="fa fa-clock-o"></i> Recent Pending Transactions
                                </h4>
                            </div>
                            <div class="panel-body">
                                <div id="pendingTransactionsList">
                                    <div class="text-center">
                                        <i class="fa fa-spinner fa-spin"></i> Loading...
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
(function() {
    // Store CSRF token name and hash
    var csrf_token_name = '<?php echo $this->security->get_csrf_token_name(); ?>';
    var csrf_token_hash = '<?php echo $this->security->get_csrf_hash(); ?>';
    
    console.log('CSRF Token Name:', csrf_token_name);
    console.log('CSRF Token Hash:', csrf_token_hash);
    
    $(document).ready(function() {
        var currentTransaction = null;
        var branchFilter = '';
        
        // Load pending transactions
        loadPendingTransactions();
        
        // Branch filter change
        $('#branch_filter').change(function() {
            branchFilter = $(this).val();
            loadPendingTransactions();
        });
        
        // Search form submission
        $('#verifyForm').submit(function(e) {
            e.preventDefault();
            searchTransaction();
        });
        
        // Reset button
        $('#resetBtn').click(function() {
            $('#identifier').val('').focus();
            $('#transactionDetails').hide();
            currentTransaction = null;
        });
        
        // Verify button
       $('#verifyBtn').click(function() {
            if (!currentTransaction) return;
            verifyTransaction();
        });
        
        function searchTransaction() {
            var identifier = $('#identifier').val().trim();
            if (!identifier) {
                alert('Please enter receipt number or checkout ID');
                return;
            }
            
            $('#searchBtn').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Searching...');
            
            // Use regular POST instead of FormData for better CSRF compatibility
            var postData = {
                identifier: identifier,
                action: 'search',
                branch_id: branchFilter
            };
            postData[csrf_token_name] = csrf_token_hash;
            
            console.log('Search POST Data:', postData);
            
            $.ajax({
                url: base_url + 'sendsmsmail/verify_transaction',
                type: 'POST',
                data: postData,
                dataType: 'json',
                success: function(response) {
                    console.log('Search Response:', response);
                    if (response.success) {
                        displayTransaction(response.transaction);
                        currentTransaction = response.transaction;
                        alert('Transaction found successfully');
                    } else {
                        alert(response.message);
                        $('#transactionDetails').hide();
                        currentTransaction = null;
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', error);
                    console.error('Response Text:', xhr.responseText);
                    alert('Failed to search transaction: ' + error);
                },
                complete: function() {
                    $('#searchBtn').prop('disabled', false).html('<i class="fa fa-search"></i> Search');
                    // Refresh CSRF token after each request
                    refreshCsrfToken();
                }
            });
        }
        
        function verifyTransaction() {
        if (!confirm('Are you sure you want to manually verify and credit this transaction?')) {
            return;
        }
        
        $('#verifyBtn').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Verifying...');
        
        var postData = {
            identifier: $('#identifier').val(),
            action: 'verify',
            branch_id: branchFilter
        };
        postData[csrf_token_name] = csrf_token_hash;
        
        $.ajax({
            url: base_url + 'sendsmsmail/verify_transaction',
            type: 'POST',
            data: postData,
            dataType: 'json',
            timeout: 30000, // 30 second timeout
            success: function(response) {
                console.log('Verify Response:', response);
                if (response.success) {
                    alert(response.message);
                    
                    // Reload transaction details
                    setTimeout(function() {
                        searchTransaction();
                        loadPendingTransactions();
                    }, 1000);
                } else {
                    alert(response.message || 'Unknown error occurred');
                    $('#verifyBtn').prop('disabled', false).html('<i class="fa fa-check-circle"></i> Verify & Credit SMS');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                console.error('Status:', status);
                console.error('Response Text:', xhr.responseText);
                
                // Try to parse error response if it's JSON
                try {
                    var errorResponse = JSON.parse(xhr.responseText);
                    alert(errorResponse.message || 'Failed to verify transaction');
                } catch(e) {
                    alert('Failed to verify transaction: Server returned HTML/Error page. Check logs.');
                }
                
                $('#verifyBtn').prop('disabled', false).html('<i class="fa fa-check-circle"></i> Verify & Credit SMS');
            },
            complete: function() {
                refreshCsrfToken();
            }
        });
    }
        
        function loadPendingTransactions() {
            var postData = {
                branch_id: branchFilter,
                limit: 20
            };
            postData[csrf_token_name] = csrf_token_hash;
            
            $.ajax({
                url: base_url + 'sendsmsmail/get_pending_transactions',
                type: 'POST',
                data: postData,
                dataType: 'json',
                success: function(response) {
                    console.log('Pending Transactions Response:', response);
                    if (response.success && response.transactions && response.transactions.length > 0) {
                        var html = '<table class="table table-striped table-hover">';
                        html += '<thead><tr>';
                        html += '<th>ID</th>';
                        html += '<th>Branch</th>';
                        html += '<th>Phone</th>';
                        html += '<th>Amount</th>';
                        html += '<th>Receipt</th>';
                        html += '<th>Status</th>';
                        html += '<th>Date</th>';
                        html += '<th>Action</th>';
                        html += '</tr></thead><tbody>';
                        
                        $.each(response.transactions, function(i, tx) {
                            html += '<tr>';
                            html += '<td>' + (tx.id || 'N/A') + '</td>';
                            html += '<td>' + (tx.branch_name || 'N/A') + '</td>';
                            html += '<td>' + (tx.user_phone || 'N/A') + '</td>';
                            html += '<td>KES ' + (tx.amount ? parseFloat(tx.amount).toFixed(2) : '0.00') + '</td>';
                            html += '<td>' + (tx.receipt_number || '<span class="text-danger">Missing</span>') + '</td>';
                            html += '<td>' + getStatusBadge(tx.status) + '</td>';
                            html += '<td>' + (tx.created_at || 'N/A') + '</td>';
                            html += '<td>';
                            html += '<button class="btn btn-xs btn-info" onclick="quickVerify(' + (tx.id || 0) + ', \'' + (tx.receipt_number || tx.checkout_request_id || '') + '\')">';
                            html += '<i class="fa fa-check"></i> Verify';
                            html += '</button>';
                            html += '</td>';
                            html += '</tr>';
                        });
                        
                        html += '</tbody></table>';
                        $('#pendingTransactionsList').html(html);
                    } else {
                        $('#pendingTransactionsList').html('<div class="alert alert-info">No pending transactions found</div>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error loading pending transactions:', error);
                    $('#pendingTransactionsList').html('<div class="alert alert-danger">Failed to load pending transactions</div>');
                },
                complete: function() {
                    refreshCsrfToken();
                }
            });
        }
        
        function refreshCsrfToken() {
            // Get new CSRF token
            $.ajax({
                url: base_url + 'sendsmsmail/get_csrf_hash',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.csrf_hash) {
                        csrf_token_hash = response.csrf_hash;
                        console.log('CSRF Token Refreshed:', csrf_token_hash);
                    }
                }
            });
        }
        
        function getStatusBadge(status) {
            if (!status) return '<span class="label label-default">Unknown</span>';
            
            var statusLower = status.toLowerCase();
            switch(statusLower) {
                case 'completed':
                    return '<span class="label label-success">Completed</span>';
                case 'pending':
                    return '<span class="label label-warning">Pending</span>';
                case 'failed':
                    return '<span class="label label-danger">Failed</span>';
                default:
                    return '<span class="label label-default">' + status + '</span>';
            }
        }
        
        function displayTransaction(tx) {
            $('#tx_id').text(tx.id || 'N/A');
            $('#tx_branch').text(tx.branch_name || 'N/A');
            $('#tx_phone').text(tx.user_phone || 'N/A');
            $('#tx_amount').text(tx.amount ? 'KES ' + parseFloat(tx.amount).toFixed(2) : 'N/A');
            $('#tx_sms_units').text(tx.sms_units || '0');
            $('#tx_receipt').html(tx.receipt_number || '<span class="text-danger">Missing</span>');
            $('#tx_checkout').text(tx.checkout_request_id || 'N/A');
            $('#tx_status').html(getStatusBadge(tx.status));
            $('#tx_credited').html(tx.is_credited == 1 ? 
                '<span class="label label-success">Yes</span>' : 
                '<span class="label label-danger">No</span>');
            $('#tx_date').text(tx.created_at || 'N/A');
            
            // Check if already completed
            if (tx.status == 'completed' && tx.is_credited == 1) {
                $('#verifyBtn').prop('disabled', true);
                $('#verifyWarning').show();
                $('#warningMessage').text('This transaction is already completed and credited.');
            } else {
                $('#verifyBtn').prop('disabled', false);
                $('#verifyWarning').hide();
            }
            
            $('#transactionDetails').show();
        }
    });
})();

// Quick verify function (called from table)
function quickVerify(id, identifier) {
    if (identifier) {
        $('#identifier').val(identifier);
        $('#searchBtn').click();
    } else {
        alert('No identifier available for this transaction');
    }
}
</script>