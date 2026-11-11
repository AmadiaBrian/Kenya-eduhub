package com.example.kenyaeduhubparents.ui.profile

import android.content.Context
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.setValue
import androidx.lifecycle.ViewModel
import androidx.lifecycle.ViewModelProvider
import androidx.lifecycle.viewModelScope
import com.example.kenyaeduhubparents.data.local.SessionManager
import com.example.kenyaeduhubparents.data.model.ParentProfile
import com.example.kenyaeduhubparents.data.repository.ProfileRepository
import kotlinx.coroutines.launch

class ProfileViewModel(private val repository: ProfileRepository) : ViewModel() {
    var profile by mutableStateOf<ParentProfile?>(null)
        private set

    var firstName by mutableStateOf("")
        private set

    var lastName by mutableStateOf("")
        private set

    var phone by mutableStateOf("")
        private set

    var address by mutableStateOf("")
        private set

    var isLoading by mutableStateOf(false)
        private set

    var isSaving by mutableStateOf(false)
        private set

    var errorMessage by mutableStateOf<String?>(null)
        private set

    var successMessage by mutableStateOf<String?>(null)
        private set

    init {
        loadProfile()
    }

    fun onFirstNameChanged(value: String) {
        firstName = value
        successMessage = null
    }

    fun onLastNameChanged(value: String) {
        lastName = value
        successMessage = null
    }

    fun onPhoneChanged(value: String) {
        phone = value
        successMessage = null
    }

    fun onAddressChanged(value: String) {
        address = value
        successMessage = null
    }

    fun loadProfile() {
        viewModelScope.launch {
            isLoading = profile == null
            errorMessage = null
            repository.getProfile()
                .onSuccess(::showProfile)
                .onFailure { errorMessage = it.localizedMessage ?: "Unable to load your profile." }
            isLoading = false
        }
    }

    fun saveProfile(onUpdated: (ParentProfile) -> Unit) {
        val trimmedFirstName = firstName.trim()
        val trimmedLastName = lastName.trim()
        val trimmedPhone = phone.trim()
        val trimmedAddress = address.trim()

        if (trimmedFirstName.isEmpty() || trimmedLastName.isEmpty() || trimmedPhone.isEmpty()) {
            errorMessage = "First name, last name, and phone number are required."
            successMessage = null
            return
        }

        viewModelScope.launch {
            isSaving = true
            errorMessage = null
            successMessage = null
            repository.updateProfile(
                firstName = trimmedFirstName,
                lastName = trimmedLastName,
                phone = trimmedPhone,
                address = trimmedAddress
            ).onSuccess { updatedProfile ->
                showProfile(updatedProfile)
                successMessage = "Profile updated successfully."
                onUpdated(updatedProfile)
            }.onFailure {
                errorMessage = it.localizedMessage ?: "Unable to update your profile."
            }
            isSaving = false
        }
    }

    private fun showProfile(value: ParentProfile) {
        profile = value
        firstName = value.firstName
        lastName = value.lastName
        phone = value.phone
        address = value.address
    }

    class Factory(private val context: Context) : ViewModelProvider.Factory {
        @Suppress("UNCHECKED_CAST")
        override fun <T : ViewModel> create(modelClass: Class<T>): T {
            if (!modelClass.isAssignableFrom(ProfileViewModel::class.java)) {
                throw IllegalArgumentException("Unknown ViewModel class")
            }
            val repository = ProfileRepository(SessionManager(context.applicationContext))
            return ProfileViewModel(repository) as T
        }
    }
}
