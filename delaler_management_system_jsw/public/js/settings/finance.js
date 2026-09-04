import Request from "RequestModule";

$(document).ready(function () {
    const apiRequest = new Request();

    // Live preview update functions
    function updatePreview() {
        const holder = $("#account_holder_name").val().trim() || "N/A";
        const bank = $("#bank_name").val().trim() || "N/A";
        const acc = $("#account_no").val().trim() || "N/A";
        const ifsc = $("#ifsc_code").val().trim().toUpperCase() || "N/A";
        const branch = $("#branch_name").val().trim();
        const swift = $("#swift_code").val().trim().toUpperCase();
        const upi = $("#upi_id").val().trim();

        $("#prev_holder").text(holder);
        $("#prev_bank").text(bank);
        $("#prev_acc").text(acc);

        let ifscText = ifsc;
        if (branch) {
            ifscText += " (" + branch + ")";
        }
        $("#prev_ifsc").text(ifscText);

        if (swift) {
            $("#prev_swift").text(swift);
            $("#prev_swift_row").removeClass("d-none");
        } else {
            $("#prev_swift_row").addClass("d-none");
        }

        if (upi) {
            $("#prev_upi").text(upi);
            $("#prev_upi_row").removeClass("d-none");
        } else {
            $("#prev_upi_row").addClass("d-none");
        }
    }

    // Attach live input listeners to form fields
    $(document).on("input change", "#companyBankForm input, #companyBankForm select", function () {
        updatePreview();
    });

    // Form Submit Handler (Add New Bank Details)
    $("#companyBankForm").on("submit", async function (e) {
        e.preventDefault();

        const btn = $("#btnSaveFinance");
        const originalText = btn.html();

        btn.prop("disabled", true).html('<i class="fas fa-spinner fa-spin me-1"></i> Saving...');

        try {
            const formData = new FormData(this);
            const url = $(this).data("url");

            const response = await apiRequest.formPost(formData, url, false);

            btn.prop("disabled", false).html(originalText);

            if (response && response.success) {
                Swal.fire({
                    icon: "success",
                    title: "Success!",
                    text: response.message || "New bank details added successfully.",
                    confirmButtonColor: "#0d6efd",
                    timer: 1500
                }).then(() => {
                    window.location.reload();
                });
            } else {
                Swal.fire({
                    icon: "error",
                    title: "Validation Error",
                    text: (response ? response.message : null) || "Failed to save bank details.",
                    confirmButtonColor: "#dc3545"
                });
            }
        } catch (error) {
            btn.prop("disabled", false).html(originalText);
            console.error("Save Company Bank Error:", error);
            Swal.fire({
                icon: "error",
                title: "Error",
                text: "An unexpected error occurred while saving bank details.",
                confirmButtonColor: "#dc3545"
            });
        }
    });

    // Set Active Bank Account Handler
    $(document).on("click", ".btn-set-active", async function () {
        const id = $(this).data("id");
        const url = $(this).data("url");
        const btn = $(this);

        const result = await Swal.fire({
            title: "Set as Active Bank Account?",
            text: "This bank account will be displayed on all new invoices. All other bank accounts will be set to inactive.",
            icon: "question",
            showCancelButton: true,
            confirmButtonColor: "#0d6efd",
            cancelButtonColor: "#6c757d",
            confirmButtonText: "Yes, Set Active"
        });

        if (!result.isConfirmed) return;

        btn.prop("disabled", true).html('<i class="fas fa-spinner fa-spin"></i>');

        try {
            const formData = new FormData();
            formData.append("bank_detail_id", id);
            formData.append("_token", $('meta[name="csrf-token"]').attr("content") || $('input[name="_token"]').val());

            const response = await apiRequest.formPost(formData, url, false);

            if (response && response.success) {
                Swal.fire({
                    icon: "success",
                    title: "Activated!",
                    text: response.message || "Bank details set as active.",
                    confirmButtonColor: "#0d6efd",
                    timer: 1500
                }).then(() => {
                    window.location.reload();
                });
            } else {
                btn.prop("disabled", false).html('<i class="fas fa-toggle-on me-1"></i> Set Active');
                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: (response ? response.message : null) || "Failed to update active status.",
                    confirmButtonColor: "#dc3545"
                });
            }
        } catch (error) {
            btn.prop("disabled", false).html('<i class="fas fa-toggle-on me-1"></i> Set Active');
            console.error("Set Active Bank Details Error:", error);
            Swal.fire({
                icon: "error",
                title: "Error",
                text: "An unexpected error occurred while setting active status.",
                confirmButtonColor: "#dc3545"
            });
        }
    });
});
