import Request from "RequestModule";
import Reusebase from "ReusebaseModule";

$(document).ready(function () {
    const apiRequest = new Request();
    const reusebase = new Reusebase();

    // ==========================================
    // 1. Fetch & Pagination Logic
    // ==========================================
    
    const fetchPricings = async (page = 1) => {
        let searchQuery = $("#searchPricing").val() || "";
        let productQuery = $("#productFilter").val() || "";
        let statusQuery = $("#statusFilter").val() || "";
        let minPriceQuery = $("#minPriceFilter").val() || "";
        let maxPriceQuery = $("#maxPriceFilter").val() || "";

        $("#pricingTableContainer").html(`
            <div class="text-center py-5 text-muted">
                <i class="fas fa-spinner fa-spin fs-1 mb-3 text-primary"></i>
                <h5 class="fw-bold">Loading Pricing Data...</h5>
            </div>
        `);

        try {
            let formData = new FormData();
            formData.append("page", page);
            formData.append("search", searchQuery);
            formData.append("product_id", productQuery);
            formData.append("status", statusQuery);
            formData.append("min_price", minPriceQuery);
            formData.append("max_price", maxPriceQuery);

            let response = await apiRequest.formPost(
                formData,
                "/product-pricings/list",
                false,
            );

            if (response.success) {
                $("#pricingTableContainer").html(response.html);
            } else {
                $("#pricingTableContainer").html(
                    response.html ||
                        '<div class="alert alert-danger">Failed to load pricings.</div>',
                );
            }
        } catch (error) {
            console.error("Error rendering pricing list:", error);
            $("#pricingTableContainer").html(
                '<div class="alert alert-danger">An error occurred while rendering the pricings list.</div>',
            );
        }
    };

    // Initial load
    fetchPricings(1);

    // Search functionality with debounce
    let searchTimeout;
    $("#searchPricing, #minPriceFilter, #maxPriceFilter").on("keyup", function () {
        clearTimeout(searchTimeout);

        searchTimeout = setTimeout(() => {
            fetchPricings(1);
        }, 500); // 500ms delay
    });

    // Dropdown filter handlers
    $("#productFilter, #statusFilter").on("change", function () {
        fetchPricings(1);
    });

    // Pagination click handler
    $(document).on("click", ".pagination a", function (e) {
        e.preventDefault();
        let pageUrl = $(this).attr("href");
        let pageNumber = new URL(pageUrl).searchParams.get("page");

        if (pageNumber) {
            fetchPricings(pageNumber);
        }
    });

    // ==========================================
    // 2. Create Pricing Form Logic
    // ==========================================
    $("#createPricingForm").on("submit", async function (e) {
        e.preventDefault();

        let originalText = reusebase.setButtonLoading(
            "#btnSubmitPricing",
            "Saving...",
        );

        try {
            let actionUrl = $(this).attr("action");
            let formData = new FormData(this);

            let response = await apiRequest.formPost(
                formData,
                actionUrl,
                false,
            );

            if (response.success) {
                $("#createPricingModal").modal("hide");
                $("#createPricingForm")[0].reset();

                Swal.fire({
                    icon: "success",
                    title: "Pricing Created!",
                    text:
                        response.message ||
                        "The product pricing was successfully added.",
                    showConfirmButton: false,
                    timer: 2000,
                }).then(() => {
                    fetchPricings(1);
                });
            } else {
                Swal.fire({
                    icon: "error",
                    title: "Creation Failed",
                    text:
                        response.message ||
                        "Could not create pricing. Please check the inputs.",
                });
            }
        } catch (error) {
            console.error("Pricing creation error:", error);
            Swal.fire({
                icon: "error",
                title: "Network Error",
                text: "A network error occurred while submitting the form.",
            });
        } finally {
            reusebase.resetButtonLoading("#btnSubmitPricing", originalText);
        }
    });

    // ==========================================
    // 3. Edit Pricing Logic
    // ==========================================

    $(document).on("click", ".btn-edit-pricing", async function (e) {
        e.preventDefault();
        let pricingId = $(this).data("id");
        let btn = $(this);

        let originalHtml = btn.html();
        reusebase.setButtonLoading(
            btn,
            '<i class="fas fa-spinner fa-spin"></i>',
        );

        try {
            let response = await apiRequest.formGet(
                {},
                `/product-pricings/${pricingId}/edit`,
                false,
            );

            if (response.success && response.pricing) {
                // Populate the form
                $("#edit_pricing_id").val(response.pricing.id);
                $("#edit_product_id").val(response.pricing.product_id);
                $("#edit_price_per_mt").val(response.pricing.price_per_mt);
                $("#edit_price_type").val(response.pricing.price_type);
                $("#edit_gst_percentage").val(response.pricing.gst_percentage);
                $("#edit_discount_amount").val(response.pricing.discount_amount);
                $("#edit_status").val(response.pricing.status);

                // Set form action URL dynamically
                $("#editPricingForm").attr(
                    "action",
                    `/product-pricings/${pricingId}/update`,
                );

                $("#editPricingModal").modal("show");
            } else {
                Swal.fire({
                    icon: "error",
                    title: "Fetch Failed",
                    text: response.message || "Could not fetch pricing details.",
                });
            }
        } catch (error) {
            console.error("Error fetching pricing:", error);
            Swal.fire({
                icon: "error",
                title: "Network Error",
                text: "Failed to communicate with the server.",
            });
        } finally {
            reusebase.resetButtonLoading(btn, originalHtml);
        }
    });

    // Handle submitting the edit form
    $("#editPricingForm").on("submit", async function (e) {
        e.preventDefault();

        let originalText = reusebase.setButtonLoading(
            "#btnUpdatePricing",
            "Updating...",
        );

        try {
            let actionUrl = $(this).attr("action");
            let formData = new FormData(this);

            let response = await apiRequest.formPost(
                formData,
                actionUrl,
                false,
            );

            if (response.success) {
                $("#editPricingModal").modal("hide");

                Swal.fire({
                    icon: "success",
                    title: "Pricing Updated!",
                    text:
                        response.message ||
                        "The pricing was successfully updated.",
                    showConfirmButton: false,
                    timer: 2000,
                }).then(() => {
                    fetchPricings(1);
                });
            } else {
                Swal.fire({
                    icon: "error",
                    title: "Update Failed",
                    text:
                        response.message ||
                        "Could not update pricing. Please check the inputs.",
                });
            }
        } catch (error) {
            console.error("Pricing update error:", error);
            Swal.fire({
                icon: "error",
                title: "Network Error",
                text: "A network error occurred while submitting the form.",
            });
        } finally {
            reusebase.resetButtonLoading("#btnUpdatePricing", originalText);
        }
    });
});
