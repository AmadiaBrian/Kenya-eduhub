package com.example.kenyatduhubtechers.ui.dashboard

import androidx.lifecycle.ViewModel
import androidx.lifecycle.ViewModelProvider
import androidx.lifecycle.viewModelScope
import com.example.kenyatduhubtechers.data.model.CalendarStatusDto
import com.example.kenyatduhubtechers.data.model.StatsDto
import com.example.kenyatduhubtechers.data.model.TeacherDto
import com.example.kenyatduhubtechers.data.repository.DashboardRepository
import com.example.kenyatduhubtechers.data.repository.Result
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch

data class DashboardUiState(
    val isLoading: Boolean = false,
    val teacher: TeacherDto? = null,
    val calendarStatus: CalendarStatusDto? = null,
    val stats: StatsDto? = null,
    val errorMessage: String? = null
)

class DashboardViewModel(
    private val dashboardRepository: DashboardRepository,
    initialTeacher: TeacherDto?
) : ViewModel() {

    private val _uiState = MutableStateFlow(DashboardUiState(teacher = initialTeacher))
    val uiState: StateFlow<DashboardUiState> = _uiState.asStateFlow()

    init {
        loadDashboardData()
    }

    fun loadDashboardData() {
        _uiState.update { it.copy(isLoading = true, errorMessage = null) }
        viewModelScope.launch {
            when (val result = dashboardRepository.getDashboardData()) {
                is Result.Success -> {
                    val data = result.data
                    _uiState.update {
                        it.copy(
                            isLoading = false,
                            teacher = data.teacher ?: it.teacher,
                            calendarStatus = data.calendarStatus,
                            stats = data.stats
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
        private val dashboardRepository: DashboardRepository,
        private val initialTeacher: TeacherDto?
    ) : ViewModelProvider.Factory {
        @Suppress("UNCHECKED_CAST")
        override fun <T : ViewModel> create(modelClass: Class<T>): T {
            if (modelClass.isAssignableFrom(DashboardViewModel::class.java)) {
                return DashboardViewModel(dashboardRepository, initialTeacher) as T
            }
            throw IllegalArgumentException("Unknown ViewModel class")
        }
    }
}
