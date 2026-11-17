package com.example.kenyatduhubtechers.ui.attendance

import android.content.ContentValues
import android.content.Context
import android.content.Intent
import android.os.Build
import android.os.Environment
import android.provider.MediaStore
import android.widget.Toast
import androidx.compose.animation.AnimatedVisibility
import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.background
import androidx.compose.foundation.horizontalScroll
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
import androidx.compose.material.icons.filled.BeachAccess
import androidx.compose.material.icons.filled.CalendarMonth
import androidx.compose.material.icons.filled.Cancel
import androidx.compose.material.icons.filled.CheckCircle
import androidx.compose.material.icons.filled.Close
import androidx.compose.material.icons.filled.Download
import androidx.compose.material.icons.filled.Event
import androidx.compose.material.icons.filled.EventBusy
import androidx.compose.material.icons.filled.Info
import androidx.compose.material.icons.filled.Refresh
import androidx.compose.material.icons.filled.Save
import androidx.compose.material.icons.filled.Schedule
import androidx.compose.material.icons.filled.School
import androidx.compose.material.icons.filled.Search
import androidx.compose.material.icons.filled.Undo
import androidx.compose.material.icons.filled.Warning
import androidx.compose.material3.Button
import androidx.compose.material3.ButtonDefaults
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.DropdownMenuItem
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.ExposedDropdownMenuBox
import androidx.compose.material3.ExposedDropdownMenuDefaults
import androidx.compose.material3.HorizontalDivider
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.OutlinedTextFieldDefaults
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.material3.pulltorefresh.PullToRefreshBox
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.core.content.FileProvider
import com.example.kenyatduhubtechers.ui.theme.EduHubBackground
import com.example.kenyatduhubtechers.ui.theme.EduHubBorder
import com.example.kenyatduhubtechers.ui.theme.EduHubCardSurface
import com.example.kenyatduhubtechers.ui.theme.EduHubError
import com.example.kenyatduhubtechers.ui.theme.EduHubErrorContainer
import com.example.kenyatduhubtechers.ui.theme.EduHubOrange
import com.example.kenyatduhubtechers.ui.theme.EduHubRowEvenBg
import com.example.kenyatduhubtechers.ui.theme.EduHubRowOddBg
import com.example.kenyatduhubtechers.ui.theme.EduHubSuccess
import com.example.kenyatduhubtechers.ui.theme.EduHubSuccessContainer
import com.example.kenyatduhubtechers.ui.theme.EduHubTableHeaderBg
import com.example.kenyatduhubtechers.ui.theme.EduHubTextDark
import com.example.kenyatduhubtechers.ui.theme.EduHubTextMuted
import java.io.File
import java.text.SimpleDateFormat
import java.util.Date
import java.util.Locale

