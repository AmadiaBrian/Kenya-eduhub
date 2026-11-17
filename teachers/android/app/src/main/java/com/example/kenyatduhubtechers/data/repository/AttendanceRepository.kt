package com.example.kenyatduhubtechers.data.repository

import com.example.kenyatduhubtechers.data.local.LocalCacheManager
import com.example.kenyatduhubtechers.data.local.SessionManager
import com.example.kenyatduhubtechers.data.model.AttendanceHistoryResponse
import com.example.kenyatduhubtechers.data.model.AttendanceMainResponse
import com.example.kenyatduhubtechers.data.model.GenericActionResponse
import com.example.kenyatduhubtechers.data.model.SaveAttendanceItem
import com.example.kenyatduhubtechers.data.model.SaveAttendanceRequest
import com.example.kenyatduhubtechers.data.model.StudentsForAttendanceResponse
import com.example.kenyatduhubtechers.data.network.ApiConfig
import com.example.kenyatduhubtechers.data.network.RetrofitClient
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext

class AttendanceRepository(private val sessionManager: SessionManager) {

    init {
        RetrofitClient.initialize(sessionManager)
    }

    suspend fun getAttendanceMainData(): Result<AttendanceMainResponse> {
        return withContext(Dispatchers.IO) {
            try {
                val apiService = RetrofitClient.getApiService()
                val response = apiService.getAttendanceMainData()

                if (response.isSuccessful) {
                    val body = response.body()
                    if (body != null && body.success) {
                        sessionManager.cacheManager.saveCache(LocalCacheManager.KEY_ATTENDANCE, body)
                        Result.Success(body)
                    } else {
                        val cached = sessionManager.cacheManager.getCache(LocalCacheManager.KEY_ATTENDANCE, AttendanceMainResponse::class.java)
                        if (cached != null) {
                            Result.Success(cached)
                        } else {
                            Result.Error(body?.error ?: ApiConfig.NETWORK_ERROR_MSG)
                        }
                    }
                } else {
                    val cached = sessionManager.cacheManager.getCache(LocalCacheManager.KEY_ATTENDANCE, AttendanceMainResponse::class.java)
                    if (cached != null) {
                        Result.Success(cached)
                    } else {
                        Result.Error(ApiConfig.NETWORK_ERROR_MSG)
                    }
                }
            } catch (e: Exception) {
                val cached = sessionManager.cacheManager.getCache(LocalCacheManager.KEY_ATTENDANCE, AttendanceMainResponse::class.java)
                if (cached != null) {
                    Result.Success(cached)
                } else {
                    Result.Error(ApiConfig.NETWORK_ERROR_MSG)
                }
            }
        }
    }

    suspend fun getStudentsForAttendance(classId: Int, date: String): Result<StudentsForAttendanceResponse> {
        return withContext(Dispatchers.IO) {
            try {
                val apiService = RetrofitClient.getApiService()
                val response = apiService.getStudentsForAttendance(classId = classId, date = date)

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

    suspend fun saveAttendance(classId: Int, date: String, attendanceList: List<SaveAttendanceItem>): Result<GenericActionResponse> {
        return withContext(Dispatchers.IO) {
            try {
                val apiService = RetrofitClient.getApiService()
                val req = SaveAttendanceRequest(
                    classId = classId,
                    date = date,
                    attendance = attendanceList
                )
                val response = apiService.saveAttendance(req)

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

    suspend fun getAttendanceHistory(classId: Int, startDate: String, endDate: String): Result<AttendanceHistoryResponse> {
        return withContext(Dispatchers.IO) {
            try {
                val apiService = RetrofitClient.getApiService()
                val response = apiService.getAttendanceHistory(classId = classId, startDate = startDate, endDate = endDate)

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
