@extends('layouts.app')



@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="h3 mb-2 text-gray-800 fw-bold">Order Tracking & Financials</h2>
            <p class="text-muted">Select a dealer and/or a specific order to view detailed payment tracks or download reports.</p>
        </div>
    </div>

    <!-- Filter Form -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4 bg-white rounded-4">
                    <form id="trackingForm" action="{{ route('accounts.order_tracking.index') }}" method="GET" class="row g-3 align-items-end">
                        <div class="col-md-5">
                            <label for="dealer_company_id" class="form-label fw-bold text-primary">1. Select Dealer Company</label>
                            <select class="form-select" id="dealer_company_id" name="dealer_company_id" required style="color: #333; background-color: #fff;">
                                <option value="" selected disabled style="color: #999;">-- Choose a Dealer --</option>
                                @foreach($dealerCompanies as $company)
                                    <option value="{{ $company->id }}" {{ (isset($dealerCompanyId) && $dealerCompanyId == $company->id) ? 'selected' : '' }} style="color: #333;">
                                        {{ $company->dealer->dealer_name ?? 'Unknown' }} 
                                        @if($company->company)
                                            ({{ $company->company->company_name }})
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="col-md-5">
                            <label for="order_id" class="form-label fw-bold text-primary">2. Select Order (Optional)</label>
                            <select class="form-select" id="order_id" name="order_id" {{ !isset($dealerCompanyId) || !$dealerCompanyId ? 'disabled' : '' }} style="color: #333; background-color: #fff;">
                                <option value="" selected style="color: #999;">-- All Orders --</option>
                                <!-- Populated via AJAX, but server handles initial state if orderId exists -->
                            </select>
                        </div>
                        
                        <div class="col-md-2 d-grid">
                            <button type="submit" class="btn btn-primary btn-lg rounded-3 shadow-sm mb-2">
                                <i class="fas fa-search me-2"></i> Fetch
                            </button>
                        </div>

                        <!-- PDF Buttons -->
                        <div class="col-12 mt-3 d-flex justify-content-end gap-2 border-top pt-3">
                            <button type="submit" formaction="{{ route('accounts.order_tracking.dealer_pdf') }}" formmethod="POST" formtarget="_blank" class="btn btn-outline-danger shadow-sm">
                                @csrf
                                <i class="fas fa-file-pdf me-2"></i> Dealer Wise PDF
                            </button>
                            <button type="submit" formaction="{{ route('accounts.order_tracking.order_pdf') }}" formmethod="POST" formtarget="_blank" class="btn btn-outline-danger shadow-sm" id="btnOrderPdf" disabled>
                                @csrf
                                <i class="fas fa-file-pdf me-2"></i> Order Wise Receipt
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @if(isset($dealerCompanyId) && isset($selectedCompany) && $selectedCompany)


        <!-- Render Paginated Order Data -->
        @include('accounts.order_tracking.partials.order_data')

    @else
        <!-- Empty State Container -->
        <div class="row">
            <div class="col-12" id="timelineContainer">
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-chart-line fa-4x mb-3 text-light"></i>
                    <h5>Select parameters above to generate tracking data</h5>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    
    // Initialize Select2
    $('#dealer_company_id').select2({
        theme: 'bootstrap-5',
        placeholder: '-- Choose a Dealer --',
        width: '100%'
    });
    
    $('#order_id').select2({
        theme: 'bootstrap-5',
        placeholder: '-- All Orders --',
        width: '100%'
    });

    // Function to load orders based on dealer ID
    function loadOrders(dealerId, selectedOrderId = null) {
        let orderSelect = $('#order_id');
        let orderPdfBtn = $('#btnOrderPdf');
        
        if (dealerId) {
            orderSelect.prop('disabled', true).html('<option value="">Loading...</option>');
            
            $.ajax({
                url: "{{ url('accounts/order-tracking/get-orders') }}/" + dealerId,
                type: "GET",
                success: function(response) {
                    if (response.status === 'success') {
                        let options = '<option value="" style="color: #999;">-- All Orders --</option>';
                        if(response.data.length > 0){
                            response.data.forEach(function(order) {
                                let d = new Date(order.purchase_date);
                                let dateStr = d.toLocaleDateString('en-GB', {day: '2-digit', month: 'short', year: 'numeric'});
                                let selected = (selectedOrderId == order.id) ? 'selected' : '';
                                
                                options += `<option value="${order.id}" ${selected} style="color: #333;">Order: ${order.order_no} (${dateStr})</option>`;
                            });
                        } else {
                            options = '<option value="" style="color: #999;">-- No Orders Found --</option>';
                        }
                        
                        // Add a dummy option for testing purposes
                        options += '<option value="999" ' + (selectedOrderId == 999 ? 'selected' : '') + ' style="color: #333;">Dummy Order (Testing)</option>';

                        orderSelect.html(options).prop('disabled', false).trigger('change.select2');
                        
                        // Enable order PDF button if an order is selected
                        if(selectedOrderId) {
                            orderPdfBtn.prop('disabled', false);
                        } else {
                            orderPdfBtn.prop('disabled', true);
                        }
                    }
                },
                error: function() {
                    orderSelect.html('<option value="">Error loading orders</option>');
                }
            });
        } else {
            orderSelect.html('<option value="">-- All Orders --</option>').prop('disabled', true).trigger('change.select2');
            orderPdfBtn.prop('disabled', true);
        }
    }

    // Initialize orders if dealer is already selected (e.g. after form submit)
    let initialDealerId = $('#dealer_company_id').val();
    let initialOrderId = "{{ $orderId ?? '' }}";
    if (initialDealerId) {
        loadOrders(initialDealerId, initialOrderId);
    }

    // When Dealer is changed, reload orders
    $('#dealer_company_id').on('change', function() {
        let dealerId = $(this).val();
        loadOrders(dealerId);
        $('#btnOrderPdf').prop('disabled', true);
    });

    // When Order is changed, enable/disable Order PDF button
    $('#order_id').on('change', function() {
        if($(this).val()) {
            $('#btnOrderPdf').prop('disabled', false);
        } else {
            $('#btnOrderPdf').prop('disabled', true);
        }
    });

});
</script>
@endpush
