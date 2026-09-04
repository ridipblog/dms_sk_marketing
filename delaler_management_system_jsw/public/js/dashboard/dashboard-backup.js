import Request from "RequestModule";

$(document).ready(function () {
    const apiRequest = new Request();
    const roleType = window.dashboardRoleType || 'Admin';

    // Initialize Select2 on all filter select boxes
    if ($.fn.select2) {
        $('.filter-card .select2').select2({
            theme: 'bootstrap-5',
            width: '100%',
            allowClear: true
        });
    }

    // ----------------------------------------------------
    // 1. Cascading Filters Logic (Respecting Hierarchy)
    // ----------------------------------------------------

    // Reset Buttons
    $('.reset-filters-btn').on('click', function () {
        $('select').val('').trigger('change');
        if (roleType === 'Admin') {
            $('#admin-filter-dm').trigger('change');
            $('#admin-asm-table tbody tr').show();
        } else if (roleType === 'BM') {
            $('#bm-filter-asm').trigger('change');
            $('#bm-asm-table tbody tr').show();
        } else if (roleType === 'ASM') {
            $('#asm-filter-aso').trigger('change');
            $('#asm-aso-table tbody tr').show();
        } else if (roleType === 'ASO') {
            $('#aso-dealers-table tbody tr').show();
        }
        
        // Show notification of reset
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'info',
            title: 'Filters reset to default',
            showConfirmButton: false,
            timer: 1500
        });
    });

    // ----------------------------------------------------
    // Unified Short Filter Handler using RequestModule
    // ----------------------------------------------------
    async function handleFilterChange($select) {
        const parentRole = $select.attr('data-role');
        const parentId = $select.val();
        const targetSelector = $select.attr('data-target');

        let formData = new FormData();
        if (parentRole && parentId) {
            formData.append('parent_role', parentRole);
            formData.append('parent_id', parentId);
        }
        formData.append('dm_id', $('#admin-filter-dm').val() || '');
        formData.append('asm_id', $('#admin-filter-asm').val() || '');
        formData.append('aso_id', $('#admin-filter-aso').val() || '');
        formData.append('dealer_id', $('#admin-filter-dealer').val() || '');
        formData.append('date', $('#admin-filter-date').val() || '');

        try {
            const res = await apiRequest.formPost(formData, '/dashboard/filter-options', false);
            if (res && res.success) {
                if (targetSelector) {
                    const $target = $(targetSelector);
                    const placeholder = $target.attr('data-placeholder') || 'Options';
                    let html = `<option value="">${placeholder}</option>`;
                    if (res.options) {
                        res.options.forEach(opt => {
                            html += `<option value="${opt.id}">${opt.name}</option>`;
                        });
                    }
                    $target.html(html).trigger('change.select2');
                }
                if (res.outstanding_formatted) {
                    $('#admin-kpi-outstanding').text(res.outstanding_formatted);
                }
            }
        } catch (err) {
            console.error('Error in filter change:', err);
        }
    }

    if (roleType === 'Admin') {
        $('.admin-cascade-select, #admin-filter-dealer, #admin-filter-date').on('change', function () {
            handleFilterChange($(this));
        });
    }

    // BM Filters Cascade: ASM -> ASO -> Dealer
    if (roleType === 'BM') {
        const $asm = $('#bm-filter-asm');
        const $aso = $('#bm-filter-aso');
        const $dealer = $('#bm-filter-dealer');

        // Cache original options
        const originalAsoOpts = $aso.find('option').clone();
        const originalDealerOpts = $dealer.find('option').clone();

        $asm.on('change', function () {
            const selectedAsm = $(this).val();
            const selectedAsmText = $(this).find('option:selected').text().trim();

            // Filter ASOs
            $aso.empty().append(originalAsoOpts.first());
            if (selectedAsm) {
                originalAsoOpts.each(function () {
                    const dataAsm = $(this).attr('data-asm');
                    if (dataAsm && String(dataAsm) === String(selectedAsm)) {
                        $aso.append($(this).clone());
                    }
                });
            } else {
                $aso.append(originalAsoOpts.slice(1).clone());
            }
            $aso.trigger('change.select2').trigger('change');

            // Visual Table Filtering (Filter ASM performance league table)
            if (selectedAsm) {
                $('#bm-asm-table tbody tr').each(function() {
                    const rowAsmId = $(this).attr('data-asm-id');
                    const rowAsmName = $(this).attr('data-asm-name') || $(this).text();
                    if ((rowAsmId && String(rowAsmId) === String(selectedAsm)) || rowAsmName.includes(selectedAsmText)) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            } else {
                $('#bm-asm-table tbody tr').show();
            }
        });

        $aso.on('change', function () {
            const selectedAso = $(this).val();

            // Filter Dealers
            $dealer.empty().append(originalDealerOpts.first());
            if (selectedAso) {
                originalDealerOpts.each(function () {
                    const dataAso = $(this).attr('data-aso');
                    if (dataAso && String(dataAso) === String(selectedAso)) {
                        $dealer.append($(this).clone());
                    }
                });
            } else {
                $dealer.append(originalDealerOpts.slice(1).clone());
            }
            $dealer.trigger('change.select2').trigger('change');
        });
    }

    // ASM Filters Cascade: ASO -> Dealer
    if (roleType === 'ASM') {
        const $aso = $('#asm-filter-aso');
        const $dealer = $('#asm-filter-dealer');
        const originalDealerOpts = $dealer.find('option').clone();

        $aso.on('change', function () {
            const selectedAso = $(this).val();
            const selectedAsoText = $(this).find('option:selected').text().trim();

            // Filter Dealers
            $dealer.empty().append(originalDealerOpts.first());
            if (selectedAso) {
                originalDealerOpts.each(function () {
                    const dataAso = $(this).attr('data-aso');
                    if (dataAso && String(dataAso) === String(selectedAso)) {
                        $dealer.append($(this).clone());
                    }
                });
            } else {
                $dealer.append(originalDealerOpts.slice(1).clone());
            }

            // Filter ASO scorecard table
            if (selectedAso) {
                $('#asm-aso-table tbody tr').each(function() {
                    const rowAsoId = $(this).attr('data-aso-id');
                    const rowAsoName = $(this).attr('data-aso-name') || $(this).text();
                    if ((rowAsoId && String(rowAsoId) === String(selectedAso)) || rowAsoName.includes(selectedAsoText)) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            } else {
                $('#asm-aso-table tbody tr').show();
            }
        });

        $dealer.on('change', function () {
            const selectedDealer = $(this).val();
            const selectedDealerText = $(this).find('option:selected').text().trim();

            if (selectedDealer) {
                $('#asm-dealer-table tbody tr').each(function() {
                    const rowDealerId = $(this).attr('data-dealer-id');
                    const rowDealerName = $(this).attr('data-dealer-name') || $(this).text();
                    if ((rowDealerId && String(rowDealerId) === String(selectedDealer)) || rowDealerName.includes(selectedDealerText)) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            } else {
                $('#asm-dealer-table tbody tr').show();
            }
        });
    }

    // ASO Filters: Dealer & Status
    if (roleType === 'ASO') {
        const $dealerSelect = $('#aso-filter-dealer');
        const $statusSelect = $('#aso-filter-status');

        function filterAsoTable() {
            const dealerVal = $dealerSelect.val();
            const dealerText = $dealerSelect.find('option:selected').text().trim();
            const statusVal = $statusSelect.val();

            $('#aso-dealers-table tbody tr').each(function() {
                const trDealerId = $(this).attr('data-dealer-id');
                const trDealerName = $(this).attr('data-dealer-name') || $(this).find('td:first-child').text().trim();
                const trStatus = $(this).attr('data-status');
                
                let show = true;
                if (dealerVal) {
                    if (trDealerId && String(trDealerId) === String(dealerVal)) {
                        // Matches by ID
                    } else if (trDealerName.includes(dealerText)) {
                        // Matches by Name
                    } else {
                        show = false;
                    }
                }
                if (statusVal && trStatus !== statusVal) {
                    show = false;
                }

                if (show) $(this).show();
                else $(this).hide();
            });
        }

        $dealerSelect.on('change', filterAsoTable);
        $statusSelect.on('change', filterAsoTable);
    }


    // ----------------------------------------------------
    // 2. Charts Rendering (Chart.js Role-Specific Layouts)
    // ----------------------------------------------------
    
    // BRANCH MANAGER CHARTS
    if (roleType === 'BM') {
        const ctxAsm = document.getElementById('bmAsmChart');
        if (ctxAsm) {
            const perfData = window.asmPerformance || [];
            const labels = perfData.map(p => p.name);
            const salesData = perfData.map(p => (p.sales / 100000).toFixed(2));
            const outstandingData = perfData.map(p => (p.outstanding / 100000).toFixed(2));

            new Chart(ctxAsm, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Billing (₹ L)',
                            data: salesData,
                            backgroundColor: '#0f4c81', // JSW Blue
                            borderRadius: 6,
                            barPercentage: 0.6
                        },
                        {
                            label: 'Outstanding (₹ L)',
                            data: outstandingData,
                            backgroundColor: '#ef4444',
                            borderRadius: 6,
                            barPercentage: 0.6
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'top' }
                    },
                    scales: {
                        y: { beginAtZero: true, grid: { borderDash: [5, 5] } },
                        x: { grid: { display: false } }
                    }
                }
            });
        }

        const ctxAgeing = document.getElementById('bmAgeingChart');
        if (ctxAgeing) {
            new Chart(ctxAgeing, {
                type: 'doughnut',
                data: {
                    labels: ['0-30 Days', '31-60 Days', '61-90 Days', '90+ Days'],
                    datasets: [{
                        data: [12.4, 6.2, 4.1, 1.8],
                        backgroundColor: ['#10b981', '#06b6d4', '#f59e0b', '#ef4444'],
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '70%',
                    plugins: {
                        legend: { display: false }
                    }
                }
            });
        }
    }

    // AREA SALES MANAGER CHARTS
    if (roleType === 'ASM') {
        const ctxAso = document.getElementById('asmAsoChart');
        if (ctxAso) {
            const scorecard = window.asoScorecard || [];
            const labels = scorecard.map(s => s.name);
            const salesData = scorecard.map(s => (s.sales / 100000).toFixed(2));
            const outstandingData = scorecard.map(s => (s.outstanding / 100000).toFixed(2));

            new Chart(ctxAso, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Billing (₹ L)',
                            data: salesData,
                            backgroundColor: '#10b981',
                            borderRadius: 6
                        },
                        {
                            label: 'Outstanding (₹ L)',
                            data: outstandingData,
                            backgroundColor: '#ef4444',
                            borderRadius: 6
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: 'y', // Horizontal bars
                    plugins: {
                        legend: { position: 'top' }
                    },
                    scales: {
                        x: { beginAtZero: true, grid: { borderDash: [5, 5] } },
                        y: { grid: { display: false } }
                    }
                }
            });
        }

        const ctxBrand = document.getElementById('asmBrandChart');
        if (ctxBrand) {
            new Chart(ctxBrand, {
                type: 'pie',
                data: {
                    labels: ['JSW NeoSteel', 'JSW Cement', 'JSW Paints'],
                    datasets: [{
                        data: [55, 25, 20],
                        backgroundColor: ['#0f4c81', '#10b981', '#f59e0b'],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom' }
                    }
                }
            });
        }
    }

    // ASSISTANT SECTION OFFICER CHARTS
    if (roleType === 'ASO') {
        const ctxDealerOut = document.getElementById('asoDealerOutstandingChart');
        if (ctxDealerOut) {
            const dealers = window.dealersList || [];
            const labels = dealers.map(d => d.name);
            const salesData = dealers.map(d => (d.sales / 100000).toFixed(2));
            const outstandingData = dealers.map(d => (d.outstanding / 100000).toFixed(2));

            new Chart(ctxDealerOut, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Billing (₹ L)',
                            data: salesData,
                            backgroundColor: '#0f4c81',
                            borderRadius: 6
                        },
                        {
                            label: 'Outstanding (₹ L)',
                            data: outstandingData,
                            backgroundColor: '#ef4444',
                            borderRadius: 6
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'top' }
                    },
                    scales: {
                        y: { beginAtZero: true, grid: { borderDash: [5, 5] } },
                        x: { grid: { display: false } }
                    }
                }
            });
        }

        const ctxCatSales = document.getElementById('asoCategorySalesChart');
        if (ctxCatSales) {
            new Chart(ctxCatSales, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                    datasets: [
                        {
                            label: 'NeoSteel (MT)',
                            data: [42, 48, 55, 52, 58, 62],
                            borderColor: '#0f4c81',
                            backgroundColor: 'transparent',
                            tension: 0.3,
                            borderWidth: 2
                        },
                        {
                            label: 'Cement (MT)',
                            data: [25, 28, 30, 29, 32, 35],
                            borderColor: '#10b981',
                            backgroundColor: 'transparent',
                            tension: 0.3,
                            borderWidth: 2
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom', labels: { boxHeight: 2 } }
                    },
                    scales: {
                        y: { beginAtZero: true, grid: { borderDash: [5, 5] } },
                        x: { grid: { display: false } }
                    }
                }
            });
        }
    }

    // DEALER CHARTS
    if (roleType === 'Dealer') {
        const ctxPurchase = document.getElementById('dealerPurchaseChart');
        if (ctxPurchase) {
            new Chart(ctxPurchase, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                    datasets: [
                        {
                            label: 'JSW NeoSteel Purchases (MT)',
                            data: [35, 42, 40, 48, 45, 47.5],
                            borderColor: '#0f4c81',
                            backgroundColor: 'rgba(15, 76, 129, 0.05)',
                            fill: true,
                            tension: 0.3,
                            borderWidth: 3
                        },
                        {
                            label: 'JSW Cement Purchases (Bag x100)',
                            data: [20, 25, 22, 30, 28, 32],
                            borderColor: '#10b981',
                            backgroundColor: 'transparent',
                            tension: 0.3,
                            borderWidth: 2
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'top' }
                    },
                    scales: {
                        y: { beginAtZero: true, grid: { borderDash: [5, 5] } },
                        x: { grid: { display: false } }
                    }
                }
            });
        }
    }
});
