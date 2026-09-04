export default class ReuseInvoice {
    constructor(apiRequest, reusebase) {
        this.apiRequest = apiRequest;
        this.reusebase = reusebase;
    }

    async generate(
        btn,
        invoice_id,
        finalizeInvoiceUrl,
        successCallback = null,
    ) {
        let isConfirmed = await this.reusebase.confirmAction(
            "Generate Invoice?",
            "Are you sure you want to finalize this invoice? You won't be able to edit it afterwards.",
            "Yes, generate it!",
        );

        if (!isConfirmed) return;

        let originalHtml = this.reusebase.setButtonLoading(
            btn,
            "Generating...",
        );
        try {
            let formData = new FormData();
            formData.append("invoice_id", invoice_id);

            let response = await this.apiRequest.formPost(
                formData,
                finalizeInvoiceUrl,
            );
            console.log(response);

            if (response?.prompt_scheme) {
                let confirmScheme = await Swal.fire({
                    title: "Use Scheme Balance?",
                    text: response.message,
                    icon: "question",
                    showCancelButton: true,
                    confirmButtonColor: "#3085d6",
                    cancelButtonColor: "#6c757d",
                    confirmButtonText: "Yes, use as Credit Note",
                    cancelButtonText: "No, skip scheme amount",
                    allowOutsideClick: false
                });

                formData.append("confirm_scheme", "1");
                formData.append("use_scheme", confirmScheme.isConfirmed ? "1" : "0");

                response = await this.apiRequest.formPost(
                    formData,
                    finalizeInvoiceUrl,
                );
            }

            if (response?.success) {
                await this.reusebase.showSwal(
                    "success",
                    "Generated!",
                    response.message,
                );
                if (response.redirect_url) {
                    window.location.href = response.redirect_url;
                } else if (typeof successCallback === "function") {
                    successCallback();
                } else {
                    window.location.reload();
                }
            } else {
                this.reusebase.showSwal(
                    "error",
                    "Error",
                    response?.message || "Failed to generate invoice",
                );
            }
        } catch (error) {
            console.error(error);
            this.reusebase.showSwal(
                "error",
                "Error",
                "An unexpected error occurred while generating the invoice",
            );
        } finally {
            this.reusebase.resetButtonLoading(btn, originalHtml);
        }
    }

    async delete(
        btn,
        invoice_id,
        checkDeleteUrl,
        deleteUrl,
        successCallback = null,
    ) {
        let originalHtml = this.reusebase.setButtonLoading(
            btn,
            "Checking...",
        );

        try {
            let checkFormData = new FormData();
            checkFormData.append("invoice_id", invoice_id);

            let checkResponse = await this.apiRequest.formPost(
                checkFormData,
                checkDeleteUrl,
                false,
            );

            this.reusebase.resetButtonLoading(btn, originalHtml);

            if (!checkResponse?.success) {
                this.reusebase.showSwal(
                    "error",
                    "Error",
                    checkResponse?.message || "Failed to verify invoice records.",
                );
                return;
            }

            let title = "Delete Invoice?";
            let htmlText = `Are you sure you want to delete invoice <strong>${checkResponse.invoice_no || ''}</strong>?`;
            let confirmButtonText = "Yes, delete!";

            if (checkResponse.has_payment_records) {
                title = "Delete Invoice & Related Payment Records?";
                htmlText = `Invoice <strong>${checkResponse.invoice_no || ''}</strong> has associated payment records (receipts, credit notes, debit notes, etc.).<br><br><span class="text-danger fw-bold">Warning:</span> If you delete this invoice, it will auto delete all this invoice related payment records (soft delete). Are you sure?`;
                confirmButtonText = "Yes, delete invoice & payment records!";
            }

            let result = await Swal.fire({
                title: title,
                html: htmlText,
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#d33",
                cancelButtonColor: "#6c757d",
                confirmButtonText: confirmButtonText,
                cancelButtonText: "Cancel",
                allowOutsideClick: false
            });

            if (!result.isConfirmed) return;

            originalHtml = this.reusebase.setButtonLoading(
                btn,
                "Deleting...",
            );

            let deleteFormData = new FormData();
            deleteFormData.append("invoice_id", invoice_id);

            let deleteResponse = await this.apiRequest.formPost(
                deleteFormData,
                deleteUrl,
            );

            if (deleteResponse?.success) {
                await this.reusebase.showSwal(
                    "success",
                    "Deleted!",
                    deleteResponse.message || "Invoice deleted successfully.",
                );
                if (typeof successCallback === "function") {
                    successCallback();
                } else {
                    window.location.reload();
                }
            } else {
                this.reusebase.showSwal(
                    "error",
                    "Error",
                    deleteResponse?.message || "Failed to delete invoice.",
                );
            }
        } catch (error) {
            console.error(error);
            this.reusebase.showSwal(
                "error",
                "Error",
                "An unexpected error occurred while deleting the invoice.",
            );
        } finally {
            this.reusebase.resetButtonLoading(btn, originalHtml);
        }
    }
}
