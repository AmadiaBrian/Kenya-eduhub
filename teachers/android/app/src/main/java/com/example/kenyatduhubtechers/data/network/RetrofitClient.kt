package com.example.kenyatduhubtechers.data.network

import com.example.kenyatduhubtechers.data.local.SessionManager
import okhttp3.Interceptor
import okhttp3.OkHttpClient
import okhttp3.logging.HttpLoggingInterceptor
import retrofit2.Retrofit
import retrofit2.converter.gson.GsonConverterFactory
import java.util.concurrent.TimeUnit

object RetrofitClient {

    private var currentBaseUrl: String = ApiConfig.DEFAULT_BASE_URL
    private var apiService: TeacherApiService? = null
    private var sessionManager: SessionManager? = null

    fun initialize(sessionManager: SessionManager) {
        this.sessionManager = sessionManager
        this.currentBaseUrl = sessionManager.getBaseUrl()
        this.apiService = null
    }

    fun getApiService(baseUrl: String = getBaseUrl()): TeacherApiService {
        val formattedUrl = if (baseUrl.endsWith("/")) baseUrl else "$baseUrl/"
        if (apiService == null || currentBaseUrl != formattedUrl) {
            currentBaseUrl = formattedUrl

            val loggingInterceptor = HttpLoggingInterceptor().apply {
                level = HttpLoggingInterceptor.Level.BODY
            }

            val authInterceptor = Interceptor { chain ->
                val originalRequest = chain.request()
                var rawToken = sessionManager?.getSessionToken() ?: ""
                if (rawToken.startsWith("Bearer ", ignoreCase = true)) {
                    rawToken = rawToken.substring(7).trim()
                }
                val requestBuilder = originalRequest.newBuilder()
                if (rawToken.isNotBlank()) {
                    requestBuilder.header("Authorization", "Bearer $rawToken")
                    requestBuilder.header("X-Session-Token", rawToken)
                }
                chain.proceed(requestBuilder.build())
            }

            val okHttpClient = OkHttpClient.Builder()
                .addInterceptor(authInterceptor)
                .addInterceptor(loggingInterceptor)
                .connectTimeout(2, TimeUnit.MINUTES)
                .readTimeout(2, TimeUnit.MINUTES)
                .writeTimeout(2, TimeUnit.MINUTES)
                .build()

            val retrofit = Retrofit.Builder()
                .baseUrl(currentBaseUrl)
                .client(okHttpClient)
                .addConverterFactory(GsonConverterFactory.create())
                .build()

            apiService = retrofit.create(TeacherApiService::class.java)
        }
        return apiService!!
    }

    fun updateBaseUrl(newUrl: String) {
        if (newUrl.isNotBlank()) {
            currentBaseUrl = if (newUrl.endsWith("/")) newUrl else "$newUrl/"
            apiService = null
            getApiService(currentBaseUrl)
        }
    }

    fun getBaseUrl(): String = sessionManager?.getBaseUrl() ?: currentBaseUrl
}
