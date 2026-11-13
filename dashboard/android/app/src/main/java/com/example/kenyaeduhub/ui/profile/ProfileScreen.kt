package com.example.kenyaeduhub.ui.profile

import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Key
import androidx.compose.material.icons.filled.Save
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.PasswordVisualTransformation
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.example.kenyaeduhub.data.User
import com.example.kenyaeduhub.ui.dashboard.DashboardViewModel
import com.example.kenyaeduhub.ui.theme.getAppThemeColors

@Composable
fun ProfileScreen(
    user: User,
    viewModel: DashboardViewModel,
    cardBgColor: Color = Color(0xFFF0F4F9),
    borderColor: Color = Color(0xFFE8EAED)
) {
    val dashboardData by viewModel.dashboardData.collectAsState()
    val activeUser = dashboardData?.user ?: user

    val themeColors = getAppThemeColors()
    val activeCardBg = themeColors.cardBgColor
    val activeBorder = if (themeColors.isDark) null else BorderStroke(1.dp, themeColors.borderColor)

    var profileName by remember(activeUser) { mutableStateOf(activeUser.name) }
    var profileEmail by remember(activeUser) { mutableStateOf(activeUser.email) }
    var profilePhone by remember(activeUser) { mutableStateOf(activeUser.phone) }
    var profileMessage by remember { mutableStateOf<String?>(null) }
    var isProfileSuccess by remember { mutableStateOf(false) }

    var currentPassword by remember { mutableStateOf("") }
    var newPassword by remember { mutableStateOf("") }
    var confirmNewPassword by remember { mutableStateOf("") }
    var passwordMessage by remember { mutableStateOf<String?>(null) }
    var isPasswordSuccess by remember { mutableStateOf(false) }

    LazyColumn(
        modifier = Modifier
            .fillMaxSize()
            .padding(horizontal = 16.dp),
        contentPadding = PaddingValues(vertical = 16.dp),
        verticalArrangement = Arrangement.spacedBy(16.dp)
    ) {
        // 1. Profile Header with Large Circular Avatar
        item {
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(vertical = 12.dp),
                verticalAlignment = Alignment.CenterVertically
            ) {
                Box(
                    modifier = Modifier
                        .size(80.dp)
                        .clip(CircleShape)
                        .background(Color(0xFFFF6B35)),
                    contentAlignment = Alignment.Center
                ) {
                    Text(
                        text = activeUser.name.take(1).uppercase(),
                        color = Color.White,
                        fontSize = 36.sp,
                        fontWeight = FontWeight.Bold
                    )
                }

                Spacer(modifier = Modifier.width(16.dp))

                Column {
                    Text(
                        text = activeUser.name,
                        fontSize = 22.sp,
                        fontWeight = FontWeight.Bold,
                        color = themeColors.primaryText
                    )
                    Spacer(modifier = Modifier.height(4.dp))
                    Text(
                        text = activeUser.email,
                        fontSize = 14.sp,
                        color = themeColors.secondaryText
                    )
                }
            }
        }

        // 2. Update Profile Form Card
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
                        text = "Update Profile",
                        fontSize = 18.sp,
                        fontWeight = FontWeight.Bold,
                        color = themeColors.primaryText
                    )

                    HorizontalDivider(color = themeColors.borderColor)

                    OutlinedTextField(
                        value = profileName,
                        onValueChange = { profileName = it },
                        label = { Text("Full Name *") },
                        placeholder = { Text("Enter your full name") },
                        singleLine = true,
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

                    OutlinedTextField(
                        value = profileEmail,
                        onValueChange = { profileEmail = it },
                        label = { Text("Email Address *") },
                        placeholder = { Text("Enter your email address") },
                        singleLine = true,
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

                    Column {
                        OutlinedTextField(
                            value = profilePhone,
                            onValueChange = { profilePhone = it },
                            label = { Text("Phone Number (Optional)") },
                            placeholder = { Text("07XXXXXXXX") },
                            singleLine = true,
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
                        Spacer(modifier = Modifier.height(4.dp))
                        Text(
                            text = "Kenya format: 07XXXXXXXX, 01XXXXXXXX, +2547XXXXXXXX, or +2541XXXXXXXX (for SMS verification)",
                            fontSize = 11.sp,
                            color = themeColors.secondaryText
                        )
                    }

                    profileMessage?.let { msg ->
                        Box(
                            modifier = Modifier
                                .fillMaxWidth()
                                .clip(RoundedCornerShape(20.dp))
                                .background(if (isProfileSuccess) Color(0xFFE6F4EA) else Color(0xFFFCE8E6))
                                .padding(12.dp),
                            contentAlignment = Alignment.Center
                        ) {
                            Text(
                                text = msg,
                                color = if (isProfileSuccess) Color(0xFF137333) else Color(0xFFC5221F),
                                fontSize = 13.sp,
                                fontWeight = FontWeight.Medium,
                                textAlign = TextAlign.Center
                            )
                        }
                    }

                    Button(
                        onClick = {
                            if (profileName.isBlank() || profileEmail.isBlank()) {
                                profileMessage = "Name and Email are required"
                                isProfileSuccess = false
                            } else {
                                viewModel.updateProfile(user.id, profileName, profileEmail, profilePhone) { success, msg ->
                                    profileMessage = msg
                                    isProfileSuccess = success
                                }
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
                        Text("Update Profile", fontSize = 14.sp, fontWeight = FontWeight.Bold, color = Color.White)
                    }
                }
            }
        }

        // 3. Change Password Form Card
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
                        text = "Change Password",
                        fontSize = 18.sp,
                        fontWeight = FontWeight.Bold,
                        color = themeColors.primaryText
                    )

                    HorizontalDivider(color = themeColors.borderColor)

                    OutlinedTextField(
                        value = currentPassword,
                        onValueChange = { currentPassword = it },
                        label = { Text("Current Password *") },
                        placeholder = { Text("Enter your current password") },
                        singleLine = true,
                        visualTransformation = PasswordVisualTransformation(),
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

                    OutlinedTextField(
                        value = newPassword,
                        onValueChange = { newPassword = it },
                        label = { Text("New Password *") },
                        placeholder = { Text("Enter your new password") },
                        singleLine = true,
                        visualTransformation = PasswordVisualTransformation(),
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

                    OutlinedTextField(
                        value = confirmNewPassword,
                        onValueChange = { confirmNewPassword = it },
                        label = { Text("Confirm New Password *") },
                        placeholder = { Text("Confirm your new password") },
                        singleLine = true,
                        visualTransformation = PasswordVisualTransformation(),
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

                    passwordMessage?.let { msg ->
                        Box(
                            modifier = Modifier
                                .fillMaxWidth()
                                .clip(RoundedCornerShape(20.dp))
                                .background(if (isPasswordSuccess) Color(0xFFE6F4EA) else Color(0xFFFCE8E6))
                                .padding(12.dp),
                            contentAlignment = Alignment.Center
                        ) {
                            Text(
                                text = msg,
                                color = if (isPasswordSuccess) Color(0xFF137333) else Color(0xFFC5221F),
                                fontSize = 13.sp,
                                fontWeight = FontWeight.Medium,
                                textAlign = TextAlign.Center
                            )
                        }
                    }

                    Button(
                        onClick = {
                            if (currentPassword.isBlank() || newPassword.isBlank()) {
                                passwordMessage = "Please fill in all password fields"
                                isPasswordSuccess = false
                            } else if (newPassword != confirmNewPassword) {
                                passwordMessage = "Passwords do not match"
                                isPasswordSuccess = false
                            } else if (newPassword.length < 6) {
                                passwordMessage = "New password must be at least 6 characters"
                                isPasswordSuccess = false
                            } else {
                                viewModel.changePassword(user.id, currentPassword, newPassword, confirmNewPassword) { success, msg ->
                                    passwordMessage = msg
                                    isPasswordSuccess = success
                                    if (success) {
                                        currentPassword = ""
                                        newPassword = ""
                                        confirmNewPassword = ""
                                    }
                                }
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
                        Icon(Icons.Default.Key, contentDescription = "Key", tint = Color.White, modifier = Modifier.size(18.dp))
                        Spacer(modifier = Modifier.width(8.dp))
                        Text("Change Password", fontSize = 14.sp, fontWeight = FontWeight.Bold, color = Color.White)
                    }
                }
            }
        }
    }
}
