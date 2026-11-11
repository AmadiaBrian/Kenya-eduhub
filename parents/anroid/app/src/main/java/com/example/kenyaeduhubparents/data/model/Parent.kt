package com.example.kenyaeduhubparents.data.model

import com.google.gson.annotations.SerializedName

data class Parent(
    @SerializedName("id") val id: Int = 0,
    @SerializedName("name") val name: String = "",
    @SerializedName("email") val email: String = "",
    @SerializedName("phone") val phone: String = "",
    @SerializedName("role") val role: String? = null,
    @SerializedName("school_id") val schoolId: Int = 0,
    @SerializedName("school_name") val schoolName: String = ""
)
