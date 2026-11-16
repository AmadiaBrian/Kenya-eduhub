package com.example.kenyatduhubtechers.ui.performance

import android.content.ContentValues
import android.content.Context
import android.content.Intent
import android.net.Uri
import android.os.Build
import android.os.Environment
import android.provider.MediaStore
import android.widget.Toast
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.animation.AnimatedVisibility
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
import androidx.compose.foundation.layout.fillMaxHeight
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
import androidx.compose.material.icons.filled.Book
import androidx.compose.material.icons.filled.Close
import androidx.compose.material.icons.filled.CloudUpload
import androidx.compose.material.icons.filled.Download
import androidx.compose.material.icons.filled.Fullscreen
import androidx.compose.material.icons.filled.Save
import androidx.compose.material.icons.filled.Search
import androidx.compose.material.icons.filled.TableChart
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
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.compose.ui.window.Dialog
import androidx.compose.ui.window.DialogProperties
import androidx.core.content.FileProvider
import com.example.kenyatduhubtechers.data.model.PerformanceRecordDto
import com.example.kenyatduhubtechers.ui.theme.EduHubBackground
import com.example.kenyatduhubtechers.ui.theme.EduHubBorder
import com.example.kenyatduhubtechers.ui.theme.EduHubError
import com.example.kenyatduhubtechers.ui.theme.EduHubErrorContainer
import com.example.kenyatduhubtechers.ui.theme.EduHubOrange
import com.example.kenyatduhubtechers.ui.theme.EduHubSuccess
import com.example.kenyatduhubtechers.ui.theme.EduHubSuccessContainer
import com.example.kenyatduhubtechers.ui.theme.EduHubTextDark
import com.example.kenyatduhubtechers.ui.theme.EduHubTextMuted
import java.io.File
import java.util.Locale

