package com.example.kenyaeduhubparents.data.model

import com.google.gson.annotations.SerializedName

data class ChildrenResponse(
    @SerializedName("success") val success: Boolean,
    @SerializedName("error") val error: String? = null,
    @SerializedName("data") var data: List<ChildDetail> = emptyList()
)

data class ChildDetail(
    @SerializedName("id") val id: Int,
    @SerializedName("first_name") val firstName: String,
    @SerializedName("last_name") val lastName: String,
    @SerializedName("admission_number") val admissionNumber: String,
    @SerializedName("class_id") val classId: Int?,
    @SerializedName("stream_id") val streamId: Int?,
    @SerializedName("class_name") val className: String?,
    @SerializedName("stream_name") val streamName: String?,
    @SerializedName("fee_balance") var feeBalance: Double,
    @SerializedName("total_fees") var totalFees: Double,
    @SerializedName("total_paid") var totalPaid: Double,
    @SerializedName("attendance_rate") val attendanceRate: Double,
    @SerializedName("performance_records") val performanceRecords: Int,
    var feeStructureStatus: List<FeeStructureStatus> = emptyList()
) {
    val fullName: String
        get() = "$firstName $lastName"
}
