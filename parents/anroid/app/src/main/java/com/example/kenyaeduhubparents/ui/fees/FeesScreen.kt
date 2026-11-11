package com.example.kenyaeduhubparents.ui.fees

import android.app.DownloadManager
import android.content.ContentValues
import android.content.Context
import android.net.Uri
import android.os.Environment
import android.provider.MediaStore
import android.widget.Toast
import androidx.compose.runtime.rememberCoroutineScope
import com.example.kenyaeduhubparents.data.local.SessionManager
import com.example.kenyaeduhubparents.data.network.ApiConfig
import com.example.kenyaeduhubparents.data.model.GeneratePdfResponse
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext
import okhttp3.OkHttpClient
import okhttp3.Request
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.horizontalScroll
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.width
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
import androidx.compose.material3.HorizontalDivider
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
import androidx.compose.ui.unit.sp
import androidx.lifecycle.viewmodel.compose.viewModel

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun FeesScreen(
    modifier: Modifier = Modifier,
    viewModel: FeesViewModel = viewModel(
        factory = FeesViewModel.Factory(LocalContext.current)
    )
) {
    val uiState by viewModel.uiState.collectAsState()
    val paymentDialogState by viewModel.paymentDialog.collectAsState()
    val context = LocalContext.current

    // Payment Processing Dialog
    if (paymentDialogState.isVisible) {
        AlertDialog(
            onDismissRequest = {
                if (paymentDialogState.type != FeesViewModel.DialogType.LOADING) {
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
                    if (paymentDialogState.type == FeesViewModel.DialogType.LOADING) {
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
                if (paymentDialogState.type != FeesViewModel.DialogType.LOADING) {
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
        // Page Title
        Column(
            modifier = Modifier.fillMaxWidth(),
            horizontalAlignment = Alignment.CenterHorizontally,
            verticalArrangement = Arrangement.spacedBy(4.dp)
        ) {
            Text(
                text = "Fee Payments",
                style = MaterialTheme.typography.headlineMedium,
                fontWeight = FontWeight.Bold,
                color = MaterialTheme.colorScheme.onBackground
            )
            Text(
                text = "View fee payment history and balances",
                style = MaterialTheme.typography.bodyMedium,
                color = MaterialTheme.colorScheme.onSurfaceVariant,
                textAlign = TextAlign.Center
            )
        }

        when (val state = uiState) {
            is FeesUiState.Loading -> {
                Box(
                    modifier = Modifier
                        .fillMaxWidth()
                        .height(250.dp),
                    contentAlignment = Alignment.Center
                ) {
                    CircularProgressIndicator(color = MaterialTheme.colorScheme.primary)
                }
            }
            is FeesUiState.Error -> {
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
                        OutlinedButton(onClick = { viewModel.loadFees() }) {
                            Text("Retry")
                        }
                    }
                }
            }
            is FeesUiState.Success -> {
                val data = state.data
                val children = data.children

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
                    // Select Child Card
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
                            val selectedChild = children.find { it.id.toString() == viewModel.selectedChildId } ?: children.firstOrNull()

                            ExposedDropdownMenuBox(
                                expanded = childExpanded,
                                onExpandedChange = { childExpanded = !childExpanded }
                            ) {
                                OutlinedTextField(
                                    value = selectedChild?.name ?: "Select a child",
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

                            if (selectedChild != null) {
                                Spacer(modifier = Modifier.height(4.dp))
                                val sessionManager = SessionManager(context)
                                val scope = rememberCoroutineScope()

                                Button(
                                    onClick = {
                                        scope.launch(Dispatchers.IO) {
                                            try {
                                                val sessionId = sessionManager.getSessionId()
                                                if (sessionId.isNullOrBlank()) {
                                                    withContext(Dispatchers.Main) {
                                                        Toast.makeText(context, "Session unavailable. Please sign in again.", Toast.LENGTH_LONG).show()
                                                    }
                                                    return@launch
                                                }

                                                withContext(Dispatchers.Main) {
                                                    Toast.makeText(context, "Generating fee statement...", Toast.LENGTH_SHORT).show()
                                                }

                                                val client = OkHttpClient.Builder().build()
                                                val formBody = okhttp3.FormBody.Builder()
                                                    .add("student_id", selectedChild.id.toString())
                                                    .build()

                                                val genRequest = Request.Builder()
                                                    .url(ApiConfig.FEE_STATEMENT_DOWNLOAD_URL)
                                                    .post(formBody)
                                                    .header("ngrok-skip-browser-warning", "true")
                                                    .header("Cookie", "${ApiConfig.PHP_SESSION_COOKIE}=$sessionId")
                                                    .build()

                                                val genResponseJson = client.newCall(genRequest).execute().use { resp ->
                                                    if (!resp.isSuccessful) {
                                                        withContext(Dispatchers.Main) {
                                                            Toast.makeText(context, "Generation failed (Server Error ${resp.code})", Toast.LENGTH_LONG).show()
                                                        }
                                                        return@launch
                                                    }
                                                    resp.body?.string() ?: throw IllegalStateException("Empty response")
                                                }

                                                val gson = com.google.gson.Gson()
                                                val pdfResp = gson.fromJson(genResponseJson, GeneratePdfResponse::class.java)

                                                if (!pdfResp.success || pdfResp.resolvedUrl.isNullOrBlank()) {
                                                    withContext(Dispatchers.Main) {
                                                        Toast.makeText(context, pdfResp.error ?: "Failed to generate fee statement", Toast.LENGTH_LONG).show()
                                                    }
                                                    return@launch
                                                }

                                                withContext(Dispatchers.Main) {
                                                    Toast.makeText(context, "Downloading PDF...", Toast.LENGTH_SHORT).show()
                                                }

                                                val pdfUrl = "${ApiConfig.BASE_URL.substringBeforeLast("/parents")}${pdfResp.resolvedUrl}"
                                                val pdfRequest = Request.Builder()
                                                    .url(pdfUrl)
                                                    .header("ngrok-skip-browser-warning", "true")
                                                    .header("Cookie", "${ApiConfig.PHP_SESSION_COOKIE}=$sessionId")
                                                    .build()

                                                client.newCall(pdfRequest).execute().use { pdfRespCall ->
                                                    if (!pdfRespCall.isSuccessful) {
                                                        withContext(Dispatchers.Main) {
                                                            Toast.makeText(context, "Download failed (Server Error ${pdfRespCall.code})", Toast.LENGTH_LONG).show()
                                                        }
                                                        return@launch
                                                    }

                                                    val bytes = pdfRespCall.body?.bytes() ?: throw IllegalStateException("Empty PDF body")
                                                    val fileName = pdfResp.resolvedFilename ?: "fee_statement_${selectedChild.id}.pdf"
                                                    val saved = saveFileToDownloads(context, bytes, fileName)

                                                    withContext(Dispatchers.Main) {
                                                        if (saved) {
                                                            Toast.makeText(context, "Saved to Downloads/KenyaEduHub/$fileName", Toast.LENGTH_LONG).show()
                                                        } else {
                                                            Toast.makeText(context, "Failed to save file", Toast.LENGTH_SHORT).show()
                                                        }
                                                    }
                                                }
                                            } catch (e: Exception) {
                                                withContext(Dispatchers.Main) {
                                                    Toast.makeText(context, "Error: ${e.localizedMessage}", Toast.LENGTH_LONG).show()
                                                }
                                            }
                                        }
                                    },
                                    modifier = Modifier
                                        .fillMaxWidth()
                                        .height(42.dp),
                                    shape = RoundedCornerShape(25.dp),
                                    colors = ButtonDefaults.buttonColors(containerColor = Color(0xFFFF6B35))
                                ) {
                                    Text("Generate Fee Statement", fontWeight = FontWeight.Bold, color = Color.White, fontSize = 13.sp)
                                }

                                Button(
                                    onClick = {
                                        scope.launch(Dispatchers.IO) {
                                            try {
                                                val sessionId = sessionManager.getSessionId()
                                                if (sessionId.isNullOrBlank()) {
                                                    withContext(Dispatchers.Main) {
                                                        Toast.makeText(context, "Session unavailable. Please sign in again.", Toast.LENGTH_LONG).show()
                                                    }
                                                    return@launch
                                                }

                                                withContext(Dispatchers.Main) {
                                                    Toast.makeText(context, "Generating fee structure...", Toast.LENGTH_SHORT).show()
                                                }

                                                val client = OkHttpClient.Builder().build()
                                                val formBody = okhttp3.FormBody.Builder()
                                                    .add("student_id", selectedChild.id.toString())
                                                    .build()

                                                val genRequest = Request.Builder()
                                                    .url(ApiConfig.FEE_STRUCTURE_DOWNLOAD_URL)
                                                    .post(formBody)
                                                    .header("ngrok-skip-browser-warning", "true")
                                                    .header("Cookie", "${ApiConfig.PHP_SESSION_COOKIE}=$sessionId")
                                                    .build()

                                                val genResponseJson = client.newCall(genRequest).execute().use { resp ->
                                                    if (!resp.isSuccessful) {
                                                        withContext(Dispatchers.Main) {
                                                            Toast.makeText(context, "Generation failed (Server Error ${resp.code})", Toast.LENGTH_LONG).show()
                                                        }
                                                        return@launch
                                                    }
                                                    resp.body?.string() ?: throw IllegalStateException("Empty response")
                                                }

                                                val gson = com.google.gson.Gson()
                                                val pdfResp = gson.fromJson(genResponseJson, GeneratePdfResponse::class.java)

                                                if (!pdfResp.success || pdfResp.resolvedUrl.isNullOrBlank()) {
                                                    withContext(Dispatchers.Main) {
                                                        Toast.makeText(context, pdfResp.error ?: "Failed to generate fee structure", Toast.LENGTH_LONG).show()
                                                    }
                                                    return@launch
                                                }

                                                withContext(Dispatchers.Main) {
                                                    Toast.makeText(context, "Downloading PDF...", Toast.LENGTH_SHORT).show()
                                                }

                                                val pdfUrl = "${ApiConfig.BASE_URL.substringBeforeLast("/parents")}${pdfResp.resolvedUrl}"
                                                val pdfRequest = Request.Builder()
                                                    .url(pdfUrl)
                                                    .header("ngrok-skip-browser-warning", "true")
                                                    .header("Cookie", "${ApiConfig.PHP_SESSION_COOKIE}=$sessionId")
                                                    .build()

                                                client.newCall(pdfRequest).execute().use { pdfRespCall ->
                                                    if (!pdfRespCall.isSuccessful) {
                                                        withContext(Dispatchers.Main) {
                                                            Toast.makeText(context, "Download failed (Server Error ${pdfRespCall.code})", Toast.LENGTH_LONG).show()
                                                        }
                                                        return@launch
                                                    }

                                                    val bytes = pdfRespCall.body?.bytes() ?: throw IllegalStateException("Empty PDF body")
                                                    val fileName = pdfResp.resolvedFilename ?: "fee_structure_${selectedChild.id}.pdf"
                                                    val saved = saveFileToDownloads(context, bytes, fileName)

                                                    withContext(Dispatchers.Main) {
                                                        if (saved) {
                                                            Toast.makeText(context, "Saved to Downloads/KenyaEduHub/$fileName", Toast.LENGTH_LONG).show()
                                                        } else {
                                                            Toast.makeText(context, "Failed to save file", Toast.LENGTH_SHORT).show()
                                                        }
                                                    }
                                                }
                                            } catch (e: Exception) {
                                                withContext(Dispatchers.Main) {
                                                    Toast.makeText(context, "Error: ${e.localizedMessage}", Toast.LENGTH_LONG).show()
                                                }
                                            }
                                        }
                                    },
                                    modifier = Modifier
                                        .fillMaxWidth()
                                        .height(42.dp),
                                    shape = RoundedCornerShape(25.dp),
                                    colors = ButtonDefaults.buttonColors(containerColor = Color(0xFFFF6B35))
                                ) {
                                    Text("Fee Structure Document", fontWeight = FontWeight.Bold, color = Color.White, fontSize = 13.sp)
                                }
                            }
                        }
                    }

                    // Fee Data for Selected Child
                    val selectedChild = children.find { it.id.toString() == viewModel.selectedChildId } ?: children.firstOrNull()
                    val currentChildId = selectedChild?.id?.toString()
                    val feeWrapper = currentChildId?.let { data.feeData[it] }

                    if (feeWrapper != null && selectedChild != null) {
                        // M-Pesa Payment Form Card with Fee Type Dropdown & System Phone Number (Read-Only)
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
                                    text = "Pay Fees via M-Pesa",
                                    style = MaterialTheme.typography.titleMedium,
                                    fontWeight = FontWeight.Bold,
                                    color = MaterialTheme.colorScheme.onSurface
                                )

                                val feeTypes = mutableListOf("Tuition")
                                feeWrapper.feeStructureStatus.forEach { fs ->
                                    if (!feeTypes.contains(fs.feeType)) {
                                        feeTypes.add(fs.feeType)
                                    }
                                }

                                var feeTypeExpanded by remember { mutableStateOf(false) }
                                var selectedFeeType by remember { mutableStateOf("Tuition") }

                                fun getBalanceForType(type: String): String {
                                    val matching = feeWrapper.feeStructureStatus.find { it.feeType.equals(type, ignoreCase = true) }
                                    return if (matching != null && matching.balance > 0) matching.balance.toInt().toString() else feeWrapper.yearBalance.toInt().toString()
                                }

                                var amount by remember(selectedFeeType) { mutableStateOf(getBalanceForType(selectedFeeType)) }

                                // Parent phone number automatically loaded from system database / session (read-only)
                                val systemPhone = data.parent?.displayPhone?.takeIf { it.isNotBlank() } ?: SessionManager(context).getParentData()?.phone ?: ""

                                // Fee Type Dropdown
                                ExposedDropdownMenuBox(
                                    expanded = feeTypeExpanded,
                                    onExpandedChange = { feeTypeExpanded = !feeTypeExpanded }
                                ) {
                                    OutlinedTextField(
                                        value = selectedFeeType,
                                        onValueChange = {},
                                        readOnly = true,
                                        label = { Text("Fee Type") },
                                        trailingIcon = { ExposedDropdownMenuDefaults.TrailingIcon(expanded = feeTypeExpanded) },
                                        modifier = Modifier
                                            .fillMaxWidth()
                                            .menuAnchor(),
                                        shape = RoundedCornerShape(25.dp)
                                    )
                                    ExposedDropdownMenu(
                                        expanded = feeTypeExpanded,
                                        onDismissRequest = { feeTypeExpanded = false }
                                    ) {
                                        feeTypes.forEach { type ->
                                            DropdownMenuItem(
                                                text = { Text(type) },
                                                onClick = {
                                                    selectedFeeType = type
                                                    amount = getBalanceForType(type)
                                                    feeTypeExpanded = false
                                                }
                                            )
                                        }
                                    }
                                }

                                OutlinedTextField(
                                    value = amount,
                                    onValueChange = { amount = it },
                                    label = { Text("Amount (KES)") },
                                    modifier = Modifier.fillMaxWidth(),
                                    shape = RoundedCornerShape(25.dp),
                                    singleLine = true
                                )

                                // Read-only M-Pesa Phone Number from system database
                                OutlinedTextField(
                                    value = systemPhone,
                                    onValueChange = {},
                                    readOnly = true,
                                    label = { Text("M-Pesa Phone Number (System)") },
                                    modifier = Modifier.fillMaxWidth(),
                                    shape = RoundedCornerShape(25.dp),
                                    singleLine = true
                                )

                                Button(
                                    onClick = {
                                        if (amount.isBlank() || systemPhone.isBlank()) {
                                            Toast.makeText(context, "Amount or system phone number is missing", Toast.LENGTH_SHORT).show()
                                        } else {
                                            viewModel.payWithMpesa(
                                                studentId = selectedChild.id.toString(),
                                                feeType = selectedFeeType,
                                                term = data.currentTerm ?: "Term 1",
                                                year = data.currentYear.toString(),
                                                amount = amount,
                                                phone = systemPhone
                                            )
                                        }
                                    },
                                    modifier = Modifier
                                        .fillMaxWidth()
                                        .height(48.dp),
                                    shape = RoundedCornerShape(25.dp),
                                    colors = ButtonDefaults.buttonColors(containerColor = Color(0xFF137333))
                                ) {
                                    Text("Pay with M-Pesa", fontWeight = FontWeight.Bold, color = Color.White, fontSize = 15.sp)
                                }
                            }
                        }

                        // Fee Summary Cards
                        Card(
                            modifier = Modifier.fillMaxWidth(),
                            shape = RoundedCornerShape(20.dp),
                            colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                            elevation = CardDefaults.cardElevation(defaultElevation = 1.dp)
                        ) {
                            Column(
                                modifier = Modifier.padding(18.dp),
                                verticalArrangement = Arrangement.spacedBy(16.dp)
                            ) {
                                Text(
                                    text = "Fee Summary - ${feeWrapper.currentYear}",
                                    style = MaterialTheme.typography.titleMedium,
                                    fontWeight = FontWeight.Bold,
                                    color = MaterialTheme.colorScheme.onSurface
                                )

                                Column(verticalArrangement = Arrangement.spacedBy(12.dp)) {
                                    feeWrapper.termBalances.forEach { (term, balance) ->
                                        FeeSummaryRow(
                                            label = "$term Balance",
                                            fee = balance.fees,
                                            paid = balance.paid,
                                            balance = balance.balance
                                        )
                                    }
                                    HorizontalDivider(color = MaterialTheme.colorScheme.outlineVariant)
                                    FeeSummaryRow(
                                        label = "Year Total Balance",
                                        fee = feeWrapper.yearTotalFees,
                                        paid = feeWrapper.yearTotalPaid,
                                        balance = feeWrapper.yearBalance,
                                        isTotal = true
                                    )
                                }
                            }
                        }

                        // Fee Structure Status Table
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
                                    text = "Fee Structure Status",
                                    style = MaterialTheme.typography.titleMedium,
                                    fontWeight = FontWeight.Bold,
                                    color = MaterialTheme.colorScheme.onSurface
                                )

                                if (feeWrapper.feeStructureStatus.isEmpty()) {
                                    Text(
                                        text = "No fee structures found for this student.",
                                        style = MaterialTheme.typography.bodyMedium,
                                        color = MaterialTheme.colorScheme.onSurfaceVariant
                                    )
                                } else {
                                    val horizontalScrollState = rememberScrollState()
                                    Box(
                                        modifier = Modifier
                                            .fillMaxWidth()
                                            .horizontalScroll(horizontalScrollState)
                                    ) {
                                        Column(verticalArrangement = Arrangement.spacedBy(6.dp)) {
                                            Row(
                                                modifier = Modifier
                                                    .background(Color(0xFFF0F0F0))
                                                    .border(1.dp, Color.Black)
                                                    .padding(8.dp),
                                                horizontalArrangement = Arrangement.spacedBy(12.dp)
                                            ) {
                                                Text(text = "Fee Type", fontWeight = FontWeight.Bold, fontSize = 12.sp, modifier = Modifier.width(100.dp))
                                                Text(text = "Term", fontWeight = FontWeight.Bold, fontSize = 12.sp, modifier = Modifier.width(70.dp))
                                                Text(text = "Amount", fontWeight = FontWeight.Bold, fontSize = 12.sp, modifier = Modifier.width(80.dp))
                                                Text(text = "Paid", fontWeight = FontWeight.Bold, fontSize = 12.sp, modifier = Modifier.width(80.dp))
                                                Text(text = "Balance", fontWeight = FontWeight.Bold, fontSize = 12.sp, modifier = Modifier.width(80.dp))
                                                Text(text = "Status", fontWeight = FontWeight.Bold, fontSize = 12.sp, modifier = Modifier.width(80.dp))
                                            }

                                            feeWrapper.feeStructureStatus.forEach { status ->
                                                Row(
                                                    modifier = Modifier
                                                        .border(1.dp, Color.LightGray)
                                                        .padding(8.dp),
                                                    horizontalArrangement = Arrangement.spacedBy(12.dp),
                                                    verticalAlignment = Alignment.CenterVertically
                                                ) {
                                                    Text(text = status.feeType, fontSize = 12.sp, color = Color.Black, modifier = Modifier.width(100.dp))
                                                    Text(text = status.term, fontSize = 12.sp, color = Color.Black, modifier = Modifier.width(70.dp))
                                                    Text(text = "KES ${String.format("%,.0f", status.amount)}", fontSize = 12.sp, color = Color.Black, modifier = Modifier.width(80.dp))
                                                    Text(text = "KES ${String.format("%,.0f", status.paid)}", fontSize = 12.sp, color = Color.Black, modifier = Modifier.width(80.dp))
                                                    Text(text = "KES ${String.format("%,.0f", status.balance)}", fontWeight = FontWeight.Bold, fontSize = 12.sp, color = Color.Black, modifier = Modifier.width(80.dp))
                                                    val statusColor = if (status.balance <= 0) Color(0xFF137333) else Color(0xFFC5221F)
                                                    Text(text = status.status, fontWeight = FontWeight.Bold, fontSize = 12.sp, color = statusColor, modifier = Modifier.width(80.dp))
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }

                        // Payment History Table
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
                                    text = "Payment History",
                                    style = MaterialTheme.typography.titleMedium,
                                    fontWeight = FontWeight.Bold,
                                    color = MaterialTheme.colorScheme.onSurface
                                )

                                if (feeWrapper.payments.isEmpty()) {
                                    Text(
                                        text = "No payment records found.",
                                        style = MaterialTheme.typography.bodyMedium,
                                        color = MaterialTheme.colorScheme.onSurfaceVariant
                                    )
                                } else {
                                    val horizontalScrollState = rememberScrollState()
                                    Box(
                                        modifier = Modifier
                                            .fillMaxWidth()
                                            .horizontalScroll(horizontalScrollState)
                                    ) {
                                        Column(verticalArrangement = Arrangement.spacedBy(6.dp)) {
                                            Row(
                                                modifier = Modifier
                                                    .background(Color(0xFFF0F0F0))
                                                    .border(1.dp, Color.Black)
                                                    .padding(8.dp),
                                                horizontalArrangement = Arrangement.spacedBy(12.dp)
                                            ) {
                                                Text(text = "Date", fontWeight = FontWeight.Bold, fontSize = 12.sp, modifier = Modifier.width(90.dp))
                                                Text(text = "Fee Type", fontWeight = FontWeight.Bold, fontSize = 12.sp, modifier = Modifier.width(90.dp))
                                                Text(text = "Amount", fontWeight = FontWeight.Bold, fontSize = 12.sp, modifier = Modifier.width(80.dp))
                                                Text(text = "Method", fontWeight = FontWeight.Bold, fontSize = 12.sp, modifier = Modifier.width(80.dp))
                                                Text(text = "Receipt", fontWeight = FontWeight.Bold, fontSize = 12.sp, modifier = Modifier.width(90.dp))
                                                Text(text = "Status", fontWeight = FontWeight.Bold, fontSize = 12.sp, modifier = Modifier.width(70.dp))
                                            }

                                            feeWrapper.payments.forEach { payment ->
                                                Row(
                                                    modifier = Modifier
                                                        .border(1.dp, Color.LightGray)
                                                        .padding(8.dp),
                                                    horizontalArrangement = Arrangement.spacedBy(12.dp),
                                                    verticalAlignment = Alignment.CenterVertically
                                                ) {
                                                    Text(text = payment.paymentDate, fontSize = 12.sp, color = Color.Black, modifier = Modifier.width(90.dp))
                                                    Text(text = payment.feeType ?: "Tuition", fontSize = 12.sp, color = Color.Black, modifier = Modifier.width(90.dp))
                                                    Text(text = "KES ${String.format("%,.0f", payment.amount)}", fontSize = 12.sp, color = Color.Black, modifier = Modifier.width(80.dp))
                                                    Text(text = payment.paymentMethod ?: "-", fontSize = 12.sp, color = Color.Black, modifier = Modifier.width(80.dp))
                                                    Text(text = payment.receiptNumber ?: "-", fontSize = 12.sp, color = Color.Black, modifier = Modifier.width(90.dp))
                                                    Text(text = payment.status, fontWeight = FontWeight.Bold, fontSize = 12.sp, color = Color(0xFF137333), modifier = Modifier.width(70.dp))
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}

private fun saveFileToDownloads(context: Context, bytes: ByteArray, fileName: String): Boolean {
    return try {
        val resolver = context.contentResolver
        val contentValues = ContentValues().apply {
            put(MediaStore.MediaColumns.DISPLAY_NAME, fileName)
            put(MediaStore.MediaColumns.MIME_TYPE, "application/pdf")
            put(MediaStore.MediaColumns.RELATIVE_PATH, Environment.DIRECTORY_DOWNLOADS + "/KenyaEduHub")
        }
        val uri = resolver.insert(MediaStore.Files.getContentUri("external"), contentValues)
        if (uri != null) {
            resolver.openOutputStream(uri)?.use { outputStream ->
                outputStream.write(bytes)
            }
            true
        } else {
            false
        }
    } catch (e: Exception) {
        e.printStackTrace()
        false
    }
}


@Composable
fun FeeSummaryRow(
    label: String,
    fee: Double,
    paid: Double,
    balance: Double,
    isTotal: Boolean = false
) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(12.dp),
        colors = CardDefaults.cardColors(
            containerColor = if (isTotal) MaterialTheme.colorScheme.primaryContainer.copy(alpha = 0.5f)
            else MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.3f)
        ),
        elevation = CardDefaults.cardElevation(defaultElevation = 0.dp)
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
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                val balanceColor = if (balance > 0) Color(0xFFC5221F) else Color(0xFF137333)
                Text(
                    text = "KES ${String.format("%,.0f", balance)}",
                    style = MaterialTheme.typography.titleMedium,
                    fontWeight = FontWeight.Bold,
                    color = balanceColor
                )
                Text(
                    text = "Paid: KES ${String.format("%,.0f", paid)} / ${String.format("%,.0f", fee)}",
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )
            }
        }
    }
}

