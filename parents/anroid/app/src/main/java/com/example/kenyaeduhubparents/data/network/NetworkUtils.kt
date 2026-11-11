package com.example.kenyaeduhubparents.data.network

object NetworkUtils {
    fun parseHttpError(code: Int, message: String): String {
        return "Network error. Please check your internet connection and try again."
    }

    fun parseException(e: Exception): String {
        return "Network error. Please check your internet connection and try again."
    }
}
