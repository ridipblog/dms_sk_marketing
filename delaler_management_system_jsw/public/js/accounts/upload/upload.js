import RequestModule from "RequestModule";
import Reusebase from "ReusebaseModule";

const listInvoiceUploadsUrl = "/accounts/upload-excel/invoices/list";
const uploadInvoiceUrl = "/accounts/upload-excel/invoices/import";
const listVoucherUploadsUrl = "/accounts/upload-excel/vouchers/list";
const uploadVoucherUrl = "/accounts/upload-excel/vouchers/import";

const apiRequest = new RequestModule();
const reusebase = new Reusebase();

$(document).ready(function () {
    
    // ---- STATE TRACKING TO AVOID RE-FETCHING UNNECESSARILY ----
    let invoicesLoaded = false;
    let vouchersLoaded = false;

    // ---- INVOICES UPLOAD ----
    const $uploadInvoiceForm = $("#uploadInvoiceForm");
    const $btnUploadInvoice = $("#btnUploadInvoice");
    const $btnRefreshInvoiceList = $("#btnRefreshInvoiceList");
    const $searchInvoiceFiles = $("#searchInvoiceFiles");
    const $invoiceUploadsTableContainer = $("#invoiceUploadsTableContainer");

    if ($uploadInvoiceForm.length) {
        $uploadInvoiceForm.on("submit", async function (e) {
            e.preventDefault();
            $btnUploadInvoice.prop("disabled", true).html('<i class="fas fa-spinner fa-spin me-2"></i> Uploading...');

            try {
                const formData = new FormData(this);
                const response = await apiRequest.formPost(formData, uploadInvoiceUrl, false);

                if (response?.success) {
                    reusebase.showSwal("success", "Success", response.message || "File uploaded successfully.");
                    this.reset();
                    fetchInvoiceList();
                } else {
                    reusebase.showSwal("error", "Error", response?.message || "Failed to upload file.");
                }
            } catch (error) {
                console.error("Upload error:", error);
                reusebase.showSwal("error", "Error", "An error occurred during upload.");
            } finally {
                $btnUploadInvoice.prop("disabled", false).html('<i class="fas fa-cloud-upload-alt me-2"></i> Upload & Process');
            }
        });
    }

    if ($btnRefreshInvoiceList.length) {
        $btnRefreshInvoiceList.on("click", function () {
            fetchInvoiceList();
        });
    }

    if ($searchInvoiceFiles.length) {
        $searchInvoiceFiles.on(
            "keyup",
            reusebase.debounce(function () {
                fetchInvoiceList();
            }, 500)
        );
    }

    const fetchInvoiceList = async (page = 1) => {
        if (!$invoiceUploadsTableContainer.length) return;

        const search = $searchInvoiceFiles.val() || "";
        $invoiceUploadsTableContainer.html(reusebase.getLoadingHtml());

        try {
            const formData = new FormData();
            formData.append("page", page);
            formData.append("search", search);

            const response = await apiRequest.formPost(formData, listInvoiceUploadsUrl, false, "GET");

            if (response?.success) {
                $invoiceUploadsTableContainer.html(response.html);
                invoicesLoaded = true;
            } else {
                $invoiceUploadsTableContainer.html(reusebase.getErrorHtml(response?.message || "Failed to load data."));
            }
        } catch (error) {
            console.error(error);
            $invoiceUploadsTableContainer.html(reusebase.getErrorHtml("An error occurred while loading data."));
        }
    };

    $(document).on("click", "#invoiceUploadsTableContainer .pagination a", function (e) {
        e.preventDefault();
        let page = $(this).attr("href").split("page=")[1];
        fetchInvoiceList(page);
    });


    // ---- VOUCHERS UPLOAD ----
    const $uploadVoucherForm = $("#uploadVoucherForm");
    const $btnUploadVoucher = $("#btnUploadVoucher");
    const $btnRefreshVoucherList = $("#btnRefreshVoucherList");
    const $searchVoucherFiles = $("#searchVoucherFiles");
    const $voucherUploadsTableContainer = $("#voucherUploadsTableContainer");

    if ($uploadVoucherForm.length) {
        $uploadVoucherForm.on("submit", async function (e) {
            e.preventDefault();
            $btnUploadVoucher.prop("disabled", true).html('<i class="fas fa-spinner fa-spin me-2"></i> Uploading...');

            try {
                const formData = new FormData(this);
                const response = await apiRequest.formPost(formData, uploadVoucherUrl, false);

                if (response?.success) {
                    reusebase.showSwal("success", "Success", response.message || "File uploaded successfully.");
                    this.reset();
                    fetchVoucherList();
                } else {
                    reusebase.showSwal("error", "Error", response?.message || "Failed to upload file.");
                }
            } catch (error) {
                console.error("Upload error:", error);
                reusebase.showSwal("error", "Error", "An error occurred during upload.");
            } finally {
                $btnUploadVoucher.prop("disabled", false).html('<i class="fas fa-cloud-upload-alt me-2"></i> Upload & Process');
            }
        });
    }

    if ($btnRefreshVoucherList.length) {
        $btnRefreshVoucherList.on("click", function () {
            fetchVoucherList();
        });
    }

    if ($searchVoucherFiles.length) {
        $searchVoucherFiles.on(
            "keyup",
            reusebase.debounce(function () {
                fetchVoucherList();
            }, 500)
        );
    }

    const fetchVoucherList = async (page = 1) => {
        if (!$voucherUploadsTableContainer.length) return;

        const search = $searchVoucherFiles.val() || "";
        $voucherUploadsTableContainer.html(reusebase.getLoadingHtml());

        try {
            const formData = new FormData();
            formData.append("page", page);
            formData.append("search", search);

            const response = await apiRequest.formPost(formData, listVoucherUploadsUrl, false, "GET");

            if (response?.success) {
                $voucherUploadsTableContainer.html(response.html);
                vouchersLoaded = true;
            } else {
                $voucherUploadsTableContainer.html(reusebase.getErrorHtml(response?.message || "Failed to load data."));
            }
        } catch (error) {
            console.error(error);
            $voucherUploadsTableContainer.html(reusebase.getErrorHtml("An error occurred while loading data."));
        }
    };

    $(document).on("click", "#voucherUploadsTableContainer .pagination a", function (e) {
        e.preventDefault();
        let page = $(this).attr("href").split("page=")[1];
        fetchVoucherList(page);
    });

    // ---- TAB CLICK EVENT LISTENERS ----
    // Load initial active tab data
    if ($("#invoices-tab").hasClass("active")) {
        fetchInvoiceList();
    } else if ($("#vouchers-tab").hasClass("active")) {
        fetchVoucherList();
    }

    // Refresh when tabs are clicked/shown
    $('#invoices-tab').on('shown.bs.tab', function (e) {
        fetchInvoiceList();
    });

    $('#vouchers-tab').on('shown.bs.tab', function (e) {
        fetchVoucherList();
    });

});
