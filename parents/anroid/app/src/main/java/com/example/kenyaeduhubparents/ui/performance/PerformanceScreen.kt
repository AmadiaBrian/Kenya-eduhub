package com.example.kenyaeduhubparents.ui.performance

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
import androidx.compose.material3.HorizontalDivider
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
import com.example.kenyaeduhubparents.data.model.ChildPerformanceWrapper

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun PerformanceScreen(
    modifier: Modifier = Modifier,
    viewModel: PerformanceViewModel = viewModel(
        factory = PerformanceViewModel.Factory(LocalContext.current)
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
                text = "Performance",
                style = MaterialTheme.typography.headlineMedium,
                fontWeight = FontWeight.Bold,
                color = MaterialTheme.colorScheme.onBackground
            )
            Text(
                text = "View academic performance records by exam type",
                style = MaterialTheme.typography.bodyMedium,
                color = MaterialTheme.colorScheme.onSurfaceVariant,
                textAlign = TextAlign.Center
            )
        }

        when (val state = uiState) {
            is PerformanceUiState.Loading -> {
                Box(
                    modifier = Modifier
                        .fillMaxWidth()
                        .height(250.dp),
                    contentAlignment = Alignment.Center
                ) {
                    CircularProgressIndicator(color = MaterialTheme.colorScheme.primary)
                }
            }
            is PerformanceUiState.Error -> {
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
                        OutlinedButton(onClick = { viewModel.loadPerformance() }) {
                            Text("Retry")
                        }
                    }
                }
            }
            is PerformanceUiState.Success -> {
                val data = state.data
                val children = data.children
                val terms = data.terms.ifEmpty { listOf("Term 1", "Term 2", "Term 3") }
                val years = data.years.ifEmpty { listOf(data.currentYear) }

                // Filters Card
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
                            text = "Filter Performance Records",
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
                        val selectedTermVal = viewModel.selectedTerm ?: terms.firstOrNull() ?: "Term 1"

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
                                            viewModel.updateFilters(viewModel.selectedChildId, term, viewModel.selectedYear, viewModel.selectedExamType)
                                            termExpanded = false
                                        }
                                    )
                                }
                            }
                        }

                        // Year Filter Dropdown
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
                                            viewModel.updateFilters(viewModel.selectedChildId, viewModel.selectedTerm, year.toString(), viewModel.selectedExamType)
                                            yearExpanded = false
                                        }
                                    )
                                }
                            }
                        }
                    }
                }

                // Performance Records / Cards by Exam Type
                if (data.performanceData.isEmpty()) {
                    Card(
                        modifier = Modifier.fillMaxWidth(),
                        shape = RoundedCornerShape(16.dp),
                        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface)
                    ) {
                        Text(
                            text = "No performance records found for your children with the selected filters.",
                            style = MaterialTheme.typography.bodyMedium,
                            color = MaterialTheme.colorScheme.onSurfaceVariant,
                            modifier = Modifier.padding(24.dp),
                            textAlign = TextAlign.Center
                        )
                    }
                } else {
                    Column(verticalArrangement = Arrangement.spacedBy(16.dp)) {
                        data.performanceData.forEach { (_, wrapper) ->
                            PdfPerformanceReportCard(wrapper = wrapper, term = viewModel.selectedTerm ?: data.currentTerm ?: "Term 1", year = viewModel.selectedYear ?: data.currentYear.toString())
                        }
                    }
                }
            }
        }
    }
}

