package com.example.kenyaeduhubparents.ui.children

import android.content.Context
import androidx.lifecycle.ViewModel
import androidx.lifecycle.ViewModelProvider
import androidx.lifecycle.viewModelScope
import com.example.kenyaeduhubparents.data.local.SessionManager
import com.example.kenyaeduhubparents.data.model.ChildrenResponse
import com.example.kenyaeduhubparents.data.repository.ChildrenRepository
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch

sealed interface ChildrenUiState {
    object Loading : ChildrenUiState
    data class Success(val data: ChildrenResponse) : ChildrenUiState
    data class Error(val message: String) : ChildrenUiState
}

class ChildrenViewModel(private val repository: ChildrenRepository) : ViewModel() {
    private val _uiState = MutableStateFlow<ChildrenUiState>(ChildrenUiState.Loading)
    val uiState: StateFlow<ChildrenUiState> = _uiState.asStateFlow()

    init {
        loadChildren()
    }

    fun loadChildren() {
        viewModelScope.launch {
            _uiState.value = ChildrenUiState.Loading
            val result = repository.getChildrenData()
            result.onSuccess { response ->
                if (response.success) {
                    _uiState.value = ChildrenUiState.Success(response)
                } else {
                    _uiState.value = ChildrenUiState.Error(response.error ?: "Failed to load children records")
                }
            }.onFailure { e ->
                _uiState.value = ChildrenUiState.Error(e.localizedMessage ?: "Unknown error occurred")
            }
        }
    }

    class Factory(private val context: Context) : ViewModelProvider.Factory {
        @Suppress("UNCHECKED_CAST")
        override fun <T : ViewModel> create(modelClass: Class<T>): T {
            if (modelClass.isAssignableFrom(ChildrenViewModel::class.java)) {
                val sessionManager = SessionManager(context)
                val repository = ChildrenRepository(sessionManager)
                return ChildrenViewModel(repository) as T
            }
            throw IllegalArgumentException("Unknown ViewModel class")
        }
    }
}
