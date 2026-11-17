package com.example.kenyatduhubtechers.data.repository

import com.example.kenyatduhubtechers.data.local.LocalCacheManager
import com.example.kenyatduhubtechers.data.local.SessionManager
import com.example.kenyatduhubtechers.data.model.FeesResponse
import com.example.kenyatduhubtechers.data.model.SendFeeReminderRequest
import com.example.kenyatduhubtechers.data.model.SendFeeReminderResponse
import com.example.kenyatduhubtechers.data.model.StatementResponse
import com.example.kenyatduhubtechers.data.model.StudentStatementRequest
import com.example.kenyatduhubtechers.data.network.ApiConfig
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
                        sessionManager.cacheManager.saveCache(LocalCacheManager.KEY_FEES, feesResponse)
                        Result.Success(feesResponse)
                    } else {
                        val cached = sessionManager.cacheManager.getCache(LocalCacheManager.KEY_FEES, FeesResponse::class.java)
                        if (cached != null) {
                            Result.Success(cached)
                        } else {
                            Result.Error(feesResponse?.error ?: ApiConfig.NETWORK_ERROR_MSG)
                        }
                    }
                } else {
                    val cached = sessionManager.cacheManager.getCache(LocalCacheManager.KEY_FEES, FeesResponse::class.java)
                    if (cached != null) {
                        Result.Success(cached)
                    } else {
                        Result.Error(ApiConfig.NETWORK_ERROR_MSG)
                    }
                }
            } catch (e: Exception) {
                val cached = sessionManager.cacheManager.getCache(LocalCacheManager.KEY_FEES, FeesResponse::class.java)
                if (cached != null) {
                    Result.Success(cached)
                } else {
                    Result.Error(ApiConfig.NETWORK_ERROR_MSG)
                }
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
                        Result.Error(reminderResponse?.error ?: ApiConfig.NETWORK_ERROR_MSG)
                    }
                } else {
                    Result.Error(ApiConfig.NETWORK_ERROR_MSG)
                }
            } catch (e: Exception) {
                Result.Error(ApiConfig.NETWORK_ERROR_MSG)
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
                        Result.Error(stmtResponse?.error ?: ApiConfig.NETWORK_ERROR_MSG)
                    }
                } else {
                    Result.Error(ApiConfig.NETWORK_ERROR_MSG)
                }
            } catch (e: Exception) {
                Result.Error(ApiConfig.NETWORK_ERROR_MSG)
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
                        Result.Error(stmtResponse?.error ?: ApiConfig.NETWORK_ERROR_MSG)
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
