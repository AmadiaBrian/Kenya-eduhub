package com.example.kenyatduhubtechers.ui.studentsubjects

import androidx.lifecycle.ViewModel
import androidx.lifecycle.ViewModelProvider
import androidx.lifecycle.viewModelScope
import com.example.kenyatduhubtechers.data.model.ClassDto
import com.example.kenyatduhubtechers.data.model.StreamDto
import com.example.kenyatduhubtechers.data.model.StudentAffairsItemDto
import com.example.kenyatduhubtechers.data.model.StudentSubjectDetailDto
import com.example.kenyatduhubtechers.data.model.SubjectDto
import com.example.kenyatduhubtechers.data.repository.PerformanceRepository
import com.example.kenyatduhubtechers.data.repository.Result
import com.example.kenyatduhubtechers.data.repository.StudentSubjectsRepository
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch

data class StudentSubjectsUiState(
    val isLoading: Boolean = false,
    val isSubmitting: Boolean = false,
    val isLoadingStudentDetails: Boolean = false,
    val students: List<StudentAffairsItemDto> = emptyList(),
    val classes: List<ClassDto> = emptyList(),
    val streams: List<StreamDto> = emptyList(),
    val availableSubjects: List<SubjectDto> = emptyList(),
    val subjectAssignmentsMap: Map<Int, String> = emptyMap(),
    val selectedStudentDetail: StudentAffairsItemDto? = null,
    val studentDetailSubjects: List<StudentSubjectDetailDto> = emptyList(),
    val showDetailsDialog: Boolean = false,
    val showAssignDialog: Boolean = false,
    val selectedStudentIdsForAssign: Set<Int> = emptySet(),
    val selectedSubjectIdForAssign: Int? = null,
    val searchQuery: String = "",
    val classFilter: String = "",
    val streamFilter: String = "",
    val subjectFilter: String = "",
    val successMessage: String? = null,
    val errorMessage: String? = null
)

