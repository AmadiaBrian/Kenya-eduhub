package com.example.kenyaeduhubparents.ui.libraryfines

import android.widget.Toast
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.Button
import androidx.compose.material3.ButtonDefaults
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.DropdownMenuItem
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.ExposedDropdownMenuBox
import androidx.compose.material3.ExposedDropdownMenuDefaults
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.lifecycle.viewmodel.compose.viewModel
import com.example.kenyaeduhubparents.data.local.SessionManager
import com.example.kenyaeduhubparents.data.model.LibraryFine

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun LibraryFinesScreen(
    modifier: Modifier = Modifier,
    viewModel: LibraryFinesViewModel = viewModel(
        factory = LibraryFinesViewModel.Factory(LocalContext.current)
    )
) {
    val uiState by viewModel.uiState.collectAsState()
    val paymentDialogState by viewModel.paymentDialog.collectAsState()
    val context = LocalContext.current

    // Payment Processing Dialog
    if (paymentDialogState.isVisible) {
        AlertDialog(
            onDismissRequest = {
                if (paymentDialogState.type != LibraryFinesViewModel.DialogType.LOADING) {
                    viewModel.hidePaymentDialog()
                }
            },
            title = {
                Text(text = paymentDialogState.title, fontWeight = FontWeight.Bold)
            },
            text = {
                Column(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalAlignment = Alignment.CenterHorizontally,
                    verticalArrangement = Arrangement.spacedBy(16.dp)
                ) {
                    if (paymentDialogState.type == LibraryFinesViewModel.DialogType.LOADING) {
                        CircularProgressIndicator(color = MaterialTheme.colorScheme.primary)
                    }
                    Text(
                        text = paymentDialogState.message,
                        style = MaterialTheme.typography.bodyMedium,
                        textAlign = TextAlign.Center
                    )
                }
            },
            confirmButton = {
                if (paymentDialogState.type != LibraryFinesViewModel.DialogType.LOADING) {
                    Button(
                        onClick = { viewModel.hidePaymentDialog() },
                        colors = ButtonDefaults.buttonColors(containerColor = MaterialTheme.colorScheme.primary),
                        shape = RoundedCornerShape(20.dp)
                    ) {
                        Text("Close", color = Color.White)
                    }
                }
            },
            shape = RoundedCornerShape(24.dp)
        )
    }

    Column(
        modifier = modifier
            .fillMaxSize()
            .verticalScroll(rememberScrollState())
            .padding(16.dp),
        verticalArrangement = Arrangement.spacedBy(20.dp)
    ) {
        Column(
            modifier = Modifier.fillMaxWidth(),
            horizontalAlignment = Alignment.CenterHorizontally,
            verticalArrangement = Arrangement.spacedBy(4.dp)
        ) {
            Text(
                text = "Library Fines",
                style = MaterialTheme.typography.headlineMedium,
                fontWeight = FontWeight.Bold,
                color = MaterialTheme.colorScheme.onBackground
            )
            Text(
                text = "Track overdue books and payment status",
                style = MaterialTheme.typography.bodyMedium,
                color = MaterialTheme.colorScheme.onSurfaceVariant,
                textAlign = TextAlign.Center
            )
        }

        when (val state = uiState) {
            is LibraryFinesUiState.Loading -> {
                Box(
                    modifier = Modifier
                        .fillMaxWidth()
                        .height(250.dp),
                    contentAlignment = Alignment.Center
                ) {
                    CircularProgressIndicator(color = MaterialTheme.colorScheme.primary)
                }
            }
            is LibraryFinesUiState.Error -> {
                Card(
                    modifier = Modifier.fillMaxWidth(),
                    colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.errorContainer),
                    shape = RoundedCornerShape(16.dp)
                ) {
                    Column(
                        modifier = Modifier.padding(16.dp),
                        verticalArrangement = Arrangement.spacedBy(12.dp)
                    ) {
                        Text(
                            text = state.message,
                            color = MaterialTheme.colorScheme.onErrorContainer,
                            style = MaterialTheme.typography.bodyMedium
                        )
                        OutlinedButton(onClick = { viewModel.loadFines() }) {
                            Text("Retry")
                        }
                    }
                }
            }
            is LibraryFinesUiState.Success -> {
                val response = state.data
                val children = response.children

                if (children.isEmpty()) {
                    Card(
                        modifier = Modifier.fillMaxWidth(),
                        shape = RoundedCornerShape(16.dp),
                        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface)
                    ) {
                        Text(
                            text = "No children found.",
                            style = MaterialTheme.typography.bodyMedium,
                            color = MaterialTheme.colorScheme.onSurfaceVariant,
                            modifier = Modifier.padding(24.dp),
                            textAlign = TextAlign.Center
                        )
                    }
                } else {
                    val selectedChild = children.find { it.id.toString() == viewModel.selectedChildId } ?: children.first()
                    Card(
                        modifier = Modifier.fillMaxWidth(),
                        shape = RoundedCornerShape(20.dp),
                        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                        elevation = CardDefaults.cardElevation(defaultElevation = 1.dp)
                    ) {
                        Column(
                            modifier = Modifier.padding(18.dp),
                            verticalArrangement = Arrangement.spacedBy(14.dp)
                        ) {
                            Text(
                                text = "Select Child",
                                style = MaterialTheme.typography.titleMedium,
                                fontWeight = FontWeight.Bold,
                                color = MaterialTheme.colorScheme.onSurface
                            )

                            var childExpanded by remember { mutableStateOf(false) }
                            ExposedDropdownMenuBox(
                                expanded = childExpanded,
                                onExpandedChange = { childExpanded = !childExpanded }
                            ) {
                                OutlinedTextField(
                                    value = selectedChild.name,
                                    onValueChange = {},
                                    readOnly = true,
                                    label = { Text("Child") },
                                    trailingIcon = { ExposedDropdownMenuDefaults.TrailingIcon(expanded = childExpanded) },
                                    modifier = Modifier
                                        .fillMaxWidth()
                                        .menuAnchor(),
                                    shape = RoundedCornerShape(25.dp)
                                )
                                ExposedDropdownMenu(
                                    expanded = childExpanded,
                                    onDismissRequest = { childExpanded = false }
                                ) {
                                    children.forEach { child ->
                                        DropdownMenuItem(
                                            text = { Text("${child.name} (${child.admissionNumber})") },
                                            onClick = {
                                                viewModel.setSelectedChild(child.id.toString())
                                                childExpanded = false
                                            }
                                        )
                                    }
                                }
                            }
                        }
                    }

                    val selectedSummary = response.finesByChild[selectedChild.id.toString()]
                    val selectedFines = selectedSummary?.fines ?: emptyList()
                    val selectedOverdue = selectedSummary?.overdueBooks ?: emptyList()

                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.spacedBy(12.dp)
                    ) {
                        SummaryCard(
                            label = "Total Fines",
                            value = "KES ${String.format("%,.0f", response.fineStats.totalAmount)}",
                            modifier = Modifier.weight(1f)
                        )
                        SummaryCard(
                            label = "Outstanding",
                            value = "KES ${String.format("%,.0f", response.fineStats.unpaidAmount)}",
                            modifier = Modifier.weight(1f),
                            isNegative = true
                        )
                    }

                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.spacedBy(12.dp)
                    ) {
                        SummaryCard(
                            label = "Paid",
                            value = "KES ${String.format("%,.0f", response.fineStats.totalPaid)}",
                            modifier = Modifier.weight(1f),
                            isPositive = true
                        )
                        SummaryCard(
                            label = "Records",
                            value = response.fineStats.totalFines.toString(),
                            modifier = Modifier.weight(1f)
                        )
                    }

                    Card(
                        modifier = Modifier.fillMaxWidth(),
                        shape = RoundedCornerShape(20.dp),
                        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                        elevation = CardDefaults.cardElevation(defaultElevation = 1.dp)
                    ) {
                        Column(
                            modifier = Modifier.padding(18.dp),
                            verticalArrangement = Arrangement.spacedBy(12.dp)
                        ) {
                            Text(
                                text = "Overdue Books",
                                style = MaterialTheme.typography.titleMedium,
                                fontWeight = FontWeight.Bold,
                                color = MaterialTheme.colorScheme.onSurface
                            )

                            if (selectedOverdue.isEmpty()) {
                                Text(
                                    text = "No overdue books for this child.",
                                    style = MaterialTheme.typography.bodyMedium,
                                    color = MaterialTheme.colorScheme.onSurfaceVariant
                                )
                            } else {
                                selectedOverdue.forEach { overdue ->
                                    Row(
                                        modifier = Modifier
                                            .fillMaxWidth()
                                            .background(MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.35f), RoundedCornerShape(12.dp))
                                            .padding(12.dp),
                                        horizontalArrangement = Arrangement.SpaceBetween,
                                        verticalAlignment = Alignment.CenterVertically
                                    ) {
                                        Column(verticalArrangement = Arrangement.spacedBy(2.dp)) {
                                            Text(
                                                text = overdue.title,
                                                fontWeight = FontWeight.Bold,
                                                color = MaterialTheme.colorScheme.onSurface
                                            )
                                            Text(
                                                text = overdue.author ?: "Unknown author",
                                                style = MaterialTheme.typography.bodySmall,
                                                color = MaterialTheme.colorScheme.onSurfaceVariant
                                            )
                                        }
                                        Text(
                                            text = "${overdue.daysOverdue} days",
                                            fontWeight = FontWeight.Bold,
                                            color = Color(0xFFC5221F)
                                        )
                                    }
                                }
                            }
                        }
                    }

                    Card(
                        modifier = Modifier.fillMaxWidth(),
                        shape = RoundedCornerShape(20.dp),
                        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                        elevation = CardDefaults.cardElevation(defaultElevation = 1.dp)
                    ) {
                        Column(
                            modifier = Modifier.padding(18.dp),
                            verticalArrangement = Arrangement.spacedBy(12.dp)
                        ) {
                            Text(
                                text = "Fine Records",
                                style = MaterialTheme.typography.titleMedium,
                                fontWeight = FontWeight.Bold,
                                color = MaterialTheme.colorScheme.onSurface
                            )

                            if (selectedFines.isEmpty()) {
                                Text(
                                    text = "No library fines found for this child.",
                                    style = MaterialTheme.typography.bodyMedium,
                                    color = MaterialTheme.colorScheme.onSurfaceVariant
                                )
                            } else {
                                val parentPhone = response.parent?.displayPhone ?: SessionManager(context).getParentData()?.phone ?: ""
                                selectedFines.forEach { fine ->
                                    FineItemCard(
                                        fine = fine,
                                        parentPhone = parentPhone,
                                        viewModel = viewModel
                                    )
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}

@Composable
private fun SummaryCard(
    label: String,
    value: String,
    modifier: Modifier = Modifier,
    isPositive: Boolean = false,
    isNegative: Boolean = false
) {
    Card(
        modifier = modifier,
        shape = RoundedCornerShape(16.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        elevation = CardDefaults.cardElevation(defaultElevation = 1.dp)
    ) {
        Column(
            modifier = Modifier.padding(14.dp),
            verticalArrangement = Arrangement.spacedBy(4.dp)
        ) {
            Text(
                text = label,
                style = MaterialTheme.typography.bodySmall,
                color = MaterialTheme.colorScheme.onSurfaceVariant
            )
            Text(
                text = value,
                style = MaterialTheme.typography.titleMedium,
                fontWeight = FontWeight.Bold,
                color = when {
                    isPositive -> Color(0xFF137333)
                    isNegative -> Color(0xFFC5221F)
                    else -> MaterialTheme.colorScheme.onSurface
                }
            )
        }
    }
}

@Composable
private fun FineItemCard(
    fine: LibraryFine,
    parentPhone: String,
    viewModel: LibraryFinesViewModel
) {
    val context = LocalContext.current
    var showPaymentDialog by remember { mutableStateOf(false) }
    var paymentAmount by remember { mutableStateOf(fine.balance.toInt().toString()) }
    var paymentPhone by remember { mutableStateOf(parentPhone) }

    val statusText = fine.displayStatus.replaceFirstChar { it.uppercase() }
    val statusColor = when (fine.displayStatus.lowercase()) {
        "paid" -> Color(0xFF137333)
        "partial" -> Color(0xFFB26A00)
        "unpaid" -> Color(0xFFC5221F)
        else -> MaterialTheme.colorScheme.primary
    }

    if (showPaymentDialog) {
        AlertDialog(
            onDismissRequest = { showPaymentDialog = false },
            title = {
                Text(text = "Pay Library Fine", fontWeight = FontWeight.Bold)
            },
            text = {
                Column(
                    modifier = Modifier.fillMaxWidth(),
                    verticalArrangement = Arrangement.spacedBy(12.dp)
                ) {
                    Text(
                        text = "Book: ${fine.title.ifBlank { "Library Fine" }}",
                        style = MaterialTheme.typography.bodyMedium,
                        fontWeight = FontWeight.Medium
                    )
                    Text(
                        text = "Remaining Balance: KES ${String.format("%,.0f", fine.balance)}",
                        style = MaterialTheme.typography.bodySmall,
                        color = MaterialTheme.colorScheme.onSurfaceVariant
                    )

                    OutlinedTextField(
                        value = paymentAmount,
                        onValueChange = { paymentAmount = it },
                        label = { Text("Amount to Pay (KES)") },
                        modifier = Modifier.fillMaxWidth(),
                        shape = RoundedCornerShape(20.dp),
                        singleLine = true
                    )

                    OutlinedTextField(
                        value = paymentPhone,
                        onValueChange = { paymentPhone = it },
                        label = { Text("M-Pesa Phone Number") },
                        modifier = Modifier.fillMaxWidth(),
                        shape = RoundedCornerShape(20.dp),
                        singleLine = true
                    )
                }
            },
            confirmButton = {
                Button(
                    onClick = {
                        val amountVal = paymentAmount.toDoubleOrNull() ?: 0.0
                        if (amountVal <= 0) {
                            Toast.makeText(context, "Please enter a valid amount", Toast.LENGTH_SHORT).show()
                        } else if (amountVal > fine.balance) {
                            Toast.makeText(context, "Amount cannot exceed balance (KES ${fine.balance})", Toast.LENGTH_SHORT).show()
                        } else if (paymentPhone.isBlank()) {
                            Toast.makeText(context, "Please enter a phone number", Toast.LENGTH_SHORT).show()
                        } else {
                            showPaymentDialog = false
                            viewModel.payFineWithMpesa(
                                fineId = fine.id.toString(),
                                bookTitle = fine.title.ifBlank { "Library Fine" },
                                amount = paymentAmount,
                                phone = paymentPhone
                            )
                        }
                    },
                    colors = ButtonDefaults.buttonColors(containerColor = Color(0xFF137333)),
                    shape = RoundedCornerShape(20.dp)
                ) {
                    Text("Pay with M-Pesa", color = Color.White)
                }
            },
            dismissButton = {
                OutlinedButton(
                    onClick = { showPaymentDialog = false },
                    shape = RoundedCornerShape(20.dp)
                ) {
                    Text("Cancel")
                }
            },
            shape = RoundedCornerShape(24.dp)
        )
    }

    Card(
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(16.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.28f))
    ) {
        Column(
            modifier = Modifier.padding(14.dp),
            verticalArrangement = Arrangement.spacedBy(10.dp)
        ) {
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Column(modifier = Modifier.weight(1f)) {
                    Text(
                        text = fine.title.ifBlank { "Book title unavailable" },
                        fontWeight = FontWeight.Bold,
                        color = MaterialTheme.colorScheme.onSurface
                    )
                    Text(
                        text = fine.author ?: "Unknown author",
                        style = MaterialTheme.typography.bodySmall,
                        color = MaterialTheme.colorScheme.onSurfaceVariant
                    )
                }
                Box(
                    modifier = Modifier
                        .background(statusColor.copy(alpha = 0.12f), RoundedCornerShape(999.dp))
                        .border(1.dp, statusColor.copy(alpha = 0.35f), RoundedCornerShape(999.dp))
                        .padding(horizontal = 10.dp, vertical = 6.dp)
                ) {
                    Text(
                        text = statusText,
                        style = MaterialTheme.typography.labelMedium,
                        color = statusColor,
                        fontWeight = FontWeight.Bold
                    )
                }
            }

            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                InfoBlock(label = "Issue", value = fine.issueDate ?: "-", modifier = Modifier.weight(1f))
                InfoBlock(label = "Due", value = fine.dueDate ?: "-", modifier = Modifier.weight(1f))
            }

            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                InfoBlock(label = "Fine", value = "KES ${String.format("%,.0f", fine.amount)}", modifier = Modifier.weight(1f))
                InfoBlock(label = "Paid", value = "KES ${String.format("%,.0f", fine.amountPaid)}", modifier = Modifier.weight(1f))
            }

            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Text(
                    text = "Balance: KES ${String.format("%,.0f", fine.balance)}",
                    style = MaterialTheme.typography.bodyMedium,
                    fontWeight = FontWeight.Medium,
                    color = if (fine.balance > 0) Color(0xFFC5221F) else Color(0xFF137333)
                )
                Button(
                    onClick = {
                        if (fine.balance > 0) {
                            if (parentPhone.isBlank()) {
                                Toast.makeText(context, "Parent phone number not available", Toast.LENGTH_SHORT).show()
                            } else {
                                paymentAmount = fine.balance.toInt().toString()
                                paymentPhone = parentPhone
                                showPaymentDialog = true
                            }
                        }
                    },
                    enabled = fine.balance > 0,
                    colors = ButtonDefaults.buttonColors(
                        containerColor = Color(0xFF137333),
                        disabledContainerColor = MaterialTheme.colorScheme.primary.copy(alpha = 0.3f)
                    ),
                    shape = RoundedCornerShape(20.dp)
                ) {
                    Text("Pay with M-Pesa", color = Color.White)
                }
            }
        }
    }
}

@Composable
private fun InfoBlock(label: String, value: String, modifier: Modifier = Modifier) {
    Column(
        modifier = modifier,
        verticalArrangement = Arrangement.spacedBy(2.dp)
    ) {
        Text(
            text = label,
            style = MaterialTheme.typography.labelSmall,
            color = MaterialTheme.colorScheme.onSurfaceVariant
        )
        Text(
            text = value,
            style = MaterialTheme.typography.bodySmall,
            color = MaterialTheme.colorScheme.onSurface
        )
    }
}
