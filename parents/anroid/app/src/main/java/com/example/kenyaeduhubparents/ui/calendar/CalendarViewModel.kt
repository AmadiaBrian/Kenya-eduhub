package com.example.kenyaeduhubparents.ui.calendar

import android.content.Context
import androidx.lifecycle.ViewModel
import androidx.lifecycle.ViewModelProvider
import androidx.lifecycle.viewModelScope
import com.example.kenyaeduhubparents.data.local.SessionManager
import com.example.kenyaeduhubparents.data.model.CalendarResponse
import com.example.kenyaeduhubparents.data.repository.CalendarRepository
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch

sealed interface CalendarUiState {
    object Loading : CalendarUiState
    data class Success(val data: CalendarResponse) : CalendarUiState
    data class Error(val message: String) : CalendarUiState
}

class CalendarViewModel(private val repository: CalendarRepository) : ViewModel() {
    private val _uiState = MutableStateFlow<CalendarUiState>(CalendarUiState.Loading)
    val uiState: StateFlow<CalendarUiState> = _uiState.asStateFlow()

    init {
        loadCalendar()
    }

    fun loadCalendar() {
        viewModelScope.launch {
            _uiState.value = CalendarUiState.Loading
            val result = repository.getCalendarData()
            result.onSuccess { response ->
                if (response.success) {
                    _uiState.value = CalendarUiState.Success(response)
                } else {
                    _uiState.value = CalendarUiState.Error(response.error ?: "Failed to load calendar")
                }
            }.onFailure { e ->
                _uiState.value = CalendarUiState.Error(e.localizedMessage ?: "Unknown error occurred")
            }
        }
    }

    class Factory(private val context: Context) : ViewModelProvider.Factory {
        @Suppress("UNCHECKED_CAST")
        override fun <T : ViewModel> create(modelClass: Class<T>): T {
            if (modelClass.isAssignableFrom(CalendarViewModel::class.java)) {
                val sessionManager = SessionManager(context)
                val repository = CalendarRepository(sessionManager)
                return CalendarViewModel(repository) as T
            }
            throw IllegalArgumentException("Unknown ViewModel class")
        }
    }
}
