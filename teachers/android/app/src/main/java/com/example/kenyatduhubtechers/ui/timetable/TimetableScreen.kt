package com.example.kenyatduhubtechers.ui.timetable

import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.ExperimentalLayoutApi
import androidx.compose.foundation.layout.FlowRow
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
import androidx.compose.material.icons.filled.CheckCircle
import androidx.compose.material.icons.filled.Coffee
import androidx.compose.material.icons.filled.Info
import androidx.compose.material.icons.filled.Schedule
import androidx.compose.material.icons.filled.Warning
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
import com.example.kenyatduhubtechers.data.model.SchoolBreakDto
import com.example.kenyatduhubtechers.data.model.TimetableAssignmentDto
import com.example.kenyatduhubtechers.ui.theme.EduHubBackground
import com.example.kenyatduhubtechers.ui.theme.EduHubBorder
import com.example.kenyatduhubtechers.ui.theme.EduHubError
import com.example.kenyatduhubtechers.ui.theme.EduHubOrange
import com.example.kenyatduhubtechers.ui.theme.EduHubSuccess
import com.example.kenyatduhubtechers.ui.theme.EduHubTextDark
import com.example.kenyatduhubtechers.ui.theme.EduHubTextMuted

@OptIn(ExperimentalLayoutApi::class)
@Composable
fun TimetableScreen(
    viewModel: TimetableViewModel,
    modifier: Modifier = Modifier
) {
    val uiState by viewModel.uiState.collectAsState()

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
            text = "My Timetable",
            fontSize = 22.sp,
            fontWeight = FontWeight.Bold,
            color = EduHubTextDark,
            modifier = Modifier.padding(bottom = 16.dp)
        )

        // 1. Calendar Status Card
        val status = uiState.calendarStatus
        val isHoliday = status?.isHoliday ?: false
        val schoolStatus = status?.schoolStatus ?: "in_session"

        val (statusBg, statusBorder, statusIcon, statusTitle, statusTitleColor, statusDetail) = when {
            isHoliday || schoolStatus == "holiday" -> {
                val holidayName = status?.currentHoliday?.holidayName ?: "Holiday"
                val start = status?.currentHoliday?.startDate ?: ""
                val end = status?.currentHoliday?.endDate ?: ""
                val dateText = if (start.isNotEmpty()) " ($start - $end)" else ""
                Tuple6(
                    Color(0xFFFCE8E6),
                    EduHubError,
                    Icons.Default.Warning,
                    "School is on Holiday",
                    EduHubError,
                    "$holidayName$dateText"
                )
            }
            schoolStatus == "break" -> {
                Tuple6(
                    Color(0xFFFEF7E0),
                    Color(0xFFF9AB00),
                    Icons.Default.Info,
                    "School is on Break",
                    Color(0xFFB06000),
                    "No active term is currently set."
                )
            }
            else -> {
                val termName = status?.currentTerm?.termName ?: "Active Term"
                val start = status?.currentTerm?.startDate ?: ""
                val end = status?.currentTerm?.endDate ?: ""
                val dateText = if (start.isNotEmpty()) " ($start - $end)" else ""
                Tuple6(
                    Color(0xFFE6F4EA),
                    EduHubSuccess,
                    Icons.Default.CheckCircle,
                    "School is In Session",
                    EduHubSuccess,
                    "$termName$dateText"
                )
            }
        }

        Card(
            modifier = Modifier.fillMaxWidth(),
            colors = CardDefaults.cardColors(containerColor = statusBg),
            border = BorderStroke(1.dp, statusBorder),
            shape = RoundedCornerShape(8.dp),
            elevation = CardDefaults.cardElevation(defaultElevation = 0.dp)
        ) {
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(16.dp),
                verticalAlignment = Alignment.CenterVertically
            ) {
                Icon(
                    imageVector = statusIcon,
                    contentDescription = null,
                    tint = statusTitleColor,
                    modifier = Modifier.size(24.dp)
                )
                Spacer(modifier = Modifier.width(12.dp))
                Column {
                    Text(
                        text = statusTitle,
                        fontSize = 15.sp,
                        fontWeight = FontWeight.Bold,
                        color = statusTitleColor
                    )
                    Spacer(modifier = Modifier.height(2.dp))
                    Text(
                        text = statusDetail,
                        fontSize = 13.sp,
                        color = EduHubTextMuted
                    )
                }
            }
        }

        Spacer(modifier = Modifier.height(16.dp))

        // 2. School-Wide Breaks Card (if present)
        if (uiState.schoolBreaks.isNotEmpty()) {
            Card(
                modifier = Modifier.fillMaxWidth(),
                colors = CardDefaults.cardColors(containerColor = Color(0xFFFFF3CD)),
                border = BorderStroke(1.dp, Color(0xFFFFC107)),
                shape = RoundedCornerShape(8.dp),
                elevation = CardDefaults.cardElevation(defaultElevation = 0.dp)
            ) {
                Column(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(16.dp)
                ) {
                    Row(verticalAlignment = Alignment.CenterVertically) {
                        Icon(
                            imageVector = Icons.Default.Coffee,
                            contentDescription = null,
                            tint = Color(0xFF856404),
                            modifier = Modifier.size(20.dp)
                        )
                        Spacer(modifier = Modifier.width(8.dp))
                        Text(
                            text = "School-Wide Breaks",
                            fontSize = 15.sp,
                            fontWeight = FontWeight.Bold,
                            color = Color(0xFF856404)
                        )
                    }

                    Spacer(modifier = Modifier.height(4.dp))
                    Text(
                        text = "These breaks apply to weekdays (Monday - Friday):",
                        fontSize = 12.sp,
                        color = Color(0xFF856404)
                    )

                    Spacer(modifier = Modifier.height(10.dp))

                    FlowRow(
                        horizontalArrangement = Arrangement.spacedBy(8.dp),
                        verticalArrangement = Arrangement.spacedBy(8.dp)
                    ) {
                        uiState.schoolBreaks.forEach { breakItem ->
                            Box(
                                modifier = Modifier
                                    .clip(RoundedCornerShape(12.dp))
                                    .background(Color(0xFFFFC107))
                                    .padding(horizontal = 10.dp, vertical = 6.dp)
                            ) {
                                Text(
                                    text = "${breakItem.breakName ?: "Break"}: ${breakItem.startTime ?: ""} - ${breakItem.endTime ?: ""}",
                                    fontSize = 12.sp,
                                    fontWeight = FontWeight.Medium,
                                    color = Color.Black
                                )
                            }
                        }
                    }
                }
            }

            Spacer(modifier = Modifier.height(16.dp))
        }

        // 3. Weekly Timetable Section
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
                    text = "Weekly Timetable",
                    fontSize = 18.sp,
                    fontWeight = FontWeight.Bold,
                    color = EduHubOrange,
                    modifier = Modifier.padding(bottom = 14.dp)
                )

                if (uiState.timetableAssignments.isEmpty()) {
                    Box(
                        modifier = Modifier
                            .fillMaxWidth()
                            .clip(RoundedCornerShape(6.dp))
                            .background(Color(0xFFE8F0FE))
                            .border(1.dp, Color(0xFFD2E3FC), RoundedCornerShape(6.dp))
                            .padding(14.dp)
                    ) {
                        Row(verticalAlignment = Alignment.CenterVertically) {
                            Icon(
                                imageVector = Icons.Default.Info,
                                contentDescription = null,
                                tint = Color(0xFF1967D2),
                                modifier = Modifier.size(20.dp)
                            )
                            Spacer(modifier = Modifier.width(10.dp))
                            Text(
                                text = "No timetable assignments found. Contact your administrator to get assigned.",
                                fontSize = 13.sp,
                                color = Color(0xFF1967D2)
                            )
                        }
                    }
                } else {
                    // Group assignments by day
                    val daysOrder = listOf("Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday")
                    val grouped = uiState.timetableAssignments.groupBy { it.dayOfWeek ?: "Other" }

                    daysOrder.filter { grouped.containsKey(it) }.forEach { day ->
                        Text(
                            text = day,
                            fontSize = 15.sp,
                            fontWeight = FontWeight.Bold,
                            color = EduHubOrange,
                            modifier = Modifier.padding(top = 10.dp, bottom = 8.dp)
                        )

                        grouped[day]?.forEachIndexed { index, assignment ->
                            TimetableCardItem(assignment = assignment)
                            if (index < (grouped[day]?.size ?: 0) - 1) {
                                Spacer(modifier = Modifier.height(8.dp))
                            }
                        }

                        Spacer(modifier = Modifier.height(12.dp))
                        HorizontalDivider(color = EduHubBorder.copy(alpha = 0.5f))
                    }
                }
            }
        }

        Spacer(modifier = Modifier.height(24.dp))
    }
}

