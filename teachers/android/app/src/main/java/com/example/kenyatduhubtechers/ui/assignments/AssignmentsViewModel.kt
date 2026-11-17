package com.example.kenyatduhubtechers.ui.assignments

import android.content.Context
import android.net.Uri
import android.provider.OpenableColumns
import androidx.lifecycle.ViewModel
import androidx.lifecycle.ViewModelProvider
import androidx.lifecycle.viewModelScope
import com.example.kenyatduhubtechers.data.model.AssignmentItemDto
import com.example.kenyatduhubtechers.data.model.CalendarStatusDto
import com.example.kenyatduhubtechers.data.model.ClassDto
import com.example.kenyatduhubtechers.data.model.SubjectDto
import com.example.kenyatduhubtechers.data.model.TeacherDto
import com.example.kenyatduhubtechers.data.repository.AssignmentsRepository
import com.example.kenyatduhubtechers.data.repository.Result
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch

data class AssignmentsUiState(
    val isLoading: Boolean = false,
    val isSubmitting: Boolean = false,
    val teacher: TeacherDto? = null,
    val calendarStatus: CalendarStatusDto? = null,
    val classes: List<ClassDto> = emptyList(),
    val subjects: List<SubjectDto> = emptyList(),
    val assignments: List<AssignmentItemDto> = emptyList(),
    val uploadAssignmentType: String = "",
    val uploadTitle: String = "",
    val uploadDescription: String = "",
    val uploadClassId: Int? = null,
    val uploadSubjectId: Int? = null,
    val uploadDueDate: String = "",
    val selectedFileUri: Uri? = null,
    val selectedFileName: String = "",
    val searchQuery: String = "",
    val typeFilter: String = "",
    val sortFilter: String = "newest",
    val editingAssignment: AssignmentItemDto? = null,
    val showEditDialog: Boolean = false,
    val editTitle: String = "",
    val editDescription: String = "",
    val editDueDate: String = "",
    val deletingAssignment: AssignmentItemDto? = null,
    val showDeleteDialog: Boolean = false,
    val duplicatingAssignment: AssignmentItemDto? = null,
    val showDuplicateDialog: Boolean = false,
    val previewAssignment: AssignmentItemDto? = null,
    val showPreviewDialog: Boolean = false,
    val successMessage: String? = null,
    val errorMessage: String? = null
)

