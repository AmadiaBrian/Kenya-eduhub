package com.example.kenyatduhubtechers.ui.profile

import androidx.lifecycle.ViewModel
import androidx.lifecycle.ViewModelProvider
import androidx.lifecycle.viewModelScope
import com.example.kenyatduhubtechers.data.model.SubjectDto
import com.example.kenyatduhubtechers.data.model.TeacherDto
import com.example.kenyatduhubtechers.data.model.UpdateProfileRequest
import com.example.kenyatduhubtechers.data.repository.ProfileRepository
import com.example.kenyatduhubtechers.data.repository.Result
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch

data class ProfileUiState(
    val isLoading: Boolean = false,
    val isUpdating: Boolean = false,
    val teacher: TeacherDto? = null,
    val subjects: List<SubjectDto> = emptyList(),
    val firstName: String = "",
    val lastName: String = "",
    val email: String = "",
    val phone: String = "",
    val idNumber: String = "",
    val address: String = "",
    val selectedSubject: String = "",
    val successMessage: String? = null,
    val errorMessage: String? = null
)

class ProfileViewModel(
    private val profileRepository: ProfileRepository
) : ViewModel() {

    private val _uiState = MutableStateFlow(ProfileUiState())
    val uiState: StateFlow<ProfileUiState> = _uiState.asStateFlow()

    init {
        loadProfileData()
    }

    fun loadProfileData() {
        _uiState.update { it.copy(isLoading = true, errorMessage = null) }
        viewModelScope.launch {
            when (val result = profileRepository.getProfileData()) {
                is Result.Success -> {
                    val data = result.data
                    val t = data.teacher
                    _uiState.update {
                        it.copy(
                            isLoading = false,
                            teacher = t,
                            subjects = data.subjects,
                            firstName = t?.firstName ?: "",
                            lastName = t?.lastName ?: "",
                            email = t?.email ?: "",
                            phone = t?.phone ?: "",
                            idNumber = t?.idNumber ?: "",
                            address = t?.address ?: "",
                            selectedSubject = t?.subject ?: ""
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

    fun onFirstNameChanged(valStr: String) { _uiState.update { it.copy(firstName = valStr) } }
    fun onLastNameChanged(valStr: String) { _uiState.update { it.copy(lastName = valStr) } }
    fun onEmailChanged(valStr: String) { _uiState.update { it.copy(email = valStr) } }
    fun onPhoneChanged(valStr: String) { _uiState.update { it.copy(phone = valStr) } }
    fun onIdNumberChanged(valStr: String) { _uiState.update { it.copy(idNumber = valStr) } }
    fun onAddressChanged(valStr: String) { _uiState.update { it.copy(address = valStr) } }
    fun onSubjectChanged(valStr: String) { _uiState.update { it.copy(selectedSubject = valStr) } }

    fun updateProfile() {
        val state = _uiState.value
        if (state.firstName.isBlank() || state.lastName.isBlank() || state.email.isBlank() ||
            state.phone.isBlank() || state.idNumber.isBlank()) {
            _uiState.update { it.copy(errorMessage = "Please fill all required profile fields.") }
            return
        }

        _uiState.update { it.copy(isUpdating = true, errorMessage = null, successMessage = null) }

        viewModelScope.launch {
            val req = UpdateProfileRequest(
                firstName = state.firstName.trim(),
                lastName = state.lastName.trim(),
                email = state.email.trim(),
                phone = state.phone.trim(),
                idNumber = state.idNumber.trim(),
                address = state.address.trim(),
                subject = state.selectedSubject.trim()
            )

            when (val result = profileRepository.updateProfileData(req)) {
                is Result.Success -> {
                    _uiState.update {
                        it.copy(
                            isUpdating = false,
                            teacher = result.data.teacher ?: it.teacher,
                            successMessage = result.data.message ?: "Profile updated successfully!"
                        )
                    }
                }
                is Result.Error -> {
                    _uiState.update {
                        it.copy(
                            isUpdating = false,
                            errorMessage = result.message
                        )
                    }
                }
            }
        }
    }

    fun clearMessages() {
        _uiState.update { it.copy(errorMessage = null, successMessage = null) }
    }

    class Factory(
        private val profileRepository: ProfileRepository
    ) : ViewModelProvider.Factory {
        @Suppress("UNCHECKED_CAST")
        override fun <T : ViewModel> create(modelClass: Class<T>): T {
            if (modelClass.isAssignableFrom(ProfileViewModel::class.java)) {
                return ProfileViewModel(profileRepository) as T
            }
            throw IllegalArgumentException("Unknown ViewModel class")
        }
    }
}
