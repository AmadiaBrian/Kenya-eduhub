package com.example.kenyatduhubtechers.ui.timetable

import androidx.lifecycle.ViewModel
import androidx.lifecycle.ViewModelProvider
import androidx.lifecycle.viewModelScope
import com.example.kenyatduhubtechers.data.model.CalendarStatusDto
import com.example.kenyatduhubtechers.data.model.SchoolBreakDto
import com.example.kenyatduhubtechers.data.model.TimetableAssignmentDto
import com.example.kenyatduhubtechers.data.repository.Result
import com.example.kenyatduhubtechers.data.repository.TimetableRepository
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch

data class TimetableUiState(
    val isLoading: Boolean = false,
    val calendarStatus: CalendarStatusDto? = null,
    val schoolBreaks: List<SchoolBreakDto> = emptyList(),
    val timetableAssignments: List<TimetableAssignmentDto> = emptyList(),
    val errorMessage: String? = null
)

class TimetableViewModel(
    private val timetableRepository: TimetableRepository
) : ViewModel() {

    private val _uiState = MutableStateFlow(TimetableUiState())
    val uiState: StateFlow<TimetableUiState> = _uiState.asStateFlow()

    init {
        loadTimetableData()
    }

    fun loadTimetableData() {
        _uiState.update { it.copy(isLoading = true, errorMessage = null) }
        viewModelScope.launch {
            when (val result = timetableRepository.getTimetableData()) {
                is Result.Success -> {
                    val data = result.data
                    _uiState.update {
                        it.copy(
                            isLoading = false,
                            calendarStatus = data.calendarStatus,
                            schoolBreaks = data.schoolBreaks,
                            timetableAssignments = data.timetableAssignments
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

    class Factory(
        private val timetableRepository: TimetableRepository
    ) : ViewModelProvider.Factory {
        @Suppress("UNCHECKED_CAST")
        override fun <T : ViewModel> create(modelClass: Class<T>): T {
            if (modelClass.isAssignableFrom(TimetableViewModel::class.java)) {
                return TimetableViewModel(timetableRepository) as T
            }
            throw IllegalArgumentException("Unknown ViewModel class")
        }
    }
}
