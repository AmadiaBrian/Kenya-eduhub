package com.example.kenyatduhubtechers.data.model

import com.google.gson.annotations.SerializedName

data class StreamDto(
    @SerializedName("id") val id: Int? = null,
    @SerializedName("stream_name") val streamName: String? = null
)

data class ExamTypeDto(
    @SerializedName("id") val id: Int? = null,
    @SerializedName("exam_type_name") val examTypeName: String? = null,
    @SerializedName("exam_type_code") val examTypeCode: String? = null
)

data class SubjectDto(
    @SerializedName("id") val id: Int? = null,
    @SerializedName("subject_name") val subjectName: String? = null
)

data class GradingScaleDto(
    @SerializedName("id") val id: Int? = null,
    @SerializedName("subject_id") val subjectId: Int? = null,
    @SerializedName("subject_name") val subjectName: String? = null,
    @SerializedName("grade_name") val gradeName: String? = null,
    @SerializedName("min_score") val minScore: Double = 0.0,
    @SerializedName("max_score") val maxScore: Double = 100.0,
    @SerializedName("points") val points: Int = 0,
    @SerializedName("grade_description") val gradeDescription: String? = null
)

data class PerformanceRecordDto(
    @SerializedName("id") val id: Int? = null,
    @SerializedName("student_id") val studentId: Int? = null,
    @SerializedName("admission_number") val admissionNumber: String? = null,
    @SerializedName("first_name") val firstName: String? = null,
    @SerializedName("last_name") val lastName: String? = null,
    @SerializedName("gender") val gender: String? = null,
    @SerializedName("class_name") val className: String? = null,
    @SerializedName("stream_name") val streamName: String? = null,
    @SerializedName("term") val term: String? = null,
    @SerializedName("year") val year: String? = null,
    @SerializedName("subject") val rawSubject: String? = null,
    @SerializedName("subject_name") val subjectName: String? = null,
    @SerializedName("exam_type_id") val examTypeId: Int? = null,
    @SerializedName("exam_type_name") val examTypeName: String? = null,
    @SerializedName("marks") val marks: Double = 0.0,
    @SerializedName("grade") val grade: String? = null,
    @SerializedName("grade_points") val gradePoints: Int? = null,
    @SerializedName("points") val points: Int? = null,
    @SerializedName("remarks") val remarks: String? = null
) {
    val subject: String?
        get() = if (!subjectName.isNull_or_empty()) subjectName else rawSubject

    val displayGrade: String
        get() {
            if (!grade.isNull_or_empty() && grade != "No match") return grade!!
            return when {
                marks >= 80 -> "A"
                marks >= 75 -> "A-"
                marks >= 70 -> "B+"
                marks >= 65 -> "B"
                marks >= 60 -> "B-"
                marks >= 55 -> "C+"
                marks >= 50 -> "C"
                marks >= 45 -> "C-"
                marks >= 40 -> "D+"
                marks >= 35 -> "D"
                marks >= 30 -> "D-"
                marks > 0 -> "E"
                else -> "-"
            }
        }

    val displayPoints: Int
        get() {
            if (gradePoints != null && gradePoints > 0) return gradePoints
            if (points != null && points > 0) return points
            val g = displayGrade.uppercase().trim()
            return when (g) {
                "A" -> 12
                "A-" -> 11
                "B+" -> 10
                "B" -> 9
                "B-" -> 8
                "C+" -> 7
                "C" -> 6
                "C-" -> 5
                "D+" -> 4
                "D" -> 3
                "D-" -> 2
                "E" -> 1
                else -> when {
                    marks >= 80 -> 12
                    marks >= 75 -> 11
                    marks >= 70 -> 10
                    marks >= 65 -> 9
                    marks >= 60 -> 8
                    marks >= 55 -> 7
                    marks >= 50 -> 6
                    marks >= 45 -> 5
                    marks >= 40 -> 4
                    marks >= 35 -> 3
                    marks >= 30 -> 2
                    marks > 0 -> 1
                    else -> 0
                }
            }
        }

    private fun String?.isNull_or_empty(): Boolean = this == null || this.trim().isEmpty()
}

data class SavePerformanceRecordItem(
    @SerializedName("student_id") val studentId: Int,
    @SerializedName("term") val term: String,
    @SerializedName("year") val year: String,
    @SerializedName("exam_type_id") val examTypeId: Int,
    @SerializedName("subject") val subject: String,
    @SerializedName("marks") val marks: Double,
    @SerializedName("grade") val grade: String,
    @SerializedName("remarks") val remarks: String
)

data class SavePerformanceRequest(
    @SerializedName("performance") val performance: List<SavePerformanceRecordItem>
)

data class SavePerformanceResponse(
    @SerializedName("success") val success: Boolean,
    @SerializedName("count") val count: Int = 0,
    @SerializedName("message") val message: String? = null,
    @SerializedName("error") val error: String? = null
)

data class PerformanceResponse(
    @SerializedName("success") val success: Boolean,
    @SerializedName("teacher") val teacher: TeacherDto? = null,
    @SerializedName("terms") val terms: List<String> = emptyList(),
    @SerializedName("current_term") val currentTerm: String? = null,
    @SerializedName("streams") val streams: List<StreamDto> = emptyList(),
    @SerializedName("all_subjects") val allSubjects: List<SubjectDto> = emptyList(),
    @SerializedName("exam_types") val examTypes: List<ExamTypeDto> = emptyList(),
    @SerializedName("grading_scales") val gradingScales: List<GradingScaleDto> = emptyList(),
    @SerializedName("performance_records") val performanceRecords: List<PerformanceRecordDto> = emptyList(),
    @SerializedName("subjects_without_performance") val subjectsWithoutPerformance: List<SubjectDto> = emptyList(),
    @SerializedName("error") val error: String? = null
)
