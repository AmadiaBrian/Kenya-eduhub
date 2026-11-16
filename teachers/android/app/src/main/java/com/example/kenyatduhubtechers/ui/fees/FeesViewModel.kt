package com.example.kenyatduhubtechers.ui.fees

import androidx.lifecycle.ViewModel
import androidx.lifecycle.ViewModelProvider
import androidx.lifecycle.viewModelScope
import com.example.kenyatduhubtechers.data.model.FeeTypeSummaryDto
import com.example.kenyatduhubtechers.data.model.SendFeeReminderRequest
import com.example.kenyatduhubtechers.data.model.StudentAffairsItemDto
import com.example.kenyatduhubtechers.data.model.StudentFeeDetailsDto
import com.example.kenyatduhubtechers.data.model.TeacherDto
import com.example.kenyatduhubtechers.data.repository.FeesRepository
import com.example.kenyatduhubtechers.data.repository.Result
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch

data class FeesUiState(
    val isLoading: Boolean = false,
    val isSendingReminders: Boolean = false,
    val isGeneratingStatement: Boolean = false,
    val teacher: TeacherDto? = null,
    val students: List<StudentAffairsItemDto> = emptyList(),
    val streamFeeSummary: List<FeeTypeSummaryDto> = emptyList(),
    val studentFeeDetails: StudentFeeDetailsDto? = null,
    val selectedStudentId: Int? = null,
    val studentSearchQuery: String = "",
    val feeTypeFilter: String = "",
    val paymentStatusFilter: String = "",
    val successMessage: String? = null,
    val errorMessage: String? = null
)

class FeesViewModel(
    private val feesRepository: FeesRepository
) : ViewModel() {

    private val _uiState = MutableStateFlow(FeesUiState())
    val uiState: StateFlow<FeesUiState> = _uiState.asStateFlow()

    init {
        loadFeesData()
    }

    fun loadFeesData(selectedStudentId: Int? = _uiState.value.selectedStudentId) {
        _uiState.update { it.copy(isLoading = true, errorMessage = null) }
        viewModelScope.launch {
            when (val result = feesRepository.getFeesData(selectedStudentId)) {
                is Result.Success -> {
                    val data = result.data
                    _uiState.update {
                        it.copy(
                            isLoading = false,
                            teacher = data.teacher ?: it.teacher,
                            students = data.students,
                            streamFeeSummary = data.streamFeeSummary,
                            studentFeeDetails = data.studentFeeDetails,
                            selectedStudentId = selectedStudentId
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

    fun onStudentSelected(studentId: Int?) {
        _uiState.update { it.copy(selectedStudentId = studentId) }
        loadFeesData(studentId)
    }

    fun onStudentSearchChanged(query: String) { _uiState.update { it.copy(studentSearchQuery = query) } }
    fun onFeeTypeFilterChanged(feeType: String) { _uiState.update { it.copy(feeTypeFilter = feeType) } }
    fun onPaymentStatusFilterChanged(status: String) { _uiState.update { it.copy(paymentStatusFilter = status) } }

    fun sendFeeReminders() {
        val state = _uiState.value
        _uiState.update { it.copy(isSendingReminders = true, errorMessage = null, successMessage = null) }

        viewModelScope.launch {
            val req = SendFeeReminderRequest(
                feeType = if (state.feeTypeFilter.isBlank()) "All Fee Types" else state.feeTypeFilter,
                status = if (state.paymentStatusFilter.isBlank()) "All Status" else state.paymentStatusFilter
            )

            when (val result = feesRepository.sendFeeReminders(req)) {
                is Result.Success -> {
                    _uiState.update {
                        it.copy(
                            isSendingReminders = false,
                            successMessage = result.data.message ?: "Fee reminders sent successfully!"
                        )
                    }
                }
                is Result.Error -> {
                    _uiState.update {
                        it.copy(
                            isSendingReminders = false,
                            errorMessage = result.message
                        )
                    }
                }
            }
        }
    }

    fun generateClassFeeStatement(onSuccess: (String, String?) -> Unit) {
        _uiState.update { it.copy(isGeneratingStatement = true, errorMessage = null) }
        viewModelScope.launch {
            when (val result = feesRepository.generateClassFeeStatement()) {
                is Result.Success -> {
                    val url = result.data.statementUrl ?: ""
                    val filename = result.data.statementFilename
                    _uiState.update {
                        it.copy(
                            isGeneratingStatement = false,
                            successMessage = "Class fee statement generated successfully!"
                        )
                    }
                    if (url.isNotBlank()) {
                        onSuccess(url, filename)
                    }
                }
                is Result.Error -> {
                    _uiState.update {
                        it.copy(
                            isGeneratingStatement = false,
                            errorMessage = result.message
                        )
                    }
                }
            }
        }
    }

    fun generateStudentFeeStatement(studentId: Int, onSuccess: (String, String?) -> Unit) {
        _uiState.update { it.copy(isGeneratingStatement = true, errorMessage = null) }
        viewModelScope.launch {
            when (val result = feesRepository.generateStudentFeeStatement(studentId)) {
                is Result.Success -> {
                    val url = result.data.statementUrl ?: ""
                    val filename = result.data.statementFilename
                    _uiState.update {
                        it.copy(
                            isGeneratingStatement = false,
                            successMessage = "Student fee statement generated successfully!"
                        )
                    }
                    if (url.isNotBlank()) {
                        onSuccess(url, filename)
                    }
                }
                is Result.Error -> {
                    _uiState.update {
                        it.copy(
                            isGeneratingStatement = false,
                            errorMessage = result.message
                        )
                    }
                }
            }
        }
    }

    fun generateCsvSummaryExport(): String {
        val sb = StringBuilder()
        sb.append("Admission No,Student Name,Stream,Fee Type,Total Fees,Total Paid,Total Balance,Status\n")
        val summary = _uiState.value.streamFeeSummary
        summary.forEach { group ->
            group.students.forEach { item ->
                val statusText = if (item.totalBalance <= 0) "Paid" else if (item.totalPaid > 0) "Partial" else "Not Paid"
                sb.append("${item.admissionNumber},${item.studentName},${item.streamName ?: "-"},${group.feeType},${item.totalFee},${item.totalPaid},${item.totalBalance},$statusText\n")
            }
        }
        return sb.toString()
    }

    fun clearMessages() {
        _uiState.update { it.copy(errorMessage = null, successMessage = null) }
    }

    class Factory(
        private val feesRepository: FeesRepository
    ) : ViewModelProvider.Factory {
        @Suppress("UNCHECKED_CAST")
        override fun <T : ViewModel> create(modelClass: Class<T>): T {
            if (modelClass.isAssignableFrom(FeesViewModel::class.java)) {
                return FeesViewModel(feesRepository) as T
            }
            throw IllegalArgumentException("Unknown ViewModel class")
        }
    }
}
