package com.example.kenyaeduhubparents.ui.attendance

import android.content.Context
import androidx.lifecycle.ViewModel
import androidx.lifecycle.ViewModelProvider
import androidx.lifecycle.viewModelScope
import com.example.kenyaeduhubparents.data.local.SessionManager
import com.example.kenyaeduhubparents.data.model.AttendanceResponse
import com.example.kenyaeduhubparents.data.repository.AttendanceRepository
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch

sealed interface AttendanceUiState {
    object Loading : AttendanceUiState
    data class Success(val data: AttendanceResponse) : AttendanceUiState
    data class Error(val message: String) : AttendanceUiState
}

class AttendanceViewModel(private val repository: AttendanceRepository) : ViewModel() {
    private val _uiState = MutableStateFlow<AttendanceUiState>(AttendanceUiState.Loading)
    val uiState: StateFlow<AttendanceUiState> = _uiState.asStateFlow()

    var selectedChildId: String? = null
    var selectedTerm: String? = null
    var selectedYear: String? = null

    init {
        loadAttendance()
    }

    fun loadAttendance(childId: String? = selectedChildId, term: String? = selectedTerm, year: String? = selectedYear) {
        viewModelScope.launch {
            _uiState.value = AttendanceUiState.Loading
            val result = repository.getAttendanceData(childId, term, year)
            result.onSuccess { response ->
                if (response.success) {
                    _uiState.value = AttendanceUiState.Success(response)
                    if (selectedChildId == null) {
                        selectedChildId = response.selectedChildId?.toString() ?: response.children.firstOrNull()?.id?.toString()
                    }
                    if (selectedTerm == null) {
                        selectedTerm = response.currentTerm ?: response.terms.firstOrNull()
                    }
                    if (selectedYear == null) {
                        selectedYear = response.currentYear.toString()
                    }
                } else {
                    _uiState.value = AttendanceUiState.Error(response.error ?: "Failed to load attendance records")
                }
            }.onFailure { e ->
                _uiState.value = AttendanceUiState.Error(e.localizedMessage ?: "Network error. Please try again.")
            }
        }
    }

    fun updateFilters(childId: String?, term: String?, year: String?) {
        selectedChildId = childId
        selectedTerm = term
        selectedYear = year
        loadAttendance(childId, term, year)
    }

    class Factory(private val context: Context) : ViewModelProvider.Factory {
        @Suppress("UNCHECKED_CAST")
        override fun <T : ViewModel> create(modelClass: Class<T>): T {
            if (modelClass.isAssignableFrom(AttendanceViewModel::class.java)) {
                val sessionManager = SessionManager(context)
                val repository = AttendanceRepository(sessionManager)
                return AttendanceViewModel(repository) as T
            }
            throw IllegalArgumentException("Unknown ViewModel class")
        }
    }
}
