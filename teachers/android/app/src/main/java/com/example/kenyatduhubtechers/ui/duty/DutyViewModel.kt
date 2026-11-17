package com.example.kenyatduhubtechers.ui.duty

import androidx.lifecycle.ViewModel
import androidx.lifecycle.ViewModelProvider
import androidx.lifecycle.viewModelScope
import com.example.kenyatduhubtechers.data.model.CleanlinessCheckDto
import com.example.kenyatduhubtechers.data.model.DutyAssignmentDto
import com.example.kenyatduhubtechers.data.model.DutyStudentDto
import com.example.kenyatduhubtechers.data.model.IncidentReportDto
import com.example.kenyatduhubtechers.data.model.LeaveoutChitDto
import com.example.kenyatduhubtechers.data.model.TeacherDto
import com.example.kenyatduhubtechers.data.repository.DutyRepository
import com.example.kenyatduhubtechers.data.repository.Result
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch

data class DutyUiState(
    val isLoading: Boolean = false,
    val isSubmitting: Boolean = false,
    val teacher: TeacherDto? = null,
    val currentDuty: DutyAssignmentDto? = null,
    val students: List<DutyStudentDto> = emptyList(),
    val leaveoutChits: List<LeaveoutChitDto> = emptyList(),
    val incidents: List<IncidentReportDto> = emptyList(),
    val cleanlinessChecks: List<CleanlinessCheckDto> = emptyList(),
    val selectedStudentIdForChit: Int? = null,
    val chitReason: String = "",
    val incidentType: String = "",
    val incidentSeverity: String = "low",
    val incidentDescription: String = "",
    val cleanlinessArea: String = "",
    val cleanlinessStatus: String = "good",
    val cleanlinessNotes: String = "",
    val selectedChitToPrint: LeaveoutChitDto? = null,
    val showPrintChitDialog: Boolean = false,
    val successMessage: String? = null,
    val errorMessage: String? = null
)

class DutyViewModel(
    private val dutyRepository: DutyRepository
) : ViewModel() {

    private val _uiState = MutableStateFlow(DutyUiState())
    val uiState: StateFlow<DutyUiState> = _uiState.asStateFlow()

    init {
        loadDutyData()
    }

    fun loadDutyData() {
        _uiState.update { it.copy(isLoading = true, errorMessage = null) }
        viewModelScope.launch {
            when (val result = dutyRepository.getDutyData()) {
                is Result.Success -> {
                    val data = result.data.data
                    _uiState.update {
                        it.copy(
                            isLoading = false,
                            teacher = data?.teacher ?: it.teacher,
                            currentDuty = data?.currentDuty,
                            students = data?.students ?: emptyList(),
                            leaveoutChits = data?.leaveoutChits ?: emptyList(),
                            incidents = data?.incidents ?: emptyList(),
                            cleanlinessChecks = data?.cleanlinessChecks ?: emptyList()
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

    fun onStudentForChitSelected(studentId: Int?) { _uiState.update { it.copy(selectedStudentIdForChit = studentId) } }
    fun onChitReasonChanged(reason: String) { _uiState.update { it.copy(chitReason = reason) } }

    fun createLeaveoutChit() {
        val state = _uiState.value
        val studentId = state.selectedStudentIdForChit
        val reason = state.chitReason.trim()

        if (studentId == null || studentId <= 0) {
            _uiState.update { it.copy(errorMessage = "Please select a student for the leaveout chit.") }
            return
        }

        if (reason.isBlank()) {
            _uiState.update { it.copy(errorMessage = "Please enter a reason for the leaveout chit.") }
            return
        }

        _uiState.update { it.copy(isSubmitting = true, errorMessage = null, successMessage = null) }

        viewModelScope.launch {
            when (val result = dutyRepository.createLeaveoutChit(studentId, reason)) {
                is Result.Success -> {
                    _uiState.update {
                        it.copy(
                            isSubmitting = false,
                            selectedStudentIdForChit = null,
                            chitReason = "",
                            successMessage = result.data.message ?: "Leaveout chit created successfully!"
                        )
                    }
                    loadDutyData()
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

    fun onIncidentTypeChanged(type: String) { _uiState.update { it.copy(incidentType = type) } }
    fun onIncidentSeverityChanged(severity: String) { _uiState.update { it.copy(incidentSeverity = severity) } }
    fun onIncidentDescriptionChanged(desc: String) { _uiState.update { it.copy(incidentDescription = desc) } }

    fun reportIncident() {
        val state = _uiState.value
        val type = state.incidentType.trim()
        val severity = state.incidentSeverity.trim()
        val description = state.incidentDescription.trim()

        if (type.isBlank()) {
            _uiState.update { it.copy(errorMessage = "Please select an incident type.") }
            return
        }

        if (description.isBlank()) {
            _uiState.update { it.copy(errorMessage = "Please enter an incident description.") }
            return
        }

        _uiState.update { it.copy(isSubmitting = true, errorMessage = null, successMessage = null) }

        viewModelScope.launch {
            when (val result = dutyRepository.reportIncident(type, description, severity)) {
                is Result.Success -> {
                    _uiState.update {
                        it.copy(
                            isSubmitting = false,
                            incidentType = "",
                            incidentSeverity = "low",
                            incidentDescription = "",
                            successMessage = result.data.message ?: "Incident reported successfully!"
                        )
                    }
                    loadDutyData()
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

    fun onCleanlinessAreaChanged(area: String) { _uiState.update { it.copy(cleanlinessArea = area) } }
    fun onCleanlinessStatusChanged(status: String) { _uiState.update { it.copy(cleanlinessStatus = status) } }
    fun onCleanlinessNotesChanged(notes: String) { _uiState.update { it.copy(cleanlinessNotes = notes) } }

    fun recordCleanliness() {
        val state = _uiState.value
        val area = state.cleanlinessArea.trim()
        val status = state.cleanlinessStatus.trim()
        val notes = state.cleanlinessNotes.trim()

        if (area.isBlank()) {
            _uiState.update { it.copy(errorMessage = "Please select an area for cleanliness check.") }
            return
        }

        _uiState.update { it.copy(isSubmitting = true, errorMessage = null, successMessage = null) }

        viewModelScope.launch {
            when (val result = dutyRepository.recordCleanliness(area, status, notes)) {
                is Result.Success -> {
                    _uiState.update {
                        it.copy(
                            isSubmitting = false,
                            cleanlinessArea = "",
                            cleanlinessStatus = "good",
                            cleanlinessNotes = "",
                            successMessage = result.data.message ?: "Cleanliness check recorded!"
                        )
                    }
                    loadDutyData()
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

    fun openPrintChitDialog(chit: LeaveoutChitDto) {
        _uiState.update {
            it.copy(
                selectedChitToPrint = chit,
                showPrintChitDialog = true
            )
        }
    }

    fun closePrintChitDialog() {
        _uiState.update {
            it.copy(
                selectedChitToPrint = null,
                showPrintChitDialog = false
            )
        }
    }

    fun clearMessages() {
        _uiState.update { it.copy(errorMessage = null, successMessage = null) }
    }

    class Factory(
        private val dutyRepository: DutyRepository
    ) : ViewModelProvider.Factory {
        @Suppress("UNCHECKED_CAST")
        override fun <T : ViewModel> create(modelClass: Class<T>): T {
            if (modelClass.isAssignableFrom(DutyViewModel::class.java)) {
                return DutyViewModel(dutyRepository) as T
            }
            throw IllegalArgumentException("Unknown ViewModel class")
        }
    }
}
