package com.example.kenyaeduhubparents.data.model

import com.google.gson.JsonDeserializationContext
import com.google.gson.JsonDeserializer
import com.google.gson.JsonElement
import com.google.gson.annotations.JsonAdapter
import com.google.gson.annotations.SerializedName
import java.lang.reflect.Type

data class CalendarResponse(
    @SerializedName("success") val success: Boolean,
    @SerializedName("error") val error: String? = null,
    @SerializedName("calendar_status") val calendarStatus: CalendarStatus? = null,
    @SerializedName("terms") val terms: List<TermItem> = emptyList(),
    @SerializedName("holidays") val holidays: List<HolidayItem> = emptyList(),
    @SerializedName("school_events") val schoolEvents: List<SchoolEventItem> = emptyList()
)

data class CalendarStatus(
    @SerializedName("school_status") val schoolStatus: String? = null,
    @SerializedName("current_year") val currentYear: Int? = null,
    @SerializedName("current_term")
    @JsonAdapter(TermItemDeserializer::class)
    val currentTerm: TermItem? = null,
    @SerializedName("current_holiday")
    @JsonAdapter(HolidayItemDeserializer::class)
    val currentHoliday: HolidayItem? = null
) {
    val displayStatus: String
        get() = schoolStatus?.replace("_", " ")?.replaceFirstChar { it.uppercase() } ?: "Unknown"
}

class TermItemDeserializer : JsonDeserializer<TermItem?> {
    override fun deserialize(json: JsonElement?, typeOfT: Type?, context: JsonDeserializationContext?): TermItem? {
        try {
            if (json == null || json.isJsonNull || (json.isJsonPrimitive && json.asJsonPrimitive.isBoolean) || (json.isJsonArray && json.asJsonArray.size() == 0)) {
                return null
            }
            if (json.isJsonObject) {
                return com.google.gson.Gson().fromJson(json, TermItem::class.java)
            }
        } catch (_: Exception) {}
        return null
    }
}

class HolidayItemDeserializer : JsonDeserializer<HolidayItem?> {
    override fun deserialize(json: JsonElement?, typeOfT: Type?, context: JsonDeserializationContext?): HolidayItem? {
        try {
            if (json == null || json.isJsonNull || (json.isJsonPrimitive && json.asJsonPrimitive.isBoolean) || (json.isJsonArray && json.asJsonArray.size() == 0)) {
                return null
            }
            if (json.isJsonObject) {
                return com.google.gson.Gson().fromJson(json, HolidayItem::class.java)
            }
        } catch (_: Exception) {}
        return null
    }
}

data class TermItem(
    @SerializedName("year") val year: Int = 0,
    @SerializedName("term_name") val termName: String = "",
    @SerializedName("term_number") val termNumber: Int = 0,
    @SerializedName("start_date") val startDate: String = "",
    @SerializedName("end_date") val endDate: String = ""
)

data class HolidayItem(
    @SerializedName("holiday_name") val holidayName: String = "",
    @SerializedName("description") val description: String? = null,
    @SerializedName("start_date") val startDate: String = "",
    @SerializedName("end_date") val endDate: String = "",
    @SerializedName("holiday_type") val holidayType: String = ""
)

data class SchoolEventItem(
    @SerializedName("event_name") val eventName: String = "",
    @SerializedName("description") val description: String? = null,
    @SerializedName("event_date") val eventDate: String = "",
    @SerializedName("event_time") val eventTime: String? = null,
    @SerializedName("event_type") val eventType: String = ""
)
