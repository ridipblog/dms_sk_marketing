// public/js/auth/auth.js

import Request from "RequestModule";
import Reusebase from "ReusebaseModule";

$(document).ready(function () {
    // 1. Initialize reusable classes
    const apiRequest = new Request();
    const reusebase = new Reusebase();

    // Enforce 10-digit limit on the mobile number input
    reusebase.preventMobileMaxLength("#phone_number", 10);

    // ==============================================
    // LOGIN FORM LOGIC
    // ==============================================

    $("#loginForm").on("submit", async function (e) {
        e.preventDefault(); // Stop the default page reload

        let actionUrl = $(this).attr("action");
        let formData = new FormData(this);

        let submitBtn = $(this).find('button[type="submit"]');
        let originalText = reusebase.setButtonLoading(
            submitBtn,
            "Logging in...",
        );

        try {
            let response = await apiRequest.formPost(formData, actionUrl, false);
            
            // Assuming your backend returns a JSON with a 'success' boolean
            if (response?.success) {
                Swal.fire({
                    icon: "success",
                    title: "Welcome Back!",
                    text:
                        response?.message ||
                        "Login successful. Redirecting...",
                    showConfirmButton: false,
                    timer: 1500,
                }).then(() => {
                    window.location.href =
                        response?.redirect_url || "/dashboard";
                });
            } else {
                Swal.fire({
                    icon: "error",
                    title: "Login Failed",
                    text:
                        response?.message ||
                        "Invalid credentials provided.",
                });
            }
        } catch (error) {
            console.error("Error processing login response:", error);
            Swal.fire({
                icon: "error",
                title: "Processing Error",
                text: "An error occurred while reading the server response.",
            });
        } finally {
            reusebase.resetButtonLoading(submitBtn, originalText);
        }
    });

    // ==============================================
    // FORGOT PASSWORD FORM LOGIC
    // ==============================================
    $("#forgotPasswordForm").on("submit", async function (e) {
        e.preventDefault();

        let actionUrl = $(this).attr("action");
        let formData = new FormData(this);

        let submitBtn = $(this).find('button[type="submit"]');
        let originalText = reusebase.setButtonLoading(submitBtn, "Sending...");

        try {
            let response = await apiRequest.formPost(formData, actionUrl, false);
            console.log("Forgot Password Response received:", response);

            if (response.success) {
                Swal.fire({
                    icon: "success",
                    title: "Email Sent!",
                    text:
                        response.message ||
                        "A reset link has been sent to your phone/email.",
                });
            } else {
                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text:
                        response.message ||
                        "Could not send the reset link.",
                });
            }
        } catch (error) {
            console.error("Error processing forgot password response:", error);
            Swal.fire({
                icon: "error",
                title: "Processing Error",
                text: "An error occurred while reading the server response.",
            });
        } finally {
            reusebase.resetButtonLoading(submitBtn, originalText);
        }
    });
});
