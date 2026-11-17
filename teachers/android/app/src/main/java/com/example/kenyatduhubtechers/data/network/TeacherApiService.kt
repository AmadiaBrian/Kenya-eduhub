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
import com.example.kenyatduhubtechers.data.model.AssignSubjectsRequest
import com.example.kenyatduhubtechers.data.model.AssignSubjectsResponse
import com.example.kenyatduhubtechers.data.model.CreateLeaveoutChitRequest
import com.example.kenyatduhubtechers.data.model.DutyResponse
import com.example.kenyatduhubtechers.data.model.GenericActionResponse
import com.example.kenyatduhubtechers.data.model.RecordCleanlinessRequest
import com.example.kenyatduhubtechers.data.model.ReportIncidentRequest
import com.example.kenyatduhubtechers.data.model.StudentSubjectDetailsResponse
import com.example.kenyatduhubtechers.data.model.StudentSubjectsListResponse
import com.example.kenyatduhubtechers.data.model.StudentsAffairsResponse
import com.example.kenyatduhubtechers.data.model.StudentsResponse
import com.example.kenyatduhubtechers.data.model.TimetableResponse
import com.example.kenyatduhubtechers.data.model.AssignmentsResponse
import com.example.kenyatduhubtechers.data.model.AttendanceHistoryResponse
import com.example.kenyatduhubtechers.data.model.AttendanceMainResponse
import com.example.kenyatduhubtechers.data.model.DuplicateAssignmentRequest
import com.example.kenyatduhubtechers.data.model.SaveAttendanceRequest
import com.example.kenyatduhubtechers.data.model.StudentsForAttendanceResponse
import com.example.kenyatduhubtechers.data.model.UpdateAssignmentRequest
import com.example.kenyatduhubtechers.data.model.UpdateProfileRequest
import com.example.kenyatduhubtechers.data.model.UpdateProfileResponse
import okhttp3.MultipartBody
import okhttp3.RequestBody
import retrofit2.Response
import retrofit2.http.Body
import retrofit2.http.DELETE
import retrofit2.http.Field
import retrofit2.http.FormUrlEncoded
import retrofit2.http.GET
import retrofit2.http.Multipart
import retrofit2.http.POST
import retrofit2.http.Part
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

    @GET(ApiConfig.Endpoints.STUDENT_SUBJECTS)
    suspend fun getStudentSubjects(
        @Query("student_ids") studentIds: String
    ): Response<StudentSubjectsListResponse>

    @GET(ApiConfig.Endpoints.STUDENT_SUBJECTS)
    suspend fun getStudentSubjectDetails(
        @Query("get_subject_ids") getSubjectIds: Int = 1,
        @Query("student_id") studentId: Int
    ): Response<StudentSubjectDetailsResponse>

    @POST(ApiConfig.Endpoints.STUDENT_SUBJECTS)
    suspend fun assignSubjectToStudents(
        @Body request: AssignSubjectsRequest
    ): Response<AssignSubjectsResponse>

    @POST(ApiConfig.Endpoints.STUDENT_SUBJECTS)
    suspend fun autoAssignCompulsorySubjects(
        @Query("auto_assign_compulsory") autoAssign: Int = 1,
        @Query("class_id") classId: Int,
        @Query("stream_id") streamId: Int? = null
    ): Response<AssignSubjectsResponse>

    @DELETE(ApiConfig.Endpoints.STUDENT_SUBJECTS)
    suspend fun removeStudentSubject(
        @Query("student_id") studentId: Int,
        @Query("subject_id") subjectId: Int
    ): Response<GenericActionResponse>

    @GET(ApiConfig.Endpoints.DUTY)
    suspend fun getDutyData(): Response<DutyResponse>

    @POST(ApiConfig.Endpoints.DUTY)
    suspend fun createLeaveoutChit(
        @Body request: CreateLeaveoutChitRequest
    ): Response<GenericActionResponse>

    @POST(ApiConfig.Endpoints.DUTY)
    suspend fun reportIncident(
        @Body request: ReportIncidentRequest
    ): Response<GenericActionResponse>

    @POST(ApiConfig.Endpoints.DUTY)
    suspend fun recordCleanliness(
        @Body request: RecordCleanlinessRequest
    ): Response<GenericActionResponse>

    @GET(ApiConfig.Endpoints.ASSIGNMENTS)
    suspend fun getAssignments(): Response<AssignmentsResponse>

    @Multipart
    @POST("upload_assignment.php")
    suspend fun uploadAssignment(
        @Part("assignment_type") assignmentType: RequestBody,
        @Part("title") title: RequestBody,
        @Part("description") description: RequestBody,
        @Part("class_id") classId: RequestBody?,
        @Part("subject_id") subjectId: RequestBody?,
        @Part("due_date") dueDate: RequestBody?,
        @Part file: MultipartBody.Part
    ): Response<GenericActionResponse>

    @POST("update_assignment.php")
    suspend fun updateAssignment(
        @Body request: UpdateAssignmentRequest
    ): Response<GenericActionResponse>

    @POST("duplicate_assignment.php")
    suspend fun duplicateAssignment(
        @Body request: DuplicateAssignmentRequest
    ): Response<GenericActionResponse>

    @FormUrlEncoded
    @POST("delete_assignment.php")
    suspend fun deleteAssignment(
        @Field("assignment_id") assignmentId: Int
    ): Response<GenericActionResponse>

    @GET(ApiConfig.Endpoints.ATTENDANCE)
    suspend fun getAttendanceMainData(): Response<AttendanceMainResponse>

    @GET(ApiConfig.Endpoints.ATTENDANCE)
    suspend fun getStudentsForAttendance(
        @Query("action") action: String = "get_students",
        @Query("class_id") classId: Int,
        @Query("date") date: String
    ): Response<StudentsForAttendanceResponse>

    @POST(ApiConfig.Endpoints.ATTENDANCE)
    suspend fun saveAttendance(
        @Body request: SaveAttendanceRequest
    ): Response<GenericActionResponse>

    @GET(ApiConfig.Endpoints.ATTENDANCE)
    suspend fun getAttendanceHistory(
        @Query("action") action: String = "get_attendance_history",
        @Query("class_id") classId: Int,
        @Query("start_date") startDate: String,
        @Query("end_date") endDate: String
    ): Response<AttendanceHistoryResponse>
}
