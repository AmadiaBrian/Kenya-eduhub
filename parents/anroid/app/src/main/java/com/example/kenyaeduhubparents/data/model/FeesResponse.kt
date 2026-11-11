package com.example.kenyaeduhubparents.data.model

import com.google.gson.annotations.SerializedName

data class FeesResponse(
    @SerializedName("success") val success: Boolean,
    @SerializedName("error") val error: String? = null,
    @SerializedName("parent") val parent: ParentInfo? = null,
    @SerializedName("children") val children: List<Child> = emptyList(),
    @SerializedName("fee_data") val feeData: Map<String, FeeDataWrapper> = emptyMap(),
    @SerializedName("current_term") val currentTerm: String? = null,
    @SerializedName("terms") val terms: List<String> = emptyList(),
    @SerializedName("current_year") val currentYear: Int = 2026
)

data class ParentInfo(
    @SerializedName("id") val id: Int,
    @SerializedName("name") val name: String,
    @SerializedName("email") val email: String,
    @SerializedName("phone") val phone: String? = null,
    @SerializedName("parent_phone") val parentPhone: String? = null,
    @SerializedName("telephone") val telephone: String? = null,
    @SerializedName("phone_number") val phoneNumber: String? = null
) {
    val displayPhone: String
        get() = phone ?: parentPhone ?: telephone ?: phoneNumber ?: ""
}

data class FeeDataWrapper(
    @SerializedName("child") val child: Child? = null,
    @SerializedName("fee_structures") val feeStructures: List<FeeStructureItem> = emptyList(),
    @SerializedName("payments") val payments: List<FeePaymentItem> = emptyList(),
    @SerializedName("term_balances") val termBalances: Map<String, TermBalance> = emptyMap(),
    @SerializedName("year_total_fees") val yearTotalFees: Double = 0.0,
    @SerializedName("year_total_paid") val yearTotalPaid: Double = 0.0,
    @SerializedName("year_balance") val yearBalance: Double = 0.0,
    @SerializedName("current_year") val currentYear: Int = 2026,
    @SerializedName("fee_structure_status") val feeStructureStatus: List<FeeStructureStatus> = emptyList()
)

data class FeeStructureItem(
    @SerializedName("id") val id: Int,
    @SerializedName("fee_type") val feeType: String,
    @SerializedName("term") val term: String,
    @SerializedName("year") val year: Int,
    @SerializedName("amount") val amount: Double,
    @SerializedName("description") val description: String? = null
)

data class FeePaymentItem(
    @SerializedName("id") val id: Int,
    @SerializedName("student_id") val studentId: Int,
    @SerializedName("amount") val amount: Double,
    @SerializedName("term") val term: String?,
    @SerializedName("year") val year: Int,
    @SerializedName("fee_type") val feeType: String?,
    @SerializedName("payment_date") val paymentDate: String,
    @SerializedName("payment_method") val paymentMethod: String?,
    @SerializedName("receipt_number") val receiptNumber: String?,
    @SerializedName("status") val status: String
)

data class TermBalance(
    @SerializedName("fees") val fees: Double,
    @SerializedName("paid") val paid: Double,
    @SerializedName("balance") val balance: Double
)

data class FeeStructureStatus(
    @SerializedName("id") val id: Int,
    @SerializedName("fee_type") val feeType: String,
    @SerializedName("term") val term: String,
    @SerializedName("year") val year: Int,
    @SerializedName("amount") val amount: Double,
    @SerializedName("paid") val paid: Double,
    @SerializedName("balance") val balance: Double,
    @SerializedName("status") val status: String,
    @SerializedName("description") val description: String? = null
)