@Composable
fun PdfPerformanceReportCard(
    wrapper: ChildPerformanceWrapper,
    term: String,
    year: String
) {
    val child = wrapper.child
    val examTypeMap = wrapper.performanceByExamType.ifEmpty { wrapper.performance.groupBy { it.displayExamType } }

    if (examTypeMap.isEmpty()) {
        Card(
            modifier = Modifier.fillMaxWidth(),
            shape = RoundedCornerShape(12.dp),
            colors = CardDefaults.cardColors(containerColor = Color.White),
            elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)
        ) {
            Column(
                modifier = Modifier.padding(16.dp),
                horizontalAlignment = Alignment.CenterHorizontally
            ) {
                Text(text = child?.fullName ?: "Student", fontWeight = FontWeight.Bold, fontSize = 16.sp)
                Text(text = "No performance records found.", fontSize = 12.sp, color = Color.Gray, modifier = Modifier.padding(top = 8.dp))
            }
        }
    } else {
        examTypeMap.forEach { (examType, records) ->
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
                    // Header for this exam type
                    Column(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalAlignment = Alignment.CenterHorizontally,
                        verticalArrangement = Arrangement.spacedBy(4.dp)
                    ) {
                        Text(
                            text = "PERFORMANCE REPORT",
                            fontWeight = FontWeight.Bold,
                            fontSize = 16.sp,
                            color = Color.Black,
                            textAlign = TextAlign.Center
                        )
                        Text(
                            text = "Exam Type: ${examType.uppercase()}",
                            fontWeight = FontWeight.Bold,
                            fontSize = 14.sp,
                            color = MaterialTheme.colorScheme.primary,
                            textAlign = TextAlign.Center
                        )
                        Text(
                            text = child?.fullName ?: "Student",
                            fontWeight = FontWeight.SemiBold,
                            fontSize = 14.sp,
                            color = Color.DarkGray
                        )
                        Text(
                            text = "Class: ${child?.displayClass ?: "No Class"} | Stream: ${child?.displayStream ?: "No Stream"} | Term: $term | Year: $year",
                            fontSize = 12.sp,
                            color = Color.Gray,
                            textAlign = TextAlign.Center
                        )
                    }
                    HorizontalDivider(thickness = 2.dp, color = Color.DarkGray)

                    // Table Header Row
                    Row(
                        modifier = Modifier
                            .fillMaxWidth()
                            .background(Color(0xFFF0F0F0))
                            .border(1.dp, Color.Black)
                            .padding(8.dp),
                        horizontalArrangement = Arrangement.SpaceBetween
                    ) {
                        Text(text = "Subject", fontWeight = FontWeight.Bold, fontSize = 12.sp, modifier = Modifier.weight(2.5f))
                        Text(text = "Marks", fontWeight = FontWeight.Bold, fontSize = 12.sp, modifier = Modifier.weight(1f))
                        Text(text = "Grade", fontWeight = FontWeight.Bold, fontSize = 12.sp, modifier = Modifier.weight(1f))
                        Text(text = "Pts", fontWeight = FontWeight.Bold, fontSize = 12.sp, modifier = Modifier.weight(0.8f))
                    }

                    // Records for this exam type
                    records.forEach { record ->
                        Row(
                            modifier = Modifier
                                .fillMaxWidth()
                                .border(1.dp, Color.LightGray)
                                .padding(8.dp),
                            horizontalArrangement = Arrangement.SpaceBetween,
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            Text(text = record.subject, fontSize = 12.sp, color = Color.Black, modifier = Modifier.weight(2.5f))
                            Text(text = String.format("%.1f", record.marks), fontSize = 12.sp, color = Color.Black, modifier = Modifier.weight(1f))
                            Text(text = record.grade, fontWeight = FontWeight.Bold, fontSize = 12.sp, color = Color.Black, modifier = Modifier.weight(1f))
                            Text(text = record.gradePoints?.toString() ?: "-", fontSize = 12.sp, color = Color.Black, modifier = Modifier.weight(0.8f))
                        }
                    }

                    // Footer
                    HorizontalDivider(color = Color.LightGray)
                    Text(
                        text = "Kenya EduHub Performance System",
                        fontSize = 10.sp,
                        color = Color.Gray,
                        fontStyle = androidx.compose.ui.text.font.FontStyle.Italic,
                        modifier = Modifier.fillMaxWidth(),
                        textAlign = TextAlign.Center
                    )
                }
            }
        }
    }
}
