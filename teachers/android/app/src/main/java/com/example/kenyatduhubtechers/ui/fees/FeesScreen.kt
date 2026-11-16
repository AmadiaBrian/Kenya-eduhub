package com.example.kenyatduhubtechers.ui.fees

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
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.CalendarMonth
import androidx.compose.material.icons.filled.Close
import androidx.compose.material.icons.filled.Download
import androidx.compose.material.icons.filled.Event
import androidx.compose.material.icons.filled.FileDownload
import androidx.compose.material.icons.filled.Mail
import androidx.compose.material.icons.filled.RequestPage
import androidx.compose.material.icons.filled.School
import androidx.compose.material.icons.filled.Search
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
import androidx.core.content.FileProvider
import com.example.kenyatduhubtechers.data.model.StudentFeeSummaryItemDto
import com.example.kenyatduhubtechers.data.network.RetrofitClient
import com.example.kenyatduhubtechers.ui.theme.EduHubBackground
import com.example.kenyatduhubtechers.ui.theme.EduHubBorder
import com.example.kenyatduhubtechers.ui.theme.EduHubError
import com.example.kenyatduhubtechers.ui.theme.EduHubErrorContainer
import com.example.kenyatduhubtechers.ui.theme.EduHubOrange
import com.example.kenyatduhubtechers.ui.theme.EduHubSuccess
import com.example.kenyatduhubtechers.ui.theme.EduHubSuccessContainer
import com.example.kenyatduhubtechers.ui.theme.EduHubTextDark
import com.example.kenyatduhubtechers.ui.theme.EduHubTextMuted
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext
import java.io.File
import java.text.SimpleDateFormat
import java.util.Date
import java.util.Locale

