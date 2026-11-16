package com.example.kenyatduhubtechers.data.repository

import com.example.kenyatduhubtechers.data.local.SessionManager
import com.example.kenyatduhubtechers.data.model.PerformanceResponse
import com.example.kenyatduhubtechers.data.model.SavePerformanceRequest
import com.example.kenyatduhubtechers.data.model.SavePerformanceResponse
import com.example.kenyatduhubtechers.data.model.StudentsResponse
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
                        Result.Success(perfResponse)
                    } else {
                        Result.Error(perfResponse?.error ?: "Failed to fetch performance data")
                    }
                } else {
                    Result.Error("Server error (${response.code()})")
                }
            } catch (e: Exception) {
                Result.Error(e.localizedMessage ?: "Network error occurred")
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
                        Result.Error(saveResponse?.error ?: "Failed to save performance")
                    }
                } else {
                    Result.Error("Server error (${response.code()})")
                }
            } catch (e: Exception) {
                Result.Error(e.localizedMessage ?: "Network error occurred")
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
                        Result.Error(studentsResponse?.error ?: "Failed to fetch students")
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
