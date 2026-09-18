<div class="page-content">
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow-lg mt-5">
                    <div class="card-body text-center p-5">
                        <div class="spinner-border text-success mb-4" style="width: 60px; height: 60px;" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        
                        <h3 class="mb-3">Processing Payment</h3>
                        
                        <p class="text-muted mb-4">
                            <i class="fas fa-mobile-alt me-2"></i>
                            Check your phone for the M-Pesa prompt and enter your PIN.
                        </p>
                        
                        <div class="alert alert-info mt-3">
                            <strong>Amount:</strong> KES <?php echo number_format($amount, 2); ?><br>
                            <strong>Phone:</strong> <?php echo $phone; ?>
                        </div>
                        
                        <div class="progress mt-4" style="height: 5px;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" style="width: 100%"></div>
                        </div>
                        
                        <p class="text-muted small mt-4" id="statusMessage">
                            <i class="fas fa-spinner fa-spin me-1"></i>
                            Waiting for payment confirmation...
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
var base_url = '<?php echo base_url(); ?>';
var checkoutId = '<?php echo $checkout_request_id; ?>';
var transactionId = <?php echo $transaction_id; ?>;
var attempts = 0;
var maxAttempts = 20;

function checkStatus() {
    attempts++;
    $('#statusMessage').html('<i class="fas fa-spinner fa-spin me-1"></i> Checking status... (' + attempts + '/' + maxAttempts + ')');
    
    $.ajax({
        url: base_url + 'feespayment/check_mpesa_status',
        type: 'POST',
        data: {
            checkout_request_id: checkoutId,
            transaction_id: transactionId
        },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'completed') {
                $('#statusMessage').html('<i class="fas fa-check-circle text-success me-1"></i> Payment successful! Redirecting...');
                setTimeout(function() { window.location.href = base_url + 'userrole/invoice'; }, 2000);
            } 
            else if (response.status === 'failed') {
                $('#statusMessage').html('<i class="fas fa-times-circle text-danger me-1"></i> Payment failed: ' + response.message);
                setTimeout(function() { window.location.href = base_url + 'userrole/invoice'; }, 3000);
            }
            else if (response.status === 'cancelled') {
                $('#statusMessage').html('<i class="fas fa-ban text-warning me-1"></i> Payment cancelled');
                setTimeout(function() { window.location.href = base_url + 'userrole/invoice'; }, 3000);
            }
            else if (attempts >= maxAttempts) {
                $('#statusMessage').html('<i class="fas fa-clock text-warning me-1"></i> Timeout. Please check later.');
                setTimeout(function() { window.location.href = base_url + 'userrole/invoice'; }, 3000);
            }
            else {
                setTimeout(checkStatus, 4000);
            }
        },
        error: function() {
            if (attempts < maxAttempts) {
                setTimeout(checkStatus, 5000);
            } else {
                window.location.href = base_url + 'userrole/invoice';
            }
        }
    });
}

$(document).ready(function() {
    setTimeout(checkStatus, 3000);
});
</script>