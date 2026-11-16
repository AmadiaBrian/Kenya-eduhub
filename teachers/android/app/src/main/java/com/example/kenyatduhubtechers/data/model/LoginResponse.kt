package com.example.kenyatduhubtechers.data.model

import com.google.gson.annotations.SerializedName

data class LoginResponse(
    @SerializedName("success")
    val success: Boolean,
    @SerializedName("session_token")
    val sessionToken: String? = null,
    @SerializedName("teacher")
    val teacher: TeacherDto? = null,
    @SerializedName("error")
    val error: String? = null
)
