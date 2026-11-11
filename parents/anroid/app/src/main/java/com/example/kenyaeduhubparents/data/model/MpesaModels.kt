package com.example.kenyaeduhubparents.data.model

import com.google.gson.annotations.SerializedName

data class MpesaPaymentResponse(
    @SerializedName("success") val success: Boolean,
    @SerializedName("message") val message: String? = null,
    @SerializedName("CheckoutRequestID") val checkoutRequestID: String? = null,
    @SerializedName("error") val error: String? = null
)

data class MpesaStatusResponse(
    @SerializedName("found") val found: Boolean,
    @SerializedName("status") val status: String? = null,
    @SerializedName("mpesa_receipt") val mpesaReceipt: String? = null,
    @SerializedName("amount") val amount: String? = null,
    @SerializedName("error_message") val errorMessage: String? = null
)
