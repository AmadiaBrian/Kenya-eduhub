package com.example.kenyaeduhubparents.data.model

import com.google.gson.annotations.SerializedName

data class ResultsResponse(
    @SerializedName("success") val success: Boolean,
    @SerializedName("error") val error: String? = null,
    @SerializedName("children") val children: List<Child> = emptyList(),
    @SerializedName("class_performance_data") val classPerformanceData: Map<String, ClassPerformanceWrapper> = emptyMap(),
    @SerializedName("current_term") val currentTerm: String? = null,
    @SerializedName("terms") val terms: List<String> = emptyList(),
    @SerializedName("current_year") val currentYear: Int = 2026,
    @SerializedName("school_settings") val schoolSettings: SchoolSettings? = null,
    @SerializedName("grading_scales") val gradingScales: List<GradingScale> = emptyList(),
    @SerializedName("aggregate_distribution") val aggregateDistribution: List<AggregateDistribution> = emptyList(),
    @SerializedName("student_subject_assignments") val studentSubjectAssignments: Map<String, List<SubjectAssignment>> = emptyMap()
)

data class ClassPerformanceWrapper(
    @SerializedName("student") val student: StudentInfo? = null,
    @SerializedName("performance") val performance: List<ResultPerformanceRecord> = emptyList()
)

data class StudentInfo(
    @SerializedName("id") val id: Int,
    @SerializedName("name") val name: String,
    @SerializedName("admission_number") val admissionNumber: String,
    @SerializedName("class") val className: String,
    @SerializedName("stream") val streamName: String
)

data class ResultPerformanceRecord(
    @SerializedName("id") val id: Int,
    @SerializedName("subject") val subject: String,
    @SerializedName("marks") val marks: Double,
    @SerializedName("grade") val grade: String,
    @SerializedName("grade_points") val gradePoints: Any? = null,
    @SerializedName("exam_type") val examType: String,
    @SerializedName("remarks") val remarks: String? = null,
    @SerializedName("term") val term: String,
    @SerializedName("year") val year: Any,
    @SerializedName("created_at") val createdAt: String? = null
)

data class SchoolSettings(
    @SerializedName("min_subjects") val minSubjects: Int = 7,
    @SerializedName("max_subjects") val maxSubjects: Int = 8
)

data class GradingScale(
    @SerializedName("min_score") val minScore: Double,
    @SerializedName("max_score") val maxScore: Double,
    @SerializedName("grade_name") val gradeName: String,
    @SerializedName("points") val points: Int,
    @SerializedName("subject_name") val subjectName: String? = null,
    @SerializedName("subject_id") val subjectId: Int? = null
)

data class AggregateDistribution(
    @SerializedName("min_points") val minPoints: Int,
    @SerializedName("max_points") val maxPoints: Int,
    @SerializedName("grade_name") val gradeName: String
)

data class SubjectAssignment(
    @SerializedName("subject_name") val subjectName: String,
    @SerializedName("is_compulsory") val isCompulsory: Int
)
