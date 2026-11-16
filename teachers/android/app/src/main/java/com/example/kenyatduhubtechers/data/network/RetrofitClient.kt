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
                val token = sessionManager?.getSessionToken() ?: ""
                val requestBuilder = originalRequest.newBuilder()
                if (token.isNotBlank()) {
                    requestBuilder.header("Authorization", token)
                    requestBuilder.header("X-Session-Token", token)
                }
                chain.proceed(requestBuilder.build())
            }

            val okHttpClient = OkHttpClient.Builder()
                .addInterceptor(authInterceptor)
                .addInterceptor(loggingInterceptor)
                .connectTimeout(15, TimeUnit.SECONDS)
                .readTimeout(15, TimeUnit.SECONDS)
                .writeTimeout(15, TimeUnit.SECONDS)
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
