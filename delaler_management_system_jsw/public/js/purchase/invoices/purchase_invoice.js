import Request from "RequestModule";
import Reusebase from "ReusebaseModule";

$(document).ready(function () {
    const apiRequest = new Request();
    const reusebase = new Reusebase();

    const fetchPurchases = async (page = 1) => {
        let searchQuery = $("#searchPurchase").val() || "";
        let supplierId = $("#supplierFilterSelect").val() || "";
        let status = $("#statusFilterSelect").val() || "";

        $("#purchaseTableContainer").html(reusebase.getLoadingHtml());

        try {
            let formData = new FormData();
            formData.append("page", page);
            formData.append("search", searchQuery);
            formData.append("supplier_id", supplierId);
            formData.append("status", status);

            let response = await apiRequest.formPost(
                formData,
                "/purchase/invoices/list",
                false
            );

            if (response?.success) {
                $("#purchaseTableContainer").html(response.html);
            } else {
                reusebase.showSwal(
                    "error",
                    "Error",
                    response?.message || "Failed to fetch invoices"
                );
                $("#purchaseTableContainer").html(
                    reusebase.getErrorHtml("Failed to load invoices. Please try again.", "fas fa-exclamation-circle")
                );
            }
        } catch (error) {
            console.error(error);
            reusebase.showSwal(
                "error",
                "Error",
                "An unexpected error occurred while fetching invoices."
            );
            $("#purchaseTableContainer").html(
                reusebase.getErrorHtml("Unexpected error. Please try again.", "fas fa-exclamation-triangle")
            );
        }
    };

    // Initial Fetch
    fetchPurchases();

    // Filters and search
    $("#btnRefreshPurchases").click(function () {
        $("#searchPurchase").val("");
        $("#statusFilterSelect").val("");
        if ($("#supplierFilterSelect").val() !== "") {
            $("#supplierFilterSelect").val("").trigger("change");
        } else {
            fetchPurchases();
        }
    });

    $("#supplierFilterSelect, #statusFilterSelect").on("change", function () {
        fetchPurchases();
    });

    // Export Excel Click Handler
    $("#btnExportPurchaseExcel").on("click", function (e) {
        e.preventDefault();
        let search = $("#searchPurchase").val() || "";
        let supplierId = $("#supplierFilterSelect").val() || "";
        let status = $("#statusFilterSelect").val() || "";

        let params = new URLSearchParams({
            search: search,
            supplier_id: supplierId,
            status: status
        });

        window.location.href = "/purchase/invoices/export?" + params.toString();
    });

    $("#searchPurchase").on(
        "keyup",
        reusebase.debounce(function () {
            fetchPurchases();
        }, 500)
    );

    $(document).on("click", ".pagination a", function (e) {
        e.preventDefault();
        let page = $(this).attr("href").split("page=")[1];
        fetchPurchases(page);
    });

    // Record payment modal setup
    $(document).on("click", ".record-payment-btn", function () {
        let encId = $(this).data("id");
        let outstanding = $(this).data("outstanding");

        $("#payment_purchase_id").val(encId);
        $("#outstandingBalanceDisplay").text("₹" + parseFloat(outstanding).toLocaleString("en-IN", { minimumFractionDigits: 2 }));
        $("#amount").attr("max", outstanding).val(outstanding);
        $("#recordPaymentModal").modal("show");
    });

    // Submit payment form
    $("#recordPaymentForm").submit(async function (e) {
        e.preventDefault();
        let form = $(this);
        let actionUrl = form.attr("action");
        let btn = form.find('button[type="submit"]');

        let originalText = reusebase.setButtonLoading(btn, "Recording...");

        try {
            let formData = new FormData(this);
            let response = await apiRequest.formPost(formData, actionUrl, false);

            if (response?.success) {
                $("#recordPaymentModal").modal("hide");
                form.trigger("reset");
                await reusebase.showSwal("success", "Recorded!", response.message);
                fetchPurchases();
            } else {
                reusebase.showSwal("error", "Failed", response?.message || "Failed to record payment.");
            }
        } catch (error) {
            console.error(error);
            reusebase.showSwal("error", "Error", "An unexpected error occurred.");
        } finally {
            reusebase.resetButtonLoading(btn, originalText);
        }
    });

    // Finalize invoice action
    $(document).on("click", ".finalize-purchase-btn", async function () {
        let btn = $(this);
        let encId = btn.data("id");
        if (!encId) return;

        let isConfirmed = await reusebase.confirmAction(
            "Finalize Purchase?",
            "Are you sure you want to finalize this purchase invoice? This will increase stock counts and lock the document.",
            "Yes, finalize it!"
        );

        if (!isConfirmed) return;

        let originalHtml = reusebase.setButtonLoading(btn, "Finalizing...");

        try {
            let formData = new FormData();
            formData.append("purchase_id", encId);

            let response = await apiRequest.formPost(formData, "/purchase/invoices/finalize", false);

            if (response?.success) {
                await reusebase.showSwal("success", "Finalized!", response.message);
                if (response.redirect_url) {
                    window.location.href = response.redirect_url;
                } else {
                    fetchPurchases();
                }
            } else {
                reusebase.showSwal("error", "Error", response?.message || "Failed to finalize bill.");
            }
        } catch (error) {
            console.error(error);
            reusebase.showSwal("error", "Error", "An unexpected error occurred during finalization.");
        } finally {
            reusebase.resetButtonLoading(btn, originalHtml);
        }
    });
});
