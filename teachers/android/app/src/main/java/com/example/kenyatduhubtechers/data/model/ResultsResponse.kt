package com.example.kenyatduhubtechers.data.model

import com.google.gson.annotations.SerializedName

data class AggregateDistributionDto(
    @SerializedName("id") val id: Int? = null,
    @SerializedName("min_points") val minPoints: Int = 0,
    @SerializedName("max_points") val maxPoints: Int = 84,
    @SerializedName("grade_name") val gradeName: String = "-",
    @SerializedName("description") val description: String? = null
)

data class SchoolLimitsDto(
    @SerializedName("min_subjects") val minSubjects: Int = 7,
    @SerializedName("max_subjects") val maxSubjects: Int = 8
)

data class StudentSubjectAssignmentDto(
    @SerializedName("subject_name") val subjectName: String,
    @SerializedName("is_compulsory") val isCompulsory: Int = 0
)

data class ClassDto(
    @SerializedName("id") val id: Int? = null,
    @SerializedName("class_name") val className: String? = null
)

data class ResultsResponse(
    @SerializedName("success") val success: Boolean,
    @SerializedName("teacher") val teacher: TeacherDto? = null,
    @SerializedName("terms") val terms: List<String> = emptyList(),
    @SerializedName("classes") val classes: List<ClassDto> = emptyList(),
    @SerializedName("streams") val streams: List<StreamDto> = emptyList(),
    @SerializedName("exam_types") val examTypes: List<ExamTypeDto> = emptyList(),
    @SerializedName("grading_scales") val gradingScales: List<GradingScaleDto> = emptyList(),
    @SerializedName("aggregate_distribution") val aggregateDistribution: List<AggregateDistributionDto> = emptyList(),
    @SerializedName("school_limits") val schoolLimits: SchoolLimitsDto? = null,
    @SerializedName("student_subject_assignments") val studentSubjectAssignments: Map<String, List<StudentSubjectAssignmentDto>> = emptyMap(),
    @SerializedName("performance_records") val performanceRecords: List<PerformanceRecordDto> = emptyList(),
    @SerializedName("error") val error: String? = null
)
