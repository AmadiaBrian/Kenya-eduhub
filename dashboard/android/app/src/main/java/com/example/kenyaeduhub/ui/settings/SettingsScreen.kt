package com.example.kenyaeduhub.ui.settings

import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Delete
import androidx.compose.material.icons.filled.Save
import androidx.compose.material.icons.filled.Warning
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.example.kenyaeduhub.data.User
import com.example.kenyaeduhub.ui.dashboard.DashboardViewModel
import com.example.kenyaeduhub.ui.theme.ThemeState
import com.example.kenyaeduhub.ui.theme.getAppThemeColors

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun SettingsScreen(
    user: User,
    viewModel: DashboardViewModel,
    onLogout: () -> Unit,
    cardBgColor: Color = Color(0xFFF0F4F9),
    borderColor: Color = Color(0xFFE8EAED)
) {
    val dashboardData by viewModel.dashboardData.collectAsState()
    val themeColors = getAppThemeColors()
    val activeCardBg = themeColors.cardBgColor
    val activeBorder = if (themeColors.isDark) null else BorderStroke(1.dp, themeColors.borderColor)

    var emailUploads by remember { mutableStateOf(true) }
    var emailComments by remember { mutableStateOf(true) }
    var emailUpdates by remember { mutableStateOf(false) }
    var showProfile by remember { mutableStateOf(true) }
    var showEmail by remember { mutableStateOf(false) }

    var selectedTheme by remember { mutableStateOf(ThemeState.themePreference) }
    var selectedLanguage by remember { mutableStateOf("English") }
    var themeExpanded by remember { mutableStateOf(false) }
    var langExpanded by remember { mutableStateOf(false) }

    val themeOptions = listOf("Light Mode", "Dark Mode", "System Default")
    val langOptions = listOf("English", "Kiswahili")

    var settingsMessage by remember { mutableStateOf<String?>(null) }

    // Delete Account Modal State
    var showDeleteDialog by remember { mutableStateOf(false) }
    var confirmDeleteEmail by remember { mutableStateOf("") }
    var deletionReason by remember { mutableStateOf("") }
    var deleteAgreementChecked by remember { mutableStateOf(false) }
    var deleteErrorMsg by remember { mutableStateOf<String?>(null) }

    LazyColumn(
        modifier = Modifier
            .fillMaxSize()
            .padding(horizontal = 16.dp),
        contentPadding = PaddingValues(vertical = 16.dp),
        verticalArrangement = Arrangement.spacedBy(16.dp)
    ) {
        // 1. Account Settings Card (Email Notifications & Privacy)
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
                        text = "Account Settings",
                        fontSize = 20.sp,
                        fontWeight = FontWeight.Bold,
                        color = themeColors.primaryText
                    )

                    HorizontalDivider(color = themeColors.borderColor)

                    Text("Email Notifications", fontSize = 16.sp, fontWeight = FontWeight.Bold, color = themeColors.primaryText)
                    Text("Choose which email notifications you want to receive.", fontSize = 12.sp, color = themeColors.secondaryText)

                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.SpaceBetween,
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Text("Notify me when my resources are downloaded", fontSize = 13.sp, color = themeColors.primaryText, modifier = Modifier.weight(1f))
                        Switch(checked = emailUploads, onCheckedChange = { emailUploads = it })
                    }

                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.SpaceBetween,
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Text("Notify me of new comments on my resources", fontSize = 13.sp, color = themeColors.primaryText, modifier = Modifier.weight(1f))
                        Switch(checked = emailComments, onCheckedChange = { emailComments = it })
                    }

                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.SpaceBetween,
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Text("Notify me about platform updates and new features", fontSize = 13.sp, color = themeColors.primaryText, modifier = Modifier.weight(1f))
                        Switch(checked = emailUpdates, onCheckedChange = { emailUpdates = it })
                    }

                    HorizontalDivider(color = themeColors.borderColor, modifier = Modifier.padding(vertical = 4.dp))

                    Text("Privacy Settings", fontSize = 16.sp, fontWeight = FontWeight.Bold, color = themeColors.primaryText)
                    Text("Control your privacy preferences.", fontSize = 12.sp, color = themeColors.secondaryText)

                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.SpaceBetween,
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Text("Make my profile visible to other users", fontSize = 13.sp, color = themeColors.primaryText, modifier = Modifier.weight(1f))
                        Switch(checked = showProfile, onCheckedChange = { showProfile = it })
                    }

                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.SpaceBetween,
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Text("Show my email address on my profile", fontSize = 13.sp, color = themeColors.primaryText, modifier = Modifier.weight(1f))
                        Switch(checked = showEmail, onCheckedChange = { showEmail = it })
                    }
                }
            }
        }

        // 2. Display Settings Card (Theme & Language)
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
                        text = "Display Settings",
                        fontSize = 20.sp,
                        fontWeight = FontWeight.Bold,
                        color = themeColors.primaryText
                    )

                    HorizontalDivider(color = themeColors.borderColor)

                    Text("Theme Preference", fontSize = 14.sp, fontWeight = FontWeight.Medium, color = themeColors.primaryText)
                    ExposedDropdownMenuBox(
                        expanded = themeExpanded,
                        onExpandedChange = { themeExpanded = !themeExpanded },
                        modifier = Modifier.fillMaxWidth()
                    ) {
                        OutlinedTextField(
                            value = selectedTheme,
                            onValueChange = {},
                            readOnly = true,
                            trailingIcon = { ExposedDropdownMenuDefaults.TrailingIcon(expanded = themeExpanded) },
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
                            expanded = themeExpanded,
                            onDismissRequest = { themeExpanded = false }
                        ) {
                            themeOptions.forEach { option ->
                                DropdownMenuItem(
                                    text = { Text(option) },
                                    onClick = {
                                        selectedTheme = option
                                        ThemeState.themePreference = option
                                        themeExpanded = false
                                    }
                                )
                            }
                        }
                    }

                    Spacer(modifier = Modifier.height(4.dp))

                    Text("Language Preference", fontSize = 14.sp, fontWeight = FontWeight.Medium, color = themeColors.primaryText)
                    ExposedDropdownMenuBox(
                        expanded = langExpanded,
                        onExpandedChange = { langExpanded = !langExpanded },
                        modifier = Modifier.fillMaxWidth()
                    ) {
                        OutlinedTextField(
                            value = selectedLanguage,
                            onValueChange = {},
                            readOnly = true,
                            trailingIcon = { ExposedDropdownMenuDefaults.TrailingIcon(expanded = langExpanded) },
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
                            expanded = langExpanded,
                            onDismissRequest = { langExpanded = false }
                        ) {
                            langOptions.forEach { option ->
                                DropdownMenuItem(
                                    text = { Text(option) },
                                    onClick = {
                                        selectedLanguage = option
                                        langExpanded = false
                                    }
                                )
                            }
                        }
                    }
                }
            }
        }

        // 3. Danger Zone Card (Delete Account)
        item {
            Card(
                modifier = Modifier.fillMaxWidth(),
                shape = RoundedCornerShape(16.dp),
                colors = CardDefaults.cardColors(containerColor = activeCardBg),
                border = if (themeColors.isDark) null else BorderStroke(1.dp, Color(0xFFFCE8E6))
            ) {
                Column(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(20.dp),
                    verticalArrangement = Arrangement.spacedBy(12.dp)
                ) {
                    Text(
                        text = "Danger Zone",
                        fontSize = 20.sp,
                        fontWeight = FontWeight.Bold,
                        color = Color(0xFFC5221F)
                    )

                    HorizontalDivider(color = Color(0xFFFCE8E6))

                    Text(
                        text = "Delete Account",
                        fontSize = 16.sp,
                        fontWeight = FontWeight.Bold,
                        color = Color(0xFFC5221F)
                    )

                    Text(
                        text = "Once you delete your account, all your data will be archived for 30 days before permanent deletion. Please be certain.",
                        fontSize = 13.sp,
                        color = themeColors.secondaryText
                    )

                    Spacer(modifier = Modifier.height(4.dp))

                    OutlinedButton(
                        onClick = {
                            confirmDeleteEmail = ""
                            deletionReason = ""
                            deleteAgreementChecked = false
                            deleteErrorMsg = null
                            showDeleteDialog = true
                        },
                        shape = RoundedCornerShape(25.dp),
                        colors = ButtonDefaults.outlinedButtonColors(
                            containerColor = if (themeColors.isDark) Color(0xFF2A1515) else Color(0xFFFCE8E6),
                            contentColor = Color(0xFFC5221F)
                        ),
                        border = BorderStroke(1.dp, Color(0xFFC5221F)),
                        modifier = Modifier
                            .fillMaxWidth()
                            .height(48.dp)
                    ) {
                        Icon(Icons.Default.Delete, contentDescription = "Delete Account", tint = Color(0xFFC5221F), modifier = Modifier.size(18.dp))
                        Spacer(modifier = Modifier.width(8.dp))
                        Text("Delete My Account", fontSize = 14.sp, fontWeight = FontWeight.Bold, color = Color(0xFFC5221F))
                    }
                }
            }
        }

        // 4. Save Settings Button
        item {
            Column(verticalArrangement = Arrangement.spacedBy(12.dp)) {
                settingsMessage?.let { msg ->
                    Box(
                        modifier = Modifier
                            .fillMaxWidth()
                            .clip(RoundedCornerShape(20.dp))
                            .background(Color(0xFFE6F4EA))
                            .padding(12.dp),
                        contentAlignment = Alignment.Center
                    ) {
                        Text(
                            text = msg,
                            color = Color(0xFF137333),
                            fontSize = 13.sp,
                            fontWeight = FontWeight.Medium,
                            textAlign = TextAlign.Center
                        )
                    }
                }

                Button(
                    onClick = {
                        viewModel.saveSettings(
                            userId = user.id,
                            emailUploads = emailUploads,
                            emailComments = emailComments,
                            emailUpdates = emailUpdates,
                            showProfile = showProfile,
                            showEmail = showEmail,
                            theme = selectedTheme,
                            language = selectedLanguage
                        ) { _, msg ->
                            settingsMessage = msg
                        }
                    },
                    shape = RoundedCornerShape(25.dp),
                    colors = ButtonDefaults.buttonColors(
                        containerColor = Color(0xFFFF6B35),
                        contentColor = Color.White,
                        disabledContainerColor = Color(0xFFFF6B35).copy(alpha = 0.7f),
                        disabledContentColor = Color.White
                    ),
                    modifier = Modifier
                        .fillMaxWidth()
                        .height(48.dp)
                ) {
                    Icon(Icons.Default.Save, contentDescription = "Save", tint = Color.White, modifier = Modifier.size(18.dp))
                    Spacer(modifier = Modifier.width(8.dp))
                    Text("Save Settings", fontSize = 14.sp, fontWeight = FontWeight.Bold, color = Color.White)
                }
            }
        }
    }

    // Delete Account Dialog
    if (showDeleteDialog) {
        AlertDialog(
            onDismissRequest = { showDeleteDialog = false },
            containerColor = activeCardBg,
            title = {
                Row(verticalAlignment = Alignment.CenterVertically) {
                    Icon(Icons.Default.Warning, contentDescription = "Warning", tint = Color(0xFFF9AB00), modifier = Modifier.size(24.dp))
                    Spacer(modifier = Modifier.width(8.dp))
                    Text("Delete Account", fontWeight = FontWeight.Bold, fontSize = 20.sp, color = themeColors.primaryText)
                }
            },
            text = {
                Column(verticalArrangement = Arrangement.spacedBy(12.dp)) {
                    Box(
                        modifier = Modifier
                            .fillMaxWidth()
                            .clip(RoundedCornerShape(8.dp))
                            .background(Color(0xFFFCE8E6))
                            .padding(12.dp)
                    ) {
                        Text(
                            text = "Warning: This action cannot be undone. Your account and all associated data will be deleted.",
                            fontSize = 12.sp,
                            color = Color(0xFFC5221F),
                            fontWeight = FontWeight.Medium
                        )
                    }

                    OutlinedTextField(
                        value = confirmDeleteEmail,
                        onValueChange = { confirmDeleteEmail = it },
                        label = { Text("Confirm your email address") },
                        placeholder = { Text(dashboardData?.user?.email ?: user.email) },
                        singleLine = true,
                        shape = RoundedCornerShape(25.dp),
                        colors = OutlinedTextFieldDefaults.colors(
                            focusedBorderColor = Color(0xFFC5221F),
                            unfocusedBorderColor = themeColors.formBorderColor,
                            focusedTextColor = themeColors.primaryText,
                            unfocusedTextColor = themeColors.primaryText
                        ),
                        modifier = Modifier.fillMaxWidth()
                    )

                    OutlinedTextField(
                        value = deletionReason,
                        onValueChange = { deletionReason = it },
                        label = { Text("Reason for deletion (optional)") },
                        placeholder = { Text("Why are you leaving?") },
                        maxLines = 3,
                        shape = RoundedCornerShape(20.dp),
                        colors = OutlinedTextFieldDefaults.colors(
                            focusedBorderColor = Color(0xFFFF6B35),
                            unfocusedBorderColor = themeColors.formBorderColor,
                            focusedTextColor = themeColors.primaryText,
                            unfocusedTextColor = themeColors.primaryText
                        ),
                        modifier = Modifier.fillMaxWidth()
                    )

                    Row(
                        verticalAlignment = Alignment.CenterVertically,
                        modifier = Modifier.fillMaxWidth()
                    ) {
                        Checkbox(
                            checked = deleteAgreementChecked,
                            onCheckedChange = { deleteAgreementChecked = it }
                        )
                        Text(
                            text = "I understand that my account will be deleted and this action cannot be undone",
                            fontSize = 11.sp,
                            color = themeColors.primaryText
                        )
                    }

                    deleteErrorMsg?.let { err ->
                        Text(text = err, color = Color(0xFFC5221F), fontSize = 12.sp, fontWeight = FontWeight.Bold)
                    }
                }
            },
            confirmButton = {
                Button(
                    onClick = {
                        val targetEmail = dashboardData?.user?.email ?: user.email
                        if (!deleteAgreementChecked) {
                            deleteErrorMsg = "Please confirm that you understand this action cannot be undone."
                        } else if (!confirmDeleteEmail.equals(targetEmail, ignoreCase = true)) {
                            deleteErrorMsg = "Email confirmation does not match. Please enter $targetEmail."
                        } else {
                            viewModel.deleteAccount(user.id, confirmDeleteEmail, deletionReason) { success, msg ->
                                if (success) {
                                    showDeleteDialog = false
                                    onLogout()
                                } else {
                                    deleteErrorMsg = msg
                                }
                            }
                        }
                    },
                    colors = ButtonDefaults.buttonColors(containerColor = Color(0xFFC5221F)),
                    shape = RoundedCornerShape(25.dp)
                ) {
                    Text("Delete Account", color = Color.White, fontWeight = FontWeight.Bold)
                }
            },
            dismissButton = {
                OutlinedButton(
                    onClick = { showDeleteDialog = false },
                    shape = RoundedCornerShape(25.dp)
                ) {
                    Text("Cancel", color = themeColors.primaryText)
                }
            }
        )
    }
}
