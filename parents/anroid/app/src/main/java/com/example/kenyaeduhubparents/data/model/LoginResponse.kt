package com.example.kenyaeduhubparents.data.model

import com.google.gson.annotations.SerializedName

data class User(
    @SerializedName("id") val id: Int = 0,
    @SerializedName("name") val name: String = "",
    @SerializedName("email") val email: String = "",
    @SerializedName("role") val role: String? = null
) {
    fun toParent(): Parent = Parent(
        id = id,
        name = name,
        email = email,
        phone = "",
        role = role ?: "parent",
        schoolId = 0,
        schoolName = ""
    )
}

data class LoginResponse(
    @SerializedName("success") val success: Boolean,
    @SerializedName("message") val message: String? = null,
    @SerializedName("error") val error: String? = null,
    @SerializedName("session_id") val sessionId: String? = null,
    @SerializedName("session_token") val sessionToken: String? = null,
    @SerializedName("csrf_token") val csrfToken: String? = null,
    @SerializedName("user") val user: User? = null,
    @SerializedName("parent") val parent: Parent? = null
) {
    val activeParent: Parent?
        get() = parent ?: user?.toParent()
}
