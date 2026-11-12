package com.example.kenyaeduhub.data

object ApiConfig {
    // Change your base domain and project path here once for all API endpoints
    const val BASE_URL = "https://unexplaining-lesli-nonabsolutely.ngrok-free.dev/kenyaeduhub/api/"

    // Endpoints
    val LOGIN_URL = "${BASE_URL}login.php"
    val REGISTER_URL = "${BASE_URL}register.php"
    val FORGOT_PASSWORD_URL = "${BASE_URL}forgot_password.php"
    val RESEND_URL = "${BASE_URL}resend.php"
    val DASHBOARD_URL = "${BASE_URL}dashboard.php"
    val PROFILE_URL = "${BASE_URL}profile.php"
    val SETTINGS_URL = "${BASE_URL}settings.php"
}
