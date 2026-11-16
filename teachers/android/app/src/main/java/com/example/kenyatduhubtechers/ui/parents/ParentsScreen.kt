package com.example.kenyatduhubtechers.ui.parents

import android.content.ContentValues
import android.content.Context
import android.content.Intent
import android.net.Uri
import android.os.Build
import android.os.Environment
import android.provider.MediaStore
import android.widget.Toast
import androidx.compose.animation.AnimatedVisibility
import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.horizontalScroll
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.ExperimentalLayoutApi
import androidx.compose.foundation.layout.FlowRow
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.CalendarMonth
import androidx.compose.material.icons.filled.Close
import androidx.compose.material.icons.filled.DeleteSweep
import androidx.compose.material.icons.filled.Download
import androidx.compose.material.icons.filled.Event
import androidx.compose.material.icons.filled.Groups
import androidx.compose.material.icons.filled.Mail
import androidx.compose.material.icons.filled.Man
import androidx.compose.material.icons.filled.Phone
import androidx.compose.material.icons.filled.Refresh
import androidx.compose.material.icons.filled.School
import androidx.compose.material.icons.filled.Search
import androidx.compose.material.icons.filled.Send
import androidx.compose.material.icons.filled.Visibility
import androidx.compose.material.icons.filled.Woman
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
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.OutlinedTextFieldDefaults
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontFamily
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.compose.ui.window.Dialog
import androidx.core.content.FileProvider
import com.example.kenyatduhubtechers.data.model.ParentItemDto
import com.example.kenyatduhubtechers.ui.theme.EduHubBackground
import com.example.kenyatduhubtechers.ui.theme.EduHubBorder
import com.example.kenyatduhubtechers.ui.theme.EduHubError
import com.example.kenyatduhubtechers.ui.theme.EduHubErrorContainer
import com.example.kenyatduhubtechers.ui.theme.EduHubOrange
import com.example.kenyatduhubtechers.ui.theme.EduHubSuccess
import com.example.kenyatduhubtechers.ui.theme.EduHubSuccessContainer
import com.example.kenyatduhubtechers.ui.theme.EduHubTextDark
import com.example.kenyatduhubtechers.ui.theme.EduHubTextMuted
import java.io.File
import java.text.SimpleDateFormat
import java.util.Date
import java.util.Locale

