package com.example.kenyatduhubtechers.ui.calendar

import androidx.lifecycle.ViewModel
import androidx.lifecycle.ViewModelProvider
import androidx.lifecycle.viewModelScope
import com.example.kenyatduhubtechers.data.model.CalendarStatusDto
import com.example.kenyatduhubtechers.data.model.HolidayDto
import com.example.kenyatduhubtechers.data.model.SchoolEventDto
import com.example.kenyatduhubtechers.data.model.TermDto
import com.example.kenyatduhubtechers.data.repository.CalendarRepository
import com.example.kenyatduhubtechers.data.repository.Result
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch

data class CalendarUiState(
    val isLoading: Boolean = false,
    val currentStatus: CalendarStatusDto? = null,
    val terms: List<TermDto> = emptyList(),
    val holidays: List<HolidayDto> = emptyList(),
    val schoolEvents: List<SchoolEventDto> = emptyList(),
    val errorMessage: String? = null
)

class CalendarViewModel(
    private val calendarRepository: CalendarRepository
) : ViewModel() {

    private val _uiState = MutableStateFlow(CalendarUiState())
    val uiState: StateFlow<CalendarUiState> = _uiState.asStateFlow()

    init {
        loadCalendarData()
    }

    fun loadCalendarData() {
        _uiState.update { it.copy(isLoading = true, errorMessage = null) }
        viewModelScope.launch {
            when (val result = calendarRepository.getCalendarData()) {
                is Result.Success -> {
                    val data = result.data
                    _uiState.update {
                        it.copy(
                            isLoading = false,
                            currentStatus = data.currentStatus,
                            terms = data.terms,
                            holidays = data.holidays,
                            schoolEvents = data.schoolEvents
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
        private val calendarRepository: CalendarRepository
    ) : ViewModelProvider.Factory {
        @Suppress("UNCHECKED_CAST")
        override fun <T : ViewModel> create(modelClass: Class<T>): T {
            if (modelClass.isAssignableFrom(CalendarViewModel::class.java)) {
                return CalendarViewModel(calendarRepository) as T
            }
            throw IllegalArgumentException("Unknown ViewModel class")
        }
    }
}
