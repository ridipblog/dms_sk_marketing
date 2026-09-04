import Reusebase from "ReusebaseModule";

$(document).ready(function () {
    const reusebase = new Reusebase();

    // Initialize Select2
    reusebase.initSelect2(".select2", "Choose Dealer...");

    // Handle Financial Year vs Custom Date toggle
    $(document).on("change", "#financial_year", function () {
        let val = $(this).val();
        console.log("Financial year changed to:", val);
        
        if (val === "custom") {
            $("#start_date").prop("disabled", false).removeAttr("disabled").prop("required", true);
            $("#end_date").prop("disabled", false).removeAttr("disabled").prop("required", true);
        } else {
            $("#start_date").prop("disabled", true).attr("disabled", "disabled").prop("required", false).val('');
            $("#end_date").prop("disabled", true).attr("disabled", "disabled").prop("required", false).val('');
        }
    });

    // Form submission logic (optional to add loading state)
    $("#dealerStatementForm").on("submit", function(e) {
        let dealerId = $("#dealer_id").val();
        if (!dealerId) {
            e.preventDefault();
            reusebase.showSwal("warning", "Warning", "Please select a dealer first.");
            return false;
        }

        if ($("#financial_year").val() === "custom") {
            let start = $("#start_date").val();
            let end = $("#end_date").val();
            if (!start || !end) {
                e.preventDefault();
                reusebase.showSwal("warning", "Warning", "Please select both start and end dates for custom range.");
                return false;
            }
            if (new Date(start) > new Date(end)) {
                e.preventDefault();
                reusebase.showSwal("warning", "Warning", "Start date cannot be after end date.");
                return false;
            }
        }

        // Change button to loading state
        let btnHtml = reusebase.setButtonLoading("#btnGenerate", "Generating...");
        
        // Normally we'd reset the button, but since this is a standard POST that loads a new page, 
        // we can leave it spinning until the page unloads, or reset it after a timeout just in case it fails.
        setTimeout(() => {
            reusebase.resetButtonLoading("#btnGenerate", btnHtml);
        }, 5000);
    });
});
