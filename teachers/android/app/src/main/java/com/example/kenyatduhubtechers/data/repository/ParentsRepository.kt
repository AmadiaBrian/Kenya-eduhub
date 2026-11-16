package com.example.kenyatduhubtechers.data.repository

import com.example.kenyatduhubtechers.data.local.SessionManager
import com.example.kenyatduhubtechers.data.model.ParentsResponse
import com.example.kenyatduhubtechers.data.model.SendParentEmailRequest
import com.example.kenyatduhubtechers.data.model.SendParentEmailResponse
import com.example.kenyatduhubtechers.data.network.RetrofitClient
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext

class ParentsRepository(private val sessionManager: SessionManager) {

    init {
        RetrofitClient.initialize(sessionManager)
    }

    suspend fun getParentsData(relationship: String? = null): Result<ParentsResponse> {
        return withContext(Dispatchers.IO) {
            try {
                val apiService = RetrofitClient.getApiService()
                val response = apiService.getParents(relationship)

                if (response.isSuccessful) {
                    val parentsResponse = response.body()
                    if (parentsResponse != null && parentsResponse.success) {
                        Result.Success(parentsResponse)
                    } else {
                        Result.Error(parentsResponse?.error ?: "Failed to fetch parents data")
                    }
                } else {
                    Result.Error("Server error (${response.code()})")
                }
            } catch (e: Exception) {
                Result.Error(e.localizedMessage ?: "Network error occurred")
            }
        }
    }

    suspend fun sendParentEmail(request: SendParentEmailRequest): Result<SendParentEmailResponse> {
        return withContext(Dispatchers.IO) {
            try {
                val apiService = RetrofitClient.getApiService()
                val response = apiService.sendParentEmail(request)

                if (response.isSuccessful) {
                    val sendResponse = response.body()
                    if (sendResponse != null && sendResponse.success) {
                        Result.Success(sendResponse)
                    } else {
                        Result.Error(sendResponse?.error ?: "Failed to send email")
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
