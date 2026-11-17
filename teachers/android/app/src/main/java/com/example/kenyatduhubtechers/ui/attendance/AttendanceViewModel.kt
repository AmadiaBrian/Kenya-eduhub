package com.example.kenyatduhubtechers.ui.attendance

import androidx.lifecycle.ViewModel
import androidx.lifecycle.ViewModelProvider
import androidx.lifecycle.viewModelScope
import com.example.kenyatduhubtechers.data.model.AttendanceHistoryItemDto
import com.example.kenyatduhubtechers.data.model.AttendanceStudentDto
import com.example.kenyatduhubtechers.data.model.CalendarStatusDto
import com.example.kenyatduhubtechers.data.model.ClassDto
import com.example.kenyatduhubtechers.data.model.SaveAttendanceItem
import com.example.kenyatduhubtechers.data.model.TeacherDto
import com.example.kenyatduhubtechers.data.model.TeacherSubjectDto
import com.example.kenyatduhubtechers.data.repository.AttendanceRepository
import com.example.kenyatduhubtechers.data.repository.Result
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import java.text.SimpleDateFormat
import java.util.Calendar
import java.util.Date
import java.util.Locale

data class AttendanceUiState(
    val isLoading: Boolean = false,
    val isLoadingStudents: Boolean = false,
    val isLoadingHistory: Boolean = false,
    val isSubmitting: Boolean = false,
    val teacher: TeacherDto? = null,
    val calendarStatus: CalendarStatusDto? = null,
    val classes: List<ClassDto> = emptyList(),
    val subjectAssignments: List<TeacherSubjectDto> = emptyList(),
    val selectedClassId: Int? = null,
    val attendanceDate: String = SimpleDateFormat("yyyy-MM-dd", Locale.getDefault()).format(Date()),
    val students: List<AttendanceStudentDto> = emptyList(),
    val searchQuery: String = "",
    val dateRangeFilter: String = "today",
    val startDateFilter: String = SimpleDateFormat("yyyy-MM-dd", Locale.getDefault()).format(Date()),
    val endDateFilter: String = SimpleDateFormat("yyyy-MM-dd", Locale.getDefault()).format(Date()),
    val historyList: List<AttendanceHistoryItemDto> = emptyList(),
    val successMessage: String? = null,
    val errorMessage: String? = null
)

