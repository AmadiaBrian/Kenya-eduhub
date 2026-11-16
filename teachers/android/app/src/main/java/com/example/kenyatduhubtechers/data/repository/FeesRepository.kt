package com.example.kenyatduhubtechers.data.repository

import com.example.kenyatduhubtechers.data.local.SessionManager
import com.example.kenyatduhubtechers.data.model.FeesResponse
import com.example.kenyatduhubtechers.data.model.SendFeeReminderRequest
import com.example.kenyatduhubtechers.data.model.SendFeeReminderResponse
import com.example.kenyatduhubtechers.data.model.StatementResponse
import com.example.kenyatduhubtechers.data.model.StudentStatementRequest
import com.example.kenyatduhubtechers.data.network.RetrofitClient
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext

class FeesRepository(private val sessionManager: SessionManager) {

    init {
        RetrofitClient.initialize(sessionManager)
    }

    suspend fun getFeesData(studentId: Int? = null): Result<FeesResponse> {
        return withContext(Dispatchers.IO) {
            try {
                val apiService = RetrofitClient.getApiService()
                val response = apiService.getFees(studentId)

                if (response.isSuccessful) {
                    val feesResponse = response.body()
                    if (feesResponse != null && feesResponse.success) {
                        Result.Success(feesResponse)
                    } else {
                        Result.Error(feesResponse?.error ?: "Failed to fetch fee data")
                    }
                } else {
                    Result.Error("Server error (${response.code()})")
                }
            } catch (e: Exception) {
                Result.Error(e.localizedMessage ?: "Network error occurred")
            }
        }
    }

    suspend fun sendFeeReminders(request: SendFeeReminderRequest): Result<SendFeeReminderResponse> {
        return withContext(Dispatchers.IO) {
            try {
                val apiService = RetrofitClient.getApiService()
                val response = apiService.sendFeeReminders(request)

                if (response.isSuccessful) {
                    val reminderResponse = response.body()
                    if (reminderResponse != null && reminderResponse.success) {
                        Result.Success(reminderResponse)
                    } else {
                        Result.Error(reminderResponse?.error ?: "Failed to send fee reminders")
                    }
                } else {
                    Result.Error("Server error (${response.code()})")
                }
            } catch (e: Exception) {
                Result.Error(e.localizedMessage ?: "Network error occurred")
            }
        }
    }

    suspend fun generateClassFeeStatement(): Result<StatementResponse> {
        return withContext(Dispatchers.IO) {
            try {
                val apiService = RetrofitClient.getApiService()
                val response = apiService.generateClassFeeStatement()

                if (response.isSuccessful) {
                    val stmtResponse = response.body()
                    if (stmtResponse != null && stmtResponse.success) {
                        Result.Success(stmtResponse)
                    } else {
                        Result.Error(stmtResponse?.error ?: "Failed to generate class statement")
                    }
                } else {
                    Result.Error("Server error (${response.code()})")
                }
            } catch (e: Exception) {
                Result.Error(e.localizedMessage ?: "Network error occurred")
            }
        }
    }

    suspend fun generateStudentFeeStatement(studentId: Int): Result<StatementResponse> {
        return withContext(Dispatchers.IO) {
            try {
                val apiService = RetrofitClient.getApiService()
                val response = apiService.generateStudentFeeStatement(StudentStatementRequest(studentId))

                if (response.isSuccessful) {
                    val stmtResponse = response.body()
                    if (stmtResponse != null && stmtResponse.success) {
                        Result.Success(stmtResponse)
                    } else {
                        Result.Error(stmtResponse?.error ?: "Failed to generate student statement")
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
