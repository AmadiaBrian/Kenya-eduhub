package com.example.kenyaeduhubparents.data.repository

import com.example.kenyaeduhubparents.data.local.SessionManager
import com.example.kenyaeduhubparents.data.model.LibraryFinesResponse
import com.example.kenyaeduhubparents.data.model.MpesaPaymentResponse
import com.example.kenyaeduhubparents.data.model.MpesaStatusResponse
import com.example.kenyaeduhubparents.data.network.ApiConfig
import com.example.kenyaeduhubparents.data.network.NetworkUtils
import com.example.kenyaeduhubparents.data.network.RetrofitClient
import com.google.gson.Gson
import com.google.gson.GsonBuilder
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext

class LibraryFinesRepository(private val sessionManager: SessionManager) {
    private val gson = Gson()

    suspend fun getLibraryFinesData(): Result<LibraryFinesResponse> {
        return withContext(Dispatchers.IO) {
            try {
                val apiService = RetrofitClient.getApiService(ApiConfig.BASE_URL)
                val parentId = sessionManager.getParentId()
                val response = apiService.getLibraryFines(parentId)
                if (response.isSuccessful && response.body() != null) {
                    val body = response.body()!!
                    sessionManager.saveCachedJson("library_fines", gson.toJson(body))
                    Result.success(body)
                } else {
                    checkCache<LibraryFinesResponse>("library_fines")?.let { return@withContext Result.success(it) }
                    Result.failure(Exception(NetworkUtils.parseHttpError(response.code(), response.message())))
                }
            } catch (e: Exception) {
                checkCache<LibraryFinesResponse>("library_fines")?.let { return@withContext Result.success(it) }
                Result.failure(Exception(NetworkUtils.parseException(e)))
            }
        }
    }

    suspend fun initiateLibraryFinePayment(
        fineId: String,
        amount: String,
        phone: String
    ): Result<MpesaPaymentResponse> {
        return withContext(Dispatchers.IO) {
            try {
                val apiService = RetrofitClient.getApiService(ApiConfig.BASE_URL)
                val response = apiService.initiateLibraryFinePayment(fineId, amount, phone)
                if (response.isSuccessful && response.body() != null) {
                    val rawBody = response.body()!!.string()
                    val lenientGson = GsonBuilder().setLenient().create()
                    val mpesaResp = try {
                        lenientGson.fromJson(rawBody, MpesaPaymentResponse::class.java)
                    } catch (_: Exception) {
                        MpesaPaymentResponse(success = false, error = rawBody)
                    }
                    Result.success(mpesaResp)
                } else {
                    Result.failure(Exception(NetworkUtils.parseHttpError(response.code(), response.message())))
                }
            } catch (e: Exception) {
                Result.failure(Exception(NetworkUtils.parseException(e)))
            }
        }
    }

    suspend fun checkMpesaStatus(checkoutRequestID: String): Result<MpesaStatusResponse> {
        return withContext(Dispatchers.IO) {
            try {
                val apiService = RetrofitClient.getApiService(ApiConfig.BASE_URL)
                val response = apiService.checkMpesaStatus(checkoutRequestID)
                if (response.isSuccessful && response.body() != null) {
                    val rawBody = response.body()!!.string()
                    val lenientGson = GsonBuilder().setLenient().create()
                    val statusResp = try {
                        lenientGson.fromJson(rawBody, MpesaStatusResponse::class.java)
                    } catch (_: Exception) {
                        MpesaStatusResponse(found = false, errorMessage = rawBody)
                    }
                    Result.success(statusResp)
                } else {
                    Result.failure(Exception(NetworkUtils.parseHttpError(response.code(), response.message())))
                }
            } catch (e: Exception) {
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
