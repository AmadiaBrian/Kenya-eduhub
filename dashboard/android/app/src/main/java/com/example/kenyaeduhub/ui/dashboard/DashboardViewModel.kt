package com.example.kenyaeduhub.ui.dashboard

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.example.kenyaeduhub.data.DashboardData
import com.example.kenyaeduhub.data.DashboardRepository
import com.example.kenyaeduhub.data.DashboardResult
import com.example.kenyaeduhub.data.ProfileResult
import com.example.kenyaeduhub.data.SettingsResult
import com.example.kenyaeduhub.data.User
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.launch

class DashboardViewModel(
    private val repository: DashboardRepository = DashboardRepository()
) : ViewModel() {

    val isLoading = MutableStateFlow(false)
    val dashboardData = MutableStateFlow<DashboardData?>(null)
    val errorMessage = MutableStateFlow<String?>(null)
    val searchQuery = MutableStateFlow("")

    fun loadDashboard(user: User) {
        isLoading.value = true
        errorMessage.value = null
        viewModelScope.launch {
            when (val result = repository.getDashboardData(user.id, user.email)) {
                is DashboardResult.Success -> {
                    isLoading.value = false
                    dashboardData.value = result.data
                }
                is DashboardResult.Error -> {
                    isLoading.value = false
                    errorMessage.value = result.message
                }
            }
        }
    }

    fun updateProfile(userId: Int, name: String, email: String, phone: String, onResult: (Boolean, String) -> Unit) {
        viewModelScope.launch {
            when (val result = repository.updateProfile(userId, name, email, phone)) {
                is ProfileResult.Success -> {
                    result.updatedUser?.let { updated ->
                        val current = dashboardData.value
                        if (current != null) {
                            dashboardData.value = current.copy(user = updated)
                        }
                    }
                    onResult(true, result.message)
                }
                is ProfileResult.Error -> {
                    onResult(false, result.message)
                }
            }
        }
    }

    fun changePassword(userId: Int, currentPass: String, newPass: String, confirmPass: String, onResult: (Boolean, String) -> Unit) {
        viewModelScope.launch {
            when (val result = repository.changePassword(userId, currentPass, newPass, confirmPass)) {
                is ProfileResult.Success -> {
                    onResult(true, result.message)
                }
                is ProfileResult.Error -> {
                    onResult(false, result.message)
                }
            }
        }
    }

    fun saveSettings(
        userId: Int,
        emailUploads: Boolean,
        emailComments: Boolean,
        emailUpdates: Boolean,
        showProfile: Boolean,
        showEmail: Boolean,
        theme: String,
        language: String,
        onResult: (Boolean, String) -> Unit
    ) {
        viewModelScope.launch {
            when (val result = repository.saveSettings(userId, emailUploads, emailComments, emailUpdates, showProfile, showEmail, theme, language)) {
                is SettingsResult.Success -> onResult(true, result.message)
                is SettingsResult.Error -> onResult(false, result.message)
            }
        }
    }

    fun deleteAccount(userId: Int, confirmEmail: String, deletionReason: String, onResult: (Boolean, String) -> Unit) {
        viewModelScope.launch {
            when (val result = repository.deleteAccount(userId, confirmEmail, deletionReason)) {
                is SettingsResult.Success -> onResult(true, result.message)
                is SettingsResult.Error -> onResult(false, result.message)
            }
        }
    }
}
