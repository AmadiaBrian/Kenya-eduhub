package com.example.kenyatduhubtechers.data.repository

import android.content.Context
import android.net.Uri
import android.provider.OpenableColumns
import com.example.kenyatduhubtechers.data.local.LocalCacheManager
import com.example.kenyatduhubtechers.data.local.SessionManager
import com.example.kenyatduhubtechers.data.model.AssignmentsResponse
import com.example.kenyatduhubtechers.data.model.DuplicateAssignmentRequest
import com.example.kenyatduhubtechers.data.model.GenericActionResponse
import com.example.kenyatduhubtechers.data.model.UpdateAssignmentRequest
import com.example.kenyatduhubtechers.data.network.ApiConfig
import com.example.kenyatduhubtechers.data.network.RetrofitClient
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import okhttp3.MediaType.Companion.toMediaTypeOrNull
import okhttp3.MultipartBody
import okhttp3.RequestBody.Companion.asRequestBody
import okhttp3.RequestBody.Companion.toRequestBody
import java.io.File
import java.io.FileOutputStream

class AssignmentsRepository(private val sessionManager: SessionManager) {

    init {
        RetrofitClient.initialize(sessionManager)
    }

    suspend fun getAssignmentsData(): Result<AssignmentsResponse> {
        return withContext(Dispatchers.IO) {
            try {
                val apiService = RetrofitClient.getApiService()
                val response = apiService.getAssignments()

                if (response.isSuccessful) {
                    val body = response.body()
                    if (body != null && body.success) {
                        sessionManager.cacheManager.saveCache(LocalCacheManager.KEY_ASSIGNMENTS, body)
                        Result.Success(body)
                    } else {
                        val cached = sessionManager.cacheManager.getCache(LocalCacheManager.KEY_ASSIGNMENTS, AssignmentsResponse::class.java)
                        if (cached != null) {
                            Result.Success(cached)
                        } else {
                            Result.Error(body?.error ?: ApiConfig.NETWORK_ERROR_MSG)
                        }
                    }
                } else {
                    val cached = sessionManager.cacheManager.getCache(LocalCacheManager.KEY_ASSIGNMENTS, AssignmentsResponse::class.java)
                    if (cached != null) {
                        Result.Success(cached)
                    } else {
                        Result.Error(ApiConfig.NETWORK_ERROR_MSG)
                    }
                }
            } catch (e: Exception) {
                val cached = sessionManager.cacheManager.getCache(LocalCacheManager.KEY_ASSIGNMENTS, AssignmentsResponse::class.java)
                if (cached != null) {
                    Result.Success(cached)
                } else {
                    Result.Error(ApiConfig.NETWORK_ERROR_MSG)
                }
            }
        }
    }

    suspend fun uploadAssignment(
        context: Context,
        fileUri: Uri,
        assignmentType: String,
        title: String,
        description: String,
        classId: Int?,
        subjectId: Int?,
        dueDate: String
    ): Result<GenericActionResponse> {
        return withContext(Dispatchers.IO) {
            try {
                val tempFile = createTempFileFromUri(context, fileUri)
                    ?: return@withContext Result.Error("Could not process selected file")

                val requestFile = tempFile.asRequestBody(context.contentResolver.getType(fileUri)?.toMediaTypeOrNull() ?: "application/octet-stream".toMediaTypeOrNull())
                val filePart = MultipartBody.Part.createFormData("file", tempFile.name, requestFile)

                val typeBody = assignmentType.toRequestBody("text/plain".toMediaTypeOrNull())
                val titleBody = title.toRequestBody("text/plain".toMediaTypeOrNull())
                val descBody = description.toRequestBody("text/plain".toMediaTypeOrNull())
                val classBody = classId?.toString()?.toRequestBody("text/plain".toMediaTypeOrNull())
                val subjectBody = subjectId?.toString()?.toRequestBody("text/plain".toMediaTypeOrNull())
                val dateBody = dueDate.toRequestBody("text/plain".toMediaTypeOrNull())

                val apiService = RetrofitClient.getApiService()
                val response = apiService.uploadAssignment(
                    assignmentType = typeBody,
                    title = titleBody,
                    description = descBody,
                    classId = classBody,
                    subjectId = subjectBody,
                    dueDate = dateBody,
                    file = filePart
                )

                tempFile.delete()

                if (response.isSuccessful) {
                    val body = response.body()
                    if (body != null && body.success) {
                        Result.Success(body)
                    } else {
                        Result.Error(body?.error ?: ApiConfig.NETWORK_ERROR_MSG)
                    }
                } else {
                    Result.Error(ApiConfig.NETWORK_ERROR_MSG)
                }
            } catch (e: Exception) {
                Result.Error(ApiConfig.NETWORK_ERROR_MSG)
            }
        }
    }

