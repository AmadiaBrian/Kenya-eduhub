package com.example.kenyaeduhubparents.data.network

import com.example.kenyaeduhubparents.data.model.AssignmentsResponse
import com.example.kenyaeduhubparents.data.model.AttendanceResponse
import com.example.kenyaeduhubparents.data.model.CalendarResponse
import com.example.kenyaeduhubparents.data.model.ChildrenResponse
import com.example.kenyaeduhubparents.data.model.DashboardResponse
import com.example.kenyaeduhubparents.data.model.FeesResponse
import com.example.kenyaeduhubparents.data.model.LibraryFinesResponse
import com.example.kenyaeduhubparents.data.model.LoginRequest
import com.example.kenyaeduhubparents.data.model.LoginResponse
import com.example.kenyaeduhubparents.data.model.ParentProfileResponse
import com.example.kenyaeduhubparents.data.model.UpdateParentProfileRequest
import com.example.kenyaeduhubparents.data.model.PerformanceResponse
import com.example.kenyaeduhubparents.data.model.ResultsResponse
import okhttp3.ResponseBody
import retrofit2.Response
import retrofit2.http.Body
import retrofit2.http.Field
import retrofit2.http.FormUrlEncoded
import retrofit2.http.GET
import retrofit2.http.Header
import retrofit2.http.POST
import retrofit2.http.Query

interface ApiService {
    @POST("login.php")
    suspend fun login(
        @Body request: LoginRequest
    ): Response<LoginResponse>

    @GET("dashboard.php")
    suspend fun getDashboard(
        @Query("parent_id") parentId: Int
    ): Response<DashboardResponse>

    @GET("performance.php")
    suspend fun getPerformance(
        @Query("parent_id") parentId: Int,
        @Query("child_id") childId: String? = null,
        @Query("term") term: String? = null,
        @Query("year") year: String? = null,
        @Query("exam_type") examType: String? = null
    ): Response<PerformanceResponse>

    @GET("results.php")
    suspend fun getResults(
        @Query("parent_id") parentId: Int,
        @Query("scope") scope: String? = null
    ): Response<ResultsResponse>

    @GET("assignments.php")
    suspend fun getAssignments(
        @Query("parent_id") parentId: Int
    ): Response<AssignmentsResponse>

    @GET("download_assignment.php")
    suspend fun downloadAssignment(
        @Query("assignment_id") assignmentId: Int,
        @Query("parent_id") parentId: Int
    ): Response<ResponseBody>

    @GET("fees.php")
    suspend fun getFees(
        @Query("parent_id") parentId: Int
    ): Response<FeesResponse>

    @GET("children.php")
    suspend fun getChildren(
        @Query("parent_id") parentId: Int
    ): Response<ChildrenResponse>

    @GET("calendar.php")
    suspend fun getCalendar(
        @Query("parent_id") parentId: Int
    ): Response<CalendarResponse>

    @GET("attendance.php")
    suspend fun getAttendance(
        @Query("parent_id") parentId: Int,
        @Query("child_id") childId: String? = null,
        @Query("term") term: String? = null,
        @Query("year") year: String? = null
    ): Response<AttendanceResponse>

    @GET("fines.php")
    suspend fun getLibraryFines(
        @Query("parent_id") parentId: Int
    ): Response<LibraryFinesResponse>

    @POST("mpesa_payment.php")
    @FormUrlEncoded
    suspend fun initiateMpesaPayment(
        @Field("student_id") studentId: String,
        @Field("fee_type") feeType: String,
        @Field("term") term: String,
        @Field("year") year: String,
        @Field("amount") amount: String,
        @Field("phone") phone: String,
        @Field("parent_phone") parentPhone: String
    ): Response<ResponseBody>

    @POST("mpesa_fine_payment.php")
    @FormUrlEncoded
    suspend fun initiateLibraryFinePayment(
        @Field("fine_id") fineId: String,
        @Field("amount") amount: String,
        @Field("phone") phone: String
    ): Response<ResponseBody>

    @POST("check_fee_payment_status.php")
    @FormUrlEncoded
    suspend fun checkMpesaStatus(
        @Field("checkoutRequestID") checkoutRequestID: String
    ): Response<ResponseBody>

    @GET("profile.php")
    suspend fun getParentProfile(
        @Header("Cookie") sessionCookie: String
    ): Response<ParentProfileResponse>

    @POST("profile.php")
    suspend fun updateParentProfile(
        @Header("Cookie") sessionCookie: String,
        @Body request: UpdateParentProfileRequest
    ): Response<ParentProfileResponse>
}
