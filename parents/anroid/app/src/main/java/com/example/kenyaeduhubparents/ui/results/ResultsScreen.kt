package com.example.kenyaeduhubparents.ui.results

import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Refresh
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
import com.example.kenyaeduhubparents.data.model.AggregateDistribution
import com.example.kenyaeduhubparents.data.model.GradingScale
import com.example.kenyaeduhubparents.data.model.ResultPerformanceRecord

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun ResultsScreen(
    modifier: Modifier = Modifier,
    viewModel: ResultsViewModel = viewModel(
        factory = ResultsViewModel.Factory(LocalContext.current)
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
        // Page Title
        Column(
            modifier = Modifier.fillMaxWidth(),
            horizontalAlignment = Alignment.CenterHorizontally,
            verticalArrangement = Arrangement.spacedBy(4.dp)
        ) {
            Text(
                text = "Student Results",
                style = MaterialTheme.typography.headlineMedium,
                fontWeight = FontWeight.Bold,
                color = MaterialTheme.colorScheme.onBackground
            )
            Text(
                text = "View and filter student examination results",
                style = MaterialTheme.typography.bodyMedium,
                color = MaterialTheme.colorScheme.onSurfaceVariant,
                textAlign = TextAlign.Center
            )
        }

        when (val state = uiState) {
            is ResultsUiState.Loading -> {
                Box(
                    modifier = Modifier
                        .fillMaxWidth()
                        .height(250.dp),
                    contentAlignment = Alignment.Center
                ) {
                    CircularProgressIndicator(color = MaterialTheme.colorScheme.primary)
                }
            }
            is ResultsUiState.Error -> {
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
                        OutlinedButton(onClick = { viewModel.loadResults() }) {
                            Text("Retry")
                        }
                    }
                }
            }
            is ResultsUiState.Success -> {
                val data = state.data
                val children = data.children
                val terms = data.terms.ifEmpty { listOf("Term 1", "Term 2", "Term 3") }
                val currentYear = data.currentYear
                val years = (currentYear downTo currentYear - 5).toList()
                val gradingScales = data.gradingScales
                val aggregateDistribution = data.aggregateDistribution
                val subjectAssignments = data.studentSubjectAssignments
                val minSubjects = data.schoolSettings?.minSubjects ?: 7

                // Filter Card
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
                            text = "Filter Results",
                            style = MaterialTheme.typography.titleMedium,
                            fontWeight = FontWeight.Bold,
                            color = MaterialTheme.colorScheme.onSurface
                        )

                        // Child Filter Dropdown
                        var childExpanded by remember { mutableStateOf(false) }
                        val selectedChildName = children.find { it.id.toString() == viewModel.selectedChildId }?.name ?: "All Children"

                        ExposedDropdownMenuBox(
                            expanded = childExpanded,
                            onExpandedChange = { childExpanded = !childExpanded }
                        ) {
                            OutlinedTextField(
                                value = selectedChildName,
                                onValueChange = {},
                                readOnly = true,
                                label = { Text("Select Child") },
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
                                DropdownMenuItem(
                                    text = { Text("All Children") },
                                    onClick = {
                                        viewModel.updateFilters(null, viewModel.selectedTerm, viewModel.selectedYear, viewModel.selectedExamType)
                                        childExpanded = false
                                    }
                                )
                                children.forEach { child ->
                                    DropdownMenuItem(
                                        text = { Text(child.name) },
                                        onClick = {
                                            viewModel.updateFilters(child.id.toString(), viewModel.selectedTerm, viewModel.selectedYear, viewModel.selectedExamType)
                                            childExpanded = false
                                        }
                                    )
                                }
                            }
                        }

                        // Term Filter Dropdown
                        var termExpanded by remember { mutableStateOf(false) }
                        val selectedTermVal = viewModel.selectedTerm ?: "All Terms"

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
                                DropdownMenuItem(
                                    text = { Text("All Terms") },
                                    onClick = {
                                        viewModel.updateFilters(viewModel.selectedChildId, null, viewModel.selectedYear, viewModel.selectedExamType)
                                        termExpanded = false
                                    }
                                )
                                terms.forEach { term ->
                                    DropdownMenuItem(
                                        text = { Text(term) },
                                        onClick = {
                                            viewModel.updateFilters(viewModel.selectedChildId, term, viewModel.selectedYear, viewModel.selectedExamType)
                                            termExpanded = false
                                        }
                                    )
                                }
                            }
                        }

                        // Year Filter Dropdown
                        var yearExpanded by remember { mutableStateOf(false) }
                        val selectedYearVal = viewModel.selectedYear ?: "All Years"

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
                                DropdownMenuItem(
                                    text = { Text("All Years") },
                                    onClick = {
                                        viewModel.updateFilters(viewModel.selectedChildId, viewModel.selectedTerm, null, viewModel.selectedExamType)
                                        yearExpanded = false
                                    }
                                )
                                years.forEach { year ->
                                    DropdownMenuItem(
                                        text = { Text(year.toString()) },
                                        onClick = {
                                            viewModel.updateFilters(viewModel.selectedChildId, viewModel.selectedTerm, year.toString(), viewModel.selectedExamType)
                                            yearExpanded = false
                                        }
                                    )
                                }
                            }
                        }

                        // Load Results Button
                        Button(
                            onClick = { viewModel.loadResults() },
                            modifier = Modifier
                                .fillMaxWidth()
                                .height(48.dp),
                            shape = RoundedCornerShape(25.dp),
                            colors = ButtonDefaults.buttonColors(containerColor = MaterialTheme.colorScheme.primary)
                        ) {
                            Icon(imageVector = Icons.Filled.Search, contentDescription = null, tint = Color.White)
                            Spacer(modifier = Modifier.width(8.dp))
                            Text("Load Results", fontWeight = FontWeight.Bold, color = Color.White)
                        }

                        OutlinedButton(
                            onClick = {
                                viewModel.updateFilters(null, null, null, null)
                                viewModel.loadResults()
                            },
                            modifier = Modifier
                                .fillMaxWidth()
                                .height(48.dp),
                            shape = RoundedCornerShape(25.dp)
                        ) {
                            Icon(imageVector = Icons.Filled.Refresh, contentDescription = null)
                            Spacer(modifier = Modifier.width(8.dp))
                            Text("Reset Filters")
                        }
                    }
                }

                // Results Container
                val perfData = data.classPerformanceData
                if (perfData.isEmpty()) {
                    Card(
                        modifier = Modifier.fillMaxWidth(),
                        shape = RoundedCornerShape(16.dp),
                        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface)
                    ) {
                        Text(
                            text = "Select filters and click \"Load Results\" to view student results",
                            style = MaterialTheme.typography.bodyMedium,
                            color = MaterialTheme.colorScheme.onSurfaceVariant,
                            modifier = Modifier.padding(24.dp),
                            textAlign = TextAlign.Center
                        )
                    }
                } else {
                    // Filter and group records per child and exam type
                    val studentExamReports = mutableListOf<StudentExamReport>()

                    for ((studentId, wrapper) in perfData) {
                        val student = wrapper.student ?: continue
                        val records = wrapper.performance

                        if (viewModel.selectedChildId != null && student.id.toString() != viewModel.selectedChildId) {
                            continue
                        }

                        val filteredRecords = records.filter { rec ->
                            val matchTerm = viewModel.selectedTerm == null || rec.term.trim().equals(viewModel.selectedTerm!!.trim(), ignoreCase = true)
                            val matchYear = viewModel.selectedYear == null || rec.year.toString().trim() == viewModel.selectedYear!!.trim()
                            matchTerm && matchYear
                        }

                        val admNumber = student.admissionNumber
                        val assignedSubjects = subjectAssignments[admNumber] ?: emptyList()
                        val assignedSubjectNames = assignedSubjects.map { it.subjectName }

                        // Only include assigned subjects for this student
                        val assignedFilteredRecords = filteredRecords.filter { assignedSubjectNames.contains(it.subject) }

                        val groupedByExam = assignedFilteredRecords.groupBy { it.examType }
                        for ((examType, examRecords) in groupedByExam) {
                            studentExamReports.add(
                                StudentExamReport(
                                    studentName = student.name,
                                    admissionNumber = admNumber,
                                    className = student.className,
                                    streamName = student.streamName,
                                    examType = examType,
                                    term = examRecords.firstOrNull()?.term ?: "",
                                    year = examRecords.firstOrNull()?.year?.toString() ?: "",
                                    records = examRecords,
                                    assignedSubjects = assignedSubjects,
                                    gradingScales = gradingScales,
                                    aggregateDistribution = aggregateDistribution,
                                    minSubjects = minSubjects
                                )
                            )
                        }
                    }

                    if (studentExamReports.isEmpty()) {
                        Card(
                            modifier = Modifier.fillMaxWidth(),
                            shape = RoundedCornerShape(16.dp),
                            colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface)
                        ) {
                            Text(
                                text = "No results found for the selected filters.",
                                style = MaterialTheme.typography.bodyMedium,
                                color = MaterialTheme.colorScheme.onSurfaceVariant,
                                modifier = Modifier.padding(24.dp),
                                textAlign = TextAlign.Center
                            )
                        }
                    } else {
                        studentExamReports.forEach { report ->
                            StudentReportCard(report = report)
                        }
                    }
                }
            }
        }
    }
}

