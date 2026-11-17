package com.example.kenyatduhubtechers.data.repository

import com.example.kenyatduhubtechers.data.local.LocalCacheManager
import com.example.kenyatduhubtechers.data.local.SessionManager
import com.example.kenyatduhubtechers.data.model.ProfileResponse
import com.example.kenyatduhubtechers.data.model.UpdateProfileRequest
import com.example.kenyatduhubtechers.data.model.UpdateProfileResponse
import com.example.kenyatduhubtechers.data.network.ApiConfig
import com.example.kenyatduhubtechers.data.network.RetrofitClient
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext

class ProfileRepository(private val sessionManager: SessionManager) {

    init {
        RetrofitClient.initialize(sessionManager)
    }

    suspend fun getProfileData(): Result<ProfileResponse> {
        return withContext(Dispatchers.IO) {
            try {
                val apiService = RetrofitClient.getApiService()
                val response = apiService.getProfile()

                if (response.isSuccessful) {
                    val profileResponse = response.body()
                    if (profileResponse != null && profileResponse.success) {
                        sessionManager.cacheManager.saveCache(LocalCacheManager.KEY_PROFILE, profileResponse)
                        Result.Success(profileResponse)
                    } else {
                        val cached = sessionManager.cacheManager.getCache(LocalCacheManager.KEY_PROFILE, ProfileResponse::class.java)
                        if (cached != null) {
                            Result.Success(cached)
                        } else {
                            Result.Error(profileResponse?.error ?: ApiConfig.NETWORK_ERROR_MSG)
                        }
                    }
                } else {
                    val cached = sessionManager.cacheManager.getCache(LocalCacheManager.KEY_PROFILE, ProfileResponse::class.java)
                    if (cached != null) {
                        Result.Success(cached)
                    } else {
                        Result.Error(ApiConfig.NETWORK_ERROR_MSG)
                    }
                }
            } catch (e: Exception) {
                val cached = sessionManager.cacheManager.getCache(LocalCacheManager.KEY_PROFILE, ProfileResponse::class.java)
                if (cached != null) {
                    Result.Success(cached)
                } else {
                    Result.Error(ApiConfig.NETWORK_ERROR_MSG)
                }
            }
        }
    }

    suspend fun updateProfileData(request: UpdateProfileRequest): Result<UpdateProfileResponse> {
        return withContext(Dispatchers.IO) {
            try {
                val apiService = RetrofitClient.getApiService()
                val response = apiService.updateProfile(request)

                if (response.isSuccessful) {
                    val updateResponse = response.body()
                    if (updateResponse != null && updateResponse.success) {
                        updateResponse.teacher?.let { updatedTeacher ->
                            sessionManager.saveSession(sessionManager.getSessionToken() ?: "", updatedTeacher)
                        }
                        Result.Success(updateResponse)
                    } else {
                        Result.Error(updateResponse?.error ?: ApiConfig.NETWORK_ERROR_MSG)
                    }
                } else {
                    Result.Error(ApiConfig.NETWORK_ERROR_MSG)
                }
            } catch (e: Exception) {
                Result.Error(ApiConfig.NETWORK_ERROR_MSG)
            }
        }
    }
}
