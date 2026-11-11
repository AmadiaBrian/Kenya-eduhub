package com.example.kenyaeduhubparents.ui.assignments

import android.content.Context
import androidx.lifecycle.ViewModel
import androidx.lifecycle.ViewModelProvider
import androidx.lifecycle.viewModelScope
import com.example.kenyaeduhubparents.data.local.SessionManager
import com.example.kenyaeduhubparents.data.model.AssignmentsResponse
import com.example.kenyaeduhubparents.data.repository.AssignmentsRepository
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch

sealed interface AssignmentsUiState {
    object Loading : AssignmentsUiState
    data class Success(val data: AssignmentsResponse) : AssignmentsUiState
    data class Error(val message: String) : AssignmentsUiState
}

class AssignmentsViewModel(private val repository: AssignmentsRepository) : ViewModel() {

    private val _uiState = MutableStateFlow<AssignmentsUiState>(AssignmentsUiState.Loading)
    val uiState: StateFlow<AssignmentsUiState> = _uiState.asStateFlow()

    init {
        loadAssignments()
    }

    fun loadAssignments() {
        viewModelScope.launch {
            _uiState.value = AssignmentsUiState.Loading
            val result = repository.getAssignmentsData()
            result.onSuccess { response ->
                if (response.success) {
                    _uiState.value = AssignmentsUiState.Success(response)
                } else {
                    _uiState.value = AssignmentsUiState.Error(response.error ?: "Failed to load assignments")
                }
            }.onFailure { e ->
                _uiState.value = AssignmentsUiState.Error(e.localizedMessage ?: "Unknown error occurred")
            }
        }
    }

    class Factory(private val context: Context) : ViewModelProvider.Factory {
        @Suppress("UNCHECKED_CAST")
        override fun <T : ViewModel> create(modelClass: Class<T>): T {
            if (modelClass.isAssignableFrom(AssignmentsViewModel::class.java)) {
                val sessionManager = SessionManager(context)
                val repository = AssignmentsRepository(sessionManager)
                return AssignmentsViewModel(repository) as T
            }
            throw IllegalArgumentException("Unknown ViewModel class")
        }
    }
}
