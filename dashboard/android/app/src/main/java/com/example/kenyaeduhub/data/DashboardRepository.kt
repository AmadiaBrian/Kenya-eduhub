package com.example.kenyaeduhub.data

import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import org.json.JSONObject
import java.io.BufferedReader
import java.io.InputStreamReader
import java.io.OutputStreamWriter
import java.net.HttpURLConnection
import java.net.URL

data class DashboardStats(
    val myResourcesCount: Int,
    val totalDownloads: Int,
    val recentUploads: Int,
    val allResourcesCount: Int
)

data class ResourceItem(
    val id: Int,
    val title: String,
    val subject: String,
    val fileType: String,
    val uploaderName: String,
    val description: String,
    val downloads: Int
)

data class DashboardData(
    val user: User,
    val accountAge: String,
    val activityLevel: String,
    val stats: DashboardStats,
    val myResources: List<ResourceItem>,
    val allResources: List<ResourceItem>
)

sealed class DashboardResult {
    data class Success(val data: DashboardData) : DashboardResult()
    data class Error(val message: String) : DashboardResult()
}

sealed class ProfileResult {
    data class Success(val message: String, val updatedUser: User?) : ProfileResult()
    data class Error(val message: String) : ProfileResult()
}

sealed class SettingsResult {
    data class Success(val message: String) : SettingsResult()
    data class Error(val message: String) : SettingsResult()
}

class DashboardRepository {

    suspend fun getDashboardData(userId: Int, email: String): DashboardResult = withContext(Dispatchers.IO) {
        var connection: HttpURLConnection? = null
        try {
            val url = URL(ApiConfig.DASHBOARD_URL)
            connection = (url.openConnection() as HttpURLConnection).apply {
                requestMethod = "POST"
                setRequestProperty("Content-Type", "application/json; charset=utf-8")
                setRequestProperty("Accept", "application/json")
                AuthRepository.sessionCookie?.let { setRequestProperty("Cookie", it) }
                doOutput = true
                doInput = true
                connectTimeout = 15000
                readTimeout = 15000
            }

            val jsonParam = JSONObject().apply {
                put("user_id", userId)
                put("email", email)
            }

            OutputStreamWriter(connection.outputStream, "UTF-8").use { writer ->
                writer.write(jsonParam.toString())
                writer.flush()
            }

            val responseCode = connection.responseCode
            val stream = if (responseCode in 200..299) connection.inputStream else (connection.errorStream ?: connection.inputStream)
            val responseText = BufferedReader(InputStreamReader(stream, "UTF-8")).use { it.readText() }

            val jsonResponse = JSONObject(responseText)
            val success = jsonResponse.optBoolean("success", false)

            if (success) {
                val userObj = jsonResponse.optJSONObject("user")
                val currentUser = User(
                    id = userObj?.optInt("id", userId) ?: userId,
                    name = userObj?.optString("name", "") ?: "",
                    email = userObj?.optString("email", email) ?: email,
                    role = userObj?.optString("role", "user") ?: "user",
                    phone = userObj?.optString("phone", "") ?: ""
                )
                val accountAge = userObj?.optString("account_age", "New Member") ?: "New Member"
                val activityLevel = userObj?.optString("activity_level", "Getting Started") ?: "Getting Started"

                val statsObj = jsonResponse.optJSONObject("stats")
                val stats = DashboardStats(
                    myResourcesCount = statsObj?.optInt("my_resources_count", 0) ?: 0,
                    totalDownloads = statsObj?.optInt("total_downloads", 0) ?: 0,
                    recentUploads = statsObj?.optInt("recent_uploads", 0) ?: 0,
                    allResourcesCount = statsObj?.optInt("all_resources_count", 0) ?: 0
                )

                val myResArray = jsonResponse.optJSONArray("my_resources")
                val myResources = mutableListOf<ResourceItem>()
                if (myResArray != null) {
                    for (i in 0 until myResArray.length()) {
                        val item = myResArray.getJSONObject(i)
                        myResources.add(
                            ResourceItem(
                                id = item.optInt("id", 0),
                                title = item.optString("title", "Untitled"),
                                subject = item.optString("subject", "General"),
                                fileType = item.optString("file_type", "PDF"),
                                uploaderName = item.optString("uploader_name", currentUser.name),
                                description = item.optString("description", "No description"),
                                downloads = item.optInt("downloads", 0)
                            )
                        )
                    }
                }

                val allResArray = jsonResponse.optJSONArray("all_resources")
                val allResources = mutableListOf<ResourceItem>()
                if (allResArray != null) {
                    for (i in 0 until allResArray.length()) {
                        val item = allResArray.getJSONObject(i)
                        allResources.add(
                            ResourceItem(
                                id = item.optInt("id", 0),
                                title = item.optString("title", "Untitled"),
                                subject = item.optString("subject", "General"),
                                fileType = item.optString("file_type", "PDF"),
                                uploaderName = item.optString("uploader_name", "Unknown"),
                                description = item.optString("description", "No description"),
                                downloads = item.optInt("downloads", 0)
                            )
                        )
                    }
                }

                DashboardResult.Success(
                    DashboardData(
                        user = currentUser,
                        accountAge = accountAge,
                        activityLevel = activityLevel,
                        stats = stats,
                        myResources = myResources,
                        allResources = allResources
                    )
                )
            } else {
                DashboardResult.Error(jsonResponse.optString("message", "Failed to load dashboard"))
            }
        } catch (e: Exception) {
            DashboardResult.Error("Network error: ${e.localizedMessage ?: "Please check your connection"}")
        } finally {
            connection?.disconnect()
        }
    }

