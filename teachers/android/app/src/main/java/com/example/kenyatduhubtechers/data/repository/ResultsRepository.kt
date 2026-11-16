package com.example.kenyatduhubtechers.data.repository

import com.example.kenyatduhubtechers.data.local.SessionManager
import com.example.kenyatduhubtechers.data.model.ResultsResponse
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
                        Result.Success(resultsResponse)
                    } else {
                        Result.Error(resultsResponse?.error ?: "Failed to fetch results data")
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
