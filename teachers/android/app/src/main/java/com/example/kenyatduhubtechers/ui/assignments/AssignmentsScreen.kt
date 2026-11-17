package com.example.kenyatduhubtechers.ui.assignments

import android.content.Intent
import android.net.Uri
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
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
import androidx.compose.material.icons.filled.BeachAccess
import androidx.compose.material.icons.filled.CalendarMonth
import androidx.compose.material.icons.filled.CheckCircle
import androidx.compose.material.icons.filled.Close
import androidx.compose.material.icons.filled.CloudUpload
import androidx.compose.material.icons.filled.ContentCopy
import androidx.compose.material.icons.filled.Delete
import androidx.compose.material.icons.filled.Download
import androidx.compose.material.icons.filled.Edit
import androidx.compose.material.icons.filled.Event
import androidx.compose.material.icons.filled.EventBusy
import androidx.compose.material.icons.filled.Groups
import androidx.compose.material.icons.filled.Info
import androidx.compose.material.icons.filled.MenuBook
import androidx.compose.material.icons.filled.Refresh
import androidx.compose.material.icons.filled.School
import androidx.compose.material.icons.filled.Search
import androidx.compose.material.icons.filled.Upload
import androidx.compose.material.icons.filled.Visibility
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
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.compose.ui.window.Dialog
import androidx.compose.ui.window.DialogProperties
import com.example.kenyatduhubtechers.data.model.AssignmentItemDto
import com.example.kenyatduhubtechers.data.network.RetrofitClient
import com.example.kenyatduhubtechers.ui.theme.EduHubBackground
import com.example.kenyatduhubtechers.ui.theme.EduHubBorder
import java.text.SimpleDateFormat
import java.util.Date
import java.util.Locale
import com.example.kenyatduhubtechers.ui.theme.EduHubError
import com.example.kenyatduhubtechers.ui.theme.EduHubErrorContainer
import com.example.kenyatduhubtechers.ui.theme.EduHubOrange
import com.example.kenyatduhubtechers.ui.theme.EduHubSuccess
import com.example.kenyatduhubtechers.ui.theme.EduHubSuccessContainer
import com.example.kenyatduhubtechers.ui.theme.EduHubTextDark
import com.example.kenyatduhubtechers.ui.theme.EduHubTextMuted