    suspend fun updateProfile(userId: Int, name: String, email: String, phone: String): ProfileResult = withContext(Dispatchers.IO) {
        var connection: HttpURLConnection? = null
        try {
            val url = URL(ApiConfig.PROFILE_URL)
            connection = (url.openConnection() as HttpURLConnection).apply {
                requestMethod = "POST"
                setRequestProperty("Content-Type", "application/json; charset=utf-8")
                setRequestProperty("Accept", "application/json")
                AuthRepository.sessionCookie?.let { setRequestProperty("Cookie", it) }
                doOutput = true
                doInput = true
                connectTimeout = 15000
                readTimeout = 15000
            }

            val jsonParam = JSONObject().apply {
                put("action", "update_profile")
                put("user_id", userId)
                put("name", name)
                put("email", email)
                put("phone", phone)
            }

            OutputStreamWriter(connection.outputStream, "UTF-8").use { writer ->
                writer.write(jsonParam.toString())
                writer.flush()
            }

            val responseCode = connection.responseCode
            val stream = if (responseCode in 200..299) connection.inputStream else (connection.errorStream ?: connection.inputStream)
            val responseText = BufferedReader(InputStreamReader(stream, "UTF-8")).use { it.readText() }

            val jsonResponse = JSONObject(responseText)
            val success = jsonResponse.optBoolean("success", false)
            val msg = jsonResponse.optString("message", if (success) "Profile updated successfully!" else "Failed to update profile")

            if (success) {
                val userObj = jsonResponse.optJSONObject("user")
                val updatedUser = if (userObj != null) {
                    User(
                        id = userObj.optInt("id", userId),
                        name = userObj.optString("name", name),
                        email = userObj.optString("email", email),
                        role = userObj.optString("role", "user"),
                        phone = userObj.optString("phone", phone)
                    )
                } else null
                ProfileResult.Success(msg, updatedUser)
            } else {
                ProfileResult.Error(msg)
            }
        } catch (e: Exception) {
            ProfileResult.Error("Network error: ${e.localizedMessage ?: "Please check your connection"}")
        } finally {
            connection?.disconnect()
        }
    }

    suspend fun changePassword(userId: Int, currentPass: String, newPass: String, confirmPass: String): ProfileResult = withContext(Dispatchers.IO) {
        var connection: HttpURLConnection? = null
        try {
            val url = URL(ApiConfig.PROFILE_URL)
            connection = (url.openConnection() as HttpURLConnection).apply {
                requestMethod = "POST"
                setRequestProperty("Content-Type", "application/json; charset=utf-8")
                setRequestProperty("Accept", "application/json")
                AuthRepository.sessionCookie?.let { setRequestProperty("Cookie", it) }
                doOutput = true
                doInput = true
                connectTimeout = 15000
                readTimeout = 15000
            }

            val jsonParam = JSONObject().apply {
                put("action", "change_password")
                put("user_id", userId)
                put("current_password", currentPass)
                put("new_password", newPass)
                put("confirm_password", confirmPass)
            }

            OutputStreamWriter(connection.outputStream, "UTF-8").use { writer ->
                writer.write(jsonParam.toString())
                writer.flush()
            }

            val responseCode = connection.responseCode
            val stream = if (responseCode in 200..299) connection.inputStream else (connection.errorStream ?: connection.inputStream)
            val responseText = BufferedReader(InputStreamReader(stream, "UTF-8")).use { it.readText() }

            val jsonResponse = JSONObject(responseText)
            val success = jsonResponse.optBoolean("success", false)
            val msg = jsonResponse.optString("message", if (success) "Password changed successfully!" else "Failed to change password")

            if (success) {
                ProfileResult.Success(msg, null)
            } else {
                ProfileResult.Error(msg)
            }
        } catch (e: Exception) {
            ProfileResult.Error("Network error: ${e.localizedMessage ?: "Please check your connection"}")
        } finally {
            connection?.disconnect()
        }
    }