@OptIn(ExperimentalLayoutApi::class, ExperimentalMaterial3Api::class)
@Composable
fun FeesScreen(
    viewModel: FeesViewModel,
    modifier: Modifier = Modifier
) {
    val uiState by viewModel.uiState.collectAsState()
    val context = LocalContext.current

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
            .padding(12.dp)
    ) {
        Text(
            text = "Fee Payments",
            fontSize = 22.sp,
            fontWeight = FontWeight.Bold,
            color = EduHubTextDark
        )
        Text(
            text = "View fee payment history and balance for students in your class",
            fontSize = 12.sp,
            color = EduHubTextMuted,
            modifier = Modifier.padding(bottom = 16.dp)
        )

        // Success / Error Alerts
        AnimatedVisibility(visible = uiState.successMessage != null) {
            uiState.successMessage?.let { msg ->
                Card(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(bottom = 12.dp),
                    colors = CardDefaults.cardColors(containerColor = EduHubSuccessContainer),
                    border = BorderStroke(1.dp, EduHubSuccess),
                    shape = RoundedCornerShape(8.dp)
                ) {
                    Row(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(12.dp),
                        verticalAlignment = Alignment.CenterVertically,
                        horizontalArrangement = Arrangement.SpaceBetween
                    ) {
                        Text(text = msg, color = EduHubSuccess, fontSize = 13.sp, modifier = Modifier.weight(1f))
                        IconButton(onClick = { viewModel.clearMessages() }, modifier = Modifier.size(24.dp)) {
                            Icon(Icons.Default.Close, contentDescription = null, tint = EduHubSuccess)
                        }
                    }
                }
            }
        }

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

        // Sleek Orange Session Status Banner
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
                    text = "School Session & Fee Management",
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
                            text = "Class Fee Tracker - ${uiState.teacher?.schoolName ?: "Kenya EduHub"}",
                            fontSize = 15.sp,
                            fontWeight = FontWeight.Bold,
                            color = Color.White
                        )
                        Text(
                            text = "Class: ${uiState.teacher?.className ?: "All Classes"} ${uiState.teacher?.streamName ?: ""}".trim(),
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
                        Icon(Icons.Default.CalendarMonth, contentDescription = null, tint = Color.White, modifier = Modifier.size(16.dp))
                        Spacer(modifier = Modifier.width(6.dp))
                        Text("Academic Year 2026", fontSize = 12.sp, color = Color.White)
                    }

                    Row(verticalAlignment = Alignment.CenterVertically) {
                        Icon(Icons.Default.Event, contentDescription = null, tint = Color.White, modifier = Modifier.size(16.dp))
                        Spacer(modifier = Modifier.width(6.dp))
                        Text("Today: ${SimpleDateFormat("MMMM d, yyyy", Locale.getDefault()).format(Date())}", fontSize = 12.sp, color = Color.White)
                    }
                }
            }
        }

        // 1. Select Student Card
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
                Text("Select Student", fontSize = 16.sp, fontWeight = FontWeight.Bold, color = EduHubTextDark, modifier = Modifier.padding(bottom = 12.dp))

                // Search Student Input
                OutlinedTextField(
                    value = uiState.studentSearchQuery,
                    onValueChange = { viewModel.onStudentSearchChanged(it) },
                    modifier = Modifier.fillMaxWidth(),
                    placeholder = { Text("Search Student (Name or Admission No)", color = EduHubTextMuted, fontSize = 13.sp) },
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

                Spacer(modifier = Modifier.height(10.dp))

                // Filtered Students Dropdown
                val filteredStudentList = remember(uiState.students, uiState.studentSearchQuery) {
                    val q = uiState.studentSearchQuery.lowercase().trim()
                    if (q.isEmpty()) uiState.students
                    else uiState.students.filter { it.admissionNumber.lowercase().contains(q) || it.fullName.lowercase().contains(q) }
                }

                val studentMap = remember(filteredStudentList) {
                    filteredStudentList.associate { "${it.admissionNumber} - ${it.fullName}" to it.id }
                }
                val currentStudentLabel = studentMap.entries.find { it.value == uiState.selectedStudentId }?.key ?: "Select a student"

                FilterDropdown(
                    modifier = Modifier.fillMaxWidth(),
                    label = "Student",
                    selectedOption = currentStudentLabel,
                    options = listOf("Select a student") + studentMap.keys.toList(),
                    onOptionSelected = {
                        val id = if (it == "Select a student") null else studentMap[it]
                        viewModel.onStudentSelected(id)
                    }
                )

                Spacer(modifier = Modifier.height(14.dp))

                // Statement Generation Action Buttons
                Button(
                    onClick = {
                        viewModel.generateClassFeeStatement { url, filename ->
                            downloadStatementToPhoneDownloads(context, url, filename)
                        }
                    },
                    enabled = !uiState.isGeneratingStatement,
                    modifier = Modifier.fillMaxWidth(),
                    shape = RoundedCornerShape(25.dp),
                    colors = ButtonDefaults.buttonColors(containerColor = EduHubOrange)
                ) {
                    if (uiState.isGeneratingStatement) {
                        CircularProgressIndicator(color = Color.White, modifier = Modifier.size(20.dp))
                    } else {
                        Icon(Icons.Default.RequestPage, contentDescription = null, modifier = Modifier.size(18.dp))
                        Spacer(modifier = Modifier.width(8.dp))
                        Text("Generate Class Fee Statement", fontSize = 13.sp, fontWeight = FontWeight.Bold)
                    }
                }

                if (uiState.selectedStudentId != null) {
                    Spacer(modifier = Modifier.height(8.dp))
                    OutlinedButton(
                        onClick = {
                            viewModel.generateStudentFeeStatement(uiState.selectedStudentId!!) { url, filename ->
                                downloadStatementToPhoneDownloads(context, url, filename)
                            }
                        },
                        enabled = !uiState.isGeneratingStatement,
                        modifier = Modifier.fillMaxWidth(),
                        shape = RoundedCornerShape(25.dp)
                    ) {
                        Icon(Icons.Default.FileDownload, contentDescription = null, modifier = Modifier.size(18.dp))
                        Spacer(modifier = Modifier.width(8.dp))
                        Text("Generate Student Fee Statement", fontSize = 13.sp)
                    }
                }
            }
        }

        // 2. Stream Fee Summary Statistics Grid
        var allStudentsCount = 0
        var totalFeesSum = 0.0
        var totalPaidSum = 0.0
        var totalBalanceSum = 0.0
        var paidCount = 0
        var unpaidCount = 0

        val allItems = mutableListOf<StudentFeeSummaryItemDto>()
        uiState.streamFeeSummary.forEach { summaryGroup ->
            summaryGroup.students.forEach { item ->
                allItems.add(item)
            }
        }

        val uniqueStudentItems = allItems.distinctBy { it.admissionNumber }
        allStudentsCount = uniqueStudentItems.size
        uniqueStudentItems.forEach { item ->
            totalFeesSum += item.totalFee
            totalPaidSum += item.totalPaid
            totalBalanceSum += item.totalBalance
            if (item.totalBalance <= 0) paidCount++ else unpaidCount++
        }

        val collectionRate = if (totalFeesSum > 0) String.format(Locale.US, "%.1f", (totalPaidSum / totalFeesSum) * 100) else "0.0"

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
                    value = allStudentsCount.toString(),
                    bgColor = Color(0xFFE8F0FE),
                    textColor = Color(0xFF1967D2)
                )
                MetricCard(
                    modifier = Modifier.weight(1f),
                    label = "Total Collected",
                    value = "KES ${String.format(Locale.US, "%.0f", totalPaidSum)}",
                    bgColor = Color(0xFFE6F4EA),
                    textColor = EduHubSuccess
                )
            }

            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                MetricCard(
                    modifier = Modifier.weight(1f),
                    label = "Total Balance",
                    value = "KES ${String.format(Locale.US, "%.0f", totalBalanceSum)}",
                    bgColor = Color(0xFFFCE8E6),
                    textColor = EduHubError
                )
                MetricCard(
                    modifier = Modifier.weight(1f),
                    label = "Collection Rate",
                    value = "$collectionRate%",
                    bgColor = Color(0xFFFEF7E0),
                    textColor = Color(0xFFB06000)
                )
            }

            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                MetricCard(
                    modifier = Modifier.weight(1f),
                    label = "Fully Paid",
                    value = paidCount.toString(),
                    bgColor = Color(0xFFF1F3F4),
                    textColor = EduHubTextDark
                )
                MetricCard(
                    modifier = Modifier.weight(1f),
                    label = "Not Paid",
                    value = unpaidCount.toString(),
                    bgColor = Color(0xFFE8F0FE),
                    textColor = Color(0xFF1967D2)
                )
            }
        }

        // 3. Stream Fee Summary Cards & Action Bar
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
                    Text("Stream Fee Summary", fontSize = 16.sp, fontWeight = FontWeight.Bold, color = EduHubTextDark)
                    OutlinedButton(
                        onClick = {
                            val csvStr = viewModel.generateCsvSummaryExport()
                            downloadAndShareFeesCsv(context, csvStr, "fee_summary_export.csv")
                        },
                        shape = RoundedCornerShape(25.dp)
                    ) {
                        Icon(Icons.Default.Download, contentDescription = null, modifier = Modifier.size(16.dp))
                        Spacer(modifier = Modifier.width(4.dp))
                        Text("Export CSV", fontSize = 12.sp)
                    }
                }

                Spacer(modifier = Modifier.height(12.dp))

                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.spacedBy(8.dp)
                ) {
                    val allFeeTypes = uiState.streamFeeSummary.map { it.feeType }
                    FilterDropdown(
                        modifier = Modifier.weight(1f),
                        label = "Fee Type",
                        selectedOption = if (uiState.feeTypeFilter.isEmpty()) "All Fee Types" else uiState.feeTypeFilter,
                        options = listOf("All Fee Types") + allFeeTypes,
                        onOptionSelected = {
                            viewModel.onFeeTypeFilterChanged(if (it == "All Fee Types") "" else it)
                        }
                    )

                    FilterDropdown(
                        modifier = Modifier.weight(1f),
                        label = "Payment Status",
                        selectedOption = if (uiState.paymentStatusFilter.isEmpty()) "All Status" else uiState.paymentStatusFilter,
                        options = listOf("All Status", "Paid", "Partial", "Not Paid"),
                        onOptionSelected = {
                            viewModel.onPaymentStatusFilterChanged(if (it == "All Status") "" else it)
                        }
                    )
                }

                Spacer(modifier = Modifier.height(12.dp))

                Button(
                    onClick = { viewModel.sendFeeReminders() },
                    enabled = !uiState.isSendingReminders,
                    modifier = Modifier.fillMaxWidth(),
                    shape = RoundedCornerShape(25.dp),
                    colors = ButtonDefaults.buttonColors(containerColor = EduHubOrange)
                ) {
                    if (uiState.isSendingReminders) {
                        CircularProgressIndicator(color = Color.White, modifier = Modifier.size(20.dp))
                    } else {
                        Icon(Icons.Default.Mail, contentDescription = null, modifier = Modifier.size(18.dp))
                        Spacer(modifier = Modifier.width(8.dp))
                        Text("Send Fee Reminders to Parents", fontSize = 13.sp, fontWeight = FontWeight.Bold)
                    }
                }
            }
        }

        // 4. Stream Fee Summary Full 100% Width Table
        if (uiState.streamFeeSummary.isEmpty()) {
            Card(
                modifier = Modifier.fillMaxWidth(),
                colors = CardDefaults.cardColors(containerColor = Color.White),
                border = BorderStroke(1.dp, EduHubBorder),
                shape = RoundedCornerShape(8.dp)
            ) {
                Text(
                    text = "No fee summary records found.",
                    fontSize = 13.sp,
                    color = EduHubTextMuted,
                    modifier = Modifier.padding(20.dp),
                    textAlign = TextAlign.Center
                )
            }
        } else {
            val filteredSummaryList = remember(uiState.streamFeeSummary, uiState.feeTypeFilter, uiState.paymentStatusFilter) {
                uiState.streamFeeSummary.filter { group ->
                    val matchesType = uiState.feeTypeFilter.isEmpty() || group.feeType.equals(uiState.feeTypeFilter, ignoreCase = true)
                    matchesType
                }
            }

            filteredSummaryList.forEach { feeGroup ->
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
                                text = "${feeGroup.feeType.uppercase()} FEES",
                                fontSize = 13.sp,
                                fontWeight = FontWeight.Bold,
                                color = Color.White,
                                fontFamily = FontFamily.Serif
                            )
                        }

                        val feeTableScrollState = rememberScrollState()
                        Column(
                            modifier = Modifier
                                .fillMaxWidth()
                                .horizontalScroll(feeTableScrollState)
                        ) {
                            // Header Row
                            Row(
                                modifier = Modifier
                                    .background(Color(0xFFE8ECEF))
                                    .border(BorderStroke(1.dp, Color.Black)),
                                verticalAlignment = Alignment.CenterVertically
                            ) {
                                HeaderCell("ADM NO", width = 80.dp)
                                HeaderCell("STUDENT NAME", width = 140.dp, align = TextAlign.Start)
                                HeaderCell("STREAM", width = 85.dp)
                                HeaderCell("TOTAL FEES", width = 95.dp)
                                HeaderCell("TOTAL PAID", width = 95.dp)
                                HeaderCell("BALANCE", width = 95.dp)
                                HeaderCell("STATUS", width = 80.dp)
                            }

                            val studentRows = feeGroup.students.filter { stu ->
                                val statusText = if (stu.totalBalance <= 0) "Paid" else if (stu.totalPaid > 0) "Partial" else "Not Paid"
                                val matchesStatus = uiState.paymentStatusFilter.isEmpty() || statusText.equals(uiState.paymentStatusFilter, ignoreCase = true)
                                matchesStatus
                            }

                            studentRows.forEachIndexed { index, stu ->
                                val bg = if (index % 2 == 0) Color.White else Color(0xFFFAFAFA)
                                val statusText = if (stu.totalBalance <= 0) "Paid" else if (stu.totalPaid > 0) "Partial" else "Not Paid"
                                val statusColor = if (stu.totalBalance <= 0) EduHubSuccess else if (stu.totalPaid > 0) Color(0xFFB06000) else EduHubError

                                Row(
                                    modifier = Modifier
                                        .background(bg)
                                        .border(BorderStroke(0.5.dp, Color.Black)),
                                    verticalAlignment = Alignment.CenterVertically
                                ) {
                                    DataCell(text = stu.admissionNumber, width = 80.dp, fontWeight = FontWeight.Bold)
                                    DataCell(text = stu.studentName.uppercase(), width = 140.dp, fontWeight = FontWeight.Bold, align = TextAlign.Start)
                                    DataCell(text = stu.streamName ?: "-", width = 85.dp)
                                    DataCell(text = "KES ${String.format(Locale.US, "%.0f", stu.totalFee)}", width = 95.dp)
                                    DataCell(text = "KES ${String.format(Locale.US, "%.0f", stu.totalPaid)}", width = 95.dp)
                                    DataCell(text = "KES ${String.format(Locale.US, "%.0f", stu.totalBalance)}", width = 95.dp, fontWeight = FontWeight.Bold, textColor = if (stu.totalBalance > 0) EduHubError else EduHubSuccess)

                                    Box(
                                        modifier = Modifier
                                            .width(80.dp)
                                            .border(BorderStroke(0.5.dp, Color.Black))
                                            .padding(vertical = 6.dp, horizontal = 2.dp),
                                        contentAlignment = Alignment.Center
                                    ) {
                                        Text(
                                            text = statusText,
                                            fontSize = 11.sp,
                                            fontWeight = FontWeight.Bold,
                                            color = statusColor,
                                            fontFamily = FontFamily.Serif
                                        )
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        // 5. Selected Student Fee Breakdown Card (if student selected)
        uiState.studentFeeDetails?.let { feeDetails ->
            Card(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(bottom = 16.dp),
                colors = CardDefaults.cardColors(containerColor = Color.White),
                border = BorderStroke(1.5.dp, Color(0xFF1E1E1E)),
                shape = RoundedCornerShape(0.dp),
                elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)
            ) {
                Column(modifier = Modifier.padding(16.dp)) {
                    Text(
                        text = "Fee Details - ${feeDetails.student?.fullName ?: "Selected Student"}",
                        fontSize = 16.sp,
                        fontWeight = FontWeight.Bold,
                        color = EduHubTextDark,
                        modifier = Modifier.padding(bottom = 12.dp)
                    )

                    Row(
                        modifier = Modifier
                            .fillMaxWidth()
                            .background(Color(0xFFF0F4F9))
                            .border(1.dp, EduHubBorder, RoundedCornerShape(8.dp))
                            .padding(12.dp),
                        horizontalArrangement = Arrangement.SpaceBetween,
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Column {
                            Text("Year Total Balance", fontSize = 12.sp, color = EduHubTextMuted)
                            Text(
                                text = "Paid: KES ${String.format(Locale.US, "%.0f", feeDetails.yearTotalPaid)} / KES ${String.format(Locale.US, "%.0f", feeDetails.yearTotalFees)}",
                                fontSize = 11.sp,
                                color = EduHubTextMuted
                            )
                        }
                        Text(
                            text = "KES ${String.format(Locale.US, "%.0f", feeDetails.yearBalance)}",
                            fontSize = 20.sp,
                            fontWeight = FontWeight.Bold,
                            color = if (feeDetails.yearBalance > 0) EduHubError else EduHubSuccess
                        )
                    }

                    Spacer(modifier = Modifier.height(16.dp))

                    Text("Payment History", fontSize = 14.sp, fontWeight = FontWeight.Bold, color = EduHubTextDark, modifier = Modifier.padding(bottom = 8.dp))

                    if (feeDetails.payments.isEmpty()) {
                        Text("No payment records found.", fontSize = 12.sp, color = EduHubTextMuted)
                    } else {
                        val paymentScrollState = rememberScrollState()
                        Column(
                            modifier = Modifier
                                .fillMaxWidth()
                                .horizontalScroll(paymentScrollState)
                        ) {
                            Row(
                                modifier = Modifier
                                    .background(Color(0xFFE8ECEF))
                                    .border(BorderStroke(1.dp, Color.Black)),
                                verticalAlignment = Alignment.CenterVertically
                            ) {
                                HeaderCell("DATE", width = 85.dp)
                                HeaderCell("FEE TYPE", width = 110.dp, align = TextAlign.Start)
                                HeaderCell("AMOUNT", width = 90.dp)
                                HeaderCell("METHOD", width = 90.dp)
                                HeaderCell("REFERENCE", width = 100.dp)
                                HeaderCell("STATUS", width = 70.dp)
                            }

                            feeDetails.payments.forEachIndexed { index, p ->
                                val bg = if (index % 2 == 0) Color.White else Color(0xFFFAFAFA)
                                Row(
                                    modifier = Modifier
                                        .background(bg)
                                        .border(BorderStroke(0.5.dp, Color.Black)),
                                    verticalAlignment = Alignment.CenterVertically
                                ) {
                                    DataCell(text = p.paymentDate ?: "-", width = 85.dp)
                                    DataCell(text = p.feeType ?: "Tuition", width = 110.dp, fontWeight = FontWeight.Bold, align = TextAlign.Start)
                                    DataCell(text = "KES ${String.format(Locale.US, "%.0f", p.amount)}", width = 90.dp)
                                    DataCell(text = p.paymentMethod ?: "M-Pesa", width = 90.dp)
                                    DataCell(text = p.receiptNumber ?: "-", width = 100.dp)
                                    DataCell(text = "Paid", width = 70.dp, fontWeight = FontWeight.Bold, textColor = EduHubSuccess)
                                }
                            }
                        }
                    }
                }
            }
        }

        Spacer(modifier = Modifier.height(24.dp))
    }
}

private fun downloadStatementToPhoneDownloads(context: Context, relativeUrl: String, filename: String?) {
    CoroutineScope(Dispatchers.IO).launch {
        try {
            val baseUrl = RetrofitClient.getBaseUrl() // "http://10.0.2.2/kenyaeduhub/teachers/api/"
            val teachersRoot = if (baseUrl.contains("/api/")) baseUrl.substringBefore("/api/") else baseUrl.removeSuffix("/")

            val cleanRel = relativeUrl
                .removePrefix("/teachers/")
                .removePrefix("teachers/")
                .removePrefix("/")

            val fullUrl = if (relativeUrl.startsWith("http")) relativeUrl else "$teachersRoot/$cleanRel"

            val url = java.net.URL(fullUrl)
            val connection = url.openConnection()
            connection.connectTimeout = 15000
            connection.readTimeout = 15000
            val bytes = connection.getInputStream().use { it.readBytes() }

            val outFileName = filename?.ifBlank { null } ?: "fee_statement_${System.currentTimeMillis()}.html"

            if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.Q) {
                val contentValues = ContentValues().apply {
                    put(MediaStore.MediaColumns.DISPLAY_NAME, outFileName)
                    put(MediaStore.MediaColumns.MIME_TYPE, if (outFileName.endsWith(".pdf")) "application/pdf" else "text/html")
                    put(MediaStore.MediaColumns.RELATIVE_PATH, Environment.DIRECTORY_DOWNLOADS)
                }
                val uri = context.contentResolver.insert(MediaStore.Downloads.EXTERNAL_CONTENT_URI, contentValues)
                uri?.let {
                    context.contentResolver.openOutputStream(it)?.use { os ->
                        os.write(bytes)
                    }
                }
            } else {
                @Suppress("DEPRECATION")
                val downloadsDir = Environment.getExternalStoragePublicDirectory(Environment.DIRECTORY_DOWNLOADS)
                val file = File(downloadsDir, outFileName)
                file.writeBytes(bytes)
            }

            withContext(Dispatchers.Main) {
                Toast.makeText(context, "Fee statement downloaded to Downloads folder: $outFileName", Toast.LENGTH_LONG).show()
            }
        } catch (e: Exception) {
            withContext(Dispatchers.Main) {
                Toast.makeText(context, "Failed to download statement: ${e.localizedMessage}", Toast.LENGTH_LONG).show()
            }
        }
    }
}