@OptIn(ExperimentalLayoutApi::class, ExperimentalMaterial3Api::class)
@Composable
fun ParentsScreen(
    viewModel: ParentsViewModel,
    modifier: Modifier = Modifier
) {
    val uiState by viewModel.uiState.collectAsState()
    val context = LocalContext.current

    if (uiState.isLoading) {
        Box(
            modifier = modifier.fillMaxSize(),
            contentAlignment = Alignment.Center
        ) {
            CircularProgressIndicator(color = EduHubOrange)
        }
        return
    }

    // Filter parents
    val filteredParents = remember(
        uiState.parents,
        uiState.searchQuery,
        uiState.relationshipFilter
    ) {
        uiState.parents.filter { parent ->
            val query = uiState.searchQuery.lowercase().trim()
            val matchesSearch = query.isEmpty() ||
                    parent.email.lowercase().contains(query) ||
                    parent.phone.contains(query) ||
                    parent.fullName.lowercase().contains(query)

            val matchesRelationship = uiState.relationshipFilter.isEmpty() ||
                    parent.relationship?.equals(uiState.relationshipFilter, ignoreCase = true) == true

            matchesSearch && matchesRelationship
        }
    }

    Column(
        modifier = modifier
            .fillMaxSize()
            .verticalScroll(rememberScrollState())
            .padding(12.dp)
    ) {
        Text(
            text = "Parent Affairs",
            fontSize = 22.sp,
            fontWeight = FontWeight.Bold,
            color = EduHubTextDark,
            modifier = Modifier.padding(bottom = 16.dp)
        )

        // Success / Error Alerts
        AnimatedVisibility(visible = uiState.successMessage != null) {
            uiState.successMessage?.let { msg ->
                Card(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(bottom = 12.dp),
                    colors = CardDefaults.cardColors(containerColor = EduHubSuccessContainer),
                    border = BorderStroke(1.dp, EduHubSuccess),
                    shape = RoundedCornerShape(8.dp)
                ) {
                    Row(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(12.dp),
                        verticalAlignment = Alignment.CenterVertically,
                        horizontalArrangement = Arrangement.SpaceBetween
                    ) {
                        Text(text = msg, color = EduHubSuccess, fontSize = 13.sp, modifier = Modifier.weight(1f))
                        IconButton(onClick = { viewModel.clearMessages() }, modifier = Modifier.size(24.dp)) {
                            Icon(Icons.Default.Close, contentDescription = null, tint = EduHubSuccess)
                        }
                    }
                }
            }
        }

        AnimatedVisibility(visible = uiState.errorMessage != null) {
            uiState.errorMessage?.let { error ->
                Card(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(bottom = 12.dp),
                    colors = CardDefaults.cardColors(containerColor = EduHubErrorContainer),
                    border = BorderStroke(1.dp, EduHubError),
                    shape = RoundedCornerShape(8.dp)
                ) {
                    Row(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(12.dp),
                        verticalAlignment = Alignment.CenterVertically,
                        horizontalArrangement = Arrangement.SpaceBetween
                    ) {
                        Text(text = error, color = EduHubError, fontSize = 13.sp, modifier = Modifier.weight(1f))
                        IconButton(onClick = { viewModel.clearMessages() }, modifier = Modifier.size(24.dp)) {
                            Icon(Icons.Default.Close, contentDescription = null, tint = EduHubError)
                        }
                    }
                }
            }
        }

        // Sleek Orange Parent Affairs Status Card (Matching Dashboard Status Card Style)
        Card(
            modifier = Modifier
                .fillMaxWidth()
                .padding(bottom = 16.dp),
            shape = RoundedCornerShape(12.dp),
            colors = CardDefaults.cardColors(containerColor = EduHubOrange),
            elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)
        ) {
            Column(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(20.dp)
            ) {
                Text(
                    text = "Parent Affairs & Communication",
                    fontSize = 18.sp,
                    fontWeight = FontWeight.Bold,
                    color = Color.White
                )

                Spacer(modifier = Modifier.height(12.dp))

                Row(verticalAlignment = Alignment.CenterVertically) {
                    Icon(
                        imageVector = Icons.Default.Groups,
                        contentDescription = null,
                        tint = Color.White,
                        modifier = Modifier.size(28.dp)
                    )
                    Spacer(modifier = Modifier.width(12.dp))
                    Column {
                        Text(
                            text = "Registered Parents & Guardians",
                            fontSize = 15.sp,
                            fontWeight = FontWeight.Bold,
                            color = Color.White
                        )
                        Text(
                            text = "Total Parents: ${filteredParents.size} Registered",
                            fontSize = 13.sp,
                            color = Color.White.copy(alpha = 0.9f)
                        )
                    }
                }

                Spacer(modifier = Modifier.height(14.dp))
                HorizontalDivider(color = Color.White.copy(alpha = 0.35f))
                Spacer(modifier = Modifier.height(12.dp))

                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.SpaceBetween,
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    Row(verticalAlignment = Alignment.CenterVertically) {
                        Icon(
                            imageVector = Icons.Default.CalendarMonth,
                            contentDescription = null,
                            tint = Color.White,
                            modifier = Modifier.size(16.dp)
                        )
                        Spacer(modifier = Modifier.width(6.dp))
                        Text(
                            text = "Academic Year 2026",
                            fontSize = 12.sp,
                            color = Color.White
                        )
                    }

                    Row(verticalAlignment = Alignment.CenterVertically) {
                        Icon(
                            imageVector = Icons.Default.Event,
                            contentDescription = null,
                            tint = Color.White,
                            modifier = Modifier.size(16.dp)
                        )
                        Spacer(modifier = Modifier.width(6.dp))
                        Text(
                            text = "Today: ${SimpleDateFormat("MMMM d, yyyy", Locale.getDefault()).format(Date())}",
                            fontSize = 12.sp,
                            color = Color.White
                        )
                    }
                }
            }
        }

        // 1. Parent Statistics Grid
        val totalParents = filteredParents.size
        val fathers = filteredParents.count { it.relationship.equals("Father", ignoreCase = true) }
        val mothers = filteredParents.count { it.relationship.equals("Mother", ignoreCase = true) }
        var linkedStudentsCount = 0
        filteredParents.forEach { parent ->
            if (!parent.linkedStudents.isNull_or_empty() && parent.linkedStudents != "No students linked") {
                linkedStudentsCount += parent.linkedStudents!!.split(",").size
            }
        }

        Column(
            verticalArrangement = Arrangement.spacedBy(8.dp),
            modifier = Modifier.padding(bottom = 16.dp)
        ) {
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                StatCard(
                    modifier = Modifier.weight(1f),
                    label = "Total Parents",
                    value = totalParents.toString(),
                    icon = Icons.Default.Groups,
                    iconTint = Color(0xFF1967D2)
                )
                StatCard(
                    modifier = Modifier.weight(1f),
                    label = "Linked Students",
                    value = linkedStudentsCount.toString(),
                    icon = Icons.Default.School,
                    iconTint = EduHubSuccess
                )
            }

            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                StatCard(
                    modifier = Modifier.weight(1f),
                    label = "Fathers",
                    value = fathers.toString(),
                    icon = Icons.Default.Man,
                    iconTint = EduHubError
                )
                StatCard(
                    modifier = Modifier.weight(1f),
                    label = "Mothers",
                    value = mothers.toString(),
                    icon = Icons.Default.Woman,
                    iconTint = Color(0xFFB06000)
                )
            }
        }

        // 2. Search & Filter Card
        Card(
            modifier = Modifier
                .fillMaxWidth()
                .padding(bottom = 16.dp),
            colors = CardDefaults.cardColors(containerColor = EduHubBackground),
            border = BorderStroke(1.dp, EduHubBorder),
            shape = RoundedCornerShape(8.dp)
        ) {
            Column(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(16.dp)
            ) {
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.SpaceBetween,
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    Text("Search Parents", fontSize = 16.sp, fontWeight = FontWeight.Bold, color = EduHubTextDark)
                    OutlinedButton(
                        onClick = {
                            val csvStr = viewModel.generateCsvExport(filteredParents)
                            downloadAndShareParentsCsv(context, csvStr)
                        },
                        shape = RoundedCornerShape(25.dp)
                    ) {
                        Icon(Icons.Default.Download, contentDescription = null, modifier = Modifier.size(16.dp))
                        Spacer(modifier = Modifier.width(4.dp))
                        Text("Export CSV", fontSize = 12.sp)
                    }
                }

                Spacer(modifier = Modifier.height(12.dp))

                // Search Input
                OutlinedTextField(
                    value = uiState.searchQuery,
                    onValueChange = { viewModel.onSearchQueryChanged(it) },
                    modifier = Modifier.fillMaxWidth(),
                    placeholder = { Text("Name, email or phone", color = EduHubTextMuted, fontSize = 13.sp) },
                    leadingIcon = { Icon(Icons.Default.Search, contentDescription = null, tint = EduHubTextMuted) },
                    singleLine = true,
                    shape = RoundedCornerShape(25.dp),
                    colors = OutlinedTextFieldDefaults.colors(
                        focusedBorderColor = EduHubOrange,
                        unfocusedBorderColor = EduHubBorder,
                        focusedContainerColor = EduHubBackground,
                        unfocusedContainerColor = EduHubBackground
                    )
                )

                Spacer(modifier = Modifier.height(8.dp))

                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.spacedBy(8.dp)
                ) {
                    FilterDropdown(
                        modifier = Modifier.weight(1f),
                        label = "Relationship",
                        selectedOption = if (uiState.relationshipFilter.isEmpty()) "All" else uiState.relationshipFilter,
                        options = listOf("All", "Father", "Mother", "Guardian"),
                        onOptionSelected = {
                            viewModel.onRelationshipFilterChanged(if (it == "All") "" else it)
                        }
                    )

                    Button(
                        onClick = { viewModel.toggleComposeSection() },
                        modifier = Modifier.weight(1f),
                        shape = RoundedCornerShape(25.dp),
                        colors = ButtonDefaults.buttonColors(containerColor = EduHubOrange)
                    ) {
                        Icon(Icons.Default.Mail, contentDescription = null, modifier = Modifier.size(16.dp))
                        Spacer(modifier = Modifier.width(4.dp))
                        Text("Compose Email", fontSize = 12.sp)
                    }
                }

                Spacer(modifier = Modifier.height(8.dp))

                OutlinedButton(
                    onClick = { viewModel.resetFilters() },
                    shape = RoundedCornerShape(25.dp),
                    modifier = Modifier.align(Alignment.End),
                    colors = ButtonDefaults.outlinedButtonColors(contentColor = EduHubTextMuted)
                ) {
                    Icon(Icons.Default.Refresh, contentDescription = null, modifier = Modifier.size(16.dp))
                    Spacer(modifier = Modifier.width(6.dp))
                    Text("Reset Filters", fontSize = 12.sp)
                }
            }
        }

        // 3. Compose Email Section Card
        AnimatedVisibility(visible = uiState.showComposeSection) {
            Card(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(bottom = 16.dp),
                colors = CardDefaults.cardColors(containerColor = EduHubBackground),
                border = BorderStroke(1.dp, EduHubOrange),
                shape = RoundedCornerShape(8.dp)
            ) {
                Column(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(16.dp)
                ) {
                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.SpaceBetween,
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Row(verticalAlignment = Alignment.CenterVertically) {
                            Icon(Icons.Default.Mail, contentDescription = null, tint = EduHubOrange, modifier = Modifier.size(20.dp))
                            Spacer(modifier = Modifier.width(8.dp))
                            Text("Compose Email to Parents", fontSize = 16.sp, fontWeight = FontWeight.Bold, color = EduHubOrange)
                        }
                        IconButton(onClick = { viewModel.toggleComposeSection() }) {
                            Icon(Icons.Default.Close, contentDescription = null)
                        }
                    }

                    Spacer(modifier = Modifier.height(12.dp))

                    // Recipient Selector
                    val recipientMap = remember(uiState.parents) {
                        uiState.parents.associate { "${it.fullName} (${it.email})" to it.id.toString() }
                    }
                    val currentRecipientLabel = if (uiState.composeRecipientId == "all") "All Parents" else recipientMap.entries.find { it.value == uiState.composeRecipientId }?.key ?: "All Parents"

                    FilterDropdown(
                        modifier = Modifier.fillMaxWidth(),
                        label = "To Recipient",
                        selectedOption = currentRecipientLabel,
                        options = listOf("All Parents") + recipientMap.keys.toList(),
                        onOptionSelected = {
                            val id = if (it == "All Parents") "all" else recipientMap[it] ?: "all"
                            viewModel.onComposeRecipientChanged(id)
                        }
                    )

                    Spacer(modifier = Modifier.height(8.dp))

                    OutlinedTextField(
                        value = uiState.composeSubject,
                        onValueChange = { viewModel.onComposeSubjectChanged(it) },
                        label = { Text("Subject", fontSize = 12.sp) },
                        modifier = Modifier.fillMaxWidth(),
                        singleLine = true,
                        shape = RoundedCornerShape(25.dp),
                        colors = OutlinedTextFieldDefaults.colors(
                            focusedBorderColor = EduHubOrange,
                            unfocusedBorderColor = EduHubBorder
                        )
                    )

                    Spacer(modifier = Modifier.height(8.dp))

                    OutlinedTextField(
                        value = uiState.composeBody,
                        onValueChange = { viewModel.onComposeBodyChanged(it) },
                        label = { Text("Message Body", fontSize = 12.sp) },
                        modifier = Modifier.fillMaxWidth(),
                        minLines = 4,
                        maxLines = 6,
                        shape = RoundedCornerShape(12.dp),
                        colors = OutlinedTextFieldDefaults.colors(
                            focusedBorderColor = EduHubOrange,
                            unfocusedBorderColor = EduHubBorder
                        )
                    )

                    Spacer(modifier = Modifier.height(12.dp))

                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.spacedBy(8.dp)
                    ) {
                        Button(
                            onClick = { viewModel.sendComposedEmail() },
                            enabled = !uiState.isSendingEmail,
                            modifier = Modifier.weight(1f),
                            shape = RoundedCornerShape(25.dp),
                            colors = ButtonDefaults.buttonColors(containerColor = EduHubOrange)
                        ) {
                            if (uiState.isSendingEmail) {
                                CircularProgressIndicator(color = Color.White, modifier = Modifier.size(20.dp))
                            } else {
                                Icon(Icons.Default.Send, contentDescription = null, modifier = Modifier.size(16.dp))
                                Spacer(modifier = Modifier.width(6.dp))
                                Text("Send Email", fontSize = 13.sp, fontWeight = FontWeight.Bold)
                            }
                        }

                        OutlinedButton(
                            onClick = { viewModel.clearComposeForm() },
                            modifier = Modifier.weight(1f),
                            shape = RoundedCornerShape(25.dp)
                        ) {
                            Icon(Icons.Default.DeleteSweep, contentDescription = null, modifier = Modifier.size(16.dp))
                            Spacer(modifier = Modifier.width(6.dp))
                            Text("Clear", fontSize = 13.sp)
                        }
                    }
                }
            }
        }

        // 4. Full 100% Screen-Width All Parents Table Card
        Card(
            modifier = Modifier.fillMaxWidth(),
            colors = CardDefaults.cardColors(containerColor = Color.White),
            border = BorderStroke(1.5.dp, Color(0xFF1E1E1E)),
            shape = RoundedCornerShape(0.dp),
            elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)
        ) {
            Column(modifier = Modifier.fillMaxWidth()) {
                Box(
                    modifier = Modifier
                        .fillMaxWidth()
                        .background(Color(0xFF2E3B4E))
                        .padding(vertical = 10.dp, horizontal = 12.dp)
                ) {
                    Text(
                        text = "ALL PARENTS ROSTER (${filteredParents.size})",
                        fontSize = 13.sp,
                        fontWeight = FontWeight.Bold,
                        color = Color.White,
                        fontFamily = FontFamily.Serif
                    )
                }

                if (filteredParents.isEmpty()) {
                    Text(
                        text = "NO PARENT RECORDS FOUND",
                        fontSize = 12.sp,
                        fontWeight = FontWeight.Bold,
                        color = Color.Red,
                        fontFamily = FontFamily.Serif,
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(vertical = 24.dp),
                        textAlign = TextAlign.Center
                    )
                } else {
                    val parentTableScrollState = rememberScrollState()
                    Column(
                        modifier = Modifier
                            .fillMaxWidth()
                            .horizontalScroll(parentTableScrollState)
                    ) {
                        // Header Row
                        Row(
                            modifier = Modifier
                                .background(Color(0xFFE8ECEF))
                                .border(BorderStroke(1.dp, Color.Black)),
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            HeaderCell("NAME", width = 135.dp, align = TextAlign.Start)
                            HeaderCell("EMAIL", width = 145.dp, align = TextAlign.Start)
                            HeaderCell("PHONE", width = 100.dp)
                            HeaderCell("ID NUMBER", width = 90.dp)
                            HeaderCell("RELATIONSHIP", width = 95.dp)
                            HeaderCell("LINKED STUDENTS", width = 160.dp, align = TextAlign.Start)
                            HeaderCell("ACTIONS", width = 110.dp)
                        }

                        // Data Rows
                        filteredParents.forEachIndexed { index, parent ->
                            val bg = if (index % 2 == 0) Color.White else Color(0xFFFAFAFA)
                            Row(
                                modifier = Modifier
                                    .background(bg)
                                    .border(BorderStroke(0.5.dp, Color.Black)),
                                verticalAlignment = Alignment.CenterVertically
                            ) {
                                DataCell(text = parent.fullName.uppercase(), width = 135.dp, fontWeight = FontWeight.Bold, align = TextAlign.Start)
                                DataCell(text = parent.email, width = 145.dp, align = TextAlign.Start)
                                DataCell(text = parent.phone, width = 100.dp)
                                DataCell(text = parent.idNumber, width = 90.dp)
                                DataCell(text = parent.relationship ?: "Guardian", width = 95.dp, fontWeight = FontWeight.Bold)
                                DataCell(text = parent.linkedStudents ?: "No students linked", width = 160.dp, align = TextAlign.Start)

                                Box(
                                    modifier = Modifier
                                        .width(110.dp)
                                        .border(BorderStroke(0.5.dp, Color.Black))
                                        .padding(vertical = 4.dp),
                                    contentAlignment = Alignment.Center
                                ) {
                                    Row(
                                        horizontalArrangement = Arrangement.spacedBy(4.dp),
                                        verticalAlignment = Alignment.CenterVertically
                                    ) {
                                        IconButton(
                                            onClick = { viewModel.openComposeForParent(parent.id.toString()) },
                                            modifier = Modifier.size(28.dp)
                                        ) {
                                            Icon(Icons.Default.Mail, contentDescription = "Email", tint = EduHubOrange, modifier = Modifier.size(16.dp))
                                        }

                                        IconButton(
                                            onClick = {
                                                val intent = Intent(Intent.ACTION_DIAL, Uri.parse("tel:${parent.phone}"))
                                                context.startActivity(intent)
                                            },
                                            modifier = Modifier.size(28.dp)
                                        ) {
                                            Icon(Icons.Default.Phone, contentDescription = "Call", tint = EduHubSuccess, modifier = Modifier.size(16.dp))
                                        }

                                        IconButton(
                                            onClick = { viewModel.openParentDetails(parent) },
                                            modifier = Modifier.size(28.dp)
                                        ) {
                                            Icon(Icons.Default.Visibility, contentDescription = "Details", tint = EduHubTextDark, modifier = Modifier.size(16.dp))
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        Spacer(modifier = Modifier.height(24.dp))
    }

    // Parent Details Dialog Sheet
    if (uiState.showDetailsDialog && uiState.selectedParentDetail != null) {
        val parent = uiState.selectedParentDetail!!
        Dialog(onDismissRequest = { viewModel.closeParentDetails() }) {
            Surface(
                shape = RoundedCornerShape(16.dp),
                color = Color.White,
                border = BorderStroke(1.dp, EduHubBorder),
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(16.dp)
            ) {
                Column(modifier = Modifier.padding(20.dp)) {
                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.SpaceBetween,
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Text("Parent Details", fontSize = 18.sp, fontWeight = FontWeight.Bold, color = EduHubTextDark)
                        IconButton(onClick = { viewModel.closeParentDetails() }) {
                            Icon(Icons.Default.Close, contentDescription = null)
                        }
                    }

                    Spacer(modifier = Modifier.height(12.dp))
                    HorizontalDivider(color = EduHubBorder)
                    Spacer(modifier = Modifier.height(12.dp))

                    Text("Name: ${parent.fullName}", fontSize = 14.sp, fontWeight = FontWeight.Bold)
                    Text("Email: ${parent.email}", fontSize = 13.sp, color = EduHubTextMuted)
                    Text("Phone: ${parent.phone}", fontSize = 13.sp, color = EduHubTextMuted)
                    Text("ID Number: ${parent.idNumber}", fontSize = 13.sp, color = EduHubTextMuted)
                    Text("Relationship: ${parent.relationship ?: "Guardian"}", fontSize = 13.sp, fontWeight = FontWeight.Bold, color = EduHubOrange)
                    Text("Address: ${parent.address ?: "Not provided"}", fontSize = 13.sp, color = EduHubTextMuted)

                    Spacer(modifier = Modifier.height(12.dp))
                    Text("Linked Students:", fontSize = 13.sp, fontWeight = FontWeight.Bold, color = EduHubTextDark)
                    Text(parent.linkedStudents ?: "No students linked", fontSize = 13.sp, color = EduHubTextMuted)

                    Spacer(modifier = Modifier.height(16.dp))

                    OutlinedButton(
                        onClick = { viewModel.closeParentDetails() },
                        modifier = Modifier.align(Alignment.End),
                        shape = RoundedCornerShape(25.dp)
                    ) {
                        Text("Close")
                    }
                }
            }
        }
    }
}

private fun downloadAndShareParentsCsv(context: Context, csvContent: String) {
    try {
        val fileName = "parents_${System.currentTimeMillis()}.csv"

        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.Q) {
            val contentValues = ContentValues().apply {
                put(MediaStore.MediaColumns.DISPLAY_NAME, fileName)
                put(MediaStore.MediaColumns.MIME_TYPE, "text/csv")
                put(MediaStore.MediaColumns.RELATIVE_PATH, Environment.DIRECTORY_DOWNLOADS)
            }
            val uri = context.contentResolver.insert(MediaStore.Downloads.EXTERNAL_CONTENT_URI, contentValues)
            uri?.let {
                context.contentResolver.openOutputStream(it)?.use { os ->
                    os.write(csvContent.toByteArray())
                }
            }
        } else {
            @Suppress("DEPRECATION")
            val downloadsDir = Environment.getExternalStoragePublicDirectory(Environment.DIRECTORY_DOWNLOADS)
            val file = File(downloadsDir, fileName)
            file.writeText(csvContent)
        }

        val cacheFile = File(context.cacheDir, "parents_export.csv")
        cacheFile.writeText(csvContent)
        val shareUri = FileProvider.getUriForFile(
            context,
            "${context.packageName}.fileprovider",
            cacheFile
        )

        val shareIntent = Intent(Intent.ACTION_SEND).apply {
            type = "text/csv"
            putExtra(Intent.EXTRA_STREAM, shareUri)
            addFlags(Intent.FLAG_GRANT_READ_URI_PERMISSION)
        }

        context.startActivity(Intent.createChooser(shareIntent, "Export Parent List"))
        Toast.makeText(context, "Parent list saved to Downloads folder!", Toast.LENGTH_LONG).show()
    } catch (e: Exception) {
        Toast.makeText(context, "Error exporting parents: ${e.localizedMessage}", Toast.LENGTH_SHORT).show()
    }
}

@Composable
private fun StatCard(
    modifier: Modifier = Modifier,
    label: String,
    value: String,
    icon: ImageVector,
    iconTint: Color
) {
    Box(
        modifier = modifier
            .clip(RoundedCornerShape(8.dp))
            .background(Color.White)
            .border(1.dp, EduHubBorder, RoundedCornerShape(8.dp))
            .padding(14.dp),
        contentAlignment = Alignment.Center
    ) {
        Column(horizontalAlignment = Alignment.CenterHorizontally) {
            Icon(imageVector = icon, contentDescription = null, tint = iconTint, modifier = Modifier.size(24.dp))
            Spacer(modifier = Modifier.height(4.dp))
            Text(text = value, fontSize = 22.sp, fontWeight = FontWeight.Bold, color = EduHubTextDark)
            Spacer(modifier = Modifier.height(2.dp))
            Text(text = label, fontSize = 11.sp, color = EduHubTextMuted)
        }
    }
}

@Composable
private fun HeaderCell(
    text: String,
    width: androidx.compose.ui.unit.Dp,
    align: TextAlign = TextAlign.Center
) {
    Box(
        modifier = Modifier
            .width(width)
            .border(BorderStroke(0.5.dp, Color.Black))
            .padding(vertical = 10.dp, horizontal = 4.dp),
        contentAlignment = Alignment.Center
    ) {
        Text(
            text = text,
            fontSize = 10.sp,
            fontWeight = FontWeight.Bold,
            fontFamily = FontFamily.Serif,
            color = Color.Black,
            textAlign = align
        )
    }
}

@Composable
private fun DataCell(
    text: String,
    width: androidx.compose.ui.unit.Dp,
    fontWeight: FontWeight = FontWeight.Normal,
    textColor: Color = Color.Black,
    align: TextAlign = TextAlign.Center
) {
    Box(
        modifier = Modifier
            .width(width)
            .border(BorderStroke(0.5.dp, Color.Black))
            .padding(vertical = 8.dp, horizontal = 4.dp),
        contentAlignment = Alignment.Center
    ) {
        Text(
            text = text,
            fontSize = 11.sp,
            fontWeight = fontWeight,
            fontFamily = FontFamily.Serif,
            color = textColor,
            textAlign = align
        )
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
private fun FilterDropdown(
    modifier: Modifier = Modifier,
    label: String,
    selectedOption: String,
    options: List<String>,
    onOptionSelected: (String) -> Unit
) {
    var expanded by remember { mutableStateOf(false) }

    ExposedDropdownMenuBox(
        expanded = expanded,
        onExpandedChange = { expanded = !expanded },
        modifier = modifier
    ) {
        OutlinedTextField(
            value = selectedOption,
            onValueChange = {},
            readOnly = true,
            label = { Text(label, fontSize = 11.sp) },
            trailingIcon = { ExposedDropdownMenuDefaults.TrailingIcon(expanded = expanded) },
            modifier = Modifier
                .fillMaxWidth()
                .menuAnchor(),
            singleLine = true,
            shape = RoundedCornerShape(25.dp),
            colors = OutlinedTextFieldDefaults.colors(
                focusedBorderColor = EduHubOrange,
                unfocusedBorderColor = EduHubBorder,
                focusedContainerColor = EduHubBackground,
                unfocusedContainerColor = EduHubBackground,
                focusedTextColor = EduHubTextDark,
                unfocusedTextColor = EduHubTextDark
            )
        )

        ExposedDropdownMenu(
            expanded = expanded,
            onDismissRequest = { expanded = false }
        ) {
            options.forEach { option ->
                DropdownMenuItem(
                    text = { Text(option, fontSize = 13.sp) },
                    onClick = {
                        onOptionSelected(option)
                        expanded = false
                    }
                )
            }
        }
    }
}

private fun String?.isNull_or_empty(): Boolean = this == null || this.trim().isEmpty()
