package com.example.kenyatduhubtechers.data.repository

import com.example.kenyatduhubtechers.data.local.SessionManager
import com.example.kenyatduhubtechers.data.model.StudentsAffairsResponse
import com.example.kenyatduhubtechers.data.network.RetrofitClient
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext

class StudentsRepository(private val sessionManager: SessionManager) {

    init {
        RetrofitClient.initialize(sessionManager)
    }

    suspend fun getStudentsAffairsData(
        classId: Int? = null,
        streamId: Int? = null,
        status: String? = null
    ): Result<StudentsAffairsResponse> {
        return withContext(Dispatchers.IO) {
            try {
                val apiService = RetrofitClient.getApiService()
                val response = apiService.getStudentsAffairs(classId, streamId, status)

                if (response.isSuccessful) {
                    val studentsResponse = response.body()
                    if (studentsResponse != null && studentsResponse.success) {
                        Result.Success(studentsResponse)
                    } else {
                        Result.Error(studentsResponse?.error ?: "Failed to fetch student affairs data")
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
