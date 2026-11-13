package com.example.kenyaeduhub.data

import com.example.kenyaeduhub.utils.formatNetworkError
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import org.json.JSONObject
import java.io.BufferedReader
import java.io.InputStreamReader
import java.io.OutputStreamWriter
import java.net.HttpURLConnection
import java.net.URL

data class User(
    val id: Int,
    val name: String,
    val email: String,
    val role: String,
    val phone: String = ""
)

sealed class LoginResult {
    data class Success(val user: User, val sessionId: String, val message: String) : LoginResult()
    data class Error(val message: String) : LoginResult()
}

sealed class RegisterResult {
    data class Success(val message: String) : RegisterResult()
    data class Error(val message: String) : RegisterResult()
}

sealed class ForgotPasswordResult {
    data class Success(val message: String, val nextStep: Int) : ForgotPasswordResult()
    data class Error(val message: String) : ForgotPasswordResult()
}

sealed class VerifyResult {
    data class Success(val message: String) : VerifyResult()
    data class Error(val message: String) : VerifyResult()
}

sealed class ResendVerificationResult {
    data class Success(val message: String, val attemptCount: Int) : ResendVerificationResult()
    data class Error(val message: String) : ResendVerificationResult()
}

class AuthRepository {
    companion object {
        var sessionCookie: String? = null
        var csrfToken: String? = null
    }

    suspend fun login(email: String, password: String): LoginResult = withContext(Dispatchers.IO) {
        var connection: HttpURLConnection? = null
        try {
            val url = URL(ApiConfig.LOGIN_URL)
            connection = (url.openConnection() as HttpURLConnection).apply {
                requestMethod = "POST"
                setRequestProperty("Content-Type", "application/json; charset=utf-8")
                setRequestProperty("Accept", "application/json")
                sessionCookie?.let {
                    setRequestProperty("Cookie", it)
                }
                doOutput = true
                doInput = true
                connectTimeout = 120000
                readTimeout = 120000
            }

            val jsonParam = JSONObject().apply {
                put("email", email)
                put("password", password)
            }

            OutputStreamWriter(connection.outputStream, "UTF-8").use { writer ->
                writer.write(jsonParam.toString())
                writer.flush()
            }

            for ((key, values) in connection.headerFields) {
                if (key != null && key.equals("Set-Cookie", ignoreCase = true)) {
                    for (value in values) {
                        val cookieValue = value.split(";").firstOrNull()
                        if (cookieValue != null) {
                            sessionCookie = cookieValue
                        }
                    }
                }
            }

            val responseCode = connection.responseCode
            val stream = if (responseCode in 200..299) {
                connection.inputStream
            } else {
                connection.errorStream ?: connection.inputStream
            }

            val responseText = BufferedReader(InputStreamReader(stream, "UTF-8")).use { it.readText() }

            val jsonResponse = try {
                JSONObject(responseText)
            } catch (e: Exception) {
                null
            }

            if (jsonResponse != null) {
                val success = jsonResponse.optBoolean("success", false)
                val message = jsonResponse.optString("message", "An error occurred")

                if (success) {
                    val userObj = jsonResponse.optJSONObject("user")
                    val user = if (userObj != null) {
                        User(
                            id = userObj.optInt("id", 0),
                            name = userObj.optString("name", ""),
                            email = userObj.optString("email", ""),
                            role = userObj.optString("role", "user"),
                            phone = userObj.optString("phone", "")
                        )
                    } else {
                        User(0, "", email, "user", "")
                    }
                    val sessionId = jsonResponse.optString("session_id", "")
                    csrfToken = jsonResponse.optString("csrf_token", "")
                    LoginResult.Success(user, sessionId, message)
                } else {
                    LoginResult.Error(message)
                }
            } else {
                LoginResult.Error("Unable to reach Kenya EduHub server. Please check your internet connection or server status.")
            }
        } catch (e: Exception) {
            LoginResult.Error(formatNetworkError(e))
        } finally {
            connection?.disconnect()
        }
    }

    suspend fun register(name: String, email: String, phone: String, password: String): RegisterResult = withContext(Dispatchers.IO) {
        var connection: HttpURLConnection? = null
        try {
            val url = URL(ApiConfig.REGISTER_URL)
            connection = (url.openConnection() as HttpURLConnection).apply {
                requestMethod = "POST"
                setRequestProperty("Content-Type", "application/json; charset=utf-8")
                setRequestProperty("Accept", "application/json")
                sessionCookie?.let {
                    setRequestProperty("Cookie", it)
                }
                doOutput = true
                doInput = true
                connectTimeout = 120000
                readTimeout = 120000
            }

            val jsonParam = JSONObject().apply {
                put("name", name)
                put("email", email)
                put("phone", phone)
                put("password", password)
            }

            OutputStreamWriter(connection.outputStream, "UTF-8").use { writer ->
                writer.write(jsonParam.toString())
                writer.flush()
            }

            for ((key, values) in connection.headerFields) {
                if (key != null && key.equals("Set-Cookie", ignoreCase = true)) {
                    for (value in values) {
                        val cookieValue = value.split(";").firstOrNull()
                        if (cookieValue != null) {
                            sessionCookie = cookieValue
                        }
                    }
                }
            }

            val responseCode = connection.responseCode
            val stream = if (responseCode in 200..299) {
                connection.inputStream
            } else {
                connection.errorStream ?: connection.inputStream
            }

            val responseText = BufferedReader(InputStreamReader(stream, "UTF-8")).use { it.readText() }

            val jsonResponse = try {
                JSONObject(responseText)
            } catch (e: Exception) {
                null
            }

            if (jsonResponse != null) {
                val success = jsonResponse.optBoolean("success", false)
                val message = jsonResponse.optString("message", "An error occurred")

                if (success) {
                    RegisterResult.Success(message)
                } else {
                    RegisterResult.Error(message)
                }
            } else {
                RegisterResult.Error("Unable to reach Kenya EduHub server. Please check your internet connection or server status.")
            }
        } catch (e: Exception) {
            RegisterResult.Error(formatNetworkError(e))
        } finally {
            connection?.disconnect()
        }
    }

