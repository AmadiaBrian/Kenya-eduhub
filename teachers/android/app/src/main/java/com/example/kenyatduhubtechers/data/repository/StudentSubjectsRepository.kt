package com.example.kenyatduhubtechers.data.repository

import com.example.kenyatduhubtechers.data.local.LocalCacheManager
import com.example.kenyatduhubtechers.data.local.SessionManager
import com.example.kenyatduhubtechers.data.model.AssignSubjectsRequest
import com.example.kenyatduhubtechers.data.model.AssignSubjectsResponse
import com.example.kenyatduhubtechers.data.model.GenericActionResponse
import com.example.kenyatduhubtechers.data.model.StudentSubjectDetailDto
import com.example.kenyatduhubtechers.data.model.StudentsAffairsResponse
import com.example.kenyatduhubtechers.data.network.ApiConfig
import com.example.kenyatduhubtechers.data.network.RetrofitClient
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext

data class StudentSubjectMapItem(
    val studentId: Int,
    val subjectNames: String?
)

data class CombinedStudentSubjectsData(
    val affairsResponse: StudentsAffairsResponse,
    val subjectAssignments: List<StudentSubjectMapItem>
)

class StudentSubjectsRepository(private val sessionManager: SessionManager) {

    init {
        RetrofitClient.initialize(sessionManager)
    }

    suspend fun getStudentsAndSubjectsData(
        classId: Int? = null,
        streamId: Int? = null
    ): Result<CombinedStudentSubjectsData> {
        return withContext(Dispatchers.IO) {
            try {
                val apiService = RetrofitClient.getApiService()
                val response = apiService.getStudentsAffairs(classId = classId, streamId = streamId)

                if (!response.isSuccessful) {
                    val cached = sessionManager.cacheManager.getCache(LocalCacheManager.KEY_STUDENT_SUBJECTS, CombinedStudentSubjectsData::class.java)
                    return@withContext if (cached != null) Result.Success(cached) else Result.Error(ApiConfig.NETWORK_ERROR_MSG)
                }

                val affairs = response.body()
                if (affairs == null || !affairs.success) {
                    val cached = sessionManager.cacheManager.getCache(LocalCacheManager.KEY_STUDENT_SUBJECTS, CombinedStudentSubjectsData::class.java)
                    return@withContext if (cached != null) Result.Success(cached) else Result.Error(affairs?.error ?: ApiConfig.NETWORK_ERROR_MSG)
                }

                val studentIds = affairs.data.map { it.id }
                val assignments = if (studentIds.isNotEmpty()) {
                    val idsJoined = studentIds.joinToString(",")
                    val subjectsResp = apiService.getStudentSubjects(idsJoined)
                    if (subjectsResp.isSuccessful && subjectsResp.body()?.success == true) {
                        subjectsResp.body()?.data?.map {
                            StudentSubjectMapItem(it.studentId, it.subjectNames)
                        } ?: emptyList()
                    } else {
                        emptyList()
                    }
                } else {
                    emptyList()
                }

                val combined = CombinedStudentSubjectsData(
                    affairsResponse = affairs,
                    subjectAssignments = assignments
                )
                sessionManager.cacheManager.saveCache(LocalCacheManager.KEY_STUDENT_SUBJECTS, combined)
                Result.Success(combined)
            } catch (e: Exception) {
                val cached = sessionManager.cacheManager.getCache(LocalCacheManager.KEY_STUDENT_SUBJECTS, CombinedStudentSubjectsData::class.java)
                if (cached != null) {
                    Result.Success(cached)
                } else {
                    Result.Error(ApiConfig.NETWORK_ERROR_MSG)
                }
            }
        }
    }

    suspend fun getStudentSubjectDetails(studentId: Int): Result<List<StudentSubjectDetailDto>> {
        return withContext(Dispatchers.IO) {
            try {
                val apiService = RetrofitClient.getApiService()
                val response = apiService.getStudentSubjectDetails(studentId = studentId)

                if (response.isSuccessful) {
                    val body = response.body()
                    if (body != null && body.success) {
                        Result.Success(body.data)
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

    suspend fun assignSubjectToStudents(studentIds: List<Int>, subjectId: Int): Result<AssignSubjectsResponse> {
        return withContext(Dispatchers.IO) {
            try {
                val apiService = RetrofitClient.getApiService()
                val req = AssignSubjectsRequest(studentIds = studentIds, subjectId = subjectId)
                val response = apiService.assignSubjectToStudents(req)

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

    suspend fun autoAssignCompulsorySubjects(classId: Int, streamId: Int? = null): Result<AssignSubjectsResponse> {
        return withContext(Dispatchers.IO) {
            try {
                val apiService = RetrofitClient.getApiService()
                val response = apiService.autoAssignCompulsorySubjects(classId = classId, streamId = streamId)

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

    suspend fun removeStudentSubject(studentId: Int, subjectId: Int): Result<GenericActionResponse> {
        return withContext(Dispatchers.IO) {
            try {
                val apiService = RetrofitClient.getApiService()
                val response = apiService.removeStudentSubject(studentId = studentId, subjectId = subjectId)

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
}
