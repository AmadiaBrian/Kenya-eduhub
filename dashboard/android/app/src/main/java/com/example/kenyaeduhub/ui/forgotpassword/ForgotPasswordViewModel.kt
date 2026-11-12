package com.example.kenyaeduhub.ui.forgotpassword

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.example.kenyaeduhub.data.AuthRepository
import com.example.kenyaeduhub.data.ForgotPasswordResult
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.launch

class ForgotPasswordViewModel(
    private val authRepository: AuthRepository = AuthRepository()
) : ViewModel() {

    val step = MutableStateFlow(1)
    val method = MutableStateFlow("email") // "email" or "sms"
    val email = MutableStateFlow("")
    val phone = MutableStateFlow("")
    val code = MutableStateFlow("")
    val newPassword = MutableStateFlow("")
    val confirmPassword = MutableStateFlow("")

    val isLoading = MutableStateFlow(false)
    val errorMessage = MutableStateFlow<String?>(null)
    val successMessage = MutableStateFlow<String?>(null)

    fun resetState() {
        step.value = 1
        method.value = "email"
        email.value = ""
        phone.value = ""
        code.value = ""
        newPassword.value = ""
        confirmPassword.value = ""
        isLoading.value = false
        errorMessage.value = null
        successMessage.value = null
    }

    fun goToStep1() {
        step.value = 1
        errorMessage.value = null
        successMessage.value = null
    }

    fun resendCode() {
        submitStep1 { }
    }

    fun submitStep1(onSuccess: () -> Unit) {
        errorMessage.value = null
        successMessage.value = null

        if (method.value == "email" && email.value.isBlank()) {
            errorMessage.value = "Email is required"
            return
        }
        if (method.value == "sms" && phone.value.isBlank()) {
            errorMessage.value = "Phone number is required"
            return
        }

        isLoading.value = true
        viewModelScope.launch {
            val identifier = if (method.value == "email") email.value.trim() else phone.value.trim()
            when (val result = authRepository.forgotPasswordStep1(method.value, identifier)) {
                is ForgotPasswordResult.Success -> {
                    isLoading.value = false
                    successMessage.value = result.message
                    step.value = result.nextStep
                    onSuccess()
                }
                is ForgotPasswordResult.Error -> {
                    isLoading.value = false
                    errorMessage.value = result.message
                }
            }
        }
    }

    fun submitStep2(onSuccess: () -> Unit) {
        errorMessage.value = null
        successMessage.value = null

        if (code.value.isBlank()) {
            errorMessage.value = "Verification code is required"
            return
        }

        isLoading.value = true
        viewModelScope.launch {
            val identifier = if (method.value == "email") email.value.trim() else phone.value.trim()
            when (val result = authRepository.forgotPasswordStep2(code.value.trim(), method.value, identifier)) {
                is ForgotPasswordResult.Success -> {
                    isLoading.value = false
                    successMessage.value = result.message
                    step.value = result.nextStep
                    onSuccess()
                }
                is ForgotPasswordResult.Error -> {
                    isLoading.value = false
                    errorMessage.value = result.message
                }
            }
        }
    }

    fun submitStep3(onSuccess: (String) -> Unit) {
        errorMessage.value = null
        successMessage.value = null

        if (newPassword.value.isBlank()) {
            errorMessage.value = "New password is required"
            return
        }
        if (newPassword.value != confirmPassword.value) {
            errorMessage.value = "Passwords do not match"
            return
        }
        if (newPassword.value.length < 6) {
            errorMessage.value = "Password must be at least 6 characters long"
            return
        }

        isLoading.value = true
        viewModelScope.launch {
            val identifier = if (method.value == "email") email.value.trim() else phone.value.trim()
            when (val result = authRepository.forgotPasswordStep3(newPassword.value, confirmPassword.value, code.value.trim(), method.value, identifier)) {
                is ForgotPasswordResult.Success -> {
                    isLoading.value = false
                    successMessage.value = result.message
                    onSuccess(result.message)
                }
                is ForgotPasswordResult.Error -> {
                    isLoading.value = false
                    errorMessage.value = result.message
                }
            }
        }
    }
}
