$(document).ready(function() {
    let currentPage = 1;

    // Initial load
    loadUploadTracks();

    // Refresh history button handler
    $('#btnRefreshHistory').on('click', function() {
        let btn = $(this);
        let icon = btn.find('i');
        btn.prop('disabled', true);
        icon.addClass('fa-spin');

        loadUploadTracks(currentPage, $('#searchInput').val(), function() {
            btn.prop('disabled', false);
            icon.removeClass('fa-spin');
        });
    });

    // Search input handler with debounce
    let searchTimer;
    $('#searchInput').on('keyup', function() {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function() {
            currentPage = 1;
            loadUploadTracks(currentPage, $('#searchInput').val());
        }, 400);
    });

    // Pagination handler
    $(document).on('click', '#tableContainer .pagination a', function(e) {
        e.preventDefault();
        let page = $(this).attr('href').split('page=')[1];
        if (page) {
            currentPage = page;
            loadUploadTracks(currentPage, $('#searchInput').val());
        }
    });

    // Load upload tracks list via AJAX
    function loadUploadTracks(page = 1, search = '', callback = null) {
        let listUrl = window.CompanyWiseUploadConfig ? window.CompanyWiseUploadConfig.listUrl : '';
        let csrfToken = window.CompanyWiseUploadConfig ? window.CompanyWiseUploadConfig.csrfToken : '';

        $.ajax({
            url: listUrl,
            type: "POST",
            data: {
                _token: csrfToken,
                page: page,
                search: search
            },
            success: function(response) {
                if(response.success) {
                    $('#tableContainer').html(response.html);
                } else {
                    $('#tableContainer').html('<div class="alert alert-danger m-3">' + response.message + '</div>');
                }
                if (typeof callback === 'function') callback();
            },
            error: function() {
                $('#tableContainer').html('<div class="alert alert-danger m-3">Failed to load upload history data.</div>');
                if (typeof callback === 'function') callback();
            }
        });
    }

    // Submit upload form handler
    $('#companyWiseUploadForm').on('submit', function(e) {
        e.preventDefault();
        let formData = new FormData(this);
        let alertBox = $('#uploadAlert');
        let btn = $('#btnSubmit');
        let importUrl = window.CompanyWiseUploadConfig ? window.CompanyWiseUploadConfig.importUrl : '';

        alertBox.addClass('d-none').removeClass('alert-success alert-danger');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Uploading...');

        $.ajax({
            url: importUrl,
            type: "POST",
            data: formData,
            contentType: false,
            processData: false,
            success: function(response) {
                btn.prop('disabled', false).html('<i class="fas fa-cloud-upload-alt me-1"></i> Start Upload');
                if(response.success) {
                    alertBox.removeClass('d-none').addClass('alert-success').text(response.message);
                    $('#companyWiseUploadForm')[0].reset();
                    setTimeout(function() {
                        $('#uploadCompanyWiseModal').modal('hide');
                        alertBox.addClass('d-none');
                        loadUploadTracks();
                    }, 1500);
                } else {
                    alertBox.removeClass('d-none').addClass('alert-danger').text(response.message || 'Upload failed.');
                }
            },
            error: function(xhr) {
                btn.prop('disabled', false).html('<i class="fas fa-cloud-upload-alt me-1"></i> Start Upload');
                let errMsg = 'An error occurred during file upload.';
                if(xhr.responseJSON && xhr.responseJSON.message) {
                    errMsg = xhr.responseJSON.message;
                }
                alertBox.removeClass('d-none').addClass('alert-danger').text(errMsg);
            }
        });
    });
});