@Composable
private fun TimetableCardItem(assignment: TimetableAssignmentDto) {
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
                .padding(12.dp)
        ) {
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Row(verticalAlignment = Alignment.CenterVertically) {
                    Icon(
                        imageVector = Icons.Default.Schedule,
                        contentDescription = null,
                        tint = EduHubOrange,
                        modifier = Modifier.size(16.dp)
                    )
                    Spacer(modifier = Modifier.width(6.dp))
                    Text(
                        text = "${assignment.startTime ?: ""} - ${assignment.endTime ?: ""}",
                        fontSize = 13.sp,
                        fontWeight = FontWeight.Bold,
                        color = EduHubTextDark
                    )
                }

                Box(
                    modifier = Modifier
                        .clip(RoundedCornerShape(10.dp))
                        .background(Color(0xFFE8F0FE))
                        .padding(horizontal = 8.dp, vertical = 2.dp)
                ) {
                    Text(
                        text = "${assignment.className ?: ""} - ${assignment.streamName ?: ""}",
                        fontSize = 11.sp,
                        fontWeight = FontWeight.Bold,
                        color = EduHubOrange
                    )
                }
            }

            Spacer(modifier = Modifier.height(6.dp))

            Text(
                text = assignment.subjectName ?: "Subject",
                fontSize = 15.sp,
                fontWeight = FontWeight.Bold,
                color = EduHubTextDark
            )

            Spacer(modifier = Modifier.height(2.dp))

            Text(
                text = "${assignment.timetableName ?: ""} • ${assignment.year ?: ""} (${assignment.term ?: ""})",
                fontSize = 12.sp,
                color = EduHubTextMuted
            )

            val notes = assignment.notes
            if (!notes.isNullOrBlank()) {
                Spacer(modifier = Modifier.height(4.dp))
                Text(
                    text = "Notes: $notes",
                    fontSize = 11.sp,
                    color = EduHubTextMuted
                )
            }
        }
    }
}

private data class Tuple6<A, B, C, D, E, F>(
    val first: A,
    val second: B,
    val third: C,
    val fourth: D,
    val fifth: E,
    val sixth: F
)
