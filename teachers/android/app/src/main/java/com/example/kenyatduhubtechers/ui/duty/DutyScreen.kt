package com.example.kenyatduhubtechers.ui.duty

import android.content.Intent
import android.widget.Toast
import androidx.compose.animation.AnimatedVisibility
import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.background
import androidx.compose.foundation.border
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
import androidx.compose.material.icons.filled.Add
import androidx.compose.material.icons.filled.CleaningServices
import androidx.compose.material.icons.filled.Close
import androidx.compose.material.icons.filled.Info
import androidx.compose.material.icons.filled.MedicalServices
import androidx.compose.material.icons.filled.Print
import androidx.compose.material.icons.filled.Send
import androidx.compose.material.icons.filled.VerifiedUser
import androidx.compose.material.icons.filled.Warning
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
import androidx.compose.material3.pulltorefresh.PullToRefreshBox
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
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontFamily
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.compose.ui.window.Dialog
import androidx.compose.ui.window.DialogProperties
import com.example.kenyatduhubtechers.data.model.LeaveoutChitDto
import com.example.kenyatduhubtechers.ui.theme.EduHubBackground
import com.example.kenyatduhubtechers.ui.theme.EduHubBorder
import com.example.kenyatduhubtechers.ui.theme.EduHubCardSurface
import com.example.kenyatduhubtechers.ui.theme.EduHubError
import com.example.kenyatduhubtechers.ui.theme.EduHubErrorContainer
import com.example.kenyatduhubtechers.ui.theme.EduHubOrange
import com.example.kenyatduhubtechers.ui.theme.EduHubSuccess
import com.example.kenyatduhubtechers.ui.theme.EduHubSuccessContainer
import com.example.kenyatduhubtechers.ui.theme.EduHubTextDark
import com.example.kenyatduhubtechers.ui.theme.EduHubTextMuted

