package com.example.kenyatduhubtechers.data.model

import com.google.gson.annotations.SerializedName

data class ParentItemDto(
    @SerializedName("id") val id: Int,
    @SerializedName("first_name") val firstName: String,
    @SerializedName("last_name") val lastName: String,
    @SerializedName("email") val email: String,
    @SerializedName("phone") val phone: String,
    @SerializedName("id_number") val idNumber: String,
    @SerializedName("relationship") val relationship: String? = "Guardian",
    @SerializedName("address") val address: String? = null,
    @SerializedName("linked_students") val linkedStudents: String? = "No students linked"
) {
    val fullName: String
        get() = "$firstName $lastName".trim()
}

data class ParentsResponse(
    @SerializedName("success") val success: Boolean,
    @SerializedName("data") val data: List<ParentItemDto> = emptyList(),
    @SerializedName("error") val error: String? = null
)

data class SendParentEmailRequest(
    @SerializedName("recipient") val recipient: String = "all",
    @SerializedName("subject") val subject: String,
    @SerializedName("body") val body: String
)

data class SendParentEmailResponse(
    @SerializedName("success") val success: Boolean,
    @SerializedName("message") val message: String? = null,
    @SerializedName("error") val error: String? = null
)
