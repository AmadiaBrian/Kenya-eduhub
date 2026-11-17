package com.example.kenyatduhubtechers.data.network

object ApiConfig {

    /**
     * Change your ngrok or server base URL here in one single place.
     * Replace with your active ngrok URL e.g. "https://xxxx-xx-xx-xx.ngrok-free.app/kenyaeduhub/teachers/api/"
     * Or local XAMPP emulator URL: "http://10.0.2.2/kenyaeduhub/teachers/api/"
     */
    const val DEFAULT_BASE_URL = "https://unexplaining-lesli-nonabsolutely.ngrok-free.dev/kenyaeduhub/teachers/api/"
    const val NETWORK_ERROR_MSG = "Network error, check your internet connection and try again"

    object Endpoints {
        const val LOGIN = "login.php"
        const val LOGOUT = "logout.php"
        const val DASHBOARD = "dashboard.php"
        const val PROFILE = "profile.php"
        const val ATTENDANCE = "attendance.php"
        const val DUTY = "duty.php"
        const val FEES = "fees.php"
        const val PARENTS = "parents.php"
        const val PERFORMANCE = "performance.php"
        const val RESULTS = "results.php"
        const val STUDENTS = "students.php"
        const val ASSIGNMENTS = "assignments.php"
        const val TIMETABLE = "timetable.php"
        const val CALENDAR = "calendar.php"
        const val STUDENT_SUBJECTS = "student-subjects.php"
    }
}
