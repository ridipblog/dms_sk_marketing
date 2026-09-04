import Request from "RequestModule";
import Reusebase from "ReusebaseModule";

$(document).ready(function () {
    const apiRequest = new Request();
    const reusebase = new Reusebase();

    // Initialize Select2 search dropdowns
    reusebase.initSelect2("#supplier_id", "Choose Supplier...");
    reusebase.initSelect2("#product_id", "Choose Product...");

    const purchaseIdEnc = $('#generatePurchaseForm input[name="purchase_id"]').val() || "";

    // If editing, load the draft items automatically
    if (purchaseIdEnc) {
        fetchPurchaseItems();
    }

    // Select product change updates totals and rate
    $("#product_id").on("change", function () {
        let selectedOption = $(this).find("option:selected");
        let price = parseFloat(selectedOption.data("price")) || 0;
        if (price > 0 && !$("#rate").val()) {
            $("#rate").val(price);
        }
        calculateTotalAmount();
    });

    // Quantity or rate typing updates display amount
    $("#quantity, #rate").on("keyup change", function () {
        calculateTotalAmount();
    });

    function calculateTotalAmount() {
        let qty = parseFloat($("#quantity").val()) || 0;
        let rate = parseFloat($("#rate").val()) || 0;
        $("#amount_display").val((qty * rate).toFixed(2));
    }

    // Save Header Form
    $("#generatePurchaseForm").submit(async function (e) {
        e.preventDefault();
        let form = $(this);
        let actionUrl = form.attr("action");
        let btn = form.find('button[type="submit"]');

        let originalText = reusebase.setButtonLoading(btn, "Saving Draft...");

        try {
            let formData = new FormData(this);
            let response = await apiRequest.formPost(formData, actionUrl, false);

            if (response?.success) {
                await reusebase.showSwal("success", "Success!", response.message);
                if (response.purchase_id) {
                    window.location.href = "/purchase/invoices/generate/" + response.purchase_id;
                } else {
                    window.location.reload();
                }
            } else {
                reusebase.showSwal("error", "Failed", response?.message || "Failed to save draft.");
            }
        } catch (error) {
            console.error(error);
            reusebase.showSwal("error", "Error", "An unexpected error occurred.");
        } finally {
            reusebase.resetButtonLoading(btn, originalText);
        }
    });

    // Add Line Item Form
    $("#addPurchaseItemForm").submit(async function (e) {
        e.preventDefault();
        let form = $(this);
        let actionUrl = form.attr("action");
        let btn = form.find('button[type="submit"]');

        let originalText = reusebase.setButtonLoading(btn, "Adding...");

        try {
            let formData = new FormData(this);
            let response = await apiRequest.formPost(formData, actionUrl, false);

            if (response?.success) {
                form.trigger("reset");
                $("#product_id").val("").trigger("change");
                $("#amount_display").val("");
                fetchPurchaseItems();
                reusebase.showToast("success", response.message);
            } else {
                reusebase.showSwal("error", "Failed", response?.message || "Failed to add item.");
            }
        } catch (error) {
            console.error(error);
            reusebase.showSwal("error", "Error", "An unexpected error occurred.");
        } finally {
            reusebase.resetButtonLoading(btn, originalText);
        }
    });

    // Edit line item updates
    $(document).on("submit", ".edit-purchase-item-form", async function (e) {
        e.preventDefault();
        let form = $(this);
        let actionUrl = form.attr("action");
        let btn = form.find('button[type="submit"]');

        let originalHtml = reusebase.setButtonLoading(btn, "");

        try {
            let formData = new FormData(this);
            let response = await apiRequest.formPost(formData, actionUrl, false);

            if (response?.success) {
                reusebase.showToast("success", response.message);
                fetchPurchaseItems();
            } else {
                reusebase.showSwal("error", "Failed", response?.message || "Failed to update item.");
            }
        } catch (error) {
            console.error(error);
            reusebase.showSwal("error", "Error", "An unexpected error occurred.");
        } finally {
            reusebase.resetButtonLoading(btn, originalHtml);
        }
    });

    // Delete item action
    $(document).on("click", ".delete-item-btn", async function () {
        let btn = $(this);
        let detailId = btn.data("detail-id");

        let isConfirmed = await reusebase.confirmAction(
            "Delete Item?",
            "Are you sure you want to delete this item? This cannot be undone.",
            "Yes, delete it!"
        );

        if (!isConfirmed) return;

        let originalHtml = reusebase.setButtonLoading(btn, "");

        try {
            let formData = new FormData();
            formData.append("purchase_id", purchaseIdEnc);
            formData.append("purchase_detail_id", detailId);

            let response = await apiRequest.formPost(formData, "/purchase/invoices/delete-item", false);

            if (response?.success) {
                reusebase.showToast("success", response.message);
                fetchPurchaseItems();
            } else {
                reusebase.showSwal("error", "Failed", response?.message || "Failed to delete item.");
            }
        } catch (error) {
            console.error(error);
            reusebase.showSwal("error", "Error", "An unexpected error occurred.");
        } finally {
            reusebase.resetButtonLoading(btn, originalHtml);
        }
    });

    // Refresh items button
    $(document).on("click", "#refresh-items-btn", async function () {
        let btn = $(this);
        let originalHtml = reusebase.setButtonLoading(btn, "");

        try {
            await fetchPurchaseItems();
            reusebase.showToast("success", "Items refreshed!");
        } catch (error) {
            console.error(error);
            reusebase.showToast("error", "Failed to refresh items.");
        } finally {
            reusebase.resetButtonLoading(btn, originalHtml);
        }
    });

    // Load added items function
    async function fetchPurchaseItems() {
        let container = $("#purchase-items-container");
        if (!purchaseIdEnc) return;

        container.html(
            '<div class="text-center p-4"><i class="fas fa-spinner fa-spin fa-2x text-success"></i></div>'
        );

        try {
            let formData = new FormData();
            formData.append("purchase_id", purchaseIdEnc);

            let response = await apiRequest.formPost(formData, "/purchase/invoices/fetch-items", false);

            if (response?.success) {
                container.html(response.html);

                // Initialize select2 for dynamic edit product dropdowns
                container.find(".edit-product-id").each(function () {
                    reusebase.initSelect2(this, "Choose Product...");
                });

                // Bind product change event on edit rows
                container.find(".edit-product-id").on("change", function () {
                    let selectedOption = $(this).find("option:selected");
                    let price = parseFloat(selectedOption.data("price")) || 0;
                    let rowForm = $(this).closest("form");
                    if (price > 0) {
                        rowForm.find(".edit-rate").val(price);
                    }
                    let qty = parseFloat(rowForm.find(".edit-quantity").val()) || 0;
                    let rate = parseFloat(rowForm.find(".edit-rate").val()) || 0;
                    rowForm.find(".edit-amount-exclude").val((qty * rate).toFixed(2));
                });

                // Bind change calculate events to inline edit rows
                container.find(".edit-quantity, .edit-rate").on("keyup change", function () {
                    let rowForm = $(this).closest("form");
                    let qty = parseFloat(rowForm.find(".edit-quantity").val()) || 0;
                    let rate = parseFloat(rowForm.find(".edit-rate").val()) || 0;
                    rowForm.find(".edit-amount-exclude").val((qty * rate).toFixed(2));
                });
            } else {
                container.html('<div class="alert alert-danger">Failed to load items.</div>');
            }
        } catch (error) {
            console.error(error);
            container.html('<div class="alert alert-danger">Error loading items.</div>');
        }
    }

    // Finalize Bill
    $("#btnFinalizePurchase").click(async function () {
        let btn = $(this);
        let isConfirmed = await reusebase.confirmAction(
            "Finalize Purchase?",
            "Are you sure you want to finalize this purchase invoice? This will increase stock counts and lock the document.",
            "Yes, finalize it!"
        );

        if (!isConfirmed) return;

        let originalHtml = reusebase.setButtonLoading(btn, "Finalizing...");

        try {
            let formData = new FormData();
            formData.append("purchase_id", purchaseIdEnc);

            let response = await apiRequest.formPost(formData, "/purchase/invoices/finalize", false);

            if (response?.success) {
                await reusebase.showSwal("success", "Finalized!", response.message);
                if (response.redirect_url) {
                    window.location.href = response.redirect_url;
                } else {
                    window.location.reload();
                }
            } else {
                reusebase.showSwal("error", "Error", response?.message || "Failed to finalize bill.");
            }
        } catch (error) {
            console.error(error);
            reusebase.showSwal("error", "Error", "An unexpected error occurred during finalization.");
        } finally {
            reusebase.resetButtonLoading(btn, originalHtml);
        }
    });
});
