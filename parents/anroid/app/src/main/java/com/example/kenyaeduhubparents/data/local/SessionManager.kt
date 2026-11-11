package com.example.kenyaeduhubparents.data.local

import android.content.Context
import android.content.SharedPreferences
import com.example.kenyaeduhubparents.data.model.Parent
import com.google.gson.Gson

class SessionManager(context: Context) {
    private val prefs: SharedPreferences =
        context.getSharedPreferences("KenyaEduHubPrefs", Context.MODE_PRIVATE)
    private val gson = Gson()

    companion object {
        private const val KEY_SESSION_TOKEN = "session_token"
        private const val KEY_SESSION_ID = "session_id"
        private const val KEY_PARENT_DATA = "parent_data"
    }

    fun saveSession(sessionToken: String?, sessionId: String?, parent: Parent?) {
        val editor = prefs.edit()
        editor.putString(KEY_SESSION_TOKEN, sessionToken)
        editor.putString(KEY_SESSION_ID, sessionId)
        if (parent != null) {
            editor.putString(KEY_PARENT_DATA, gson.toJson(parent))
        }
        editor.apply()
    }

    fun getSessionToken(): String? = prefs.getString(KEY_SESSION_TOKEN, null)

    fun getSessionId(): String? = prefs.getString(KEY_SESSION_ID, null)

    fun getParentData(): Parent? {
        val json = prefs.getString(KEY_PARENT_DATA, null) ?: return null
        return try {
            gson.fromJson(json, Parent::class.java)
        } catch (e: Exception) {
            null
        }
    }

    fun saveParentData(parent: Parent) {
        prefs.edit()
            .putString(KEY_PARENT_DATA, gson.toJson(parent))
            .apply()
    }

    fun getParentId(): Int {
        return getParentData()?.id ?: 0
    }

    fun isLoggedIn(): Boolean = !getSessionToken().isNullOrEmpty()

    fun clearSession() {
        prefs.edit()
            .remove(KEY_SESSION_TOKEN)
            .remove(KEY_SESSION_ID)
            .remove(KEY_PARENT_DATA)
            .apply()
    }

    fun saveCachedJson(key: String, json: String) {
        prefs.edit().putString("cache_$key", json).apply()
    }

    fun getCachedJson(key: String): String? {
        return prefs.getString("cache_$key", null)
    }
}
