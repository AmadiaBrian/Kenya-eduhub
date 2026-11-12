package com.example.kenyaeduhub.ui.login

import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.setValue
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.example.kenyaeduhub.data.AuthRepository
import com.example.kenyaeduhub.data.LoginResult
import com.example.kenyaeduhub.data.User
import kotlinx.coroutines.launch

class LoginViewModel(
    private val authRepository: AuthRepository = AuthRepository()
) : ViewModel() {

    var email by mutableStateOf("")
    var password by mutableStateOf("")
    var rememberMe by mutableStateOf(false)
    var isLoading by mutableStateOf(false)
    var errorMessage by mutableStateOf<String?>(null)
    var successMessage by mutableStateOf<String?>(null)
    var loggedInUser by mutableStateOf<User?>(null)

    fun login(onSuccess: (User) -> Unit) {
        errorMessage = null
        successMessage = null

        if (email.isBlank()) {
            errorMessage = "Email is required"
            return
        }
        if (password.isBlank()) {
            errorMessage = "Password is required"
            return
        }

        isLoading = true
        viewModelScope.launch {
            when (val result = authRepository.login(email.trim(), password)) {
                is LoginResult.Success -> {
                    isLoading = false
                    successMessage = result.message
                    loggedInUser = result.user
                    onSuccess(result.user)
                }
                is LoginResult.Error -> {
                    isLoading = false
                    errorMessage = result.message
                }
            }
        }
    }

    fun logout() {
        loggedInUser = null
        email = ""
        password = ""
        successMessage = null
        errorMessage = null
    }
}