private fun downloadAndShareFeesCsv(context: Context, csvContent: String, fileName: String) {
    try {
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

        val cacheFile = File(context.cacheDir, fileName)
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

        context.startActivity(Intent.createChooser(shareIntent, "Download & Share Fee Summary"))
        Toast.makeText(context, "Fee summary CSV saved to Downloads folder!", Toast.LENGTH_LONG).show()
    } catch (e: Exception) {
        Toast.makeText(context, "Error exporting CSV: ${e.localizedMessage}", Toast.LENGTH_SHORT).show()
    }
}

@Composable
private fun MetricCard(
    modifier: Modifier = Modifier,
    label: String,
    value: String,
    bgColor: Color,
    textColor: Color
) {
    Box(
        modifier = modifier
            .clip(RoundedCornerShape(8.dp))
            .background(bgColor)
            .border(1.dp, textColor.copy(alpha = 0.3f), RoundedCornerShape(8.dp))
            .padding(12.dp),
        contentAlignment = Alignment.Center
    ) {
        Column(horizontalAlignment = Alignment.CenterHorizontally) {
            Text(text = value, fontSize = 20.sp, fontWeight = FontWeight.Bold, color = textColor)
            Spacer(modifier = Modifier.height(2.dp))
            Text(text = label, fontSize = 11.sp, color = EduHubTextMuted)
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
