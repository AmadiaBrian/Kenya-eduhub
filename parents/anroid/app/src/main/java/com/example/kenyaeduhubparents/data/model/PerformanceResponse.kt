package com.example.kenyaeduhubparents.data.model

import com.google.gson.annotations.SerializedName

data class PerformanceResponse(
    @SerializedName("success") val success: Boolean,
    @SerializedName("error") val error: String? = null,
    @SerializedName("children") val children: List<Child> = emptyList(),
    @SerializedName("performance_data") val performanceData: Map<String, ChildPerformanceWrapper> = emptyMap(),
    @SerializedName("current_term") val currentTerm: String? = null,
    @SerializedName("terms") val terms: List<String> = emptyList(),
    @SerializedName("years") val years: List<Int> = emptyList(),
    @SerializedName("current_year") val currentYear: Int = 2026
)

data class ChildPerformanceWrapper(
    @SerializedName("child") val child: StudentChild? = null,
    @SerializedName("performance") val performance: List<PerformanceRecord> = emptyList(),
    @SerializedName("performance_by_exam_type") val performanceByExamType: Map<String, List<PerformanceRecord>> = emptyMap()
) {
    val allRecords: List<PerformanceRecord>
        get() = if (performance.isNotEmpty()) {
            performance
        } else {
            performanceByExamType.values.flatten()
        }
}

data class StudentChild(
    @SerializedName("id") val id: Int,
    @SerializedName("first_name") val firstName: String? = null,
    @SerializedName("last_name") val lastName: String? = null,
    @SerializedName("name") val name: String? = null,
    @SerializedName("admission_number") val admissionNumber: String? = null,
    @SerializedName("class_name") val className: String? = null,
    @SerializedName("stream_name") val streamName: String? = null,
    @SerializedName("class") val clazz: String? = null,
    @SerializedName("stream") val stream: String? = null
) {
    val fullName: String
        get() = name?.takeIf { it.isNotBlank() } ?: "$firstName $lastName".trim()

    val displayClass: String
        get() = className ?: clazz ?: "Not assigned"

    val displayStream: String
        get() = streamName ?: stream ?: "Not assigned"
}

data class PerformanceRecord(
    @SerializedName("id") val id: Int,
    @SerializedName("subject") val subject: String,
    @SerializedName("marks") val marks: Double,
    @SerializedName("grade") val grade: String,
    @SerializedName("grade_points") val gradePoints: Any? = null,
    @SerializedName("exam_type_name") val examTypeName: String? = null,
    @SerializedName("exam_type") val examType: String? = null,
    @SerializedName("remarks") val remarks: String? = null,
    @SerializedName("term") val term: String? = null,
    @SerializedName("year") val year: Any? = null,
    @SerializedName("created_at") val createdAt: String? = null
) {
    val displayExamType: String
        get() = examTypeName ?: examType ?: "-"
}
