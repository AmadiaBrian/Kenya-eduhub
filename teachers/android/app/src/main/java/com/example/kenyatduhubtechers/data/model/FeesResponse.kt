package com.example.kenyatduhubtechers.data.model

import com.google.gson.annotations.SerializedName

data class StudentFeeSummaryItemDto(
    @SerializedName("student_id") val studentId: Int,
    @SerializedName("admission_number") val admissionNumber: String,
    @SerializedName("student_name") val studentName: String,
    @SerializedName("stream_name") val streamName: String? = "-",
    @SerializedName("total_fee") val totalFee: Double = 0.0,
    @SerializedName("total_paid") val totalPaid: Double = 0.0,
    @SerializedName("total_balance") val totalBalance: Double = 0.0
)

data class FeeTypeSummaryDto(
    @SerializedName("fee_type") val feeType: String,
    @SerializedName("students") val students: List<StudentFeeSummaryItemDto> = emptyList()
)

data class PaymentRecordDto(
    @SerializedName("id") val id: Int? = null,
    @SerializedName("payment_date") val paymentDate: String? = null,
    @SerializedName("fee_type") val feeType: String? = "Tuition",
    @SerializedName("amount") val amount: Double = 0.0,
    @SerializedName("payment_method") val paymentMethod: String? = "M-Pesa",
    @SerializedName("receipt_number") val receiptNumber: String? = null,
    @SerializedName("status") val status: String? = "completed"
)

data class StudentFeeDetailsDto(
    @SerializedName("student") val student: StudentAffairsItemDto? = null,
    @SerializedName("year_total_fees") val yearTotalFees: Double = 0.0,
    @SerializedName("year_total_paid") val yearTotalPaid: Double = 0.0,
    @SerializedName("year_balance") val yearBalance: Double = 0.0,
    @SerializedName("payments") val payments: List<PaymentRecordDto> = emptyList()
)

data class FeesResponse(
    @SerializedName("success") val success: Boolean,
    @SerializedName("teacher") val teacher: TeacherDto? = null,
    @SerializedName("students") val students: List<StudentAffairsItemDto> = emptyList(),
    @SerializedName("stream_fee_summary") val streamFeeSummary: List<FeeTypeSummaryDto> = emptyList(),
    @SerializedName("student_fee_details") val studentFeeDetails: StudentFeeDetailsDto? = null,
    @SerializedName("error") val error: String? = null
)

data class SendFeeReminderRequest(
    @SerializedName("fee_type") val feeType: String = "All Fee Types",
    @SerializedName("status") val status: String = "All Status"
)

data class SendFeeReminderResponse(
    @SerializedName("success") val success: Boolean,
    @SerializedName("message") val message: String? = null,
    @SerializedName("error") val error: String? = null
)

data class StatementResponse(
    @SerializedName("success") val success: Boolean,
    @SerializedName("statement_url") val statementUrl: String? = null,
    @SerializedName("statement_filename") val statementFilename: String? = null,
    @SerializedName("error") val error: String? = null
)

data class StudentStatementRequest(
    @SerializedName("student_id") val studentId: Int
)
