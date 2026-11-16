package com.example.kenyatduhubtechers.data.repository

import com.example.kenyatduhubtechers.data.local.SessionManager
import com.example.kenyatduhubtechers.data.model.CalendarResponse
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
                        Result.Success(calendarResponse)
                    } else {
                        Result.Error(calendarResponse?.error ?: "Failed to fetch calendar data")
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
