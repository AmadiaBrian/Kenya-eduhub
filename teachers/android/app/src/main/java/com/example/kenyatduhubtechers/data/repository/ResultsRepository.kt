package com.example.kenyatduhubtechers.data.repository

import com.example.kenyatduhubtechers.data.local.LocalCacheManager
import com.example.kenyatduhubtechers.data.local.SessionManager
import com.example.kenyatduhubtechers.data.model.ResultsResponse
import com.example.kenyatduhubtechers.data.network.ApiConfig
import com.example.kenyatduhubtechers.data.network.RetrofitClient
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext

class ResultsRepository(private val sessionManager: SessionManager) {

    init {
        RetrofitClient.initialize(sessionManager)
    }

    suspend fun getResultsData(): Result<ResultsResponse> {
        return withContext(Dispatchers.IO) {
            try {
                val apiService = RetrofitClient.getApiService()
                val response = apiService.getResults()

                if (response.isSuccessful) {
                    val resultsResponse = response.body()
                    if (resultsResponse != null && resultsResponse.success) {
                        sessionManager.cacheManager.saveCache(LocalCacheManager.KEY_RESULTS, resultsResponse)
                        Result.Success(resultsResponse)
                    } else {
                        val cached = sessionManager.cacheManager.getCache(LocalCacheManager.KEY_RESULTS, ResultsResponse::class.java)
                        if (cached != null) {
                            Result.Success(cached)
                        } else {
                            Result.Error(resultsResponse?.error ?: ApiConfig.NETWORK_ERROR_MSG)
                        }
                    }
                } else {
                    val cached = sessionManager.cacheManager.getCache(LocalCacheManager.KEY_RESULTS, ResultsResponse::class.java)
                    if (cached != null) {
                        Result.Success(cached)
                    } else {
                        Result.Error(ApiConfig.NETWORK_ERROR_MSG)
                    }
                }
            } catch (e: Exception) {
                val cached = sessionManager.cacheManager.getCache(LocalCacheManager.KEY_RESULTS, ResultsResponse::class.java)
                if (cached != null) {
                    Result.Success(cached)
                } else {
                    Result.Error(ApiConfig.NETWORK_ERROR_MSG)
                }
            }
        }
    }
}
