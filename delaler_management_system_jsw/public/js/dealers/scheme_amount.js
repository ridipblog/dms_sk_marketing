import Request from "RequestModule";
import Reusebase from "ReusebaseModule";

$(document).ready(function () {
    const apiRequest = new Request();
    const reusebase = new Reusebase();

    // Initialize Select2
    reusebase.initSelect2("#dealer_company_id", "Choose Dealer...");

    // Load initial list
    loadSchemeAmounts();

    // When dealer selection changes in form, automatically load and filter records for that dealer
    $(document).on("change", "#dealer_company_id", function () {
        loadSchemeAmounts();
    });

    // Auto-fetch total quantity for selected dealer, month, and year from invoices
    async function fetchMonthlyQuantity() {
        let dealerCompanyId = $("#dealer_company_id").val();
        let month = $("#month").val();
        let year = $("#year").val();

        if (!dealerCompanyId || !month || !year) {
            return;
        }

        try {
            let formData = new FormData();
            formData.append("dealer_company_id", dealerCompanyId);
            formData.append("month", month);
            formData.append("year", year);

            let response = await apiRequest.formPost(formData, "/dealers/scheme-amounts/get-monthly-quantity", false);

            if (response && response.success) {
                $("#quantity").val(response.total_quantity);
            }
        } catch (error) {
            console.error("Fetch Monthly Quantity Error:", error);
        }
    }

    // Trigger monthly quantity fetch on dealer, month, or year selection
    $(document).on("change", "#dealer_company_id, #month, #year", function () {
        fetchMonthlyQuantity();
    });

    // Trigger initial fetch if dealer, month, and year are pre-selected
    if ($("#dealer_company_id").val() && $("#month").val() && $("#year").val()) {
        fetchMonthlyQuantity();
    }

    // Form Submit
    $("#addSchemeAmountForm").on("submit", async function (e) {
        e.preventDefault();
        let btn = $("#btnSaveScheme");
        let originalText = btn.html();

        btn.prop("disabled", true).html('<i class="fas fa-spinner fa-spin me-1"></i> Saving...');

        try {
            let formData = new FormData(this);

            let response = await apiRequest.formPost(formData, "/dealers/scheme-amounts/store", false);

            if (response && response.prompt_scheme) {
                let confirmResult = await Swal.fire({
                    title: "Apply to Outstanding Invoices?",
                    text: response.message,
                    icon: "question",
                    showCancelButton: true,
                    confirmButtonColor: "#3085d6",
                    cancelButtonColor: "#6c757d",
                    confirmButtonText: "Yes, apply to invoices",
                    cancelButtonText: "No, save as scheme balance only",
                    allowOutsideClick: false
                });

                formData.append("confirm_scheme", "1");
                formData.append("use_scheme", confirmResult.isConfirmed ? "1" : "0");

                response = await apiRequest.formPost(formData, "/dealers/scheme-amounts/store", false);
            }

            btn.prop("disabled", false).html(originalText);

            if (response && response.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Saved!',
                    text: response.message,
                    timer: 1500,
                    showConfirmButton: false
                });
                $("#quantity").val("");
                $("#rate_per_mt").val("");
                $("#amount").val("");
                $("#remarks").val("");
                // Reload list for the current selected dealer
                loadSchemeAmounts();
            } else {
                Swal.fire('Error', (response ? response.message : null) || 'Failed to save scheme amount', 'error');
            }
        } catch (error) {
            btn.prop("disabled", false).html(originalText);
            console.error("Store Scheme Amount Error:", error);
            Swal.fire('Error', 'An unexpected error occurred.', 'error');
        }
    });

    // Delete Scheme Amount Record
    $(document).on("click", ".delete-scheme-btn", function () {
        let recordId = $(this).data("id");

        Swal.fire({
            title: 'Are you sure?',
            text: 'This will remove the scheme amount record and update the dealer total.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then(async (result) => {
            if (result.isConfirmed) {
                try {
                    let formData = new FormData();
                    formData.append("id", recordId);

                    let response = await apiRequest.formPost(formData, "/dealers/scheme-amounts/delete", false);

                    if (response && response.success) {
                        Swal.fire('Deleted!', response.message, 'success');
                        loadSchemeAmounts();
                    } else {
                        Swal.fire('Error', (response ? response.message : null) || 'Failed to delete record', 'error');
                    }
                } catch (error) {
                    console.error("Delete Scheme Amount Error:", error);
                    Swal.fire('Error', 'An unexpected error occurred.', 'error');
                }
            }
        });
    });

    // Handle Pagination Clicks
    $(document).on("click", "#schemeAmountsTableContainer .pagination a", function (e) {
        e.preventDefault();
        let pageUrl = $(this).attr("href");
        if (pageUrl) {
            let pageNumber = new URL(pageUrl, window.location.origin).searchParams.get("page");
            if (pageNumber) {
                loadSchemeAmounts(pageNumber);
            }
        }
    });

    // Export Excel Handler
    $("#btnExportSchemeExcel").on("click", function (e) {
        e.preventDefault();
        let dealerCompanyId = $("#dealer_company_id").val();
        let url = "/dealers/scheme-amounts/export-excel";
        if (dealerCompanyId) {
            url += "?dealer_company_id=" + encodeURIComponent(dealerCompanyId);
        }
        window.location.href = url;
    });

    // Export PDF Handler
    $("#btnExportSchemePdf").on("click", function (e) {
        e.preventDefault();
        let dealerCompanyId = $("#dealer_company_id").val();
        let url = "/dealers/scheme-amounts/export-pdf";
        if (dealerCompanyId) {
            url += "?dealer_company_id=" + encodeURIComponent(dealerCompanyId);
        }
        window.open(url, "_blank");
    });

    async function loadSchemeAmounts(page = 1) {
        try {
            let dealerCompanyId = $("#dealer_company_id").val();
            let formData = new FormData();
            formData.append("page", page);
            if (dealerCompanyId) {
                formData.append("dealer_company_id", dealerCompanyId);
            }

            let response = await apiRequest.formPost(formData, "/dealers/scheme-amounts/list", false);

            if (response && response.success) {
                $("#schemeAmountsTableContainer").html(response.html);

                if (response.total_scheme_amount !== null && response.total_scheme_amount !== undefined) {
                    $("#dealerTotalSchemeBadge").text("Total Scheme Amount: ₹" + response.total_scheme_amount);
                    $("#dealerTotalSchemeBadgeContainer").removeClass("d-none");
                } else {
                    $("#dealerTotalSchemeBadgeContainer").addClass("d-none");
                }
            } else {
                $("#dealerTotalSchemeBadgeContainer").addClass("d-none");
                $("#schemeAmountsTableContainer").html(
                    '<div class="alert alert-danger m-3">' + ((response ? response.message : null) || 'Failed to load data') + '</div>'
                );
            }
        } catch (error) {
            console.error("Load Scheme Amounts Error:", error);
            $("#schemeAmountsTableContainer").html(
                '<div class="alert alert-danger m-3">An unexpected error occurred loading data.</div>'
            );
        }
    }
});
