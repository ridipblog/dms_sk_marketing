import RequestModule from "RequestModule";
import Reusebase from "ReusebaseModule";

const apiRequest = new RequestModule();
const reusebase = new Reusebase();

$(document).ready(function () {
    let currentReportType = "product_wise";

    const fetchReport = async (page = 1) => {
        let productId = $("#productSelect").val() || "";
        let startDate = $("#startDate").val() || "";
        let endDate = $("#endDate").val() || "";

        $("#reportTableContainer").html(reusebase.getLoadingHtml());

        try {
            let formData = new FormData();
            formData.append("page", page);
            formData.append("report_type", currentReportType);
            formData.append("product_id", productId);
            formData.append("start_date", startDate);
            formData.append("end_date", endDate);

            let response = await apiRequest.formPost(
                formData,
                "/reports/daily-stock/list",
                false,
            );

            if (response?.success ?? null) {
                $("#reportTableContainer").html(response?.html ?? null);

                if (response.opening_stock !== undefined) {
                    $("#cardOpeningStock").text(response.opening_stock);
                }
                if (response.closing_stock !== undefined) {
                    $("#cardClosingStock").text(response.closing_stock);
                }
                if (response.total_sale !== undefined) {
                    $("#cardTotalSale").text(response.total_sale);
                }
                if (response.total_purchase !== undefined) {
                    $("#cardTotalPurchase").text(response.total_purchase);
                }
            } else {
                reusebase.showSwal(
                    "error",
                    "Error",
                    response?.message || "Failed to fetch daily stock report",
                );
                $("#reportTableContainer").html(
                    reusebase.getErrorHtml("Failed to load stock data. Please try again.", "fas fa-exclamation-circle")
                );
            }
        } catch (error) {
            console.error(error);
            reusebase.showSwal(
                "error",
                "Error",
                "An unexpected error occurred while fetching stock report data",
            );
            $("#reportTableContainer").html(
                reusebase.getErrorHtml("Unexpected error. Please try again.", "fas fa-exclamation-triangle")
            );
        }
    };

    // Initial Fetch
    fetchReport();

    // Initialize Select2 for Product Select
    reusebase.initSelect2(".select2", "Filter by Product...");

    // Tab Switch Handler
    $("#stockReportTabs .nav-link").on("click", function (e) {
        e.preventDefault();
        $("#stockReportTabs .nav-link").removeClass("active text-primary").addClass("text-secondary");
        $(this).addClass("active text-primary").removeClass("text-secondary");

        currentReportType = $(this).data("type");
        fetchReport(1);
    });

    // Refresh Button Handler
    $("#btnRefreshReport").on("click", function () {
        const now = new Date();
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, "0");
        const firstDay = `${year}-${month}-01`;
        const lastDayObj = new Date(year, now.getMonth() + 1, 0);
        const lastDay = `${year}-${month}-${String(lastDayObj.getDate()).padStart(2, "0")}`;

        $("#startDate").val(firstDay);
        $("#endDate").val(lastDay);
        if ($("#productSelect").val() !== "") {
            $("#productSelect").val("").trigger("change");
        } else {
            fetchReport();
        }
    });

    // Fetch on Product or Date Change
    $("#productSelect, #startDate, #endDate").on("change", function () {
        fetchReport();
    });

    // Pagination Click Handler
    $(document).on("click", ".pagination a", function (e) {
        e.preventDefault();
        let page = $(this).attr("href").split("page=")[1];
        fetchReport(page);
    });

    // Export Excel Handler
    $("#btnExportReport").on("click", function (e) {
        e.preventDefault();
        let productId = $("#productSelect").val() || "";
        let startDate = $("#startDate").val() || "";
        let endDate = $("#endDate").val() || "";

        let params = new URLSearchParams({
            report_type: currentReportType,
            product_id: productId,
            start_date: startDate,
            end_date: endDate
        });

        window.location.href = `/reports/daily-stock/export?${params.toString()}`;
    });
});
