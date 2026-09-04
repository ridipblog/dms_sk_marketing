import RequestModule from "RequestModule";
import Reusebase from "ReusebaseModule";

const apiRequest = new RequestModule();
const reusebase = new Reusebase();

$(document).ready(function () {
    const fetchDebitNotes = async (page = 1) => {
        let searchQuery = $("#searchDebitNote").val() || "";
        let dealerId = $("#dealerSelect").val() || "";
        let startDate = $("#startDate").val() || "";
        let endDate = $("#endDate").val() || "";

        $("#debitNoteTableContainer").html(reusebase.getLoadingHtml());

        try {
            let formData = new FormData();
            formData.append("page", page);
            formData.append("search", searchQuery);
            formData.append("dealer_id", dealerId);
            formData.append("start_date", startDate);
            formData.append("end_date", endDate);

            let response = await apiRequest.formPost(
                formData,
                "/accounts/debit-notes/list",
                false,
            );

            if (response?.success ?? null) {
                $("#debitNoteTableContainer").html(response?.html ?? null);
            } else {
                reusebase.showSwal(
                    "error",
                    "Error",
                    response?.message || "Failed to fetch debit notes",
                );
                $("#debitNoteTableContainer").html(
                    reusebase.getErrorHtml("Failed to load data. Please try again.", "fas fa-exclamation-circle")
                );
            }
        } catch (error) {
            console.error(error);
            reusebase.showSwal(
                "error",
                "Error",
                "An unexpected error occurred while fetching debit notes",
            );
            $("#debitNoteTableContainer").html(
                reusebase.getErrorHtml("Unexpected error. Please try again.", "fas fa-exclamation-triangle")
            );
        }
    };

    // Initial Fetch
    fetchDebitNotes();

    // Refresh Button Handler
    $("#btnRefreshDebitNotes").on("click", function () {
        $("#searchDebitNote").val("");
        $("#startDate").val("");
        $("#endDate").val("");
        if ($("#dealerSelect").val() !== "") {
            $("#dealerSelect").val("").trigger("change"); // This triggers fetch automatically
        } else {
            fetchDebitNotes();
        }
    });

    // Initialize Select2 for Dealer Select
    reusebase.initSelect2(".select2", "Select Dealer...");

    // Fetch on Dealer Change
    $("#dealerSelect").on("change", function () {
        fetchDebitNotes();
    });

    // Fetch on Date Range Change
    $("#startDate, #endDate").on("change", function () {
        fetchDebitNotes();
    });

    // Setup Debounce for Search
    $("#searchDebitNote").on(
        "keyup",
        reusebase.debounce(function () {
            fetchDebitNotes();
        }, 500),
    );

    // Pagination Click Handler
    $(document).on("click", ".pagination a", function (e) {
        e.preventDefault();
        let page = $(this).attr("href").split("page=")[1];
        fetchDebitNotes(page);
    });

    // Populate View Modal - Use Event Delegation since buttons are loaded via AJAX
    $(document).on("click", ".view-debit-note", function () {
        let btn = $(this);
        let data = btn.data("debit-note");

        $("#viewOrderNo").text(data.order_no);
        $("#viewDealerName").text(data.dealer_name);
        $("#viewTransactionId").text(data.transaction_id);
        $("#viewDebitDate").text(data.debit_date);
        $("#viewBaseAmount").text(data.base_amount);
        $("#viewGstAmount").text(data.gst_amount);
        $("#viewAmount").text(data.amount);
        $("#viewNos").text(data.nos);
        $("#viewReason").text(data.reason);

        $('#viewDebitNoteModal').modal('show');
    });
});
