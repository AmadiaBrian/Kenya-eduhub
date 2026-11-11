package com.example.kenyaeduhubparents.data.network

import android.content.Context
import com.google.gson.GsonBuilder
import okhttp3.Cookie
import okhttp3.CookieJar
import okhttp3.HttpUrl
import okhttp3.OkHttpClient
import okhttp3.logging.HttpLoggingInterceptor
import retrofit2.Retrofit
import retrofit2.converter.gson.GsonConverterFactory
import java.util.concurrent.TimeUnit

class SessionCookieJar : CookieJar {
    private val cookieStore = mutableMapOf<String, List<Cookie>>()

    override fun saveFromResponse(url: HttpUrl, cookies: List<Cookie>) {
        cookieStore[url.host] = cookies
    }

    override fun loadForRequest(url: HttpUrl): List<Cookie> {
        return cookieStore[url.host] ?: emptyList()
    }
}

object RetrofitClient {

    private val cookieJar = SessionCookieJar()
    private var appContext: Context? = null

    fun init(context: Context) {
        appContext = context.applicationContext
    }

    private fun getOkHttpClient(): OkHttpClient {
        val logging = HttpLoggingInterceptor().apply {
            level = HttpLoggingInterceptor.Level.BODY
        }
        return OkHttpClient.Builder()
            .cookieJar(cookieJar)
            .addInterceptor { chain ->
                val originalRequest = chain.request()
                val requestBuilder = originalRequest.newBuilder()
                    .addHeader("ngrok-skip-browser-warning", "true")

                appContext?.let { ctx ->
                    val prefs = ctx.getSharedPreferences("KenyaEduHubPrefs", Context.MODE_PRIVATE)
                    val sessionId = prefs.getString("session_id", null)
                    if (!sessionId.isNullOrEmpty()) {
                        requestBuilder.addHeader("Cookie", "PHPSESSID=$sessionId")
                    }
                }

                chain.proceed(requestBuilder.build())
            }
            .addInterceptor(logging)
            .connectTimeout(30, TimeUnit.SECONDS)
            .readTimeout(30, TimeUnit.SECONDS)
            .writeTimeout(30, TimeUnit.SECONDS)
            .build()
    }

    fun getApiService(baseUrl: String): ApiService {
        val formattedBaseUrl = if (baseUrl.endsWith("/")) baseUrl else "$baseUrl/"
        val gson = GsonBuilder().setLenient().create()
        return Retrofit.Builder()
            .baseUrl(formattedBaseUrl)
            .client(getOkHttpClient())
            .addConverterFactory(GsonConverterFactory.create(gson))
            .build()
            .create(ApiService::class.java)
    }
}
