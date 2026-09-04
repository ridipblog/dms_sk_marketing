import RequestModule from "RequestModule";
import Reusebase from "ReusebaseModule";

const apiRequest = new RequestModule();
const reusebase = new Reusebase();

$(document).ready(function () {
    const fetchReport = async (page = 1) => {
        let searchQuery = $("#searchReport").val() || "";
        let dealerId = $("#dealerSelect").val() || "";
        let startDate = $("#startDate").val() || "";
        let endDate = $("#endDate").val() || "";

        $("#reportTableContainer").html(reusebase.getLoadingHtml());

        try {
            let formData = new FormData();
            formData.append("page", page);
            formData.append("search", searchQuery);
            formData.append("dealer_id", dealerId);
            formData.append("start_date", startDate);
            formData.append("end_date", endDate);

            let response = await apiRequest.formPost(
                formData,
                "/reports/dealer-wise-payments/list",
                false,
            );

            if (response?.success ?? null) {
                $("#reportTableContainer").html(response?.html ?? null);

                if (response.total_count !== undefined) {
                    $("#cardTotalCount").text(response.total_count);
                }
                if (response.total_amount !== undefined) {
                    $("#cardTotalAmount").text(response.total_amount);
                }
            } else {
                reusebase.showSwal(
                    "error",
                    "Error",
                    response?.message || "Failed to fetch dealer payment report",
                );
                $("#reportTableContainer").html(
                    reusebase.getErrorHtml("Failed to load report data. Please try again.", "fas fa-exclamation-circle")
                );
            }
        } catch (error) {
            console.error(error);
            reusebase.showSwal(
                "error",
                "Error",
                "An unexpected error occurred while fetching report data",
            );
            $("#reportTableContainer").html(
                reusebase.getErrorHtml("Unexpected error. Please try again.", "fas fa-exclamation-triangle")
            );
        }
    };

    // Initial Fetch
    fetchReport();

    // Refresh Button Handler
    $("#btnRefreshReport").on("click", function () {
        $("#searchReport").val("");
        $("#startDate").val("");
        $("#endDate").val("");
        if ($("#dealerSelect").val() !== "") {
            $("#dealerSelect").val("").trigger("change");
        } else {
            fetchReport();
        }
    });

    // Initialize Select2 for Dealer Select
    reusebase.initSelect2(".select2", "Select Dealer...");

    // Fetch on Dealer Select Change
    $("#dealerSelect").on("change", function () {
        fetchReport();
    });

    // Fetch on Date Range Change
    $("#startDate, #endDate").on("change", function () {
        fetchReport();
    });

    // Debounce for Search
    $("#searchReport").on(
        "keyup",
        reusebase.debounce(function () {
            fetchReport();
        }, 500),
    );

    // Pagination Click Handler
    $(document).on("click", ".pagination a", function (e) {
        e.preventDefault();
        let page = $(this).attr("href").split("page=")[1];
        fetchReport(page);
    });

    // Export Excel Handler
    $("#btnExportReport").on("click", function (e) {
        e.preventDefault();
        let search = $("#searchReport").val() || "";
        let dealerId = $("#dealerSelect").val() || "";
        let startDate = $("#startDate").val() || "";
        let endDate = $("#endDate").val() || "";

        let params = new URLSearchParams({
            search: search,
            dealer_id: dealerId,
            start_date: startDate,
            end_date: endDate
        });

        window.location.href = `/reports/dealer-wise-payments/export?${params.toString()}`;
    });
});
