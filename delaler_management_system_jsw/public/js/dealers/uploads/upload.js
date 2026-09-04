import Request from "RequestModule";
import Reusebase from "ReusebaseModule";

$(document).ready(function () {
    const apiRequest = new Request();
    const reusebase = new Reusebase();
    let pollInterval = null;

    // ==========================================
    // 1. Fetch & Pagination Logic
    // ==========================================
    
    const fetchUploads = async (page = 1, searchQuery = "") => {
        try {
            let formData = new FormData();
            formData.append("page", page);
            formData.append("search", searchQuery);

            let response = await apiRequest.formPost(
                formData,
                "/dealers/upload/list",
                false, // don't show loading on screen, just quietly refresh if polling
            );

            if (response.success) {
                $("#uploadTableContainer").html(response.html);
                
                // If any rows have "processing" or "pending" status, start polling
                if (response.html.includes("fa-spinner fa-spin")) {
                    startPolling();
                } else {
                    stopPolling();
                }
            } else {
                $("#uploadTableContainer").html(
                    response.html || '<div class="alert alert-danger">Failed to load upload history.</div>',
                );
                stopPolling();
            }
        } catch (error) {
            console.error("Error rendering upload list:", error);
            stopPolling();
        }
    };

    const startPolling = () => {
        if (!pollInterval) {
            pollInterval = setInterval(() => {
                let currentSearch = $("#searchUploads").val();
                let currentPage = new URLSearchParams(window.location.search).get('page') || 1;
                fetchUploads(currentPage, currentSearch);
            }, 5000); // Check every 5 seconds
        }
    };

    const stopPolling = () => {
        if (pollInterval) {
            clearInterval(pollInterval);
            pollInterval = null;
        }
    };

    // Initial load
    $("#uploadTableContainer").html(`
        <div class="text-center py-5 text-muted">
            <i class="fas fa-spinner fa-spin fs-1 mb-3 text-primary"></i>
            <h5 class="fw-bold">Loading Upload History...</h5>
        </div>
    `);
    fetchUploads(1, "");

    // Search functionality with debounce
    let searchTimeout;
    $("#searchUploads").on("keyup", function () {
        clearTimeout(searchTimeout);
        let query = $(this).val();

        searchTimeout = setTimeout(() => {
            fetchUploads(1, query);
        }, 500); 
    });

    $("#btnRefreshList").on("click", function() {
        let btn = $(this);
        btn.find('i').addClass('fa-spin');
        
        let currentSearch = $("#searchUploads").val();
        fetchUploads(1, currentSearch).finally(() => {
            setTimeout(() => btn.find('i').removeClass('fa-spin'), 500);
        });
    });

    // Pagination click handler
    $(document).on("click", ".pagination a", function (e) {
        e.preventDefault();
        let pageUrl = $(this).attr("href");
        let pageNumber = new URL(pageUrl).searchParams.get("page");
        let currentSearch = $("#searchUploads").val();

        if (pageNumber) {
            fetchUploads(pageNumber, currentSearch);
        }
    });

    // ==========================================
    // 2. Upload Form Logic
    // ==========================================
    $("#uploadDealerForm").on("submit", async function (e) {
        e.preventDefault();

        let originalText = reusebase.setButtonLoading(
            "#btnSubmitUpload",
            "Uploading...",
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
                $("#uploadDealerModal").modal("hide");
                $("#uploadDealerForm")[0].reset();

                Swal.fire({
                    icon: "success",
                    title: "File Uploaded!",
                    text: response.message || "Your file is now processing in the background.",
                    showConfirmButton: true,
                    confirmButtonText: 'Great!'
                }).then(() => {
                    let currentSearch = $("#searchUploads").val();
                    // Show a quick loading spinner
                    $("#uploadTableContainer").html(`
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-spinner fa-spin fs-1 mb-3 text-primary"></i>
                            <h5 class="fw-bold">Refreshing...</h5>
                        </div>
                    `);
                    fetchUploads(1, currentSearch);
                });
            } else {
                Swal.fire({
                    icon: "error",
                    title: "Upload Failed",
                    text: response.message || "Could not upload the file.",
                });
            }
        } catch (error) {
            console.error("File upload error:", error);
            Swal.fire({
                icon: "error",
                title: "Network Error",
                text: "A network error occurred while submitting the file.",
            });
        } finally {
            reusebase.resetButtonLoading("#btnSubmitUpload", originalText);
        }
    });
});
