package com.example.kenyatduhubtechers.data.model

import com.google.gson.annotations.SerializedName

data class StudentItemDto(
    @SerializedName("id") val id: Int,
    @SerializedName("admission_number") val admissionNumber: String,
    @SerializedName("first_name") val firstName: String,
    @SerializedName("last_name") val lastName: String,
    @SerializedName("gender") val gender: String? = null,
    @SerializedName("class_id") val classId: Int? = null,
    @SerializedName("stream_id") val streamId: Int? = null,
    @SerializedName("class_name") val className: String? = null,
    @SerializedName("stream_name") val streamName: String? = null
)

data class StudentsResponse(
    @SerializedName("success") val success: Boolean,
    @SerializedName("data") val data: List<StudentItemDto> = emptyList(),
    @SerializedName("error") val error: String? = null
)