data class StudentExamReport(
    val studentName: String,
    val admissionNumber: String,
    val className: String,
    val streamName: String,
    val examType: String,
    val term: String,
    val year: String,
    val records: List<ResultPerformanceRecord>,
    val assignedSubjects: List<com.example.kenyaeduhubparents.data.model.SubjectAssignment>,
    val gradingScales: List<GradingScale>,
    val aggregateDistribution: List<AggregateDistribution>,
    val minSubjects: Int
) {
    val subjectPointsList: List<SubjectPointData>
        get() {
            val recordMap = records.associateBy { it.subject }
            return assignedSubjects.map { assigned ->
                val record = recordMap[assigned.subjectName]
                val marks = record?.marks ?: 0.0
                val grade = record?.grade ?: "-"
                val points = getPointsFromMarks(marks, assigned.subjectName, gradingScales)
                SubjectPointData(
                    subjectName = assigned.subjectName,
                    marks = marks,
                    grade = grade,
                    points = points,
                    isCompulsory = assigned.isCompulsory == 1,
                    hasRecord = record != null
                )
            }
        }

    val gradingSubjects: List<SubjectPointData>
        get() {
            val allWithPoints = subjectPointsList.filter { it.hasRecord }
            val compulsory = allWithPoints.filter { it.isCompulsory }
            val nonCompulsory = allWithPoints.filter { !it.isCompulsory }
                .sortedWith(compareByDescending<SubjectPointData> { it.points }.thenByDescending { it.marks })

            val neededNonCompulsory = maxOf(0, minSubjects - compulsory.size)
            val selectedNonCompulsory = nonCompulsory.take(neededNonCompulsory)
            return compulsory + selectedNonCompulsory
        }

    val totalMarks: Double
        get() = gradingSubjects.sumOf { it.marks }

    val totalPoints: Int
        get() = gradingSubjects.sumOf { it.points }

    val average: Double
        get() = if (gradingSubjects.isNotEmpty()) totalMarks / gradingSubjects.size else 0.0

    val aggregateGrade: String
        get() = getAggregateGrade(totalPoints, aggregateDistribution)
}

