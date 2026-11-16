package com.example.kenyatduhubtechers.ui.calendar

import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.BeachAccess
import androidx.compose.material.icons.filled.CalendarMonth
import androidx.compose.material.icons.filled.Event
import androidx.compose.material.icons.filled.EventBusy
import androidx.compose.material.icons.filled.School
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.HorizontalDivider
import androidx.compose.material3.Icon
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.example.kenyatduhubtechers.data.model.HolidayDto
import com.example.kenyatduhubtechers.data.model.SchoolEventDto
import com.example.kenyatduhubtechers.data.model.TermDto
import com.example.kenyatduhubtechers.ui.theme.EduHubBackground
import com.example.kenyatduhubtechers.ui.theme.EduHubBorder
import com.example.kenyatduhubtechers.ui.theme.EduHubError
import com.example.kenyatduhubtechers.ui.theme.EduHubOrange
import com.example.kenyatduhubtechers.ui.theme.EduHubSuccess
import com.example.kenyatduhubtechers.ui.theme.EduHubTextDark
import com.example.kenyatduhubtechers.ui.theme.EduHubTextMuted
import java.text.SimpleDateFormat
import java.util.Date
import java.util.Locale

@Composable
fun CalendarScreen(
    viewModel: CalendarViewModel,
    modifier: Modifier = Modifier
) {
    val uiState by viewModel.uiState.collectAsState()
    val currentDateStr = SimpleDateFormat("MMMM d, yyyy", Locale.getDefault()).format(Date())

    if (uiState.isLoading) {
        Box(
            modifier = modifier.fillMaxSize(),
            contentAlignment = Alignment.Center
        ) {
            CircularProgressIndicator(color = EduHubOrange)
        }
        return
    }

    Column(
        modifier = modifier
            .fillMaxSize()
            .verticalScroll(rememberScrollState())
            .padding(16.dp)
    ) {
        Text(
            text = "School Calendar",
            fontSize = 22.sp,
            fontWeight = FontWeight.Bold,
            color = EduHubTextDark,
            modifier = Modifier.padding(bottom = 16.dp)
        )

        // 1. School Session Status Card (Matching Dashboard Orange Status Card Style)
        val status = uiState.currentStatus
        val schoolStatus = status?.schoolStatus ?: "in_session"
        val isHoliday = status?.isHoliday ?: false

        Card(
            modifier = Modifier
                .fillMaxWidth()
                .padding(bottom = 20.dp),
            shape = RoundedCornerShape(12.dp),
            colors = CardDefaults.cardColors(containerColor = EduHubOrange),
            elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)
        ) {
            Column(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(20.dp)
            ) {
                Text(
                    text = "School Session Status",
                    fontSize = 18.sp,
                    fontWeight = FontWeight.Bold,
                    color = Color.White
                )

                Spacer(modifier = Modifier.height(14.dp))

                when {
                    isHoliday || schoolStatus == "holiday" -> {
                        Row(verticalAlignment = Alignment.CenterVertically) {
                            Icon(Icons.Default.BeachAccess, contentDescription = null, tint = Color.White, modifier = Modifier.size(28.dp))
                            Spacer(modifier = Modifier.width(12.dp))
                            Column {
                                Text("School is on Holiday", fontSize = 15.sp, fontWeight = FontWeight.Bold, color = Color.White)
                                Text(
                                    text = "${status?.currentHoliday?.holidayName ?: "Holiday"} (${status?.currentHoliday?.startDate ?: ""} - ${status?.currentHoliday?.endDate ?: ""})",
                                    fontSize = 13.sp,
                                    color = Color.White.copy(alpha = 0.9f)
                                )
                            }
                        }
                    }
                    schoolStatus == "break" -> {
                        Row(verticalAlignment = Alignment.CenterVertically) {
                            Icon(Icons.Default.EventBusy, contentDescription = null, tint = Color.White, modifier = Modifier.size(28.dp))
                            Spacer(modifier = Modifier.width(12.dp))
                            Column {
                                Text("School is on Break", fontSize = 15.sp, fontWeight = FontWeight.Bold, color = Color.White)
                                Text("No active term for current date", fontSize = 13.sp, color = Color.White.copy(alpha = 0.9f))
                            }
                        }
                    }
                    else -> {
                        Row(verticalAlignment = Alignment.CenterVertically) {
                            Icon(Icons.Default.School, contentDescription = null, tint = Color.White, modifier = Modifier.size(28.dp))
                            Spacer(modifier = Modifier.width(12.dp))
                            Column {
                                Text("School is in Session", fontSize = 15.sp, fontWeight = FontWeight.Bold, color = Color.White)
                                Text(status?.currentTerm?.termName ?: "Term 1", fontSize = 13.sp, fontWeight = FontWeight.SemiBold, color = Color.White)
                                if (status?.currentTerm?.startDate != null) {
                                    Text(
                                        text = "${status.currentTerm.startDate} - ${status.currentTerm.endDate}",
                                        fontSize = 12.sp,
                                        color = Color.White.copy(alpha = 0.85f)
                                    )
                                }
                            }
                        }
                    }
                }

                Spacer(modifier = Modifier.height(14.dp))
                HorizontalDivider(color = Color.White.copy(alpha = 0.35f))
                Spacer(modifier = Modifier.height(12.dp))

                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.SpaceBetween,
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    Row(verticalAlignment = Alignment.CenterVertically) {
                        Icon(Icons.Default.CalendarMonth, contentDescription = null, tint = Color.White, modifier = Modifier.size(16.dp))
                        Spacer(modifier = Modifier.width(6.dp))
                        Text("Academic Year: ${status?.currentYear ?: SimpleDateFormat("yyyy", Locale.getDefault()).format(Date())}", fontSize = 12.sp, color = Color.White)
                    }

                    Row(verticalAlignment = Alignment.CenterVertically) {
                        Icon(Icons.Default.Event, contentDescription = null, tint = Color.White, modifier = Modifier.size(16.dp))
                        Spacer(modifier = Modifier.width(6.dp))
                        Text("Today: $currentDateStr", fontSize = 12.sp, color = Color.White)
                    }
                }
            }
        }

        // 2. Academic Terms Card
        Card(
            modifier = Modifier.fillMaxWidth(),
            colors = CardDefaults.cardColors(containerColor = EduHubBackground),
            border = BorderStroke(1.dp, EduHubBorder),
            shape = RoundedCornerShape(8.dp),
            elevation = CardDefaults.cardElevation(defaultElevation = 0.dp)
        ) {
            Column(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(20.dp)
            ) {
                Text(
                    text = "Academic Terms",
                    fontSize = 18.sp,
                    fontWeight = FontWeight.Bold,
                    color = EduHubTextDark,
                    modifier = Modifier.padding(bottom = 14.dp)
                )

                if (uiState.terms.isEmpty()) {
                    Text(
                        text = "No terms recorded.",
                        fontSize = 13.sp,
                        color = EduHubTextMuted
                    )
                } else {
                    uiState.terms.forEachIndexed { index, term ->
                        TermItem(term = term)
                        if (index < uiState.terms.size - 1) {
                            HorizontalDivider(
                                color = EduHubBorder.copy(alpha = 0.6f),
                                modifier = Modifier.padding(vertical = 10.dp)
                            )
                        }
                    }
                }
            }
        }

        Spacer(modifier = Modifier.height(16.dp))

        // 3. School Holidays Card
        Card(
            modifier = Modifier.fillMaxWidth(),
            colors = CardDefaults.cardColors(containerColor = EduHubBackground),
            border = BorderStroke(1.dp, EduHubBorder),
            shape = RoundedCornerShape(8.dp),
            elevation = CardDefaults.cardElevation(defaultElevation = 0.dp)
        ) {
            Column(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(20.dp)
            ) {
                Text(
                    text = "School Holidays",
                    fontSize = 18.sp,
                    fontWeight = FontWeight.Bold,
                    color = EduHubTextDark,
                    modifier = Modifier.padding(bottom = 14.dp)
                )

                if (uiState.holidays.isEmpty()) {
                    Text(
                        text = "No school holidays listed.",
                        fontSize = 13.sp,
                        color = EduHubTextMuted
                    )
                } else {
                    uiState.holidays.forEachIndexed { index, holiday ->
                        HolidayItem(holiday = holiday)
                        if (index < uiState.holidays.size - 1) {
                            HorizontalDivider(
                                color = EduHubBorder.copy(alpha = 0.6f),
                                modifier = Modifier.padding(vertical = 10.dp)
                            )
                        }
                    }
                }
            }
        }

        Spacer(modifier = Modifier.height(16.dp))

        // 4. School Events Card
        Card(
            modifier = Modifier.fillMaxWidth(),
            colors = CardDefaults.cardColors(containerColor = EduHubBackground),
            border = BorderStroke(1.dp, EduHubBorder),
            shape = RoundedCornerShape(8.dp),
            elevation = CardDefaults.cardElevation(defaultElevation = 0.dp)
        ) {
            Column(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(20.dp)
            ) {
                Text(
                    text = "School Events",
                    fontSize = 18.sp,
                    fontWeight = FontWeight.Bold,
                    color = EduHubTextDark,
                    modifier = Modifier.padding(bottom = 14.dp)
                )

                if (uiState.schoolEvents.isEmpty()) {
                    Text(
                        text = "No school events scheduled.",
                        fontSize = 13.sp,
                        color = EduHubTextMuted
                    )
                } else {
                    uiState.schoolEvents.forEachIndexed { index, event ->
                        EventItem(event = event)
                        if (index < uiState.schoolEvents.size - 1) {
                            HorizontalDivider(
                                color = EduHubBorder.copy(alpha = 0.6f),
                                modifier = Modifier.padding(vertical = 10.dp)
                            )
                        }
                    }
                }
            }
        }

        Spacer(modifier = Modifier.height(24.dp))
    }
}

