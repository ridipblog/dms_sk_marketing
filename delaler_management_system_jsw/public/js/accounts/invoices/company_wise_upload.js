$(document).ready(function() {
    let currentPage = 1;

    // Initial load
    try {
        loadUploadTracks();
    } catch (err) {
        console.error('Error during initial load of company wise upload tracks:', err);
    }

    // Refresh history button handler
    $('#btnRefreshHistory').on('click', function() {
        let btn = $(this);
        let icon = btn.find('i');
        try {
            btn.prop('disabled', true);
            icon.addClass('fa-spin');

            loadUploadTracks(currentPage, $('#searchInput').val(), function() {
                try {
                    btn.prop('disabled', false);
                    icon.removeClass('fa-spin');
                } catch (e) {
                    console.error('Error resetting refresh button state:', e);
                }
            });
        } catch (err) {
            console.error('Error refreshing company wise upload history:', err);
            btn.prop('disabled', false);
            icon.removeClass('fa-spin');
        }
    });

    // Search input handler with debounce
    let searchTimer;
    $('#searchInput').on('keyup', function() {
        try {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function() {
                try {
                    currentPage = 1;
                    loadUploadTracks(currentPage, $('#searchInput').val());
                } catch (err) {
                    console.error('Error in search timer callback:', err);
                }
            }, 400);
        } catch (err) {
            console.error('Error in search input handler:', err);
        }
    });

    // Pagination handler
    $(document).on('click', '#tableContainer .pagination a', function(e) {
        try {
            e.preventDefault();
            let page = $(this).attr('href').split('page=')[1];
            if (page) {
                currentPage = page;
                loadUploadTracks(currentPage, $('#searchInput').val());
            }
        } catch (err) {
            console.error('Error handling pagination click:', err);
        }
    });

    // Load upload tracks list via AJAX
    function loadUploadTracks(page = 1, search = '', callback = null) {
        try {
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
                    try {
                        if (response.success) {
                            $('#tableContainer').html(response.html);
                        } else {
                            $('#tableContainer').html('<div class="alert alert-danger m-3">' + (response.message || 'Failed to load data.') + '</div>');
                        }
                    } catch (err) {
                        console.error('Error rendering upload tracks table HTML:', err);
                        $('#tableContainer').html('<div class="alert alert-danger m-3">An error occurred while displaying history table.</div>');
                    } finally {
                        if (typeof callback === 'function') callback();
                    }
                },
                error: function(xhr, status, error) {
                    try {
                        console.error('AJAX error loading upload history:', status, error);
                        $('#tableContainer').html('<div class="alert alert-danger m-3">Failed to load upload history data.</div>');
                    } catch (err) {
                        console.error('Error handling AJAX failure:', err);
                    } finally {
                        if (typeof callback === 'function') callback();
                    }
                }
            });
        } catch (err) {
            console.error('Error in loadUploadTracks function:', err);
            $('#tableContainer').html('<div class="alert alert-danger m-3">An error occurred while requesting upload tracks.</div>');
            if (typeof callback === 'function') callback();
        }
    }

    // Submit upload form handler
    $('#companyWiseUploadForm').on('submit', function(e) {
        try {
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
                    try {
                        btn.prop('disabled', false).html('<i class="fas fa-cloud-upload-alt me-1"></i> Start Upload');
                        if (response.success) {
                            alertBox.removeClass('d-none').addClass('alert-success').text(response.message);
                            $('#companyWiseUploadForm')[0].reset();
                            setTimeout(function() {
                                try {
                                    $('#uploadCompanyWiseModal').modal('hide');
                                    alertBox.addClass('d-none');
                                    loadUploadTracks();
                                } catch (err) {
                                    console.error('Error closing upload modal:', err);
                                }
                            }, 1500);
                        } else {
                            alertBox.removeClass('d-none').addClass('alert-danger').text(response.message || 'Upload failed.');
                        }
                    } catch (err) {
                        console.error('Error handling upload response:', err);
                        btn.prop('disabled', false).html('<i class="fas fa-cloud-upload-alt me-1"></i> Start Upload');
                        alertBox.removeClass('d-none').addClass('alert-danger').text('An error occurred while processing server response.');
                    }
                },
                error: function(xhr) {
                    try {
                        btn.prop('disabled', false).html('<i class="fas fa-cloud-upload-alt me-1"></i> Start Upload');
                        let errMsg = 'An error occurred during file upload.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errMsg = xhr.responseJSON.message;
                        }
                        alertBox.removeClass('d-none').addClass('alert-danger').text(errMsg);
                    } catch (err) {
                        console.error('Error handling upload AJAX failure:', err);
                        btn.prop('disabled', false).html('<i class="fas fa-cloud-upload-alt me-1"></i> Start Upload');
                    }
                }
            });
        } catch (err) {
            console.error('Error in companyWiseUploadForm submit handler:', err);
            $('#btnSubmit').prop('disabled', false).html('<i class="fas fa-cloud-upload-alt me-1"></i> Start Upload');
            $('#uploadAlert').removeClass('d-none').addClass('alert-danger').text('An error occurred while preparing form submission.');
        }
    });
});
