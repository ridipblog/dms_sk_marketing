import Request from "RequestModule";
import Reusebase from "ReusebaseModule";

$(document).ready(function () {
    const apiRequest = new Request(); // We now only need one instance!
    const reusebase = new Reusebase();

    // Enforce 10-digit limit on the mobile number input
    reusebase.preventMobileMaxLength("#phone", 10);

    // Initialize Select2 search select boxes for ASO dropdowns in modals
    $("#aso_id").select2({
        theme: "bootstrap-5",
        dropdownParent: $("#createDealerModal"),
        placeholder: "-- Select ASO Officer (Optional) --",
        allowClear: true,
        width: "100%"
    });

    $("#edit_aso_id").select2({
        theme: "bootstrap-5",
        dropdownParent: $("#editDealerModal"),
        placeholder: "-- Select ASO Officer (Optional) --",
        allowClear: true,
        width: "100%"
    });

    $("#createDealerModal").on("hidden.bs.modal", function () {
        $("#createDealerForm")[0].reset();
        $("#aso_id").val("").trigger("change");
    });

    // ==========================================
    // 1. Fetch & Pagination Logic
    // ==========================================

    const fetchDealers = async (page = 1) => {
        let searchQuery = $("#searchDealer").val() || "";
        let statusFilter = $("#statusFilter").val() || "";
        let fromDate = $("#fromDate").val() || "";
        let toDate = $("#toDate").val() || "";
        $("#dealerTableContainer").html(`
            <div class="text-center py-5 text-muted">
                <i class="fas fa-spinner fa-spin fs-1 mb-3 text-primary"></i>
                <h5 class="fw-bold">Loading Dealers...</h5>
            </div>
        `);

        try {
            let formData = new FormData();
            formData.append("page", page);
            formData.append("search", searchQuery);
            formData.append("status", statusFilter);

            // Both from_date and to_date are required for the date range filter
            if (fromDate && toDate) {
                formData.append("from_date", fromDate);
                formData.append("to_date", toDate);
            }

            let response = await apiRequest.formPost(
                formData,
                "/dealers/list",
                false,
            );

            if (response.success) {
                $("#dealerTableContainer").html(response.html);
            } else {
                $("#dealerTableContainer").html(
                    response.html ||
                        '<div class="alert alert-danger">Failed to load dealers.</div>',
                );
            }
        } catch (error) {
            console.error("Error rendering dealer list:", error);
            $("#dealerTableContainer").html(
                '<div class="alert alert-danger">An error occurred while rendering the dealers list.</div>',
            );
        }
    };

    // Initial load
    fetchDealers(1);

    // Search functionality with debounce
    let searchTimeout;
    $("#searchDealer").on("keyup", function () {
        clearTimeout(searchTimeout);

        searchTimeout = setTimeout(() => {
            fetchDealers(1);
        }, 500); // 500ms delay
    });

    // Status filter change handler
    $("#statusFilter").on("change", function () {
        fetchDealers(1);
    });

    // Date range filter change handlers - only fetch when BOTH dates are selected or BOTH are cleared
    $("#fromDate, #toDate").on("change", function () {
        let fromDate = $("#fromDate").val();
        let toDate = $("#toDate").val();

        if ((fromDate && toDate) || (!fromDate && !toDate)) {
            fetchDealers(1);
        }
    });

    // Clear filters handler
    $("#btnClearFilters").on("click", function (e) {
        e.preventDefault();
        $("#searchDealer").val("");
        $("#statusFilter").val("");
        $("#fromDate").val("");
        $("#toDate").val("");
        fetchDealers(1);
    });

    // Export Excel Click Handler
    $("#btnExportExcel").on("click", function (e) {
        e.preventDefault();
        let search = $("#searchDealer").val() || "";
        let status = $("#statusFilter").val() || "";
        let fromDate = $("#fromDate").val() || "";
        let toDate = $("#toDate").val() || "";

        let params = new URLSearchParams({
            search: search,
            status: status,
            from_date: fromDate,
            to_date: toDate
        });

        window.location.href = "/dealers/export?" + params.toString();
    });

    // Pagination click handler
    $(document).on("click", ".pagination a", function (e) {
        e.preventDefault();
        let pageUrl = $(this).attr("href");
        let pageNumber = new URL(pageUrl).searchParams.get("page");

        if (pageNumber) {
            fetchDealers(pageNumber);
        }
    });

    // ==========================================
    // 2. Create Dealer Form Logic
    // ==========================================
    $("#createDealerForm").on("submit", async function (e) {
        e.preventDefault();

        let originalText = reusebase.setButtonLoading(
            "#btnSubmitDealer",
            "Saving...",
        );

        try {
            let actionUrl = $(this).attr("action");
            let formData = new FormData(this);

            let response = await apiRequest.formPost(
                formData,
                actionUrl,
                false,
            );

            if (response.success) {
                // Close modal and show success alert
                $("#createDealerModal").modal("hide");
                $("#createDealerForm")[0].reset(); // Reset form
                $("#aso_id").val("").trigger("change");

                Swal.fire({
                    icon: "success",
                    title: "Dealer Created!",
                    text:
                        response.message ||
                        "The dealer was successfully added.",
                    showConfirmButton: false,
                    timer: 2000,
                }).then(() => {
                    // Refresh the table asynchronously instead of full page reload
                    fetchDealers(1);
                });
            } else {
                // Show error alert
                Swal.fire({
                    icon: "error",
                    title: "Creation Failed",
                    text:
                        response.message ||
                        "Could not create dealer. Please check the inputs.",
                });
            }
        } catch (error) {
            console.error("Dealer creation error:", error);
            Swal.fire({
                icon: "error",
                title: "Network Error",
                text: "A network error occurred while submitting the form.",
            });
        } finally {
            reusebase.resetButtonLoading("#btnSubmitDealer", originalText);
        }
    });

    // ==========================================
    // 3. Edit Dealer Logic
    // ==========================================

    // Handle clicking the edit button
    $(document).on("click", ".btn-edit-dealer", async function (e) {
        e.preventDefault();
        let dealerId = $(this).data("id");
        let btn = $(this);

        let originalHtml = btn.html();
        reusebase.setButtonLoading(
            btn,
            '<i class="fas fa-spinner fa-spin"></i>',
        );

        try {
            let response = await apiRequest.formGet(
                {},
                `/dealers/${dealerId}/edit`,
                false,
            );

            if (response.success && response.dealer) {
                // Populate the form
                $("#edit_dealer_id").val(response.dealer.id);
                $("#edit_dealer_name").val(response.dealer.dealer_name);
                $("#edit_dealer_code").val(response.dealer.dealer_code);
                $("#edit_email").val(response.dealer.email);
                $("#edit_phone").val(response.dealer.phone);
                $("#edit_status").val(response.dealer.status);
                $("#edit_pan_number").val(response.dealer.pan_number);
                $("#edit_gst_number").val(response.dealer.gst_number);
                $("#edit_address").val(response.dealer.address);

                // Populate ASO options if returned
                if (response.asos && response.asos.length > 0) {
                    let asoOptions = '<option value=""></option>';
                    response.asos.forEach((aso) => {
                        asoOptions += `<option value="${aso.id}">${aso.name}</option>`;
                    });
                    $("#edit_aso_id").html(asoOptions);
                }
                $("#edit_aso_id").val(response.aso_id || "").trigger("change");

                // Set form action URL dynamically
                $("#editDealerForm").attr(
                    "action",
                    `/dealers/${dealerId}/update`,
                );

                // Open Modal
                $("#editDealerModal").modal("show");
            } else {
                Swal.fire({
                    icon: "error",
                    title: "Fetch Failed",
                    text: response.message || "Could not fetch dealer details.",
                });
            }
        } catch (error) {
            console.error("Error fetching dealer:", error);
            Swal.fire({
                icon: "error",
                title: "Network Error",
                text: "Failed to communicate with the server.",
            });
        } finally {
            reusebase.resetButtonLoading(btn, originalHtml);
        }
    });

    // Handle submitting the edit form
    $("#editDealerForm").on("submit", async function (e) {
        e.preventDefault();

        let originalText = reusebase.setButtonLoading(
            "#btnUpdateDealer",
            "Updating...",
        );

        try {
            let actionUrl = $(this).attr("action");
            let formData = new FormData(this);

            let response = await apiRequest.formPost(
                formData,
                actionUrl,
                false,
            );

            if (response.success) {
                $("#editDealerModal").modal("hide");

                Swal.fire({
                    icon: "success",
                    title: "Dealer Updated!",
                    text:
                        response.message ||
                        "The dealer was successfully updated.",
                    showConfirmButton: false,
                    timer: 2000,
                }).then(() => {
                    fetchDealers(1);
                });
            } else {
                Swal.fire({
                    icon: "error",
                    title: "Update Failed",
                    text:
                        response.message ||
                        "Could not update dealer. Please check the inputs.",
                });
            }
        } catch (error) {
            console.error("Dealer update error:", error);
            Swal.fire({
                icon: "error",
                title: "Network Error",
                text: "A network error occurred while submitting the form.",
            });
        } finally {
            reusebase.resetButtonLoading("#btnUpdateDealer", originalText);
        }
    });

    // ==========================================
    // 4. View Dealer Logic
    // ==========================================
    $(document).on("click", ".btn-view-dealer", async function (e) {
        e.preventDefault();
        let dealerId = $(this).data("id");
        let btn = $(this);

        let originalHtml = btn.html();
        reusebase.setButtonLoading(
            btn,
            '<i class="fas fa-spinner fa-spin"></i>',
        );

        try {
            let response = await apiRequest.formGet(
                {},
                `/dealers/${dealerId}/show`,
                false,
            );

            if (response.success && response.dealer) {
                let dealer = response.dealer;

                // Populate profile
                $("#view_dealer_name").text(dealer.dealer_name || "N/A");
                $("#view_dealer_code").text(dealer.dealer_code || "N/A");

                // Populate contact details
                $("#view_email").text(dealer.email || "N/A");
                $("#view_phone").text(dealer.phone || "N/A");
                $("#view_address").text(dealer.address || "N/A");

                // Populate Tax & Status
                $("#view_pan").text(dealer.pan_number || "N/A");
                $("#view_gst").text(dealer.gst_number || "N/A");

                let companies = dealer.dealer_company || dealer.dealerCompany || [];
                let activeCompanyRel = companies.length > 0 ? companies[0] : null;
                let asoName = (activeCompanyRel && activeCompanyRel.aso && activeCompanyRel.aso.name) ? activeCompanyRel.aso.name : "Unassigned";
                $("#view_aso").text(asoName);

                let statusBadge = "";
                if (dealer.status === "active") {
                    statusBadge =
                        '<span class="badge bg-success px-3 py-2 rounded-pill shadow-sm"><i class="fas fa-check-circle me-1"></i> Active</span>';
                } else if (dealer.status === "inactive") {
                    statusBadge =
                        '<span class="badge bg-warning text-dark px-3 py-2 rounded-pill shadow-sm"><i class="fas fa-pause-circle me-1"></i> Inactive</span>';
                } else {
                    statusBadge =
                        '<span class="badge bg-danger px-3 py-2 rounded-pill shadow-sm"><i class="fas fa-ban me-1"></i> Blocked</span>';
                }
                $("#view_status_container").html(statusBadge);

                // Populate companies using Flexbox
                let companiesHtml = "";

                if (companies.length > 0) {
                    companies.forEach((companyRelation) => {
                        let comp = companyRelation.company;
                        if (comp) {
                            let totalCredit = parseFloat(companyRelation.total_credit_amount || 0);
                            let totalDebit = parseFloat(companyRelation.total_debit_amount || 0);
                            let outstandingBalance = totalDebit - totalCredit;
                            
                            let outstandingColor = 'text-secondary';
                            let outstandingDisplay = '0.00';
                            
                            if (outstandingBalance > 0) {
                                outstandingColor = 'text-primary';
                                outstandingDisplay = outstandingBalance.toFixed(2) + ' <small class="text-muted">(Dr)</small>';
                            } else if (outstandingBalance < 0) {
                                outstandingColor = 'text-success';
                                outstandingDisplay = Math.abs(outstandingBalance).toFixed(2) + ' <small class="text-muted">(Cr)</small>';
                            }

                            companiesHtml += `
                                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center bg-white border rounded p-3 shadow-sm">
                                    <div class="mb-2 mb-md-0 d-flex align-items-center">
                                        <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                            <i class="fas fa-building"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-1 fw-bold text-dark">${comp.company_name}</h6>
                                            <span class="badge ${companyRelation.status == 1 ? "bg-success" : "bg-warning text-dark"} bg-opacity-10 border ${companyRelation.status == 1 ? "border-success text-success" : "border-warning"}" style="font-size: 0.7rem;">${companyRelation.status == 1 ? "Active" : "Inactive"}</span>
                                        </div>
                                    </div>
                                    <div class="d-flex gap-4">
                                        <div class="text-md-end">
                                            <p class="text-muted mb-0" style="font-size: 0.65rem; text-transform: uppercase;">Total Credit</p>
                                            <div class="text-success fw-bold" style="font-size: 0.9rem;">₹ ${totalCredit.toFixed(2)}</div>
                                        </div>
                                        <div class="text-md-end">
                                            <p class="text-muted mb-0" style="font-size: 0.65rem; text-transform: uppercase;">Total Debit</p>
                                            <div class="text-danger fw-bold" style="font-size: 0.9rem;">₹ ${totalDebit.toFixed(2)}</div>
                                        </div>
                                        <div class="text-md-end border-start ps-3">
                                            <p class="text-muted mb-0" style="font-size: 0.65rem; text-transform: uppercase;">Outstanding</p>
                                            <div class="${outstandingColor} fw-bold" style="font-size: 0.9rem;">₹ ${outstandingDisplay}</div>
                                        </div>
                                    </div>
                                </div>
                            `;
                        }
                    });
                } else {
                    companiesHtml =
                        '<div class="text-center text-muted py-4"><i class="fas fa-info-circle fs-3 mb-2 opacity-50"></i><p class="mb-0">No associated companies found.</p></div>';
                }
                $("#view_companies_container").html(companiesHtml);

                // Open Modal
                $("#viewDealerModal").modal("show");
            } else {
                Swal.fire({
                    icon: "error",
                    title: "Fetch Failed",
                    text: response.message || "Could not fetch dealer details.",
                });
            }
        } catch (error) {
            console.error("Error fetching dealer view details:", error);
            Swal.fire({
                icon: "error",
                title: "Network Error",
                text: "Failed to communicate with the server.",
            });
        } finally {
            reusebase.resetButtonLoading(btn, originalHtml);
        }
    });
});
