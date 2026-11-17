package com.example.kenyatduhubtechers.data.repository

import com.example.kenyatduhubtechers.data.local.SessionManager
import com.example.kenyatduhubtechers.data.model.LoginRequest
import com.example.kenyatduhubtechers.data.model.LoginResponse
import com.example.kenyatduhubtechers.data.model.TeacherDto
import com.example.kenyatduhubtechers.data.network.ApiConfig
import com.example.kenyatduhubtechers.data.network.RetrofitClient
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext

sealed class Result<out T> {
    data class Success<out T>(val data: T) : Result<T>()
    data class Error(val message: String) : Result<Nothing>()
}

class AuthRepository(private val sessionManager: SessionManager) {

    suspend fun login(email: String, password: String): Result<LoginResponse> {
        return withContext(Dispatchers.IO) {
            try {
                val apiService = RetrofitClient.getApiService(sessionManager.getBaseUrl())
                val response = apiService.login(LoginRequest(email.trim(), password))

                if (response.isSuccessful) {
                    val loginResponse = response.body()
                    if (loginResponse != null && loginResponse.success) {
                        val token = loginResponse.sessionToken ?: ""
                        sessionManager.saveSession(token, loginResponse.teacher)
                        Result.Success(loginResponse)
                    } else {
                        val errorMsg = loginResponse?.error ?: "Invalid email or password"
                        Result.Error(errorMsg)
                    }
                } else {
                    Result.Error(ApiConfig.NETWORK_ERROR_MSG)
                }
            } catch (e: Exception) {
                Result.Error(ApiConfig.NETWORK_ERROR_MSG)
            }
        }
    }

    fun isLoggedIn(): Boolean = sessionManager.isLoggedIn()

    fun getTeacherInfo(): TeacherDto = sessionManager.getTeacherInfo()

    fun logout() = sessionManager.clearSession()

    fun getBaseUrl(): String = sessionManager.getBaseUrl()

    fun saveBaseUrl(url: String) = sessionManager.saveBaseUrl(url)
}
