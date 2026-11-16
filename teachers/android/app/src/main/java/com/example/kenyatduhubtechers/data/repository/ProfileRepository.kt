package com.example.kenyatduhubtechers.data.repository

import com.example.kenyatduhubtechers.data.local.SessionManager
import com.example.kenyatduhubtechers.data.model.ProfileResponse
import com.example.kenyatduhubtechers.data.model.UpdateProfileRequest
import com.example.kenyatduhubtechers.data.model.UpdateProfileResponse
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
                        Result.Success(profileResponse)
                    } else {
                        Result.Error(profileResponse?.error ?: "Failed to fetch profile data")
                    }
                } else {
                    Result.Error("Server error (${response.code()})")
                }
            } catch (e: Exception) {
                Result.Error(e.localizedMessage ?: "Network error occurred")
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
                        Result.Error(updateResponse?.error ?: "Failed to update profile")
                    }
                } else {
                    Result.Error("Server error (${response.code()})")
                }
            } catch (e: Exception) {
                Result.Error(e.localizedMessage ?: "Network error occurred")
            }
        }
    }
}
