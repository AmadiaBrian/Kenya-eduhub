package com.example.kenyaeduhubparents.data.model

import com.google.gson.annotations.SerializedName

data class LibraryFinesResponse(
    @SerializedName("success") val success: Boolean,
    @SerializedName("error") val error: String? = null,
    @SerializedName("parent") val parent: ParentInfo? = null,
    @SerializedName("children") val children: List<Child> = emptyList(),
    @SerializedName("fines_by_child") val finesByChild: Map<String, ChildFineSummary> = emptyMap(),
    @SerializedName("fine_stats") val fineStats: FineStats = FineStats(),
    @SerializedName("overdue_books") val overdueBooks: List<OverdueBook> = emptyList()
)

data class FineStats(
    @SerializedName("total_fines") val totalFines: Int = 0,
    @SerializedName("total_amount") val totalAmount: Double = 0.0,
    @SerializedName("total_paid") val totalPaid: Double = 0.0,
    @SerializedName("unpaid_amount") val unpaidAmount: Double = 0.0,
    @SerializedName("paid_count") val paidCount: Int = 0,
    @SerializedName("unpaid_count") val unpaidCount: Int = 0,
    @SerializedName("partial_count") val partialCount: Int = 0
)

data class ChildFineSummary(
    @SerializedName("child") val child: Child? = null,
    @SerializedName("fines") val fines: List<LibraryFine> = emptyList(),
    @SerializedName("overdue_books") val overdueBooks: List<OverdueBook> = emptyList(),
    @SerializedName("total_fines") val totalFines: Int = 0,
    @SerializedName("total_amount") val totalAmount: Double = 0.0,
    @SerializedName("total_paid") val totalPaid: Double = 0.0,
    @SerializedName("unpaid_amount") val unpaidAmount: Double = 0.0
)

data class LibraryFine(
    @SerializedName("id") val id: Int = 0,
    @SerializedName("book_id") val bookId: Int = 0,
    @SerializedName("title") val title: String = "",
    @SerializedName("author") val author: String? = null,
    @SerializedName("user_type") val userType: String? = null,
    @SerializedName("user_name") val userName: String? = null,
    @SerializedName("user_identifier") val userIdentifier: String? = null,
    @SerializedName("student_id") val studentId: Int? = null,
    @SerializedName("issue_date") val issueDate: String? = null,
    @SerializedName("due_date") val dueDate: String? = null,
    @SerializedName("amount") val amount: Double = 0.0,
    @SerializedName("amount_paid") val amountPaid: Double = 0.0,
    @SerializedName("status") val status: String = "unpaid",
    @SerializedName("payment_status") val paymentStatus: String? = null
) {
    val displayStatus: String
        get() = status.ifBlank { paymentStatus ?: "unpaid" }

    val balance: Double
        get() = (amount - amountPaid).coerceAtLeast(0.0)
}

data class OverdueBook(
    @SerializedName("id") val id: Int = 0,
    @SerializedName("book_id") val bookId: Int = 0,
    @SerializedName("title") val title: String = "",
    @SerializedName("author") val author: String? = null,
    @SerializedName("isbn") val isbn: String? = null,
    @SerializedName("borrower_id") val borrowerId: Int = 0,
    @SerializedName("borrower_type") val borrowerType: String? = null,
    @SerializedName("student_name") val studentName: String? = null,
    @SerializedName("admission_number") val admissionNumber: String? = null,
    @SerializedName("days_overdue") val daysOverdue: Int = 0,
    @SerializedName("due_date") val dueDate: String? = null,
    @SerializedName("status") val status: String? = null
)