data class SubjectPointData(
    val subjectName: String,
    val marks: Double,
    val grade: String,
    val points: Int,
    val isCompulsory: Boolean,
    val hasRecord: Boolean
)

fun getPointsFromMarks(marks: Double, subject: String, gradingScales: List<GradingScale>): Int {
    val upperSubject = subject.uppercase()
    val subjectScales = gradingScales.filter { it.subjectName?.uppercase() == upperSubject }
    for (scale in subjectScales) {
        if (marks >= scale.minScore && marks <= scale.maxScore) {
            return scale.points
        }
    }
    val generalScales = gradingScales.filter { it.subjectId == null }
    for (scale in generalScales) {
        if (marks >= scale.minScore && marks <= scale.maxScore) {
            return scale.points
        }
    }
    for (scale in gradingScales) {
        if (marks >= scale.minScore && marks <= scale.maxScore) {
            return scale.points
        }
    }
    return 0
}

fun getAggregateGrade(totalPoints: Int, aggregateDistribution: List<AggregateDistribution>): String {
    if (aggregateDistribution.isEmpty()) return "-"
    for (dist in aggregateDistribution) {
        if (totalPoints >= dist.minPoints && totalPoints <= dist.maxPoints) {
            return dist.gradeName
        }
    }
    return "-"
}

