package com.example.kenyatduhubtechers.data.model

import com.google.gson.annotations.SerializedName

data class ProfileResponse(
    @SerializedName("success") val success: Boolean,
    @SerializedName("teacher") val teacher: TeacherDto? = null,
    @SerializedName("subjects") val subjects: List<SubjectDto> = emptyList(),
    @SerializedName("error") val error: String? = null
)

data class UpdateProfileRequest(
    @SerializedName("action") val action: String = "update_profile",
    @SerializedName("first_name") val firstName: String,
    @SerializedName("last_name") val lastName: String,
    @SerializedName("email") val email: String,
    @SerializedName("phone") val phone: String,
    @SerializedName("id_number") val idNumber: String,
    @SerializedName("address") val address: String,
    @SerializedName("subject") val subject: String
)

data class UpdateProfileResponse(
    @SerializedName("success") val success: Boolean,
    @SerializedName("message") val message: String? = null,
    @SerializedName("teacher") val teacher: TeacherDto? = null,
    @SerializedName("error") val error: String? = null
)