@Composable
private fun TermItem(term: TermDto) {
    Row(
        modifier = Modifier.fillMaxWidth(),
        horizontalArrangement = Arrangement.SpaceBetween,
        verticalAlignment = Alignment.CenterVertically
    ) {
        Column(modifier = Modifier.weight(1f)) {
            Text(
                text = "${term.termName ?: "Term"} (${term.year ?: ""})",
                fontSize = 15.sp,
                fontWeight = FontWeight.Bold,
                color = EduHubTextDark
            )
            Spacer(modifier = Modifier.height(2.dp))
            Text(
                text = "${term.startDate ?: ""} to ${term.endDate ?: ""}",
                fontSize = 12.sp,
                color = EduHubTextMuted
            )
        }

        val today = SimpleDateFormat("yyyy-MM-dd", Locale.getDefault()).format(Date())
        val start = term.startDate ?: ""
        val end = term.endDate ?: ""

        val (badgeLabel, badgeBg, badgeTextColor) = when {
            start.isNotEmpty() && today < start -> Triple("Coming", Color(0xFFFEF7E0), Color(0xFFB06000))
            end.isNotEmpty() && today > end -> Triple("Past", Color(0xFFE8EAED), Color(0xFF5F6368))
            else -> Triple("Current", Color(0xFFE6F4EA), EduHubSuccess)
        }

        Box(
            modifier = Modifier
                .clip(RoundedCornerShape(12.dp))
                .background(badgeBg)
                .padding(horizontal = 10.dp, vertical = 4.dp)
        ) {
            Text(
                text = badgeLabel,
                fontSize = 11.sp,
                fontWeight = FontWeight.Bold,
                color = badgeTextColor
            )
        }
    }
}

