package com.example.kenyatduhubtechers.ui.students

import androidx.lifecycle.ViewModel
import androidx.lifecycle.ViewModelProvider
import androidx.lifecycle.viewModelScope
import com.example.kenyatduhubtechers.data.model.ClassDto
import com.example.kenyatduhubtechers.data.model.DisciplinaryRecordDto
import com.example.kenyatduhubtechers.data.model.StreamDto
import com.example.kenyatduhubtechers.data.model.StudentAffairsItemDto
import com.example.kenyatduhubtechers.data.repository.Result
import com.example.kenyatduhubtechers.data.repository.StudentsRepository
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch

data class StudentsUiState(
    val isLoading: Boolean = false,
    val students: List<StudentAffairsItemDto> = emptyList(),
    val classes: List<ClassDto> = emptyList(),
    val streams: List<StreamDto> = emptyList(),
    val disciplinaryRecords: List<DisciplinaryRecordDto> = emptyList(),
    val selectedStudentDetail: StudentAffairsItemDto? = null,
    val showDetailsDialog: Boolean = false,
    val searchQuery: String = "",
    val classFilter: String = "",
    val streamFilter: String = "",
    val genderFilter: String = "",
    val statusFilter: String = "",
    val errorMessage: String? = null
)

class StudentsViewModel(
    private val studentsRepository: StudentsRepository
) : ViewModel() {

    private val _uiState = MutableStateFlow(StudentsUiState())
    val uiState: StateFlow<StudentsUiState> = _uiState.asStateFlow()

    init {
        loadStudentsData()
    }

    fun loadStudentsData() {
        _uiState.update { it.copy(isLoading = true, errorMessage = null) }
        viewModelScope.launch {
            when (val result = studentsRepository.getStudentsAffairsData()) {
                is Result.Success -> {
                    val data = result.data
                    _uiState.update {
                        it.copy(
                            isLoading = false,
                            students = data.data,
                            classes = data.classes,
                            streams = data.streams,
                            disciplinaryRecords = data.disciplinaryRecords
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

    fun onSearchQueryChanged(query: String) { _uiState.update { it.copy(searchQuery = query) } }
    fun onClassFilterChanged(cls: String) { _uiState.update { it.copy(classFilter = cls) } }
    fun onStreamFilterChanged(stream: String) { _uiState.update { it.copy(streamFilter = stream) } }
    fun onGenderFilterChanged(gender: String) { _uiState.update { it.copy(genderFilter = gender) } }
    fun onStatusFilterChanged(status: String) { _uiState.update { it.copy(statusFilter = status) } }

    fun resetFilters() {
        _uiState.update {
            it.copy(
                searchQuery = "",
                classFilter = "",
                streamFilter = "",
                genderFilter = "",
                statusFilter = ""
            )
        }
    }

    fun openStudentDetails(student: StudentAffairsItemDto) {
        _uiState.update { it.copy(selectedStudentDetail = student, showDetailsDialog = true) }
    }

    fun closeStudentDetails() {
        _uiState.update { it.copy(showDetailsDialog = false, selectedStudentDetail = null) }
    }

    fun generateCsvExport(students: List<StudentAffairsItemDto>): String {
        val sb = StringBuilder()
        sb.append("Admission No,Name,Gender,Class,Stream,Status,Total Fees,Total Paid,Balance\n")
        students.forEach { student ->
            var totalFees = 0.0
            var totalPaid = 0.0
            var balance = 0.0
            student.feeBalances?.values?.forEach { fee ->
                totalFees += fee.totalFees
                totalPaid += fee.totalPaid
                balance += fee.balance
            }
            sb.append("${student.admissionNumber},${student.fullName},${student.gender ?: "-"},${student.className ?: "-"},${student.streamName ?: "-"},${student.status ?: "active"},${String.format("%.2f", totalFees)},${String.format("%.2f", totalPaid)},${String.format("%.2f", balance)}\n")
        }
        return sb.toString()
    }

    fun clearMessages() {
        _uiState.update { it.copy(errorMessage = null) }
    }

    class Factory(
        private val studentsRepository: StudentsRepository
    ) : ViewModelProvider.Factory {
        @Suppress("UNCHECKED_CAST")
        override fun <T : ViewModel> create(modelClass: Class<T>): T {
            if (modelClass.isAssignableFrom(StudentsViewModel::class.java)) {
                return StudentsViewModel(studentsRepository) as T
            }
            throw IllegalArgumentException("Unknown ViewModel class")
        }
    }
}
