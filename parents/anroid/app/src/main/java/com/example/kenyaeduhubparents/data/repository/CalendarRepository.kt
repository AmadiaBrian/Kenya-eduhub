package com.example.kenyaeduhubparents.data.repository

import com.example.kenyaeduhubparents.data.local.SessionManager
import com.example.kenyaeduhubparents.data.model.CalendarResponse
import com.example.kenyaeduhubparents.data.network.ApiConfig
import com.example.kenyaeduhubparents.data.network.NetworkUtils
import com.example.kenyaeduhubparents.data.network.RetrofitClient
import com.google.gson.Gson
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext

class CalendarRepository(private val sessionManager: SessionManager) {
    private val gson = Gson()

    suspend fun getCalendarData(): Result<CalendarResponse> {
        return withContext(Dispatchers.IO) {
            try {
                val apiService = RetrofitClient.getApiService(ApiConfig.BASE_URL)
                val parentId = sessionManager.getParentId()
                val response = apiService.getCalendar(parentId)
                if (response.isSuccessful && response.body() != null) {
                    val body = response.body()!!
                    sessionManager.saveCachedJson("calendar", gson.toJson(body))
                    Result.success(body)
                } else {
                    checkCache<CalendarResponse>("calendar")?.let { return@withContext Result.success(it) }
                    Result.failure(Exception(NetworkUtils.parseHttpError(response.code(), response.message())))
                }
            } catch (e: Exception) {
                checkCache<CalendarResponse>("calendar")?.let { return@withContext Result.success(it) }
                Result.failure(Exception(NetworkUtils.parseException(e)))
            }
        }
    }

    private inline fun <reified T> checkCache(key: String): T? {
        return try {
            val cachedJson = sessionManager.getCachedJson(key)
            if (!cachedJson.isNullOrBlank()) {
                gson.fromJson(cachedJson, T::class.java)
            } else {
                null
            }
        } catch (_: Exception) {
            null
        }
    }
}
