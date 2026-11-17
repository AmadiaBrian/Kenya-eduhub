package com.example.kenyatduhubtechers.data.local

import android.content.Context
import android.content.SharedPreferences
import com.google.gson.Gson
import java.lang.reflect.Type

class LocalCacheManager(context: Context) {

    private val prefs: SharedPreferences = context.getSharedPreferences(PREF_NAME, Context.MODE_PRIVATE)
    private val gson = Gson()

    companion object {
        private const val PREF_NAME = "teacher_offline_cache_prefs"

        const val KEY_DASHBOARD = "cache_dashboard"
        const val KEY_CALENDAR = "cache_calendar"
        const val KEY_TIMETABLE = "cache_timetable"
        const val KEY_PERFORMANCE = "cache_performance"
        const val KEY_RESULTS = "cache_results"
        const val KEY_PROFILE = "cache_profile"
        const val KEY_STUDENTS = "cache_students"
        const val KEY_PARENTS = "cache_parents"
        const val KEY_FEES = "cache_fees"
        const val KEY_STUDENT_SUBJECTS = "cache_student_subjects"
        const val KEY_DUTY = "cache_duty"
        const val KEY_ASSIGNMENTS = "cache_assignments"
        const val KEY_ATTENDANCE = "cache_attendance"
    }

    fun <T> saveCache(key: String, data: T) {
        try {
            val json = gson.toJson(data)
            prefs.edit()
                .putString(key, json)
                .putLong("${key}_time", System.currentTimeMillis())
                .apply()
        } catch (e: Exception) {
            e.printStackTrace()
        }
    }

    fun <T> getCache(key: String, classOfT: Class<T>): T? {
        return try {
            val json = prefs.getString(key, null) ?: return null
            gson.fromJson(json, classOfT)
        } catch (e: Exception) {
            null
        }
    }

    fun <T> getCache(key: String, typeOfT: Type): T? {
        return try {
            val json = prefs.getString(key, null) ?: return null
            gson.fromJson(json, typeOfT)
        } catch (e: Exception) {
            null
        }
    }

    fun getCacheTime(key: String): Long {
        return prefs.getLong("${key}_time", 0L)
    }

    fun clearCache() {
        prefs.edit().clear().apply()
    }
}
