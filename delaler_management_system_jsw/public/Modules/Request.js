class Request {
    constructor() {}
    
    // ---------------- request form using post method --------------
    formPost = async (form_data, api_route, is_html = false) => {
        try {
            return await $.ajax({
                type: "post",
                url: api_route,
                headers: {
                    "X-CSRF-Token": $('meta[name="csrf-token"]').attr("content"),
                },
                data: form_data,
                dataType: is_html ? "html" : "json",
                contentType: false,
                processData: false,
            });
        } catch (error) {
            console.error("AJAX POST Error:", error);
            throw error; // Rethrow to be caught by the caller
        }
    };
    
    // ---------------- request form using get method --------------
    formGet = async (form_data, api_route, is_html = false) => {
        try {
            return await $.ajax({
                type: "get",
                url: api_route,
                data: form_data,
                dataType: is_html ? "html" : "json",
            });
        } catch (error) {
            console.error("AJAX GET Error:", error);
            throw error; // Rethrow to be caught by the caller
        }
    };
}

export default Request;