@Composable
private fun HolidayItem(holiday: HolidayDto) {
    Column(modifier = Modifier.fillMaxWidth()) {
        Text(
            text = holiday.holidayName ?: "Holiday",
            fontSize = 14.sp,
            fontWeight = FontWeight.Bold,
            color = EduHubError
        )
        val desc = holiday.description
        if (!desc.isNullOrEmpty()) {
            Spacer(modifier = Modifier.height(2.dp))
            Text(
                text = desc,
                fontSize = 12.sp,
                color = EduHubTextMuted
            )
        }
        Spacer(modifier = Modifier.height(2.dp))
        Text(
            text = "${holiday.startDate ?: ""} - ${holiday.endDate ?: ""} • ${holiday.holidayType ?: "School Holiday"}",
            fontSize = 11.sp,
            color = EduHubTextMuted
        )
    }
}

@Composable
private fun EventItem(event: SchoolEventDto) {
    Column(modifier = Modifier.fillMaxWidth()) {
        Text(
            text = event.eventName ?: "Event",
            fontSize = 14.sp,
            fontWeight = FontWeight.Bold,
            color = EduHubOrange
        )
        val desc = event.description
        if (!desc.isNullOrEmpty()) {
            Spacer(modifier = Modifier.height(2.dp))
            Text(
                text = desc,
                fontSize = 12.sp,
                color = EduHubTextMuted
            )
        }
        Spacer(modifier = Modifier.height(2.dp))
        Text(
            text = "${event.eventDate ?: ""} ${event.eventTime ?: ""} • ${event.eventType ?: "Event"}",
            fontSize = 11.sp,
            color = EduHubTextMuted
        )
    }
}
