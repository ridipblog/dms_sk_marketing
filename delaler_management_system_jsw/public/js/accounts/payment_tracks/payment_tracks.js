import RequestModule from "RequestModule";
import Reusebase from "ReusebaseModule";

const apiRequest = new RequestModule();
const reusebase = new Reusebase();

$(document).ready(function () {
    const fetchPaymentTracks = async (page = 1) => {
        let searchQuery = $("#searchPayment").val() || "";
        let dealerId = $("#dealerSelect").val() || "";

        $("#paymentTableContainer").html(reusebase.getLoadingHtml());

        try {
            let formData = new FormData();
            formData.append("page", page);
            formData.append("search", searchQuery);
            formData.append("dealer_id", dealerId);

            let response = await apiRequest.formPost(
                formData,
                "/accounts/payment-tracks/list",
                false,
            );

            if (response?.success ?? null) {
                $("#paymentTableContainer").html(response?.html ?? null);
            } else {
                reusebase.showSwal(
                    "error",
                    "Error",
                    response?.message || "Failed to fetch payment tracks",
                );
                $("#paymentTableContainer").html(
                    reusebase.getErrorHtml("Failed to load data. Please try again.", "fas fa-exclamation-circle")
                );
            }
        } catch (error) {
            console.error(error);
            reusebase.showSwal(
                "error",
                "Error",
                "An unexpected error occurred while fetching payment tracks",
            );
            $("#paymentTableContainer").html(
                reusebase.getErrorHtml("Unexpected error. Please try again.", "fas fa-exclamation-triangle")
            );
        }
    };

    // Initial Fetch
    fetchPaymentTracks();

    // Refresh Button Handler
    $("#btnRefreshPayments").on("click", function () {
        $("#searchPayment").val("");
        if ($("#dealerSelect").val() !== "") {
            $("#dealerSelect").val("").trigger("change"); // This triggers fetchPaymentTracks automatically
        } else {
            fetchPaymentTracks();
        }
    });

    // Initialize Select2 for Dealer Select
    reusebase.initSelect2(".select2", "Select Dealer...");

    // Fetch on Dealer Change
    $("#dealerSelect").on("change", function () {
        fetchPaymentTracks();
    });

    // Setup Debounce for Search
    $("#searchPayment").on(
        "keyup",
        reusebase.debounce(function () {
            fetchPaymentTracks();
        }, 500),
    );

    // Pagination Click Handler
    $(document).on("click", ".pagination a", function (e) {
        e.preventDefault();
        let page = $(this).attr("href").split("page=")[1];
        fetchPaymentTracks(page);
    });

    // Validation logic for Payment for MT based on Voucher Type
    function handleVoucherTypeChange(
        selectElement,
        inputElement,
        formSelector,
    ) {
        let selectedOption = $(selectElement).find("option:selected");
        let voucherName = selectedOption.data("name");

        if (voucherName && voucherName.toLowerCase() === "receipt") {
            $(inputElement).prop("required", true);
        } else {
            $(inputElement).prop("required", false);
            $(inputElement).removeClass("is-invalid");
        }

        let journalContainer = $(formSelector + " .journal-type-container");
        let journalSelect = $(formSelector + " .journal-type-select");

        if (
            voucherName &&
            (voucherName.toLowerCase() === "journal" ||
                voucherName.toLowerCase() === "journal voucher")
        ) {
            journalContainer.show();
            journalSelect.prop("required", true);
        } else {
            journalContainer.hide();
            journalSelect.prop("required", false);
            journalSelect.val("");
            journalSelect.removeClass("is-invalid");
        }
    }

    // Add Modal
    $("#addPaymentTrackModal .voucher-type-select").on("change", function () {
        handleVoucherTypeChange(
            this,
            "#addPaymentTrackModal .payment-for-mt-input",
            "#addPaymentTrackModal",
        );
    });

    // Edit Modal
    $("#editPaymentTrackModal .voucher-type-select").on("change", function () {
        handleVoucherTypeChange(
            this,
            "#editPaymentTrackModal .payment-for-mt-input",
            "#editPaymentTrackModal",
        );
    });

    // Form Submit Validation
    $("#addPaymentTrackForm, #editPaymentTrackForm").on("submit", function (e) {
        let form = $(this);
        let select = form.find(".voucher-type-select");
        let mtInput = form.find(".payment-for-mt-input");
        let journalSelect = form.find(".journal-type-select");
        let voucherName = select.find("option:selected").data("name");

        let isValid = true;

        if (
            voucherName &&
            voucherName.toLowerCase() === "receipt" &&
            !mtInput.val()
        ) {
            mtInput.addClass("is-invalid");
            isValid = false;
        } else {
            mtInput.removeClass("is-invalid");
        }

        if (
            voucherName &&
            (voucherName.toLowerCase() === "journal" ||
                voucherName.toLowerCase() === "journal voucher") &&
            !journalSelect.val()
        ) {
            journalSelect.addClass("is-invalid");
            isValid = false;
        } else {
            journalSelect.removeClass("is-invalid");
        }

        if (!isValid) {
            e.preventDefault();
            return false;
        }
    });

    // Populate View Modal - Use Event Delegation since buttons are loaded via AJAX
    $(document).on("click", ".view-payment-btn", function () {
        let btn = $(this);
        $("#view_transaction_id").text(btn.data("transaction_id"));
        $("#view_amount").text("₹ " + btn.data("amount"));
        $("#view_order_no").text(btn.data("order_no"));
        $("#view_transaction_date").text(btn.data("transaction_date"));
        $("#view_voucher_type").text(btn.data("voucher_type"));
        $("#view_payment_mode").text(btn.data("payment_mode"));
        $("#view_payment_for_mt").text(btn.data("payment_for_mt"));
        $("#view_remarks").text(btn.data("remarks"));

        // Mathematical Calculation
        let amountStr = btn.data("amount")
            ? btn.data("amount").toString()
            : "0";
        let amount = parseFloat(amountStr.replace(/,/g, ""));

        let balanceStr = btn.data("balance")
            ? btn.data("balance").toString()
            : "0";
        let balance = parseFloat(balanceStr.replace(/,/g, ""));

        let voucherType = btn.data("voucher_type")
            ? btn.data("voucher_type").toLowerCase()
            : "";
        let openingBalance = 0;
        let actionLabel = "TRANSACTION";
        let actionAmountElem = $("#view_action_amount");

        if (
            voucherType.includes("receipt") ||
            voucherType.includes("credit note")
        ) {
            openingBalance = balance + amount;
            actionLabel = "LESS: " + btn.data("voucher_type").toUpperCase();
            actionAmountElem.html(
                '<span class="text-success">- ₹ ' +
                    amount.toLocaleString("en-IN", {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2,
                    }) +
                    "</span>",
            );
        } else {
            openingBalance = balance - amount;
            actionLabel = "ADD: " + btn.data("voucher_type").toUpperCase();
            actionAmountElem.html(
                '<span class="text-danger">+ ₹ ' +
                    amount.toLocaleString("en-IN", {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2,
                    }) +
                    "</span>",
            );
        }

        let formatCurr = (val) =>
            "₹ " +
            val.toLocaleString("en-IN", {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            });

        $("#view_opening_balance").text(formatCurr(openingBalance));
        $("#view_action_label").text(actionLabel);
        $("#view_closing_balance").text(formatCurr(balance));

        if (voucherType === "debit note") {
            $("#view_late_fine_calculation").show();
        } else {
            $("#view_late_fine_calculation").hide();
        }
    });

    // Populate Edit Modal - Use Event Delegation
    $(document).on("click", ".edit-payment-btn", function () {
        let btn = $(this);
        let form = $("#editPaymentTrackForm");

        // Update form action URL dynamically if needed
        // Assuming route is standard resource route like /accounts/payment_tracks/{id}
        // form.attr('action', '/accounts/payment-tracks/' + btn.data('id'));

        $("#edit_order_id").val(btn.data("order_id")).trigger("change");
        $("#edit_transaction_id").val(btn.data("transaction_id"));
        $("#edit_transaction_date").val(btn.data("transaction_date"));
        $("#edit_amount").val(btn.data("amount").toString().replace(/,/g, ""));
        $("#edit_balance").val(
            btn.data("balance").toString().replace(/,/g, ""),
        );
        $("#edit_voucher_type_id")
            .val(btn.data("voucher_type_id"))
            .trigger("change");
        $("#edit_payment_mode").val(btn.data("payment_mode")).trigger("change");
        $("#edit_payment_for_mt").val(btn.data("payment_for_mt"));
        $("#edit_remarks").val(btn.data("remarks"));
    });
});
