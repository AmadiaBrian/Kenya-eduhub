package com.example.kenyatduhubtechers.data.model

import com.google.gson.annotations.SerializedName

data class AssignmentItemDto(
    @SerializedName("id") val id: Int,
    @SerializedName("title") val title: String,
    @SerializedName("description") val description: String? = null,
    @SerializedName("assignment_type") val assignmentType: String,
    @SerializedName("file_name") val fileName: String? = null,
    @SerializedName("file_path") val filePath: String? = null,
    @SerializedName("class_id") val classId: Int? = null,
    @SerializedName("subject_id") val subjectId: Int? = null,
    @SerializedName("class_name") val className: String? = null,
    @SerializedName("subject_name") val subjectName: String? = null,
    @SerializedName("teacher_name") val teacherName: String? = null,
    @SerializedName("due_date") val dueDate: String? = null,
    @SerializedName("created_at") val createdAt: String? = null,
    @SerializedName("download_count") val downloadCount: Int = 0,
    @SerializedName("comment_count") val commentCount: Int = 0
)

data class AssignmentsResponse(
    @SerializedName("success") val success: Boolean,
    @SerializedName("teacher") val teacher: TeacherDto? = null,
    @SerializedName("calendar_status") val calendarStatus: CalendarStatusDto? = null,
    @SerializedName("classes") val classes: List<ClassDto> = emptyList(),
    @SerializedName("subjects") val subjects: List<SubjectDto> = emptyList(),
    @SerializedName("assignments") val assignments: List<AssignmentItemDto> = emptyList(),
    @SerializedName("error") val error: String? = null
)

data class DuplicateAssignmentRequest(
    @SerializedName("assignment_id") val assignmentId: Int
)

data class UpdateAssignmentRequest(
    @SerializedName("assignment_id") val assignmentId: Int,
    @SerializedName("title") val title: String,
    @SerializedName("description") val description: String? = null,
    @SerializedName("due_date") val dueDate: String? = null
)
