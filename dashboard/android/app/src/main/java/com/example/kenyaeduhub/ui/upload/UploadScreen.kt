package com.example.kenyaeduhub.ui.upload

import android.content.Context
import android.net.Uri
import android.provider.OpenableColumns
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.CheckCircle
import androidx.compose.material.icons.filled.FolderOpen
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.example.kenyaeduhub.data.User
import com.example.kenyaeduhub.ui.dashboard.DashboardViewModel
import com.example.kenyaeduhub.ui.theme.getAppThemeColors

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun UploadScreen(
    user: User,
    viewModel: DashboardViewModel,
    userUploadCount: Int,
    cardBgColor: Color = Color(0xFFF0F4F9),
    borderColor: Color = Color(0xFFE8EAED)
) {
    val context = LocalContext.current
    val isUploading by viewModel.isUploading.collectAsState()

    val themeColors = getAppThemeColors()
    val activeCardBg = themeColors.cardBgColor
    val activeBorder = if (themeColors.isDark) null else BorderStroke(1.dp, themeColors.borderColor)

    var uploadTitle by remember { mutableStateOf("") }
    var uploadLevel by remember { mutableStateOf("Secondary School") }
    var uploadSubject by remember { mutableStateOf("") }
    var uploadFileType by remember { mutableStateOf("PDF") }
    var uploadDescription by remember { mutableStateOf("") }
    var uploadMessage by remember { mutableStateOf<String?>(null) }
    var isUploadSuccess by remember { mutableStateOf(false) }

    var selectedFileUri by remember { mutableStateOf<Uri?>(null) }
    var selectedFileName by remember { mutableStateOf<String?>(null) }

    val filePickerLauncher = rememberLauncherForActivityResult(
        contract = ActivityResultContracts.GetContent()
    ) { uri: Uri? ->
        if (uri != null) {
            selectedFileUri = uri
            selectedFileName = getFileNameFromUri(context, uri) ?: "Selected Document"
            uploadMessage = null
        }
    }

    val levelOptions = listOf("Primary School", "Secondary School", "College", "University")
    val fileTypeOptions = listOf("PDF", "DOC", "DOCX", "PPT", "PPTX", "XLS", "XLSX", "TXT")

    var levelExpanded by remember { mutableStateOf(false) }
    var typeExpanded by remember { mutableStateOf(false) }

    LazyColumn(
        modifier = Modifier
            .fillMaxSize()
            .padding(horizontal = 16.dp),
        contentPadding = PaddingValues(vertical = 16.dp),
        verticalArrangement = Arrangement.spacedBy(16.dp)
    ) {
        item {
            Card(
                modifier = Modifier.fillMaxWidth(),
                shape = RoundedCornerShape(16.dp),
                colors = CardDefaults.cardColors(containerColor = activeCardBg),
                border = activeBorder
            ) {
                Column(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(20.dp),
                    verticalArrangement = Arrangement.spacedBy(16.dp)
                ) {
                    Text(
                        text = "Upload Resource",
                        fontSize = 20.sp,
                        fontWeight = FontWeight.Bold,
                        color = themeColors.primaryText
                    )

                    if (userUploadCount < 2) {
                        Card(
                            modifier = Modifier.fillMaxWidth(),
                            shape = RoundedCornerShape(12.dp),
                            colors = CardDefaults.cardColors(containerColor = if (themeColors.isDark) Color(0xFF1E2A3A) else Color(0xFFE8F0FE))
                        ) {
                            Column(modifier = Modifier.padding(14.dp)) {
                                Text(
                                    text = "You need to upload at least 2 resources to unlock downloads.",
                                    fontSize = 12.sp,
                                    fontWeight = FontWeight.SemiBold,
                                    color = Color(0xFF1967D2)
                                )
                                Spacer(modifier = Modifier.height(2.dp))
                                Text(
                                    text = "You have uploaded $userUploadCount resource(s).",
                                    fontSize = 12.sp,
                                    color = themeColors.primaryText
                                )
                            }
                        }
                    }

                    // Progress Indicator when Uploading
                    if (isUploading) {
                        Card(
                            modifier = Modifier.fillMaxWidth(),
                            shape = RoundedCornerShape(12.dp),
                            colors = CardDefaults.cardColors(containerColor = if (themeColors.isDark) Color(0xFF1E2A3A) else Color(0xFFE8F0FE)),
                            border = BorderStroke(1.dp, Color(0xFF1967D2))
                        ) {
                            Column(
                                modifier = Modifier.padding(16.dp),
                                horizontalAlignment = Alignment.CenterHorizontally,
                                verticalArrangement = Arrangement.spacedBy(10.dp)
                            ) {
                                Row(verticalAlignment = Alignment.CenterVertically) {
                                    CircularProgressIndicator(
                                        color = Color(0xFFFF6B35),
                                        modifier = Modifier.size(20.dp),
                                        strokeWidth = 2.dp
                                    )
                                    Spacer(modifier = Modifier.width(10.dp))
                                    Text(
                                        text = "Uploading resource to server, please wait...",
                                        fontSize = 13.sp,
                                        fontWeight = FontWeight.Bold,
                                        color = Color(0xFF1967D2)
                                    )
                                }
                                LinearProgressIndicator(
                                    modifier = Modifier
                                        .fillMaxWidth()
                                        .height(6.dp)
                                        .clip(RoundedCornerShape(3.dp)),
                                    color = Color(0xFFFF6B35),
                                    trackColor = Color(0xFFDADCE0)
                                )
                            }
                        }
                    }

                    // Title Input
                    OutlinedTextField(
                        value = uploadTitle,
                        onValueChange = { uploadTitle = it },
                        label = { Text("Resource Title *") },
                        placeholder = { Text("Enter resource title") },
                        singleLine = true,
                        enabled = !isUploading,
                        shape = RoundedCornerShape(25.dp),
                        colors = OutlinedTextFieldDefaults.colors(
                            focusedBorderColor = Color(0xFFFF6B35),
                            unfocusedBorderColor = themeColors.formBorderColor,
                            focusedContainerColor = activeCardBg,
                            unfocusedContainerColor = activeCardBg,
                            focusedTextColor = themeColors.primaryText,
                            unfocusedTextColor = themeColors.primaryText
                        ),
                        modifier = Modifier.fillMaxWidth()
                    )

                    // Education Level Selector
                    ExposedDropdownMenuBox(
                        expanded = levelExpanded && !isUploading,
                        onExpandedChange = { if (!isUploading) levelExpanded = !levelExpanded },
                        modifier = Modifier.fillMaxWidth()
                    ) {
                        OutlinedTextField(
                            value = uploadLevel,
                            onValueChange = {},
                            readOnly = true,
                            enabled = !isUploading,
                            label = { Text("Education Level *") },
                            trailingIcon = { ExposedDropdownMenuDefaults.TrailingIcon(expanded = levelExpanded) },
                            shape = RoundedCornerShape(25.dp),
                            colors = OutlinedTextFieldDefaults.colors(
                                focusedBorderColor = Color(0xFFFF6B35),
                                unfocusedBorderColor = themeColors.formBorderColor,
                                focusedContainerColor = activeCardBg,
                                unfocusedContainerColor = activeCardBg,
                                focusedTextColor = themeColors.primaryText,
                                unfocusedTextColor = themeColors.primaryText
                            ),
                            modifier = Modifier
                                .menuAnchor(ExposedDropdownMenuAnchorType.PrimaryNotEditable)
                                .fillMaxWidth()
                        )
                        ExposedDropdownMenu(
                            expanded = levelExpanded && !isUploading,
                            onDismissRequest = { levelExpanded = false }
                        ) {
                            levelOptions.forEach { option ->
                                DropdownMenuItem(
                                    text = { Text(option) },
                                    onClick = {
                                        uploadLevel = option
                                        levelExpanded = false
                                    }
                                )
                            }
                        }
                    }

                    // Subject Input
                    OutlinedTextField(
                        value = uploadSubject,
                        onValueChange = { uploadSubject = it },
                        label = { Text("Subject *") },
                        placeholder = { Text("Enter subject name") },
                        singleLine = true,
                        enabled = !isUploading,
                        shape = RoundedCornerShape(25.dp),
                        colors = OutlinedTextFieldDefaults.colors(
                            focusedBorderColor = Color(0xFFFF6B35),
                            unfocusedBorderColor = themeColors.formBorderColor,
                            focusedContainerColor = activeCardBg,
                            unfocusedContainerColor = activeCardBg,
                            focusedTextColor = themeColors.primaryText,
                            unfocusedTextColor = themeColors.primaryText
                        ),
                        modifier = Modifier.fillMaxWidth()
                    )

                    // File Type Selector
                    ExposedDropdownMenuBox(
                        expanded = typeExpanded && !isUploading,
                        onExpandedChange = { if (!isUploading) typeExpanded = !typeExpanded },
                        modifier = Modifier.fillMaxWidth()
                    ) {
                        OutlinedTextField(
                            value = uploadFileType,
                            onValueChange = {},
                            readOnly = true,
                            enabled = !isUploading,
                            label = { Text("File Type *") },
                            trailingIcon = { ExposedDropdownMenuDefaults.TrailingIcon(expanded = typeExpanded) },
                            shape = RoundedCornerShape(25.dp),
                            colors = OutlinedTextFieldDefaults.colors(
                                focusedBorderColor = Color(0xFFFF6B35),
                                unfocusedBorderColor = themeColors.formBorderColor,
                                focusedContainerColor = activeCardBg,
                                unfocusedContainerColor = activeCardBg,
                                focusedTextColor = themeColors.primaryText,
                                unfocusedTextColor = themeColors.primaryText
                            ),
                            modifier = Modifier
                                .menuAnchor(ExposedDropdownMenuAnchorType.PrimaryNotEditable)
                                .fillMaxWidth()
                        )
                        ExposedDropdownMenu(
                            expanded = typeExpanded && !isUploading,
                            onDismissRequest = { typeExpanded = false }
                        ) {
                            fileTypeOptions.forEach { option ->
                                DropdownMenuItem(
                                    text = { Text(option) },
                                    onClick = {
                                        uploadFileType = option
                                        typeExpanded = false
                                    }
                                )
                            }
                        }
                    }

                    // Description Input
                    OutlinedTextField(
                        value = uploadDescription,
                        onValueChange = { uploadDescription = it },
                        label = { Text("Description *") },
                        placeholder = { Text("Brief description of the resource...") },
                        maxLines = 3,
                        enabled = !isUploading,
                        shape = RoundedCornerShape(25.dp),
                        colors = OutlinedTextFieldDefaults.colors(
                            focusedBorderColor = Color(0xFFFF6B35),
                            unfocusedBorderColor = themeColors.formBorderColor,
                            focusedContainerColor = activeCardBg,
                            unfocusedContainerColor = activeCardBg,
                            focusedTextColor = themeColors.primaryText,
                            unfocusedTextColor = themeColors.primaryText
                        ),
                        modifier = Modifier.fillMaxWidth()
                    )

                    // Interactive File Selection Drop Box
                    Box(
                        modifier = Modifier
                            .fillMaxWidth()
                            .clip(RoundedCornerShape(25.dp))
                            .background(if (selectedFileUri != null) (if (themeColors.isDark) Color(0xFF1B382B) else Color(0xFFE6F4EA)) else activeCardBg)
                            .border(
                                if (themeColors.isDark) 0.dp else 2.dp,
                                if (selectedFileUri != null) Color(0xFF137333) else themeColors.formBorderColor,
                                RoundedCornerShape(25.dp)
                            )
                            .clickable(enabled = !isUploading) { filePickerLauncher.launch("*/*") }
                            .padding(24.dp),
                        contentAlignment = Alignment.Center
                    ) {
                        Column(horizontalAlignment = Alignment.CenterHorizontally) {
                            Icon(
                                imageVector = if (selectedFileUri != null) Icons.Default.CheckCircle else Icons.Default.FolderOpen,
                                contentDescription = "Upload Document",
                                tint = if (selectedFileUri != null) Color(0xFF137333) else Color(0xFFFF6B35),
                                modifier = Modifier.size(36.dp)
                            )
                            Spacer(modifier = Modifier.height(8.dp))
                            Text(
                                text = selectedFileName ?: "Select resource document file",
                                fontWeight = FontWeight.Bold,
                                fontSize = 14.sp,
                                color = if (selectedFileUri != null) Color(0xFF137333) else themeColors.primaryText
                            )
                            Text(
                                text = if (selectedFileUri != null) "Tap to change file" else "Supported: PDF, DOC, DOCX, PPT, PPTX, XLS, XLSX, TXT (Max 20MB)",
                                fontSize = 11.sp,
                                color = themeColors.secondaryText,
                                textAlign = TextAlign.Center
                            )
                        }
                    }

                    // Upload Message Feedback
                    uploadMessage?.let { msg ->
                        Box(
                            modifier = Modifier
                                .fillMaxWidth()
                                .clip(RoundedCornerShape(20.dp))
                                .background(if (isUploadSuccess) Color(0xFFE6F4EA) else Color(0xFFFCE8E6))
                                .padding(12.dp),
                            contentAlignment = Alignment.Center
                        ) {
                            Text(
                                text = msg,
                                color = if (isUploadSuccess) Color(0xFF137333) else Color(0xFFC5221F),
                                fontSize = 13.sp,
                                fontWeight = FontWeight.Medium,
                                textAlign = TextAlign.Center
                            )
                        }
                    }

                    // Upload & Clear Buttons
                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.spacedBy(12.dp)
                    ) {
                        Button(
                            onClick = {
                                if (uploadTitle.isBlank() || uploadSubject.isBlank() || uploadDescription.isBlank()) {
                                    uploadMessage = "Please fill in all required fields"
                                    isUploadSuccess = false
                                } else if (selectedFileUri == null) {
                                    uploadMessage = "Please select a resource document file to upload"
                                    isUploadSuccess = false
                                } else {
                                    uploadMessage = null
                                    val uri = selectedFileUri!!
                                    val fName = selectedFileName ?: "document.pdf"

                                    viewModel.uploadResource(
                                        context = context,
                                        userId = user.id,
                                        title = uploadTitle,
                                        level = uploadLevel,
                                        subject = uploadSubject,
                                        type = uploadFileType,
                                        description = uploadDescription,
                                        fileUri = uri,
                                        fileName = fName
                                    ) { success, msg ->
                                        uploadMessage = msg
                                        isUploadSuccess = success
                                        if (success) {
                                            uploadTitle = ""
                                            uploadSubject = ""
                                            uploadDescription = ""
                                            selectedFileUri = null
                                            selectedFileName = null
                                        }
                                    }
                                }
                            },
                            enabled = !isUploading,
                            shape = RoundedCornerShape(25.dp),
                            colors = ButtonDefaults.buttonColors(
                                containerColor = Color(0xFFFF6B35),
                                contentColor = Color.White,
                                disabledContainerColor = Color(0xFFFF6B35).copy(alpha = 0.7f),
                                disabledContentColor = Color.White
                            ),
                            modifier = Modifier
                                .weight(1f)
                                .height(48.dp)
                        ) {
                            if (isUploading) {
                                CircularProgressIndicator(color = Color.White, modifier = Modifier.size(20.dp), strokeWidth = 2.dp)
                                Spacer(modifier = Modifier.width(8.dp))
                                Text("Uploading...", fontSize = 14.sp, fontWeight = FontWeight.Bold, color = Color.White)
                            } else {
                                Text("Upload Resource", fontSize = 14.sp, fontWeight = FontWeight.Bold, color = Color.White)
                            }
                        }

                        OutlinedButton(
                            onClick = {
                                uploadTitle = ""
                                uploadSubject = ""
                                uploadDescription = ""
                                selectedFileUri = null
                                selectedFileName = null
                                uploadMessage = null
                            },
                            enabled = !isUploading,
                            shape = RoundedCornerShape(25.dp),
                            colors = ButtonDefaults.outlinedButtonColors(
                                containerColor = if (themeColors.isDark) Color(0xFF2A2A2A) else Color(0xFFF1F3F4),
                                contentColor = themeColors.primaryText
                            ),
                            border = BorderStroke(1.dp, if (themeColors.isDark) Color(0xFF3C4043) else Color(0xFF000000)),
                            modifier = Modifier
                                .weight(1f)
                                .height(48.dp)
                        ) {
                            Text("Clear", fontSize = 14.sp, fontWeight = FontWeight.Medium, color = themeColors.primaryText)
                        }
                    }
                }
            }
        }
    }
}

fun getFileNameFromUri(context: Context, uri: Uri): String? {
    var name: String? = null
    val cursor = context.contentResolver.query(uri, null, null, null, null)
    cursor?.use {
        if (it.moveToFirst()) {
            val nameIndex = it.getColumnIndex(OpenableColumns.DISPLAY_NAME)
            if (nameIndex != -1) {
                name = it.getString(nameIndex)
            }
        }
    }
    return name ?: uri.path?.substringAfterLast('/')
}
