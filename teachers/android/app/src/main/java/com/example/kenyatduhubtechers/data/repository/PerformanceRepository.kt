package com.example.kenyatduhubtechers.data.repository

import com.example.kenyatduhubtechers.data.local.LocalCacheManager
import com.example.kenyatduhubtechers.data.local.SessionManager
import com.example.kenyatduhubtechers.data.model.PerformanceResponse
import com.example.kenyatduhubtechers.data.model.SavePerformanceRequest
import com.example.kenyatduhubtechers.data.model.SavePerformanceResponse
import com.example.kenyatduhubtechers.data.model.StudentsResponse
import com.example.kenyatduhubtechers.data.network.ApiConfig
import com.example.kenyatduhubtechers.data.network.RetrofitClient
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext

class PerformanceRepository(private val sessionManager: SessionManager) {

    init {
        RetrofitClient.initialize(sessionManager)
    }

    suspend fun getPerformanceData(): Result<PerformanceResponse> {
        return withContext(Dispatchers.IO) {
            try {
                val apiService = RetrofitClient.getApiService()
                val response = apiService.getPerformance()

                if (response.isSuccessful) {
                    val perfResponse = response.body()
                    if (perfResponse != null && perfResponse.success) {
                        sessionManager.cacheManager.saveCache(LocalCacheManager.KEY_PERFORMANCE, perfResponse)
                        Result.Success(perfResponse)
                    } else {
                        val cached = sessionManager.cacheManager.getCache(LocalCacheManager.KEY_PERFORMANCE, PerformanceResponse::class.java)
                        if (cached != null) {
                            Result.Success(cached)
                        } else {
                            Result.Error(perfResponse?.error ?: ApiConfig.NETWORK_ERROR_MSG)
                        }
                    }
                } else {
                    val cached = sessionManager.cacheManager.getCache(LocalCacheManager.KEY_PERFORMANCE, PerformanceResponse::class.java)
                    if (cached != null) {
                        Result.Success(cached)
                    } else {
                        Result.Error(ApiConfig.NETWORK_ERROR_MSG)
                    }
                }
            } catch (e: Exception) {
                val cached = sessionManager.cacheManager.getCache(LocalCacheManager.KEY_PERFORMANCE, PerformanceResponse::class.java)
                if (cached != null) {
                    Result.Success(cached)
                } else {
                    Result.Error(ApiConfig.NETWORK_ERROR_MSG)
                }
            }
        }
    }

    suspend fun savePerformanceData(request: SavePerformanceRequest): Result<SavePerformanceResponse> {
        return withContext(Dispatchers.IO) {
            try {
                val apiService = RetrofitClient.getApiService()
                val response = apiService.savePerformance(request)

                if (response.isSuccessful) {
                    val saveResponse = response.body()
                    if (saveResponse != null && saveResponse.success) {
                        Result.Success(saveResponse)
                    } else {
                        Result.Error(saveResponse?.error ?: ApiConfig.NETWORK_ERROR_MSG)
                    }
                } else {
                    Result.Error(ApiConfig.NETWORK_ERROR_MSG)
                }
            } catch (e: Exception) {
                Result.Error(ApiConfig.NETWORK_ERROR_MSG)
            }
        }
    }

    suspend fun getStudents(classId: Int? = null, streamId: Int? = null): Result<StudentsResponse> {
        return withContext(Dispatchers.IO) {
            try {
                val apiService = RetrofitClient.getApiService()
                val response = apiService.getStudents(classId, streamId)

                if (response.isSuccessful) {
                    val studentsResponse = response.body()
                    if (studentsResponse != null && studentsResponse.success) {
                        Result.Success(studentsResponse)
                    } else {
                        Result.Error(studentsResponse?.error ?: ApiConfig.NETWORK_ERROR_MSG)
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
