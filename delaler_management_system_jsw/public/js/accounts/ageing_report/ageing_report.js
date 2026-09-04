import Request from "RequestModule";
import Reusebase from "ReusebaseModule";

$(document).ready(function () {
    const apiRequest = new Request();
    const reusebase = new Reusebase();

    // Initialize Select2
    reusebase.initSelect2("#filterDealer", "All Dealers");

    // ==========================================
    // 1. Fetch & Pagination Logic
    // ==========================================

    const fetchAgeingData = async (page = 1) => {
        let searchQuery = $("#searchQuery").val() || "";
        let dealerFilter = $("#filterDealer").val() || "";
        let bucketFilter = $("#filterBucket").val() || "";

        $("#ageingTableContainer").html(`
            <div class="text-center py-5 text-muted">
                <i class="fas fa-spinner fa-spin fs-1 mb-3 text-primary"></i>
                <h5 class="fw-bold">Generating Ageing Report...</h5>
            </div>
        `);

        try {
            let formData = new FormData();
            formData.append("page", page);
            formData.append("search", searchQuery);
            formData.append("dealer_id", dealerFilter);
            formData.append("bucket", bucketFilter);

            let response = await apiRequest.formPost(
                formData,
                "/accounts/ageing-report/list",
                false,
            );

            if (response.success) {
                $("#ageingTableContainer").html(response.html);
                if (response.totals) {
                    $("#total-outstanding-val").text("₹ " + response.totals.total_outstanding);
                    $("#range-30-val").text("₹ " + response.totals.range_30);
                    $("#range-60-val").text("₹ " + response.totals.range_60);
                    $("#range-90-val").text("₹ " + response.totals.range_90);
                    $("#range-90-plus-val").text("₹ " + response.totals.range_90_plus);
                }
            } else {
                $("#ageingTableContainer").html(
                    response.html ||
                        '<div class="alert alert-danger m-3">Failed to load ageing report details.</div>',
                );
            }
        } catch (error) {
            console.error("Error rendering ageing list:", error);
            $("#ageingTableContainer").html(
                '<div class="alert alert-danger m-3">An error occurred while rendering the ageing list.</div>',
            );
        }
    };

    // Initial load
    fetchAgeingData(1);

    // Search functionality with debounce (500ms delay)
    let searchTimeout;
    $("#searchQuery").on("keyup", function () {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            fetchAgeingData(1);
        }, 500);
    });

    // Filter handlers
    $("#filterDealer, #filterBucket").on("change", function () {
        fetchAgeingData(1);
    });

    // Pagination click handler
    $(document).on("click", ".ageing-pagination .pagination a", function (e) {
        e.preventDefault();
        let pageUrl = $(this).attr("href");
        let pageNumber = new URL(pageUrl).searchParams.get("page");

        if (pageNumber) {
            fetchAgeingData(pageNumber);
        }
    });

    // Export functionality
    $("#btnExportCSV").on("click", function () {
        let searchQuery = $("#searchQuery").val() || "";
        let dealerFilter = $("#filterDealer").val() || "";
        let bucketFilter = $("#filterBucket").val() || "";

        let params = $.param({
            search: searchQuery,
            dealer_id: dealerFilter,
            bucket: bucketFilter
        });

        window.location.href = "/accounts/ageing-report/export?" + params;
    });

    // Refresh and Reset functionality
    $("#btnRefresh").on("click", function () {
        $("#searchQuery").val("");
        $("#filterBucket").val("");
        $("#filterDealer").val("all").trigger("change");
    });
});
