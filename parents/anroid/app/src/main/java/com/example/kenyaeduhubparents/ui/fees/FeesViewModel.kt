package com.example.kenyaeduhubparents.ui.fees

import android.content.Context
import androidx.lifecycle.ViewModel
import androidx.lifecycle.ViewModelProvider
import androidx.lifecycle.viewModelScope
import com.example.kenyaeduhubparents.data.local.SessionManager
import com.example.kenyaeduhubparents.data.model.FeesResponse
import com.example.kenyaeduhubparents.data.repository.FeesRepository
import kotlinx.coroutines.delay
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch

sealed interface FeesUiState {
    object Loading : FeesUiState
    data class Success(val data: FeesResponse) : FeesUiState
    data class Error(val message: String) : FeesUiState
}

class FeesViewModel(private val repository: FeesRepository) : ViewModel() {

    private val _uiState = MutableStateFlow<FeesUiState>(FeesUiState.Loading)
    val uiState: StateFlow<FeesUiState> = _uiState.asStateFlow()

    var selectedChildId: String? = null

    data class PaymentDialogState(
        val isVisible: Boolean = false,
        val title: String = "",
        val message: String = "",
        val type: DialogType = DialogType.INFO
    )

    enum class DialogType { INFO, LOADING, SUCCESS, ERROR }

    private val _paymentDialog = MutableStateFlow(PaymentDialogState())
    val paymentDialog: StateFlow<PaymentDialogState> = _paymentDialog.asStateFlow()

    init {
        loadFees()
    }

    fun loadFees() {
        viewModelScope.launch {
            _uiState.value = FeesUiState.Loading
            val result = repository.getFeesData()
            result.onSuccess { response ->
                if (response.success) {
                    _uiState.value = FeesUiState.Success(response)
                    if (selectedChildId == null && response.children.isNotEmpty()) {
                        selectedChildId = response.children[0].id.toString()
                    }
                } else {
                    _uiState.value = FeesUiState.Error(response.error ?: "Failed to load fee records")
                }
            }.onFailure { e ->
                _uiState.value = FeesUiState.Error(e.localizedMessage ?: "Unknown error occurred")
            }
        }
    }

    fun setSelectedChild(childId: String) {
        selectedChildId = childId
        val currentState = _uiState.value
        if (currentState is FeesUiState.Success) {
            _uiState.value = FeesUiState.Success(currentState.data)
        }
    }

    fun hidePaymentDialog() {
        _paymentDialog.value = PaymentDialogState(isVisible = false)
    }

    fun payWithMpesa(
        studentId: String,
        feeType: String,
        term: String,
        year: String,
        amount: String,
        phone: String
    ) {
        viewModelScope.launch {
            _paymentDialog.value = PaymentDialogState(
                isVisible = true,
                title = "Initiating Payment",
                message = "Please wait while we initiate your M-Pesa payment request...",
                type = DialogType.LOADING
            )

            val result = repository.initiateMpesaPayment(studentId, feeType, term, year, amount, phone)
            result.onSuccess { response ->
                if (response.success && !response.checkoutRequestID.isNullOrBlank()) {
                    _paymentDialog.value = PaymentDialogState(
                        isVisible = true,
                        title = "Payment Initiated",
                        message = response.message ?: "Please check your phone and enter your M-Pesa PIN.",
                        type = DialogType.LOADING
                    )
                    pollPaymentStatus(response.checkoutRequestID)
                } else {
                    _paymentDialog.value = PaymentDialogState(
                        isVisible = true,
                        title = "Payment Failed",
                        message = response.error ?: "Failed to initiate M-Pesa payment.",
                        type = DialogType.ERROR
                    )
                }
            }.onFailure { e ->
                _paymentDialog.value = PaymentDialogState(
                    isVisible = true,
                    title = "Network Error",
                    message = e.localizedMessage ?: "Failed to connect to payment server.",
                    type = DialogType.ERROR
                )
            }
        }
    }

    private fun pollPaymentStatus(checkoutRequestID: String) {
        viewModelScope.launch {
            var attempts = 0
            val maxAttempts = 20
            while (attempts < maxAttempts) {
                delay(6000)
                attempts++
                val result = repository.checkMpesaStatus(checkoutRequestID)
                result.onSuccess { statusResp ->
                    if (statusResp.found) {
                        if (statusResp.status == "success") {
                            _paymentDialog.value = PaymentDialogState(
                                isVisible = true,
                                title = "Payment Successful!",
                                message = "Your payment has been processed successfully.",
                                type = DialogType.SUCCESS
                            )
                            loadFees()
                            return@launch
                        } else if (statusResp.status == "failed") {
                            _paymentDialog.value = PaymentDialogState(
                                isVisible = true,
                                title = "Payment Failed",
                                message = statusResp.errorMessage ?: "Transaction failed.",
                                type = DialogType.ERROR
                            )
                            return@launch
                        }
                    }
                }
            }
            _paymentDialog.value = PaymentDialogState(
                isVisible = true,
                title = "Payment Status Timeout",
                message = "We could not confirm your payment status within the expected time. Please check your payment history.",
                type = DialogType.ERROR
            )
        }
    }

    class Factory(private val context: Context) : ViewModelProvider.Factory {
        @Suppress("UNCHECKED_CAST")
        override fun <T : ViewModel> create(modelClass: Class<T>): T {
            if (modelClass.isAssignableFrom(FeesViewModel::class.java)) {
                val sessionManager = SessionManager(context)
                val repository = FeesRepository(sessionManager)
                return FeesViewModel(repository) as T
            }
            throw IllegalArgumentException("Unknown ViewModel class")
        }
    }
}