    suspend fun verifyAccount(identifier: String, code: String): VerifyResult = withContext(Dispatchers.IO) {
        var connection: HttpURLConnection? = null
        try {
            val url = URL(ApiConfig.VERIFY_URL)
            connection = (url.openConnection() as HttpURLConnection).apply {
                requestMethod = "POST"
                setRequestProperty("Content-Type", "application/json; charset=utf-8")
                setRequestProperty("Accept", "application/json")
                sessionCookie?.let { setRequestProperty("Cookie", it) }
                doOutput = true
                doInput = true
                connectTimeout = 120000
                readTimeout = 120000
            }

            val jsonParam = JSONObject().apply {
                put("email", identifier)
                put("phone", identifier)
                put("code", code)
            }

            OutputStreamWriter(connection.outputStream, "UTF-8").use { writer ->
                writer.write(jsonParam.toString())
                writer.flush()
            }

            val responseCode = connection.responseCode
            val stream = if (responseCode in 200..299) connection.inputStream else (connection.errorStream ?: connection.inputStream)
            val responseText = BufferedReader(InputStreamReader(stream, "UTF-8")).use { it.readText() }

            val jsonResponse = try {
                JSONObject(responseText)
            } catch (e: Exception) {
                null
            }

            if (jsonResponse != null) {
                val success = jsonResponse.optBoolean("success", false)
                val msg = jsonResponse.optString("message", if (success) "Verification successful!" else "Verification failed")

                if (success) VerifyResult.Success(msg) else VerifyResult.Error(msg)
            } else {
                VerifyResult.Error("Unable to reach Kenya EduHub server. Please check your internet connection or server status.")
            }
        } catch (e: Exception) {
            VerifyResult.Error(formatNetworkError(e))
        } finally {
            connection?.disconnect()
        }
    }

    suspend fun resendVerificationCode(email: String, phone: String, method: String): ResendVerificationResult = withContext(Dispatchers.IO) {
        var connection: HttpURLConnection? = null
        try {
            val url = URL(ApiConfig.RESEND_VERIFICATION_URL)
            connection = (url.openConnection() as HttpURLConnection).apply {
                requestMethod = "POST"
                setRequestProperty("Content-Type", "application/json; charset=utf-8")
                setRequestProperty("Accept", "application/json")
                sessionCookie?.let { setRequestProperty("Cookie", it) }
                doOutput = true
                doInput = true
                connectTimeout = 120000
                readTimeout = 120000
            }

            val jsonParam = JSONObject().apply {
                if (email.isNotBlank()) put("email", email)
                if (phone.isNotBlank()) put("phone", phone)
                put("method", method)
            }

            OutputStreamWriter(connection.outputStream, "UTF-8").use { writer ->
                writer.write(jsonParam.toString())
                writer.flush()
            }

            val responseCode = connection.responseCode
            val stream = if (responseCode in 200..299) connection.inputStream else (connection.errorStream ?: connection.inputStream)
            val responseText = BufferedReader(InputStreamReader(stream, "UTF-8")).use { it.readText() }

            val jsonResponse = try {
                JSONObject(responseText)
            } catch (e: Exception) {
                null
            }

            if (jsonResponse != null) {
                val success = jsonResponse.optBoolean("success", false)
                val msg = jsonResponse.optString("message", if (success) "Verification code sent!" else "Failed to resend code")
                val attemptCount = jsonResponse.optInt("attempt_count", 0)

                if (success) ResendVerificationResult.Success(msg, attemptCount) else ResendVerificationResult.Error(msg)
            } else {
                ResendVerificationResult.Error("Unable to reach Kenya EduHub server. Please check connection.")
            }
        } catch (e: Exception) {
            ResendVerificationResult.Error(formatNetworkError(e))
        } finally {
            connection?.disconnect()
        }
    }

