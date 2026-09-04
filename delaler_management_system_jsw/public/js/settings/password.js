import Request from "RequestModule";

$(document).ready(function() {
    const apiRequest = new Request();
    
    $('#passwordForm').on('submit', async function(e) {
        e.preventDefault();
        
        const btn = $('#submitBtn');
        const originalText = btn.html();
        
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i> Updating...');
        
        try {
            const formData = new FormData(this);
            // We use the action attribute of the form for the URL, or a global variable.
            // Since it's an external JS file, we'll get the URL from a data attribute or hardcode it if known.
            // Best practice is to set a data-url on the form.
            const url = $(this).data('url');
            const response = await apiRequest.formPost(formData, url, false);
            
            if (response.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: response.message,
                    confirmButtonColor: '#4e73df'
                });
                $('#passwordForm')[0].reset();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: response.message,
                    confirmButtonColor: '#e74a3b'
                });
            }
        } catch (error) {
            console.error("Password update error:", error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'An unexpected error occurred while updating the password.',
                confirmButtonColor: '#e74a3b'
            });
        } finally {
            btn.prop('disabled', false).html(originalText);
        }
    });
});
