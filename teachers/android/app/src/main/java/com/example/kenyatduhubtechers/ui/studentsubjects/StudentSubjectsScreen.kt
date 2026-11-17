package com.example.kenyatduhubtechers.ui.studentsubjects

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
import androidx.compose.material.icons.filled.Add
import androidx.compose.material.icons.filled.AutoFixHigh
import androidx.compose.material.icons.filled.Book
import androidx.compose.material.icons.filled.Close
import androidx.compose.material.icons.filled.Delete
import androidx.compose.material.icons.filled.Edit
import androidx.compose.material.icons.filled.Refresh
import androidx.compose.material.icons.filled.School
import androidx.compose.material.icons.filled.Search
import androidx.compose.material.icons.filled.Visibility
import androidx.compose.material3.Button
import androidx.compose.material3.ButtonDefaults
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.Checkbox
import androidx.compose.material3.CheckboxDefaults
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
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.compose.ui.window.Dialog
import androidx.compose.ui.window.DialogProperties
import com.example.kenyatduhubtechers.data.model.StudentAffairsItemDto
import com.example.kenyatduhubtechers.data.model.SubjectDto
import com.example.kenyatduhubtechers.ui.theme.EduHubBackground
import com.example.kenyatduhubtechers.ui.theme.EduHubBorder
import com.example.kenyatduhubtechers.ui.theme.EduHubError
import com.example.kenyatduhubtechers.ui.theme.EduHubErrorContainer
import com.example.kenyatduhubtechers.ui.theme.EduHubOrange
import com.example.kenyatduhubtechers.ui.theme.EduHubSuccess
import com.example.kenyatduhubtechers.ui.theme.EduHubSuccessContainer
import com.example.kenyatduhubtechers.ui.theme.EduHubTextDark
import com.example.kenyatduhubtechers.ui.theme.EduHubTextMuted

