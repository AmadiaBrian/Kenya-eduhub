package com.example.kenyatduhubtechers.data.network

import com.example.kenyatduhubtechers.data.model.CalendarResponse
import com.example.kenyatduhubtechers.data.model.DashboardResponse
import com.example.kenyatduhubtechers.data.model.FeesResponse
import com.example.kenyatduhubtechers.data.model.LoginRequest
import com.example.kenyatduhubtechers.data.model.LoginResponse
import com.example.kenyatduhubtechers.data.model.ParentsResponse
import com.example.kenyatduhubtechers.data.model.PerformanceResponse
import com.example.kenyatduhubtechers.data.model.ProfileResponse
import com.example.kenyatduhubtechers.data.model.ResultsResponse
import com.example.kenyatduhubtechers.data.model.SavePerformanceRequest
import com.example.kenyatduhubtechers.data.model.SavePerformanceResponse
import com.example.kenyatduhubtechers.data.model.SendFeeReminderRequest
import com.example.kenyatduhubtechers.data.model.SendFeeReminderResponse
import com.example.kenyatduhubtechers.data.model.SendParentEmailRequest
import com.example.kenyatduhubtechers.data.model.SendParentEmailResponse
import com.example.kenyatduhubtechers.data.model.StatementResponse
import com.example.kenyatduhubtechers.data.model.StudentStatementRequest
import com.example.kenyatduhubtechers.data.model.StudentsAffairsResponse
import com.example.kenyatduhubtechers.data.model.StudentsResponse
import com.example.kenyatduhubtechers.data.model.TimetableResponse
import com.example.kenyatduhubtechers.data.model.UpdateProfileRequest
import com.example.kenyatduhubtechers.data.model.UpdateProfileResponse
import retrofit2.Response
import retrofit2.http.Body
import retrofit2.http.GET
import retrofit2.http.POST
import retrofit2.http.Query

interface TeacherApiService {

    @POST(ApiConfig.Endpoints.LOGIN)
    suspend fun login(
        @Body request: LoginRequest
    ): Response<LoginResponse>

    @GET(ApiConfig.Endpoints.DASHBOARD)
    suspend fun getDashboard(): Response<DashboardResponse>

    @GET(ApiConfig.Endpoints.CALENDAR)
    suspend fun getCalendar(): Response<CalendarResponse>

    @GET(ApiConfig.Endpoints.TIMETABLE)
    suspend fun getTimetable(): Response<TimetableResponse>

    @GET(ApiConfig.Endpoints.PERFORMANCE)
    suspend fun getPerformance(): Response<PerformanceResponse>

    @POST(ApiConfig.Endpoints.PERFORMANCE)
    suspend fun savePerformance(
        @Body request: SavePerformanceRequest
    ): Response<SavePerformanceResponse>

    @GET(ApiConfig.Endpoints.STUDENTS)
    suspend fun getStudents(
        @Query("class_id") classId: Int? = null,
        @Query("stream_id") streamId: Int? = null
    ): Response<StudentsResponse>

    @GET(ApiConfig.Endpoints.STUDENTS)
    suspend fun getStudentsAffairs(
        @Query("class_id") classId: Int? = null,
        @Query("stream_id") streamId: Int? = null,
        @Query("status") status: String? = null
    ): Response<StudentsAffairsResponse>

    @GET(ApiConfig.Endpoints.RESULTS)
    suspend fun getResults(): Response<ResultsResponse>

    @GET(ApiConfig.Endpoints.PROFILE)
    suspend fun getProfile(): Response<ProfileResponse>

    @POST(ApiConfig.Endpoints.PROFILE)
    suspend fun updateProfile(
        @Body request: UpdateProfileRequest
    ): Response<UpdateProfileResponse>

    @GET(ApiConfig.Endpoints.PARENTS)
    suspend fun getParents(
        @Query("relationship") relationship: String? = null
    ): Response<ParentsResponse>

    @POST(ApiConfig.Endpoints.PARENTS)
    suspend fun sendParentEmail(
        @Body request: SendParentEmailRequest
    ): Response<SendParentEmailResponse>

    @GET(ApiConfig.Endpoints.FEES)
    suspend fun getFees(
        @Query("student_id") studentId: Int? = null
    ): Response<FeesResponse>

    @POST(ApiConfig.Endpoints.FEES)
    suspend fun sendFeeReminders(
        @Body request: SendFeeReminderRequest
    ): Response<SendFeeReminderResponse>

    @POST("generate_class_fee_statement.php")
    suspend fun generateClassFeeStatement(): Response<StatementResponse>

    @POST("generate_student_fee_statement.php")
    suspend fun generateStudentFeeStatement(
        @Body request: StudentStatementRequest
    ): Response<StatementResponse>
}
