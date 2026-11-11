package com.example.kenyaeduhubparents.data.model

import com.google.gson.annotations.SerializedName

data class ParentProfileResponse(
    @SerializedName("success") val success: Boolean,
    @SerializedName("message") val message: String? = null,
    @SerializedName("error") val error: String? = null,
    @SerializedName("parent") val parent: ParentProfile? = null
)

data class ParentProfile(
    @SerializedName("id") val id: Int = 0,
    @SerializedName("first_name") val firstName: String = "",
    @SerializedName("last_name") val lastName: String = "",
    @SerializedName("email") val email: String = "",
    @SerializedName("phone") val phone: String = "",
    @SerializedName("address") val address: String = "",
    @SerializedName("id_number") val idNumber: String = "",
    @SerializedName("school_name") val schoolName: String = "",
    @SerializedName("created_at") val createdAt: String = ""
) {
    val fullName: String
        get() = listOf(firstName, lastName)
            .filter { it.isNotBlank() }
            .joinToString(" ")

    fun toParent(existing: Parent? = null) = Parent(
        id = id,
        name = fullName,
        email = email,
        phone = phone,
        role = existing?.role,
        schoolId = existing?.schoolId ?: 0,
        schoolName = schoolName.ifBlank { existing?.schoolName.orEmpty() }
    )
}

data class UpdateParentProfileRequest(
    @SerializedName("first_name") val firstName: String,
    @SerializedName("last_name") val lastName: String,
    @SerializedName("phone") val phone: String,
    @SerializedName("address") val address: String
)
