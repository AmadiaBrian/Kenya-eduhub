package com.example.kenyaeduhubparents.data.model

import com.google.gson.annotations.SerializedName

data class LoginRequest(
    @SerializedName("email") val email: String,
    @SerializedName("identifier") val identifier: String
)
