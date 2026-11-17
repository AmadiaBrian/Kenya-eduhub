package com.example.kenyatduhubtechers.data.repository

import com.example.kenyatduhubtechers.data.local.LocalCacheManager
import com.example.kenyatduhubtechers.data.local.SessionManager
import com.example.kenyatduhubtechers.data.model.ParentsResponse
import com.example.kenyatduhubtechers.data.model.SendParentEmailRequest
import com.example.kenyatduhubtechers.data.model.SendParentEmailResponse
import com.example.kenyatduhubtechers.data.network.ApiConfig
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
                        sessionManager.cacheManager.saveCache(LocalCacheManager.KEY_PARENTS, parentsResponse)
                        Result.Success(parentsResponse)
                    } else {
                        val cached = sessionManager.cacheManager.getCache(LocalCacheManager.KEY_PARENTS, ParentsResponse::class.java)
                        if (cached != null) {
                            Result.Success(cached)
                        } else {
                            Result.Error(parentsResponse?.error ?: ApiConfig.NETWORK_ERROR_MSG)
                        }
                    }
                } else {
                    val cached = sessionManager.cacheManager.getCache(LocalCacheManager.KEY_PARENTS, ParentsResponse::class.java)
                    if (cached != null) {
                        Result.Success(cached)
                    } else {
                        Result.Error(ApiConfig.NETWORK_ERROR_MSG)
                    }
                }
            } catch (e: Exception) {
                val cached = sessionManager.cacheManager.getCache(LocalCacheManager.KEY_PARENTS, ParentsResponse::class.java)
                if (cached != null) {
                    Result.Success(cached)
                } else {
                    Result.Error(ApiConfig.NETWORK_ERROR_MSG)
                }
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
                        Result.Error(sendResponse?.error ?: ApiConfig.NETWORK_ERROR_MSG)
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
