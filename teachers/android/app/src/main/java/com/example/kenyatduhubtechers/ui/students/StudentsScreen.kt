package com.example.kenyatduhubtechers.ui.students

import android.content.ContentValues
import android.content.Context
import android.content.Intent
import android.net.Uri
import android.os.Build
import android.os.Environment
import android.provider.MediaStore
import android.widget.Toast
import androidx.compose.animation.AnimatedVisibility
import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.background
import androidx.compose.foundation.border
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
import androidx.compose.material.icons.filled.AccountBalanceWallet
import androidx.compose.material.icons.filled.CalendarMonth
import androidx.compose.material.icons.filled.Cancel
import androidx.compose.material.icons.filled.CheckCircle
import androidx.compose.material.icons.filled.Close
import androidx.compose.material.icons.filled.Download
import androidx.compose.material.icons.filled.Event
import androidx.compose.material.icons.filled.Man
import androidx.compose.material.icons.filled.Refresh
import androidx.compose.material.icons.filled.School
import androidx.compose.material.icons.filled.Search
import androidx.compose.material.icons.filled.Visibility
import androidx.compose.material.icons.filled.Woman
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
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontFamily
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.compose.ui.window.Dialog
import androidx.core.content.FileProvider
import androidx.compose.material3.pulltorefresh.PullToRefreshBox
import androidx.compose.ui.graphics.vector.ImageVector
import com.example.kenyatduhubtechers.data.model.StudentAffairsItemDto
import com.example.kenyatduhubtechers.ui.theme.EduHubBackground
import com.example.kenyatduhubtechers.ui.theme.EduHubBorder
import com.example.kenyatduhubtechers.ui.theme.EduHubError
import com.example.kenyatduhubtechers.ui.theme.EduHubErrorContainer
import com.example.kenyatduhubtechers.ui.theme.EduHubOrange
import com.example.kenyatduhubtechers.ui.theme.EduHubSuccess
import com.example.kenyatduhubtechers.ui.theme.EduHubTextDark
import com.example.kenyatduhubtechers.ui.theme.EduHubTextMuted
import java.io.File
import java.text.SimpleDateFormat
import java.util.Date
import java.util.Locale