    suspend fun saveSettings(
        userId: Int,
        emailUploads: Boolean,
        emailComments: Boolean,
        emailUpdates: Boolean,
        showProfile: Boolean,
        showEmail: Boolean,
        theme: String,
        language: String
    ): SettingsResult = withContext(Dispatchers.IO) {
        var connection: HttpURLConnection? = null
        try {
            val url = URL(ApiConfig.SETTINGS_URL)
            connection = (url.openConnection() as HttpURLConnection).apply {
                requestMethod = "POST"
                setRequestProperty("Content-Type", "application/json; charset=utf-8")
                setRequestProperty("Accept", "application/json")
                AuthRepository.sessionCookie?.let { setRequestProperty("Cookie", it) }
                doOutput = true
                doInput = true
                connectTimeout = 15000
                readTimeout = 15000
            }

            val jsonParam = JSONObject().apply {
                put("action", "update_settings")
                put("user_id", userId)
                put("email_uploads", emailUploads)
                put("email_comments", emailComments)
                put("email_updates", emailUpdates)
                put("show_profile", showProfile)
                put("show_email", showEmail)
                put("theme", theme)
                put("language", language)
            }

            OutputStreamWriter(connection.outputStream, "UTF-8").use { writer ->
                writer.write(jsonParam.toString())
                writer.flush()
            }

            val responseCode = connection.responseCode
            val stream = if (responseCode in 200..299) connection.inputStream else (connection.errorStream ?: connection.inputStream)
            val responseText = BufferedReader(InputStreamReader(stream, "UTF-8")).use { it.readText() }

            val jsonResponse = JSONObject(responseText)
            val success = jsonResponse.optBoolean("success", false)
            val msg = jsonResponse.optString("message", if (success) "Settings saved successfully!" else "Failed to save settings")

            if (success) SettingsResult.Success(msg) else SettingsResult.Error(msg)
        } catch (e: Exception) {
            SettingsResult.Error("Network error: ${e.localizedMessage ?: "Please check your connection"}")
        } finally {
            connection?.disconnect()
        }
    }

    suspend fun deleteAccount(userId: Int, confirmEmail: String, deletionReason: String): SettingsResult = withContext(Dispatchers.IO) {
        var connection: HttpURLConnection? = null
        try {
            val url = URL(ApiConfig.SETTINGS_URL)
            connection = (url.openConnection() as HttpURLConnection).apply {
                requestMethod = "POST"
                setRequestProperty("Content-Type", "application/json; charset=utf-8")
                setRequestProperty("Accept", "application/json")
                AuthRepository.sessionCookie?.let { setRequestProperty("Cookie", it) }
                doOutput = true
                doInput = true
                connectTimeout = 15000
                readTimeout = 15000
            }

            val jsonParam = JSONObject().apply {
                put("action", "delete_account")
                put("user_id", userId)
                put("confirm_email", confirmEmail)
                put("deletion_reason", deletionReason)
            }

            OutputStreamWriter(connection.outputStream, "UTF-8").use { writer ->
                writer.write(jsonParam.toString())
                writer.flush()
            }

            val responseCode = connection.responseCode
            val stream = if (responseCode in 200..299) connection.inputStream else (connection.errorStream ?: connection.inputStream)
            val responseText = BufferedReader(InputStreamReader(stream, "UTF-8")).use { it.readText() }

            val jsonResponse = JSONObject(responseText)
            val success = jsonResponse.optBoolean("success", false)
            val msg = jsonResponse.optString("message", if (success) "Account deleted successfully." else "Failed to delete account")

            if (success) SettingsResult.Success(msg) else SettingsResult.Error(msg)
        } catch (e: Exception) {
            SettingsResult.Error("Network error: ${e.localizedMessage ?: "Please check your connection"}")
        } finally {
            connection?.disconnect()
        }
    }
}
