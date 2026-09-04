import Request from "RequestModule";

$(document).ready(function () {
    const apiRequest = new Request();

    // Initialize Select2 on filter select boxes
    if ($.fn.select2) {
        $(".filter-card .select2").select2({
            theme: "bootstrap-5",
            width: "100%"
        });
    }

    $(".admin-cascade-select").on("change", function () {
        const roleTypeAttr = $(this).attr("data-role");
        const roleId = $(this).val();
        const targetSelect = $(this).attr("data-target");

        // Reset downstream dropdowns when parent option changes
        if (roleTypeAttr === "DM") {
            $("#admin-filter-aso")
                .html('<option value="">All ASOs</option>')
                .trigger("change.select2");
            $("#admin-filter-dealer")
                .html('<option value="">All Dealers</option>')
                .trigger("change.select2");
        } else if (roleTypeAttr === "ASM") {
            $("#admin-filter-dealer")
                .html('<option value="">All Dealers</option>')
                .trigger("change.select2");
        }

        filterDashboardData(roleTypeAttr, roleId, targetSelect);
    });

    $("#admin-filter-dealer, #admin-filter-date").on("change", function () {
        filterDashboardData();
    });

    // Filter Handler using RequestModule
    async function filterDashboardData(
        parentRole = null,
        parentId = null,
        targetSelect = null
    ) {
        let formData = new FormData();
        if (parentRole) {
            formData.append("parent_role", parentRole);
            formData.append("parent_id", parentId || "");
        }
        formData.append("dm_id", $("#admin-filter-dm").val() || "");
        formData.append("asm_id", $("#admin-filter-asm").val() || "");
        formData.append("aso_id", $("#admin-filter-aso").val() || "");
        formData.append("dealer_id", $("#admin-filter-dealer").val() || "");
        formData.append("date", $("#admin-filter-date").val() || "");

        try {
            const res = await apiRequest.formPost(
                formData,
                "/dashboard/filter-options",
                false
            );
            if (res && res.success) {
                // Fill target select box if targetSelect is passed
                if (targetSelect && res.options) {
                    const $target = $(targetSelect);
                    let defaultText = "All Options";
                    if ($target.attr("id") === "admin-filter-asm") defaultText = "All ASMs";
                    if ($target.attr("id") === "admin-filter-aso") defaultText = "All ASOs";
                    if ($target.attr("id") === "admin-filter-dealer") defaultText = "All Dealers";

                    let html = `<option value="">${defaultText}</option>`;
                    res.options.forEach((opt) => {
                        html += `<option value="${opt.id}">${opt.name}</option>`;
                    });
                    $target.html(html).trigger("change.select2");
                }

                // Set outstanding data value in card
                if (res.outstanding_formatted) {
                    $("#admin-kpi-outstanding").text(res.outstanding_formatted);
                }
            }
        } catch (err) {
            console.error("Error fetching dashboard filter data:", err);
        }
    }
});
