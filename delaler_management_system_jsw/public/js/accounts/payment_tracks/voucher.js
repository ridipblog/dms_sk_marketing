import RequestModule from "RequestModule";
import Reusebase from "ReusebaseModule";

const apiRequest = new RequestModule();
const reusebase = new Reusebase();

$(document).ready(function () {
    const fetchVouchers = async (page = 1) => {
        let searchQuery = $("#searchVoucher").val() || "";

        $("#voucherTableContainer").html(reusebase.getLoadingHtml());

        try {
            let formData = new FormData();
            formData.append("page", page);
            formData.append("search", searchQuery);
            formData.append("invoice_id", $("#invoice_id").val());

            const response = await apiRequest.formPost(
                formData,
                "/accounts/payment-tracks/voucher/list",
                false,
            );

            if (response && response.success) {
                $("#voucherTableContainer").html(response.html);
            } else {
                $("#voucherTableContainer").html(
                    reusebase.getErrorHtml(
                        response.message || "Failed to load vouchers.",
                    ),
                );
            }
        } catch (error) {
            console.error("Voucher Fetch Error:", error);
            $("#voucherTableContainer").html(
                reusebase.getErrorHtml(
                    "An unexpected error occurred while fetching vouchers.",
                ),
            );
        }
    };

    // Initial fetch
    fetchVouchers();

    // Debounced search
    let typingTimer;
    $("#searchVoucher").on("keyup", function () {
        clearTimeout(typingTimer);
        typingTimer = setTimeout(() => {
            fetchVouchers();
        }, 500);
    });

    // Refresh Button Handler
    $("#btnRefreshVouchers").on("click", function () {
        fetchVouchers();
    });

    // Export Excel Handler
    $("#btnExportVouchersExcel").on("click", function (e) {
        e.preventDefault();
        let encryptedId = window.location.pathname.split('/').pop();
        let search = $("#searchVoucher").val() || "";
        let url = "/accounts/payment-tracks/voucher/export-excel/" + encryptedId;
        if (search) {
            url += "?search=" + encodeURIComponent(search);
        }
        window.location.href = url;
    });

    // Export PDF Handler
    $("#btnExportVouchersPdf").on("click", function (e) {
        e.preventDefault();
        let encryptedId = window.location.pathname.split('/').pop();
        let search = $("#searchVoucher").val() || "";
        let url = "/accounts/payment-tracks/voucher/export-pdf/" + encryptedId;
        if (search) {
            url += "?search=" + encodeURIComponent(search);
        }
        window.open(url, '_blank');
    });

    // Pagination Click Handler
    $(document).on("click", ".pagination a", function (e) {
        e.preventDefault();
        let page = $(this).attr("href").split("page=")[1];
        fetchVouchers(page);
    });

    // Toggle Payment for MT field visibility
    $("#voucher_type_id").on("change", function () {
        let selectedText = $(this).find("option:selected").text().toLowerCase();
        
        if (selectedText.includes("receipt")) {
            $("#payment_for_mt_container").show();
            $("#amount").trigger("input"); // calculate immediately based on current amount
        } else {
            $("#payment_for_mt_container").hide();
            $("#payment_for_mt").val("0.000");
        }

        // Handle Number of Days visibility for Debit Note and Credit Note
        if (selectedText.includes("debit note") || selectedText.includes("credit note")) {
            $("#number_of_days_container").show();
        } else {
            $("#number_of_days_container").hide();
            $("#number_of_days").val('');
        }
    });

    // Auto-calculate Payment for MT
    $("#amount").on("input", function () {
        let selectedText = $("#voucher_type_id").find("option:selected").text().toLowerCase();
        if (!selectedText.includes("receipt")) {
            $("#payment_for_mt").val("0.000");
            return;
        }

        try {
            let amount = parseFloat($(this).val()) || 0;
            let totalQuantity = parseFloat($(this).data("total-quantity")) || 0;
            let chargeableAmount =
                parseFloat($(this).data("chargeable-amount")) || 0; // prevent division by zero

            if (chargeableAmount > 0) {
                let paymentForMt = (totalQuantity / chargeableAmount) * amount;
                $("#payment_for_mt").val(paymentForMt.toFixed(3));
            } else {
                $("#payment_for_mt").val("0.000");
            }
        } catch (error) {
            console.error("Payment auto-calculation failed:", error);
            $("#payment_for_mt").val("0.000");
        }
    });

    // Form Submission
    $("#addVoucherForm").on("submit", async function (e) {
        e.preventDefault();

        let modalErrorContainer = $("#modalErrorAlert");

        let originalBtnHtml = reusebase.setButtonLoading(
            "#btnSubmitVoucher",
            "Saving...",
        );
        modalErrorContainer.html("");

        try {
            let formData = new FormData(this);
            const response = await apiRequest.formPost(
                formData,
                "/accounts/payment-tracks/voucher/store",
                false,
            );

            if (response?.success) {
                // Close modal and reset form
                $("#addVoucherModal").modal("hide");
                $("#addVoucherForm")[0].reset();
                // Set default date back
                const today = new Date().toISOString().split("T")[0];
                $("#transaction_date").val(today);

                reusebase.showSwal(
                    "success",
                    "Success",
                    response?.message || "Voucher added successfully!",
                );

                // Refresh list
                fetchVouchers();
            } else {
                reusebase.showSwal(
                    "error",
                    "Error",
                    response?.message || "Failed to add voucher",
                );
            }
        } catch (error) {
            console.error("Voucher Store Error:", error);
            reusebase.showSwal(
                "error",
                "Error",
                error.responseJSON?.message || "An unexpected error occurred.",
            );
        } finally {
            reusebase.resetButtonLoading("#btnSubmitVoucher", originalBtnHtml);
        }
    });

    // Edit Button Handler
    $(document).on("click", ".edit-voucher-btn", async function () {
        let voucherId = $(this).data("id");
        try {
            const response = await apiRequest.formGet({}, `/accounts/payment-tracks/voucher/edit/${voucherId}`, false);
            if (response?.success) {
                let voucher = response.voucher;
                $("#edit_voucher_id").val(voucher.id);
                $("#edit_amount").val(voucher.amount);
                $("#edit_voucher_type_name").val(voucher.voucher_type_name);
                $("#edit_payment_mode").val(voucher.payment_mode);
                $("#edit_transaction_date").val(voucher.transaction_date);
                $("#edit_remarks").val(voucher.remarks);

                let selectedText = voucher.voucher_type_name.toLowerCase();
                if (selectedText.includes("receipt")) {
                    $("#edit_payment_for_mt_container").show();
                    $("#edit_amount").trigger("input"); // calculate immediately
                } else {
                    $("#edit_payment_for_mt_container").hide();
                    $("#edit_payment_for_mt").val("0.000");
                }

                if (selectedText.includes("debit note") || selectedText.includes("credit note")) {
                    $("#edit_number_of_days_container").show();
                    $("#edit_number_of_days").val(voucher.number_of_days || '');
                } else {
                    $("#edit_number_of_days_container").hide();
                    $("#edit_number_of_days").val('');
                }

                $("#editVoucherModal").modal("show");
            } else {
                reusebase.showSwal("error", "Error", response?.message || "Failed to load voucher details.");
            }
        } catch (error) {
            console.error("Fetch Voucher Details Error:", error);
            reusebase.showSwal("error", "Error", error.responseJSON?.message || "An unexpected error occurred while loading voucher details.");
        }
    });

    // Auto-calculate Payment for MT in Edit Modal
    $("#edit_amount").on("input", function () {
        let selectedText = $("#edit_voucher_type_name").val().toLowerCase();
        if (!selectedText.includes("receipt")) {
            $("#edit_payment_for_mt").val("0.000");
            return;
        }

        try {
            let amount = parseFloat($(this).val()) || 0;
            let totalQuantity = parseFloat($(this).data("total-quantity")) || 0;
            let chargeableAmount = parseFloat($(this).data("chargeable-amount")) || 0;

            if (chargeableAmount > 0) {
                let paymentForMt = (totalQuantity / chargeableAmount) * amount;
                $("#edit_payment_for_mt").val(paymentForMt.toFixed(3));
            } else {
                $("#edit_payment_for_mt").val("0.000");
            }
        } catch (error) {
            console.error("Payment auto-calculation failed:", error);
            $("#edit_payment_for_mt").val("0.000");
        }
    });

    // Edit Form Submission
    $("#editVoucherForm").on("submit", async function (e) {
        e.preventDefault();

        let originalBtnHtml = reusebase.setButtonLoading("#btnUpdateVoucher", "Updating...");
        let voucherId = $("#edit_voucher_id").val();

        try {
            let formData = new FormData(this);
            const response = await apiRequest.formPost(
                formData,
                `/accounts/payment-tracks/voucher/update/${voucherId}`,
                false
            );

            if (response?.success) {
                $("#editVoucherModal").modal("hide");
                $("#editVoucherForm")[0].reset();
                reusebase.showSwal("success", "Success", response?.message || "Voucher updated successfully!");
                fetchVouchers();
            } else {
                reusebase.showSwal("error", "Error", response?.message || "Failed to update voucher");
            }
        } catch (error) {
            console.error("Voucher Update Error:", error);
            reusebase.showSwal("error", "Error", error.responseJSON?.message || "An unexpected error occurred.");
        } finally {
            reusebase.resetButtonLoading("#btnUpdateVoucher", originalBtnHtml);
        }
    });

    // Delete Voucher Handler
    $(document).on("click", ".delete-voucher-btn", function () {
        let voucherId = $(this).data("id");

        Swal.fire({
            title: 'Are you sure?',
            text: "You want to delete this voucher? This will update the invoice's outstanding balance.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then(async (result) => {
            if (result.isConfirmed) {
                try {
                    const response = await apiRequest.formPost(
                        new FormData(),
                        `/accounts/payment-tracks/voucher/delete/${voucherId}`,
                        false
                    );

                    if (response?.success) {
                        reusebase.showSwal("success", "Success", response?.message || "Voucher deleted successfully!");
                        fetchVouchers();
                    } else {
                        reusebase.showSwal("error", "Error", response?.message || "Failed to delete voucher.");
                    }
                } catch (error) {
                    console.error("Delete Voucher Error:", error);
                    reusebase.showSwal("error", "Error", error.responseJSON?.message || "An unexpected error occurred.");
                }
            }
        });
    });
});