    suspend fun updateAssignment(
        assignmentId: Int,
        title: String,
        description: String,
        dueDate: String
    ): Result<GenericActionResponse> {
        return withContext(Dispatchers.IO) {
            try {
                val apiService = RetrofitClient.getApiService()
                val req = UpdateAssignmentRequest(
                    assignmentId = assignmentId,
                    title = title,
                    description = description,
                    dueDate = dueDate
                )
                val response = apiService.updateAssignment(req)

                if (response.isSuccessful) {
                    val body = response.body()
                    if (body != null && body.success) {
                        Result.Success(body)
                    } else {
                        Result.Error(body?.error ?: ApiConfig.NETWORK_ERROR_MSG)
                    }
                } else {
                    Result.Error(ApiConfig.NETWORK_ERROR_MSG)
                }
            } catch (e: Exception) {
                Result.Error(ApiConfig.NETWORK_ERROR_MSG)
            }
        }
    }

    suspend fun duplicateAssignment(assignmentId: Int): Result<GenericActionResponse> {
        return withContext(Dispatchers.IO) {
            try {
                val apiService = RetrofitClient.getApiService()
                val req = DuplicateAssignmentRequest(assignmentId = assignmentId)
                val response = apiService.duplicateAssignment(req)

                if (response.isSuccessful) {
                    val body = response.body()
                    if (body != null && body.success) {
                        Result.Success(body)
                    } else {
                        Result.Error(body?.error ?: ApiConfig.NETWORK_ERROR_MSG)
                    }
                } else {
                    Result.Error(ApiConfig.NETWORK_ERROR_MSG)
                }
            } catch (e: Exception) {
                Result.Error(ApiConfig.NETWORK_ERROR_MSG)
            }
        }
    }

    suspend fun deleteAssignment(assignmentId: Int): Result<GenericActionResponse> {
        return withContext(Dispatchers.IO) {
            try {
                val apiService = RetrofitClient.getApiService()
                val response = apiService.deleteAssignment(assignmentId)

                if (response.isSuccessful) {
                    val body = response.body()
                    if (body != null && body.success) {
                        Result.Success(body)
                    } else {
                        Result.Error(body?.error ?: ApiConfig.NETWORK_ERROR_MSG)
                    }
                } else {
                    Result.Error(ApiConfig.NETWORK_ERROR_MSG)
                }
            } catch (e: Exception) {
                Result.Error(ApiConfig.NETWORK_ERROR_MSG)
            }
        }
    }

    private fun createTempFileFromUri(context: Context, uri: Uri): File? {
        return try {
            val contentResolver = context.contentResolver
            val realFileName = getFileNameFromUri(context, uri)
            val tempFile = File(context.cacheDir, realFileName)
            contentResolver.openInputStream(uri)?.use { input ->
                FileOutputStream(tempFile).use { output ->
                    input.copyTo(output)
                }
            }
            tempFile
        } catch (e: Exception) {
            null
        }
    }

    private fun getFileNameFromUri(context: Context, uri: Uri): String {
        var name = ""
        try {
            context.contentResolver.query(uri, null, null, null, null)?.use { cursor ->
                val nameIndex = cursor.getColumnIndex(OpenableColumns.DISPLAY_NAME)
                if (nameIndex != -1 && cursor.moveToFirst()) {
                    name = cursor.getString(nameIndex)
                }
            }
        } catch (e: Exception) {
            // ignore
        }
        if (name.isBlank() || !name.contains(".")) {
            val mimeType = context.contentResolver.getType(uri)
            val ext = when (mimeType) {
                "application/pdf" -> "pdf"
                "image/jpeg" -> "jpg"
                "image/png" -> "png"
                "application/msword" -> "doc"
                "application/vnd.openxmlformats-officedocument.wordprocessingml.document" -> "docx"
                "application/vnd.ms-powerpoint" -> "ppt"
                "application/vnd.openxmlformats-officedocument.presentationml.presentation" -> "pptx"
                "text/plain" -> "txt"
                else -> "pdf"
            }
            name = "file_${System.currentTimeMillis()}.$ext"
        }
        return name
    }
}
