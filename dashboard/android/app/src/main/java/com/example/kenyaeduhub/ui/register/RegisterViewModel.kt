package com.example.kenyaeduhub.ui.register

import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.setValue
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.example.kenyaeduhub.data.AuthRepository
import com.example.kenyaeduhub.data.RegisterResult
import kotlinx.coroutines.launch

class RegisterViewModel(
    private val authRepository: AuthRepository = AuthRepository()
) : ViewModel() {

    var fullName by mutableStateOf("")
    var email by mutableStateOf("")
    var phone by mutableStateOf("")
    var password by mutableStateOf("")
    var confirmPassword by mutableStateOf("")

    var isLoading by mutableStateOf(false)
    var errorMessage by mutableStateOf<String?>(null)
    var successMessage by mutableStateOf<String?>(null)

    fun register(onSuccess: (String) -> Unit) {
        errorMessage = null
        successMessage = null

        if (fullName.isBlank()) {
            errorMessage = "Full name is required"
            return
        }
        if (email.isBlank()) {
            errorMessage = "Email is required"
            return
        }
        if (phone.isBlank()) {
            errorMessage = "Phone number is required"
            return
        }
        if (password.isBlank()) {
            errorMessage = "Password is required"
            return
        }
        if (password != confirmPassword) {
            errorMessage = "Passwords do not match"
            return
        }

        isLoading = true
        viewModelScope.launch {
            when (val result = authRepository.register(fullName.trim(), email.trim(), phone.trim(), password)) {
                is RegisterResult.Success -> {
                    isLoading = false
                    successMessage = result.message
                    onSuccess(result.message)
                }
                is RegisterResult.Error -> {
                    isLoading = false
                    errorMessage = result.message
                }
            }
        }
    }
}
