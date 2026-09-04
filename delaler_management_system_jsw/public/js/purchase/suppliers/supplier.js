import Request from "RequestModule";
import Reusebase from "ReusebaseModule";

$(document).ready(function () {
    const apiRequest = new Request();
    const reusebase = new Reusebase();

    // Enforce 10-digit limit on the mobile number inputs
    reusebase.preventMobileMaxLength("#phone", 10);
    reusebase.preventMobileMaxLength("#edit_phone", 10);

    const fetchSuppliers = async (page = 1) => {
        let searchQuery = $("#searchSupplier").val() || "";
        let status = $("#statusFilterSelect").val() || "";

        $("#supplierTableContainer").html(reusebase.getLoadingHtml());

        try {
            let formData = new FormData();
            formData.append("page", page);
            formData.append("search", searchQuery);
            formData.append("status", status);

            let response = await apiRequest.formPost(
                formData,
                "/purchase/suppliers/list",
                false
            );

            if (response?.success) {
                $("#supplierTableContainer").html(response.html);
            } else {
                reusebase.showSwal(
                    "error",
                    "Error",
                    response?.message || "Failed to fetch suppliers"
                );
                $("#supplierTableContainer").html(
                    reusebase.getErrorHtml("Failed to load suppliers. Please try again.", "fas fa-exclamation-circle")
                );
            }
        } catch (error) {
            console.error(error);
            reusebase.showSwal(
                "error",
                "Error",
                "An unexpected error occurred while fetching suppliers."
            );
            $("#supplierTableContainer").html(
                reusebase.getErrorHtml("Unexpected error. Please try again.", "fas fa-exclamation-triangle")
            );
        }
    };

    // Initial load
    fetchSuppliers();

    // Refresh and filters
    $("#btnRefreshSuppliers").click(function () {
        $("#searchSupplier").val("");
        $("#statusFilterSelect").val("");
        fetchSuppliers();
    });

    $("#statusFilterSelect").change(function () {
        fetchSuppliers();
    });

    $("#searchSupplier").on(
        "keyup",
        reusebase.debounce(function () {
            fetchSuppliers();
        }, 500)
    );

    $(document).on("click", ".pagination a", function (e) {
        e.preventDefault();
        let page = $(this).attr("href").split("page=")[1];
        fetchSuppliers(page);
    });

    // Form submit for registering
    $("#addSupplierForm").submit(async function (e) {
        e.preventDefault();
        let form = $(this);
        let actionUrl = form.attr("action");
        let btn = form.find('button[type="submit"]');

        let originalText = reusebase.setButtonLoading(btn, "Registering...");

        try {
            let formData = new FormData(this);
            let response = await apiRequest.formPost(formData, actionUrl, false);

            if (response?.success) {
                $("#addSupplierModal").modal("hide");
                form.trigger("reset");
                await reusebase.showSwal("success", "Registered!", response.message);
                fetchSuppliers();
            } else {
                reusebase.showSwal(
                    "error",
                    "Failed",
                    response?.message || "Failed to register supplier."
                );
            }
        } catch (error) {
            console.error(error);
            reusebase.showSwal("error", "Error", "An unexpected error occurred.");
        } finally {
            reusebase.resetButtonLoading(btn, originalText);
        }
    });

    // Open Edit Modal
    let currentEditUrl = "";
    $(document).on("click", ".edit-supplier-btn", async function () {
        let encId = $(this).data("id");
        let editUrl = "/purchase/suppliers/" + encId + "/edit";
        currentEditUrl = "/purchase/suppliers/" + encId + "/update";

        try {
            let response = await apiRequest.formGet({}, editUrl, false);
            if (response?.success) {
                $("#edit_name").val(response.supplier.name);
                $("#edit_gstin").val(response.supplier.gstin);
                $("#edit_phone").val(response.supplier.phone);
                $("#edit_email").val(response.supplier.email);
                $("#edit_address").val(response.supplier.address);
                $("#edit_status").val(response.supplier.status);
                $("#editSupplierModal").modal("show");
            } else {
                reusebase.showSwal("error", "Error", response?.message || "Supplier not found.");
            }
        } catch (error) {
            console.error(error);
            reusebase.showSwal("error", "Error", "Failed to load supplier details.");
        }
    });

    // Save Edit Form
    $("#editSupplierForm").submit(async function (e) {
        e.preventDefault();
        let form = $(this);
        let btn = form.find('button[type="submit"]');

        let originalText = reusebase.setButtonLoading(btn, "Saving...");

        try {
            let formData = new FormData(this);
            let response = await apiRequest.formPost(formData, currentEditUrl, false);

            if (response?.success) {
                $("#editSupplierModal").modal("hide");
                await reusebase.showSwal("success", "Saved!", response.message);
                fetchSuppliers();
            } else {
                reusebase.showSwal("error", "Failed", response?.message || "Failed to update supplier.");
            }
        } catch (error) {
            console.error(error);
            reusebase.showSwal("error", "Error", "An unexpected error occurred.");
        } finally {
            reusebase.resetButtonLoading(btn, originalText);
        }
    });
});
