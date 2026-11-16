package com.example.kenyatduhubtechers.data.model

import com.google.gson.annotations.SerializedName

data class SchoolEventDto(
    @SerializedName("id") val id: Int? = null,
    @SerializedName("event_name") val eventName: String? = null,
    @SerializedName("description") val description: String? = null,
    @SerializedName("event_date") val eventDate: String? = null,
    @SerializedName("event_time") val eventTime: String? = null,
    @SerializedName("event_type") val eventType: String? = null
)

data class CalendarResponse(
    @SerializedName("success") val success: Boolean,
    @SerializedName("current_status") val currentStatus: CalendarStatusDto? = null,
    @SerializedName("terms") val terms: List<TermDto> = emptyList(),
    @SerializedName("holidays") val holidays: List<HolidayDto> = emptyList(),
    @SerializedName("school_events") val schoolEvents: List<SchoolEventDto> = emptyList(),
    @SerializedName("error") val error: String? = null
)
