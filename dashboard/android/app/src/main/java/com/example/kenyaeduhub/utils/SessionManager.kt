package com.example.kenyaeduhub.utils

import android.content.Context
import android.content.SharedPreferences
import com.example.kenyaeduhub.data.AuthRepository
import com.example.kenyaeduhub.data.DashboardData
import com.example.kenyaeduhub.data.DashboardStats
import com.example.kenyaeduhub.data.ResourceItem
import com.example.kenyaeduhub.data.User
import org.json.JSONArray
import org.json.JSONObject

class SessionManager(context: Context) {
    private val prefs: SharedPreferences = context.getSharedPreferences("kenya_eduhub_session_prefs", Context.MODE_PRIVATE)

    companion object {
        private const val KEY_USER_ID = "user_id"
        private const val KEY_USER_NAME = "user_name"
        private const val KEY_USER_EMAIL = "user_email"
        private const val KEY_USER_ROLE = "user_role"
        private const val KEY_USER_PHONE = "user_phone"
        private const val KEY_SESSION_COOKIE = "session_cookie"
        private const val KEY_CSRF_TOKEN = "csrf_token"
        private const val KEY_CACHED_DASHBOARD_JSON = "cached_dashboard_json"
    }

    fun saveUserSession(user: User, sessionId: String? = null, csrfToken: String? = null) {
        prefs.edit().apply {
            putInt(KEY_USER_ID, user.id)
            putString(KEY_USER_NAME, user.name)
            putString(KEY_USER_EMAIL, user.email)
            putString(KEY_USER_ROLE, user.role)
            putString(KEY_USER_PHONE, user.phone)
            AuthRepository.sessionCookie?.let { putString(KEY_SESSION_COOKIE, it) }
            AuthRepository.csrfToken?.let { putString(KEY_CSRF_TOKEN, it) }
            apply()
        }
    }

    fun getSavedUser(): User? {
        val id = prefs.getInt(KEY_USER_ID, -1)
        if (id <= 0) return null

        val name = prefs.getString(KEY_USER_NAME, "") ?: ""
        val email = prefs.getString(KEY_USER_EMAIL, "") ?: ""
        val role = prefs.getString(KEY_USER_ROLE, "user") ?: "user"
        val phone = prefs.getString(KEY_USER_PHONE, "") ?: ""

        AuthRepository.sessionCookie = prefs.getString(KEY_SESSION_COOKIE, null)
        AuthRepository.csrfToken = prefs.getString(KEY_CSRF_TOKEN, null)

        return User(id, name, email, role, phone)
    }

    fun clearSession() {
        prefs.edit().clear().apply()
        AuthRepository.sessionCookie = null
        AuthRepository.csrfToken = null
    }

    fun saveCachedDashboardData(data: DashboardData) {
        try {
            val json = JSONObject().apply {
                put("user", JSONObject().apply {
                    put("id", data.user.id)
                    put("name", data.user.name)
                    put("email", data.user.email)
                    put("role", data.user.role)
                    put("phone", data.user.phone)
                })
                put("account_age", data.accountAge)
                put("activity_level", data.activityLevel)
                put("stats", JSONObject().apply {
                    put("my_resources_count", data.stats.myResourcesCount)
                    put("total_downloads", data.stats.totalDownloads)
                    put("recent_uploads", data.stats.recentUploads)
                    put("all_resources_count", data.stats.allResourcesCount)
                })

                val myResArray = JSONArray()
                data.myResources.forEach { item ->
                    myResArray.put(JSONObject().apply {
                        put("id", item.id)
                        put("title", item.title)
                        put("subject", item.subject)
                        put("file_type", item.fileType)
                        put("uploader_name", item.uploaderName)
                        put("description", item.description)
                        put("downloads", item.downloads)
                    })
                }
                put("my_resources", myResArray)

                val allResArray = JSONArray()
                data.allResources.forEach { item ->
                    allResArray.put(JSONObject().apply {
                        put("id", item.id)
                        put("title", item.title)
                        put("subject", item.subject)
                        put("file_type", item.fileType)
                        put("uploader_name", item.uploaderName)
                        put("description", item.description)
                        put("downloads", item.downloads)
                    })
                }
                put("all_resources", allResArray)
            }

            prefs.edit().putString(KEY_CACHED_DASHBOARD_JSON, json.toString()).apply()
        } catch (e: Exception) {
            e.printStackTrace()
        }
    }

    fun getCachedDashboardData(): DashboardData? {
        val jsonStr = prefs.getString(KEY_CACHED_DASHBOARD_JSON, null) ?: return null
        return try {
            val json = JSONObject(jsonStr)
            val userObj = json.getJSONObject("user")
            val currentUser = User(
                id = userObj.getInt("id"),
                name = userObj.getString("name"),
                email = userObj.getString("email"),
                role = userObj.getString("role"),
                phone = userObj.optString("phone", "")
            )

            val accountAge = json.optString("account_age", "Member")
            val activityLevel = json.optString("activity_level", "Active")

            val statsObj = json.getJSONObject("stats")
            val stats = DashboardStats(
                myResourcesCount = statsObj.getInt("my_resources_count"),
                totalDownloads = statsObj.getInt("total_downloads"),
                recentUploads = statsObj.getInt("recent_uploads"),
                allResourcesCount = statsObj.getInt("all_resources_count")
            )

            val myResArray = json.optJSONArray("my_resources")
            val myResources = mutableListOf<ResourceItem>()
            if (myResArray != null) {
                for (i in 0 until myResArray.length()) {
                    val item = myResArray.getJSONObject(i)
                    myResources.add(
                        ResourceItem(
                            id = item.getInt("id"),
                            title = item.getString("title"),
                            subject = item.getString("subject"),
                            fileType = item.getString("file_type"),
                            uploaderName = item.getString("uploader_name"),
                            description = item.getString("description"),
                            downloads = item.getInt("downloads")
                        )
                    )
                }
            }

            val allResArray = json.optJSONArray("all_resources")
            val allResources = mutableListOf<ResourceItem>()
            if (allResArray != null) {
                for (i in 0 until allResArray.length()) {
                    val item = allResArray.getJSONObject(i)
                    allResources.add(
                        ResourceItem(
                            id = item.getInt("id"),
                            title = item.getString("title"),
                            subject = item.getString("subject"),
                            fileType = item.getString("file_type"),
                            uploaderName = item.getString("uploader_name"),
                            description = item.getString("description"),
                            downloads = item.getInt("downloads")
                        )
                    )
                }
            }

            DashboardData(
                user = currentUser,
                accountAge = accountAge,
                activityLevel = activityLevel,
                stats = stats,
                myResources = myResources,
                allResources = allResources
            )
        } catch (e: Exception) {
            null
        }
    }
}