@OptIn(ExperimentalLayoutApi::class, ExperimentalMaterial3Api::class)
@Composable
fun PerformanceScreen(
    viewModel: PerformanceViewModel,
    modifier: Modifier = Modifier
) {
    val uiState by viewModel.uiState.collectAsState()
    val context = LocalContext.current

    var selectedStreamId by remember { mutableStateOf<Int?>(null) }
    var selectedTerm by remember { mutableStateOf(uiState.currentTerm) }
    var selectedYear by remember { mutableStateOf("2026") }
    var selectedSubject by remember { mutableStateOf("") }
    var selectedExamTypeId by remember { mutableStateOf<Int?>(null) }

    // Launcher for selecting existing CSV files from phone
    val csvPickerLauncher = rememberLauncherForActivityResult(
        contract = ActivityResultContracts.GetContent()
    ) { uri: Uri? ->
        uri?.let { fileUri ->
            try {
                val inputStream = context.contentResolver.openInputStream(fileUri)
                val csvContent = inputStream?.bufferedReader()?.use { it.readText() } ?: ""
                if (csvContent.isNotBlank()) {
                    viewModel.parseAndOpenCsvFile(csvContent)
                } else {
                    Toast.makeText(context, "Selected CSV file is empty.", Toast.LENGTH_SHORT).show()
                }
            } catch (e: Exception) {
                Toast.makeText(context, "Failed to read CSV file: ${e.localizedMessage}", Toast.LENGTH_SHORT).show()
            }
        }
    }

    if (uiState.isLoading) {
        Box(
            modifier = modifier.fillMaxSize(),
            contentAlignment = Alignment.Center
        ) {
            CircularProgressIndicator(color = EduHubOrange)
        }
        return
    }

    val filteredRecords = remember(
        uiState.performanceRecords,
        uiState.searchQuery,
        uiState.termFilter,
        uiState.yearFilter,
        uiState.subjectFilter,
        uiState.examTypeFilter
    ) {
        uiState.performanceRecords.filter { record ->
            val query = uiState.searchQuery.lowercase().trim()
            val matchesSearch = query.isEmpty() ||
                    (record.admissionNumber?.lowercase()?.contains(query) == true) ||
                    ("${record.firstName ?: ""} ${record.lastName ?: ""}".lowercase().contains(query))

            val matchesTerm = uiState.termFilter.isEmpty() || record.term == uiState.termFilter
            val matchesYear = uiState.yearFilter.isEmpty() || record.year == uiState.yearFilter
            val matchesSubject = uiState.subjectFilter.isEmpty() || record.subject?.equals(uiState.subjectFilter, ignoreCase = true) == true
            val matchesExamType = uiState.examTypeFilter.isEmpty() || record.examTypeId?.toString() == uiState.examTypeFilter

            matchesSearch && matchesTerm && matchesYear && matchesSubject && matchesExamType
        }
    }

    Column(
        modifier = modifier
            .fillMaxSize()
            .verticalScroll(rememberScrollState())
    ) {
        Column(modifier = Modifier.padding(horizontal = 12.dp, vertical = 12.dp)) {
            Text(
                text = "Academic Performance",
                fontSize = 22.sp,
                fontWeight = FontWeight.Bold,
                color = EduHubTextDark,
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

            // 1. Built-In CSV Spreadsheet Performance Entry Card
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
                        verticalAlignment = Alignment.CenterVertically,
                        modifier = Modifier.padding(bottom = 8.dp)
                    ) {
                        Icon(
                            imageVector = Icons.Default.TableChart,
                            contentDescription = null,
                            tint = EduHubOrange,
                            modifier = Modifier.size(20.dp)
                        )
                        Spacer(modifier = Modifier.width(8.dp))
                        Text(
                            text = "CSV Performance Management",
                            fontSize = 16.sp,
                            fontWeight = FontWeight.Bold,
                            color = EduHubTextDark
                        )
                    }

                    Text(
                        text = "Record student performance via CSV. Use the built-in full-screen CSV spreadsheet editor or upload an existing CSV file.",
                        fontSize = 12.sp,
                        color = EduHubTextMuted,
                        modifier = Modifier.padding(bottom = 12.dp)
                    )

                    // Stream Selector Dropdown
                    val streamMap = remember(uiState.streams) {
                        uiState.streams.associate { (it.streamName ?: "Stream") to (it.id ?: 0) }
                    }
                    val currentStreamLabel = streamMap.entries.find { it.value == selectedStreamId }?.key ?: "All Streams"

                    FilterDropdown(
                        modifier = Modifier.fillMaxWidth(),
                        label = "Target Stream",
                        selectedOption = currentStreamLabel,
                        options = listOf("All Streams") + streamMap.keys.toList(),
                        onOptionSelected = {
                            val streamId = if (it == "All Streams") null else streamMap[it]
                            selectedStreamId = streamId
                        }
                    )

                    Spacer(modifier = Modifier.height(12.dp))

                    // Action Buttons
                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.spacedBy(8.dp)
                    ) {
                        Button(
                            onClick = {
                                viewModel.openBuiltInCsvEditor(selectedStreamId, selectedSubject)
                            },
                            modifier = Modifier.weight(1f),
                            shape = RoundedCornerShape(25.dp),
                            colors = ButtonDefaults.buttonColors(containerColor = EduHubOrange)
                        ) {
                            Icon(Icons.Default.Fullscreen, contentDescription = null, modifier = Modifier.size(20.dp))
                            Spacer(modifier = Modifier.width(6.dp))
                            Text("Full Screen CSV Editor", fontSize = 12.sp, fontWeight = FontWeight.Bold)
                        }

                        OutlinedButton(
                            onClick = { csvPickerLauncher.launch("*/*") },
                            modifier = Modifier.weight(1f),
                            shape = RoundedCornerShape(25.dp)
                        ) {
                            Icon(Icons.Default.CloudUpload, contentDescription = null, modifier = Modifier.size(16.dp))
                            Spacer(modifier = Modifier.width(4.dp))
                            Text("Open External CSV", fontSize = 12.sp)
                        }
                    }

                    if (uiState.isLoadingCsvEditor) {
                        Spacer(modifier = Modifier.height(12.dp))
                        Box(modifier = Modifier.fillMaxWidth(), contentAlignment = Alignment.Center) {
                            CircularProgressIndicator(color = EduHubOrange, modifier = Modifier.size(24.dp))
                        }
                    }
                }
            }

            // 2. Performance Statistics Summary Grid
            val totalCount = filteredRecords.size
            val avgScore = if (totalCount > 0) String.format(Locale.US, "%.1f", filteredRecords.map { it.marks }.average()) else "0.0"
            val highestScore = if (totalCount > 0) filteredRecords.maxOf { it.marks }.toString() else "0"
            val lowestScore = if (totalCount > 0) filteredRecords.minOf { it.marks }.toString() else "0"
            val passCount = filteredRecords.count { it.marks >= 50.0 }
            val passRate = if (totalCount > 0) (passCount * 100 / totalCount) else 0

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
                        label = "Total Records",
                        value = totalCount.toString(),
                        bgColor = Color(0xFFE8F0FE),
                        textColor = Color(0xFF1967D2)
                    )
                    MetricCard(
                        modifier = Modifier.weight(1f),
                        label = "Average Score",
                        value = avgScore,
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
                        label = "Highest Score",
                        value = highestScore,
                        bgColor = Color(0xFFFCE8E6),
                        textColor = EduHubError
                    )
                    MetricCard(
                        modifier = Modifier.weight(1f),
                        label = "Lowest Score",
                        value = lowestScore,
                        bgColor = Color(0xFFFEF7E0),
                        textColor = Color(0xFFB06000)
                    )
                    MetricCard(
                        modifier = Modifier.weight(1f),
                        label = "Pass Rate",
                        value = "$passRate%",
                        bgColor = Color(0xFFF1F3F4),
                        textColor = EduHubTextDark
                    )
                }
            }

            // 3. Filters Section
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
                        .padding(16.dp)
                ) {
                    Text(
                        text = "Filter Records",
                        fontSize = 16.sp,
                        fontWeight = FontWeight.Bold,
                        color = EduHubTextDark,
                        modifier = Modifier.padding(bottom = 12.dp)
                    )

                    // Search Input
                    OutlinedTextField(
                        value = uiState.searchQuery,
                        onValueChange = { viewModel.onSearchQueryChanged(it) },
                        modifier = Modifier.fillMaxWidth(),
                        placeholder = { Text("Search by student name or admission no", color = EduHubTextMuted, fontSize = 13.sp) },
                        leadingIcon = { Icon(Icons.Default.Search, contentDescription = null, tint = EduHubTextMuted) },
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

                    Spacer(modifier = Modifier.height(12.dp))

                    // Term & Subject Filters
                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.spacedBy(8.dp)
                    ) {
                        FilterDropdown(
                            modifier = Modifier.weight(1f),
                            label = "Term",
                            selectedOption = if (uiState.termFilter.isEmpty()) "All Terms" else uiState.termFilter,
                            options = listOf("All Terms") + uiState.terms,
                            onOptionSelected = {
                                viewModel.onTermFilterChanged(if (it == "All Terms") "" else it)
                            }
                        )

                        FilterDropdown(
                            modifier = Modifier.weight(1f),
                            label = "Subject",
                            selectedOption = if (uiState.subjectFilter.isEmpty()) "All Subjects" else uiState.subjectFilter,
                            options = listOf("All Subjects") + uiState.allSubjects.mapNotNull { it.subjectName },
                            onOptionSelected = {
                                viewModel.onSubjectFilterChanged(if (it == "All Subjects") "" else it)
                            }
                        )
                    }

                    Spacer(modifier = Modifier.height(8.dp))

                    // Year & Exam Type Filters
                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.spacedBy(8.dp)
                    ) {
                        FilterDropdown(
                            modifier = Modifier.weight(1f),
                            label = "Year",
                            selectedOption = if (uiState.yearFilter.isEmpty()) "All Years" else uiState.yearFilter,
                            options = listOf("All Years", "2024", "2025", "2026"),
                            onOptionSelected = {
                                viewModel.onYearFilterChanged(if (it == "All Years") "" else it)
                            }
                        )

                        val examTypeMap = remember(uiState.examTypes) {
                            uiState.examTypes.associate { (it.examTypeName ?: "Exam") to (it.id?.toString() ?: "") }
                        }
                        val currentExamTypeLabel = examTypeMap.entries.find { it.value == uiState.examTypeFilter }?.key ?: "All Exam Types"

                        FilterDropdown(
                            modifier = Modifier.weight(1f),
                            label = "Exam Type",
                            selectedOption = currentExamTypeLabel,
                            options = listOf("All Exam Types") + examTypeMap.keys.toList(),
                            onOptionSelected = {
                                val id = examTypeMap[it] ?: ""
                                viewModel.onExamTypeFilterChanged(id)
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
                        Icon(Icons.Default.Undo, contentDescription = null, modifier = Modifier.size(16.dp))
                        Spacer(modifier = Modifier.width(6.dp))
                        Text("Reset Filters", fontSize = 12.sp)
                    }
                }
            }

            Spacer(modifier = Modifier.height(16.dp))

            // 4. Subjects Without Performance Warning (if any)
            if (uiState.subjectsWithoutPerformance.isNotEmpty()) {
                Card(
                    modifier = Modifier.fillMaxWidth(),
                    colors = CardDefaults.cardColors(containerColor = Color(0xFFFFF3E0)),
                    border = BorderStroke(1.dp, EduHubOrange),
                    shape = RoundedCornerShape(8.dp),
                    elevation = CardDefaults.cardElevation(defaultElevation = 0.dp)
                ) {
                    Column(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(16.dp)
                    ) {
                        Row(verticalAlignment = Alignment.CenterVertically) {
                            Icon(Icons.Default.Warning, contentDescription = null, tint = EduHubOrange, modifier = Modifier.size(20.dp))
                            Spacer(modifier = Modifier.width(8.dp))
                            Text("Subjects Without Performance Records", fontSize = 14.sp, fontWeight = FontWeight.Bold, color = EduHubOrange)
                        }
                        Spacer(modifier = Modifier.height(6.dp))
                        Text("The following subjects have no performance records recorded yet:", fontSize = 12.sp, color = EduHubTextMuted)
                        Spacer(modifier = Modifier.height(8.dp))
                        FlowRow(horizontalArrangement = Arrangement.spacedBy(6.dp), verticalArrangement = Arrangement.spacedBy(6.dp)) {
                            uiState.subjectsWithoutPerformance.mapNotNull { it.subjectName }.forEach { subjectName ->
                                Box(
                                    modifier = Modifier
                                        .clip(RoundedCornerShape(12.dp))
                                        .background(Color(0xFFFCE8E6))
                                        .border(1.dp, EduHubError.copy(alpha = 0.4f), RoundedCornerShape(12.dp))
                                        .padding(horizontal = 10.dp, vertical = 4.dp)
                                ) {
                                    Text(text = subjectName, fontSize = 11.sp, fontWeight = FontWeight.Medium, color = EduHubError)
                                }
                            }
                        }
                    }
                }

                Spacer(modifier = Modifier.height(16.dp))
            }
        }

        // 5. Full 100% Screen-Width PDF Document Result Sheet View (Edge to Edge)
        PdfResultSheet(
            schoolName = uiState.teacher?.schoolName ?: "KENYA EDUHUB TEACHER PORTAL",
            filteredRecords = filteredRecords
        )

        Spacer(modifier = Modifier.height(24.dp))
    }

    // 6. Full-Screen Built-In CSV Sheet Editor Dialog
    if (uiState.showCsvEditorDialog) {
        BuiltInCsvSheetDialog(
            uiState = uiState,
            viewModel = viewModel,
            initialTerm = selectedTerm,
            initialYear = selectedYear,
            initialSubject = selectedSubject,
            initialExamTypeId = selectedExamTypeId,
            onDismiss = { viewModel.closeCsvEditor() }
        )
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
private fun BuiltInCsvSheetDialog(
    uiState: PerformanceUiState,
    viewModel: PerformanceViewModel,
    initialTerm: String,
    initialYear: String,
    initialSubject: String,
    initialExamTypeId: Int?,
    onDismiss: () -> Unit
) {
    val context = LocalContext.current

    var editorTerm by remember { mutableStateOf(if (initialTerm.isNotBlank()) initialTerm else uiState.currentTerm) }
    var editorYear by remember { mutableStateOf(if (initialYear.isNotBlank()) initialYear else "2026") }
    var editorSubject by remember { mutableStateOf(initialSubject) }
    var editorExamTypeId by remember { mutableStateOf(initialExamTypeId) }

    Dialog(
        onDismissRequest = onDismiss,
        properties = DialogProperties(
            usePlatformDefaultWidth = false,
            decorFitsSystemWindows = false
        )
    ) {
        Surface(
            modifier = Modifier.fillMaxSize(),
            shape = RoundedCornerShape(0.dp),
            color = Color.White
        ) {
            Column(
                modifier = Modifier
                    .fillMaxSize()
                    .padding(8.dp)
            ) {
                // Header Bar
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.SpaceBetween,
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    Row(verticalAlignment = Alignment.CenterVertically) {
                        Icon(Icons.Default.TableChart, contentDescription = null, tint = EduHubOrange, modifier = Modifier.size(24.dp))
                        Spacer(modifier = Modifier.width(8.dp))
                        Column {
                            Text("Full Screen CSV Sheet Editor", fontSize = 17.sp, fontWeight = FontWeight.Bold, color = EduHubTextDark)
                            Text("performance_template.csv • ${uiState.csvEditorRows.size} Pre-Populated Rows", fontSize = 11.sp, color = EduHubTextMuted)
                        }
                    }

                    IconButton(onClick = onDismiss) {
                        Icon(Icons.Default.Close, contentDescription = null, tint = EduHubTextDark)
                    }
                }

                Spacer(modifier = Modifier.height(6.dp))
                HorizontalDivider(color = EduHubBorder)
                Spacer(modifier = Modifier.height(6.dp))

                // Selector Bar: Term, Year, Exam Type, Subject
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.spacedBy(6.dp)
                ) {
                    FilterDropdown(
                        modifier = Modifier.weight(1f),
                        label = "Term",
                        selectedOption = editorTerm,
                        options = if (uiState.terms.isNotEmpty()) uiState.terms else listOf("Term 1", "Term 2", "Term 3"),
                        onOptionSelected = { editorTerm = it }
                    )

                    OutlinedTextField(
                        value = editorYear,
                        onValueChange = { editorYear = it },
                        label = { Text("Year", fontSize = 11.sp) },
                        modifier = Modifier.weight(0.8f),
                        singleLine = true,
                        keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Number),
                        shape = RoundedCornerShape(25.dp)
                    )

                    val examTypeMap = remember(uiState.examTypes) {
                        uiState.examTypes.associate { (it.examTypeName ?: "Exam") to (it.id ?: 0) }
                    }
                    val currentExamTypeLabel = examTypeMap.entries.find { it.value == editorExamTypeId }?.key ?: "Exam Type"

                    FilterDropdown(
                        modifier = Modifier.weight(1.2f),
                        label = "Exam Type",
                        selectedOption = currentExamTypeLabel,
                        options = examTypeMap.keys.toList(),
                        onOptionSelected = { editorExamTypeId = examTypeMap[it] }
                    )
                }

                Spacer(modifier = Modifier.height(6.dp))

                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.spacedBy(6.dp)
                ) {
                    val subjectList = uiState.allSubjects.mapNotNull { it.subjectName }
                    FilterDropdown(
                        modifier = Modifier.weight(1f),
                        label = "Select Subject",
                        selectedOption = if (editorSubject.isBlank()) "Select Subject" else editorSubject,
                        options = subjectList,
                        onOptionSelected = { editorSubject = it }
                    )
                }

                Spacer(modifier = Modifier.height(8.dp))

                // Full Screen Spreadsheet Table Grid
                Column(
                    modifier = Modifier
                        .weight(1f)
                        .fillMaxWidth()
                        .verticalScroll(rememberScrollState())
                        .border(1.dp, Color(0xFFC0C7D0), RoundedCornerShape(4.dp))
                ) {
                    // Table Header
                    Row(
                        modifier = Modifier
                            .fillMaxWidth()
                            .background(Color(0xFF2E3B4E))
                            .padding(vertical = 10.dp, horizontal = 4.dp),
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Text("#", fontSize = 11.sp, fontWeight = FontWeight.Bold, color = Color.White, modifier = Modifier.width(28.dp), textAlign = TextAlign.Center)
                        Text("ADM NO", fontSize = 11.sp, fontWeight = FontWeight.Bold, color = Color.White, modifier = Modifier.weight(1.2f))
                        Text("STUDENT NAME", fontSize = 11.sp, fontWeight = FontWeight.Bold, color = Color.White, modifier = Modifier.weight(2.2f))
                        Text("MARKS (0-100)", fontSize = 11.sp, fontWeight = FontWeight.Bold, color = Color.White, modifier = Modifier.weight(1.5f), textAlign = TextAlign.Center)
                        Text("GRADE & PTS", fontSize = 11.sp, fontWeight = FontWeight.Bold, color = Color.White, modifier = Modifier.weight(1.3f), textAlign = TextAlign.Center)
                    }

                    // Table Rows
                    uiState.csvEditorRows.forEachIndexed { index, row ->
                        val bg = if (index % 2 == 0) Color.White else Color(0xFFF6F8FA)
                        Row(
                            modifier = Modifier
                                .fillMaxWidth()
                                .background(bg)
                                .border(BorderStroke(0.5.dp, Color(0xFFD0D7DE)))
                                .padding(vertical = 4.dp, horizontal = 4.dp),
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            Text("${index + 1}", fontSize = 11.sp, fontWeight = FontWeight.Bold, color = Color.Gray, modifier = Modifier.width(28.dp), textAlign = TextAlign.Center)

                            Box(
                                modifier = Modifier
                                    .weight(1.2f)
                                    .clip(RoundedCornerShape(4.dp))
                                    .background(Color(0xFFF0F4F9))
                                    .padding(horizontal = 6.dp, vertical = 4.dp)
                            ) {
                                Text(row.admissionNumber, fontSize = 11.sp, fontWeight = FontWeight.Bold, color = EduHubTextDark)
                            }

                            Spacer(modifier = Modifier.width(4.dp))

                            Text(row.studentName, fontSize = 11.sp, fontWeight = FontWeight.Medium, color = EduHubTextDark, modifier = Modifier.weight(2.2f))

                            OutlinedTextField(
                                value = row.marks,
                                onValueChange = { viewModel.updateCsvRowMark(row.admissionNumber, it, editorSubject) },
                                modifier = Modifier
                                    .weight(1.5f)
                                    .height(48.dp),
                                singleLine = true,
                                keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Number),
                                shape = RoundedCornerShape(8.dp),
                                colors = OutlinedTextFieldDefaults.colors(
                                    focusedBorderColor = EduHubOrange,
                                    unfocusedBorderColor = EduHubBorder,
                                    focusedContainerColor = Color.White,
                                    unfocusedContainerColor = Color.White
                                )
                            )

                            Spacer(modifier = Modifier.width(4.dp))

                            Box(
                                modifier = Modifier
                                    .weight(1.3f)
                                    .clip(RoundedCornerShape(6.dp))
                                    .background(if (row.grade.isNotBlank()) Color(0xFFE8F0FE) else Color(0xFFF1F3F4))
                                    .padding(horizontal = 4.dp, vertical = 4.dp),
                                contentAlignment = Alignment.Center
                            ) {
                                Text(
                                    text = if (row.grade.isNotBlank()) "${row.grade} (${row.points}p)" else "-",
                                    fontSize = 11.sp,
                                    fontWeight = FontWeight.Bold,
                                    color = if (row.grade.isNotBlank()) Color(0xFF1967D2) else Color.Gray,
                                    textAlign = TextAlign.Center
                                )
                            }
                        }
                    }
                }

                Spacer(modifier = Modifier.height(10.dp))

                // Bottom Action Bar
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.spacedBy(8.dp)
                ) {
                    OutlinedButton(
                        onClick = {
                            val csvStr = viewModel.generateCsvContentFromEditor()
                            downloadAndShareCsvTemplate(context, csvStr)
                        },
                        modifier = Modifier.weight(1f),
                        shape = RoundedCornerShape(25.dp)
                    ) {
                        Icon(Icons.Default.Download, contentDescription = null, modifier = Modifier.size(16.dp))
                        Spacer(modifier = Modifier.width(4.dp))
                        Text("Export CSV File", fontSize = 12.sp)
                    }

                    Button(
                        onClick = {
                            if (editorExamTypeId != null && editorSubject.isNotBlank()) {
                                viewModel.submitCsvEditorRecords(
                                    term = editorTerm,
                                    year = editorYear,
                                    examTypeId = editorExamTypeId!!,
                                    subject = editorSubject
                                )
                            } else {
                                Toast.makeText(context, "Please select Exam Type and Subject before submitting.", Toast.LENGTH_LONG).show()
                            }
                        },
                        enabled = !uiState.isSaving,
                        modifier = Modifier.weight(1.3f),
                        shape = RoundedCornerShape(25.dp),
                        colors = ButtonDefaults.buttonColors(containerColor = EduHubSuccess)
                    ) {
                        if (uiState.isSaving) {
                            CircularProgressIndicator(color = Color.White, modifier = Modifier.size(20.dp))
                        } else {
                            Icon(Icons.Default.Save, contentDescription = null, modifier = Modifier.size(18.dp))
                            Spacer(modifier = Modifier.width(6.dp))
                            Text("Save & Upload Performance", fontSize = 12.sp, fontWeight = FontWeight.Bold)
                        }
                    }
                }
            }
        }
    }
}

