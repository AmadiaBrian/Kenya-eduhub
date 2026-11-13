package com.example.kenyaeduhub.ui.dashboard

import android.content.Context
import android.net.Uri
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.example.kenyaeduhub.data.DashboardData
import com.example.kenyaeduhub.data.DashboardRepository
import com.example.kenyaeduhub.data.DashboardResult
import com.example.kenyaeduhub.data.ProfileResult
import com.example.kenyaeduhub.data.SettingsResult
import com.example.kenyaeduhub.data.UploadResult
import com.example.kenyaeduhub.data.User
import com.example.kenyaeduhub.utils.SessionManager
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.launch

class DashboardViewModel(
    private val repository: DashboardRepository = DashboardRepository()
) : ViewModel() {

    val isLoading = MutableStateFlow(false)
    val isUploading = MutableStateFlow(false)
    val dashboardData = MutableStateFlow<DashboardData?>(null)
    val errorMessage = MutableStateFlow<String?>(null)
    val searchQuery = MutableStateFlow("")

    fun loadDashboard(context: Context, user: User) {
        isLoading.value = true
        errorMessage.value = null
        val sessionManager = SessionManager(context)

        viewModelScope.launch {
            when (val result = repository.getDashboardData(user.id, user.email)) {
                is DashboardResult.Success -> {
                    isLoading.value = false
                    dashboardData.value = result.data
                    sessionManager.saveCachedDashboardData(result.data)
                    sessionManager.saveUserSession(result.data.user)
                }
                is DashboardResult.Error -> {
                    isLoading.value = false
                    val cached = sessionManager.getCachedDashboardData()
                    if (cached != null) {
                        dashboardData.value = cached
                        errorMessage.value = "Offline mode: Displaying cached data from last sync."
                    } else {
                        errorMessage.value = result.message
                    }
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

    fun uploadResource(
        context: Context,
        userId: Int,
        title: String,
        level: String,
        subject: String,
        type: String,
        description: String,
        fileUri: Uri,
        fileName: String,
        onResult: (Boolean, String) -> Unit
    ) {
        isUploading.value = true
        viewModelScope.launch {
            when (val result = repository.uploadResource(context, userId, title, level, subject, type, description, fileUri, fileName)) {
                is UploadResult.Success -> {
                    isUploading.value = false
                    val currentUser = dashboardData.value?.user
                    if (currentUser != null) {
                        loadDashboard(context, currentUser)
                    }
                    onResult(true, result.message)
                }
                is UploadResult.Error -> {
                    isUploading.value = false
                    onResult(false, result.message)
                }
            }
        }
    }
}