@OptIn(ExperimentalLayoutApi::class, ExperimentalMaterial3Api::class)
@Composable
fun StudentsScreen(
    viewModel: StudentsViewModel,
    modifier: Modifier = Modifier
) {
    val uiState by viewModel.uiState.collectAsState()
    val context = LocalContext.current

    PullToRefreshBox(
        isRefreshing = uiState.isLoading,
        onRefresh = { viewModel.loadStudentsData() },
        modifier = modifier.fillMaxSize()
    ) {
        if (uiState.isLoading && uiState.students.isEmpty()) {
            Box(
                modifier = Modifier.fillMaxSize(),
                contentAlignment = Alignment.Center
            ) {
                CircularProgressIndicator(color = EduHubOrange)
            }
        } else {

    // Filter students based on state
    val filteredStudents = remember(
        uiState.students,
        uiState.searchQuery,
        uiState.classFilter,
        uiState.streamFilter,
        uiState.genderFilter,
        uiState.statusFilter
    ) {
        uiState.students.filter { student ->
            val query = uiState.searchQuery.lowercase().trim()
            val matchesSearch = query.isEmpty() ||
                    student.admissionNumber.lowercase().contains(query) ||
                    student.fullName.lowercase().contains(query)

            val matchesClass = uiState.classFilter.isEmpty() || student.className == uiState.classFilter
            val matchesStream = uiState.streamFilter.isEmpty() || student.streamName == uiState.streamFilter
            val matchesGender = uiState.genderFilter.isEmpty() || student.gender?.equals(uiState.genderFilter, ignoreCase = true) == true
            val matchesStatus = uiState.statusFilter.isEmpty() || student.status?.equals(uiState.statusFilter, ignoreCase = true) == true

            matchesSearch && matchesClass && matchesStream && matchesGender && matchesStatus
        }
    }

    Column(
        modifier = modifier
            .fillMaxSize()
            .verticalScroll(rememberScrollState())
            .padding(12.dp)
    ) {
        Text(
            text = "Student Affairs",
            fontSize = 22.sp,
            fontWeight = FontWeight.Bold,
            color = EduHubTextDark,
            modifier = Modifier.padding(bottom = 16.dp)
        )

        // Error Banner
        AnimatedVisibility(visible = uiState.errorMessage != null) {
            uiState.errorMessage?.let { error ->
                Card(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(bottom = 12.dp),
                    colors = CardDefaults.cardColors(containerColor = EduHubErrorContainer),
                    border = BorderStroke(1.dp, EduHubError),
                    shape = RoundedCornerShape(8.dp)
                ) {
                    Row(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(12.dp),
                        verticalAlignment = Alignment.CenterVertically,
                        horizontalArrangement = Arrangement.SpaceBetween
                    ) {
                        Text(text = error, color = EduHubError, fontSize = 13.sp, modifier = Modifier.weight(1f))
                        IconButton(onClick = { viewModel.clearMessages() }, modifier = Modifier.size(24.dp)) {
                            Icon(Icons.Default.Close, contentDescription = null, tint = EduHubError)
                        }
                    }
                }
            }
        }

        // Sleek Orange School Session Status Card (Matching Dashboard Status Card Style)
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
                    text = "Student Affairs & Status",
                    fontSize = 18.sp,
                    fontWeight = FontWeight.Bold,
                    color = Color.White
                )

                Spacer(modifier = Modifier.height(12.dp))

                Row(verticalAlignment = Alignment.CenterVertically) {
                    Icon(
                        imageVector = Icons.Default.School,
                        contentDescription = null,
                        tint = Color.White,
                        modifier = Modifier.size(28.dp)
                    )
                    Spacer(modifier = Modifier.width(12.dp))
                    Column {
                        Text(
                            text = "Student Roster & Fee Status",
                            fontSize = 15.sp,
                            fontWeight = FontWeight.Bold,
                            color = Color.White
                        )
                        Text(
                            text = "Total Registered: ${filteredStudents.size} Students",
                            fontSize = 13.sp,
                            color = Color.White.copy(alpha = 0.9f)
                        )
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
                        Icon(
                            imageVector = Icons.Default.CalendarMonth,
                            contentDescription = null,
                            tint = Color.White,
                            modifier = Modifier.size(16.dp)
                        )
                        Spacer(modifier = Modifier.width(6.dp))
                        Text(
                            text = "Academic Year 2026",
                            fontSize = 12.sp,
                            color = Color.White
                        )
                    }

                    Row(verticalAlignment = Alignment.CenterVertically) {
                        Icon(
                            imageVector = Icons.Default.Event,
                            contentDescription = null,
                            tint = Color.White,
                            modifier = Modifier.size(16.dp)
                        )
                        Spacer(modifier = Modifier.width(6.dp))
                        Text(
                            text = "Today: ${SimpleDateFormat("MMMM d, yyyy", Locale.getDefault()).format(Date())}",
                            fontSize = 12.sp,
                            color = Color.White
                        )
                    }
                }
            }
        }

        // 1. Filter Students Card
        Card(
            modifier = Modifier
                .fillMaxWidth()
                .padding(bottom = 16.dp),
            colors = CardDefaults.cardColors(containerColor = EduHubBackground),
            border = BorderStroke(1.dp, EduHubBorder),
            shape = RoundedCornerShape(8.dp)
        ) {
            Column(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(16.dp)
            ) {
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.SpaceBetween,
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    Text("Filter Students", fontSize = 16.sp, fontWeight = FontWeight.Bold, color = EduHubTextDark)
                    OutlinedButton(
                        onClick = {
                            val csvStr = viewModel.generateCsvExport(filteredStudents)
                            downloadAndShareCsv(context, csvStr)
                        },
                        shape = RoundedCornerShape(25.dp)
                    ) {
                        Icon(Icons.Default.Download, contentDescription = null, modifier = Modifier.size(16.dp))
                        Spacer(modifier = Modifier.width(4.dp))
                        Text("Export CSV", fontSize = 12.sp)
                    }
                }

                Spacer(modifier = Modifier.height(12.dp))

                // Search Input
                OutlinedTextField(
                    value = uiState.searchQuery,
                    onValueChange = { viewModel.onSearchQueryChanged(it) },
                    modifier = Modifier.fillMaxWidth(),
                    placeholder = { Text("Search by name or admission no", color = EduHubTextMuted, fontSize = 13.sp) },
                    leadingIcon = { Icon(Icons.Default.Search, contentDescription = null, tint = EduHubTextMuted) },
                    singleLine = true,
                    shape = RoundedCornerShape(25.dp),
                    colors = OutlinedTextFieldDefaults.colors(
                        focusedBorderColor = EduHubOrange,
                        unfocusedBorderColor = EduHubBorder,
                        focusedContainerColor = EduHubBackground,
                        unfocusedContainerColor = EduHubBackground
                    )
                )

                Spacer(modifier = Modifier.height(8.dp))

                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.spacedBy(8.dp)
                ) {
                    FilterDropdown(
                        modifier = Modifier.weight(1f),
                        label = "Class",
                        selectedOption = if (uiState.classFilter.isEmpty()) "All Classes" else uiState.classFilter,
                        options = listOf("All Classes") + uiState.classes.mapNotNull { it.className },
                        onOptionSelected = {
                            viewModel.onClassFilterChanged(if (it == "All Classes") "" else it)
                        }
                    )

                    FilterDropdown(
                        modifier = Modifier.weight(1f),
                        label = "Stream",
                        selectedOption = if (uiState.streamFilter.isEmpty()) "All Streams" else uiState.streamFilter,
                        options = listOf("All Streams") + uiState.streams.mapNotNull { it.streamName },
                        onOptionSelected = {
                            viewModel.onStreamFilterChanged(if (it == "All Streams") "" else it)
                        }
                    )
                }

                Spacer(modifier = Modifier.height(8.dp))

                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.spacedBy(8.dp)
                ) {
                    FilterDropdown(
                        modifier = Modifier.weight(1f),
                        label = "Gender",
                        selectedOption = if (uiState.genderFilter.isEmpty()) "All Gender" else uiState.genderFilter,
                        options = listOf("All Gender", "Male", "Female"),
                        onOptionSelected = {
                            viewModel.onGenderFilterChanged(if (it == "All Gender") "" else it)
                        }
                    )

                    FilterDropdown(
                        modifier = Modifier.weight(1f),
                        label = "Status",
                        selectedOption = if (uiState.statusFilter.isEmpty()) "All Status" else uiState.statusFilter,
                        options = listOf("All Status", "Active", "Inactive"),
                        onOptionSelected = {
                            viewModel.onStatusFilterChanged(if (it == "All Status") "" else it)
                        }
                    )
                }

                Spacer(modifier = Modifier.height(12.dp))

                OutlinedButton(
                    onClick = { viewModel.resetFilters() },
                    shape = RoundedCornerShape(25.dp),
                    modifier = Modifier.align(Alignment.End),
                    colors = ButtonDefaults.outlinedButtonColors(contentColor = EduHubTextMuted)
                ) {
                    Icon(Icons.Default.Refresh, contentDescription = null, modifier = Modifier.size(16.dp))
                    Spacer(modifier = Modifier.width(6.dp))
                    Text("Reset Filters", fontSize = 12.sp)
                }
            }
        }

        // 2. Student Statistics Summary Grid
        val totalCount = filteredStudents.size
        val activeCount = filteredStudents.count { it.status.equals("active", ignoreCase = true) }
        val inactiveCount = totalCount - activeCount
        val maleCount = filteredStudents.count { it.gender.equals("male", ignoreCase = true) }
        val femaleCount = filteredStudents.count { it.gender.equals("female", ignoreCase = true) }

        var totalBalance = 0.0
        filteredStudents.forEach { student ->
            val bal = student.totalBalance ?: 0.0
            if (bal != 0.0) {
                totalBalance += bal
            } else {
                student.feeBalances?.values?.forEach { fee ->
                    totalBalance += fee.balance
                }
            }
        }

        Column(
            verticalArrangement = Arrangement.spacedBy(8.dp),
            modifier = Modifier.padding(bottom = 16.dp)
        ) {
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                MetricCard(
                    modifier = Modifier.weight(1f),
                    label = "Total Students",
                    value = totalCount.toString(),
                    subtext = "Enrolled Students",
                    icon = Icons.Default.School,
                    iconTint = EduHubOrange
                )
                MetricCard(
                    modifier = Modifier.weight(1f),
                    label = "Active Students",
                    value = activeCount.toString(),
                    subtext = "Active Status",
                    icon = Icons.Default.CheckCircle,
                    iconTint = EduHubOrange
                )
            }

            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                MetricCard(
                    modifier = Modifier.weight(1f),
                    label = "Inactive",
                    value = inactiveCount.toString(),
                    subtext = "Inactive Status",
                    icon = Icons.Default.Cancel,
                    iconTint = EduHubOrange
                )
                MetricCard(
                    modifier = Modifier.weight(1f),
                    label = "Total Balance",
                    value = "KES ${String.format(Locale.US, "%.0f", totalBalance)}",
                    subtext = "Fee Balance",
                    icon = Icons.Default.AccountBalanceWallet,
                    iconTint = EduHubOrange
                )
            }

            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                MetricCard(
                    modifier = Modifier.weight(1f),
                    label = "Male",
                    value = maleCount.toString(),
                    subtext = "Male Students",
                    icon = Icons.Default.Man,
                    iconTint = EduHubOrange
                )
                MetricCard(
                    modifier = Modifier.weight(1f),
                    label = "Female",
                    value = femaleCount.toString(),
                    subtext = "Female Students",
                    icon = Icons.Default.Woman,
                    iconTint = EduHubOrange
                )
            }
        }

        // 3. Fee Balances / Student Roster Table
        Card(
            modifier = Modifier
                .fillMaxWidth()
                .padding(bottom = 16.dp),
            colors = CardDefaults.cardColors(containerColor = Color.White),
            border = BorderStroke(1.5.dp, Color(0xFF1E1E1E)),
            shape = RoundedCornerShape(0.dp),
            elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)
        ) {
            Column(modifier = Modifier.fillMaxWidth()) {
                Box(
                    modifier = Modifier
                        .fillMaxWidth()
                        .background(Color(0xFF2E3B4E))
                        .padding(vertical = 10.dp, horizontal = 12.dp)
                ) {
                    Text(
                        text = "STUDENT ROSTER & FEE BALANCES (${filteredStudents.size})",
                        fontSize = 13.sp,
                        fontWeight = FontWeight.Bold,
                        color = Color.White,
                        fontFamily = FontFamily.Serif
                    )
                }

                if (filteredStudents.isEmpty()) {
                    Text(
                        text = "NO STUDENTS FOUND FOR SELECTED FILTERS",
                        fontSize = 12.sp,
                        fontWeight = FontWeight.Bold,
                        color = Color.Red,
                        fontFamily = FontFamily.Serif,
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(vertical = 24.dp),
                        textAlign = TextAlign.Center
                    )
                } else {
                    val tableScrollState = rememberScrollState()
                    Column(
                        modifier = Modifier
                            .fillMaxWidth()
                            .horizontalScroll(tableScrollState)
                    ) {
                        // Table Header Row
                        Row(
                            modifier = Modifier
                                .background(Color(0xFFE8ECEF))
                                .border(BorderStroke(1.dp, Color.Black)),
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            HeaderCell("ADM NO", width = 80.dp)
                            HeaderCell("STUDENT NAME", width = 140.dp, align = TextAlign.Start)
                            HeaderCell("GENDER", width = 60.dp)
                            HeaderCell("CLASS", width = 85.dp)
                            HeaderCell("STATUS", width = 70.dp)
                            HeaderCell("TOTAL FEES", width = 95.dp)
                            HeaderCell("PAID", width = 90.dp)
                            HeaderCell("BALANCE", width = 100.dp)
                            HeaderCell("ACTION", width = 60.dp)
                        }

                        // Data Rows
                        filteredStudents.forEachIndexed { index, student ->
                            val bg = if (index % 2 == 0) Color.White else Color(0xFFFAFAFA)
                            var studentTotalFees = student.totalFees ?: 0.0
                            var studentTotalPaid = student.totalPaid ?: 0.0
                            var studentBalance = student.totalBalance ?: 0.0

                            if (studentTotalFees == 0.0 && studentTotalPaid == 0.0 && !student.feeBalances.isNullOrEmpty()) {
                                var sumFees = 0.0
                                var sumPaid = 0.0
                                var sumBal = 0.0
                                student.feeBalances.values.forEach {
                                    sumFees += it.totalFees
                                    sumPaid += it.totalPaid
                                    sumBal += it.balance
                                }
                                studentTotalFees = sumFees
                                studentTotalPaid = sumPaid
                                studentBalance = sumBal
                            }

                            val balanceText = if (studentBalance > 0) {
                                "KES ${String.format(Locale.US, "%.0f", studentBalance)} (Due)"
                            } else if (studentBalance < 0) {
                                "KES ${String.format(Locale.US, "%.0f", Math.abs(studentBalance))} (Overpaid)"
                            } else {
                                "KES 0 (Paid)"
                            }

                            val balanceColor = if (studentBalance > 0) EduHubError else if (studentBalance < 0) EduHubSuccess else Color.DarkGray

                            Row(
                                modifier = Modifier
                                    .background(bg)
                                    .border(BorderStroke(0.5.dp, Color.Black)),
                                verticalAlignment = Alignment.CenterVertically
                            ) {
                                DataCell(text = student.admissionNumber, width = 80.dp, fontWeight = FontWeight.Bold)
                                DataCell(text = student.fullName.uppercase(), width = 140.dp, fontWeight = FontWeight.Bold, align = TextAlign.Start)
                                DataCell(text = student.gender ?: "Male", width = 60.dp)
                                DataCell(text = "${student.className ?: "-"}\n${student.streamName ?: ""}".trim(), width = 85.dp)

                                Box(
                                    modifier = Modifier
                                        .width(70.dp)
                                        .border(BorderStroke(0.5.dp, Color.Black))
                                        .padding(vertical = 6.dp, horizontal = 2.dp),
                                    contentAlignment = Alignment.Center
                                ) {
                                    val isActive = student.status.equals("active", ignoreCase = true)
                                    Box(
                                        modifier = Modifier
                                            .clip(RoundedCornerShape(4.dp))
                                            .background(if (isActive) Color(0xFFE6F4EA) else Color(0xFFF1F3F4))
                                            .padding(horizontal = 6.dp, vertical = 2.dp),
                                        contentAlignment = Alignment.Center
                                    ) {
                                        Text(
                                            text = if (isActive) "Active" else "Inactive",
                                            fontSize = 10.sp,
                                            fontWeight = FontWeight.Bold,
                                            color = if (isActive) EduHubSuccess else Color.Gray
                                        )
                                    }
                                }

                                DataCell(text = "KES ${String.format(Locale.US, "%.0f", studentTotalFees)}", width = 95.dp)
                                DataCell(text = "KES ${String.format(Locale.US, "%.0f", studentTotalPaid)}", width = 90.dp)
                                DataCell(text = balanceText, width = 100.dp, fontWeight = FontWeight.Bold, textColor = balanceColor)

                                Box(
                                    modifier = Modifier
                                        .width(60.dp)
                                        .border(BorderStroke(0.5.dp, Color.Black))
                                        .padding(vertical = 4.dp),
                                    contentAlignment = Alignment.Center
                                ) {
                                    IconButton(
                                        onClick = { viewModel.openStudentDetails(student) },
                                        modifier = Modifier.size(28.dp)
                                    ) {
                                        Icon(
                                            imageVector = Icons.Default.Visibility,
                                            contentDescription = "View Details",
                                            tint = EduHubTextDark,
                                            modifier = Modifier.size(18.dp)
                                        )
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        // 4. Disciplinary Records Section Card
        Card(
            modifier = Modifier.fillMaxWidth(),
            colors = CardDefaults.cardColors(containerColor = Color.White),
            border = BorderStroke(1.5.dp, Color(0xFF1E1E1E)),
            shape = RoundedCornerShape(0.dp),
            elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)
        ) {
            Column(modifier = Modifier.fillMaxWidth()) {
                Box(
                    modifier = Modifier
                        .fillMaxWidth()
                        .background(Color(0xFF333333))
                        .padding(vertical = 10.dp, horizontal = 12.dp)
                ) {
                    Text(
                        text = "DISCIPLINARY RECORDS (${uiState.disciplinaryRecords.size})",
                        fontSize = 13.sp,
                        fontWeight = FontWeight.Bold,
                        color = Color.White,
                        fontFamily = FontFamily.Serif
                    )
                }

                if (uiState.disciplinaryRecords.isEmpty()) {
                    Text(
                        text = "NO DISCIPLINARY RECORDS FOUND",
                        fontSize = 12.sp,
                        fontWeight = FontWeight.Bold,
                        color = Color.Gray,
                        fontFamily = FontFamily.Serif,
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(vertical = 20.dp),
                        textAlign = TextAlign.Center
                    )
                } else {
                    val discScrollState = rememberScrollState()
                    Column(
                        modifier = Modifier
                            .fillMaxWidth()
                            .horizontalScroll(discScrollState)
                    ) {
                        Row(
                            modifier = Modifier
                                .background(Color(0xFFE8ECEF))
                                .border(BorderStroke(1.dp, Color.Black)),
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            HeaderCell("ADM NO", width = 80.dp)
                            HeaderCell("STUDENT NAME", width = 130.dp, align = TextAlign.Start)
                            HeaderCell("DATE", width = 85.dp)
                            HeaderCell("ACTION TYPE", width = 95.dp)
                            HeaderCell("SEVERITY", width = 85.dp)
                            HeaderCell("TITLE", width = 140.dp, align = TextAlign.Start)
                            HeaderCell("STATUS", width = 80.dp)
                        }

                        uiState.disciplinaryRecords.forEachIndexed { index, record ->
                            val bg = if (index % 2 == 0) Color.White else Color(0xFFFAFAFA)
                            Row(
                                modifier = Modifier
                                    .background(bg)
                                    .border(BorderStroke(0.5.dp, Color.Black)),
                                verticalAlignment = Alignment.CenterVertically
                            ) {
                                DataCell(text = record.admissionNumber ?: "-", width = 80.dp, fontWeight = FontWeight.Bold)
                                DataCell(text = (record.studentName ?: "-").uppercase(), width = 130.dp, fontWeight = FontWeight.Bold, align = TextAlign.Start)
                                DataCell(text = record.incidentDate ?: "-", width = 85.dp)
                                DataCell(text = record.actionType ?: "-", width = 95.dp)
                                DataCell(
                                    text = record.severity ?: "Moderate",
                                    width = 85.dp,
                                    textColor = if (record.severity.equals("critical", ignoreCase = true)) EduHubError else EduHubOrange
                                )
                                DataCell(text = record.title ?: "-", width = 140.dp, align = TextAlign.Start)
                                DataCell(text = record.status ?: "active", width = 80.dp)
                            }
                        }
                    }
                }
            }
        }

        Spacer(modifier = Modifier.height(24.dp))
    }

    // Student Details Modal
    if (uiState.showDetailsDialog && uiState.selectedStudentDetail != null) {
        val student = uiState.selectedStudentDetail!!
        Dialog(onDismissRequest = { viewModel.closeStudentDetails() }) {
            Surface(
                shape = RoundedCornerShape(16.dp),
                color = Color.White,
                border = BorderStroke(1.dp, EduHubBorder),
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(16.dp)
            ) {
                Column(modifier = Modifier.padding(20.dp)) {
                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.SpaceBetween,
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Text("Student Details", fontSize = 18.sp, fontWeight = FontWeight.Bold, color = EduHubTextDark)
                        IconButton(onClick = { viewModel.closeStudentDetails() }) {
                            Icon(Icons.Default.Close, contentDescription = null)
                        }
                    }

                    Spacer(modifier = Modifier.height(12.dp))
                    HorizontalDivider(color = EduHubBorder)
                    Spacer(modifier = Modifier.height(12.dp))

                    Text("Admission No: ${student.admissionNumber}", fontSize = 14.sp, fontWeight = FontWeight.Bold)
                    Text("Name: ${student.fullName}", fontSize = 14.sp, fontWeight = FontWeight.Bold)
                    Text("Gender: ${student.gender ?: "Male"}", fontSize = 13.sp, color = EduHubTextMuted)
                    Text("Class: ${student.className ?: "Not assigned"} ${student.streamName ?: ""}".trim(), fontSize = 13.sp, color = EduHubTextMuted)
                    Text("Date of Birth: ${student.dateOfBirth ?: "N/A"}", fontSize = 13.sp, color = EduHubTextMuted)
                    Text("Admission Date: ${student.admissionDate ?: "N/A"}", fontSize = 13.sp, color = EduHubTextMuted)
                    Text("Status: ${student.status ?: "active"}", fontSize = 13.sp, fontWeight = FontWeight.Bold, color = EduHubSuccess)

                    Spacer(modifier = Modifier.height(16.dp))

                    OutlinedButton(
                        onClick = { viewModel.closeStudentDetails() },
                        modifier = Modifier.align(Alignment.End),
                        shape = RoundedCornerShape(25.dp)
                    ) {
                        Text("Close")
                    }
                }
            }
        }
    }
    }
    }
}

private fun downloadAndShareCsv(context: Context, csvContent: String) {
    try {
        val fileName = "students_${System.currentTimeMillis()}.csv"

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

        val cacheFile = File(context.cacheDir, "students_export.csv")
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

        context.startActivity(Intent.createChooser(shareIntent, "Export Student List"))
        Toast.makeText(context, "Student list saved to Downloads folder!", Toast.LENGTH_LONG).show()
    } catch (e: Exception) {
        Toast.makeText(context, "Error exporting students: ${e.localizedMessage}", Toast.LENGTH_SHORT).show()
    }
}

@Composable
private fun MetricCard(
    modifier: Modifier = Modifier,
    label: String,
    value: String,
    subtext: String = "",
    icon: ImageVector,
    iconTint: Color
) {
    Card(
        modifier = modifier,
        shape = RoundedCornerShape(8.dp),
        colors = CardDefaults.cardColors(containerColor = EduHubBackground),
        border = BorderStroke(1.dp, EduHubBorder),
        elevation = CardDefaults.cardElevation(defaultElevation = 0.dp)
    ) {
        Column(
            modifier = Modifier
                .fillMaxWidth()
                .padding(16.dp),
            horizontalAlignment = Alignment.CenterHorizontally
        ) {
            Icon(
                imageVector = icon,
                contentDescription = null,
                tint = iconTint,
                modifier = Modifier.size(32.dp)
            )
            Spacer(modifier = Modifier.height(8.dp))
            Text(text = label, fontSize = 13.sp, color = EduHubTextMuted, textAlign = TextAlign.Center)
            Spacer(modifier = Modifier.height(4.dp))
            Text(text = value, fontSize = 24.sp, fontWeight = FontWeight.Normal, color = EduHubTextDark, textAlign = TextAlign.Center)
            if (subtext.isNotEmpty()) {
                Spacer(modifier = Modifier.height(4.dp))
                Text(
                    text = subtext,
                    fontSize = 11.sp,
                    color = EduHubTextMuted,
                    textAlign = TextAlign.Center
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
    Box(
        modifier = Modifier
            .width(width)
            .border(BorderStroke(0.5.dp, Color.Black))
            .padding(vertical = 10.dp, horizontal = 4.dp),
        contentAlignment = Alignment.Center
    ) {
        Text(
            text = text,
            fontSize = 10.sp,
            fontWeight = FontWeight.Bold,
            fontFamily = FontFamily.Serif,
            color = Color.Black,
            textAlign = align
        )
    }
}

@Composable
private fun DataCell(
    text: String,
    width: androidx.compose.ui.unit.Dp,
    fontWeight: FontWeight = FontWeight.Normal,
    textColor: Color = Color.Black,
    align: TextAlign = TextAlign.Center
) {
    Box(
        modifier = Modifier
            .width(width)
            .border(BorderStroke(0.5.dp, Color.Black))
            .padding(vertical = 8.dp, horizontal = 4.dp),
        contentAlignment = Alignment.Center
    ) {
        Text(
            text = text,
            fontSize = 11.sp,
            fontWeight = fontWeight,
            fontFamily = FontFamily.Serif,
            color = textColor,
            textAlign = align
        )
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
            label = { Text(label, fontSize = 11.sp) },
            trailingIcon = { ExposedDropdownMenuDefaults.TrailingIcon(expanded = expanded) },
            modifier = Modifier
                .fillMaxWidth()
                .menuAnchor(),
            singleLine = true,
            shape = RoundedCornerShape(25.dp),
            colors = OutlinedTextFieldDefaults.colors(
                focusedBorderColor = EduHubOrange,
                unfocusedBorderColor = EduHubBorder,
                focusedContainerColor = EduHubBackground,
                unfocusedContainerColor = EduHubBackground,
                focusedTextColor = EduHubTextDark,
                unfocusedTextColor = EduHubTextDark
            )
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
