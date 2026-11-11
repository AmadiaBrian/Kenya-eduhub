package com.example.kenyaeduhubparents.ui.login

import com.example.kenyaeduhubparents.data.model.Parent

sealed interface LoginUiState {
    object Idle : LoginUiState
    object Loading : LoginUiState
    data class Success(val message: String, val parent: Parent?) : LoginUiState
    data class Error(val errorMessage: String) : LoginUiState
}
