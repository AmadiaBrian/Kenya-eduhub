package com.example.kenyaeduhubparents.ui.login

import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.setValue
import androidx.lifecycle.ViewModel
import androidx.lifecycle.ViewModelProvider
import androidx.lifecycle.viewModelScope
import com.example.kenyaeduhubparents.data.repository.AuthRepository
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch

class LoginViewModel(private val repository: AuthRepository) : ViewModel() {

    var email by mutableStateOf("")
        private set

    var identifier by mutableStateOf("")
        private set

    private val _uiState = MutableStateFlow<LoginUiState>(LoginUiState.Idle)
    val uiState: StateFlow<LoginUiState> = _uiState.asStateFlow()

    fun onEmailChanged(newValue: String) {
        email = newValue
    }

    fun onIdentifierChanged(newValue: String) {
        identifier = newValue
    }

    fun login() {
        val trimmedEmail = email.trim()
        val trimmedIdentifier = identifier.trim()

        if (trimmedEmail.isEmpty() || trimmedIdentifier.isEmpty()) {
            _uiState.value = LoginUiState.Error("Please enter both email and phone number/ID number")
            return
        }

        _uiState.value = LoginUiState.Loading

        viewModelScope.launch {
            val result = repository.login(trimmedEmail, trimmedIdentifier)
            result.fold(
                onSuccess = { response ->
                    if (response.success) {
                        _uiState.value = LoginUiState.Success(
                            message = response.message ?: "Login successful",
                            parent = response.activeParent
                        )
                    } else {
                        _uiState.value = LoginUiState.Error(
                            response.message ?: response.error ?: "Login failed. Please check your credentials."
                        )
                    }
                },
                onFailure = { throwable ->
                    _uiState.value = LoginUiState.Error(
                        throwable.localizedMessage ?: "Unable to connect to the server. Check your internet connection and try again."
                    )
                }
            )
        }
    }

    fun resetState() {
        _uiState.value = LoginUiState.Idle
    }

    class Factory(private val repository: AuthRepository) : ViewModelProvider.Factory {
        @Suppress("UNCHECKED_CAST")
        override fun <T : ViewModel> create(modelClass: Class<T>): T {
            return LoginViewModel(repository) as T
        }
    }
}