@OptIn(ExperimentalLayoutApi::class, ExperimentalMaterial3Api::class)
@Composable
fun AttendanceScreen(
    viewModel: AttendanceViewModel,
    modifier: Modifier = Modifier
) {
    val uiState by viewModel.uiState.collectAsState()
    val context = LocalContext.current

    PullToRefreshBox(
        isRefreshing = uiState.isLoading,
        onRefresh = { viewModel.loadAttendanceMainData() },
        modifier = modifier.fillMaxSize()
    ) {
        if (uiState.isLoading && uiState.teacher == null && uiState.classes.isEmpty()) {
            Box(
                modifier = Modifier.fillMaxSize(),
                contentAlignment = Alignment.Center
            ) {
                CircularProgressIndicator(color = EduHubOrange)
            }
        } else {
            val classOptions = remember(uiState.classes) {
                listOf("Select Class") + uiState.classes.mapNotNull { it.className }
            }
            val classMap = remember(uiState.classes) {
                uiState.classes.associateBy { it.className }
            }
            val selectedClassName = uiState.classes.find { it.id == uiState.selectedClassId }?.className ?: "Select Class"

            val filteredStudents = remember(uiState.students, uiState.searchQuery) {
                uiState.students.filter { student ->
                    val query = uiState.searchQuery.lowercase().trim()
                    val fullName = "${student.firstName} ${student.lastName}".lowercase()
                    val adm = student.admissionNumber.lowercase()
                    query.isEmpty() || fullName.contains(query) || adm.contains(query)
                }
            }

            // Attendance Counts
            val presentCount = uiState.students.count { it.status == "present" }
            val absentCount = uiState.students.count { it.status == "absent" }
            val lateCount = uiState.students.count { it.status == "late" }
            val excusedCount = uiState.students.count { it.status == "excused" }
            val markedTotal = presentCount + absentCount + lateCount + excusedCount
            val attendancePercentage = if (markedTotal > 0) (presentCount * 100 / markedTotal) else 0

            Column(
                modifier = Modifier
                    .fillMaxSize()
                    .verticalScroll(rememberScrollState())
                    .padding(10.dp)
            ) {
                Text(
                    text = "Attendance Management",
                    fontSize = 22.sp,
                    fontWeight = FontWeight.Bold,
                    color = EduHubTextDark
                )
                Text(
                    text = "Take daily class attendance and view attendance history.",
                    fontSize = 13.sp,
                    color = EduHubTextMuted,
                    modifier = Modifier.padding(bottom = 16.dp)
                )

                // Banners
                AnimatedVisibility(visible = uiState.errorMessage != null) {
                    uiState.errorMessage?.let { msg ->
                        Card(
                            modifier = Modifier
                                .fillMaxWidth()
                                .padding(bottom = 12.dp),
                            colors = CardDefaults.cardColors(containerColor = EduHubErrorContainer),
                            border = BorderStroke(1.dp, EduHubError)
                        ) {
                            Row(
                                modifier = Modifier
                                    .fillMaxWidth()
                                    .padding(12.dp),
                                horizontalArrangement = Arrangement.SpaceBetween,
                                verticalAlignment = Alignment.CenterVertically
                            ) {
                                Text(msg, color = EduHubError, fontSize = 13.sp, modifier = Modifier.weight(1f))
                                IconButton(onClick = { viewModel.clearMessages() }, modifier = Modifier.size(24.dp)) {
                                    Icon(Icons.Default.Close, contentDescription = null, tint = EduHubError)
                                }
                            }
                        }
                    }
                }

                AnimatedVisibility(visible = uiState.successMessage != null) {
                    uiState.successMessage?.let { msg ->
                        Card(
                            modifier = Modifier
                                .fillMaxWidth()
                                .padding(bottom = 12.dp),
                            colors = CardDefaults.cardColors(containerColor = EduHubSuccessContainer),
                            border = BorderStroke(1.dp, EduHubSuccess)
                        ) {
                            Row(
                                modifier = Modifier
                                    .fillMaxWidth()
                                    .padding(12.dp),
                                horizontalArrangement = Arrangement.SpaceBetween,
                                verticalAlignment = Alignment.CenterVertically
                            ) {
                                Text(msg, color = EduHubSuccess, fontSize = 13.sp, modifier = Modifier.weight(1f))
                                IconButton(onClick = { viewModel.clearMessages() }, modifier = Modifier.size(24.dp)) {
                                    Icon(Icons.Default.Close, contentDescription = null, tint = EduHubSuccess)
                                }
                            }
                        }
                    }
                }

                // Sleek Orange Session Status Banner
                val status = uiState.calendarStatus
                val schoolStatus = status?.schoolStatus ?: "in_session"
                val isHoliday = status?.isHoliday ?: false

                Card(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(bottom = 16.dp),
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
                                Text("Today: ${SimpleDateFormat("MMMM d, yyyy", Locale.getDefault()).format(Date())}", fontSize = 12.sp, color = Color.White)
                            }
                        }
                    }
                }

                // Take Attendance Card
                Card(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(bottom = 20.dp),
                    shape = RoundedCornerShape(12.dp),
                    colors = CardDefaults.cardColors(containerColor = EduHubBackground),
                    border = BorderStroke(1.dp, EduHubBorder)
                ) {
                    Column(modifier = Modifier.padding(20.dp)) {
                        Text("Take Attendance", fontSize = 18.sp, fontWeight = FontWeight.Bold, color = EduHubTextDark)
                        Spacer(modifier = Modifier.height(14.dp))

                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            horizontalArrangement = Arrangement.spacedBy(8.dp)
                        ) {
                            OutlinedTextField(
                                value = uiState.attendanceDate,
                                onValueChange = { viewModel.onAttendanceDateChanged(it) },
                                label = { Text("Date", fontSize = 12.sp) },
                                placeholder = { Text("YYYY-MM-DD", fontSize = 13.sp) },
                                modifier = Modifier.weight(1f),
                                shape = RoundedCornerShape(8.dp),
                                colors = OutlinedTextFieldDefaults.colors(focusedBorderColor = EduHubOrange)
                            )

                            FilterDropdown(
                                modifier = Modifier.weight(1f),
                                label = "Class",
                                selectedOption = selectedClassName,
                                options = classOptions,
                                onOptionSelected = {
                                    val cls = classMap[it]
                                    viewModel.onClassSelected(cls?.id)
                                }
                            )
                        }

                        Spacer(modifier = Modifier.height(12.dp))

                        Button(
                            onClick = { viewModel.loadStudentsForAttendance() },
                            modifier = Modifier.fillMaxWidth(),
                            colors = ButtonDefaults.buttonColors(containerColor = EduHubOrange),
                            shape = RoundedCornerShape(8.dp),
                            enabled = !uiState.isLoadingStudents
                        ) {
                            if (uiState.isLoadingStudents) {
                                CircularProgressIndicator(color = Color.White, modifier = Modifier.size(18.dp))
                            } else {
                                Icon(Icons.Default.Search, contentDescription = null, modifier = Modifier.size(18.dp))
                                Spacer(modifier = Modifier.width(6.dp))
                                Text("Load Students", fontSize = 14.sp, fontWeight = FontWeight.Bold)
                            }
                        }
                    }
                }

                // Attendance Section
                if (uiState.students.isNotEmpty()) {
                    Card(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(bottom = 20.dp),
                        shape = RoundedCornerShape(12.dp),
                        colors = CardDefaults.cardColors(containerColor = EduHubBackground),
                        border = BorderStroke(1.dp, EduHubBorder)
                    ) {
                        Column(modifier = Modifier.padding(16.dp)) {
                            // Statistics Cards
                            FlowRow(
                                modifier = Modifier
                                    .fillMaxWidth()
                                    .padding(bottom = 16.dp),
                                horizontalArrangement = Arrangement.spacedBy(8.dp),
                                verticalArrangement = Arrangement.spacedBy(8.dp)
                            ) {
                                StatBadge("Present", "$presentCount", Color(0xFFE6F4EA), EduHubSuccess)
                                StatBadge("Absent", "$absentCount", Color(0xFFFCE8E6), EduHubError)
                                StatBadge("Late", "$lateCount", Color(0xFFFEF7E0), Color(0xFFB06000))
                                StatBadge("Excused", "$excusedCount", Color(0xFFE8F0FE), Color(0xFF1967D2))
                                StatBadge("Rate", "$attendancePercentage%", Color(0xFFF1F3F4), EduHubTextDark)
                            }

                            // Bulk Actions
                            FlowRow(
                                modifier = Modifier
                                    .fillMaxWidth()
                                    .padding(bottom = 16.dp),
                                horizontalArrangement = Arrangement.spacedBy(6.dp),
                                verticalArrangement = Arrangement.spacedBy(6.dp)
                            ) {
                                Button(
                                    onClick = { viewModel.markAllAs("present") },
                                    colors = ButtonDefaults.buttonColors(containerColor = EduHubSuccess),
                                    shape = RoundedCornerShape(20.dp)
                                ) {
                                    Icon(Icons.Default.CheckCircle, contentDescription = null, modifier = Modifier.size(14.dp))
                                    Spacer(modifier = Modifier.width(4.dp))
                                    Text("All Present", fontSize = 11.sp)
                                }

                                Button(
                                    onClick = { viewModel.markAllAs("absent") },
                                    colors = ButtonDefaults.buttonColors(containerColor = EduHubError),
                                    shape = RoundedCornerShape(20.dp)
                                ) {
                                    Icon(Icons.Default.Cancel, contentDescription = null, modifier = Modifier.size(14.dp))
                                    Spacer(modifier = Modifier.width(4.dp))
                                    Text("All Absent", fontSize = 11.sp)
                                }

                                Button(
                                    onClick = { viewModel.markAllAs("late") },
                                    colors = ButtonDefaults.buttonColors(containerColor = Color(0xFFB06000)),
                                    shape = RoundedCornerShape(20.dp)
                                ) {
                                    Icon(Icons.Default.Schedule, contentDescription = null, modifier = Modifier.size(14.dp))
                                    Spacer(modifier = Modifier.width(4.dp))
                                    Text("All Late", fontSize = 11.sp)
                                }

                                Button(
                                    onClick = { viewModel.markAllAs("excused") },
                                    colors = ButtonDefaults.buttonColors(containerColor = Color(0xFF1967D2)),
                                    shape = RoundedCornerShape(20.dp)
                                ) {
                                    Icon(Icons.Default.Info, contentDescription = null, modifier = Modifier.size(14.dp))
                                    Spacer(modifier = Modifier.width(4.dp))
                                    Text("All Excused", fontSize = 11.sp)
                                }

                                OutlinedButton(
                                    onClick = { viewModel.clearAllStatus() },
                                    shape = RoundedCornerShape(20.dp)
                                ) {
                                    Icon(Icons.Default.Undo, contentDescription = null, modifier = Modifier.size(14.dp))
                                    Spacer(modifier = Modifier.width(4.dp))
                                    Text("Clear", fontSize = 11.sp)
                                }
                            }

                            // Student Filter
                            OutlinedTextField(
                                value = uiState.searchQuery,
                                onValueChange = { viewModel.onSearchQueryChanged(it) },
                                placeholder = { Text("Search student by admission number or name...", fontSize = 13.sp) },
                                leadingIcon = { Icon(Icons.Default.Search, contentDescription = null, tint = EduHubTextMuted) },
                                modifier = Modifier
                                    .fillMaxWidth()
                                    .padding(bottom = 12.dp),
                                shape = RoundedCornerShape(8.dp),
                                colors = OutlinedTextFieldDefaults.colors(focusedBorderColor = EduHubOrange)
                            )

                            // Student Table
                            Row(
                                modifier = Modifier
                                    .fillMaxWidth()
                                    .horizontalScroll(rememberScrollState())
                            ) {
                                Column {
                                    Row(
                                        modifier = Modifier
                                            .background(EduHubTableHeaderBg)
                                            .padding(vertical = 10.dp, horizontal = 12.dp),
                                        verticalAlignment = Alignment.CenterVertically
                                    ) {
                                        HeaderCell("ADM NO", width = 85.dp)
                                        HeaderCell("STUDENT NAME", width = 140.dp, align = TextAlign.Start)
                                        HeaderCell("STATUS", width = 220.dp)
                                        HeaderCell("REMARKS", width = 140.dp, align = TextAlign.Start)
                                    }
                                    HorizontalDivider(color = EduHubBorder)

                                    filteredStudents.forEachIndexed { index, student ->
                                        val bg = if (index % 2 == 0) EduHubRowEvenBg else EduHubRowOddBg
                                        Row(
                                            modifier = Modifier
                                                .background(bg)
                                                .padding(vertical = 8.dp, horizontal = 12.dp),
                                            verticalAlignment = Alignment.CenterVertically
                                        ) {
                                            DataCell(text = student.admissionNumber, width = 85.dp, fontWeight = FontWeight.Bold)
                                            DataCell(text = "${student.firstName} ${student.lastName}", width = 140.dp, align = TextAlign.Start)

                                            // Status Buttons Group
                                            Row(
                                                modifier = Modifier.width(220.dp),
                                                horizontalArrangement = Arrangement.spacedBy(4.dp),
                                                verticalAlignment = Alignment.CenterVertically
                                            ) {
                                                StatusChip("Present", student.status == "present", EduHubSuccess) { viewModel.setStudentStatus(student.id, "present") }
                                                StatusChip("Absent", student.status == "absent", EduHubError) { viewModel.setStudentStatus(student.id, "absent") }
                                                StatusChip("Late", student.status == "late", Color(0xFFB06000)) { viewModel.setStudentStatus(student.id, "late") }
                                                StatusChip("Excused", student.status == "excused", Color(0xFF1967D2)) { viewModel.setStudentStatus(student.id, "excused") }
                                            }

                                            OutlinedTextField(
                                                value = student.remarks ?: "",
                                                onValueChange = { viewModel.setStudentRemarks(student.id, it) },
                                                placeholder = { Text("Remarks", fontSize = 11.sp) },
                                                modifier = Modifier
                                                    .width(140.dp)
                                                    .height(42.dp),
                                                singleLine = true,
                                                shape = RoundedCornerShape(6.dp)
                                            )
                                        }
                                        HorizontalDivider(color = EduHubBorder.copy(alpha = 0.5f))
                                    }
                                }
                            }

                            Spacer(modifier = Modifier.height(16.dp))

                            Button(
                                onClick = { viewModel.saveAttendance() },
                                colors = ButtonDefaults.buttonColors(containerColor = EduHubSuccess),
                                shape = RoundedCornerShape(8.dp),
                                modifier = Modifier.fillMaxWidth(),
                                enabled = !uiState.isSubmitting
                            ) {
                                if (uiState.isSubmitting) {
                                    CircularProgressIndicator(color = Color.White, modifier = Modifier.size(18.dp))
                                } else {
                                    Icon(Icons.Default.Save, contentDescription = null, modifier = Modifier.size(18.dp))
                                    Spacer(modifier = Modifier.width(6.dp))
                                    Text("Save Attendance", fontSize = 14.sp, fontWeight = FontWeight.Bold)
                                }
                            }
                        }
                    }
                }

                // Attendance History Card
                Card(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(bottom = 24.dp),
                    shape = RoundedCornerShape(12.dp),
                    colors = CardDefaults.cardColors(containerColor = EduHubBackground),
                    border = BorderStroke(1.dp, EduHubBorder)
                ) {
                    Column(modifier = Modifier.padding(16.dp)) {
                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            horizontalArrangement = Arrangement.SpaceBetween,
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            Text("Attendance History", fontSize = 18.sp, fontWeight = FontWeight.Bold, color = EduHubTextDark)
                            OutlinedButton(
                                onClick = { downloadAndShareCsv(context, viewModel.generateCsvExport()) },
                                shape = RoundedCornerShape(20.dp)
                            ) {
                                Icon(Icons.Default.Download, contentDescription = null, modifier = Modifier.size(16.dp))
                                Spacer(modifier = Modifier.width(4.dp))
                                Text("Export CSV", fontSize = 12.sp)
                            }
                        }
                        Spacer(modifier = Modifier.height(12.dp))

                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            horizontalArrangement = Arrangement.spacedBy(8.dp),
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            FilterDropdown(
                                modifier = Modifier.weight(1f),
                                label = "Range",
                                selectedOption = when (uiState.dateRangeFilter) {
                                    "week" -> "This Week"
                                    "month" -> "This Month"
                                    "custom" -> "Custom Range"
                                    else -> "Today"
                                },
                                options = listOf("Today", "This Week", "This Month", "Custom Range"),
                                onOptionSelected = {
                                    val key = when (it) {
                                        "This Week" -> "week"
                                        "This Month" -> "month"
                                        "Custom Range" -> "custom"
                                        else -> "today"
                                    }
                                    viewModel.onDateRangeFilterChanged(key)
                                }
                            )
                        }

                        if (uiState.dateRangeFilter == "custom") {
                            Spacer(modifier = Modifier.height(8.dp))
                            Row(
                                modifier = Modifier.fillMaxWidth(),
                                horizontalArrangement = Arrangement.spacedBy(8.dp),
                                verticalAlignment = Alignment.CenterVertically
                            ) {
                                OutlinedTextField(
                                    value = uiState.startDateFilter,
                                    onValueChange = { viewModel.onCustomStartDateChanged(it) },
                                    label = { Text("Start Date", fontSize = 11.sp) },
                                    modifier = Modifier.weight(1f),
                                    shape = RoundedCornerShape(8.dp)
                                )
                                OutlinedTextField(
                                    value = uiState.endDateFilter,
                                    onValueChange = { viewModel.onCustomEndDateChanged(it) },
                                    label = { Text("End Date", fontSize = 11.sp) },
                                    modifier = Modifier.weight(1f),
                                    shape = RoundedCornerShape(8.dp)
                                )
                                Button(
                                    onClick = { viewModel.applyCustomDateRange() },
                                    shape = RoundedCornerShape(8.dp),
                                    colors = ButtonDefaults.buttonColors(containerColor = EduHubOrange)
                                ) {
                                    Text("Apply", fontSize = 12.sp)
                                }
                            }
                        }

                        Spacer(modifier = Modifier.height(16.dp))

                        if (uiState.isLoadingHistory) {
                            Box(
                                modifier = Modifier
                                    .fillMaxWidth()
                                    .height(120.dp),
                                contentAlignment = Alignment.Center
                            ) {
                                CircularProgressIndicator(color = EduHubOrange)
                            }
                        } else if (uiState.historyList.isEmpty()) {
                            Text("No attendance history records found.", fontSize = 13.sp, color = EduHubTextMuted)
                        } else {
                            Row(
                                modifier = Modifier
                                    .fillMaxWidth()
                                    .horizontalScroll(rememberScrollState())
                            ) {
                                Column {
                                    Row(
                                        modifier = Modifier
                                            .background(Color(0xFFF8F9FA))
                                            .padding(vertical = 10.dp, horizontal = 12.dp),
                                        verticalAlignment = Alignment.CenterVertically
                                    ) {
                                        HeaderCell("DATE", width = 110.dp)
                                        HeaderCell("STATUS", width = 110.dp)
                                        HeaderCell("RECORD COUNT", width = 110.dp)
                                    }
                                    HorizontalDivider(color = EduHubBorder)

                                    uiState.historyList.forEachIndexed { index, hist ->
                                        val bg = if (index % 2 == 0) Color.White else Color(0xFFFAFAFA)
                                        Row(
                                            modifier = Modifier
                                                .background(bg)
                                                .padding(vertical = 10.dp, horizontal = 12.dp),
                                            verticalAlignment = Alignment.CenterVertically
                                        ) {
                                            DataCell(text = hist.attendanceDate ?: "-", width = 110.dp, fontWeight = FontWeight.Bold)

                                            val badgeColor = when (hist.status?.lowercase()) {
                                                "present" -> EduHubSuccess
                                                "absent" -> EduHubError
                                                "late" -> Color(0xFFB06000)
                                                else -> Color(0xFF1967D2)
                                            }
                                            Surface(
                                                color = badgeColor.copy(alpha = 0.15f),
                                                shape = RoundedCornerShape(12.dp),
                                                modifier = Modifier.width(110.dp)
                                            ) {
                                                Text(
                                                    hist.status?.uppercase() ?: "-",
                                                    fontSize = 11.sp,
                                                    fontWeight = FontWeight.Bold,
                                                    color = badgeColor,
                                                    textAlign = TextAlign.Center,
                                                    modifier = Modifier.padding(horizontal = 8.dp, vertical = 2.dp)
                                                )
                                            }

                                            DataCell(text = "${hist.count}", width = 110.dp)
                                        }
                                        HorizontalDivider(color = EduHubBorder.copy(alpha = 0.5f))
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}

@Composable
private fun StatBadge(label: String, value: String, bgColor: Color, textColor: Color) {
    Surface(
        color = bgColor,
        shape = RoundedCornerShape(8.dp),
        border = BorderStroke(1.dp, textColor.copy(alpha = 0.3f))
    ) {
        Column(
            modifier = Modifier.padding(horizontal = 12.dp, vertical = 8.dp),
            horizontalAlignment = Alignment.CenterHorizontally
        ) {
            Text(value, fontSize = 16.sp, fontWeight = FontWeight.Bold, color = textColor)
            Text(label, fontSize = 10.sp, color = EduHubTextMuted)
        }
    }
}

@Composable
private fun StatusChip(
    text: String,
    isSelected: Boolean,
    activeColor: Color,
    onClick: () -> Unit
) {
    Surface(
        onClick = onClick,
        color = if (isSelected) activeColor else Color.White,
        shape = RoundedCornerShape(4.dp),
        border = BorderStroke(1.dp, if (isSelected) activeColor else EduHubBorder)
    ) {
        Text(
            text = text,
            fontSize = 10.sp,
            fontWeight = FontWeight.Bold,
            color = if (isSelected) Color.White else EduHubTextMuted,
            modifier = Modifier.padding(horizontal = 6.dp, vertical = 4.dp)
        )
    }
}

private fun downloadAndShareCsv(context: Context, csvContent: String) {
    try {
        val fileName = "attendance_history_${System.currentTimeMillis()}.csv"

        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.Q) {
            val contentValues = ContentValues().apply {
                put(MediaStore.MediaColumns.DISPLAY_NAME, fileName)
                put(MediaStore.MediaColumns.MIME_TYPE, "text/csv")
                put(MediaStore.MediaColumns.RELATIVE_PATH, Environment.DIRECTORY_DOWNLOADS)
            }
            val uri = context.contentResolver.insert(MediaStore.Downloads.EXTERNAL_CONTENT_URI, contentValues)
            uri?.let {
                context.contentResolver.openOutputStream(it)?.use { os ->
                    os.write(csvContent.toByteArray())
                }
            }
        } else {
            @Suppress("DEPRECATION")
            val downloadsDir = Environment.getExternalStoragePublicDirectory(Environment.DIRECTORY_DOWNLOADS)
            val file = File(downloadsDir, fileName)
            file.writeText(csvContent)
        }

        val cacheFile = File(context.cacheDir, "attendance_export.csv")
        cacheFile.writeText(csvContent)
        val shareUri = FileProvider.getUriForFile(
            context,
            "${context.packageName}.fileprovider",
            cacheFile
        )

        val shareIntent = Intent(Intent.ACTION_SEND).apply {
            type = "text/csv"
            putExtra(Intent.EXTRA_STREAM, shareUri)
            addFlags(Intent.FLAG_GRANT_READ_URI_PERMISSION)
        }

        context.startActivity(Intent.createChooser(shareIntent, "Export Attendance History"))
        Toast.makeText(context, "Attendance history saved to Downloads folder!", Toast.LENGTH_LONG).show()
    } catch (e: Exception) {
        Toast.makeText(context, "Error exporting CSV: ${e.localizedMessage}", Toast.LENGTH_SHORT).show()
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
private fun FilterDropdown(
    modifier: Modifier = Modifier,
    label: String,
    selectedOption: String,
    options: List<String>,
    onOptionSelected: (String) -> Unit
) {
    var expanded by remember { mutableStateOf(false) }

    ExposedDropdownMenuBox(
        expanded = expanded,
        onExpandedChange = { expanded = !expanded },
        modifier = modifier
    ) {
        OutlinedTextField(
            value = selectedOption,
            onValueChange = {},
            readOnly = true,
            label = { Text(label, fontSize = 12.sp) },
            trailingIcon = { ExposedDropdownMenuDefaults.TrailingIcon(expanded = expanded) },
            modifier = Modifier
                .fillMaxWidth()
                .menuAnchor(),
            shape = RoundedCornerShape(8.dp),
            colors = OutlinedTextFieldDefaults.colors(focusedBorderColor = EduHubOrange)
        )

        ExposedDropdownMenu(
            expanded = expanded,
            onDismissRequest = { expanded = false }
        ) {
            options.forEach { option ->
                DropdownMenuItem(
                    text = { Text(option, fontSize = 13.sp) },
                    onClick = {
                        onOptionSelected(option)
                        expanded = false
                    }
                )
            }
        }
    }
}

@Composable
private fun HeaderCell(
    text: String,
    width: androidx.compose.ui.unit.Dp,
    align: TextAlign = TextAlign.Center
) {
    Text(
        text = text,
        fontSize = 11.sp,
        fontWeight = FontWeight.Bold,
        color = EduHubTextMuted,
        textAlign = align,
        modifier = Modifier.width(width)
    )
}

@Composable
private fun DataCell(
    text: String,
    width: androidx.compose.ui.unit.Dp,
    align: TextAlign = TextAlign.Center,
    fontWeight: FontWeight = FontWeight.Normal,
    textColor: Color = EduHubTextDark
) {
    Text(
        text = text,
        fontSize = 12.sp,
        fontWeight = fontWeight,
        color = textColor,
        textAlign = align,
        modifier = Modifier.width(width)
    )
}
