import Request from "RequestModule";
import Reusebase from "ReusebaseModule";

$(document).ready(function () {
    const apiRequest = new Request();
    const reusebase = new Reusebase();

    // ==========================================
    // 1. Fetch & Pagination Logic
    // ==========================================
    
    const fetchProducts = async (page = 1) => {
        let searchQuery = $("#searchProduct").val() || "";
        let categoryQuery = $("#categoryFilter").val() || "";
        let statusQuery = $("#statusFilter").val() || "";
        let minPriceQuery = $("#minPriceFilter").val() || "";
        let maxPriceQuery = $("#maxPriceFilter").val() || "";

        $("#productTableContainer").html(`
            <div class="text-center py-5 text-muted">
                <i class="fas fa-spinner fa-spin fs-1 mb-3 text-primary"></i>
                <h5 class="fw-bold">Loading Products...</h5>
            </div>
        `);

        try {
            let formData = new FormData();
            formData.append("page", page);
            formData.append("search", searchQuery);
            formData.append("category", categoryQuery);
            formData.append("status", statusQuery);
            formData.append("min_price", minPriceQuery);
            formData.append("max_price", maxPriceQuery);

            let response = await apiRequest.formPost(
                formData,
                "/products/list",
                false,
            );

            if (response.success) {
                $("#productTableContainer").html(response.html);
            } else {
                $("#productTableContainer").html(
                    response.html ||
                        '<div class="alert alert-danger">Failed to load products.</div>',
                );
            }
        } catch (error) {
            console.error("Error rendering product list:", error);
            $("#productTableContainer").html(
                '<div class="alert alert-danger">An error occurred while rendering the products list.</div>',
            );
        }
    };

    // Initial load
    fetchProducts(1);

    // Search functionality with debounce
    let searchTimeout;
    $("#searchProduct, #minPriceFilter, #maxPriceFilter").on("keyup", function () {
        clearTimeout(searchTimeout);

        searchTimeout = setTimeout(() => {
            fetchProducts(1);
        }, 500); // 500ms delay
    });

    // Dropdown filter handlers
    $("#categoryFilter, #statusFilter").on("change", function () {
        fetchProducts(1);
    });

    // Pagination click handler
    $(document).on("click", ".pagination a", function (e) {
        e.preventDefault();
        let pageUrl = $(this).attr("href");
        let pageNumber = new URL(pageUrl).searchParams.get("page");

        if (pageNumber) {
            fetchProducts(pageNumber);
        }
    });

    // ==========================================
    // 2. Create Product Form Logic
    // ==========================================
    $("#createProductForm").on("submit", async function (e) {
        e.preventDefault();

        let originalText = reusebase.setButtonLoading(
            "#btnSubmitProduct",
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
                $("#createProductModal").modal("hide");
                $("#createProductForm")[0].reset();

                Swal.fire({
                    icon: "success",
                    title: "Product Created!",
                    text:
                        response.message ||
                        "The product was successfully added.",
                    showConfirmButton: false,
                    timer: 2000,
                }).then(() => {
                    fetchProducts(1);
                });
            } else {
                Swal.fire({
                    icon: "error",
                    title: "Creation Failed",
                    text:
                        response.message ||
                        "Could not create product. Please check the inputs.",
                });
            }
        } catch (error) {
            console.error("Product creation error:", error);
            Swal.fire({
                icon: "error",
                title: "Network Error",
                text: "A network error occurred while submitting the form.",
            });
        } finally {
            reusebase.resetButtonLoading("#btnSubmitProduct", originalText);
        }
    });

    // ==========================================
    // 3. Edit Product Logic
    // ==========================================

    $(document).on("click", ".btn-edit-product", async function (e) {
        e.preventDefault();
        let productId = $(this).data("id");
        let btn = $(this);

        let originalHtml = btn.html();
        reusebase.setButtonLoading(
            btn,
            '<i class="fas fa-spinner fa-spin"></i>',
        );

        try {
            let response = await apiRequest.formGet(
                {},
                `/products/${productId}/edit`,
                false,
            );

            if (response.success && response.product) {
                // Populate the form
                $("#edit_product_id").val(response.product.id);
                $("#edit_category_id").val(response.product.category_id);
                $("#edit_product_name").val(response.product.product_name);
                $("#edit_sku_code").val(response.product.sku_code);
                $("#edit_hsn_code").val(response.product.hsn_code);
                $("#edit_size").val(response.product.size);
                $("#edit_unit").val(response.product.unit);
                $("#edit_base_price").val(response.product.base_price);
                $("#edit_status").val(response.product.status);

                // Set form action URL dynamically
                $("#editProductForm").attr(
                    "action",
                    `/products/${productId}/update`,
                );

                $("#editProductModal").modal("show");
            } else {
                Swal.fire({
                    icon: "error",
                    title: "Fetch Failed",
                    text: response.message || "Could not fetch product details.",
                });
            }
        } catch (error) {
            console.error("Error fetching product:", error);
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
    $("#editProductForm").on("submit", async function (e) {
        e.preventDefault();

        let originalText = reusebase.setButtonLoading(
            "#btnUpdateProduct",
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
                $("#editProductModal").modal("hide");

                Swal.fire({
                    icon: "success",
                    title: "Product Updated!",
                    text:
                        response.message ||
                        "The product was successfully updated.",
                    showConfirmButton: false,
                    timer: 2000,
                }).then(() => {
                    fetchProducts(1);
                });
            } else {
                Swal.fire({
                    icon: "error",
                    title: "Update Failed",
                    text:
                        response.message ||
                        "Could not update product. Please check the inputs.",
                });
            }
        } catch (error) {
            console.error("Product update error:", error);
            Swal.fire({
                icon: "error",
                title: "Network Error",
                text: "A network error occurred while submitting the form.",
            });
        } finally {
            reusebase.resetButtonLoading("#btnUpdateProduct", originalText);
        }
    });

    // ==========================================
    // 4. Export Products Logic
    // ==========================================
    $("#btnExportProducts").on("click", function (e) {
        e.preventDefault();
        let search = $("#searchProduct").val() || "";
        let category = $("#categoryFilter").val() || "";
        let status = $("#statusFilter").val() || "";
        let minPrice = $("#minPriceFilter").val() || "";
        let maxPrice = $("#maxPriceFilter").val() || "";

        let params = new URLSearchParams({
            search: search,
            category: category,
            status: status,
            min_price: minPrice,
            max_price: maxPrice
        });

        window.location.href = `/products/export?${params.toString()}`;
    });
});
