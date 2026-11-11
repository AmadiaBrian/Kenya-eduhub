package com.example.kenyaeduhubparents.ui.performance

import android.content.Context
import androidx.lifecycle.ViewModel
import androidx.lifecycle.ViewModelProvider
import androidx.lifecycle.viewModelScope
import com.example.kenyaeduhubparents.data.local.SessionManager
import com.example.kenyaeduhubparents.data.model.PerformanceResponse
import com.example.kenyaeduhubparents.data.repository.PerformanceRepository
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch

sealed interface PerformanceUiState {
    object Loading : PerformanceUiState
    data class Success(val data: PerformanceResponse) : PerformanceUiState
    data class Error(val message: String) : PerformanceUiState
}

class PerformanceViewModel(private val repository: PerformanceRepository) : ViewModel() {

    private val _uiState = MutableStateFlow<PerformanceUiState>(PerformanceUiState.Loading)
    val uiState: StateFlow<PerformanceUiState> = _uiState.asStateFlow()

    var selectedChildId: String? = null
    var selectedTerm: String? = null
    var selectedYear: String? = null
    var selectedExamType: String? = null

    init {
        loadPerformance()
    }

    fun loadPerformance() {
        viewModelScope.launch {
            _uiState.value = PerformanceUiState.Loading
            val result = repository.getPerformanceData(
                childId = selectedChildId,
                term = selectedTerm,
                year = selectedYear,
                examType = selectedExamType
            )
            result.onSuccess { response ->
                if (response.success) {
                    _uiState.value = PerformanceUiState.Success(response)
                    if (selectedTerm == null && !response.currentTerm.isNullOrEmpty()) {
                        selectedTerm = response.currentTerm
                    }
                    if (selectedYear == null) {
                        selectedYear = response.currentYear.toString()
                    }
                } else {
                    _uiState.value = PerformanceUiState.Error(response.error ?: "Failed to load performance records")
                }
            }.onFailure { e ->
                _uiState.value = PerformanceUiState.Error(e.localizedMessage ?: "Unknown error occurred")
            }
        }
    }

    fun updateFilters(childId: String?, term: String?, year: String?, examType: String?) {
        selectedChildId = childId.takeIf { !it.isNullOrBlank() }
        selectedTerm = term.takeIf { !it.isNullOrBlank() }
        selectedYear = year.takeIf { !it.isNullOrBlank() }
        selectedExamType = examType.takeIf { !it.isNullOrBlank() }
        loadPerformance()
    }

    class Factory(private val context: Context) : ViewModelProvider.Factory {
        @Suppress("UNCHECKED_CAST")
        override fun <T : ViewModel> create(modelClass: Class<T>): T {
            if (modelClass.isAssignableFrom(PerformanceViewModel::class.java)) {
                val sessionManager = SessionManager(context)
                val repository = PerformanceRepository(sessionManager)
                return PerformanceViewModel(repository) as T
            }
            throw IllegalArgumentException("Unknown ViewModel class")
        }
    }
}
