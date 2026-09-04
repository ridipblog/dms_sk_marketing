import Request from "RequestModule";
import Reusebase from "ReusebaseModule";
import ReuseInvoice from "ReuseInvoiceModule";

$(document).ready(function () {
    const apiRequest = new Request();
    const reusebase = new Reusebase();
    const reuseInvoice = new ReuseInvoice(apiRequest, reusebase);

    // Initialize Select2
    reusebase.initSelect2("#buyer_id", "Choose Buyer...");
    reusebase.initSelect2("#ship_to", "Choose Ship To...");
    reusebase.initSelect2("#product_pricing_id", "Choose Product...");

    // Calculate Rate fields when Product, Custom Price, Quantity, or Invoice GST is changed
    $(document).on("change keyup input", "#product_pricing_id, #custom_price, #quantity, #gst", function () {
        try {
            let selectedOption = $("#product_pricing_id").find("option:selected");
            let customPriceInput = $("#custom_price").val();
            let price = (customPriceInput !== "" && !isNaN(customPriceInput))
                ? parseFloat(customPriceInput)
                : 0;
            let headerGst = parseFloat($("#gst").val());
            let gst = !isNaN(headerGst) ? headerGst : (parseFloat(selectedOption.data("gst")) || 18);
            let qty = parseFloat($("#quantity").val());
            let stock = parseFloat(selectedOption.data("stock")) || 0;

            // Remove existing warnings if any
            $("#stock-warning").remove();

            if (selectedOption.val() && qty > 0) {
                if (qty > stock) {
                    $(
                        "<div id='stock-warning' class='text-danger fw-bold mt-1' style='font-size: 0.825rem;'><i class='fas fa-exclamation-triangle me-1'></i>Stock is low! Only " +
                            stock.toFixed(3) +
                            " MT available.</div>",
                    ).insertAfter("#quantity");
                }
            }

            if (price > 0 && qty > 0) {
                let baseTotal = price * qty;
                let totalWithGst = baseTotal * (1 + gst / 100);

                $("#rate_exclude_tax").val(baseTotal.toFixed(2));
                $("#rate_include_tax").val(totalWithGst.toFixed(2));
            } else {
                $("#rate_exclude_tax").val("");
                $("#rate_include_tax").val("");
            }
        } catch (error) {
            console.error("Error calculating rates: ", error);
        }
    });

    // Handle dynamic calculation for Edit Items
    $(document).on(
        "change keyup input",
        ".edit-product-id, .edit-price, .edit-quantity, #gst",
        function () {
            try {
                let form = $(this).closest("form");
                let selectedOption = form
                    .find(".edit-product-id")
                    .find("option:selected");
                let customPriceInput = form.find(".edit-price").val();
                let price = (customPriceInput !== "" && !isNaN(customPriceInput))
                    ? parseFloat(customPriceInput)
                    : 0;
                let headerGst = parseFloat($("#gst").val());
                let gst = !isNaN(headerGst) ? headerGst : (parseFloat(selectedOption.data("gst")) || 18);
                let qty = parseFloat(form.find(".edit-quantity").val());
                let stock = parseFloat(selectedOption.data("stock")) || 0;

                // Remove existing warnings in this form
                form.find(".edit-stock-warning").remove();

                if (selectedOption.val() && qty > 0) {
                    if (qty > stock) {
                        $(
                            "<div class='edit-stock-warning text-danger fw-bold mt-1' style='font-size: 0.825rem;'><i class='fas fa-exclamation-triangle me-1'></i>Stock is low! Only " +
                                stock.toFixed(3) +
                                " MT available.</div>",
                        ).insertAfter(form.find(".edit-quantity"));
                    }
                }

                if (price > 0 && qty > 0) {
                    let baseTotal = price * qty;
                    let totalWithGst = baseTotal * (1 + gst / 100);

                    form.find(".edit-rate-exclude").val(baseTotal.toFixed(2));
                    form.find(".edit-rate-include").val(
                        totalWithGst.toFixed(2),
                    );
                } else {
                    form.find(".edit-rate-exclude").val("");
                    form.find(".edit-rate-include").val("");
                }
            } catch (error) {
                console.error("Error calculating edit rates: ", error);
            }
        },
    );

    // Handle Invoice Form Submission
    $("#generateInvoiceForm").on("submit", async function (e) {
        e.preventDefault();

        try {
            let form = $(this);
            let actionUrl = form.attr("action");
            let formData = new FormData(this);
            let btn = form.find('button[type="submit"]');

            let originalText = reusebase.setButtonLoading(btn, "Saving...");

            try {
                let response = await apiRequest.formPost(
                    formData,
                    actionUrl,
                    false,
                );

                if (response?.success) {
                    await reusebase.showSwal(
                        "success",
                        "Success!",
                        response.message,
                    );
                    if (response.invoice_id) {
                        window.location.href =
                            "/accounts/invoices/generate/" +
                            response.invoice_id;
                    } else {
                        window.location.reload();
                    }
                } else {
                    reusebase.showSwal(
                        "error",
                        "Failed",
                        response?.message ||
                            "An error occurred while saving the invoice.",
                    );
                }
            } catch (error) {
                console.error(error);
                reusebase.showSwal(
                    "error",
                    "Error",
                    "An error occurred while saving the invoice.",
                );
            } finally {
                reusebase.resetButtonLoading(btn, originalText);
            }
        } catch (error) {
            console.error("Invoice form submission error:", error);
            reusebase.showSwal(
                "error",
                "Unexpected Error",
                "An unexpected error occurred. Please try again or contact support.",
            );
        }
    });

    // Handle Add and Edit Invoice Item Submission
    $(document).on(
        "submit",
        "#addInvoiceItemForm, .edit-invoice-item-form",
        async function (e) {
            e.preventDefault();

            try {
                let form = $(this);
                let actionUrl =
                    form.attr("action") || "/accounts/invoices/store-item"; // Fallback
                let formData = new FormData(this);
                let btn = form.find('button[type="submit"]');

                let isAddForm = form.attr("id") === "addInvoiceItemForm";

                // Reusable method handles both text logic and saving original HTML!
                let originalHtml = reusebase.setButtonLoading(
                    btn,
                    isAddForm ? "Saving..." : "",
                );

                try {
                    let response = await apiRequest.formPost(
                        formData,
                        actionUrl,
                        false,
                    );

                    if (response?.success) {
                        if (isAddForm) {
                            // Reset the form fields without clearing the hidden invoice_id
                            form.find("select").val("").trigger("change");
                            form.find('input[type="number"]').val("");
                        }

                        // Fetch the updated items list dynamically!
                        fetchInvoiceItems();
                        reusebase.showToast("success", response.message);
                    } else {
                        reusebase.showSwal(
                            "error",
                            "Failed",
                            response?.message ||
                                "An error occurred while saving the item.",
                        );
                    }
                } catch (error) {
                    console.error(error);
                    reusebase.showSwal(
                        "error",
                        "Error",
                        "An error occurred while saving the item.",
                    );
                } finally {
                    reusebase.resetButtonLoading(btn, originalHtml);
                }
            } catch (error) {
                console.error("Item form submission error:", error);
                reusebase.showSwal(
                    "error",
                    "Unexpected Error",
                    "An unexpected error occurred.",
                );
            }
        },
    );

    // Handle Delete Invoice Item
    $(document).on("click", ".delete-item-btn", async function () {
        let btn = $(this);
        let form = btn.closest("form");
        let invoiceDetailId = form
            .find('input[name="invoice_detail_id"]')
            .val();
        let invoiceId = form.find('input[name="invoice_id"]').val();

        let isConfirmed = await reusebase.confirmAction(
            "Delete Item?",
            "Are you sure you want to remove this item from the invoice? This cannot be undone.",
            "Yes, delete it!",
        );

        if (!isConfirmed) return;

        let originalHtml = reusebase.setButtonLoading(btn, "");

        try {
            let formData = new FormData();
            formData.append("invoice_detail_id", invoiceDetailId);
            formData.append("invoice_id", invoiceId);

            let response = await apiRequest.formPost(
                formData,
                "/accounts/invoices/delete-item",
                false,
            );

            if (response?.success) {
                reusebase.showToast("success", response.message);
                fetchInvoiceItems();
            } else {
                reusebase.showSwal(
                    "error",
                    "Failed",
                    response?.message || "Could not delete item.",
                );
            }
        } catch (error) {
            console.error("Delete item error:", error);
            reusebase.showSwal(
                "error",
                "Error",
                "An unexpected error occurred.",
            );
        } finally {
            reusebase.resetButtonLoading(btn, originalHtml);
        }
    });

    // Handle Refresh Items Button
    $(document).on("click", "#refresh-items-btn", async function () {
        let btn = $(this);
        let originalHtml = reusebase.setButtonLoading(btn, "");

        try {
            await fetchInvoiceItems();
            reusebase.showToast("success", "Items list refreshed!");
        } catch (error) {
            console.error("Refresh items error:", error);
            reusebase.showToast("error", "Failed to refresh items.");
        } finally {
            reusebase.resetButtonLoading(btn, originalHtml);
        }
    });

    // Fetch Dynamic Items List
    async function fetchInvoiceItems() {
        let container = $("#invoice-items-container");
        let invoiceId = $('#addInvoiceItemForm input[name="invoice_id"]').val();

        if (!invoiceId) return;

        container.html(
            '<div class="text-center p-4"><i class="fas fa-spinner fa-spin fa-2x text-primary"></i></div>',
        );

        try {
            let formData = new FormData();
            formData.append("invoice_id", invoiceId);

            let response = await apiRequest.formPost(
                formData,
                "/accounts/invoices/fetch-items",
                false,
            );

            if (response?.success) {
                container.html(response.html);

                // Reinitialize select2 for the dynamic elements if needed
                container.find(".edit-product-id").each(function () {
                    reusebase.initSelect2(this, "Choose Product...");
                });
            } else {
                container.html(
                    '<div class="alert alert-danger">Failed to load items.</div>',
                );
            }
        } catch (error) {
            console.error("Fetch items error:", error);
            container.html(
                '<div class="alert alert-danger">Error loading items. Please refresh the page.</div>',
            );
        }
    }

    // Call fetch on initial page load if editing an invoice
    fetchInvoiceItems();
    // Generate Invoice Click Handler
    $(document).on("click", ".generate-invoice-btn", async function () {
        let btn = $(this);
        let invoice_id = btn.data("id");
        if (!invoice_id) return;

        // Ensure finalizeInvoiceUrl is defined globally in the blade file
        if (typeof finalizeInvoiceUrl === "undefined") {
            console.error("finalizeInvoiceUrl is not defined");
            return;
        }

        await reuseInvoice.generate(btn, invoice_id, finalizeInvoiceUrl);
    });
});
