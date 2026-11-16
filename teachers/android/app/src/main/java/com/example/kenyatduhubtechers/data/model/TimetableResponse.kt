package com.example.kenyatduhubtechers.data.model

import com.google.gson.annotations.SerializedName

data class SchoolBreakDto(
    @SerializedName("id") val id: Int? = null,
    @SerializedName("break_name") val breakName: String? = null,
    @SerializedName("start_time") val startTime: String? = null,
    @SerializedName("end_time") val endTime: String? = null
)

data class TimetableAssignmentDto(
    @SerializedName("id") val id: Int? = null,
    @SerializedName("day_of_week") val dayOfWeek: String? = null,
    @SerializedName("start_time") val startTime: String? = null,
    @SerializedName("end_time") val endTime: String? = null,
    @SerializedName("break_type") val breakType: String? = null,
    @SerializedName("subject_name") val subjectName: String? = null,
    @SerializedName("timetable_name") val timetableName: String? = null,
    @SerializedName("year") val year: String? = null,
    @SerializedName("term") val term: String? = null,
    @SerializedName("status") val status: String? = null,
    @SerializedName("class_name") val className: String? = null,
    @SerializedName("stream_name") val streamName: String? = null,
    @SerializedName("notes") val notes: String? = null
)

data class TimetableResponse(
    @SerializedName("success") val success: Boolean,
    @SerializedName("calendar_status") val calendarStatus: CalendarStatusDto? = null,
    @SerializedName("school_breaks") val schoolBreaks: List<SchoolBreakDto> = emptyList(),
    @SerializedName("timetable_assignments") val timetableAssignments: List<TimetableAssignmentDto> = emptyList(),
    @SerializedName("error") val error: String? = null
)
