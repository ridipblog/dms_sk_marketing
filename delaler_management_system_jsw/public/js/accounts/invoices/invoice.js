import RequestModule from "RequestModule";
import Reusebase from "ReusebaseModule";
import ReuseInvoice from "ReuseInvoiceModule";

const apiRequest = new RequestModule();
const reusebase = new Reusebase();
const reuseInvoice = new ReuseInvoice(apiRequest, reusebase);

$(document).ready(function () {
    const fetchInvoices = async (page = 1) => {
        let searchQuery = $("#searchInvoice").val() || "";
        let dealerId = $("#dealerSelect").val() || "";
        let status = $("#statusSelect").val() || "";

        $("#invoiceTableContainer").html(reusebase.getLoadingHtml());

        try {
            let formData = new FormData();
            formData.append("page", page);
            formData.append("search", searchQuery);
            formData.append("dealer_id", dealerId);
            formData.append("status", status);

            let response = await apiRequest.formPost(
                formData,
                "/accounts/invoices/list",
                false,
            );

            if (response?.success ?? null) {
                $("#invoiceTableContainer").html(response?.html ?? null);
            } else {
                reusebase.showSwal(
                    "error",
                    "Error",
                    response?.message || "Failed to fetch invoices",
                );
                $("#invoiceTableContainer").html(
                    reusebase.getErrorHtml("Failed to load data. Please try again.", "fas fa-exclamation-circle")
                );
            }
        } catch (error) {
            console.error(error);
            reusebase.showSwal(
                "error",
                "Error",
                "An unexpected error occurred while fetching invoices",
            );
            $("#invoiceTableContainer").html(
                reusebase.getErrorHtml("Unexpected error. Please try again.", "fas fa-exclamation-triangle")
            );
        }
    };

    // Initial Fetch
    fetchInvoices();

    // Initialize Select2 for Dealer Select
    reusebase.initSelect2(".select2", "Select Dealer...");

    // Refresh Button Handler
    $("#btnRefreshInvoices").on("click", function () {
        $("#searchInvoice").val("");
        $("#statusSelect").val("");
        
        if ($("#dealerSelect").val() !== "") {
            $("#dealerSelect").val("").trigger("change"); // This triggers fetchInvoices automatically
        } else {
            fetchInvoices();
        }
    });

    // Export Excel Click Handler
    $("#btnExportInvoicesExcel").on("click", function (e) {
        e.preventDefault();
        let search = $("#searchInvoice").val() || "";
        let dealerId = $("#dealerSelect").val() || "";
        let status = $("#statusSelect").val() || "";

        let params = new URLSearchParams({
            search: search,
            dealer_id: dealerId,
            status: status
        });

        window.location.href = "/accounts/invoices/export?" + params.toString();
    });

    // Fetch on Select Change
    $("#dealerSelect, #statusSelect").on("change", function () {
        fetchInvoices();
    });

    // Setup Debounce for Search
    $("#searchInvoice").on(
        "keyup",
        reusebase.debounce(function () {
            fetchInvoices();
        }, 500),
    );

    // Pagination Click Handler
    $(document).on("click", ".pagination a", function (e) {
        e.preventDefault();
        let page = $(this).attr("href").split("page=")[1];
        fetchInvoices(page);
    });

    // Generate Invoice Click Handler
    $(document).on("click", ".generate-invoice-btn", async function () {
        let btn = $(this);
        let invoice_id = btn.data("id");
        if (!invoice_id) return;

        await reuseInvoice.generate(btn, invoice_id, finalizeInvoiceUrl, fetchInvoices);
    });

    // Delete Invoice Click Handler
    $(document).on("click", ".delete-invoice-btn", async function () {
        let btn = $(this);
        let invoice_id = btn.data("id");
        if (!invoice_id) return;

        await reuseInvoice.delete(btn, invoice_id, checkDeleteInvoiceUrl, deleteInvoiceUrl, fetchInvoices);
    });
});
