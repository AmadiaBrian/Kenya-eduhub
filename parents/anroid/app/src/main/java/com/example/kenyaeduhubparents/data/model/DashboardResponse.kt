package com.example.kenyaeduhubparents.data.model

import com.google.gson.annotations.SerializedName

data class DashboardResponse(
    @SerializedName("success") val success: Boolean,
    @SerializedName("error") val error: String? = null,
    @SerializedName("parent") val parent: Parent? = null,
    @SerializedName("children") val children: List<Child> = emptyList(),
    @SerializedName("stats") val stats: DashboardStats? = null,
    @SerializedName("current_term") val currentTerm: String? = null,
    @SerializedName("terms") val terms: List<String> = emptyList(),
    @SerializedName("notifications") val notifications: List<NotificationItem> = emptyList()
)

data class Child(
    @SerializedName("id") val id: Int,
    @SerializedName("name") val name: String,
    @SerializedName("admission_number") val admissionNumber: String,
    @SerializedName("class") val className: String,
    @SerializedName("stream") val streamName: String
)

data class DashboardStats(
    @SerializedName("total_children") val totalChildren: Int,
    @SerializedName("total_fees_due") val totalFeesDue: Double,
    @SerializedName("attendance_rate") val attendanceRate: Double,
    @SerializedName("performance_records") val performanceRecords: Int
)

data class NotificationItem(
    @SerializedName("id") val id: Int,
    @SerializedName("title") val title: String,
    @SerializedName("message") val message: String,
    @SerializedName("created_at") val createdAt: String
)
