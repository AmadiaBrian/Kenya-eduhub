package com.example.kenyatduhubtechers.data.repository

import com.example.kenyatduhubtechers.data.local.LocalCacheManager
import com.example.kenyatduhubtechers.data.local.SessionManager
import com.example.kenyatduhubtechers.data.model.TimetableResponse
import com.example.kenyatduhubtechers.data.network.ApiConfig
import com.example.kenyatduhubtechers.data.network.RetrofitClient
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext

class TimetableRepository(private val sessionManager: SessionManager) {

    init {
        RetrofitClient.initialize(sessionManager)
    }

    suspend fun getTimetableData(): Result<TimetableResponse> {
        return withContext(Dispatchers.IO) {
            try {
                val apiService = RetrofitClient.getApiService()
                val response = apiService.getTimetable()

                if (response.isSuccessful) {
                    val timetableResponse = response.body()
                    if (timetableResponse != null && timetableResponse.success) {
                        sessionManager.cacheManager.saveCache(LocalCacheManager.KEY_TIMETABLE, timetableResponse)
                        Result.Success(timetableResponse)
                    } else {
                        val cached = sessionManager.cacheManager.getCache(LocalCacheManager.KEY_TIMETABLE, TimetableResponse::class.java)
                        if (cached != null) {
                            Result.Success(cached)
                        } else {
                            Result.Error(timetableResponse?.error ?: ApiConfig.NETWORK_ERROR_MSG)
                        }
                    }
                } else {
                    val cached = sessionManager.cacheManager.getCache(LocalCacheManager.KEY_TIMETABLE, TimetableResponse::class.java)
                    if (cached != null) {
                        Result.Success(cached)
                    } else {
                        Result.Error(ApiConfig.NETWORK_ERROR_MSG)
                    }
                }
            } catch (e: Exception) {
                val cached = sessionManager.cacheManager.getCache(LocalCacheManager.KEY_TIMETABLE, TimetableResponse::class.java)
                if (cached != null) {
                    Result.Success(cached)
                } else {
                    Result.Error(ApiConfig.NETWORK_ERROR_MSG)
                }
            }
        }
    }
}
