import Request from "RequestModule";
import Reusebase from "ReusebaseModule";

$(document).ready(function () {
    const apiRequest = new Request();
    const reusebase = new Reusebase();

    // ==========================================
    // 1. Fetch & Pagination Logic
    // ==========================================
    
    const fetchCategories = async (page = 1) => {
        let searchQuery = $("#searchCategory").val() || "";
        let statusQuery = $("#statusFilter").val() || "";

        $("#categoryTableContainer").html(`
            <div class="text-center py-5 text-muted">
                <i class="fas fa-spinner fa-spin fs-1 mb-3 text-primary"></i>
                <h5 class="fw-bold">Loading Categories...</h5>
            </div>
        `);

        try {
            let formData = new FormData();
            formData.append("page", page);
            formData.append("search", searchQuery);
            formData.append("status", statusQuery);

            let response = await apiRequest.formPost(
                formData,
                "/categories/list",
                false,
            );

            if (response.success) {
                $("#categoryTableContainer").html(response.html);
            } else {
                $("#categoryTableContainer").html(
                    response.html ||
                        '<div class="alert alert-danger">Failed to load categories.</div>',
                );
            }
        } catch (error) {
            console.error("Error rendering category list:", error);
            $("#categoryTableContainer").html(
                '<div class="alert alert-danger">An error occurred while rendering the categories list.</div>',
            );
        }
    };

    // Initial load
    fetchCategories(1);

    // Search functionality with debounce
    let searchTimeout;
    $("#searchCategory").on("keyup", function () {
        clearTimeout(searchTimeout);

        searchTimeout = setTimeout(() => {
            fetchCategories(1);
        }, 500); // 500ms delay
    });

    // Status filter handler
    $("#statusFilter").on("change", function () {
        fetchCategories(1);
    });

    // Pagination click handler
    $(document).on("click", ".pagination a", function (e) {
        e.preventDefault();
        let pageUrl = $(this).attr("href");
        let pageNumber = new URL(pageUrl).searchParams.get("page");

        if (pageNumber) {
            fetchCategories(pageNumber);
        }
    });

    // ==========================================
    // 2. Create Category Form Logic
    // ==========================================
    $("#createCategoryForm").on("submit", async function (e) {
        e.preventDefault();

        let originalText = reusebase.setButtonLoading(
            "#btnSubmitCategory",
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
                $("#createCategoryModal").modal("hide");
                $("#createCategoryForm")[0].reset();

                Swal.fire({
                    icon: "success",
                    title: "Category Created!",
                    text:
                        response.message ||
                        "The category was successfully added.",
                    showConfirmButton: false,
                    timer: 2000,
                }).then(() => {
                    fetchCategories(1);
                });
            } else {
                Swal.fire({
                    icon: "error",
                    title: "Creation Failed",
                    text:
                        response.message ||
                        "Could not create category. Please check the inputs.",
                });
            }
        } catch (error) {
            console.error("Category creation error:", error);
            Swal.fire({
                icon: "error",
                title: "Network Error",
                text: "A network error occurred while submitting the form.",
            });
        } finally {
            reusebase.resetButtonLoading("#btnSubmitCategory", originalText);
        }
    });

    // ==========================================
    // 3. Edit Category Logic
    // ==========================================

    $(document).on("click", ".btn-edit-category", async function (e) {
        e.preventDefault();
        let categoryId = $(this).data("id");
        let btn = $(this);

        let originalHtml = btn.html();
        reusebase.setButtonLoading(
            btn,
            '<i class="fas fa-spinner fa-spin"></i>',
        );

        try {
            let response = await apiRequest.formGet(
                {},
                `/categories/${categoryId}/edit`,
                false,
            );

            if (response.success && response.category) {
                // Populate the form
                $("#edit_category_id").val(response.category.id);
                $("#edit_category_name").val(response.category.category_name);
                $("#edit_category_code").val(response.category.category_code);
                $("#edit_status").val(response.category.status);
                $("#edit_description").val(response.category.description);

                // Set form action URL dynamically
                $("#editCategoryForm").attr(
                    "action",
                    `/categories/${categoryId}/update`,
                );

                $("#editCategoryModal").modal("show");
            } else {
                Swal.fire({
                    icon: "error",
                    title: "Fetch Failed",
                    text: response.message || "Could not fetch category details.",
                });
            }
        } catch (error) {
            console.error("Error fetching category:", error);
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
    $("#editCategoryForm").on("submit", async function (e) {
        e.preventDefault();

        let originalText = reusebase.setButtonLoading(
            "#btnUpdateCategory",
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
                $("#editCategoryModal").modal("hide");

                Swal.fire({
                    icon: "success",
                    title: "Category Updated!",
                    text:
                        response.message ||
                        "The category was successfully updated.",
                    showConfirmButton: false,
                    timer: 2000,
                }).then(() => {
                    fetchCategories(1);
                });
            } else {
                Swal.fire({
                    icon: "error",
                    title: "Update Failed",
                    text:
                        response.message ||
                        "Could not update category. Please check the inputs.",
                });
            }
        } catch (error) {
            console.error("Category update error:", error);
            Swal.fire({
                icon: "error",
                title: "Network Error",
                text: "A network error occurred while submitting the form.",
            });
        } finally {
            reusebase.resetButtonLoading("#btnUpdateCategory", originalText);
        }
    });
});
