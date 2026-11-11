package com.example.kenyaeduhubparents.data.repository

import com.example.kenyaeduhubparents.data.local.SessionManager
import com.example.kenyaeduhubparents.data.model.ParentProfile
import com.example.kenyaeduhubparents.data.model.ParentProfileResponse
import com.example.kenyaeduhubparents.data.model.UpdateParentProfileRequest
import com.example.kenyaeduhubparents.data.network.ApiConfig
import com.example.kenyaeduhubparents.data.network.NetworkUtils
import com.example.kenyaeduhubparents.data.network.RetrofitClient
import com.google.gson.Gson
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext

class ProfileRepository(private val sessionManager: SessionManager) {
    private val gson = Gson()

    suspend fun getProfile(): Result<ParentProfile> = requestProfile {
        it.getParentProfile(sessionCookie())
    }

    suspend fun updateProfile(
        firstName: String,
        lastName: String,
        phone: String,
        address: String
    ): Result<ParentProfile> = requestProfile {
        it.updateParentProfile(
            sessionCookie(),
            UpdateParentProfileRequest(firstName, lastName, phone, address)
        )
    }

    private suspend fun requestProfile(
        request: suspend (com.example.kenyaeduhubparents.data.network.ApiService) ->
            retrofit2.Response<ParentProfileResponse>
    ): Result<ParentProfile> = withContext(Dispatchers.IO) {
        try {
            val response = request(RetrofitClient.getApiService(ApiConfig.BASE_URL))
            if (!response.isSuccessful) {
                checkCache<ParentProfile>("profile")?.let { return@withContext Result.success(it) }
                return@withContext Result.failure(
                    Exception(NetworkUtils.parseHttpError(response.code(), response.message()))
                )
            }

            val body = response.body()
            if (body == null || !body.success || body.parent == null) {
                checkCache<ParentProfile>("profile")?.let { return@withContext Result.success(it) }
                return@withContext Result.failure(Exception("Network error. Please try again."))
            }

            val profile = body.parent
            sessionManager.saveCachedJson("profile", gson.toJson(profile))
            sessionManager.saveParentData(profile.toParent(sessionManager.getParentData()))
            Result.success(profile)
        } catch (e: Exception) {
            checkCache<ParentProfile>("profile")?.let { return@withContext Result.success(it) }
            Result.failure(Exception(NetworkUtils.parseException(e)))
        }
    }

    private inline fun <reified T> checkCache(key: String): T? {
        return try {
            val cachedJson = sessionManager.getCachedJson(key)
            if (!cachedJson.isNullOrBlank()) {
                gson.fromJson(cachedJson, T::class.java)
            } else {
                null
            }
        } catch (_: Exception) {
            null
        }
    }

    private fun sessionCookie(): String {
        val sessionId = sessionManager.getSessionId()
        require(!sessionId.isNullOrBlank()) {
            "Your session is unavailable. Please sign out and sign in again."
        }
        return "${ApiConfig.PHP_SESSION_COOKIE}=$sessionId"
    }
}
