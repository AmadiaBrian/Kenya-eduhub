package com.example.kenyaeduhubparents.data.repository

import com.example.kenyaeduhubparents.data.local.SessionManager
import com.example.kenyaeduhubparents.data.model.LoginRequest
import com.example.kenyaeduhubparents.data.model.LoginResponse
import com.example.kenyaeduhubparents.data.model.Parent
import com.example.kenyaeduhubparents.data.model.ParentProfile
import com.example.kenyaeduhubparents.data.network.ApiConfig
import com.example.kenyaeduhubparents.data.network.NetworkUtils
import com.example.kenyaeduhubparents.data.network.RetrofitClient
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext

class AuthRepository(private val sessionManager: SessionManager) {

    suspend fun login(email: String, identifier: String): Result<LoginResponse> {
        return withContext(Dispatchers.IO) {
            try {
                val apiService = RetrofitClient.getApiService(ApiConfig.BASE_URL)
                val response = apiService.login(LoginRequest(email = email, identifier = identifier))

                if (response.isSuccessful) {
                    val body = response.body()
                    if (body != null) {
                        if (body.success) {
                            var sessionId = body.sessionId
                            val cookies = response.headers().values("Set-Cookie")
                            for (cookie in cookies) {
                                if (cookie.contains("PHPSESSID=")) {
                                    sessionId = cookie.substringAfter("PHPSESSID=").substringBefore(";")
                                    break
                                }
                            }

                            sessionManager.saveSession(
                                sessionToken = body.sessionToken ?: body.csrfToken,
                                sessionId = sessionId,
                                parent = body.activeParent
                            )
                        }
                        Result.success(body)
                    } else {
                        Result.failure(Exception("Network error. Please try again."))
                    }
                } else {
                    Result.failure(Exception(NetworkUtils.parseHttpError(response.code(), response.message())))
                }
            } catch (e: Exception) {
                Result.failure(Exception(NetworkUtils.parseException(e)))
            }
        }
    }

    fun isLoggedIn(): Boolean = sessionManager.isLoggedIn()

    fun getParentData() = sessionManager.getParentData()

    fun updateParentData(profile: ParentProfile): Parent {
        val parent = profile.toParent(sessionManager.getParentData())
        sessionManager.saveParentData(parent)
        return parent
    }

    fun logout() = sessionManager.clearSession()
}