@OptIn(ExperimentalLayoutApi::class, ExperimentalMaterial3Api::class)
@Composable
fun AssignmentsScreen(
    viewModel: AssignmentsViewModel,
    modifier: Modifier = Modifier
) {
    val uiState by viewModel.uiState.collectAsState()
    val context = LocalContext.current

    val filePickerLauncher = rememberLauncherForActivityResult(
        contract = ActivityResultContracts.GetContent()
    ) { uri: Uri? ->
        uri?.let { viewModel.onFileSelected(context, it) }
    }

    PullToRefreshBox(
        isRefreshing = uiState.isLoading,
        onRefresh = { viewModel.loadAssignmentsData() },
        modifier = modifier.fillMaxSize()
    ) {
        if (uiState.isLoading && uiState.teacher == null && uiState.assignments.isEmpty()) {
            Box(
                modifier = Modifier.fillMaxSize(),
                contentAlignment = Alignment.Center
            ) {
                CircularProgressIndicator(color = EduHubOrange)
            }
        } else {
            val assignmentTypes = listOf("syllabus", "sentiment", "notes", "holiday")
            val classOptions = remember(uiState.classes) {
                listOf("All Classes") + uiState.classes.mapNotNull { it.className }
            }
            val classMap = remember(uiState.classes) {
                uiState.classes.associateBy { it.className }
            }
            val subjectOptions = remember(uiState.subjects) {
                listOf("All Subjects") + uiState.subjects.mapNotNull { it.subjectName }
            }
            val subjectMap = remember(uiState.subjects) {
                uiState.subjects.associateBy { it.subjectName }
            }

            val filteredAssignments = remember(
                uiState.assignments,
                uiState.searchQuery,
                uiState.typeFilter,
                uiState.sortFilter
            ) {
                var list = uiState.assignments.filter { item ->
                    val query = uiState.searchQuery.lowercase().trim()
                    val matchesQuery = query.isEmpty() ||
                            item.title.lowercase().contains(query) ||
                            (item.description?.lowercase()?.contains(query) == true)

                    val matchesType = uiState.typeFilter.isEmpty() ||
                            item.assignmentType.equals(uiState.typeFilter, ignoreCase = true)

                    matchesQuery && matchesType
                }

                list = when (uiState.sortFilter) {
                    "oldest" -> list.sortedBy { it.createdAt }
                    "title_asc" -> list.sortedBy { it.title.lowercase() }
                    "title_desc" -> list.sortedByDescending { it.title.lowercase() }
                    else -> list.sortedByDescending { it.createdAt }
                }

                list
            }

            Column(
                modifier = Modifier
                    .fillMaxSize()
                    .verticalScroll(rememberScrollState())
                    .padding(10.dp)
            ) {
                Text(
                    text = "Assignments Management",
                    fontSize = 22.sp,
                    fontWeight = FontWeight.Bold,
                    color = EduHubTextDark
                )
                Text(
                    text = "Upload syllabus, sentiments, notes, and holiday assignments for students.",
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

                // Sleek Orange Session Status Banner
                val status = uiState.calendarStatus
                val schoolStatus = status?.schoolStatus ?: "in_session"
                val isHoliday = status?.isHoliday ?: false

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
                            text = "School Session Status",
                            fontSize = 18.sp,
                            fontWeight = FontWeight.Bold,
                            color = Color.White
                        )

                        Spacer(modifier = Modifier.height(14.dp))

                        when {
                            isHoliday || schoolStatus == "holiday" -> {
                                Row(verticalAlignment = Alignment.CenterVertically) {
                                    Icon(Icons.Default.BeachAccess, contentDescription = null, tint = Color.White, modifier = Modifier.size(28.dp))
                                    Spacer(modifier = Modifier.width(12.dp))
                                    Column {
                                        Text("School is on Holiday", fontSize = 15.sp, fontWeight = FontWeight.Bold, color = Color.White)
                                        Text(
                                            text = "${status?.currentHoliday?.holidayName ?: "Holiday"} (${status?.currentHoliday?.startDate ?: ""} - ${status?.currentHoliday?.endDate ?: ""})",
                                            fontSize = 13.sp,
                                            color = Color.White.copy(alpha = 0.9f)
                                        )
                                    }
                                }
                            }
                            schoolStatus == "break" -> {
                                Row(verticalAlignment = Alignment.CenterVertically) {
                                    Icon(Icons.Default.EventBusy, contentDescription = null, tint = Color.White, modifier = Modifier.size(28.dp))
                                    Spacer(modifier = Modifier.width(12.dp))
                                    Column {
                                        Text("School is on Break", fontSize = 15.sp, fontWeight = FontWeight.Bold, color = Color.White)
                                        Text("No active term for current date", fontSize = 13.sp, color = Color.White.copy(alpha = 0.9f))
                                    }
                                }
                            }
                            else -> {
                                Row(verticalAlignment = Alignment.CenterVertically) {
                                    Icon(Icons.Default.School, contentDescription = null, tint = Color.White, modifier = Modifier.size(28.dp))
                                    Spacer(modifier = Modifier.width(12.dp))
                                    Column {
                                        Text("School is in Session", fontSize = 15.sp, fontWeight = FontWeight.Bold, color = Color.White)
                                        Text(status?.currentTerm?.termName ?: "Term 1", fontSize = 13.sp, fontWeight = FontWeight.SemiBold, color = Color.White)
                                        if (status?.currentTerm?.startDate != null) {
                                            Text(
                                                text = "${status.currentTerm.startDate} - ${status.currentTerm.endDate}",
                                                fontSize = 12.sp,
                                                color = Color.White.copy(alpha = 0.85f)
                                            )
                                        }
                                    }
                                }
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
                                Icon(Icons.Default.CalendarMonth, contentDescription = null, tint = Color.White, modifier = Modifier.size(16.dp))
                                Spacer(modifier = Modifier.width(6.dp))
                                Text("Academic Year: ${status?.currentYear ?: SimpleDateFormat("yyyy", Locale.getDefault()).format(Date())}", fontSize = 12.sp, color = Color.White)
                            }

                            Row(verticalAlignment = Alignment.CenterVertically) {
                                Icon(Icons.Default.Event, contentDescription = null, tint = Color.White, modifier = Modifier.size(16.dp))
                                Spacer(modifier = Modifier.width(6.dp))
                                Text("Today: ${SimpleDateFormat("MMMM d, yyyy", Locale.getDefault()).format(Date())}", fontSize = 12.sp, color = Color.White)
                            }
                        }
                    }
                }

                // Upload Form Card
                Card(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(bottom = 20.dp),
                    shape = RoundedCornerShape(12.dp),
                    colors = CardDefaults.cardColors(containerColor = EduHubBackground),
                    border = BorderStroke(1.dp, EduHubBorder)
                ) {
                    Column(modifier = Modifier.padding(20.dp)) {
                        Text("Upload New Assignment", fontSize = 18.sp, fontWeight = FontWeight.Bold, color = EduHubTextDark)
                        Spacer(modifier = Modifier.height(14.dp))

                        FilterDropdown(
                            modifier = Modifier.fillMaxWidth(),
                            label = "Assignment Type *",
                            selectedOption = if (uiState.uploadAssignmentType.isBlank()) "Select Type" else uiState.uploadAssignmentType.uppercase(),
                            options = listOf("Select Type") + assignmentTypes.map { it.uppercase() },
                            onOptionSelected = {
                                viewModel.onUploadTypeChanged(if (it == "Select Type") "" else it.lowercase())
                            }
                        )
                        Spacer(modifier = Modifier.height(10.dp))

                        OutlinedTextField(
                            value = uiState.uploadTitle,
                            onValueChange = { viewModel.onUploadTitleChanged(it) },
                            placeholder = { Text("Enter assignment title", fontSize = 13.sp) },
                            label = { Text("Title *", fontSize = 12.sp) },
                            modifier = Modifier.fillMaxWidth(),
                            shape = RoundedCornerShape(8.dp),
                            colors = OutlinedTextFieldDefaults.colors(focusedBorderColor = EduHubOrange)
                        )
                        Spacer(modifier = Modifier.height(10.dp))

                        OutlinedTextField(
                            value = uiState.uploadDescription,
                            onValueChange = { viewModel.onUploadDescriptionChanged(it) },
                            placeholder = { Text("Enter assignment description", fontSize = 13.sp) },
                            label = { Text("Description", fontSize = 12.sp) },
                            modifier = Modifier.fillMaxWidth(),
                            shape = RoundedCornerShape(8.dp),
                            colors = OutlinedTextFieldDefaults.colors(focusedBorderColor = EduHubOrange)
                        )
                        Spacer(modifier = Modifier.height(10.dp))

                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            horizontalArrangement = Arrangement.spacedBy(8.dp)
                        ) {
                            if (classOptions.size > 1) {
                                FilterDropdown(
                                    modifier = Modifier.weight(1f),
                                    label = "Class",
                                    selectedOption = classOptions.find { classMap[it]?.id == uiState.uploadClassId } ?: "All Classes",
                                    options = classOptions,
                                    onOptionSelected = {
                                        viewModel.onUploadClassChanged(classMap[it]?.id)
                                    }
                                )
                            }

                            if (subjectOptions.size > 1) {
                                FilterDropdown(
                                    modifier = Modifier.weight(1f),
                                    label = "Subject",
                                    selectedOption = subjectOptions.find { subjectMap[it]?.id == uiState.uploadSubjectId } ?: "All Subjects",
                                    options = subjectOptions,
                                    onOptionSelected = {
                                        viewModel.onUploadSubjectChanged(subjectMap[it]?.id)
                                    }
                                )
                            }
                        }
                        Spacer(modifier = Modifier.height(10.dp))

                        OutlinedTextField(
                            value = uiState.uploadDueDate,
                            onValueChange = { viewModel.onUploadDueDateChanged(it) },
                            placeholder = { Text("YYYY-MM-DD", fontSize = 13.sp) },
                            label = { Text("Due Date (Optional)", fontSize = 12.sp) },
                            modifier = Modifier.fillMaxWidth(),
                            shape = RoundedCornerShape(8.dp),
                            colors = OutlinedTextFieldDefaults.colors(focusedBorderColor = EduHubOrange)
                        )
                        Spacer(modifier = Modifier.height(12.dp))

                        // File picker box
                        Box(
                            modifier = Modifier
                                .fillMaxWidth()
                                .border(2.dp, EduHubBorder, RoundedCornerShape(8.dp))
                                .background(Color.White)
                                .padding(16.dp),
                            contentAlignment = Alignment.Center
                        ) {
                            Column(horizontalAlignment = Alignment.CenterHorizontally) {
                                Icon(Icons.Default.CloudUpload, contentDescription = null, tint = EduHubOrange, modifier = Modifier.size(32.dp))
                                Spacer(modifier = Modifier.height(6.dp))
                                if (uiState.selectedFileUri != null) {
                                    Text("Selected: ${uiState.selectedFileName}", fontSize = 13.sp, fontWeight = FontWeight.Bold, color = EduHubSuccess)
                                    Spacer(modifier = Modifier.height(6.dp))
                                    OutlinedButton(
                                        onClick = { viewModel.clearSelectedFile() },
                                        shape = RoundedCornerShape(20.dp)
                                    ) {
                                        Text("Change File", fontSize = 11.sp)
                                    }
                                } else {
                                    Text("Click to select file (PDF, DOCX, PPT, JPG, PNG)", fontSize = 12.sp, color = EduHubTextMuted)
                                    Spacer(modifier = Modifier.height(8.dp))
                                    Button(
                                        onClick = { filePickerLauncher.launch("*/*") },
                                        colors = ButtonDefaults.buttonColors(containerColor = EduHubOrange),
                                        shape = RoundedCornerShape(20.dp)
                                    ) {
                                        Icon(Icons.Default.Upload, contentDescription = null, modifier = Modifier.size(16.dp))
                                        Spacer(modifier = Modifier.width(4.dp))
                                        Text("Browse File", fontSize = 12.sp)
                                    }
                                }
                            }
                        }

                        Spacer(modifier = Modifier.height(16.dp))

                        Button(
                            onClick = { viewModel.uploadAssignment(context) },
                            modifier = Modifier.fillMaxWidth(),
                            colors = ButtonDefaults.buttonColors(containerColor = EduHubOrange),
                            shape = RoundedCornerShape(8.dp),
                            enabled = !uiState.isSubmitting
                        ) {
                            if (uiState.isSubmitting) {
                                CircularProgressIndicator(color = Color.White, modifier = Modifier.size(18.dp))
                            } else {
                                Icon(Icons.Default.CloudUpload, contentDescription = null, modifier = Modifier.size(18.dp))
                                Spacer(modifier = Modifier.width(6.dp))
                                Text("Upload Assignment", fontSize = 14.sp, fontWeight = FontWeight.Bold)
                            }
                        }
                    }
                }

                // Recent Assignments Card
                Card(
                    modifier = Modifier.fillMaxWidth(),
                    shape = RoundedCornerShape(12.dp),
                    colors = CardDefaults.cardColors(containerColor = EduHubBackground),
                    border = BorderStroke(1.dp, EduHubBorder)
                ) {
                    Column(modifier = Modifier.padding(16.dp)) {
                        Text("Recent Assignments (${filteredAssignments.size})", fontSize = 18.sp, fontWeight = FontWeight.Bold, color = EduHubTextDark)
                        Spacer(modifier = Modifier.height(12.dp))

                        // Filters
                        OutlinedTextField(
                            value = uiState.searchQuery,
                            onValueChange = { viewModel.onSearchQueryChanged(it) },
                            placeholder = { Text("Search by title or description...", fontSize = 13.sp) },
                            leadingIcon = { Icon(Icons.Default.Search, contentDescription = null, tint = EduHubTextMuted) },
                            modifier = Modifier
                                .fillMaxWidth()
                                .padding(bottom = 10.dp),
                            shape = RoundedCornerShape(8.dp),
                            colors = OutlinedTextFieldDefaults.colors(focusedBorderColor = EduHubOrange)
                        )

                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            horizontalArrangement = Arrangement.spacedBy(8.dp),
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            FilterDropdown(
                                modifier = Modifier.weight(1f),
                                label = "Type",
                                selectedOption = if (uiState.typeFilter.isBlank()) "All Types" else uiState.typeFilter.uppercase(),
                                options = listOf("All Types") + assignmentTypes.map { it.uppercase() },
                                onOptionSelected = {
                                    viewModel.onTypeFilterChanged(if (it == "All Types") "" else it.lowercase())
                                }
                            )

                            FilterDropdown(
                                modifier = Modifier.weight(1f),
                                label = "Sort By",
                                selectedOption = when (uiState.sortFilter) {
                                    "oldest" -> "Oldest First"
                                    "title_asc" -> "Title A-Z"
                                    "title_desc" -> "Title Z-A"
                                    else -> "Newest First"
                                },
                                options = listOf("Newest First", "Oldest First", "Title A-Z", "Title Z-A"),
                                onOptionSelected = {
                                    val key = when (it) {
                                        "Oldest First" -> "oldest"
                                        "Title A-Z" -> "title_asc"
                                        "Title Z-A" -> "title_desc"
                                        else -> "newest"
                                    }
                                    viewModel.onSortFilterChanged(key)
                                }
                            )

                            IconButton(
                                onClick = { viewModel.resetFilters() },
                                modifier = Modifier.size(40.dp)
                            ) {
                                Icon(Icons.Default.Refresh, contentDescription = "Reset Filters", tint = EduHubOrange)
                            }
                        }

                        Spacer(modifier = Modifier.height(16.dp))

                        if (filteredAssignments.isEmpty()) {
                            Text("No assignments found.", fontSize = 13.sp, color = EduHubTextMuted, modifier = Modifier.padding(vertical = 12.dp))
                        } else {
                            filteredAssignments.forEach { item ->
                                AssignmentCardItem(
                                    item = item,
                                    baseUrl = RetrofitClient.getBaseUrl(),
                                    context = context,
                                    onPreview = { viewModel.openPreviewDialog(item) },
                                    onEdit = { viewModel.openEditDialog(item) },
                                    onDuplicate = { viewModel.openDuplicateDialog(item) },
                                    onDelete = { viewModel.openDeleteDialog(item) }
                                )
                                Spacer(modifier = Modifier.height(12.dp))
                            }
                        }
                    }
                }

                Spacer(modifier = Modifier.height(24.dp))
            }

            // Edit Dialog
            if (uiState.showEditDialog && uiState.editingAssignment != null) {
                Dialog(
                    onDismissRequest = { viewModel.closeEditDialog() },
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
                                Text("Edit Assignment", fontSize = 18.sp, fontWeight = FontWeight.Bold, color = EduHubTextDark)
                                IconButton(onClick = { viewModel.closeEditDialog() }) {
                                    Icon(Icons.Default.Close, contentDescription = null)
                                }
                            }

                            Spacer(modifier = Modifier.height(12.dp))
                            HorizontalDivider(color = EduHubBorder)
                            Spacer(modifier = Modifier.height(12.dp))

                            OutlinedTextField(
                                value = uiState.editTitle,
                                onValueChange = { viewModel.onEditTitleChanged(it) },
                                label = { Text("Title *", fontSize = 12.sp) },
                                modifier = Modifier.fillMaxWidth(),
                                shape = RoundedCornerShape(8.dp)
                            )
                            Spacer(modifier = Modifier.height(10.dp))

                            OutlinedTextField(
                                value = uiState.editDescription,
                                onValueChange = { viewModel.onEditDescriptionChanged(it) },
                                label = { Text("Description", fontSize = 12.sp) },
                                modifier = Modifier.fillMaxWidth(),
                                shape = RoundedCornerShape(8.dp)
                            )
                            Spacer(modifier = Modifier.height(10.dp))

                            OutlinedTextField(
                                value = uiState.editDueDate,
                                onValueChange = { viewModel.onEditDueDateChanged(it) },
                                label = { Text("Due Date", fontSize = 12.sp) },
                                modifier = Modifier.fillMaxWidth(),
                                shape = RoundedCornerShape(8.dp)
                            )
                            Spacer(modifier = Modifier.height(16.dp))

                            Row(
                                modifier = Modifier.fillMaxWidth(),
                                horizontalArrangement = Arrangement.End
                            ) {
                                OutlinedButton(onClick = { viewModel.closeEditDialog() }) { Text("Cancel") }
                                Spacer(modifier = Modifier.width(8.dp))
                                Button(
                                    onClick = { viewModel.saveEdit() },
                                    colors = ButtonDefaults.buttonColors(containerColor = EduHubOrange),
                                    enabled = !uiState.isSubmitting
                                ) {
                                    Text("Save Changes")
                                }
                            }
                        }
                    }
                }
            }

            // Duplicate Confirm Dialog
            if (uiState.showDuplicateDialog && uiState.duplicatingAssignment != null) {
                val item = uiState.duplicatingAssignment!!
                Dialog(onDismissRequest = { viewModel.closeDuplicateDialog() }) {
                    Surface(
                        shape = RoundedCornerShape(16.dp),
                        color = Color.White,
                        border = BorderStroke(1.dp, EduHubBorder),
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(16.dp)
                    ) {
                        Column(modifier = Modifier.padding(20.dp)) {
                            Text("Duplicate Assignment", fontSize = 18.sp, fontWeight = FontWeight.Bold, color = EduHubTextDark)
                            Spacer(modifier = Modifier.height(10.dp))
                            Text("Do you want to duplicate \"${item.title}\"?", fontSize = 14.sp, color = EduHubTextDark)
                            Text("A copy will be created with \"(Copy)\" added to the title.", fontSize = 12.sp, color = EduHubTextMuted)

                            Spacer(modifier = Modifier.height(16.dp))

                            Row(
                                modifier = Modifier.fillMaxWidth(),
                                horizontalArrangement = Arrangement.End
                            ) {
                                OutlinedButton(onClick = { viewModel.closeDuplicateDialog() }) { Text("Cancel") }
                                Spacer(modifier = Modifier.width(8.dp))
                                Button(
                                    onClick = { viewModel.confirmDuplicate() },
                                    colors = ButtonDefaults.buttonColors(containerColor = EduHubOrange),
                                    enabled = !uiState.isSubmitting
                                ) {
                                    Text("Duplicate")
                                }
                            }
                        }
                    }
                }
            }

            // Delete Confirm Dialog
            if (uiState.showDeleteDialog && uiState.deletingAssignment != null) {
                val item = uiState.deletingAssignment!!
                Dialog(onDismissRequest = { viewModel.closeDeleteDialog() }) {
                    Surface(
                        shape = RoundedCornerShape(16.dp),
                        color = Color.White,
                        border = BorderStroke(1.dp, EduHubBorder),
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(16.dp)
                    ) {
                        Column(modifier = Modifier.padding(20.dp)) {
                            Text("Delete Assignment", fontSize = 18.sp, fontWeight = FontWeight.Bold, color = EduHubError)
                            Spacer(modifier = Modifier.height(10.dp))
                            Text("Are you sure you want to delete \"${item.title}\"?", fontSize = 14.sp, color = EduHubTextDark)
                            Text("This action cannot be undone.", fontSize = 12.sp, color = EduHubError)

                            Spacer(modifier = Modifier.height(16.dp))

                            Row(
                                modifier = Modifier.fillMaxWidth(),
                                horizontalArrangement = Arrangement.End
                            ) {
                                OutlinedButton(onClick = { viewModel.closeDeleteDialog() }) { Text("Cancel") }
                                Spacer(modifier = Modifier.width(8.dp))
                                Button(
                                    onClick = { viewModel.confirmDelete() },
                                    colors = ButtonDefaults.buttonColors(containerColor = EduHubError),
                                    enabled = !uiState.isSubmitting
                                ) {
                                    Text("Delete")
                                }
                            }
                        }
                    }
                }
            }

            // Preview Dialog
            if (uiState.showPreviewDialog && uiState.previewAssignment != null) {
                val item = uiState.previewAssignment!!
                Dialog(
                    onDismissRequest = { viewModel.closePreviewDialog() },
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
                                Text("Preview: ${item.fileName ?: item.title}", fontSize = 16.sp, fontWeight = FontWeight.Bold, color = EduHubTextDark)
                                IconButton(onClick = { viewModel.closePreviewDialog() }) {
                                    Icon(Icons.Default.Close, contentDescription = null)
                                }
                            }

                            Spacer(modifier = Modifier.height(12.dp))
                            HorizontalDivider(color = EduHubBorder)
                            Spacer(modifier = Modifier.height(12.dp))

                            Text("Title: ${item.title}", fontSize = 14.sp, fontWeight = FontWeight.Bold, color = EduHubOrange)
                            if (!item.description.isNull_or_empty()) {
                                Text("Description: ${item.description}", fontSize = 12.sp, color = EduHubTextMuted)
                            }
                            Text("Type: ${item.assignmentType.uppercase()}", fontSize = 12.sp, fontWeight = FontWeight.Bold)
                            if (!item.className.isNull_or_empty()) Text("Class: ${item.className}", fontSize = 12.sp, color = EduHubTextMuted)
                            if (!item.subjectName.isNull_or_empty()) Text("Subject: ${item.subjectName}", fontSize = 12.sp, color = EduHubTextMuted)
                            if (!item.dueDate.isNull_or_empty()) Text("Due Date: ${item.dueDate}", fontSize = 12.sp, color = EduHubTextMuted)

                            Spacer(modifier = Modifier.height(20.dp))

                            OutlinedButton(
                                onClick = { viewModel.closePreviewDialog() },
                                modifier = Modifier.align(Alignment.End),
                                shape = RoundedCornerShape(20.dp)
                            ) {
                                Text("Close")
                            }
                        }
                    }
                }
            }
        }
    }
}

