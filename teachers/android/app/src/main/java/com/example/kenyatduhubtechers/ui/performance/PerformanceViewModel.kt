package com.example.kenyatduhubtechers.ui.performance

import androidx.lifecycle.ViewModel
import androidx.lifecycle.ViewModelProvider
import androidx.lifecycle.viewModelScope
import com.example.kenyatduhubtechers.data.model.ExamTypeDto
import com.example.kenyatduhubtechers.data.model.GradingScaleDto
import com.example.kenyatduhubtechers.data.model.PerformanceRecordDto
import com.example.kenyatduhubtechers.data.model.SavePerformanceRecordItem
import com.example.kenyatduhubtechers.data.model.SavePerformanceRequest
import com.example.kenyatduhubtechers.data.model.StreamDto
import com.example.kenyatduhubtechers.data.model.SubjectDto
import com.example.kenyatduhubtechers.data.model.TeacherDto
import com.example.kenyatduhubtechers.data.repository.PerformanceRepository
import com.example.kenyatduhubtechers.data.repository.Result
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch

data class CsvRowState(
    val studentId: Int? = null,
    val admissionNumber: String,
    val studentName: String,
    val marks: String = "",
    val grade: String = "",
    val points: Int = 0,
    val remarks: String = ""
)

data class PerformanceUiState(
    val isLoading: Boolean = false,
    val isSaving: Boolean = false,
    val isLoadingCsvEditor: Boolean = false,
    val showCsvEditorDialog: Boolean = false,
    val teacher: TeacherDto? = null,
    val terms: List<String> = emptyList(),
    val currentTerm: String = "Term 1",
    val streams: List<StreamDto> = emptyList(),
    val allSubjects: List<SubjectDto> = emptyList(),
    val examTypes: List<ExamTypeDto> = emptyList(),
    val gradingScales: List<GradingScaleDto> = emptyList(),
    val performanceRecords: List<PerformanceRecordDto> = emptyList(),
    val subjectsWithoutPerformance: List<SubjectDto> = emptyList(),
    val csvEditorRows: List<CsvRowState> = emptyList(),
    val searchQuery: String = "",
    val termFilter: String = "",
    val yearFilter: String = "",
    val subjectFilter: String = "",
    val examTypeFilter: String = "",
    val successMessage: String? = null,
    val errorMessage: String? = null
)