@OptIn(ExperimentalLayoutApi::class, ExperimentalMaterial3Api::class)
@Composable
fun DutyScreen(
    viewModel: DutyViewModel,
    modifier: Modifier = Modifier
) {
    val uiState by viewModel.uiState.collectAsState()
    val context = LocalContext.current

    PullToRefreshBox(
        isRefreshing = uiState.isLoading,
        onRefresh = { viewModel.loadDutyData() },
        modifier = modifier.fillMaxSize()
    ) {
        if (uiState.isLoading && uiState.teacher == null && uiState.students.isEmpty()) {
            Box(
                modifier = Modifier.fillMaxSize(),
                contentAlignment = Alignment.Center
            ) {
                CircularProgressIndicator(color = EduHubOrange)
            }
        } else {
            val isOnDuty = uiState.currentDuty != null
            val studentOptions = remember(uiState.students) {
                listOf("Select Student") + uiState.students.map { "${it.firstName} ${it.lastName} (${it.admissionNumber ?: "N/A"})" }
            }
            val studentMap = remember(uiState.students) {
                uiState.students.associateBy { "${it.firstName} ${it.lastName} (${it.admissionNumber ?: "N/A"})" }
            }
            val selectedStudentName = uiState.students.find { it.id == uiState.selectedStudentIdForChit }?.let { "${it.firstName} ${it.lastName} (${it.admissionNumber ?: "N/A"})" } ?: "Select Student"

            val incidentTypes = listOf("disciplinary", "medical", "security", "damage", "other")
            val incidentSeverities = listOf("low", "medium", "high", "critical")
            val cleanlinessAreas = listOf("classrooms", "corridors", "cafeteria", "playground", "toilets", "library")
            val cleanlinessStatuses = listOf("excellent", "good", "fair", "poor")

            Column(
                modifier = Modifier
                    .fillMaxSize()
                    .verticalScroll(rememberScrollState())
                    .padding(16.dp)
            ) {
                Text(
                    text = "My Duties & School Management",
                    fontSize = 22.sp,
                    fontWeight = FontWeight.Bold,
                    color = EduHubTextDark
                )
                Text(
                    text = "Weekly teacher duty management, leaveout chits, incident reports, and cleanliness checks.",
                    fontSize = 13.sp,
                    color = EduHubTextMuted,
                    modifier = Modifier.padding(bottom = 16.dp)
                )

                // Banners
                AnimatedVisibility(visible = uiState.errorMessage != null) {
                    uiState.errorMessage?.let { msg ->
                        Card(
                            modifier = Modifier
                                .fillMaxWidth()
                                .padding(bottom = 12.dp),
                            colors = CardDefaults.cardColors(containerColor = EduHubErrorContainer),
                            border = BorderStroke(1.dp, EduHubError)
                        ) {
                            Row(
                                modifier = Modifier
                                    .fillMaxWidth()
                                    .padding(12.dp),
                                horizontalArrangement = Arrangement.SpaceBetween,
                                verticalAlignment = Alignment.CenterVertically
                            ) {
                                Text(msg, color = EduHubError, fontSize = 13.sp, modifier = Modifier.weight(1f))
                                IconButton(onClick = { viewModel.clearMessages() }, modifier = Modifier.size(24.dp)) {
                                    Icon(Icons.Default.Close, contentDescription = null, tint = EduHubError)
                                }
                            }
                        }
                    }
                }

                AnimatedVisibility(visible = uiState.successMessage != null) {
                    uiState.successMessage?.let { msg ->
                        Card(
                            modifier = Modifier
                                .fillMaxWidth()
                                .padding(bottom = 12.dp),
                            colors = CardDefaults.cardColors(containerColor = EduHubSuccessContainer),
                            border = BorderStroke(1.dp, EduHubSuccess)
                        ) {
                            Row(
                                modifier = Modifier
                                    .fillMaxWidth()
                                    .padding(12.dp),
                                horizontalArrangement = Arrangement.SpaceBetween,
                                verticalAlignment = Alignment.CenterVertically
                            ) {
                                Text(msg, color = EduHubSuccess, fontSize = 13.sp, modifier = Modifier.weight(1f))
                                IconButton(onClick = { viewModel.clearMessages() }, modifier = Modifier.size(24.dp)) {
                                    Icon(Icons.Default.Close, contentDescription = null, tint = EduHubSuccess)
                                }
                            }
                        }
                    }
                }

                // 1. Weekly Duty Status Card
                if (isOnDuty) {
                    val duty = uiState.currentDuty!!
                    Card(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(bottom = 20.dp),
                        shape = RoundedCornerShape(12.dp),
                        colors = CardDefaults.cardColors(containerColor = Color(0xFFD4EDDA)),
                        border = BorderStroke(1.dp, Color(0xFFC3E6CB))
                    ) {
                        Column(
                            modifier = Modifier
                                .fillMaxWidth()
                                .padding(16.dp)
                        ) {
                            Row(verticalAlignment = Alignment.CenterVertically) {
                                Icon(Icons.Default.VerifiedUser, contentDescription = null, tint = Color(0xFF155724), modifier = Modifier.size(24.dp))
                                Spacer(modifier = Modifier.width(8.dp))
                                Text("You are on Duty This Week", fontSize = 17.sp, fontWeight = FontWeight.Bold, color = Color(0xFF155724))
                            }
                            Spacer(modifier = Modifier.height(6.dp))
                            Text(
                                "Week: ${duty.weekStart ?: "N/A"} to ${duty.weekEnd ?: "N/A"}",
                                fontSize = 13.sp,
                                fontWeight = FontWeight.Medium,
                                color = Color(0xFF155724)
                            )
                            Spacer(modifier = Modifier.height(4.dp))
                            Text(
                                "You are responsible for managing the school, handling sick students, reporting incidents, ensuring cleanliness, and maintaining order.",
                                fontSize = 12.sp,
                                color = Color(0xFF155724).copy(alpha = 0.85f)
                            )
                        }
                    }
                } else {
                    Card(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(bottom = 20.dp),
                        shape = RoundedCornerShape(12.dp),
                        colors = CardDefaults.cardColors(containerColor = Color(0xFFFEF7E0)),
                        border = BorderStroke(1.dp, Color(0xFFF9AB00))
                    ) {
                        Column(
                            modifier = Modifier
                                .fillMaxWidth()
                                .padding(16.dp)
                        ) {
                            Row(verticalAlignment = Alignment.CenterVertically) {
                                Icon(Icons.Default.Info, contentDescription = null, tint = Color(0xFFB06000), modifier = Modifier.size(24.dp))
                                Spacer(modifier = Modifier.width(8.dp))
                                Text("Not on Duty This Week", fontSize = 17.sp, fontWeight = FontWeight.Bold, color = Color(0xFFB06000))
                            }
                            Spacer(modifier = Modifier.height(6.dp))
                            Text(
                                "You are not assigned to duty this week. You can still access duty management tools below.",
                                fontSize = 12.sp,
                                color = Color(0xFFB06000)
                            )
                        }
                    }
                }

                // 2. Duty Management Tools Card
                Card(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(bottom = 20.dp),
                    shape = RoundedCornerShape(12.dp),
                    colors = CardDefaults.cardColors(containerColor = EduHubBackground),
                    border = BorderStroke(1.dp, EduHubBorder)
                ) {
                    Column(modifier = Modifier.padding(20.dp)) {
                        Text("Duty Management Tools", fontSize = 18.sp, fontWeight = FontWeight.Bold, color = EduHubTextDark)
                        Spacer(modifier = Modifier.height(16.dp))

                        // Leaveout Chit Form
                        Row(verticalAlignment = Alignment.CenterVertically) {
                            Icon(Icons.Default.MedicalServices, contentDescription = null, tint = EduHubOrange, modifier = Modifier.size(20.dp))
                            Spacer(modifier = Modifier.width(8.dp))
                            Text("Create Leaveout Chit (Sick Student)", fontSize = 15.sp, fontWeight = FontWeight.Bold, color = EduHubTextDark)
                        }
                        Spacer(modifier = Modifier.height(10.dp))

                        FilterDropdown(
                            modifier = Modifier.fillMaxWidth(),
                            label = "Select Sick Student",
                            selectedOption = selectedStudentName,
                            options = studentOptions,
                            onOptionSelected = {
                                val s = studentMap[it]
                                viewModel.onStudentForChitSelected(s?.id)
                            }
                        )
                        Spacer(modifier = Modifier.height(8.dp))

                        OutlinedTextField(
                            value = uiState.chitReason,
                            onValueChange = { viewModel.onChitReasonChanged(it) },
                            placeholder = { Text("Reason for Leaveout (e.g. Sick, Medical appointment)", fontSize = 13.sp) },
                            label = { Text("Reason", fontSize = 12.sp) },
                            modifier = Modifier.fillMaxWidth(),
                            shape = RoundedCornerShape(8.dp),
                            colors = OutlinedTextFieldDefaults.colors(focusedBorderColor = EduHubOrange)
                        )
                        Spacer(modifier = Modifier.height(10.dp))

                        Button(
                            onClick = { viewModel.createLeaveoutChit() },
                            colors = ButtonDefaults.buttonColors(containerColor = EduHubOrange),
                            shape = RoundedCornerShape(8.dp),
                            modifier = Modifier.fillMaxWidth(),
                            enabled = !uiState.isSubmitting
                        ) {
                            Icon(Icons.Default.Add, contentDescription = null, modifier = Modifier.size(18.dp))
                            Spacer(modifier = Modifier.width(6.dp))
                            Text("Create Leaveout Chit", fontSize = 13.sp, fontWeight = FontWeight.Bold)
                        }

                        HorizontalDivider(color = EduHubBorder.copy(alpha = 0.6f), modifier = Modifier.padding(vertical = 20.dp))

                        // Incident Report Form
                        Row(verticalAlignment = Alignment.CenterVertically) {
                            Icon(Icons.Default.Warning, contentDescription = null, tint = EduHubError, modifier = Modifier.size(20.dp))
                            Spacer(modifier = Modifier.width(8.dp))
                            Text("Report Incident to Administration", fontSize = 15.sp, fontWeight = FontWeight.Bold, color = EduHubTextDark)
                        }
                        Spacer(modifier = Modifier.height(10.dp))

                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            horizontalArrangement = Arrangement.spacedBy(8.dp)
                        ) {
                            FilterDropdown(
                                modifier = Modifier.weight(1f),
                                label = "Incident Type",
                                selectedOption = if (uiState.incidentType.isBlank()) "Select Type" else uiState.incidentType.uppercase(),
                                options = listOf("Select Type") + incidentTypes.map { it.uppercase() },
                                onOptionSelected = {
                                    viewModel.onIncidentTypeChanged(if (it == "Select Type") "" else it.lowercase())
                                }
                            )

                            FilterDropdown(
                                modifier = Modifier.weight(1f),
                                label = "Severity",
                                selectedOption = uiState.incidentSeverity.uppercase(),
                                options = incidentSeverities.map { it.uppercase() },
                                onOptionSelected = {
                                    viewModel.onIncidentSeverityChanged(it.lowercase())
                                }
                            )
                        }
                        Spacer(modifier = Modifier.height(8.dp))

                        OutlinedTextField(
                            value = uiState.incidentDescription,
                            onValueChange = { viewModel.onIncidentDescriptionChanged(it) },
                            placeholder = { Text("Describe the incident details clearly...", fontSize = 13.sp) },
                            label = { Text("Incident Description", fontSize = 12.sp) },
                            modifier = Modifier.fillMaxWidth(),
                            shape = RoundedCornerShape(8.dp),
                            colors = OutlinedTextFieldDefaults.colors(focusedBorderColor = EduHubError)
                        )
                        Spacer(modifier = Modifier.height(10.dp))

                        Button(
                            onClick = { viewModel.reportIncident() },
                            colors = ButtonDefaults.buttonColors(containerColor = EduHubError),
                            shape = RoundedCornerShape(8.dp),
                            modifier = Modifier.fillMaxWidth(),
                            enabled = !uiState.isSubmitting
                        ) {
                            Icon(Icons.Default.Send, contentDescription = null, modifier = Modifier.size(18.dp))
                            Spacer(modifier = Modifier.width(6.dp))
                            Text("Report Incident", fontSize = 13.sp, fontWeight = FontWeight.Bold)
                        }

                        HorizontalDivider(color = EduHubBorder.copy(alpha = 0.6f), modifier = Modifier.padding(vertical = 20.dp))

                        // Cleanliness Check Form
                        Row(verticalAlignment = Alignment.CenterVertically) {
                            Icon(Icons.Default.CleaningServices, contentDescription = null, tint = EduHubSuccess, modifier = Modifier.size(20.dp))
                            Spacer(modifier = Modifier.width(8.dp))
                            Text("Record Cleanliness Check", fontSize = 15.sp, fontWeight = FontWeight.Bold, color = EduHubTextDark)
                        }
                        Spacer(modifier = Modifier.height(10.dp))

                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            horizontalArrangement = Arrangement.spacedBy(8.dp)
                        ) {
                            FilterDropdown(
                                modifier = Modifier.weight(1f),
                                label = "Area",
                                selectedOption = if (uiState.cleanlinessArea.isBlank()) "Select Area" else uiState.cleanlinessArea.uppercase(),
                                options = listOf("Select Area") + cleanlinessAreas.map { it.uppercase() },
                                onOptionSelected = {
                                    viewModel.onCleanlinessAreaChanged(if (it == "Select Area") "" else it.lowercase())
                                }
                            )

                            FilterDropdown(
                                modifier = Modifier.weight(1f),
                                label = "Status",
                                selectedOption = uiState.cleanlinessStatus.uppercase(),
                                options = cleanlinessStatuses.map { it.uppercase() },
                                onOptionSelected = {
                                    viewModel.onCleanlinessStatusChanged(it.lowercase())
                                }
                            )
                        }
                        Spacer(modifier = Modifier.height(8.dp))

                        OutlinedTextField(
                            value = uiState.cleanlinessNotes,
                            onValueChange = { viewModel.onCleanlinessNotesChanged(it) },
                            placeholder = { Text("Any cleanliness observations or notes...", fontSize = 13.sp) },
                            label = { Text("Notes", fontSize = 12.sp) },
                            modifier = Modifier.fillMaxWidth(),
                            shape = RoundedCornerShape(8.dp),
                            colors = OutlinedTextFieldDefaults.colors(focusedBorderColor = EduHubSuccess)
                        )
                        Spacer(modifier = Modifier.height(10.dp))

                        Button(
                            onClick = { viewModel.recordCleanliness() },
                            colors = ButtonDefaults.buttonColors(containerColor = EduHubSuccess),
                            shape = RoundedCornerShape(8.dp),
                            modifier = Modifier.fillMaxWidth(),
                            enabled = !uiState.isSubmitting
                        ) {
                            Icon(Icons.Default.CleaningServices, contentDescription = null, modifier = Modifier.size(18.dp))
                            Spacer(modifier = Modifier.width(6.dp))
                            Text("Record Cleanliness Check", fontSize = 13.sp, fontWeight = FontWeight.Bold)
                        }
                    }
                }

                // 3. Recent Activity Lists
                // Recent Leaveout Chits
                Card(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(bottom = 16.dp),
                    shape = RoundedCornerShape(12.dp),
                    colors = CardDefaults.cardColors(containerColor = EduHubBackground),
                    border = BorderStroke(1.dp, EduHubBorder)
                ) {
                    Column(modifier = Modifier.padding(16.dp)) {
                        Row(verticalAlignment = Alignment.CenterVertically) {
                            Icon(Icons.Default.MedicalServices, contentDescription = null, tint = EduHubOrange, modifier = Modifier.size(20.dp))
                            Spacer(modifier = Modifier.width(8.dp))
                            Text("Recent Leaveout Chits (${uiState.leaveoutChits.size})", fontSize = 16.sp, fontWeight = FontWeight.Bold, color = EduHubTextDark)
                        }
                        Spacer(modifier = Modifier.height(12.dp))

                        if (uiState.leaveoutChits.isEmpty()) {
                            Text("No leaveout chits created yet.", fontSize = 13.sp, color = EduHubTextMuted)
                        } else {
                            uiState.leaveoutChits.forEach { chit ->
                                Card(
                                    modifier = Modifier
                                        .fillMaxWidth()
                                        .padding(vertical = 4.dp),
                                    colors = CardDefaults.cardColors(containerColor = EduHubCardSurface),
                                    border = BorderStroke(1.dp, EduHubBorder)
                                ) {
                                    Row(
                                        modifier = Modifier
                                            .fillMaxWidth()
                                            .padding(12.dp),
                                        horizontalArrangement = Arrangement.SpaceBetween,
                                        verticalAlignment = Alignment.CenterVertically
                                    ) {
                                        Column(modifier = Modifier.weight(1f)) {
                                            Text(
                                                "${chit.firstName ?: "Student"} ${chit.lastName ?: ""}".trim(),
                                                fontSize = 14.sp,
                                                fontWeight = FontWeight.Bold,
                                                color = EduHubTextDark
                                            )
                                            Text("Reason: ${chit.reason}", fontSize = 12.sp, color = EduHubTextMuted)
                                            Text("Date: ${chit.createdAt ?: ""}", fontSize = 11.sp, color = EduHubTextMuted)
                                        }

                                        OutlinedButton(
                                            onClick = { viewModel.openPrintChitDialog(chit) },
                                            colors = ButtonDefaults.outlinedButtonColors(contentColor = EduHubOrange),
                                            border = BorderStroke(1.dp, EduHubOrange),
                                            shape = RoundedCornerShape(20.dp)
                                        ) {
                                            Icon(Icons.Default.Print, contentDescription = null, modifier = Modifier.size(16.dp))
                                            Spacer(modifier = Modifier.width(4.dp))
                                            Text("Print", fontSize = 12.sp)
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

                // Recent Incidents
                Card(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(bottom = 16.dp),
                    shape = RoundedCornerShape(12.dp),
                    colors = CardDefaults.cardColors(containerColor = EduHubBackground),
                    border = BorderStroke(1.dp, EduHubBorder)
                ) {
                    Column(modifier = Modifier.padding(16.dp)) {
                        Row(verticalAlignment = Alignment.CenterVertically) {
                            Icon(Icons.Default.Warning, contentDescription = null, tint = EduHubError, modifier = Modifier.size(20.dp))
                            Spacer(modifier = Modifier.width(8.dp))
                            Text("Recent Incident Reports (${uiState.incidents.size})", fontSize = 16.sp, fontWeight = FontWeight.Bold, color = EduHubTextDark)
                        }
                        Spacer(modifier = Modifier.height(12.dp))

                        if (uiState.incidents.isEmpty()) {
                            Text("No incident reports submitted yet.", fontSize = 13.sp, color = EduHubTextMuted)
                        } else {
                            uiState.incidents.forEach { inc ->
                                Card(
                                    modifier = Modifier
                                        .fillMaxWidth()
                                        .padding(vertical = 4.dp),
                                    colors = CardDefaults.cardColors(containerColor = EduHubCardSurface),
                                    border = BorderStroke(1.dp, EduHubBorder)
                                ) {
                                    Column(modifier = Modifier.padding(12.dp)) {
                                        Row(
                                            modifier = Modifier.fillMaxWidth(),
                                            horizontalArrangement = Arrangement.SpaceBetween,
                                            verticalAlignment = Alignment.CenterVertically
                                        ) {
                                            Text(inc.incidentType.uppercase(), fontSize = 13.sp, fontWeight = FontWeight.Bold, color = EduHubTextDark)
                                            val badgeBg = when (inc.severity.lowercase()) {
                                                "critical", "high" -> EduHubErrorContainer
                                                else -> Color(0xFFE8F0FE)
                                            }
                                            val badgeColor = when (inc.severity.lowercase()) {
                                                "critical", "high" -> EduHubError
                                                else -> Color(0xFF1967D2)
                                            }
                                            Surface(
                                                color = badgeBg,
                                                shape = RoundedCornerShape(12.dp)
                                            ) {
                                                Text(inc.severity.uppercase(), fontSize = 10.sp, fontWeight = FontWeight.Bold, color = badgeColor, modifier = Modifier.padding(horizontal = 8.dp, vertical = 2.dp))
                                            }
                                        }
                                        Spacer(modifier = Modifier.height(4.dp))
                                        Text(inc.description, fontSize = 12.sp, color = EduHubTextDark)
                                        Text(inc.createdAt ?: "", fontSize = 11.sp, color = EduHubTextMuted)
                                    }
                                }
                            }
                        }
                    }
                }

                // Recent Cleanliness Checks
                Card(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(bottom = 24.dp),
                    shape = RoundedCornerShape(12.dp),
                    colors = CardDefaults.cardColors(containerColor = EduHubBackground),
                    border = BorderStroke(1.dp, EduHubBorder)
                ) {
                    Column(modifier = Modifier.padding(16.dp)) {
                        Row(verticalAlignment = Alignment.CenterVertically) {
                            Icon(Icons.Default.CleaningServices, contentDescription = null, tint = EduHubSuccess, modifier = Modifier.size(20.dp))
                            Spacer(modifier = Modifier.width(8.dp))
                            Text("Recent Cleanliness Checks (${uiState.cleanlinessChecks.size})", fontSize = 16.sp, fontWeight = FontWeight.Bold, color = EduHubTextDark)
                        }
                        Spacer(modifier = Modifier.height(12.dp))

                        if (uiState.cleanlinessChecks.isEmpty()) {
                            Text("No cleanliness checks recorded yet.", fontSize = 13.sp, color = EduHubTextMuted)
                        } else {
                            uiState.cleanlinessChecks.forEach { chk ->
                                Card(
                                    modifier = Modifier
                                        .fillMaxWidth()
                                        .padding(vertical = 4.dp),
                                    colors = CardDefaults.cardColors(containerColor = EduHubCardSurface),
                                    border = BorderStroke(1.dp, EduHubBorder)
                                ) {
                                    Column(modifier = Modifier.padding(12.dp)) {
                                        Row(
                                            modifier = Modifier.fillMaxWidth(),
                                            horizontalArrangement = Arrangement.SpaceBetween,
                                            verticalAlignment = Alignment.CenterVertically
                                        ) {
                                            Text(chk.area.uppercase(), fontSize = 13.sp, fontWeight = FontWeight.Bold, color = EduHubTextDark)
                                            val badgeBg = when (chk.status.lowercase()) {
                                                "excellent", "good" -> EduHubSuccessContainer
                                                else -> EduHubErrorContainer
                                            }
                                            val badgeColor = when (chk.status.lowercase()) {
                                                "excellent", "good" -> EduHubSuccess
                                                else -> EduHubError
                                            }
                                            Surface(
                                                color = badgeBg,
                                                shape = RoundedCornerShape(12.dp)
                                            ) {
                                                Text(chk.status.uppercase(), fontSize = 10.sp, fontWeight = FontWeight.Bold, color = badgeColor, modifier = Modifier.padding(horizontal = 8.dp, vertical = 2.dp))
                                            }
                                        }
                                        if (!chk.notes.isNullOrBlank()) {
                                            Spacer(modifier = Modifier.height(4.dp))
                                            Text("Notes: ${chk.notes}", fontSize = 12.sp, color = EduHubTextMuted)
                                        }
                                        Text(chk.createdAt ?: "", fontSize = 11.sp, color = EduHubTextMuted)
                                    }
                                }
                            }
                        }
                    }
                }
            }

            // Print Leaveout Chit Modal Dialog
            if (uiState.showPrintChitDialog && uiState.selectedChitToPrint != null) {
                val chit = uiState.selectedChitToPrint!!
                val schoolName = uiState.teacher?.schoolName ?: "KENYA EDUHUB ACADEMY"
                val teacherName = "${uiState.teacher?.firstName ?: ""} ${uiState.teacher?.lastName ?: ""}".trim().ifBlank { "Teacher on Duty" }
                val studentName = "${chit.firstName ?: "Student"} ${chit.lastName ?: ""}".trim()

                Dialog(
                    onDismissRequest = { viewModel.closePrintChitDialog() },
                    properties = DialogProperties(usePlatformDefaultWidth = false)
                ) {
                    Surface(
                        shape = RoundedCornerShape(16.dp),
                        color = Color.White,
                        border = BorderStroke(1.dp, EduHubBorder),
                        modifier = Modifier
                            .fillMaxWidth(0.95f)
                            .padding(12.dp)
                    ) {
                        Column(modifier = Modifier.padding(20.dp)) {
                            Row(
                                modifier = Modifier.fillMaxWidth(),
                                horizontalArrangement = Arrangement.SpaceBetween,
                                verticalAlignment = Alignment.CenterVertically
                            ) {
                                Text("Leaveout Chit Preview", fontSize = 18.sp, fontWeight = FontWeight.Bold, color = EduHubTextDark)
                                IconButton(onClick = { viewModel.closePrintChitDialog() }) {
                                    Icon(Icons.Default.Close, contentDescription = null)
                                }
                            }

                            Spacer(modifier = Modifier.height(12.dp))

                            // Chit Receipt Style Box
                            Box(
                                modifier = Modifier
                                    .fillMaxWidth()
                                    .border(2.dp, Color.Black, RoundedCornerShape(8.dp))
                                    .background(Color.White)
                                    .padding(16.dp)
                            ) {
                                Column(horizontalAlignment = Alignment.CenterHorizontally) {
                                    Text("LEAVEOUT CHIT", fontSize = 18.sp, fontWeight = FontWeight.Bold, fontFamily = FontFamily.Monospace)
                                    Text(schoolName.uppercase(), fontSize = 14.sp, fontWeight = FontWeight.Bold, color = EduHubOrange, fontFamily = FontFamily.Monospace)
                                    HorizontalDivider(color = Color.Black, thickness = 1.dp, modifier = Modifier.padding(vertical = 12.dp))

                                    ChitRow("Date:", chit.createdAt ?: "")
                                    ChitRow("Student:", studentName)
                                    ChitRow("Admission:", chit.admissionNumber ?: "N/A")
                                    ChitRow("Reason:", chit.reason)
                                    ChitRow("Teacher:", teacherName)

                                    HorizontalDivider(color = Color.Black, thickness = 1.dp, modifier = Modifier.padding(vertical = 12.dp))
                                    Text(
                                        "This student is permitted to leave the school premises for the reason stated above.",
                                        fontSize = 11.sp,
                                        fontFamily = FontFamily.Monospace,
                                        textAlign = TextAlign.Center,
                                        color = Color.DarkGray
                                    )

                                    Spacer(modifier = Modifier.height(24.dp))

                                    Row(
                                        modifier = Modifier.fillMaxWidth(),
                                        horizontalArrangement = Arrangement.SpaceBetween
                                    ) {
                                        Column(horizontalAlignment = Alignment.CenterHorizontally) {
                                            HorizontalDivider(color = Color.Black, thickness = 1.dp, modifier = Modifier.width(100.dp))
                                            Text("Teacher on Duty", fontSize = 10.sp, fontFamily = FontFamily.Monospace)
                                        }
                                        Column(horizontalAlignment = Alignment.CenterHorizontally) {
                                            HorizontalDivider(color = Color.Black, thickness = 1.dp, modifier = Modifier.width(100.dp))
                                            Text("Principal / Admin", fontSize = 10.sp, fontFamily = FontFamily.Monospace)
                                        }
                                    }
                                }
                            }

                            Spacer(modifier = Modifier.height(16.dp))

                            Row(
                                modifier = Modifier.fillMaxWidth(),
                                horizontalArrangement = Arrangement.End
                            ) {
                                OutlinedButton(
                                    onClick = { viewModel.closePrintChitDialog() },
                                    shape = RoundedCornerShape(20.dp)
                                ) {
                                    Text("Close")
                                }
                                Spacer(modifier = Modifier.width(8.dp))
                                Button(
                                    onClick = {
                                        val shareText = "LEAVEOUT CHIT\nSchool: $schoolName\nStudent: $studentName\nReason: ${chit.reason}\nTeacher: $teacherName\nDate: ${chit.createdAt}"
                                        val sendIntent: Intent = Intent().apply {
                                            action = Intent.ACTION_SEND
                                            putExtra(Intent.EXTRA_TEXT, shareText)
                                            type = "text/plain"
                                        }
                                        context.startActivity(Intent.createChooser(sendIntent, "Share/Print Leaveout Chit"))
                                    },
                                    colors = ButtonDefaults.buttonColors(containerColor = EduHubOrange),
                                    shape = RoundedCornerShape(20.dp)
                                ) {
                                    Icon(Icons.Default.Print, contentDescription = null, modifier = Modifier.size(16.dp))
                                    Spacer(modifier = Modifier.width(4.dp))
                                    Text("Share / Print")
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
private fun ChitRow(label: String, value: String) {
    Row(
        modifier = Modifier
            .fillMaxWidth()
            .padding(vertical = 4.dp),
        horizontalArrangement = Arrangement.SpaceBetween
    ) {
        Text(label, fontSize = 12.sp, fontWeight = FontWeight.Bold, fontFamily = FontFamily.Monospace, modifier = Modifier.width(80.dp))
        Text(value, fontSize = 12.sp, fontFamily = FontFamily.Monospace, modifier = Modifier.weight(1f), textAlign = TextAlign.End)
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
            label = { Text(label, fontSize = 12.sp) },
            trailingIcon = { ExposedDropdownMenuDefaults.TrailingIcon(expanded = expanded) },
            modifier = Modifier
                .fillMaxWidth()
                .menuAnchor(),
            shape = RoundedCornerShape(8.dp),
            colors = OutlinedTextFieldDefaults.colors(focusedBorderColor = EduHubOrange)
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
