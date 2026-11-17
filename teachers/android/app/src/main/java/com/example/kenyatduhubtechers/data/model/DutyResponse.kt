package com.example.kenyatduhubtechers.data.model

import com.google.gson.annotations.SerializedName

data class DutyAssignmentDto(
    @SerializedName("id") val id: Int? = null,
    @SerializedName("week_start") val weekStart: String? = null,
    @SerializedName("week_end") val weekEnd: String? = null,
    @SerializedName("duty_type") val dutyType: String? = null
)

data class DutyStudentDto(
    @SerializedName("id") val id: Int,
    @SerializedName("first_name") val firstName: String,
    @SerializedName("last_name") val lastName: String,
    @SerializedName("admission_number") val admissionNumber: String? = null
)

data class LeaveoutChitDto(
    @SerializedName("id") val id: Int? = null,
    @SerializedName("student_id") val studentId: Int,
    @SerializedName("first_name") val firstName: String? = null,
    @SerializedName("last_name") val lastName: String? = null,
    @SerializedName("admission_number") val admissionNumber: String? = null,
    @SerializedName("reason") val reason: String,
    @SerializedName("created_at") val createdAt: String? = null
)

data class IncidentReportDto(
    @SerializedName("id") val id: Int? = null,
    @SerializedName("incident_type") val incidentType: String,
    @SerializedName("description") val description: String,
    @SerializedName("severity") val severity: String,
    @SerializedName("created_at") val createdAt: String? = null
)

data class CleanlinessCheckDto(
    @SerializedName("id") val id: Int? = null,
    @SerializedName("area") val area: String,
    @SerializedName("status") val status: String,
    @SerializedName("notes") val notes: String? = null,
    @SerializedName("created_at") val createdAt: String? = null
)

data class DutyDataDto(
    @SerializedName("teacher") val teacher: TeacherDto? = null,
    @SerializedName("current_duty") val currentDuty: DutyAssignmentDto? = null,
    @SerializedName("students") val students: List<DutyStudentDto> = emptyList(),
    @SerializedName("leaveout_chits") val leaveoutChits: List<LeaveoutChitDto> = emptyList(),
    @SerializedName("incidents") val incidents: List<IncidentReportDto> = emptyList(),
    @SerializedName("cleanliness_checks") val cleanlinessChecks: List<CleanlinessCheckDto> = emptyList()
)

data class DutyResponse(
    @SerializedName("success") val success: Boolean,
    @SerializedName("message") val message: String? = null,
    @SerializedName("data") val data: DutyDataDto? = null,
    @SerializedName("error") val error: String? = null
)

data class CreateLeaveoutChitRequest(
    @SerializedName("action") val action: String = "create_leaveout",
    @SerializedName("student_id") val studentId: Int,
    @SerializedName("reason") val reason: String
)

data class ReportIncidentRequest(
    @SerializedName("action") val action: String = "report_incident",
    @SerializedName("incident_type") val incidentType: String,
    @SerializedName("description") val description: String,
    @SerializedName("severity") val severity: String
)

data class RecordCleanlinessRequest(
    @SerializedName("action") val action: String = "record_cleanliness",
    @SerializedName("area") val area: String,
    @SerializedName("status") val status: String,
    @SerializedName("notes") val notes: String
)
