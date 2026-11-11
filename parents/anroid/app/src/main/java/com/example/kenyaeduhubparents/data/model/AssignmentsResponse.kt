package com.example.kenyaeduhubparents.data.model

import com.google.gson.annotations.SerializedName

data class AssignmentsResponse(
    @SerializedName("success") val success: Boolean,
    @SerializedName("error") val error: String? = null,
    @SerializedName("children") val children: List<Child> = emptyList(),
    @SerializedName("assignments") val assignments: List<AssignmentItem> = emptyList()
)

data class AssignmentItem(
    @SerializedName("id") val id: Int,
    @SerializedName("title") val title: String,
    @SerializedName("description") val description: String,
    @SerializedName("subject") val subject: String,
    @SerializedName("class") val className: String,
    @SerializedName("teacher") val teacher: String,
    @SerializedName("due_date") val dueDate: String,
    @SerializedName("created_at") val createdAt: String,
    @SerializedName("assignment_type") val assignmentType: String,
    @SerializedName("badge_type") val badgeType: String,
    @SerializedName("file_name") val fileName: String? = null,
    @SerializedName("attachment") val attachment: String? = null
)