@Composable
private fun PdfResultSheet(
    schoolName: String,
    filteredRecords: List<PerformanceRecordDto>
) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(containerColor = Color.White),
        border = BorderStroke(1.5.dp, Color(0xFF1E1E1E)),
        shape = RoundedCornerShape(0.dp),
        elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)
    ) {
        Column(
            modifier = Modifier
                .fillMaxWidth()
                .padding(vertical = 12.dp, horizontal = 4.dp)
        ) {
            // PDF Official Header Banner
            Column(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(horizontal = 8.dp),
                horizontalAlignment = Alignment.CenterHorizontally
            ) {
                Text(
                    text = schoolName.uppercase(),
                    fontSize = 16.sp,
                    fontWeight = FontWeight.Bold,
                    color = Color.Black,
                    fontFamily = FontFamily.Serif,
                    textAlign = TextAlign.Center
                )
                Text(
                    text = "ACADEMIC PERFORMANCE REPORT SHEET",
                    fontSize = 13.sp,
                    fontWeight = FontWeight.Bold,
                    color = EduHubOrange,
                    fontFamily = FontFamily.Serif,
                    textAlign = TextAlign.Center
                )
                Text(
                    text = "OFFICIAL ACADEMIC EVALUATION & RESULT TRANSCRIPT",
                    fontSize = 10.sp,
                    color = Color.DarkGray,
                    fontFamily = FontFamily.Serif,
                    textAlign = TextAlign.Center
                )

                Spacer(modifier = Modifier.height(8.dp))
                HorizontalDivider(color = Color.Black, thickness = 1.5.dp)
                Spacer(modifier = Modifier.height(2.dp))
                HorizontalDivider(color = EduHubOrange, thickness = 1.dp)
                Spacer(modifier = Modifier.height(12.dp))
            }

            if (filteredRecords.isEmpty()) {
                Text(
                    text = "NO PERFORMANCE RECORDS AVAILABLE FOR THIS REPORT",
                    fontSize = 12.sp,
                    fontWeight = FontWeight.Bold,
                    color = Color.Red,
                    fontFamily = FontFamily.Serif,
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(vertical = 16.dp),
                    textAlign = TextAlign.Center
                )
            } else {
                val groupedRecords = remember(filteredRecords) {
                    filteredRecords.groupBy { record ->
                        val subject = record.subject ?: "General Subject"
                        val examType = record.examTypeName
                        if (!examType.isNull_or_empty()) {
                            "$subject • $examType"
                        } else {
                            subject
                        }
                    }.toSortedMap()
                }

                groupedRecords.entries.forEachIndexed { groupIndex, entry ->
                    val groupTitle = entry.key
                    val recordsInGroup = entry.value.sortedByDescending { it.marks }

                    Box(
                        modifier = Modifier
                            .fillMaxWidth()
                            .background(Color(0xFF333333))
                            .padding(vertical = 6.dp, horizontal = 8.dp)
                    ) {
                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            horizontalArrangement = Arrangement.SpaceBetween,
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            Text(
                                text = groupTitle.uppercase(),
                                fontSize = 12.sp,
                                fontWeight = FontWeight.Bold,
                                color = Color.White,
                                fontFamily = FontFamily.Serif
                            )
                            Text(
                                text = "${recordsInGroup.size} CANDIDATES",
                                fontSize = 10.sp,
                                fontWeight = FontWeight.Bold,
                                color = Color(0xFFFFC107),
                                fontFamily = FontFamily.Serif
                            )
                        }
                    }

                    // 100% Screen Width Table Header
                    Row(
                        modifier = Modifier
                            .fillMaxWidth()
                            .background(Color(0xFFE8ECEF))
                            .border(BorderStroke(1.dp, Color.Black))
                            .padding(vertical = 8.dp, horizontal = 2.dp),
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Text("RANK", fontSize = 10.sp, fontWeight = FontWeight.Bold, fontFamily = FontFamily.Serif, modifier = Modifier.weight(0.7f), textAlign = TextAlign.Center)
                        Text("CANDIDATE / ADM", fontSize = 10.sp, fontWeight = FontWeight.Bold, fontFamily = FontFamily.Serif, modifier = Modifier.weight(2.3f))
                        Text("CLASS/STREAM", fontSize = 10.sp, fontWeight = FontWeight.Bold, fontFamily = FontFamily.Serif, modifier = Modifier.weight(1.3f), textAlign = TextAlign.Center)
                        Text("MARKS", fontSize = 10.sp, fontWeight = FontWeight.Bold, fontFamily = FontFamily.Serif, modifier = Modifier.weight(1.1f), textAlign = TextAlign.Center)
                        Text("GRADE (PTS)", fontSize = 10.sp, fontWeight = FontWeight.Bold, fontFamily = FontFamily.Serif, modifier = Modifier.weight(1.4f), textAlign = TextAlign.Center)
                    }

                    // 100% Screen Width Table Rows
                    recordsInGroup.forEachIndexed { rankIndex, record ->
                        val rowBg = if (rankIndex % 2 == 0) Color.White else Color(0xFFF9FAFC)
                        Row(
                            modifier = Modifier
                                .fillMaxWidth()
                                .background(rowBg)
                                .border(BorderStroke(0.5.dp, Color(0xFF333333)))
                                .padding(vertical = 8.dp, horizontal = 2.dp),
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            Text(
                                text = "#${rankIndex + 1}",
                                fontSize = 11.sp,
                                fontWeight = FontWeight.Bold,
                                fontFamily = FontFamily.Serif,
                                color = if (rankIndex < 3) EduHubOrange else Color.Black,
                                modifier = Modifier.weight(0.7f),
                                textAlign = TextAlign.Center
                            )

                            Column(modifier = Modifier.weight(2.3f)) {
                                Text(
                                    text = "${record.firstName ?: ""} ${record.lastName ?: ""}".ifBlank { "Candidate" }.uppercase(),
                                    fontSize = 11.sp,
                                    fontWeight = FontWeight.Bold,
                                    fontFamily = FontFamily.Serif,
                                    color = Color.Black
                                )
                                Text(
                                    text = "ADM: ${record.admissionNumber ?: "N/A"}",
                                    fontSize = 9.sp,
                                    fontFamily = FontFamily.Serif,
                                    color = Color.DarkGray
                                )
                            }

                            Text(
                                text = "${record.className ?: "-"}\n${record.streamName ?: ""}".trim(),
                                fontSize = 10.sp,
                                fontFamily = FontFamily.Serif,
                                color = Color.Black,
                                modifier = Modifier.weight(1.3f),
                                textAlign = TextAlign.Center
                            )

                            Text(
                                text = "${record.marks}",
                                fontSize = 12.sp,
                                fontWeight = FontWeight.Bold,
                                fontFamily = FontFamily.Serif,
                                color = Color.Black,
                                modifier = Modifier.weight(1.1f),
                                textAlign = TextAlign.Center
                            )

                            Box(
                                modifier = Modifier
                                    .weight(1.4f)
                                    .clip(RoundedCornerShape(4.dp))
                                    .background(Color(0xFFE8F0FE))
                                    .padding(horizontal = 4.dp, vertical = 2.dp),
                                contentAlignment = Alignment.Center
                            ) {
                                Text(
                                    text = "${record.displayGrade} (${record.displayPoints} pts)",
                                    fontSize = 10.sp,
                                    fontWeight = FontWeight.Bold,
                                    fontFamily = FontFamily.Serif,
                                    color = Color(0xFF1967D2),
                                    textAlign = TextAlign.Center
                                )
                            }
                        }
                    }

                    Spacer(modifier = Modifier.height(12.dp))
                }

                Spacer(modifier = Modifier.height(8.dp))
                HorizontalDivider(color = Color.Black, thickness = 1.dp)
                Spacer(modifier = Modifier.height(4.dp))
                Row(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(horizontal = 8.dp),
                    horizontalArrangement = Arrangement.SpaceBetween
                ) {
                    Text("KENYA EDUHUB TEACHER PORTAL", fontSize = 9.sp, fontFamily = FontFamily.Serif, color = Color.Gray)
                    Text("CONFIDENTIAL OFFICIAL RESULT SHEET", fontSize = 9.sp, fontFamily = FontFamily.Serif, color = Color.Gray)
                }
            }
        }
    }
}

private fun downloadAndShareCsvTemplate(context: Context, csvContent: String) {
    try {
        val fileName = "performance_template_${System.currentTimeMillis()}.csv"

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

        val cacheFile = File(context.cacheDir, "performance_template.csv")
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

        context.startActivity(Intent.createChooser(shareIntent, "Download & Share Performance Template"))
        Toast.makeText(context, "Template saved to Downloads folder!", Toast.LENGTH_LONG).show()
    } catch (e: Exception) {
        Toast.makeText(context, "Error downloading template: ${e.localizedMessage}", Toast.LENGTH_SHORT).show()
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

private fun String?.isNull_or_empty(): Boolean = this == null || this.trim().isEmpty()
