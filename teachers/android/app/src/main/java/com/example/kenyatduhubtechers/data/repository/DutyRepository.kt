package com.example.kenyatduhubtechers.data.repository

import com.example.kenyatduhubtechers.data.local.LocalCacheManager
import com.example.kenyatduhubtechers.data.local.SessionManager
import com.example.kenyatduhubtechers.data.model.CreateLeaveoutChitRequest
import com.example.kenyatduhubtechers.data.model.DutyResponse
import com.example.kenyatduhubtechers.data.model.GenericActionResponse
import com.example.kenyatduhubtechers.data.model.RecordCleanlinessRequest
import com.example.kenyatduhubtechers.data.model.ReportIncidentRequest
import com.example.kenyatduhubtechers.data.network.ApiConfig
import com.example.kenyatduhubtechers.data.network.RetrofitClient
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext

class DutyRepository(private val sessionManager: SessionManager) {

    init {
        RetrofitClient.initialize(sessionManager)
    }

    suspend fun getDutyData(): Result<DutyResponse> {
        return withContext(Dispatchers.IO) {
            try {
                val apiService = RetrofitClient.getApiService()
                val response = apiService.getDutyData()

                if (response.isSuccessful) {
                    val body = response.body()
                    if (body != null && body.success) {
                        sessionManager.cacheManager.saveCache(LocalCacheManager.KEY_DUTY, body)
                        Result.Success(body)
                    } else {
                        val cached = sessionManager.cacheManager.getCache(LocalCacheManager.KEY_DUTY, DutyResponse::class.java)
                        if (cached != null) {
                            Result.Success(cached)
                        } else {
                            Result.Error(body?.error ?: ApiConfig.NETWORK_ERROR_MSG)
                        }
                    }
                } else {
                    val cached = sessionManager.cacheManager.getCache(LocalCacheManager.KEY_DUTY, DutyResponse::class.java)
                    if (cached != null) {
                        Result.Success(cached)
                    } else {
                        Result.Error(ApiConfig.NETWORK_ERROR_MSG)
                    }
                }
            } catch (e: Exception) {
                val cached = sessionManager.cacheManager.getCache(LocalCacheManager.KEY_DUTY, DutyResponse::class.java)
                if (cached != null) {
                    Result.Success(cached)
                } else {
                    Result.Error(ApiConfig.NETWORK_ERROR_MSG)
                }
            }
        }
    }

    suspend fun createLeaveoutChit(studentId: Int, reason: String): Result<GenericActionResponse> {
        return withContext(Dispatchers.IO) {
            try {
                val apiService = RetrofitClient.getApiService()
                val req = CreateLeaveoutChitRequest(studentId = studentId, reason = reason)
                val response = apiService.createLeaveoutChit(req)

                if (response.isSuccessful) {
                    val body = response.body()
                    if (body != null && body.success) {
                        Result.Success(body)
                    } else {
                        Result.Error(body?.error ?: ApiConfig.NETWORK_ERROR_MSG)
                    }
                } else {
                    Result.Error(ApiConfig.NETWORK_ERROR_MSG)
                }
            } catch (e: Exception) {
                Result.Error(ApiConfig.NETWORK_ERROR_MSG)
            }
        }
    }

    suspend fun reportIncident(incidentType: String, description: String, severity: String): Result<GenericActionResponse> {
        return withContext(Dispatchers.IO) {
            try {
                val apiService = RetrofitClient.getApiService()
                val req = ReportIncidentRequest(
                    incidentType = incidentType,
                    description = description,
                    severity = severity
                )
                val response = apiService.reportIncident(req)

                if (response.isSuccessful) {
                    val body = response.body()
                    if (body != null && body.success) {
                        Result.Success(body)
                    } else {
                        Result.Error(body?.error ?: ApiConfig.NETWORK_ERROR_MSG)
                    }
                } else {
                    Result.Error(ApiConfig.NETWORK_ERROR_MSG)
                }
            } catch (e: Exception) {
                Result.Error(ApiConfig.NETWORK_ERROR_MSG)
            }
        }
    }

    suspend fun recordCleanliness(area: String, status: String, notes: String): Result<GenericActionResponse> {
        return withContext(Dispatchers.IO) {
            try {
                val apiService = RetrofitClient.getApiService()
                val req = RecordCleanlinessRequest(area = area, status = status, notes = notes)
                val response = apiService.recordCleanliness(req)

                if (response.isSuccessful) {
                    val body = response.body()
                    if (body != null && body.success) {
                        Result.Success(body)
                    } else {
                        Result.Error(body?.error ?: ApiConfig.NETWORK_ERROR_MSG)
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
