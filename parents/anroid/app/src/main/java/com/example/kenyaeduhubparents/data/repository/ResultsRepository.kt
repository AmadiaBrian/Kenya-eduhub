package com.example.kenyaeduhubparents.data.repository

import com.example.kenyaeduhubparents.data.local.SessionManager
import com.example.kenyaeduhubparents.data.model.ResultsResponse
import com.example.kenyaeduhubparents.data.network.ApiConfig
import com.example.kenyaeduhubparents.data.network.NetworkUtils
import com.example.kenyaeduhubparents.data.network.RetrofitClient
import com.google.gson.Gson
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext

class ResultsRepository(private val sessionManager: SessionManager) {
    private val gson = Gson()

    suspend fun getResultsData(scope: String? = null): Result<ResultsResponse> {
        return withContext(Dispatchers.IO) {
            try {
                val apiService = RetrofitClient.getApiService(ApiConfig.BASE_URL)
                val parentId = sessionManager.getParentId()
                val response = apiService.getResults(parentId, scope)
                if (response.isSuccessful) {
                    val body = response.body()
                    if (body != null) {
                        sessionManager.saveCachedJson("results", gson.toJson(body))
                        Result.success(body)
                    } else {
                        checkCache<ResultsResponse>("results")?.let { return@withContext Result.success(it) }
                        Result.failure(Exception("Empty response body from server"))
                    }
                } else {
                    checkCache<ResultsResponse>("results")?.let { return@withContext Result.success(it) }
                    Result.failure(Exception(NetworkUtils.parseHttpError(response.code(), response.message())))
                }
            } catch (e: Exception) {
                checkCache<ResultsResponse>("results")?.let { return@withContext Result.success(it) }
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
