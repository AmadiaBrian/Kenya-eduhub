package com.example.kenyaeduhubparents.ui.results

import android.content.Context
import androidx.lifecycle.ViewModel
import androidx.lifecycle.ViewModelProvider
import androidx.lifecycle.viewModelScope
import com.example.kenyaeduhubparents.data.local.SessionManager
import com.example.kenyaeduhubparents.data.model.ResultsResponse
import com.example.kenyaeduhubparents.data.repository.ResultsRepository
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch

sealed interface ResultsUiState {
    object Loading : ResultsUiState
    data class Success(val data: ResultsResponse) : ResultsUiState
    data class Error(val message: String) : ResultsUiState
}

class ResultsViewModel(private val repository: ResultsRepository) : ViewModel() {

    private val _uiState = MutableStateFlow<ResultsUiState>(ResultsUiState.Loading)
    val uiState: StateFlow<ResultsUiState> = _uiState.asStateFlow()

    var selectedChildId: String? = null
    var selectedTerm: String? = null
    var selectedYear: String? = null
    var selectedExamType: String? = null

    init {
        loadResults()
    }

    fun loadResults() {
        viewModelScope.launch {
            _uiState.value = ResultsUiState.Loading
            val result = repository.getResultsData("stream")
            result.onSuccess { response ->
                if (response.success) {
                    _uiState.value = ResultsUiState.Success(response)
                } else {
                    _uiState.value = ResultsUiState.Error(response.error ?: "Failed to load results")
                }
            }.onFailure { e ->
                _uiState.value = ResultsUiState.Error(e.localizedMessage ?: "Unknown error occurred")
            }
        }
    }

    fun updateFilters(childId: String?, term: String?, year: String?, examType: String?) {
        selectedChildId = childId.takeIf { !it.isNullOrBlank() }
        selectedTerm = term.takeIf { !it.isNullOrBlank() && it != "All Terms" }
        selectedYear = year.takeIf { !it.isNullOrBlank() && it != "All Years" }
        selectedExamType = examType.takeIf { !it.isNullOrBlank() }
    }

    class Factory(private val context: Context) : ViewModelProvider.Factory {
        @Suppress("UNCHECKED_CAST")
        override fun <T : ViewModel> create(modelClass: Class<T>): T {
            if (modelClass.isAssignableFrom(ResultsViewModel::class.java)) {
                val sessionManager = SessionManager(context)
                val repository = ResultsRepository(sessionManager)
                return ResultsViewModel(repository) as T
            }
            throw IllegalArgumentException("Unknown ViewModel class")
        }
    }
}
