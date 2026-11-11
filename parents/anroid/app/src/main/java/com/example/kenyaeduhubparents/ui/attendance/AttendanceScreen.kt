package com.example.kenyaeduhubparents.ui.attendance

import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.DropdownMenuItem
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.ExposedDropdownMenuBox
import androidx.compose.material3.ExposedDropdownMenuDefaults
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Text
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
import androidx.lifecycle.viewmodel.compose.viewModel
import com.example.kenyaeduhubparents.data.model.AttendanceRecord

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun AttendanceScreen(
    modifier: Modifier = Modifier,
    viewModel: AttendanceViewModel = viewModel(
        factory = AttendanceViewModel.Factory(LocalContext.current)
    )
) {
    val uiState by viewModel.uiState.collectAsState()

    Column(
        modifier = modifier
            .fillMaxSize()
            .verticalScroll(rememberScrollState())
            .padding(16.dp),
        verticalArrangement = Arrangement.spacedBy(20.dp)
    ) {
        Column(
            modifier = Modifier.fillMaxWidth(),
            horizontalAlignment = Alignment.CenterHorizontally,
            verticalArrangement = Arrangement.spacedBy(4.dp)
        ) {
            Text(
                text = "Attendance",
                style = MaterialTheme.typography.headlineMedium,
                fontWeight = FontWeight.Bold,
                color = MaterialTheme.colorScheme.onBackground
            )
            Text(
                text = "View your child's attendance records by term and year",
                style = MaterialTheme.typography.bodyMedium,
                color = MaterialTheme.colorScheme.onSurfaceVariant,
                textAlign = TextAlign.Center
            )
        }

        when (val state = uiState) {
            is AttendanceUiState.Loading -> {
                Box(
                    modifier = Modifier
                        .fillMaxWidth()
                        .height(250.dp),
                    contentAlignment = Alignment.Center
                ) {
                    CircularProgressIndicator(color = MaterialTheme.colorScheme.primary)
                }
            }
            is AttendanceUiState.Error -> {
                Card(
                    modifier = Modifier.fillMaxWidth(),
                    colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.errorContainer),
                    shape = RoundedCornerShape(16.dp)
                ) {
                    Column(
                        modifier = Modifier.padding(16.dp),
                        verticalArrangement = Arrangement.spacedBy(12.dp)
                    ) {
                        Text(
                            text = state.message,
                            color = MaterialTheme.colorScheme.onErrorContainer,
                            style = MaterialTheme.typography.bodyMedium
                        )
                        OutlinedButton(onClick = { viewModel.loadAttendance() }) {
                            Text("Retry")
                        }
                    }
                }
            }
            is AttendanceUiState.Success -> {
                val data = state.data
                val children = data.children
                val attendanceData = data.attendanceData

                // Filter Card (Child, Term, Year)
                Card(
                    modifier = Modifier.fillMaxWidth(),
                    shape = RoundedCornerShape(20.dp),
                    colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                    elevation = CardDefaults.cardElevation(defaultElevation = 1.dp)
                ) {
                    Column(
                        modifier = Modifier.padding(18.dp),
                        verticalArrangement = Arrangement.spacedBy(14.dp)
                    ) {
                        Text(
                            text = "Filter Attendance Records",
                            style = MaterialTheme.typography.titleMedium,
                            fontWeight = FontWeight.Bold,
                            color = MaterialTheme.colorScheme.onSurface
                        )

                        if (children.isEmpty()) {
                            Text(
                                text = "No children found.",
                                style = MaterialTheme.typography.bodyMedium,
                                color = MaterialTheme.colorScheme.onSurfaceVariant
                            )
                        } else {
                            // Child Filter Dropdown
                            var childExpanded by remember { mutableStateOf(false) }
                            val selectedChild = children.find { it.id.toString() == viewModel.selectedChildId } ?: children.firstOrNull()

                            ExposedDropdownMenuBox(
                                expanded = childExpanded,
                                onExpandedChange = { childExpanded = !childExpanded }
                            ) {
                                OutlinedTextField(
                                    value = selectedChild?.fullName ?: "Select Child",
                                    onValueChange = {},
                                    readOnly = true,
                                    label = { Text("Child") },
                                    trailingIcon = { ExposedDropdownMenuDefaults.TrailingIcon(expanded = childExpanded) },
                                    modifier = Modifier
                                        .fillMaxWidth()
                                        .menuAnchor(),
                                    shape = RoundedCornerShape(25.dp)
                                )
                                ExposedDropdownMenu(
                                    expanded = childExpanded,
                                    onDismissRequest = { childExpanded = false }
                                ) {
                                    children.forEach { child ->
                                        DropdownMenuItem(
                                            text = { Text("${child.fullName} (${child.admissionNumber})") },
                                            onClick = {
                                                viewModel.updateFilters(child.id.toString(), viewModel.selectedTerm, viewModel.selectedYear)
                                                childExpanded = false
                                            }
                                        )
                                    }
                                }
                            }

                            // Term Filter Dropdown
                            val terms = data.terms.ifEmpty { listOf("Term 1", "Term 2", "Term 3") }
                            var termExpanded by remember { mutableStateOf(false) }
                            val selectedTermVal = viewModel.selectedTerm ?: data.currentTerm ?: terms.first()

                            ExposedDropdownMenuBox(
                                expanded = termExpanded,
                                onExpandedChange = { termExpanded = !termExpanded }
                            ) {
                                OutlinedTextField(
                                    value = selectedTermVal,
                                    onValueChange = {},
                                    readOnly = true,
                                    label = { Text("Term") },
                                    trailingIcon = { ExposedDropdownMenuDefaults.TrailingIcon(expanded = termExpanded) },
                                    modifier = Modifier
                                        .fillMaxWidth()
                                        .menuAnchor(),
                                    shape = RoundedCornerShape(25.dp)
                                )
                                ExposedDropdownMenu(
                                    expanded = termExpanded,
                                    onDismissRequest = { termExpanded = false }
                                ) {
                                    terms.forEach { term ->
                                        DropdownMenuItem(
                                            text = { Text(term) },
                                            onClick = {
                                                viewModel.updateFilters(viewModel.selectedChildId, term, viewModel.selectedYear)
                                                termExpanded = false
                                            }
                                        )
                                    }
                                }
                            }

                            // Year Filter Dropdown
                            val years = data.years.ifEmpty { listOf(data.currentYear) }
                            var yearExpanded by remember { mutableStateOf(false) }
                            val selectedYearVal = viewModel.selectedYear ?: data.currentYear.toString()

                            ExposedDropdownMenuBox(
                                expanded = yearExpanded,
                                onExpandedChange = { yearExpanded = !yearExpanded }
                            ) {
                                OutlinedTextField(
                                    value = selectedYearVal,
                                    onValueChange = {},
                                    readOnly = true,
                                    label = { Text("Year") },
                                    trailingIcon = { ExposedDropdownMenuDefaults.TrailingIcon(expanded = yearExpanded) },
                                    modifier = Modifier
                                        .fillMaxWidth()
                                        .menuAnchor(),
                                    shape = RoundedCornerShape(25.dp)
                                )
                                ExposedDropdownMenu(
                                    expanded = yearExpanded,
                                    onDismissRequest = { yearExpanded = false }
                                ) {
                                    years.forEach { year ->
                                        DropdownMenuItem(
                                            text = { Text(year.toString()) },
                                            onClick = {
                                                viewModel.updateFilters(viewModel.selectedChildId, viewModel.selectedTerm, year.toString())
                                                yearExpanded = false
                                            }
                                        )
                                    }
                                }
                            }
                        }
                    }
                }

                if (attendanceData != null) {
                    // Attendance Summary Card
                    Card(
                        modifier = Modifier.fillMaxWidth(),
                        shape = RoundedCornerShape(20.dp),
                        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                        elevation = CardDefaults.cardElevation(defaultElevation = 1.dp)
                    ) {
                        Column(
                            modifier = Modifier.padding(18.dp),
                            verticalArrangement = Arrangement.spacedBy(16.dp)
                        ) {
                            Text(
                                text = "Attendance Summary",
                                style = MaterialTheme.typography.titleMedium,
                                fontWeight = FontWeight.Bold,
                                color = MaterialTheme.colorScheme.onSurface
                            )

                            Row(
                                modifier = Modifier.fillMaxWidth(),
                                horizontalArrangement = Arrangement.spacedBy(12.dp)
                            ) {
                                SummaryBox(label = "Total Days", value = attendanceData.totalDays.toString(), modifier = Modifier.weight(1f))
                                SummaryBox(label = "Present", value = attendanceData.presentCount.toString(), modifier = Modifier.weight(1f), isPositive = true)
                            }
                            Row(
                                modifier = Modifier.fillMaxWidth(),
                                horizontalArrangement = Arrangement.spacedBy(12.dp)
                            ) {
                                SummaryBox(label = "Absent", value = attendanceData.absentCount.toString(), modifier = Modifier.weight(1f), isNegative = true)
                                SummaryBox(label = "Late", value = attendanceData.lateCount.toString(), modifier = Modifier.weight(1f))
                            }
                            Row(
                                modifier = Modifier.fillMaxWidth(),
                                horizontalArrangement = Arrangement.spacedBy(12.dp)
                            ) {
                                SummaryBox(label = "Excused", value = attendanceData.excusedCount.toString(), modifier = Modifier.weight(1f))
                                SummaryBox(label = "Attendance Rate", value = "${attendanceData.attendancePercentage}%", modifier = Modifier.weight(1f), isPositive = attendanceData.attendancePercentage >= 80.0)
                            }
                        }
                    }

                    // Attendance History Card
                    Card(
                        modifier = Modifier.fillMaxWidth(),
                        shape = RoundedCornerShape(20.dp),
                        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                        elevation = CardDefaults.cardElevation(defaultElevation = 1.dp)
                    ) {
                        Column(
                            modifier = Modifier.padding(18.dp),
                            verticalArrangement = Arrangement.spacedBy(12.dp)
                        ) {
                            Text(
                                text = "Attendance History",
                                style = MaterialTheme.typography.titleMedium,
                                fontWeight = FontWeight.Bold,
                                color = MaterialTheme.colorScheme.onSurface
                            )

                            val records = attendanceData.records
                            if (records.isEmpty()) {
                                Text(
                                    text = "No attendance records found for this child with the selected filters.",
                                    style = MaterialTheme.typography.bodyMedium,
                                    color = MaterialTheme.colorScheme.onSurfaceVariant
                                )
                            } else {
                                Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
                                    Row(
                                        modifier = Modifier
                                            .fillMaxWidth()
                                            .background(Color(0xFFF0F0F0))
                                            .border(1.dp, Color.Black)
                                            .padding(8.dp),
                                        horizontalArrangement = Arrangement.SpaceBetween
                                    ) {
                                        Text(text = "Date", fontWeight = FontWeight.Bold, fontSize = 12.sp, modifier = Modifier.weight(1.5f))
                                        Text(text = "Status", fontWeight = FontWeight.Bold, fontSize = 12.sp, modifier = Modifier.weight(1f))
                                        Text(text = "Remarks", fontWeight = FontWeight.Bold, fontSize = 12.sp, modifier = Modifier.weight(2f))
                                    }

                                    records.forEach { record ->
                                        AttendanceRecordRow(record = record)
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
private fun SummaryBox(
    label: String,
    value: String,
    modifier: Modifier = Modifier,
    isPositive: Boolean = false,
    isNegative: Boolean = false
) {
    Card(
        modifier = modifier,
        shape = RoundedCornerShape(14.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.3f)),
        elevation = CardDefaults.cardElevation(defaultElevation = 0.dp)
    ) {
        Column(
            modifier = Modifier.padding(12.dp),
            verticalArrangement = Arrangement.spacedBy(2.dp)
        ) {
            Text(
                text = label,
                style = MaterialTheme.typography.labelSmall,
                color = MaterialTheme.colorScheme.onSurfaceVariant
            )
            Text(
                text = value,
                style = MaterialTheme.typography.bodyMedium,
                fontWeight = FontWeight.Bold,
                color = when {
                    isPositive -> Color(0xFF137333)
                    isNegative -> Color(0xFFC5221F)
                    else -> MaterialTheme.colorScheme.onSurface
                }
            )
        }
    }
}

@Composable
private fun AttendanceRecordRow(record: AttendanceRecord) {
    val statusColor = when (record.status.lowercase()) {
        "present" -> Color(0xFF137333)
        "absent" -> Color(0xFFC5221F)
        "late" -> Color(0xFFB26A00)
        else -> Color(0xFF1967D2)
    }

    Row(
        modifier = Modifier
            .fillMaxWidth()
            .border(1.dp, Color.LightGray, RoundedCornerShape(8.dp))
            .padding(8.dp),
        horizontalArrangement = Arrangement.SpaceBetween,
        verticalAlignment = Alignment.CenterVertically
    ) {
        Text(text = record.date, fontSize = 12.sp, color = Color.Black, modifier = Modifier.weight(1.5f))
        Box(
            modifier = Modifier
                .background(statusColor.copy(alpha = 0.12f), RoundedCornerShape(999.dp))
                .border(1.dp, statusColor.copy(alpha = 0.35f), RoundedCornerShape(999.dp))
                .padding(horizontal = 8.dp, vertical = 4.dp)
                .weight(1f),
            contentAlignment = Alignment.Center
        ) {
            TestText(record.displayStatus, statusColor)
        }
        Text(text = record.remarks ?: "-", fontSize = 12.sp, color = Color.Black, modifier = Modifier.weight(2f))
    }
}

@Composable
private fun TestText(text: String, color: Color) {
    Text(
        text = text,
        style = MaterialTheme.typography.labelSmall,
        color = color,
        fontWeight = FontWeight.Bold
    )
}