@Composable
fun StudentReportCard(report: StudentExamReport) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(12.dp),
        colors = CardDefaults.cardColors(containerColor = Color.White),
        elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)
    ) {
        Column(
            modifier = Modifier
                .fillMaxWidth()
                .border(2.dp, Color.Black, RoundedCornerShape(12.dp))
                .padding(16.dp),
            verticalArrangement = Arrangement.spacedBy(14.dp)
        ) {
            // Header
            Column(
                modifier = Modifier.fillMaxWidth(),
                horizontalAlignment = Alignment.CenterHorizontally,
                verticalArrangement = Arrangement.spacedBy(4.dp)
            ) {
                Text(
                    text = "STUDENT RESULT REPORT",
                    fontWeight = FontWeight.Bold,
                    fontSize = 16.sp,
                    color = Color.Black
                )
                Text(
                    text = report.studentName,
                    fontWeight = FontWeight.SemiBold,
                    fontSize = 14.sp,
                    color = Color.DarkGray
                )
                Text(
                    text = "Adm: ${report.admissionNumber} | Class: ${report.className} (${report.streamName})",
                    fontSize = 12.sp,
                    color = Color.Gray
                )
                Text(
                    text = "Exam: ${report.examType} | Term: ${report.term} | Year: ${report.year}",
                    fontSize = 12.sp,
                    color = Color.Gray
                )
            }

            HorizontalDivider(thickness = 1.5.dp, color = Color.DarkGray)

            // Table of Assigned Subjects
            Column(verticalArrangement = Arrangement.spacedBy(6.dp)) {
                // Table header
                Row(
                    modifier = Modifier
                        .fillMaxWidth()
                        .background(Color(0xFFF0F0F0))
                        .border(1.dp, Color.Black)
                        .padding(8.dp),
                    horizontalArrangement = Arrangement.SpaceBetween
                ) {
                    Text(text = "Subject", fontWeight = FontWeight.Bold, fontSize = 12.sp, modifier = Modifier.weight(2f))
                    Text(text = "Marks", fontWeight = FontWeight.Bold, fontSize = 12.sp, modifier = Modifier.weight(1f), textAlign = TextAlign.Center)
                    Text(text = "Grade", fontWeight = FontWeight.Bold, fontSize = 12.sp, modifier = Modifier.weight(1f), textAlign = TextAlign.Center)
                    Text(text = "Pts", fontWeight = FontWeight.Bold, fontSize = 12.sp, modifier = Modifier.weight(0.8f), textAlign = TextAlign.Center)
                    Text(text = "Grading", fontWeight = FontWeight.Bold, fontSize = 12.sp, modifier = Modifier.weight(0.8f), textAlign = TextAlign.Center)
                }

                val gradingNames = report.gradingSubjects.map { it.subjectName }
                report.subjectPointsList.forEach { sub ->
                    val isGraded = gradingNames.contains(sub.subjectName)
                    Row(
                        modifier = Modifier
                            .fillMaxWidth()
                            .border(1.dp, Color.LightGray)
                            .padding(8.dp),
                        horizontalArrangement = Arrangement.SpaceBetween,
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Text(text = sub.subjectName, fontSize = 12.sp, color = Color.Black, modifier = Modifier.weight(2f))
                        Text(text = if (sub.hasRecord) String.format("%.1f", sub.marks) else "-", fontSize = 12.sp, color = Color.Black, modifier = Modifier.weight(1f), textAlign = TextAlign.Center)
                        Text(text = if (sub.hasRecord) sub.grade else "-", fontWeight = FontWeight.Bold, fontSize = 12.sp, color = Color.Black, modifier = Modifier.weight(1f), textAlign = TextAlign.Center)
                        Text(text = if (sub.hasRecord) sub.points.toString() else "-", fontSize = 12.sp, color = Color.Black, modifier = Modifier.weight(0.8f), textAlign = TextAlign.Center)
                        Text(text = if (isGraded) "✓" else "-", fontWeight = FontWeight.Bold, fontSize = 14.sp, color = if (isGraded) Color(0xFF137333) else Color.Gray, modifier = Modifier.weight(0.8f), textAlign = TextAlign.Center)
                    }
                }
            }

            // Summary Footer
            HorizontalDivider(color = Color.LightGray)
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween
            ) {
                Column(horizontalAlignment = Alignment.CenterHorizontally) {
                    Text(text = "Total Marks", fontSize = 10.sp, color = Color.Gray)
                    Text(text = String.format("%.1f", report.totalMarks), fontWeight = FontWeight.Bold, fontSize = 13.sp, color = Color.Black)
                }
                Column(horizontalAlignment = Alignment.CenterHorizontally) {
                    Text(text = "Average", fontSize = 10.sp, color = Color.Gray)
                    Text(text = String.format("%.1f", report.average), fontWeight = FontWeight.Bold, fontSize = 13.sp, color = Color.Black)
                }
                Column(horizontalAlignment = Alignment.CenterHorizontally) {
                    Text(text = "Total Points", fontSize = 10.sp, color = Color.Gray)
                    Text(text = report.totalPoints.toString(), fontWeight = FontWeight.Bold, fontSize = 13.sp, color = Color.Black)
                }
                Column(horizontalAlignment = Alignment.CenterHorizontally) {
                    Text(text = "Grade", fontSize = 10.sp, color = Color.Gray)
                    Text(text = report.aggregateGrade, fontWeight = FontWeight.Bold, fontSize = 13.sp, color = Color(0xFF137333))
                }
            }
        }
    }
}
