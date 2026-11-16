package com.example.kenyatduhubtechers.data.local

import android.content.Context
import android.content.SharedPreferences
import com.example.kenyatduhubtechers.data.model.TeacherDto
import com.example.kenyatduhubtechers.data.network.ApiConfig
import com.example.kenyatduhubtechers.data.network.RetrofitClient

class SessionManager(context: Context) {

    private val prefs: SharedPreferences = context.getSharedPreferences(PREF_NAME, Context.MODE_PRIVATE)

    companion object {
        private const val PREF_NAME = "teacher_session_prefs"
        private const val KEY_SESSION_TOKEN = "session_token"
        private const val KEY_TEACHER_ID = "teacher_id"
        private const val KEY_TEACHER_NAME = "teacher_name"
        private const val KEY_SCHOOL_ID = "school_id"
        private const val KEY_CLASS_ID = "class_id"
        private const val KEY_STREAM_ID = "stream_id"
        private const val KEY_CLASS_NAME = "class_name"
        private const val KEY_STREAM_NAME = "stream_name"
        private const val KEY_BASE_URL = "base_url"
    }

    fun saveSession(token: String, teacher: TeacherDto?) {
        prefs.edit().apply {
            putString(KEY_SESSION_TOKEN, token)
            putInt(KEY_TEACHER_ID, teacher?.id ?: -1)
            putString(KEY_TEACHER_NAME, teacher?.name ?: "")
            putInt(KEY_SCHOOL_ID, teacher?.schoolId ?: -1)
            putInt(KEY_CLASS_ID, teacher?.classId ?: -1)
            putInt(KEY_STREAM_ID, teacher?.streamId ?: -1)
            putString(KEY_CLASS_NAME, teacher?.className ?: "")
            putString(KEY_STREAM_NAME, teacher?.streamName ?: "")
            apply()
        }
    }

    fun getSessionToken(): String? {
        return prefs.getString(KEY_SESSION_TOKEN, null)
    }

    fun isLoggedIn(): Boolean {
        return !getSessionToken().isNull_or_empty()
    }

    fun getTeacherInfo(): TeacherDto {
        return TeacherDto(
            id = prefs.getInt(KEY_TEACHER_ID, -1).takeIf { it != -1 },
            name = prefs.getString(KEY_TEACHER_NAME, null),
            schoolId = prefs.getInt(KEY_SCHOOL_ID, -1).takeIf { it != -1 },
            classId = prefs.getInt(KEY_CLASS_ID, -1).takeIf { it != -1 },
            streamId = prefs.getInt(KEY_STREAM_ID, -1).takeIf { it != -1 },
            className = prefs.getString(KEY_CLASS_NAME, null),
            streamName = prefs.getString(KEY_STREAM_NAME, null)
        )
    }

    fun getBaseUrl(): String {
        return prefs.getString(KEY_BASE_URL, ApiConfig.DEFAULT_BASE_URL) ?: ApiConfig.DEFAULT_BASE_URL
    }

    fun saveBaseUrl(url: String) {
        prefs.edit().putString(KEY_BASE_URL, url).apply()
        RetrofitClient.updateBaseUrl(url)
    }

    fun clearSession() {
        prefs.edit().apply {
            remove(KEY_SESSION_TOKEN)
            remove(KEY_TEACHER_ID)
            remove(KEY_TEACHER_NAME)
            remove(KEY_SCHOOL_ID)
            remove(KEY_CLASS_ID)
            remove(KEY_STREAM_ID)
            remove(KEY_CLASS_NAME)
            remove(KEY_STREAM_NAME)
            apply()
        }
    }

    private fun String?.isNull_or_empty(): Boolean = this == null || this.trim().isEmpty()
}
