package com.example.kenyatduhubtechers.ui.results

import androidx.lifecycle.ViewModel
import androidx.lifecycle.ViewModelProvider
import androidx.lifecycle.viewModelScope
import com.example.kenyatduhubtechers.data.model.AggregateDistributionDto
import com.example.kenyatduhubtechers.data.model.ClassDto
import com.example.kenyatduhubtechers.data.model.ExamTypeDto
import com.example.kenyatduhubtechers.data.model.GradingScaleDto
import com.example.kenyatduhubtechers.data.model.PerformanceRecordDto
import com.example.kenyatduhubtechers.data.model.StreamDto
import com.example.kenyatduhubtechers.data.model.StudentSubjectAssignmentDto
import com.example.kenyatduhubtechers.data.model.TeacherDto
import com.example.kenyatduhubtechers.data.repository.Result
import com.example.kenyatduhubtechers.data.repository.ResultsRepository
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch

data class StudentSubjectResult(
    val subjectName: String,
    val marks: Double,
    val grade: String,
    val points: Int,
    val isCompulsory: Boolean,
    val isGradingSubject: Boolean
)

data class StudentAggregateResult(
    val rank: Int = 0,
    val admissionNumber: String,
    val studentName: String,
    val className: String,
    val streamName: String,
    val subjectMap: Map<String, StudentSubjectResult> = emptyMap(),
    val totalMarks: Double = 0.0,
    val average: Double = 0.0,
    val totalPoints: Int = 0,
    val aggregateGrade: String = "-",
    val subjectCount: Int = 0,
    val assignedSubjectCount: Int = 0
)

data class ExamGroupedResults(
    val examTypeName: String,
    val subjects: List<String>,
    val studentResults: List<StudentAggregateResult>
)

data class ResultsUiState(
    val isLoading: Boolean = false,
    val teacher: TeacherDto? = null,
    val terms: List<String> = emptyList(),
    val classes: List<ClassDto> = emptyList(),
    val streams: List<StreamDto> = emptyList(),
    val examTypes: List<ExamTypeDto> = emptyList(),
    val gradingScales: List<GradingScaleDto> = emptyList(),
    val aggregateDistribution: List<AggregateDistributionDto> = emptyList(),
    val minSubjects: Int = 7,
    val maxSubjects: Int = 8,
    val studentSubjectAssignments: Map<String, List<StudentSubjectAssignmentDto>> = emptyMap(),
    val performanceRecords: List<PerformanceRecordDto> = emptyList(),
    val classFilter: String = "",
    val streamFilter: String = "",
    val termFilter: String = "",
    val yearFilter: String = "",
    val examTypeFilter: String = "",
    val errorMessage: String? = null
)

class ResultsViewModel(
    private val resultsRepository: ResultsRepository
) : ViewModel() {

    private val _uiState = MutableStateFlow(ResultsUiState())
    val uiState: StateFlow<ResultsUiState> = _uiState.asStateFlow()

    init {
        loadResultsData()
    }

    fun loadResultsData() {
        _uiState.update { it.copy(isLoading = true, errorMessage = null) }
        viewModelScope.launch {
            when (val result = resultsRepository.getResultsData()) {
                is Result.Success -> {
                    val data = result.data
                    _uiState.update {
                        it.copy(
                            isLoading = false,
                            teacher = data.teacher ?: it.teacher,
                            terms = data.terms,
                            classes = data.classes,
                            streams = data.streams,
                            examTypes = data.examTypes,
                            gradingScales = data.gradingScales,
                            aggregateDistribution = data.aggregateDistribution,
                            minSubjects = data.schoolLimits?.minSubjects ?: 7,
                            maxSubjects = data.schoolLimits?.maxSubjects ?: 8,
                            studentSubjectAssignments = data.studentSubjectAssignments,
                            performanceRecords = data.performanceRecords
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

    fun onClassFilterChanged(cls: String) { _uiState.update { it.copy(classFilter = cls) } }
    fun onStreamFilterChanged(stream: String) { _uiState.update { it.copy(streamFilter = stream) } }
    fun onTermFilterChanged(term: String) { _uiState.update { it.copy(termFilter = term) } }
    fun onYearFilterChanged(year: String) { _uiState.update { it.copy(yearFilter = year) } }
    fun onExamTypeFilterChanged(examType: String) { _uiState.update { it.copy(examTypeFilter = examType) } }

    fun resetFilters() {
        _uiState.update {
            it.copy(
                classFilter = "",
                streamFilter = "",
                termFilter = "",
                yearFilter = "",
                examTypeFilter = ""
            )
        }
    }

    class Factory(
        private val resultsRepository: ResultsRepository
    ) : ViewModelProvider.Factory {
        @Suppress("UNCHECKED_CAST")
        override fun <T : ViewModel> create(modelClass: Class<T>): T {
            if (modelClass.isAssignableFrom(ResultsViewModel::class.java)) {
                return ResultsViewModel(resultsRepository) as T
            }
            throw IllegalArgumentException("Unknown ViewModel class")
        }
    }
}