class PerformanceViewModel(
    private val performanceRepository: PerformanceRepository
) : ViewModel() {

    private val _uiState = MutableStateFlow(PerformanceUiState())
    val uiState: StateFlow<PerformanceUiState> = _uiState.asStateFlow()

    init {
        loadPerformanceData()
    }

    fun loadPerformanceData() {
        _uiState.update { it.copy(isLoading = true, errorMessage = null) }
        viewModelScope.launch {
            when (val result = performanceRepository.getPerformanceData()) {
                is Result.Success -> {
                    val data = result.data
                    _uiState.update {
                        it.copy(
                            isLoading = false,
                            teacher = data.teacher ?: it.teacher,
                            terms = data.terms,
                            currentTerm = data.currentTerm ?: "Term 1",
                            streams = data.streams,
                            allSubjects = data.allSubjects,
                            examTypes = data.examTypes,
                            gradingScales = data.gradingScales,
                            performanceRecords = data.performanceRecords,
                            subjectsWithoutPerformance = data.subjectsWithoutPerformance
                        )
                    }
                }
                is Result.Error -> {
                    _uiState.update {
                        it.copy(
                            isLoading = false,
                            errorMessage = result.message
                        )
                    }
                }
            }
        }
    }

    fun openBuiltInCsvEditor(streamId: Int? = null, subject: String = "") {
        _uiState.update { it.copy(isLoadingCsvEditor = true, errorMessage = null) }
        viewModelScope.launch {
            val classId = _uiState.value.teacher?.classId
            when (val result = performanceRepository.getStudents(classId, streamId)) {
                is Result.Success -> {
                    val students = result.data.data
                    val rows = students.map { student ->
                        CsvRowState(
                            studentId = student.id,
                            admissionNumber = student.admissionNumber,
                            studentName = "${student.firstName} ${student.lastName}".trim()
                        )
                    }
                    _uiState.update {
                        it.copy(
                            isLoadingCsvEditor = false,
                            showCsvEditorDialog = true,
                            csvEditorRows = if (rows.isNotEmpty()) rows else listOf(
                                CsvRowState(admissionNumber = "ADM001", studentName = "Sample Student 1"),
                                CsvRowState(admissionNumber = "ADM002", studentName = "Sample Student 2")
                            )
                        )
                    }
                }
                is Result.Error -> {
                    // Fallback to admissions from loaded records or sample
                    val admissions = _uiState.value.performanceRecords
                        .mapNotNull { it.admissionNumber }
                        .distinct()
                        .sorted()

                    val rows = if (admissions.isNotEmpty()) {
                        admissions.map { adm ->
                            val rec = _uiState.value.performanceRecords.find { it.admissionNumber == adm }
                            CsvRowState(
                                studentId = rec?.studentId,
                                admissionNumber = adm,
                                studentName = "${rec?.firstName ?: "Student"} ${rec?.lastName ?: ""}".trim()
                            )
                        }
                    } else {
                        listOf(
                            CsvRowState(admissionNumber = "ADM001", studentName = "Sample Student 1"),
                            CsvRowState(admissionNumber = "ADM002", studentName = "Sample Student 2")
                        )
                    }

                    _uiState.update {
                        it.copy(
                            isLoadingCsvEditor = false,
                            showCsvEditorDialog = true,
                            csvEditorRows = rows
                        )
                    }
                }
            }
        }
    }

    fun parseAndOpenCsvFile(csvContent: String) {
        val lines = csvContent.lines()
        if (lines.size <= 1) {
            _uiState.update { it.copy(errorMessage = "CSV file is empty or invalid.") }
            return
        }

        viewModelScope.launch {
            val classId = _uiState.value.teacher?.classId
            val studentResult = performanceRepository.getStudents(classId, null)
            val studentMap = if (studentResult is Result.Success) {
                studentResult.data.data.associateBy { it.admissionNumber.lowercase().trim() }
            } else {
                emptyMap()
            }

            val parsedRows = mutableListOf<CsvRowState>()
            for (i in 1 until lines.size) {
                val line = lines[i].trim()
                if (line.isEmpty()) continue
                val parts = line.split(",")
                if (parts.isNotEmpty()) {
                    val admNo = parts[0].trim()
                    val marksStr = if (parts.size >= 2) parts[1].trim() else ""
                    val matchedStudent = studentMap[admNo.lowercase()]

                    val marksVal = marksStr.toDoubleOrNull() ?: -1.0
                    val (grade, points, remarks) = if (marksVal in 0.0..100.0) {
                        calculateGradeAndPoints(marksVal, "")
                    } else {
                        Triple("", 0, "")
                    }

                    parsedRows.add(
                        CsvRowState(
                            studentId = matchedStudent?.id,
                            admissionNumber = admNo,
                            studentName = matchedStudent?.let { "${it.firstName} ${it.lastName}".trim() } ?: "Student $admNo",
                            marks = if (marksVal in 0.0..100.0) marksStr else "",
                            grade = grade,
                            points = points,
                            remarks = remarks
                        )
                    )
                }
            }

            if (parsedRows.isNotEmpty()) {
                _uiState.update {
                    it.copy(
                        showCsvEditorDialog = true,
                        csvEditorRows = parsedRows
                    )
                }
            } else {
                _uiState.update { it.copy(errorMessage = "Could not parse valid student rows from CSV.") }
            }
        }
    }

    fun updateCsvRowMark(admissionNumber: String, marksStr: String, subject: String) {
        val marksVal = marksStr.toDoubleOrNull() ?: -1.0
        val (grade, points, remarks) = if (marksVal in 0.0..100.0) {
            calculateGradeAndPoints(marksVal, subject)
        } else {
            Triple("", 0, "")
        }

        _uiState.update { state ->
            val updatedRows = state.csvEditorRows.map { row ->
                if (row.admissionNumber.equals(admissionNumber, ignoreCase = true)) {
                    row.copy(
                        marks = marksStr,
                        grade = grade,
                        points = points,
                        remarks = remarks
                    )
                } else {
                    row
                }
            }
            state.copy(csvEditorRows = updatedRows)
        }
    }

    fun submitCsvEditorRecords(term: String, year: String, examTypeId: Int, subject: String) {
        val validRows = _uiState.value.csvEditorRows.filter { it.marks.isNotBlank() && (it.marks.toDoubleOrNull() ?: -1.0) >= 0.0 }
        if (validRows.isEmpty()) {
            _uiState.update { it.copy(errorMessage = "Please enter valid marks for at least one student in the CSV editor.") }
            return
        }

        viewModelScope.launch {
            _uiState.update { it.copy(isSaving = true, errorMessage = null) }

            // Fetch students to map admission numbers to student IDs if needed
            val classId = _uiState.value.teacher?.classId
            val studentResult = performanceRepository.getStudents(classId, null)
            val studentMap = if (studentResult is Result.Success) {
                studentResult.data.data.associateBy { it.admissionNumber.lowercase().trim() }
            } else {
                emptyMap()
            }

            val recordItems = mutableListOf<SavePerformanceRecordItem>()
            validRows.forEach { row ->
                val studentId = row.studentId ?: studentMap[row.admissionNumber.lowercase().trim()]?.id
                val marksVal = row.marks.toDoubleOrNull() ?: 0.0
                if (studentId != null && studentId > 0) {
                    recordItems.add(
                        SavePerformanceRecordItem(
                            studentId = studentId,
                            term = term,
                            year = year,
                            examTypeId = examTypeId,
                            subject = subject,
                            marks = marksVal,
                            grade = row.grade,
                            remarks = row.remarks
                        )
                    )
                }
            }

            if (recordItems.isEmpty()) {
                _uiState.update { it.copy(isSaving = false, errorMessage = "Could not map admission numbers to student records.") }
                return@launch
            }

            savePerformanceRecords(recordItems)
        }
    }

    private fun savePerformanceRecords(records: List<SavePerformanceRecordItem>) {
        if (records.isEmpty()) return
        _uiState.update { it.copy(isSaving = true, errorMessage = null, successMessage = null) }

        viewModelScope.launch {
            when (val result = performanceRepository.savePerformanceData(SavePerformanceRequest(records))) {
                is Result.Success -> {
                    _uiState.update {
                        it.copy(
                            isSaving = false,
                            showCsvEditorDialog = false,
                            csvEditorRows = emptyList(),
                            successMessage = result.data.message ?: "Successfully saved performance records via CSV!"
                        )
                    }
                    loadPerformanceData()
                }
                is Result.Error -> {
                    _uiState.update {
                        it.copy(
                            isSaving = false,
                            errorMessage = result.message
                        )
                    }
                }
            }
        }
    }

    private fun calculateGradeAndPoints(marks: Double, subject: String): Triple<String, Int, String> {
        val scales = _uiState.value.gradingScales

        val subjectScales = scales.filter {
            it.subjectName != null && it.subjectName.equals(subject, ignoreCase = true)
        }

        var matchedScale = subjectScales.find { marks >= it.minScore && marks <= it.maxScore }

        if (matchedScale == null) {
            val generalScales = scales.filter { it.subjectId == null }
            matchedScale = generalScales.find { marks >= it.minScore && marks <= it.maxScore }
        }

        if (matchedScale == null) {
            matchedScale = scales.find { marks >= it.minScore && marks <= it.maxScore }
        }

        if (matchedScale != null) {
            return Triple(
                matchedScale.gradeName ?: "C",
                matchedScale.points,
                matchedScale.gradeDescription ?: ""
            )
        }

        return when {
            marks >= 80 -> Triple("A", 12, "Excellent")
            marks >= 75 -> Triple("A-", 11, "Very Good")
            marks >= 70 -> Triple("B+", 10, "Good")
            marks >= 65 -> Triple("B", 9, "Good")
            marks >= 60 -> Triple("B-", 8, "Fairly Good")
            marks >= 55 -> Triple("C+", 7, "Above Average")
            marks >= 50 -> Triple("C", 6, "Average")
            marks >= 45 -> Triple("C-", 5, "Below Average")
            marks >= 40 -> Triple("D+", 4, "Passing")
            marks >= 35 -> Triple("D", 3, "Weak")
            marks >= 30 -> Triple("D-", 2, "Very Weak")
            else -> Triple("E", 1, "Poor")
        }
    }

    fun generateCsvContentFromEditor(): String {
        val sb = StringBuilder()
        sb.append("Admission Number,Marks\n")
        _uiState.value.csvEditorRows.forEach { row ->
            sb.append("${row.admissionNumber},${row.marks}\n")
        }
        return sb.toString()
    }

    fun closeCsvEditor() {
        _uiState.update { it.copy(showCsvEditorDialog = false) }
    }

    fun onSearchQueryChanged(query: String) {
        _uiState.update { it.copy(searchQuery = query) }
    }

    fun onTermFilterChanged(term: String) {
        _uiState.update { it.copy(termFilter = term) }
    }

    fun onYearFilterChanged(year: String) {
        _uiState.update { it.copy(yearFilter = year) }
    }

    fun onSubjectFilterChanged(subject: String) {
        _uiState.update { it.copy(subjectFilter = subject) }
    }

    fun onExamTypeFilterChanged(examTypeId: String) {
        _uiState.update { it.copy(examTypeFilter = examTypeId) }
    }

    fun resetFilters() {
        _uiState.update {
            it.copy(
                searchQuery = "",
                termFilter = "",
                yearFilter = "",
                subjectFilter = "",
                examTypeFilter = ""
            )
        }
    }

    fun clearMessages() {
        _uiState.update { it.copy(errorMessage = null, successMessage = null) }
    }

    class Factory(
        private val performanceRepository: PerformanceRepository
    ) : ViewModelProvider.Factory {
        @Suppress("UNCHECKED_CAST")
        override fun <T : ViewModel> create(modelClass: Class<T>): T {
            if (modelClass.isAssignableFrom(PerformanceViewModel::class.java)) {
                return PerformanceViewModel(performanceRepository) as T
            }
            throw IllegalArgumentException("Unknown ViewModel class")
        }
    }
}
