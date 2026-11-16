package com.example.kenyatduhubtechers.data.model

import com.google.gson.annotations.SerializedName

data class TermDto(
    @SerializedName("id") val id: Int? = null,
    @SerializedName("term_name") val termName: String? = null,
    @SerializedName("term_number") val termNumber: Int? = null,
    @SerializedName("year") val year: String? = null,
    @SerializedName("start_date") val startDate: String? = null,
    @SerializedName("end_date") val endDate: String? = null
)

data class HolidayDto(
    @SerializedName("id") val id: Int? = null,
    @SerializedName("holiday_name") val holidayName: String? = null,
    @SerializedName("description") val description: String? = null,
    @SerializedName("start_date") val startDate: String? = null,
    @SerializedName("end_date") val endDate: String? = null,
    @SerializedName("holiday_type") val holidayType: String? = null
)

data class CalendarStatusDto(
    @SerializedName("is_holiday") val isHoliday: Boolean = false,
    @SerializedName("school_status") val schoolStatus: String? = "in_session",
    @SerializedName("current_term") val currentTerm: TermDto? = null,
    @SerializedName("current_holiday") val currentHoliday: HolidayDto? = null,
    @SerializedName("current_year") val currentYear: String? = null
)

data class StatsDto(
    @SerializedName("total_students") val totalStudents: Int = 0,
    @SerializedName("attendance_today") val attendanceToday: Int = 0,
    @SerializedName("present_today") val presentToday: Int = 0,
    @SerializedName("attendance_rate") val attendanceRate: Double = 0.0,
    @SerializedName("performance_records") val performanceRecords: Int = 0
)

data class DashboardResponse(
    @SerializedName("success") val success: Boolean,
    @SerializedName("teacher") val teacher: TeacherDto? = null,
    @SerializedName("calendar_status") val calendarStatus: CalendarStatusDto? = null,
    @SerializedName("stats") val stats: StatsDto? = null,
    @SerializedName("error") val error: String? = null
)
