import Request from "RequestModule";
import Reusebase from "ReusebaseModule";

$(document).ready(function () {
    const apiRequest = new Request();
    const reusebase = new Reusebase();

    // Prevent more than 10 digits in phone inputs
    reusebase.preventMobileMaxLength("#create_phone", 10);
    reusebase.preventMobileMaxLength("#edit_phone", 10);

    // Helper function to toggle form fields state depending on whether the phone input is blank
    function toggleCreateUserFields() {
        let phoneVal = $("#create_phone").val().trim();
        let isBlank = phoneVal === "";

        $('#createUserForm [name="name"]').prop('disabled', isBlank);
        $('#createUserForm [name="email"]').prop('disabled', isBlank);
        $('#createUserForm [name="designation"]').prop('disabled', isBlank);
        $('#createUserForm [name="status"]').prop('disabled', isBlank);

        if (isBlank) {
            $('#createUserForm [name="name"]').val('');
            $('#createUserForm [name="email"]').val('');
            $('#createUserForm [name="designation"]').val('');
            $('#createUserForm [name="status"]').val('active');
        }
    }

    // Reset create user form and adjust fields state when opening the modal
    $("#createUserModal").on("show.bs.modal", function () {
        $("#createUserForm")[0].reset();
        toggleCreateUserFields();
    });

    // Fetch user details automatically if a 10-digit phone number matches an existing user
    $("#create_phone").on("input", async function () {
        toggleCreateUserFields();

        let phoneVal = $(this).val();
        if (phoneVal.length === 10) {
            try {
                let formData = new FormData();
                formData.append("phone", phoneVal);

                let response = await apiRequest.formPost(
                    formData,
                    "/users/find-by-phone",
                    false,
                );

                if (response?.success && response?.user) {
                    let user = response.user;
                    $('#createUserForm [name="name"]').val(user.name ?? '');
                    $('#createUserForm [name="email"]').val(user.email ?? '');
                    $('#createUserForm [name="designation"]').val(user.designation ?? '');
                    $('#createUserForm [name="status"]').val(user.status ?? 'active');
                }
            } catch (error) {
                console.error("Error finding user by phone:", error);
            }
        }
    });

    // ==========================================
    // 1. Fetch & Pagination Logic
    // ==========================================

    const fetchUsers = async (page = 1) => {
        let searchQuery = $("#searchUser").val() || "";
        let statusQuery = $("#statusFilter").val() || "";

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
            formData.append("status", statusQuery);

            let response = await apiRequest.formPost(
                formData,
                "/users/list",
                false,
            );

            if (response?.success ?? null) {
                $("#userTableContainer").html(response?.html ?? null);
            } else {
                $("#userTableContainer").html(
                    (response?.html ?? null) ||
                        '<div class="alert alert-danger">Failed to load users.</div>',
                );
            }
        } catch (error) {
            console.error("Error rendering user list:", error);
            $("#userTableContainer").html(
                '<div class="alert alert-danger">An error occurred while rendering the users list.</div>',
            );
        }
    };

    // Initial load
    fetchUsers(1);

    // Search functionality with debounce
    let searchTimeout;
    $("#searchUser").on("keyup", function () {
        clearTimeout(searchTimeout);

        searchTimeout = setTimeout(() => {
            fetchUsers(1);
        }, 500); // 500ms delay
    });

    // Status filter handler
    $("#statusFilter").on("change", function () {
        fetchUsers(1);
    });

    // Pagination click handler
    $(document).on("click", ".pagination a", function (e) {
        e.preventDefault();
        let pageUrl = $(this).attr("href");
        let pageNumber = new URL(pageUrl).searchParams.get("page");

        if (pageNumber) {
            fetchUsers(pageNumber);
        }
    });

    // ==========================================
    // 2. Create User Form Logic
    // ==========================================
    $("#createUserForm").on("submit", async function (e) {
        e.preventDefault();

        let originalText = reusebase.setButtonLoading(
            "#btnSubmitUser",
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

            if (response?.success ?? null) {
                $("#createUserModal").modal("hide");
                $("#createUserForm")[0].reset();

                Swal.fire({
                    icon: "success",
                    title: "User Created!",
                    text:
                        response?.message ?? "The user was successfully added.",
                    showConfirmButton: false,
                    timer: 2000,
                }).then(() => {
                    fetchUsers(1);
                });
            } else {
                Swal.fire({
                    icon: "error",
                    title: "Creation Failed",
                    text:
                        response?.message ??
                        "Could not create user. Please check the inputs.",
                });
            }
        } catch (error) {
            console.error("User creation error:", error);
            Swal.fire({
                icon: "error",
                title: "Network Error",
                text: "A network error occurred while submitting the form.",
            });
        } finally {
            reusebase.resetButtonLoading("#btnSubmitUser", originalText);
        }
    });

    // ==========================================
    // 3. Edit User Logic
    // ==========================================

    $(document).on("click", ".btn-edit-user", async function (e) {
        e.preventDefault();
        let userId = $(this).data("id");
        let btn = $(this);

        let originalHtml = btn.html();
        reusebase.setButtonLoading(
            btn,
            '<i class="fas fa-spinner fa-spin"></i>',
        );

        try {
            let response = await apiRequest.formGet(
                {},
                `/users/${userId}/edit`,
                false,
            );

            if ((response?.success ?? null) && (response?.user ?? null)) {
                $("#edit_user_id").val(response?.user?.id ?? null);
                $("#edit_name").val(response?.user?.name ?? null);
                $("#edit_phone").val(response?.user?.phone ?? null);
                $("#edit_email").val(response?.user?.email ?? null);
                $("#edit_designation").val(response?.user?.designation ?? null);
                $("#edit_status").val(response?.user?.status ?? null);

                // Set form action URL dynamically
                $("#editUserForm").attr("action", `/users/${userId}/update`);

                $("#editUserModal").modal("show");
            } else {
                Swal.fire({
                    icon: "error",
                    title: "Fetch Failed",
                    text: response?.message ?? "Could not fetch user details.",
                });
            }
        } catch (error) {
            console.error("Error fetching user:", error);
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
    $("#editUserForm").on("submit", async function (e) {
        e.preventDefault();

        let originalText = reusebase.setButtonLoading(
            "#btnUpdateUser",
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

            if (response?.success ?? null) {
                $("#editUserModal").modal("hide");

                Swal.fire({
                    icon: "success",
                    title: "User Updated!",
                    text:
                        response?.message ??
                        "The user was successfully updated.",
                    showConfirmButton: false,
                    timer: 2000,
                }).then(() => {
                    fetchUsers(1);
                });
            } else {
                Swal.fire({
                    icon: "error",
                    title: "Update Failed",
                    text:
                        response?.message ??
                        "Could not update user. Please check the inputs.",
                });
            }
        } catch (error) {
            console.error("User update error:", error);
            Swal.fire({
                icon: "error",
                title: "Network Error",
                text: "A network error occurred while submitting the form.",
            });
        } finally {
            reusebase.resetButtonLoading("#btnUpdateUser", originalText);
        }
    });

    // ==========================================
    // 4. View User Logic
    // ==========================================

    $(document).on("click", ".btn-view-user", async function (e) {
        e.preventDefault();
        let userId = $(this).data("id");
        let btn = $(this);

        let originalHtml = btn.html();
        reusebase.setButtonLoading(
            btn,
            '<i class="fas fa-spinner fa-spin"></i>',
        );

        try {
            let response = await apiRequest.formGet(
                {},
                `/users/${userId}/show`,
                false,
            );

            if ((response?.success ?? null) && (response?.html ?? null)) {
                $("#viewModalContainer").html(response?.html ?? null);
                $("#viewUserModal").modal("show");
            } else {
                Swal.fire({
                    icon: "error",
                    title: "Fetch Failed",
                    text: response?.message ?? "Could not fetch user details.",
                });
            }
        } catch (error) {
            console.error("Error fetching user view:", error);
            Swal.fire({
                icon: "error",
                title: "Network Error",
                text: "Failed to communicate with the server.",
            });
        } finally {
            reusebase.resetButtonLoading(btn, originalHtml);
        }
    });
});
