package com.example.kenyaeduhubparents.data.repository

import com.example.kenyaeduhubparents.data.local.SessionManager
import com.example.kenyaeduhubparents.data.model.ChildrenResponse
import com.example.kenyaeduhubparents.data.network.ApiConfig
import com.example.kenyaeduhubparents.data.network.NetworkUtils
import com.example.kenyaeduhubparents.data.network.RetrofitClient
import com.google.gson.Gson
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext

class ChildrenRepository(private val sessionManager: SessionManager) {
    private val gson = Gson()

    suspend fun getChildrenData(): Result<ChildrenResponse> {
        return withContext(Dispatchers.IO) {
            try {
                val apiService = RetrofitClient.getApiService(ApiConfig.BASE_URL)
                val parentId = sessionManager.getParentId()

                val childrenResponse = apiService.getChildren(parentId)
                val feesResponse = apiService.getFees(parentId)

                if (childrenResponse.isSuccessful && childrenResponse.body() != null) {
                    val body = childrenResponse.body()!!

                    if (feesResponse.isSuccessful && feesResponse.body() != null) {
                        val feesBody = feesResponse.body()!!
                        val feeDataMap = feesBody.feeData

                        body.data.forEach { child ->
                            val feeWrapper = feeDataMap[child.id.toString()]
                            if (feeWrapper != null) {
                                child.feeBalance = feeWrapper.yearBalance
                                child.totalFees = feeWrapper.yearTotalFees
                                child.totalPaid = feeWrapper.yearTotalPaid
                                child.feeStructureStatus = feeWrapper.feeStructureStatus
                            }
                        }
                    }

                    sessionManager.saveCachedJson("children", gson.toJson(body))
                    Result.success(body)
                } else {
                    checkCache<ChildrenResponse>("children")?.let { return@withContext Result.success(it) }
                    Result.failure(Exception(NetworkUtils.parseHttpError(childrenResponse.code(), childrenResponse.message())))
                }
            } catch (e: Exception) {
                checkCache<ChildrenResponse>("children")?.let { return@withContext Result.success(it) }
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
