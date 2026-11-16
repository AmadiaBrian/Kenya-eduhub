package com.example.kenyatduhubtechers.data.repository

import com.example.kenyatduhubtechers.data.local.SessionManager
import com.example.kenyatduhubtechers.data.model.DashboardResponse
import com.example.kenyatduhubtechers.data.network.RetrofitClient
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext

class DashboardRepository(private val sessionManager: SessionManager) {

    init {
        RetrofitClient.initialize(sessionManager)
    }

    suspend fun getDashboardData(): Result<DashboardResponse> {
        return withContext(Dispatchers.IO) {
            try {
                val apiService = RetrofitClient.getApiService()
                val response = apiService.getDashboard()

                if (response.isSuccessful) {
                    val dashboardResponse = response.body()
                    if (dashboardResponse != null && dashboardResponse.success) {
                        Result.Success(dashboardResponse)
                    } else {
                        Result.Error(dashboardResponse?.error ?: "Failed to fetch dashboard data")
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