class AssignmentsViewModel(
    private val assignmentsRepository: AssignmentsRepository
) : ViewModel() {

    private val _uiState = MutableStateFlow(AssignmentsUiState())
    val uiState: StateFlow<AssignmentsUiState> = _uiState.asStateFlow()

    init {
        loadAssignmentsData()
    }

    fun loadAssignmentsData() {
        _uiState.update { it.copy(isLoading = true, errorMessage = null) }
        viewModelScope.launch {
            when (val result = assignmentsRepository.getAssignmentsData()) {
                is Result.Success -> {
                    val data = result.data
                    _uiState.update {
                        it.copy(
                            isLoading = false,
                            teacher = data.teacher ?: it.teacher,
                            calendarStatus = data.calendarStatus,
                            classes = data.classes,
                            subjects = data.subjects,
                            assignments = data.assignments
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

    fun onUploadTypeChanged(type: String) { _uiState.update { it.copy(uploadAssignmentType = type) } }
    fun onUploadTitleChanged(title: String) { _uiState.update { it.copy(uploadTitle = title) } }
    fun onUploadDescriptionChanged(desc: String) { _uiState.update { it.copy(uploadDescription = desc) } }
    fun onUploadClassChanged(classId: Int?) { _uiState.update { it.copy(uploadClassId = classId) } }
    fun onUploadSubjectChanged(subjectId: Int?) { _uiState.update { it.copy(uploadSubjectId = subjectId) } }
    fun onUploadDueDateChanged(date: String) { _uiState.update { it.copy(uploadDueDate = date) } }

    fun onFileSelected(context: Context, uri: Uri) {
        var name = "Selected File"
        try {
            context.contentResolver.query(uri, null, null, null, null)?.use { cursor ->
                val nameIndex = cursor.getColumnIndex(OpenableColumns.DISPLAY_NAME)
                if (nameIndex != -1 && cursor.moveToFirst()) {
                    name = cursor.getString(nameIndex)
                }
            }
        } catch (e: Exception) {
            // fallback
        }
        _uiState.update {
            it.copy(
                selectedFileUri = uri,
                selectedFileName = name
            )
        }
    }

    fun clearSelectedFile() {
        _uiState.update {
            it.copy(
                selectedFileUri = null,
                selectedFileName = ""
            )
        }
    }

    fun uploadAssignment(context: Context) {
        val state = _uiState.value
        val uri = state.selectedFileUri
        val type = state.uploadAssignmentType.trim()
        val title = state.uploadTitle.trim()

        if (type.isBlank()) {
            _uiState.update { it.copy(errorMessage = "Please select an assignment type.") }
            return
        }

        if (title.isBlank()) {
            _uiState.update { it.copy(errorMessage = "Please enter an assignment title.") }
            return
        }

        if (uri == null) {
            _uiState.update { it.copy(errorMessage = "Please select a file to upload.") }
            return
        }

        _uiState.update { it.copy(isSubmitting = true, errorMessage = null, successMessage = null) }

        viewModelScope.launch {
            when (val result = assignmentsRepository.uploadAssignment(
                context = context,
                fileUri = uri,
                assignmentType = type,
                title = title,
                description = state.uploadDescription,
                classId = state.uploadClassId,
                subjectId = state.uploadSubjectId,
                dueDate = state.uploadDueDate
            )) {
                is Result.Success -> {
                    _uiState.update {
                        it.copy(
                            isSubmitting = false,
                            uploadAssignmentType = "",
                            uploadTitle = "",
                            uploadDescription = "",
                            uploadClassId = null,
                            uploadSubjectId = null,
                            uploadDueDate = "",
                            selectedFileUri = null,
                            selectedFileName = "",
                            successMessage = result.data.message ?: "Assignment uploaded successfully!"
                        )
                    }
                    loadAssignmentsData()
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

    fun openEditDialog(assignment: AssignmentItemDto) {
        _uiState.update {
            it.copy(
                editingAssignment = assignment,
                showEditDialog = true,
                editTitle = assignment.title,
                editDescription = assignment.description ?: "",
                editDueDate = assignment.dueDate ?: "",
                errorMessage = null
            )
        }
    }

    fun onEditTitleChanged(title: String) { _uiState.update { it.copy(editTitle = title) } }
    fun onEditDescriptionChanged(desc: String) { _uiState.update { it.copy(editDescription = desc) } }
    fun onEditDueDateChanged(date: String) { _uiState.update { it.copy(editDueDate = date) } }

    fun closeEditDialog() {
        _uiState.update {
            it.copy(
                editingAssignment = null,
                showEditDialog = false
            )
        }
    }

    fun saveEdit() {
        val state = _uiState.value
        val assignment = state.editingAssignment ?: return
        val title = state.editTitle.trim()

        if (title.isBlank()) {
            _uiState.update { it.copy(errorMessage = "Title is required.") }
            return
        }

        _uiState.update { it.copy(isSubmitting = true, errorMessage = null, successMessage = null) }

        viewModelScope.launch {
            when (val result = assignmentsRepository.updateAssignment(
                assignmentId = assignment.id,
                title = title,
                description = state.editDescription.trim(),
                dueDate = state.editDueDate.trim()
            )) {
                is Result.Success -> {
                    _uiState.update {
                        it.copy(
                            isSubmitting = false,
                            showEditDialog = false,
                            editingAssignment = null,
                            successMessage = result.data.message ?: "Assignment updated successfully!"
                        )
                    }
                    loadAssignmentsData()
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

    fun openDuplicateDialog(assignment: AssignmentItemDto) {
        _uiState.update {
            it.copy(
                duplicatingAssignment = assignment,
                showDuplicateDialog = true,
                errorMessage = null
            )
        }
    }

    fun closeDuplicateDialog() {
        _uiState.update {
            it.copy(
                duplicatingAssignment = null,
                showDuplicateDialog = false
            )
        }
    }

    fun confirmDuplicate() {
        val assignment = _uiState.value.duplicatingAssignment ?: return
        _uiState.update { it.copy(isSubmitting = true, errorMessage = null, successMessage = null) }

        viewModelScope.launch {
            when (val result = assignmentsRepository.duplicateAssignment(assignment.id)) {
                is Result.Success -> {
                    _uiState.update {
                        it.copy(
                            isSubmitting = false,
                            showDuplicateDialog = false,
                            duplicatingAssignment = null,
                            successMessage = result.data.message ?: "Assignment duplicated successfully!"
                        )
                    }
                    loadAssignmentsData()
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

    fun openDeleteDialog(assignment: AssignmentItemDto) {
        _uiState.update {
            it.copy(
                deletingAssignment = assignment,
                showDeleteDialog = true,
                errorMessage = null
            )
        }
    }

    fun closeDeleteDialog() {
        _uiState.update {
            it.copy(
                deletingAssignment = null,
                showDeleteDialog = false
            )
        }
    }

    fun confirmDelete() {
        val assignment = _uiState.value.deletingAssignment ?: return
        _uiState.update { it.copy(isSubmitting = true, errorMessage = null, successMessage = null) }

        viewModelScope.launch {
            when (val result = assignmentsRepository.deleteAssignment(assignment.id)) {
                is Result.Success -> {
                    _uiState.update {
                        it.copy(
                            isSubmitting = false,
                            showDeleteDialog = false,
                            deletingAssignment = null,
                            successMessage = result.data.message ?: "Assignment deleted successfully!"
                        )
                    }
                    loadAssignmentsData()
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

    fun openPreviewDialog(assignment: AssignmentItemDto) {
        _uiState.update {
            it.copy(
                previewAssignment = assignment,
                showPreviewDialog = true
            )
        }
    }

    fun closePreviewDialog() {
        _uiState.update {
            it.copy(
                previewAssignment = null,
                showPreviewDialog = false
            )
        }
    }

    fun onSearchQueryChanged(query: String) { _uiState.update { it.copy(searchQuery = query) } }
    fun onTypeFilterChanged(type: String) { _uiState.update { it.copy(typeFilter = type) } }
    fun onSortFilterChanged(sort: String) { _uiState.update { it.copy(sortFilter = sort) } }

    fun resetFilters() {
        _uiState.update {
            it.copy(
                searchQuery = "",
                typeFilter = "",
                sortFilter = "newest"
            )
        }
    }

    fun clearMessages() {
        _uiState.update { it.copy(errorMessage = null, successMessage = null) }
    }

    class Factory(
        private val assignmentsRepository: AssignmentsRepository
    ) : ViewModelProvider.Factory {
        @Suppress("UNCHECKED_CAST")
        override fun <T : ViewModel> create(modelClass: Class<T>): T {
            if (modelClass.isAssignableFrom(AssignmentsViewModel::class.java)) {
                return AssignmentsViewModel(assignmentsRepository) as T
            }
            throw IllegalArgumentException("Unknown ViewModel class")
        }
    }
}