@OptIn(ExperimentalLayoutApi::class, ExperimentalMaterial3Api::class)
@Composable
fun StudentSubjectsScreen(
    viewModel: StudentSubjectsViewModel,
    modifier: Modifier = Modifier
) {
    val uiState by viewModel.uiState.collectAsState()

    PullToRefreshBox(
        isRefreshing = uiState.isLoading,
        onRefresh = { viewModel.loadStudentSubjectsData() },
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
            // Filter students
            val filteredStudents = remember(
                uiState.students,
                uiState.searchQuery,
                uiState.classFilter,
                uiState.streamFilter,
                uiState.subjectFilter,
                uiState.subjectAssignmentsMap
            ) {
                uiState.students.filter { student ->
                    val fullName = "${student.firstName} ${student.lastName}".lowercase()
                    val adm = student.admissionNumber.lowercase()
                    val query = uiState.searchQuery.lowercase().trim()
                    val matchesQuery = query.isEmpty() || fullName.contains(query) || adm.contains(query)

                    val matchesClass = uiState.classFilter.isEmpty() || student.className == uiState.classFilter
                    val matchesStream = uiState.streamFilter.isEmpty() || student.streamName == uiState.streamFilter

                    val assignedStr = uiState.subjectAssignmentsMap[student.id] ?: ""
                    val matchesSubject = uiState.subjectFilter.isEmpty() ||
                            assignedStr.contains(uiState.subjectFilter, ignoreCase = true)

                    matchesQuery && matchesClass && matchesStream && matchesSubject
                }
            }

            val classOptions = remember(uiState.classes) {
                listOf("All Classes") + uiState.classes.mapNotNull { it.className }
            }
            val streamOptions = remember(uiState.streams) {
                listOf("All Streams") + uiState.streams.mapNotNull { it.streamName }
            }
            val subjectOptions = remember(uiState.availableSubjects) {
                listOf("All Subjects") + uiState.availableSubjects.mapNotNull { it.subjectName }
            }

            val classMap = remember(uiState.classes) {
                uiState.classes.associateBy { it.className }
            }
            val selectedClassObj = classMap[uiState.classFilter]

            Column(
                modifier = Modifier
                    .fillMaxSize()
                    .verticalScroll(rememberScrollState())
                    .padding(16.dp)
            ) {
                // Header
                Text(
                    text = "Student Subject Assignments",
                    fontSize = 22.sp,
                    fontWeight = FontWeight.Bold,
                    color = EduHubTextDark
                )
                Text(
                    text = "Manage and assign subjects to registered students.",
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

                // Action Row
                FlowRow(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(bottom = 16.dp),
                    horizontalArrangement = Arrangement.spacedBy(8.dp),
                    verticalArrangement = Arrangement.spacedBy(8.dp)
                ) {
                    Button(
                        onClick = { viewModel.openAssignModal() },
                        colors = ButtonDefaults.buttonColors(containerColor = EduHubOrange),
                        shape = RoundedCornerShape(8.dp)
                    ) {
                        Icon(Icons.Default.Add, contentDescription = null, modifier = Modifier.size(18.dp))
                        Spacer(modifier = Modifier.width(6.dp))
                        Text("Assign Subject", fontSize = 13.sp, fontWeight = FontWeight.Bold)
                    }

                    OutlinedButton(
                        onClick = {
                            val cId = selectedClassObj?.id
                            if (cId != null && cId > 0) {
                                viewModel.autoAssignCompulsorySubjects(cId)
                            } else if (uiState.classes.isNotEmpty() && uiState.classes.first().id != null) {
                                viewModel.autoAssignCompulsorySubjects(uiState.classes.first().id!!)
                            } else {
                                viewModel.loadStudentSubjectsData()
                            }
                        },
                        colors = ButtonDefaults.outlinedButtonColors(contentColor = EduHubOrange),
                        border = BorderStroke(1.dp, EduHubOrange),
                        shape = RoundedCornerShape(8.dp)
                    ) {
                        Icon(Icons.Default.AutoFixHigh, contentDescription = null, modifier = Modifier.size(18.dp))
                        Spacer(modifier = Modifier.width(6.dp))
                        Text("Auto-Assign Compulsory", fontSize = 13.sp, fontWeight = FontWeight.Bold)
                    }
                }

                // Search & Filter Card
                Card(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(bottom = 16.dp),
                    shape = RoundedCornerShape(8.dp),
                    colors = CardDefaults.cardColors(containerColor = EduHubBackground),
                    border = BorderStroke(1.dp, EduHubBorder)
                ) {
                    Column(modifier = Modifier.padding(16.dp)) {
                        OutlinedTextField(
                            value = uiState.searchQuery,
                            onValueChange = { viewModel.onSearchQueryChanged(it) },
                            placeholder = { Text("Search by name or admission number...", fontSize = 13.sp) },
                            leadingIcon = { Icon(Icons.Default.Search, contentDescription = null, tint = EduHubTextMuted) },
                            modifier = Modifier
                                .fillMaxWidth()
                                .padding(bottom = 12.dp),
                            singleLine = true,
                            shape = RoundedCornerShape(8.dp),
                            colors = OutlinedTextFieldDefaults.colors(focusedBorderColor = EduHubOrange)
                        )

                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            horizontalArrangement = Arrangement.spacedBy(8.dp)
                        ) {
                            FilterDropdown(
                                modifier = Modifier.weight(1f),
                                label = "Class",
                                selectedOption = if (uiState.classFilter.isEmpty()) "All Classes" else uiState.classFilter,
                                options = classOptions,
                                onOptionSelected = {
                                    viewModel.onClassFilterChanged(if (it == "All Classes") "" else it)
                                }
                            )

                            FilterDropdown(
                                modifier = Modifier.weight(1f),
                                label = "Stream",
                                selectedOption = if (uiState.streamFilter.isEmpty()) "All Streams" else uiState.streamFilter,
                                options = streamOptions,
                                onOptionSelected = {
                                    viewModel.onStreamFilterChanged(if (it == "All Streams") "" else it)
                                }
                            )
                        }

                        Spacer(modifier = Modifier.height(8.dp))

                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            horizontalArrangement = Arrangement.spacedBy(8.dp),
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            FilterDropdown(
                                modifier = Modifier.weight(1f),
                                label = "Subject Filter",
                                selectedOption = if (uiState.subjectFilter.isEmpty()) "All Subjects" else uiState.subjectFilter,
                                options = subjectOptions,
                                onOptionSelected = {
                                    viewModel.onSubjectFilterChanged(if (it == "All Subjects") "" else it)
                                }
                            )

                            OutlinedButton(
                                onClick = { viewModel.resetFilters() },
                                modifier = Modifier.height(50.dp),
                                shape = RoundedCornerShape(8.dp)
                            ) {
                                Icon(Icons.Default.Refresh, contentDescription = null, modifier = Modifier.size(16.dp))
                                Spacer(modifier = Modifier.width(4.dp))
                                Text("Reset", fontSize = 12.sp)
                            }
                        }
                    }
                }

                // Student Table
                Card(
                    modifier = Modifier.fillMaxWidth(),
                    shape = RoundedCornerShape(8.dp),
                    colors = CardDefaults.cardColors(containerColor = EduHubBackground),
                    border = BorderStroke(1.dp, EduHubBorder)
                ) {
                    Column(modifier = Modifier.fillMaxWidth()) {
                        Row(
                            modifier = Modifier
                                .fillMaxWidth()
                                .background(EduHubBackground)
                                .padding(12.dp)
                        ) {
                            Text("STUDENT SUBJECT ASSIGNMENTS (${filteredStudents.size})", fontSize = 13.sp, fontWeight = FontWeight.Bold, color = EduHubTextDark)
                        }
                        HorizontalDivider(color = EduHubBorder)

                        if (filteredStudents.isEmpty()) {
                            Box(
                                modifier = Modifier
                                    .fillMaxWidth()
                                    .padding(32.dp),
                                contentAlignment = Alignment.Center
                            ) {
                                Text("No students found matching current filters.", color = EduHubTextMuted, fontSize = 14.sp)
                            }
                        } else {
                            Row(
                                modifier = Modifier
                                    .fillMaxWidth()
                                    .horizontalScroll(rememberScrollState())
                            ) {
                                Column {
                                    // Table Header
                                    Row(
                                        modifier = Modifier
                                            .background(Color(0xFFF8F9FA))
                                            .padding(vertical = 10.dp, horizontal = 12.dp),
                                        verticalAlignment = Alignment.CenterVertically
                                    ) {
                                        HeaderCell("ADM NO", width = 85.dp)
                                        HeaderCell("NAME", width = 140.dp, align = TextAlign.Start)
                                        HeaderCell("CLASS", width = 90.dp)
                                        HeaderCell("ASSIGNED SUBJECTS", width = 240.dp, align = TextAlign.Start)
                                        HeaderCell("ACTIONS", width = 110.dp)
                                    }
                                    HorizontalDivider(color = EduHubBorder)

                                    // Table Rows
                                    filteredStudents.forEachIndexed { index, student ->
                                        val bg = if (index % 2 == 0) Color.White else Color(0xFFFAFAFA)
                                        val assignedStr = uiState.subjectAssignmentsMap[student.id] ?: "No subjects assigned"

                                        Row(
                                            modifier = Modifier
                                                .background(bg)
                                                .padding(vertical = 10.dp, horizontal = 12.dp),
                                            verticalAlignment = Alignment.CenterVertically
                                        ) {
                                            DataCell(text = student.admissionNumber, width = 85.dp, fontWeight = FontWeight.Bold)
                                            DataCell(text = "${student.firstName} ${student.lastName}", width = 140.dp, align = TextAlign.Start)
                                            DataCell(text = "${student.className ?: "-"} ${student.streamName ?: ""}".trim(), width = 90.dp)
                                            DataCell(text = assignedStr, width = 240.dp, align = TextAlign.Start, textColor = if (assignedStr.contains("No subjects")) EduHubError else EduHubTextDark)

                                            Row(
                                                modifier = Modifier.width(110.dp),
                                                horizontalArrangement = Arrangement.Center,
                                                verticalAlignment = Alignment.CenterVertically
                                            ) {
                                                IconButton(
                                                    onClick = { viewModel.openStudentSubjectDetails(student) },
                                                    modifier = Modifier.size(32.dp)
                                                ) {
                                                    Icon(Icons.Default.Visibility, contentDescription = "View Subjects", tint = EduHubOrange, modifier = Modifier.size(18.dp))
                                                }
                                                IconButton(
                                                    onClick = { viewModel.openAssignModal(student.id) },
                                                    modifier = Modifier.size(32.dp)
                                                ) {
                                                    Icon(Icons.Default.Add, contentDescription = "Assign Subject", tint = EduHubSuccess, modifier = Modifier.size(18.dp))
                                                }
                                            }
                                        }
                                        HorizontalDivider(color = EduHubBorder.copy(alpha = 0.5f))
                                    }
                                }
                            }
                        }
                    }
                }

                Spacer(modifier = Modifier.height(24.dp))
            }

            // Student Subjects Detail Modal Dialog
            if (uiState.showDetailsDialog && uiState.selectedStudentDetail != null) {
                val student = uiState.selectedStudentDetail!!
                Dialog(
                    onDismissRequest = { viewModel.closeStudentSubjectDetails() },
                    properties = DialogProperties(usePlatformDefaultWidth = false)
                ) {
                    Surface(
                        shape = RoundedCornerShape(16.dp),
                        color = Color.White,
                        border = BorderStroke(1.dp, EduHubBorder),
                        modifier = Modifier
                            .fillMaxWidth(0.95f)
                            .padding(12.dp)
                    ) {
                        Column(modifier = Modifier.padding(20.dp)) {
                            Row(
                                modifier = Modifier.fillMaxWidth(),
                                horizontalArrangement = Arrangement.SpaceBetween,
                                verticalAlignment = Alignment.CenterVertically
                            ) {
                                Text("Student Subject List", fontSize = 18.sp, fontWeight = FontWeight.Bold, color = EduHubTextDark)
                                IconButton(onClick = { viewModel.closeStudentSubjectDetails() }) {
                                    Icon(Icons.Default.Close, contentDescription = null)
                                }
                            }

                            Spacer(modifier = Modifier.height(8.dp))
                            Text("${student.firstName} ${student.lastName} (${student.admissionNumber})", fontSize = 14.sp, fontWeight = FontWeight.Bold, color = EduHubOrange)
                            Text("Class: ${student.className ?: "-"} ${student.streamName ?: ""}".trim(), fontSize = 12.sp, color = EduHubTextMuted)

                            Spacer(modifier = Modifier.height(12.dp))
                            HorizontalDivider(color = EduHubBorder)
                            Spacer(modifier = Modifier.height(12.dp))

                            if (uiState.isLoadingStudentDetails) {
                                Box(
                                    modifier = Modifier
                                        .fillMaxWidth()
                                        .height(140.dp),
                                    contentAlignment = Alignment.Center
                                ) {
                                    CircularProgressIndicator(color = EduHubOrange)
                                }
                            } else if (uiState.studentDetailSubjects.isEmpty()) {
                                Text("No subject assignments found for this student.", fontSize = 13.sp, color = EduHubTextMuted, modifier = Modifier.padding(vertical = 16.dp))
                            } else {
                                Column(
                                    modifier = Modifier
                                        .fillMaxWidth()
                                        .height(280.dp)
                                        .verticalScroll(rememberScrollState())
                                ) {
                                    uiState.studentDetailSubjects.forEach { sub ->
                                        Row(
                                            modifier = Modifier
                                                .fillMaxWidth()
                                                .padding(vertical = 8.dp),
                                            horizontalArrangement = Arrangement.SpaceBetween,
                                            verticalAlignment = Alignment.CenterVertically
                                        ) {
                                            Row(verticalAlignment = Alignment.CenterVertically) {
                                                Icon(Icons.Default.Book, contentDescription = null, tint = EduHubOrange, modifier = Modifier.size(18.dp))
                                                Spacer(modifier = Modifier.width(8.dp))
                                                Text(sub.subjectName, fontSize = 14.sp, fontWeight = FontWeight.Medium, color = EduHubTextDark)
                                            }

                                            IconButton(
                                                onClick = { viewModel.removeSubjectFromStudent(student.id, sub.subjectId) },
                                                modifier = Modifier.size(28.dp)
                                            ) {
                                                Icon(Icons.Default.Delete, contentDescription = "Remove Subject", tint = EduHubError, modifier = Modifier.size(18.dp))
                                            }
                                        }
                                        HorizontalDivider(color = EduHubBorder.copy(alpha = 0.4f))
                                    }
                                }
                            }

                            Spacer(modifier = Modifier.height(16.dp))

                            OutlinedButton(
                                onClick = { viewModel.closeStudentSubjectDetails() },
                                modifier = Modifier.align(Alignment.End),
                                shape = RoundedCornerShape(25.dp)
                            ) {
                                Text("Close")
                            }
                        }
                    }
                }
            }

            // Assign Subject Dialog
            if (uiState.showAssignDialog) {
                Dialog(
                    onDismissRequest = { viewModel.closeAssignModal() },
                    properties = DialogProperties(usePlatformDefaultWidth = false)
                ) {
                    Surface(
                        shape = RoundedCornerShape(16.dp),
                        color = Color.White,
                        border = BorderStroke(1.dp, EduHubBorder),
                        modifier = Modifier
                            .fillMaxWidth(0.95f)
                            .padding(12.dp)
                    ) {
                        Column(modifier = Modifier.padding(20.dp)) {
                            Row(
                                modifier = Modifier.fillMaxWidth(),
                                horizontalArrangement = Arrangement.SpaceBetween,
                                verticalAlignment = Alignment.CenterVertically
                            ) {
                                Text("Assign Subject to Students", fontSize = 18.sp, fontWeight = FontWeight.Bold, color = EduHubTextDark)
                                IconButton(onClick = { viewModel.closeAssignModal() }) {
                                    Icon(Icons.Default.Close, contentDescription = null)
                                }
                            }

                            Spacer(modifier = Modifier.height(12.dp))
                            HorizontalDivider(color = EduHubBorder)
                            Spacer(modifier = Modifier.height(12.dp))

                            // Subject Selection Dropdown
                            val assignSubjectOptions = remember(uiState.availableSubjects) {
                                listOf("Select Subject") + uiState.availableSubjects.mapNotNull { it.subjectName }
                            }
                            val subjectMap = remember(uiState.availableSubjects) {
                                uiState.availableSubjects.filter { !it.subjectName.isNullOrBlank() }.associateBy { it.subjectName!! }
                            }
                            val selectedSubjectName = uiState.availableSubjects.find { it.id == uiState.selectedSubjectIdForAssign }?.subjectName ?: "Select Subject"

                            FilterDropdown(
                                modifier = Modifier.fillMaxWidth(),
                                label = "Subject to Assign",
                                selectedOption = selectedSubjectName,
                                options = assignSubjectOptions,
                                onOptionSelected = {
                                    val sub = subjectMap[it]
                                    if (sub?.id != null) {
                                        viewModel.onSubjectSelectedForAssign(sub.id)
                                    }
                                }
                            )

                            Spacer(modifier = Modifier.height(12.dp))

                            // Student Selection Header
                            Row(
                                modifier = Modifier.fillMaxWidth(),
                                horizontalArrangement = Arrangement.SpaceBetween,
                                verticalAlignment = Alignment.CenterVertically
                            ) {
                                Text(
                                    "Select Students (${uiState.selectedStudentIdsForAssign.size})",
                                    fontSize = 13.sp,
                                    fontWeight = FontWeight.Bold,
                                    color = EduHubTextDark
                                )

                                Row {
                                    OutlinedButton(
                                        onClick = { viewModel.selectAllFilteredStudentsForAssign(filteredStudents.map { it.id }) },
                                        modifier = Modifier.height(32.dp),
                                        shape = RoundedCornerShape(16.dp)
                                    ) {
                                        Text("Select All", fontSize = 11.sp)
                                    }
                                    Spacer(modifier = Modifier.width(4.dp))
                                    OutlinedButton(
                                        onClick = { viewModel.clearSelectedStudentsForAssign() },
                                        modifier = Modifier.height(32.dp),
                                        shape = RoundedCornerShape(16.dp)
                                    ) {
                                        Text("Clear", fontSize = 11.sp)
                                    }
                                }
                            }

                            Spacer(modifier = Modifier.height(8.dp))

                            // Students Checklist
                            Column(
                                modifier = Modifier
                                    .fillMaxWidth()
                                    .height(200.dp)
                                    .border(1.dp, EduHubBorder, RoundedCornerShape(8.dp))
                                    .verticalScroll(rememberScrollState())
                                    .padding(8.dp)
                            ) {
                                filteredStudents.forEach { student ->
                                    val isSelected = uiState.selectedStudentIdsForAssign.contains(student.id)
                                    Row(
                                        modifier = Modifier
                                            .fillMaxWidth()
                                            .padding(vertical = 2.dp),
                                        verticalAlignment = Alignment.CenterVertically
                                    ) {
                                        Checkbox(
                                            checked = isSelected,
                                            onCheckedChange = { viewModel.toggleSelectStudentForAssign(student.id) },
                                            colors = CheckboxDefaults.colors(checkedColor = EduHubOrange)
                                        )
                                        Spacer(modifier = Modifier.width(4.dp))
                                        Text(
                                            "${student.firstName} ${student.lastName} (${student.admissionNumber})",
                                            fontSize = 13.sp,
                                            color = EduHubTextDark
                                        )
                                    }
                                }
                            }

                            Spacer(modifier = Modifier.height(16.dp))

                            Button(
                                onClick = { viewModel.submitAssignSubject() },
                                modifier = Modifier.fillMaxWidth(),
                                colors = ButtonDefaults.buttonColors(containerColor = EduHubOrange),
                                shape = RoundedCornerShape(8.dp),
                                enabled = !uiState.isSubmitting
                            ) {
                                if (uiState.isSubmitting) {
                                    CircularProgressIndicator(color = Color.White, modifier = Modifier.size(18.dp))
                                } else {
                                    Text("Submit Assignment", fontSize = 14.sp, fontWeight = FontWeight.Bold)
                                }
                            }
                        }
                    }
                }
            }
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
