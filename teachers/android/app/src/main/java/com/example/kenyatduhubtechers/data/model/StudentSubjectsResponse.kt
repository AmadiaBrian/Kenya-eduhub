package com.example.kenyatduhubtechers.data.model

import com.google.gson.annotations.SerializedName

data class StudentSubjectNamesDto(
    @SerializedName("student_id") val studentId: Int,
    @SerializedName("subject_names") val subjectNames: String? = null
)

data class StudentSubjectDetailDto(
    @SerializedName("subject_id") val subjectId: Int,
    @SerializedName("subject_name") val subjectName: String
)

data class StudentSubjectsListResponse(
    @SerializedName("success") val success: Boolean,
    @SerializedName("message") val message: String? = null,
    @SerializedName("data") val data: List<StudentSubjectNamesDto> = emptyList(),
    @SerializedName("error") val error: String? = null
)

data class StudentSubjectDetailsResponse(
    @SerializedName("success") val success: Boolean,
    @SerializedName("message") val message: String? = null,
    @SerializedName("data") val data: List<StudentSubjectDetailDto> = emptyList(),
    @SerializedName("error") val error: String? = null
)

data class AssignSubjectsRequest(
    @SerializedName("student_ids") val studentIds: List<Int>,
    @SerializedName("subject_id") val subjectId: Int
)

data class AssignSubjectsResponseData(
    @SerializedName("assigned_count") val assignedCount: Int = 0,
    @SerializedName("violations") val violations: List<String>? = null
)

data class AssignSubjectsResponse(
    @SerializedName("success") val success: Boolean,
    @SerializedName("message") val message: String? = null,
    @SerializedName("data") val data: AssignSubjectsResponseData? = null,
    @SerializedName("error") val error: String? = null
)

data class GenericActionResponse(
    @SerializedName("success") val success: Boolean,
    @SerializedName("message") val message: String? = null,
    @SerializedName("error") val error: String? = null
)
