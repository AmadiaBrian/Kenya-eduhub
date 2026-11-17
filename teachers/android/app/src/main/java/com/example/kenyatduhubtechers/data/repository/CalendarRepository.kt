package com.example.kenyatduhubtechers.data.repository

import com.example.kenyatduhubtechers.data.local.LocalCacheManager
import com.example.kenyatduhubtechers.data.local.SessionManager
import com.example.kenyatduhubtechers.data.model.CalendarResponse
import com.example.kenyatduhubtechers.data.network.ApiConfig
import com.example.kenyatduhubtechers.data.network.RetrofitClient
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext

class CalendarRepository(private val sessionManager: SessionManager) {

    init {
        RetrofitClient.initialize(sessionManager)
    }

    suspend fun getCalendarData(): Result<CalendarResponse> {
        return withContext(Dispatchers.IO) {
            try {
                val apiService = RetrofitClient.getApiService()
                val response = apiService.getCalendar()

                if (response.isSuccessful) {
                    val calendarResponse = response.body()
                    if (calendarResponse != null && calendarResponse.success) {
                        sessionManager.cacheManager.saveCache(LocalCacheManager.KEY_CALENDAR, calendarResponse)
                        Result.Success(calendarResponse)
                    } else {
                        val cached = sessionManager.cacheManager.getCache(LocalCacheManager.KEY_CALENDAR, CalendarResponse::class.java)
                        if (cached != null) {
                            Result.Success(cached)
                        } else {
                            Result.Error(calendarResponse?.error ?: ApiConfig.NETWORK_ERROR_MSG)
                        }
                    }
                } else {
                    val cached = sessionManager.cacheManager.getCache(LocalCacheManager.KEY_CALENDAR, CalendarResponse::class.java)
                    if (cached != null) {
                        Result.Success(cached)
                    } else {
                        Result.Error(ApiConfig.NETWORK_ERROR_MSG)
                    }
                }
            } catch (e: Exception) {
                val cached = sessionManager.cacheManager.getCache(LocalCacheManager.KEY_CALENDAR, CalendarResponse::class.java)
                if (cached != null) {
                    Result.Success(cached)
                } else {
                    Result.Error(ApiConfig.NETWORK_ERROR_MSG)
                }
            }
        }
    }
}
