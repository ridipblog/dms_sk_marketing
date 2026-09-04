import Request from "RequestModule";
import Reusebase from "ReusebaseModule";

$(document).ready(function () {
    const apiRequest = new Request();
    const reusebase = new Reusebase();

    // ==========================================
    // 1. Fetch & Pagination Logic
    // ==========================================

    const fetchUsers = async (page = 1) => {
        let searchQuery = $("#searchUser").val() || "";

        $("#userTableContainer").html(`
            <div class="text-center py-5 text-muted">
                <i class="fas fa-spinner fa-spin fs-1 mb-3 text-primary"></i>
                <h5 class="fw-bold">Loading Users...</h5>
            </div>
        `);

        try {
            let formData = new FormData();
            formData.append("page", page);
            formData.append("search", searchQuery);

            let response = await apiRequest.formPost(
                formData,
                "/users/roles/list",
                false,
            );

            if (response?.success ?? null) {
                $("#userTableContainer").html(response?.html ?? "");
            } else {
                let msg = response?.message ?? "Failed to load users.";
                reusebase.showToast("error", msg);
                $("#userTableContainer").html(`
                    <div class="alert alert-danger shadow-sm border-0 m-3">
                        <i class="fas fa-exclamation-triangle me-2"></i> ${msg}
                    </div>
                `);
            }
        } catch (error) {
            console.error("Fetch Users Error:", error);
            reusebase.showToast(
                "error",
                "An unexpected error occurred while fetching users.",
            );
        }
    };

    fetchUsers();

    // Event listeners for search and pagination
    $(document).on("keyup", "#searchUser", function () {
        fetchUsers(1);
    });

    $(document).on("click", ".pagination a", function (e) {
        e.preventDefault();
        let page = $(this).attr("href").split("page=")[1];
        fetchUsers(page);
    });

    // ==========================================
    // 2. Open Manage Modal
    // ==========================================

    let currentUserId = null;

    $(document).on("click", ".btn-manage-roles", async function () {
        const id = $(this).data("id");
        currentUserId = id;

        try {
            let response = await apiRequest.formGet(
                {},
                `/users/roles/${id}/mappings`,
                false,
            );

            if (response?.success ?? null) {
                $("#modalContainer").html(response?.html ?? "");
                $("#manageRoleModal").modal("show");
            } else {
                reusebase.showToast(
                    "error",
                    response?.message ?? "Failed to load mapping data.",
                );
            }
        } catch (error) {
            console.error("Open Manage Modal Error:", error);
            reusebase.showToast(
                "error",
                "An error occurred while opening the modal.",
            );
        }
    });

    // ==========================================
    // 3. Assign New Role
    // ==========================================

    // Helper function to load parent/reporting users
    const loadParentUsers = async (roleId, companyId, selectedParentId = null) => {
        const reportingContainer = $("#reporting_container");
        const parentSelect = $("#parent_id");

        if (!roleId || !companyId) {
            reportingContainer.addClass("d-none");
            parentSelect.val("").trigger("change");
            return;
        }

        try {
            let formData = new FormData();
            formData.append("role_id", roleId);
            formData.append("company_id", companyId);

            let response = await apiRequest.formPost(
                formData,
                "/users/roles/parent-users",
                false,
            );

            if (response?.success && response?.data?.length > 0) {
                parentSelect.empty().append('<option value="" selected disabled>-- Choose reporting user --</option>');
                response.data.forEach((user) => {
                    parentSelect.append(`<option value="${user.id}">${user.name}</option>`);
                });
                reportingContainer.removeClass("d-none");
                
                // Initialize Select2 for searchable select
                parentSelect.select2({
                    dropdownParent: $("#manageRoleModal"),
                    theme: "bootstrap-5",
                    width: "100%"
                });

                if (selectedParentId) {
                    parentSelect.val(selectedParentId).trigger("change");
                }
            } else {
                reportingContainer.addClass("d-none");
                parentSelect.empty().append('<option value="" selected disabled>-- Choose reporting user --</option>');
                parentSelect.val("").trigger("change");
            }
        } catch (error) {
            console.error("Fetch parent users error:", error);
        }
    };

    // Fetch Parent/Reporting Users dynamically on change of Role or Company
    $(document).on("change", "#role_id, #company_id", function () {
        const roleId = $("#role_id").val();
        const companyId = $("#company_id").val();
        const pendingVal = $("#parent_id").data("pending-val");

        loadParentUsers(roleId, companyId, pendingVal);
        $("#parent_id").removeData("pending-val");
    });

    // Helper to reset Edit Mode back to Assign Mode
    const resetEditMode = () => {
        $("#editing_mapping_id").val("");
        $("#assignRoleForm")[0].reset();
        $("#company_id").val("").trigger("change");
        $("#role_id").val("").trigger("change");
        $("#formCardHeader").html('<i class="fas fa-plus-circle me-2 text-success"></i>Assign New Role');
        $("#btnAssignRole").html('<i class="fas fa-check me-1"></i> Assign Context');
        $("#btnCancelEdit").addClass("d-none");
    };

    // Click Edit Mapping
    $(document).on("click", ".btn-edit-mapping", async function () {
        const mappingId = $(this).data("id");
        const btn = $(this);
        const originalHtml = btn.html();

        try {
            btn.html('<i class="fas fa-spinner fa-spin"></i>').prop("disabled", true);

            let response = await apiRequest.formGet(
                {},
                `/users/roles/mapping/${mappingId}/details`,
                false
            );

            btn.html(originalHtml).prop("disabled", false);

            if (response?.success && response?.data) {
                const companyId = response.data.company_id;
                const roleId = response.data.role_id;
                const parentId = response.data.parent_id;

                $("#editing_mapping_id").val(mappingId);
                
                // Store the pending parent value
                $("#parent_id").data("pending-val", parentId);

                // Set values and trigger change to load parent users list
                $("#company_id").val(companyId);
                $("#role_id").val(roleId).trigger("change");

                $("#formCardHeader").html('<i class="fas fa-edit me-2 text-primary"></i>Edit Role & Company');
                $("#btnAssignRole").html('<i class="fas fa-check me-1"></i> Update Context');
                $("#btnCancelEdit").removeClass("d-none");
            } else {
                reusebase.showToast("error", response?.message ?? "Failed to fetch details.");
            }
        } catch (error) {
            console.error("Fetch mapping details error:", error);
            btn.html(originalHtml).prop("disabled", false);
            reusebase.showToast("error", "An error occurred while loading details.");
        }
    });

    // Click Cancel Edit
    $(document).on("click", "#btnCancelEdit", function () {
        resetEditMode();
    });

    $(document).on("submit", "#assignRoleForm", async function (e) {
        e.preventDefault();

        const editingMappingId = $("#editing_mapping_id").val();
        const isEditMode = !!editingMappingId;

        const btn = $("#btnAssignRole");
        const originalText = btn.html();

        let formData = new FormData(this);
        const userId = $("#user_id").val();

        try {
            btn.html(
                isEditMode ? '<i class="fas fa-spinner fa-spin"></i> Updating...' : '<i class="fas fa-spinner fa-spin"></i> Assigning...',
            ).prop("disabled", true);

            const url = isEditMode 
                ? `/users/roles/mapping/${editingMappingId}/update`
                : `/users/roles/${userId}/store`;

            let response = await apiRequest.formPost(
                formData,
                url,
                false,
            );

            if (response?.success ?? null) {
                reusebase.showToast(
                    "success",
                    response?.message ?? "Saved successfully!",
                );
                
                if (isEditMode) {
                    resetEditMode();
                } else {
                    $("#assignRoleForm")[0].reset();
                    $("#company_id").val("").trigger("change");
                    $("#role_id").val("").trigger("change");
                }

                btn.html(originalText).prop("disabled", false);
                // Refresh the modal to show the updated mapping
                refreshManageModal(userId);
            } else {
                reusebase.showToast(
                    "error",
                    response?.message ?? "Failed to save mapping.",
                );
                btn.html(originalText).prop("disabled", false);
            }
        } catch (error) {
            console.error("Assign Role Error:", error);
            reusebase.showToast(
                "error",
                "An error occurred while saving the assignment.",
            );
            btn.html(originalText).prop("disabled", false);
        }
    });

    // ==========================================
    // 4. Remove Mapping
    // ==========================================

    $(document).on("click", ".btn-remove-mapping", async function () {
        const mappingId = $(this).data("id");

        let isConfirmed = await reusebase.confirmAction(
            "Remove Mapping?",
            "Are you sure you want to remove this role and company mapping for this user?",
            "Yes, remove it!",
        );

        if (!isConfirmed) return;

        try {
            let formData = new FormData();
            let response = await apiRequest.formPost(
                formData,
                `/users/roles/mapping/${mappingId}/delete`,
                false,
            );

            if (response?.success ?? null) {
                reusebase.showToast(
                    "success",
                    response?.message ?? "Mapping removed!",
                );
                // Refresh the modal
                refreshManageModal(currentUserId);
            } else {
                reusebase.showToast(
                    "error",
                    response?.message ?? "Failed to remove mapping.",
                );
            }
        } catch (error) {
            console.error("Remove Mapping Error:", error);
            reusebase.showToast(
                "error",
                "An error occurred while removing the mapping.",
            );
        }
    });

    // ==========================================
    // 5. Helper Function to Refresh Modal Content
    // ==========================================

    const refreshManageModal = async (userId) => {
        try {
            let response = await apiRequest.formGet(
                {},
                `/users/roles/${userId}/mappings`,
                false,
            );
            if (response?.success ?? null) {
                // Update the modal content
                $("#modalContainer").html(response?.html ?? "");
                // Ensure it stays open by re-initializing it (if needed) but usually HTML replacement works
                // if we don't destroy the backdrop. But replacing the whole modal HTML while open can be tricky.
                // Let's hide the old one first properly.
                $(".modal-backdrop").remove();
                $("body").removeClass("modal-open").css("padding-right", "");

                $("#manageRoleModal").modal("show");
            }
        } catch (error) {
            console.error("Refresh Modal Error:", error);
        }
    };
});