class AttendanceViewModel(
    private val attendanceRepository: AttendanceRepository
) : ViewModel() {

    private val _uiState = MutableStateFlow(AttendanceUiState())
    val uiState: StateFlow<AttendanceUiState> = _uiState.asStateFlow()

    init {
        loadAttendanceMainData()
    }

    fun loadAttendanceMainData() {
        _uiState.update { it.copy(isLoading = true, errorMessage = null) }
        viewModelScope.launch {
            when (val result = attendanceRepository.getAttendanceMainData()) {
                is Result.Success -> {
                    val data = result.data
                    val defaultClassId = data.teacher?.classId ?: data.classes.firstOrNull()?.id
                    _uiState.update {
                        it.copy(
                            isLoading = false,
                            teacher = data.teacher ?: it.teacher,
                            calendarStatus = data.calendarStatus,
                            classes = data.classes,
                            subjectAssignments = data.subjectAssignments,
                            selectedClassId = defaultClassId ?: it.selectedClassId
                        )
                    }
                    if (defaultClassId != null && defaultClassId > 0) {
                        loadStudentsForAttendance(defaultClassId)
                        loadAttendanceHistory(defaultClassId)
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

    fun onClassSelected(classId: Int?) {
        _uiState.update { it.copy(selectedClassId = classId) }
        if (classId != null && classId > 0) {
            loadStudentsForAttendance(classId)
            loadAttendanceHistory(classId)
        }
    }

    fun onAttendanceDateChanged(date: String) {
        _uiState.update { it.copy(attendanceDate = date) }
        val classId = _uiState.value.selectedClassId
        if (classId != null && classId > 0) {
            loadStudentsForAttendance(classId, date)
        }
    }

    fun loadStudentsForAttendance(
        classId: Int? = _uiState.value.selectedClassId,
        date: String = _uiState.value.attendanceDate
    ) {
        if (classId == null || classId <= 0) return
        _uiState.update { it.copy(isLoadingStudents = true, errorMessage = null) }

        viewModelScope.launch {
            when (val result = attendanceRepository.getStudentsForAttendance(classId, date)) {
                is Result.Success -> {
                    _uiState.update {
                        it.copy(
                            isLoadingStudents = false,
                            students = result.data.students
                        )
                    }
                }
                is Result.Error -> {
                    _uiState.update {
                        it.copy(
                            isLoadingStudents = false,
                            errorMessage = result.message
                        )
                    }
                }
            }
        }
    }

    fun setStudentStatus(studentId: Int, status: String) {
        _uiState.update { state ->
            val updated = state.students.map { s ->
                if (s.id == studentId) s.copy(status = status) else s
            }
            state.copy(students = updated)
        }
    }

    fun setStudentRemarks(studentId: Int, remarks: String) {
        _uiState.update { state ->
            val updated = state.students.map { s ->
                if (s.id == studentId) s.copy(remarks = remarks) else s
            }
            state.copy(students = updated)
        }
    }

    fun markAllAs(status: String) {
        _uiState.update { state ->
            val updated = state.students.map { s -> s.copy(status = status) }
            state.copy(students = updated)
        }
    }

    fun clearAllStatus() {
        _uiState.update { state ->
            val updated = state.students.map { s -> s.copy(status = null, remarks = null) }
            state.copy(students = updated)
        }
    }

    fun saveAttendance() {
        val state = _uiState.value
        val classId = state.selectedClassId
        val date = state.attendanceDate

        if (classId == null || classId <= 0) {
            _uiState.update { it.copy(errorMessage = "Please select a valid class.") }
            return
        }

        val itemsToSave = state.students.filter { !it.status.isNull_or_empty() }.map {
            SaveAttendanceItem(
                studentId = it.id,
                date = date,
                status = it.status!!,
                remarks = it.remarks
            )
        }

        if (itemsToSave.isEmpty()) {
            _uiState.update { it.copy(errorMessage = "Please mark attendance status for at least one student.") }
            return
        }

        _uiState.update { it.copy(isSubmitting = true, errorMessage = null, successMessage = null) }

        viewModelScope.launch {
            when (val result = attendanceRepository.saveAttendance(classId, date, itemsToSave)) {
                is Result.Success -> {
                    _uiState.update {
                        it.copy(
                            isSubmitting = false,
                            successMessage = result.data.message ?: "Attendance saved successfully!"
                        )
                    }
                    loadAttendanceHistory(classId)
                }
                is Result.Error -> {
                    _uiState.update {
                        it.copy(
                            isSubmitting = false,
                            errorMessage = result.message
                        )
                    }
                }
            }
        }
    }

    fun onDateRangeFilterChanged(range: String) {
        val sdf = SimpleDateFormat("yyyy-MM-dd", Locale.getDefault())
        val cal = Calendar.getInstance()
        val todayStr = sdf.format(cal.time)

        var startStr = todayStr
        var endStr = todayStr

        when (range) {
            "today" -> {
                startStr = todayStr
                endStr = todayStr
            }
            "week" -> {
                cal.set(Calendar.DAY_OF_WEEK, cal.firstDayOfWeek)
                startStr = sdf.format(cal.time)
                endStr = todayStr
            }
            "month" -> {
                cal.set(Calendar.DAY_OF_MONTH, 1)
                startStr = sdf.format(cal.time)
                endStr = todayStr
            }
            "custom" -> {
                startStr = _uiState.value.startDateFilter
                endStr = _uiState.value.endDateFilter
            }
        }

        _uiState.update {
            it.copy(
                dateRangeFilter = range,
                startDateFilter = startStr,
                endDateFilter = endStr
            )
        }

        val classId = _uiState.value.selectedClassId
        if (classId != null && classId > 0) {
            loadAttendanceHistory(classId, startStr, endStr)
        }
    }

    fun onCustomStartDateChanged(date: String) { _uiState.update { it.copy(startDateFilter = date) } }
    fun onCustomEndDateChanged(date: String) { _uiState.update { it.copy(endDateFilter = date) } }

    fun applyCustomDateRange() {
        val classId = _uiState.value.selectedClassId
        if (classId != null && classId > 0) {
            loadAttendanceHistory(classId, _uiState.value.startDateFilter, _uiState.value.endDateFilter)
        }
    }

    fun loadAttendanceHistory(
        classId: Int? = _uiState.value.selectedClassId,
        startDate: String = _uiState.value.startDateFilter,
        endDate: String = _uiState.value.endDateFilter
    ) {
        if (classId == null || classId <= 0) return
        _uiState.update { it.copy(isLoadingHistory = true, errorMessage = null) }

        viewModelScope.launch {
            when (val result = attendanceRepository.getAttendanceHistory(classId, startDate, endDate)) {
                is Result.Success -> {
                    _uiState.update {
                        it.copy(
                            isLoadingHistory = false,
                            historyList = result.data.history
                        )
                    }
                }
                is Result.Error -> {
                    _uiState.update {
                        it.copy(
                            isLoadingHistory = false,
                            errorMessage = result.message
                        )
                    }
                }
            }
        }
    }

    fun generateCsvExport(): String {
        val sb = java.lang.StringBuilder()
        sb.append("Date,Status,Count\n")
        _uiState.value.historyList.forEach { item ->
            sb.append("${item.attendanceDate ?: "-"},${item.status ?: "-"},${item.count}\n")
        }
        return sb.toString()
    }

    fun onSearchQueryChanged(query: String) { _uiState.update { it.copy(searchQuery = query) } }

    fun clearMessages() {
        _uiState.update { it.copy(errorMessage = null, successMessage = null) }
    }

    private fun String?.isNull_or_empty(): Boolean = this == null || this.trim().isEmpty()

    class Factory(
        private val attendanceRepository: AttendanceRepository
    ) : ViewModelProvider.Factory {
        @Suppress("UNCHECKED_CAST")
        override fun <T : ViewModel> create(modelClass: Class<T>): T {
            if (modelClass.isAssignableFrom(AttendanceViewModel::class.java)) {
                return AttendanceViewModel(attendanceRepository) as T
            }
            throw IllegalArgumentException("Unknown ViewModel class")
        }
    }
}
