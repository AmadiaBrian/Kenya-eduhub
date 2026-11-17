package com.example.kenyatduhubtechers.ui.results

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
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Close
import androidx.compose.material.icons.filled.Info
import androidx.compose.material.icons.filled.Search
import androidx.compose.material.icons.filled.Undo
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
import androidx.compose.ui.text.font.FontFamily
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.example.kenyatduhubtechers.data.model.AggregateDistributionDto
import com.example.kenyatduhubtechers.data.model.GradingScaleDto
import com.example.kenyatduhubtechers.data.model.PerformanceRecordDto
import com.example.kenyatduhubtechers.data.model.StudentSubjectAssignmentDto
import androidx.compose.material3.pulltorefresh.PullToRefreshBox
import com.example.kenyatduhubtechers.ui.theme.EduHubBackground
import com.example.kenyatduhubtechers.ui.theme.EduHubBorder
import com.example.kenyatduhubtechers.ui.theme.EduHubOrange
import com.example.kenyatduhubtechers.ui.theme.EduHubTextDark
import com.example.kenyatduhubtechers.ui.theme.EduHubTextMuted
import java.util.Locale

@OptIn(ExperimentalLayoutApi::class, ExperimentalMaterial3Api::class)
@Composable
fun ResultsScreen(
    viewModel: ResultsViewModel,
    modifier: Modifier = Modifier
) {
    val uiState by viewModel.uiState.collectAsState()

    PullToRefreshBox(
        isRefreshing = uiState.isLoading,
        onRefresh = { viewModel.loadResultsData() },
        modifier = modifier.fillMaxSize()
    ) {
        if (uiState.isLoading && uiState.performanceRecords.isEmpty() && uiState.examTypes.isEmpty()) {
            Box(
                modifier = Modifier.fillMaxSize(),
                contentAlignment = Alignment.Center
            ) {
                CircularProgressIndicator(color = EduHubOrange)
            }
        } else {

    // Filter performance records
    val filteredRecords = remember(
        uiState.performanceRecords,
        uiState.classFilter,
        uiState.streamFilter,
        uiState.termFilter,
        uiState.yearFilter,
        uiState.examTypeFilter
    ) {
        uiState.performanceRecords.filter { record ->
            val matchesClass = uiState.classFilter.isEmpty() || record.className == uiState.classFilter
            val matchesStream = uiState.streamFilter.isEmpty() || record.streamName == uiState.streamFilter
            val matchesTerm = uiState.termFilter.isEmpty() || record.term == uiState.termFilter
            val matchesYear = uiState.yearFilter.isEmpty() || record.year == uiState.yearFilter
            val matchesExamType = uiState.examTypeFilter.isEmpty() || record.examTypeName == uiState.examTypeFilter

            matchesClass && matchesStream && matchesTerm && matchesYear && matchesExamType
        }
    }

    Column(
        modifier = modifier
            .fillMaxSize()
            .verticalScroll(rememberScrollState())
    ) {
        Column(modifier = Modifier.padding(horizontal = 12.dp, vertical = 12.dp)) {
            Text(
                text = "Student Results",
                fontSize = 22.sp,
                fontWeight = FontWeight.Bold,
                color = EduHubTextDark,
                modifier = Modifier.padding(bottom = 16.dp)
            )

            // 1. Filter Section
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
                        text = "Filter Results",
                        fontSize = 16.sp,
                        fontWeight = FontWeight.Bold,
                        color = EduHubTextDark,
                        modifier = Modifier.padding(bottom = 12.dp)
                    )

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
                            label = "Term",
                            selectedOption = if (uiState.termFilter.isEmpty()) "All Terms" else uiState.termFilter,
                            options = listOf("All Terms") + uiState.terms,
                            onOptionSelected = {
                                viewModel.onTermFilterChanged(if (it == "All Terms") "" else it)
                            }
                        )

                        FilterDropdown(
                            modifier = Modifier.weight(1f),
                            label = "Year",
                            selectedOption = if (uiState.yearFilter.isEmpty()) "All Years" else uiState.yearFilter,
                            options = listOf("All Years", "2024", "2025", "2026"),
                            onOptionSelected = {
                                viewModel.onYearFilterChanged(if (it == "All Years") "" else it)
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
                            label = "Exam Type",
                            selectedOption = if (uiState.examTypeFilter.isEmpty()) "All Exam Types" else uiState.examTypeFilter,
                            options = listOf("All Exam Types") + uiState.examTypes.mapNotNull { it.examTypeName },
                            onOptionSelected = {
                                viewModel.onExamTypeFilterChanged(if (it == "All Exam Types") "" else it)
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
        }

        // 2. Results Transcript Sheet (Slight 4.dp horizontal margin)
        Box(
            modifier = Modifier
                .fillMaxWidth()
                .padding(horizontal = 4.dp)
        ) {
            PdfResultsTranscriptSheet(
                schoolName = uiState.teacher?.schoolName ?: "KENYA EDUHUB TEACHER PORTAL",
                filteredRecords = filteredRecords,
                studentAssignments = uiState.studentSubjectAssignments,
                gradingScales = uiState.gradingScales,
                aggregateDistribution = uiState.aggregateDistribution,
                minSubjects = uiState.minSubjects
            )
        }

        Spacer(modifier = Modifier.height(24.dp))
    }
    }
    }
}

@Composable
private fun PdfResultsTranscriptSheet(
    schoolName: String,
    filteredRecords: List<PerformanceRecordDto>,
    studentAssignments: Map<String, List<StudentSubjectAssignmentDto>>,
    gradingScales: List<GradingScaleDto>,
    aggregateDistribution: List<AggregateDistributionDto>,
    minSubjects: Int
) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(containerColor = Color.White),
        border = BorderStroke(1.5.dp, Color(0xFF1E1E1E)),
        shape = RoundedCornerShape(4.dp),
        elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)
    ) {
        Column(
            modifier = Modifier
                .fillMaxWidth()
                .padding(vertical = 12.dp)
        ) {
            // Header Banner
            Column(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(horizontal = 12.dp),
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
                    text = "OFFICIAL STUDENT RESULTS TRANSCRIPT",
                    fontSize = 13.sp,
                    fontWeight = FontWeight.Bold,
                    color = EduHubOrange,
                    fontFamily = FontFamily.Serif,
                    textAlign = TextAlign.Center
                )
                Text(
                    text = "ACADEMIC AGGREGATE EVALUATION REPORT",
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
                    text = "NO RESULT RECORDS FOUND FOR SELECTED FILTERS",
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
                val processedResults = remember(
                    filteredRecords,
                    studentAssignments,
                    gradingScales,
                    aggregateDistribution,
                    minSubjects
                ) {
                    processAggregateResults(
                        filteredRecords,
                        studentAssignments,
                        gradingScales,
                        aggregateDistribution,
                        minSubjects
                    )
                }

                processedResults.forEachIndexed { groupIndex, group ->
                    // Exam Group Banner
                    Box(
                        modifier = Modifier
                            .fillMaxWidth()
                            .background(Color(0xFF333333))
                            .padding(vertical = 6.dp, horizontal = 12.dp)
                    ) {
                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            horizontalArrangement = Arrangement.SpaceBetween,
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            Text(
                                text = group.examTypeName.uppercase(),
                                fontSize = 12.sp,
                                fontWeight = FontWeight.Bold,
                                color = Color.White,
                                fontFamily = FontFamily.Serif
                            )
                            Text(
                                text = "${group.studentResults.size} CANDIDATES GRADED",
                                fontSize = 10.sp,
                                fontWeight = FontWeight.Bold,
                                color = Color(0xFFFFC107),
                                fontFamily = FontFamily.Serif
                            )
                        }
                    }

                    // Info alert banner about grading
                    Box(
                        modifier = Modifier
                            .fillMaxWidth()
                            .background(Color(0xFFE8F0FE))
                            .padding(vertical = 6.dp, horizontal = 12.dp)
                    ) {
                        Row(verticalAlignment = Alignment.CenterVertically) {
                            Icon(Icons.Default.Info, contentDescription = null, tint = Color(0xFF1967D2), modifier = Modifier.size(14.dp))
                            Spacer(modifier = Modifier.width(6.dp))
                            Text(
                                text = "Grading uses compulsory subjects + best non-compulsory subjects ($minSubjects total). Red tick (✓) indicates subjects used in grading.",
                                fontSize = 10.sp,
                                fontFamily = FontFamily.Serif,
                                color = Color(0xFF1967D2)
                            )
                        }
                    }

                    val sortedSubjects = group.subjects

                    // Horizontally Scrollable Table Container designed to fit content columns
                    val tableScrollState = rememberScrollState()

                    Column(
                        modifier = Modifier
                            .fillMaxWidth()
                            .horizontalScroll(tableScrollState)
                    ) {
                        // Results Table Header
                        Row(
                            modifier = Modifier
                                .background(Color(0xFFF0F0F0))
                                .border(BorderStroke(1.dp, Color.Black)),
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            HeaderCell("RANK", width = 42.dp)
                            HeaderCell("ADM NO", width = 76.dp)
                            HeaderCell("CANDIDATE NAME", width = 135.dp, align = TextAlign.Start)
                            HeaderCell("CLASS/STREAM", width = 85.dp)

                            // Dynamic Subject Header Cells
                            sortedSubjects.forEach { subject ->
                                HeaderCell(abbreviateSubject(subject), width = 72.dp)
                            }

                            HeaderCell("TOTAL MARKS", width = 75.dp)
                            HeaderCell("AVG", width = 52.dp)
                            HeaderCell("POINTS", width = 52.dp)
                            HeaderCell("GRADE", width = 60.dp)
                        }

                        // Candidate Result Rows with Content-Fitting Columns
                        group.studentResults.forEachIndexed { rankIndex, student ->
                            val rowBg = if (rankIndex % 2 == 0) Color.White else Color(0xFFFAFAFA)
                            Row(
                                modifier = Modifier
                                    .background(rowBg)
                                    .border(BorderStroke(0.5.dp, Color.Black)),
                                verticalAlignment = Alignment.CenterVertically
                            ) {
                                DataCell(
                                    text = "#${student.rank}",
                                    width = 42.dp,
                                    fontWeight = FontWeight.Bold,
                                    textColor = if (student.rank <= 3) EduHubOrange else Color.Black
                                )

                                DataCell(
                                    text = student.admissionNumber,
                                    width = 76.dp,
                                    fontWeight = FontWeight.Bold
                                )

                                DataCell(
                                    text = student.studentName.uppercase(),
                                    width = 135.dp,
                                    fontWeight = FontWeight.Bold,
                                    align = TextAlign.Start
                                )

                                DataCell(
                                    text = "${student.className}\n${student.streamName}".trim(),
                                    width = 85.dp
                                )

                                // Dynamic Subject Data Cells
                                sortedSubjects.forEach { subject ->
                                    val res = student.subjectMap[subject]
                                    Box(
                                        modifier = Modifier
                                            .width(72.dp)
                                            .border(BorderStroke(0.5.dp, Color.Black))
                                            .padding(vertical = 8.dp, horizontal = 2.dp),
                                        contentAlignment = Alignment.Center
                                    ) {
                                        if (res != null) {
                                            Row(
                                                verticalAlignment = Alignment.CenterVertically,
                                                horizontalArrangement = Arrangement.Center
                                            ) {
                                                Text(
                                                    text = "${res.marks.toInt()} (${res.grade})",
                                                    fontSize = 10.sp,
                                                    fontWeight = FontWeight.Medium,
                                                    fontFamily = FontFamily.Serif,
                                                    color = Color.Black
                                                )
                                                if (res.isGradingSubject) {
                                                    Spacer(modifier = Modifier.width(1.dp))
                                                    Text(
                                                        text = "✓",
                                                        fontSize = 11.sp,
                                                        fontWeight = FontWeight.Bold,
                                                        color = Color.Red
                                                    )
                                                }
                                            }
                                        } else {
                                            Text(
                                                text = "-",
                                                fontSize = 11.sp,
                                                fontFamily = FontFamily.Serif,
                                                color = Color.Gray
                                            )
                                        }
                                    }
                                }

                                DataCell(
                                    text = "${student.totalMarks.toInt()}",
                                    width = 75.dp,
                                    fontWeight = FontWeight.Bold
                                )

                                DataCell(
                                    text = String.format(Locale.US, "%.1f", student.average),
                                    width = 52.dp,
                                    fontWeight = FontWeight.Bold
                                )

                                DataCell(
                                    text = "${student.totalPoints}",
                                    width = 52.dp,
                                    fontWeight = FontWeight.Bold,
                                    textColor = EduHubOrange
                                )

                                Box(
                                    modifier = Modifier
                                        .width(60.dp)
                                        .border(BorderStroke(0.5.dp, Color.Black))
                                        .padding(vertical = 6.dp, horizontal = 2.dp),
                                    contentAlignment = Alignment.Center
                                ) {
                                    Box(
                                        modifier = Modifier
                                            .clip(RoundedCornerShape(4.dp))
                                            .background(Color(0xFFE8F0FE))
                                            .padding(horizontal = 6.dp, vertical = 2.dp),
                                        contentAlignment = Alignment.Center
                                    ) {
                                        Text(
                                            text = student.aggregateGrade,
                                            fontSize = 11.sp,
                                            fontWeight = FontWeight.Bold,
                                            fontFamily = FontFamily.Serif,
                                            color = Color(0xFF1967D2),
                                            textAlign = TextAlign.Center
                                        )
                                    }
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
                        Text("CONFIDENTIAL OFFICIAL STUDENT RESULTS TRANSCRIPT", fontSize = 9.sp, fontFamily = FontFamily.Serif, color = Color.Gray)
                    }
                }
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
            textAlign = align,
            maxLines = 1,
            overflow = TextOverflow.Ellipsis
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
            textAlign = align,
            maxLines = 2,
            overflow = TextOverflow.Ellipsis
        )
    }
}

private fun abbreviateSubject(subject: String): String {
    val abbreviations = mapOf(
        "MATHEMATICS" to "MATH",
        "ENGLISH" to "ENG",
        "KISWAHILI" to "KISW",
        "PHYSICS" to "PHY",
        "CHEMISTRY" to "CHEM",
        "BIOLOGY" to "BIO",
        "GEOGRAPHY" to "GEOG",
        "HISTORY AND GOVERNMENT" to "HIST",
        "CHRISTIAN RELIGIOUS EDUCATION" to "CRE",
        "AGRICULTURE" to "AGR",
        "BUSINESS STUDIES" to "B/S",
        "COMPUTER STUDIES" to "COMP"
    )
    val upper = subject.uppercase().trim()
    return abbreviations[upper] ?: if (upper.length >= 4) upper.substring(0, 4) else upper
}

private fun processAggregateResults(
    records: List<PerformanceRecordDto>,
    studentAssignments: Map<String, List<StudentSubjectAssignmentDto>>,
    gradingScales: List<GradingScaleDto>,
    aggregateDistribution: List<AggregateDistributionDto>,
    minSubjects: Int
): List<ExamGroupedResults> {
    val groupedByExamType = records.groupBy { it.examTypeName ?: "Regular Exam" }
    val examGroups = mutableListOf<ExamGroupedResults>()

    for ((examTypeName, examRecords) in groupedByExamType) {
        val studentMap = examRecords.groupBy { "${it.admissionNumber}_${it.firstName}_${it.lastName}" }
        val studentResults = mutableListOf<StudentAggregateResult>()
        val allSubjectsSet = mutableSetOf<String>()

        for ((studentKey, studentRecords) in studentMap) {
            val firstRecord = studentRecords.first()
            val admNo = firstRecord.admissionNumber ?: ""
            val studentName = "${firstRecord.firstName ?: ""} ${firstRecord.lastName ?: ""}".trim()
            val className = firstRecord.className ?: "-"
            val streamName = firstRecord.streamName ?: "-"

            val assignedSubjects = studentAssignments[admNo] ?: emptyList()
            val assignedSubjectNames = assignedSubjects.map { it.subjectName }

            val rawSubjectResults = mutableMapOf<String, StudentSubjectResult>()

            for (record in studentRecords) {
                val subjectName = record.subject ?: continue
                allSubjectsSet.add(subjectName)

                val assignedInfo = assignedSubjects.find { it.subjectName.equals(subjectName, ignoreCase = true) }
                val isCompulsory = (assignedInfo?.isCompulsory ?: 0) == 1
                val marks = record.marks
                val points = record.displayPoints
                val grade = record.displayGrade

                rawSubjectResults[subjectName] = StudentSubjectResult(
                    subjectName = subjectName,
                    marks = marks,
                    grade = grade,
                    points = points,
                    isCompulsory = isCompulsory,
                    isGradingSubject = false
                )
            }

            val compulsoryList = rawSubjectResults.values.filter { it.isCompulsory }
            val nonCompulsoryList = rawSubjectResults.values.filter { !it.isCompulsory }
                .sortedWith(compareByDescending<StudentSubjectResult> { it.points }.thenByDescending { it.marks })

            val neededNonCompulsory = maxOf(0, minSubjects - compulsoryList.size)
            val selectedNonCompulsory = nonCompulsoryList.take(neededNonCompulsory)

            val gradingSubjects = compulsoryList + selectedNonCompulsory
            val gradingSubjectNames = gradingSubjects.map { it.subjectName }.toSet()

            val updatedSubjectMap = rawSubjectResults.mapValues { (subName, res) ->
                res.copy(isGradingSubject = gradingSubjectNames.contains(subName))
            }

            var totalMarks = 0.0
            var totalPoints = 0
            var count = 0

            for (sub in gradingSubjects) {
                totalMarks += sub.marks
                totalPoints += sub.points
                count++
            }

            val average = if (count > 0) totalMarks / count else 0.0

            var aggregateGrade = "-"
            for (dist in aggregateDistribution) {
                if (totalPoints >= dist.minPoints && totalPoints <= dist.maxPoints) {
                    aggregateGrade = dist.gradeName
                    break
                }
            }

            if (aggregateGrade == "-") {
                aggregateGrade = when {
                    totalPoints >= 78 -> "A"
                    totalPoints >= 71 -> "A-"
                    totalPoints >= 65 -> "B+"
                    totalPoints >= 59 -> "B"
                    totalPoints >= 53 -> "B-"
                    totalPoints >= 47 -> "C+"
                    totalPoints >= 41 -> "C"
                    totalPoints >= 35 -> "C-"
                    totalPoints >= 29 -> "D+"
                    totalPoints >= 23 -> "D"
                    totalPoints >= 17 -> "D-"
                    else -> "E"
                }
            }

            studentResults.add(
                StudentAggregateResult(
                    admissionNumber = admNo,
                    studentName = studentName,
                    className = className,
                    streamName = streamName,
                    subjectMap = updatedSubjectMap,
                    totalMarks = totalMarks,
                    average = average,
                    totalPoints = totalPoints,
                    aggregateGrade = aggregateGrade,
                    subjectCount = count,
                    assignedSubjectCount = assignedSubjectNames.size
                )
            )
        }

        studentResults.sortByDescending { it.totalPoints }
        val rankedResults = studentResults.mapIndexed { idx, res ->
            res.copy(rank = idx + 1)
        }

        examGroups.add(
            ExamGroupedResults(
                examTypeName = examTypeName,
                subjects = allSubjectsSet.toList().sorted(),
                studentResults = rankedResults
            )
        )
    }

    return examGroups
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
