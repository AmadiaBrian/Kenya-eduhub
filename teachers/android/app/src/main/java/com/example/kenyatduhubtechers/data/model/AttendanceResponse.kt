package com.example.kenyatduhubtechers.data.model

import com.google.gson.annotations.SerializedName

data class AttendanceStudentDto(
    @SerializedName("id") val id: Int,
    @SerializedName("admission_number") val admissionNumber: String,
    @SerializedName("first_name") val firstName: String,
    @SerializedName("last_name") val lastName: String,
    @SerializedName("stream_id") val streamId: Int? = null,
    @SerializedName("stream_name") val streamName: String? = null,
    @SerializedName("status") var status: String? = null,
    @SerializedName("remarks") var remarks: String? = null
)

data class StudentsForAttendanceResponse(
    @SerializedName("success") val success: Boolean,
    @SerializedName("date") val date: String? = null,
    @SerializedName("students") val students: List<AttendanceStudentDto> = emptyList(),
    @SerializedName("error") val error: String? = null
)

data class SaveAttendanceItem(
    @SerializedName("student_id") val studentId: Int,
    @SerializedName("date") val date: String,
    @SerializedName("status") val status: String,
    @SerializedName("remarks") val remarks: String? = null
)

data class SaveAttendanceRequest(
    @SerializedName("action") val action: String = "save_attendance",
    @SerializedName("class_id") val classId: Int,
    @SerializedName("date") val date: String,
    @SerializedName("attendance") val attendance: List<SaveAttendanceItem>
)

data class AttendanceHistoryItemDto(
    @SerializedName("attendance_date") val attendanceDate: String? = null,
    @SerializedName("status") val status: String? = null,
    @SerializedName("count") val count: Int = 0
)

data class AttendanceHistoryResponse(
    @SerializedName("success") val success: Boolean,
    @SerializedName("history") val history: List<AttendanceHistoryItemDto> = emptyList(),
    @SerializedName("error") val error: String? = null
)

data class TeacherSubjectDto(
    @SerializedName("id") val id: Int? = null,
    @SerializedName("teacher_id") val teacherId: Int? = null,
    @SerializedName("class_id") val classId: Int? = null,
    @SerializedName("subject_id") val subjectId: Int? = null,
    @SerializedName("class_name") val className: String? = null,
    @SerializedName("subject_name") val subjectName: String? = null
)

data class AttendanceMainResponse(
    @SerializedName("success") val success: Boolean,
    @SerializedName("teacher") val teacher: TeacherDto? = null,
    @SerializedName("calendar_status") val calendarStatus: CalendarStatusDto? = null,
    @SerializedName("classes") val classes: List<ClassDto> = emptyList(),
    @SerializedName("subject_assignments") val subjectAssignments: List<TeacherSubjectDto> = emptyList(),
    @SerializedName("error") val error: String? = null
)