@Composable
private fun AssignmentCardItem(
    item: AssignmentItemDto,
    baseUrl: String,
    context: android.content.Context,
    onPreview: () -> Unit,
    onEdit: () -> Unit,
    onDuplicate: () -> Unit,
    onDelete: () -> Unit
) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(containerColor = EduHubBackground),
        border = BorderStroke(1.dp, EduHubBorder)
    ) {
        Column(modifier = Modifier.padding(14.dp)) {
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Text(item.title, fontSize = 15.sp, fontWeight = FontWeight.Bold, color = EduHubTextDark, modifier = Modifier.weight(1f))

                val badgeBg = when (item.assignmentType.lowercase()) {
                    "syllabus" -> Color(0xFFE8F0FE)
                    "sentiment" -> EduHubErrorContainer
                    "notes" -> EduHubSuccessContainer
                    else -> Color(0xFFFEF7E0)
                }
                val badgeColor = when (item.assignmentType.lowercase()) {
                    "syllabus" -> Color(0xFF1967D2)
                    "sentiment" -> EduHubError
                    "notes" -> EduHubSuccess
                    else -> Color(0xFFB06000)
                }

                Surface(
                    color = badgeBg,
                    shape = RoundedCornerShape(12.dp)
                ) {
                    Text(item.assignmentType.uppercase(), fontSize = 10.sp, fontWeight = FontWeight.Bold, color = badgeColor, modifier = Modifier.padding(horizontal = 8.dp, vertical = 2.dp))
                }
            }

            if (!item.description.isNullOrBlank()) {
                Spacer(modifier = Modifier.height(6.dp))
                Text(item.description, fontSize = 12.sp, color = EduHubTextMuted)
            }

            Spacer(modifier = Modifier.height(8.dp))

            FlowRow(
                horizontalArrangement = Arrangement.spacedBy(10.dp),
                verticalArrangement = Arrangement.spacedBy(4.dp)
            ) {
                if (!item.className.isNullOrBlank()) {
                    Row(verticalAlignment = Alignment.CenterVertically) {
                        Icon(Icons.Default.Groups, contentDescription = null, tint = EduHubOrange, modifier = Modifier.size(14.dp))
                        Spacer(modifier = Modifier.width(4.dp))
                        Text(item.className, fontSize = 11.sp, color = EduHubTextMuted)
                    }
                }

                if (!item.subjectName.isNullOrBlank()) {
                    Row(verticalAlignment = Alignment.CenterVertically) {
                        Icon(Icons.Default.MenuBook, contentDescription = null, tint = EduHubOrange, modifier = Modifier.size(14.dp))
                        Spacer(modifier = Modifier.width(4.dp))
                        Text(item.subjectName, fontSize = 11.sp, color = EduHubTextMuted)
                    }
                }

                if (!item.dueDate.isNullOrBlank()) {
                    Row(verticalAlignment = Alignment.CenterVertically) {
                        Icon(Icons.Default.Event, contentDescription = null, tint = EduHubOrange, modifier = Modifier.size(14.dp))
                        Spacer(modifier = Modifier.width(4.dp))
                        Text("Due: ${item.dueDate}", fontSize = 11.sp, color = EduHubTextMuted)
                    }
                }
            }

            Spacer(modifier = Modifier.height(8.dp))
            HorizontalDivider(color = EduHubBorder.copy(alpha = 0.5f))
            Spacer(modifier = Modifier.height(8.dp))

            // Action row
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Text(
                    text = "${item.teacherName ?: "Teacher"} • ${item.createdAt?.take(10) ?: ""}",
                    fontSize = 11.sp,
                    color = EduHubTextMuted
                )

                Row {
                    IconButton(onClick = onPreview, modifier = Modifier.size(30.dp)) {
                        Icon(Icons.Default.Visibility, contentDescription = "Preview", tint = EduHubOrange, modifier = Modifier.size(18.dp))
                    }
                    IconButton(onClick = onEdit, modifier = Modifier.size(30.dp)) {
                        Icon(Icons.Default.Edit, contentDescription = "Edit", tint = Color(0xFF1967D2), modifier = Modifier.size(18.dp))
                    }
                    IconButton(onClick = onDuplicate, modifier = Modifier.size(30.dp)) {
                        Icon(Icons.Default.ContentCopy, contentDescription = "Duplicate", tint = EduHubSuccess, modifier = Modifier.size(18.dp))
                    }
                    IconButton(onClick = onDelete, modifier = Modifier.size(30.dp)) {
                        Icon(Icons.Default.Delete, contentDescription = "Delete", tint = EduHubError, modifier = Modifier.size(18.dp))
                    }
                }
            }
        }
    }
}

private fun String?.isNull_or_empty(): Boolean = this == null || this.trim().isEmpty()

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
