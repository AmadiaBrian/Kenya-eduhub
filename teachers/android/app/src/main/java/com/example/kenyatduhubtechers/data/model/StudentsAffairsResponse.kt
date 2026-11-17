package com.example.kenyatduhubtechers.data.model

import com.google.gson.annotations.SerializedName

data class FeeBalanceDetailDto(
    @SerializedName("total_fees") val totalFees: Double = 0.0,
    @SerializedName("total_paid") val totalPaid: Double = 0.0,
    @SerializedName("balance") val balance: Double = 0.0
)

data class StudentAffairsItemDto(
    @SerializedName("id") val id: Int,
    @SerializedName("admission_number") val admissionNumber: String,
    @SerializedName("first_name") val firstName: String,
    @SerializedName("last_name") val lastName: String,
    @SerializedName("gender") val gender: String? = "Male",
    @SerializedName("date_of_birth") val dateOfBirth: String? = null,
    @SerializedName("admission_date") val admissionDate: String? = null,
    @SerializedName("status") val status: String? = "active",
    @SerializedName("class_id") val classId: Int? = null,
    @SerializedName("stream_id") val streamId: Int? = null,
    @SerializedName("class_name") val className: String? = null,
    @SerializedName("stream_name") val streamName: String? = null,
    @SerializedName("total_fees") val totalFees: Double? = 0.0,
    @SerializedName("total_paid") val totalPaid: Double? = 0.0,
    @SerializedName("total_balance") val totalBalance: Double? = 0.0,
    @SerializedName("fee_balances") val feeBalances: Map<String, FeeBalanceDetailDto>? = emptyMap()
) {
    val fullName: String
        get() = "$firstName $lastName".trim()
}

data class DisciplinaryRecordDto(
    @SerializedName("id") val id: Int? = null,
    @SerializedName("student_id") val studentId: Int? = null,
    @SerializedName("admission_number") val admissionNumber: String? = null,
    @SerializedName("student_name") val studentName: String? = null,
    @SerializedName("incident_date") val incidentDate: String? = null,
    @SerializedName("action_type") val actionType: String? = null,
    @SerializedName("severity") val severity: String? = null,
    @SerializedName("title") val title: String? = null,
    @SerializedName("status") val status: String? = null
)

data class StudentsAffairsResponse(
    @SerializedName("success") val success: Boolean,
    @SerializedName("data") val data: List<StudentAffairsItemDto> = emptyList(),
    @SerializedName("classes") val classes: List<ClassDto> = emptyList(),
    @SerializedName("streams") val streams: List<StreamDto> = emptyList(),
    @SerializedName("disciplinary_records") val disciplinaryRecords: List<DisciplinaryRecordDto> = emptyList(),
    @SerializedName("error") val error: String? = null
)