    private suspend fun postForm(urlStr: String, params: Map<String, String>): String = withContext(Dispatchers.IO) {
        var connection: HttpURLConnection? = null
        try {
            val url = URL(urlStr)
            connection = (url.openConnection() as HttpURLConnection).apply {
                requestMethod = "POST"
                setRequestProperty("Content-Type", "application/x-www-form-urlencoded; charset=utf-8")
                setRequestProperty("Accept", "text/html,application/xhtml+xml,application/xml;json;q=0.9,*/*;q=0.8")
                setRequestProperty("User-Agent", "Mozilla/5.0 (Android)")
                sessionCookie?.let {
                    setRequestProperty("Cookie", it)
                }
                doOutput = true
                doInput = true
                connectTimeout = 120000
                readTimeout = 120000
            }

            val query = params.entries.joinToString("&") {
                "${java.net.URLEncoder.encode(it.key, "UTF-8")}=${java.net.URLEncoder.encode(it.value, "UTF-8")}"
            }

            connection.outputStream.use { os ->
                OutputStreamWriter(os, "UTF-8").use { writer ->
                    writer.write(query)
                    writer.flush()
                }
            }

            for ((key, values) in connection.headerFields) {
                if (key != null && key.equals("Set-Cookie", ignoreCase = true)) {
                    for (value in values) {
                        val cookieValue = value.split(";").firstOrNull()
                        if (cookieValue != null) {
                            sessionCookie = cookieValue
                        }
                    }
                }
            }

            val responseCode = connection.responseCode
            val stream = if (responseCode in 200..299) {
                connection.inputStream
            } else {
                connection.errorStream ?: connection.inputStream
            }

            BufferedReader(InputStreamReader(stream, "UTF-8")).use { it.readText() }
        } finally {
            connection?.disconnect()
        }
    }

    private suspend fun postJson(urlStr: String, jsonBody: JSONObject): ForgotPasswordResult = withContext(Dispatchers.IO) {
        var connection: HttpURLConnection? = null
        try {
            val url = URL(urlStr)
            connection = (url.openConnection() as HttpURLConnection).apply {
                requestMethod = "POST"
                setRequestProperty("Content-Type", "application/json; charset=utf-8")
                setRequestProperty("Accept", "application/json")
                sessionCookie?.let { setRequestProperty("Cookie", it) }
                doOutput = true
                doInput = true
                connectTimeout = 120000
                readTimeout = 120000
            }

            OutputStreamWriter(connection.outputStream, "UTF-8").use { writer ->
                writer.write(jsonBody.toString())
                writer.flush()
            }

            for ((key, values) in connection.headerFields) {
                if (key != null && key.equals("Set-Cookie", ignoreCase = true)) {
                    for (value in values) {
                        val cookieValue = value.split(";").firstOrNull()
                        if (cookieValue != null) {
                            sessionCookie = cookieValue
                        }
                    }
                }
            }

            val responseCode = connection.responseCode
            val stream = if (responseCode in 200..299) connection.inputStream else (connection.errorStream ?: connection.inputStream)
            val responseText = BufferedReader(InputStreamReader(stream, "UTF-8")).use { it.readText() }

            val jsonResponse = try {
                JSONObject(responseText)
            } catch (e: Exception) {
                null
            }

            if (jsonResponse != null) {
                val success = jsonResponse.optBoolean("success", false)
                val message = jsonResponse.optString("message", "An error occurred")
                val nextStep = jsonResponse.optInt("nextStep", 2)

                if (success) {
                    ForgotPasswordResult.Success(message, nextStep)
                } else {
                    ForgotPasswordResult.Error(message)
                }
            } else {
                ForgotPasswordResult.Error("Unable to reach Kenya EduHub server. Please check your internet connection or server status.")
            }
        } catch (e: Exception) {
            ForgotPasswordResult.Error(formatNetworkError(e))
        } finally {
            connection?.disconnect()
        }
    }

    suspend fun forgotPasswordStep1(method: String, identifier: String): ForgotPasswordResult {
        val json = JSONObject().apply {
            put("step", 1)
            put("method", method)
            if (method == "email") {
                put("email", identifier)
            } else {
                put("phone", identifier)
            }
        }
        return postJson(ApiConfig.FORGOT_PASSWORD_URL, json)
    }

    suspend fun forgotPasswordStep2(code: String, method: String = "", identifier: String = ""): ForgotPasswordResult {
        val json = JSONObject().apply {
            put("step", 2)
            put("code", code)
            if (method.isNotBlank()) put("method", method)
            if (identifier.isNotBlank()) {
                if (method == "email") put("email", identifier) else put("phone", identifier)
            }
        }
        return postJson(ApiConfig.FORGOT_PASSWORD_URL, json)
    }

    suspend fun forgotPasswordStep3(password: String, confirmPassword: String, code: String = "", method: String = "", identifier: String = ""): ForgotPasswordResult {
        val json = JSONObject().apply {
            put("step", 3)
            put("new_password", password)
            put("confirm_password", confirmPassword)
            if (code.isNotBlank()) put("code", code)
            if (method.isNotBlank()) put("method", method)
            if (identifier.isNotBlank()) {
                if (method == "email") put("email", identifier) else put("phone", identifier)
            }
        }
        return postJson(ApiConfig.FORGOT_PASSWORD_URL, json)
    }
}
