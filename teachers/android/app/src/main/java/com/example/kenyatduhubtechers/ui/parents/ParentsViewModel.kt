package com.example.kenyatduhubtechers.ui.parents

import androidx.lifecycle.ViewModel
import androidx.lifecycle.ViewModelProvider
import androidx.lifecycle.viewModelScope
import com.example.kenyatduhubtechers.data.model.ParentItemDto
import com.example.kenyatduhubtechers.data.model.SendParentEmailRequest
import com.example.kenyatduhubtechers.data.repository.ParentsRepository
import com.example.kenyatduhubtechers.data.repository.Result
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch

data class ParentsUiState(
    val isLoading: Boolean = false,
    val isSendingEmail: Boolean = false,
    val parents: List<ParentItemDto> = emptyList(),
    val showComposeSection: Boolean = false,
    val selectedParentDetail: ParentItemDto? = null,
    val showDetailsDialog: Boolean = false,
    val searchQuery: String = "",
    val relationshipFilter: String = "",
    val composeRecipientId: String = "all",
    val composeSubject: String = "",
    val composeBody: String = "",
    val successMessage: String? = null,
    val errorMessage: String? = null
)

class ParentsViewModel(
    private val parentsRepository: ParentsRepository
) : ViewModel() {

    private val _uiState = MutableStateFlow(ParentsUiState())
    val uiState: StateFlow<ParentsUiState> = _uiState.asStateFlow()

    init {
        loadParentsData()
    }

    fun loadParentsData() {
        _uiState.update { it.copy(isLoading = true, errorMessage = null) }
        viewModelScope.launch {
            when (val result = parentsRepository.getParentsData()) {
                is Result.Success -> {
                    _uiState.update {
                        it.copy(
                            isLoading = false,
                            parents = result.data.data
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

    fun onSearchQueryChanged(query: String) { _uiState.update { it.copy(searchQuery = query) } }
    fun onRelationshipFilterChanged(rel: String) { _uiState.update { it.copy(relationshipFilter = rel) } }

    fun toggleComposeSection() {
        _uiState.update { it.copy(showComposeSection = !it.showComposeSection) }
    }

    fun onComposeRecipientChanged(recipientId: String) { _uiState.update { it.copy(composeRecipientId = recipientId) } }
    fun onComposeSubjectChanged(subj: String) { _uiState.update { it.copy(composeSubject = subj) } }
    fun onComposeBodyChanged(body: String) { _uiState.update { it.copy(composeBody = body) } }

    fun openComposeForParent(parentId: String) {
        _uiState.update {
            it.copy(
                showComposeSection = true,
                composeRecipientId = parentId
            )
        }
    }

    fun clearComposeForm() {
        _uiState.update {
            it.copy(
                composeRecipientId = "all",
                composeSubject = "",
                composeBody = ""
            )
        }
    }

    fun sendComposedEmail() {
        val state = _uiState.value
        if (state.composeSubject.isBlank() || state.composeBody.isBlank()) {
            _uiState.update { it.copy(errorMessage = "Please enter email subject and message.") }
            return
        }

        _uiState.update { it.copy(isSendingEmail = true, errorMessage = null, successMessage = null) }

        viewModelScope.launch {
            val req = SendParentEmailRequest(
                recipient = state.composeRecipientId,
                subject = state.composeSubject.trim(),
                body = state.composeBody.trim()
            )

            when (val result = parentsRepository.sendParentEmail(req)) {
                is Result.Success -> {
                    _uiState.update {
                        it.copy(
                            isSendingEmail = false,
                            showComposeSection = false,
                            composeSubject = "",
                            composeBody = "",
                            successMessage = result.data.message ?: "Email sent successfully!"
                        )
                    }
                }
                is Result.Error -> {
                    _uiState.update {
                        it.copy(
                            isSendingEmail = false,
                            errorMessage = result.message
                        )
                    }
                }
            }
        }
    }

    fun openParentDetails(parent: ParentItemDto) {
        _uiState.update { it.copy(selectedParentDetail = parent, showDetailsDialog = true) }
    }

    fun closeParentDetails() {
        _uiState.update { it.copy(showDetailsDialog = false, selectedParentDetail = null) }
    }

    fun resetFilters() {
        _uiState.update {
            it.copy(
                searchQuery = "",
                relationshipFilter = ""
            )
        }
    }

    fun generateCsvExport(parents: List<ParentItemDto>): String {
        val sb = StringBuilder()
        sb.append("Name,Email,Phone,ID Number,Relationship,Address,Linked Students\n")
        parents.forEach { parent ->
            sb.append("${parent.fullName},${parent.email},${parent.phone},${parent.idNumber},${parent.relationship ?: "Guardian"},${parent.address ?: "-"},${parent.linkedStudents ?: "-"}\n")
        }
        return sb.toString()
    }

    fun clearMessages() {
        _uiState.update { it.copy(errorMessage = null, successMessage = null) }
    }

    class Factory(
        private val parentsRepository: ParentsRepository
    ) : ViewModelProvider.Factory {
        @Suppress("UNCHECKED_CAST")
        override fun <T : ViewModel> create(modelClass: Class<T>): T {
            if (modelClass.isAssignableFrom(ParentsViewModel::class.java)) {
                return ParentsViewModel(parentsRepository) as T
            }
            throw IllegalArgumentException("Unknown ViewModel class")
        }
    }
}
