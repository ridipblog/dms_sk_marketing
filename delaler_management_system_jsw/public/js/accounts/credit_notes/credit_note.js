import RequestModule from "RequestModule";
import Reusebase from "ReusebaseModule";

const apiRequest = new RequestModule();
const reusebase = new Reusebase();

$(document).ready(function () {
    const fetchCreditNotes = async (page = 1) => {
        let searchQuery = $("#searchCreditNote").val() || "";
        let dealerId = $("#dealerSelect").val() || "";
        let startDate = $("#startDate").val() || "";
        let endDate = $("#endDate").val() || "";

        $("#creditNoteTableContainer").html(reusebase.getLoadingHtml());

        try {
            let formData = new FormData();
            formData.append("page", page);
            formData.append("search", searchQuery);
            formData.append("dealer_id", dealerId);
            formData.append("start_date", startDate);
            formData.append("end_date", endDate);

            let response = await apiRequest.formPost(
                formData,
                "/accounts/credit-notes/list",
                false,
            );

            if (response?.success ?? null) {
                $("#creditNoteTableContainer").html(response?.html ?? null);
            } else {
                reusebase.showSwal(
                    "error",
                    "Error",
                    response?.message || "Failed to fetch credit notes",
                );
                $("#creditNoteTableContainer").html(
                    reusebase.getErrorHtml("Failed to load data. Please try again.", "fas fa-exclamation-circle")
                );
            }
        } catch (error) {
            console.error(error);
            reusebase.showSwal(
                "error",
                "Error",
                "An unexpected error occurred while fetching credit notes",
            );
            $("#creditNoteTableContainer").html(
                reusebase.getErrorHtml("Unexpected error. Please try again.", "fas fa-exclamation-triangle")
            );
        }
    };

    // Initial Fetch
    fetchCreditNotes();

    // Refresh Button Handler
    $("#btnRefreshCreditNotes").on("click", function () {
        $("#searchCreditNote").val("");
        $("#startDate").val("");
        $("#endDate").val("");
        if ($("#dealerSelect").val() !== "") {
            $("#dealerSelect").val("").trigger("change"); // This triggers fetch automatically
        } else {
            fetchCreditNotes();
        }
    });

    // Initialize Select2 for Dealer Select
    reusebase.initSelect2(".select2", "Select Dealer...");

    // Fetch on Dealer Change
    $("#dealerSelect").on("change", function () {
        fetchCreditNotes();
    });

    // Fetch on Date Range Change
    $("#startDate, #endDate").on("change", function () {
        fetchCreditNotes();
    });

    // Setup Debounce for Search
    $("#searchCreditNote").on(
        "keyup",
        reusebase.debounce(function () {
            fetchCreditNotes();
        }, 500),
    );

    // Pagination Click Handler
    $(document).on("click", ".pagination a", function (e) {
        e.preventDefault();
        let page = $(this).attr("href").split("page=")[1];
        fetchCreditNotes(page);
    });

    // Populate View Modal - Use Event Delegation since buttons are loaded via AJAX
    $(document).on("click", ".view-credit-note", function () {
        let btn = $(this);
        let data = btn.data("credit-note");

        $("#viewCreditOrderNo").text(data.order_no);
        $("#viewCreditDealerName").text(data.dealer_name);
        $("#viewCreditTransactionId").text(data.transaction_id);
        $("#viewCreditDate").text(data.credit_date);
        $("#viewCreditAmount").text(data.amount);
        $("#viewCreditNos").text(data.nos);
        $("#viewCreditReason").text(data.reason);

        $('#viewCreditNoteModal').modal('show');
    });
});
