class Reusebase {
    constructor() {
        if (typeof window !== "undefined" && !window.inr) {
            window.inr = this.inr;
            window.formatIndianCurrency = this.inr;
        }
    }

    /**
     * Format a numeric amount in Indian Currency format (Lakhs & Crores grouping)
     * e.g. 270995.32 -> 2,70,995.32
     *
     * @param {number|string} amount
     * @param {number} decimals
     * @returns {string}
     */
    inr = (amount, decimals = 2) => {
        if (amount === null || amount === undefined || amount === "" || isNaN(amount)) {
            return (0).toFixed(decimals);
        }
        return new Intl.NumberFormat('en-IN', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        }).format(amount);
    };

    /**
     * Get standardized HTML for an error state to place inside a container
     *
     * @param {string} message - Error message to display
     * @param {string} iconClass - FontAwesome class for the icon
     * @returns {string} HTML string
     */
    getErrorHtml = (message = "Unexpected error. Please try again.", iconClass = "fas fa-exclamation-triangle") => {
        return `
            <div class="text-center py-4 text-danger">
                <i class="${iconClass} fa-2x mb-2"></i>
                <p>${message}</p>
            </div>
        `;
    };

    /**
     * Get standardized HTML for a loading state to place inside a container
     *
     * @param {string} message - Loading message to display
     * @returns {string} HTML string
     */
    getLoadingHtml = (message = "Loading...") => {
        return `
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">${message}</span>
                </div>
            </div>
        `;
    };

    /**
     * Prevents an input field from exceeding a specified maximum length,
     * specifically tailored for numeric fields like mobile numbers.
     *
     * @param {string} selector - The jQuery selector for the input (e.g., '#phone_number')
     * @param {number} maxLength - The maximum allowed length (default: 10)
     */
    preventMobileMaxLength = (selector, maxLength = 10) => {
        $(selector).on("input", function () {
            let value = $(this).val();

            // Remove any non-numeric characters (only allow digits)
            value = value.replace(/\D/g, "");

            // If length exceeds maxLength, truncate it
            if (value.length > maxLength) {
                value = value.substring(0, maxLength);
            }

            $(this).val(value);
        });
    };

    /**
     * Set a button to a loading state with a spinner, and return its original HTML.
     *
     * @param {string|object} selector - jQuery selector or element for the button
     * @param {string} loadingText - Text to display while loading
     * @returns {string} The original HTML content of the button
     */
    setButtonLoading = (selector, loadingText = 'Saving...') => {
        let btn = $(selector);
        let originalHtml = btn.html();
        btn.html(`<i class="fas fa-spinner fa-spin me-2"></i> ${loadingText}`).prop('disabled', true);
        return originalHtml;
    };

    /**
     * Restore a button to its original state.
     *
     * @param {string|object} selector - jQuery selector or element for the button
     * @param {string} originalHtml - The original HTML to restore
     */
    resetButtonLoading = (selector, originalHtml) => {
        $(selector).html(originalHtml).prop('disabled', false);
    };

    /**
     * Initialize Select2 on a given element with standard configuration
     *
     * @param {string|object} selector - jQuery selector or element
     * @param {string} placeholderText - Placeholder text to display
     */
    initSelect2 = (selector, placeholderText = 'Select...') => {
        if ($(selector).length > 0) {
            $(selector).select2({
                theme: 'bootstrap-5',
                placeholder: placeholderText,
                width: '100%'
            });
        }
    };

    /**
     * Show a standardized SweetAlert popup
     *
     * @param {string} icon - 'success', 'error', 'warning', 'info'
     * @param {string} title - The title of the alert
     * @param {string} text - The body text
     * @param {number|null} timer - Optional auto-close timer in ms (default: 2000 for success)
     * @returns {Promise} Resolves when the alert is closed
     */
    showSwal = async (icon, title, text, timer = null) => {
        let config = {
            icon: icon,
            title: title,
            text: text
        };

        // Automatically hide confirm button and set timer for success if not specified
        if (icon === 'success' || timer) {
            config.showConfirmButton = false;
            config.timer = timer || 2000;
        }

        return Swal.fire(config);
    };

    /**
     * Show a standardized toast notification using SweetAlert2
     *
     * @param {string} icon - 'success', 'error', 'warning', 'info'
     * @param {string} title - The title/message of the toast
     */
    showToast = (icon, title) => {
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer);
                toast.addEventListener('mouseleave', Swal.resumeTimer);
            }
        });

        Toast.fire({
            icon: icon,
            title: title
        });
    };

    /**
     * Show a standardized confirmation dialog using SweetAlert2
     *
     * @param {string} title - The title of the confirmation
     * @param {string} text - The text describing the action
     * @param {string} confirmButtonText - The text for the confirm button
     * @param {function} callback - Function to execute if confirmed
     */
    confirmAction = async (title, text, confirmButtonText) => {
        let result = await Swal.fire({
            title: title,
            text: text,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: confirmButtonText
        });

        return result.isConfirmed;
    };

    /**
     * Creates a debounced function that delays invoking the provided function until after wait milliseconds
     * have elapsed since the last time the debounced function was invoked.
     *
     * @param {Function} func - The function to debounce
     * @param {number} wait - The number of milliseconds to delay
     * @returns {Function} The new debounced function
     */
    debounce = (func, wait) => {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    };
}

export default Reusebase;
