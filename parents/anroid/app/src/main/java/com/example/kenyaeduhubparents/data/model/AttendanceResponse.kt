package com.example.kenyaeduhubparents.data.model

import com.google.gson.annotations.SerializedName

data class AttendanceResponse(
    @SerializedName("success") val success: Boolean,
    @SerializedName("error") val error: String? = null,
    @SerializedName("children") val children: List<StudentChild> = emptyList(),
    @SerializedName("selected_child_id") val selectedChildId: Any? = null,
    @SerializedName("terms") val terms: List<String> = emptyList(),
    @SerializedName("years") val years: List<Int> = emptyList(),
    @SerializedName("current_term") val currentTerm: String? = null,
    @SerializedName("current_year") val currentYear: Int = 2026,
    @SerializedName("attendance_data") val attendanceData: AttendanceData? = null
)

data class AttendanceData(
    @SerializedName("records") val records: List<AttendanceRecord> = emptyList(),
    @SerializedName("total_days") val totalDays: Int = 0,
    @SerializedName("present_count") val presentCount: Int = 0,
    @SerializedName("absent_count") val absentCount: Int = 0,
    @SerializedName("late_count") val lateCount: Int = 0,
    @SerializedName("excused_count") val excusedCount: Int = 0,
    @SerializedName("attendance_percentage") val attendancePercentage: Double = 0.0
)

data class AttendanceRecord(
    @SerializedName("id") val id: Int = 0,
    @SerializedName("student_id") val studentId: Int = 0,
    @SerializedName("date") val date: String = "",
    @SerializedName("status") val status: String = "present",
    @SerializedName("remarks") val remarks: String? = null,
    @SerializedName("admission_number") val admissionNumber: String? = null,
    @SerializedName("first_name") val firstName: String? = null,
    @SerializedName("last_name") val lastName: String? = null
) {
    val displayStatus: String
        get() = status.replaceFirstChar { it.uppercase() }
}