class StudentSubjectsViewModel(
    private val studentSubjectsRepository: StudentSubjectsRepository,
    private val performanceRepository: PerformanceRepository
) : ViewModel() {

    private val _uiState = MutableStateFlow(StudentSubjectsUiState())
    val uiState: StateFlow<StudentSubjectsUiState> = _uiState.asStateFlow()

    init {
        loadStudentSubjectsData()
        loadAvailableSubjects()
    }

    fun loadStudentSubjectsData() {
        _uiState.update { it.copy(isLoading = true, errorMessage = null) }
        viewModelScope.launch {
            when (val result = studentSubjectsRepository.getStudentsAndSubjectsData()) {
                is Result.Success -> {
                    val affairs = result.data.affairsResponse
                    val map = result.data.subjectAssignments.associate {
                        it.studentId to (it.subjectNames ?: "No subjects assigned")
                    }
                    _uiState.update {
                        it.copy(
                            isLoading = false,
                            students = affairs.data,
                            classes = affairs.classes,
                            streams = affairs.streams,
                            subjectAssignmentsMap = map
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

    private fun loadAvailableSubjects() {
        viewModelScope.launch {
            when (val perfResult = performanceRepository.getPerformanceData()) {
                is Result.Success -> {
                    _uiState.update {
                        it.copy(availableSubjects = perfResult.data.allSubjects)
                    }
                }
                is Result.Error -> {
                    // Fail silently or keep empty list
                }
            }
        }
    }

    fun openStudentSubjectDetails(student: StudentAffairsItemDto) {
        _uiState.update {
            it.copy(
                selectedStudentDetail = student,
                showDetailsDialog = true,
                isLoadingStudentDetails = true,
                studentDetailSubjects = emptyList(),
                errorMessage = null
            )
        }

        viewModelScope.launch {
            when (val result = studentSubjectsRepository.getStudentSubjectDetails(student.id)) {
                is Result.Success -> {
                    _uiState.update {
                        it.copy(
                            isLoadingStudentDetails = false,
                            studentDetailSubjects = result.data
                        )
                    }
                }
                is Result.Error -> {
                    _uiState.update {
                        it.copy(
                            isLoadingStudentDetails = false,
                            errorMessage = result.message
                        )
                    }
                }
            }
        }
    }

    fun closeStudentSubjectDetails() {
        _uiState.update {
            it.copy(
                showDetailsDialog = false,
                selectedStudentDetail = null,
                studentDetailSubjects = emptyList()
            )
        }
    }

    fun removeSubjectFromStudent(studentId: Int, subjectId: Int) {
        viewModelScope.launch {
            _uiState.update { it.copy(isSubmitting = true, errorMessage = null, successMessage = null) }
            when (val result = studentSubjectsRepository.removeStudentSubject(studentId, subjectId)) {
                is Result.Success -> {
                    val msg = result.data.message ?: "Subject assignment removed successfully"
                    _uiState.update { state ->
                        state.copy(
                            isSubmitting = false,
                            studentDetailSubjects = state.studentDetailSubjects.filter { it.subjectId != subjectId },
                            successMessage = msg
                        )
                    }
                    loadStudentSubjectsData()
                }
                is Result.Error -> {
                    _uiState.update {
                        it.copy(
                            isSubmitting = false,
                            errorMessage = result.message
                        )
                    }
                }
            }
        }
    }

    fun openAssignModal(preselectedStudentId: Int? = null) {
        _uiState.update {
            it.copy(
                showAssignDialog = true,
                selectedStudentIdsForAssign = if (preselectedStudentId != null) setOf(preselectedStudentId) else emptySet(),
                selectedSubjectIdForAssign = null,
                errorMessage = null
            )
        }
    }

    fun closeAssignModal() {
        _uiState.update {
            it.copy(
                showAssignDialog = false,
                selectedStudentIdsForAssign = emptySet(),
                selectedSubjectIdForAssign = null
            )
        }
    }

    fun toggleSelectStudentForAssign(studentId: Int) {
        _uiState.update { state ->
            val set = state.selectedStudentIdsForAssign.toMutableSet()
            if (set.contains(studentId)) {
                set.remove(studentId)
            } else {
                set.add(studentId)
            }
            state.copy(selectedStudentIdsForAssign = set)
        }
    }

    fun selectAllFilteredStudentsForAssign(studentIds: List<Int>) {
        _uiState.update { state ->
            state.copy(selectedStudentIdsForAssign = studentIds.toSet())
        }
    }

    fun clearSelectedStudentsForAssign() {
        _uiState.update { state ->
            state.copy(selectedStudentIdsForAssign = emptySet())
        }
    }

    fun onSubjectSelectedForAssign(subjectId: Int) {
        _uiState.update { it.copy(selectedSubjectIdForAssign = subjectId) }
    }

    fun submitAssignSubject() {
        val state = _uiState.value
        val studentIds = state.selectedStudentIdsForAssign.toList()
        val subjectId = state.selectedSubjectIdForAssign

        if (studentIds.isEmpty()) {
            _uiState.update { it.copy(errorMessage = "Please select at least one student.") }
            return
        }

        if (subjectId == null || subjectId <= 0) {
            _uiState.update { it.copy(errorMessage = "Please select a subject to assign.") }
            return
        }

        _uiState.update { it.copy(isSubmitting = true, errorMessage = null, successMessage = null) }

        viewModelScope.launch {
            when (val result = studentSubjectsRepository.assignSubjectToStudents(studentIds, subjectId)) {
                is Result.Success -> {
                    val responseData = result.data.data
                    val count = responseData?.assignedCount ?: 0
                    val violations = responseData?.violations
                    val msg = if (!violations.isNullOrEmpty()) {
                        "Assigned subject to $count students. Skipped some due to subject limits."
                    } else {
                        result.data.message ?: "Successfully assigned subject to $count students."
                    }

                    _uiState.update {
                        it.copy(
                            isSubmitting = false,
                            showAssignDialog = false,
                            selectedStudentIdsForAssign = emptySet(),
                            selectedSubjectIdForAssign = null,
                            successMessage = msg
                        )
                    }
                    loadStudentSubjectsData()
                }
                is Result.Error -> {
                    _uiState.update {
                        it.copy(
                            isSubmitting = false,
                            errorMessage = result.message
                        )
                    }
                }
            }
        }
    }

    fun autoAssignCompulsorySubjects(classId: Int, streamId: Int? = null) {
        if (classId <= 0) {
            _uiState.update { it.copy(errorMessage = "Please select a valid class first.") }
            return
        }

        _uiState.update { it.copy(isSubmitting = true, errorMessage = null, successMessage = null) }

        viewModelScope.launch {
            when (val result = studentSubjectsRepository.autoAssignCompulsorySubjects(classId, streamId)) {
                is Result.Success -> {
                    val count = result.data.data?.assignedCount ?: 0
                    val msg = result.data.message ?: "Successfully assigned compulsory subjects to $count student records."
                    _uiState.update {
                        it.copy(
                            isSubmitting = false,
                            successMessage = msg
                        )
                    }
                    loadStudentSubjectsData()
                }
                is Result.Error -> {
                    _uiState.update {
                        it.copy(
                            isSubmitting = false,
                            errorMessage = result.message
                        )
                    }
                }
            }
        }
    }

    fun onSearchQueryChanged(query: String) { _uiState.update { it.copy(searchQuery = query) } }
    fun onClassFilterChanged(cls: String) { _uiState.update { it.copy(classFilter = cls) } }
    fun onStreamFilterChanged(stream: String) { _uiState.update { it.copy(streamFilter = stream) } }
    fun onSubjectFilterChanged(subj: String) { _uiState.update { it.copy(subjectFilter = subj) } }

    fun resetFilters() {
        _uiState.update {
            it.copy(
                searchQuery = "",
                classFilter = "",
                streamFilter = "",
                subjectFilter = ""
            )
        }
    }

    fun clearMessages() {
        _uiState.update { it.copy(errorMessage = null, successMessage = null) }
    }

    class Factory(
        private val studentSubjectsRepository: StudentSubjectsRepository,
        private val performanceRepository: PerformanceRepository
    ) : ViewModelProvider.Factory {
        @Suppress("UNCHECKED_CAST")
        override fun <T : ViewModel> create(modelClass: Class<T>): T {
            if (modelClass.isAssignableFrom(StudentSubjectsViewModel::class.java)) {
                return StudentSubjectsViewModel(studentSubjectsRepository, performanceRepository) as T
            }
            throw IllegalArgumentException("Unknown ViewModel class")
        }
    }
}
