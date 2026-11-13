package com.example.kenyaeduhub.ui.forgotpassword

import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.PasswordVisualTransformation
import androidx.compose.ui.text.input.VisualTransformation
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import kotlinx.coroutines.delay

@Composable
fun ForgotPasswordScreen(
    viewModel: ForgotPasswordViewModel,
    onResetSuccess: (String) -> Unit,
    onNavigateLogin: () -> Unit
) {
    val step by viewModel.step.collectAsState()
    val method by viewModel.method.collectAsState()
    val email by viewModel.email.collectAsState()
    val phone by viewModel.phone.collectAsState()
    val code by viewModel.code.collectAsState()
    val newPassword by viewModel.newPassword.collectAsState()
    val confirmPassword by viewModel.confirmPassword.collectAsState()
    val isLoading by viewModel.isLoading.collectAsState()
    val errorMessage by viewModel.errorMessage.collectAsState()
    val successMessage by viewModel.successMessage.collectAsState()

    val cooldownSeconds by viewModel.cooldownSeconds.collectAsState()
    val canResend = cooldownSeconds <= 0

    var newPasswordVisible by remember { mutableStateOf(false) }
    var confirmPasswordVisible by remember { mutableStateOf(false) }

    // Countdown Timer Loop
    LaunchedEffect(cooldownSeconds) {
        if (cooldownSeconds > 0) {
            delay(1000L)
            viewModel.cooldownSeconds.value -= 1
        }
    }

    Box(
        modifier = Modifier
            .fillMaxSize()
            .background(Color(0xFFF0F4F9))
            .statusBarsPadding()
            .navigationBarsPadding()
            .imePadding()
            .padding(24.dp),
        contentAlignment = Alignment.Center
    ) {
        Column(
            modifier = Modifier
                .fillMaxWidth()
                .widthIn(max = 450.dp)
                .verticalScroll(rememberScrollState())
                .padding(horizontal = 16.dp, vertical = 24.dp),
            horizontalAlignment = Alignment.CenterHorizontally
        ) {
            // Brand Logo & Circle
            Row(
                verticalAlignment = Alignment.CenterVertically,
                horizontalArrangement = Arrangement.Center,
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(bottom = 12.dp)
            ) {
                Box(
                    modifier = Modifier
                        .size(40.dp)
                        .background(Color(0xFFffc107), CircleShape)
                        .border(3.dp, Color(0xFFFF6B35), CircleShape),
                    contentAlignment = Alignment.Center
                ) {
                    Row(
                        verticalAlignment = Alignment.CenterVertically,
                        horizontalArrangement = Arrangement.Center
                    ) {
                        Text(
                            text = "K",
                            color = Color(0xFFFF6B35),
                            fontSize = 24.sp,
                            fontWeight = FontWeight.Bold
                        )
                        Text(
                            text = "E",
                            color = Color(0xFF008000),
                            fontSize = 20.sp,
                            fontWeight = FontWeight.Bold
                        )
                    }
                }
                Spacer(modifier = Modifier.width(8.dp))
                Row(
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    Text(
                        text = "Kenya",
                        fontSize = 20.sp,
                        fontWeight = FontWeight.Bold,
                        color = Color(0xFFFF6B35)
                    )
                    Spacer(modifier = Modifier.width(4.dp))
                    Text(
                        text = "EduHub",
                        fontSize = 20.sp,
                        fontWeight = FontWeight.Bold,
                        color = Color(0xFF008000)
                    )
                }
            }

            Text(
                text = "Forgot Password",
                fontSize = 20.sp,
                fontWeight = FontWeight.Normal,
                color = Color(0xFF202124),
                modifier = Modifier.padding(bottom = 24.dp)
            )

            // Error Message Alert
            errorMessage?.let { error ->
                Box(
                    modifier = Modifier
                        .fillMaxWidth()
                        .clip(RoundedCornerShape(25.dp))
                        .background(Color(0xFFFCE8E6))
                        .border(1.dp, Color(0xFFFCE8E6), RoundedCornerShape(25.dp))
                        .padding(16.dp),
                    contentAlignment = Alignment.Center
                ) {
                    Text(
                        text = error,
                        color = Color(0xFFC5221F),
                        fontSize = 14.sp,
                        fontWeight = FontWeight.Medium,
                        textAlign = TextAlign.Center
                    )
                }
                Spacer(modifier = Modifier.height(16.dp))
            }

            // Success Message Alert
            successMessage?.let { success ->
                Box(
                    modifier = Modifier
                        .fillMaxWidth()
                        .clip(RoundedCornerShape(25.dp))
                        .background(Color(0xFFE6F4EA))
                        .border(1.dp, Color(0xFFE6F4EA), RoundedCornerShape(25.dp))
                        .padding(16.dp),
                    contentAlignment = Alignment.Center
                ) {
                    Text(
                        text = success,
                        color = Color(0xFF137333),
                        fontSize = 14.sp,
                        fontWeight = FontWeight.Medium,
                        textAlign = TextAlign.Center
                    )
                }
                Spacer(modifier = Modifier.height(16.dp))
            }

            when (step) {
                1 -> {
                    // Step 1: Select Method & Enter Email/Phone
                    Text(
                        text = "Choose reset method:",
                        fontSize = 14.sp,
                        fontWeight = FontWeight.Medium,
                        color = Color(0xFF5F6368),
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(bottom = 12.dp)
                    )

                    Row(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(bottom = 20.dp),
                        horizontalArrangement = Arrangement.spacedBy(12.dp)
                    ) {
                        FilterChip(
                            selected = method == "email",
                            onClick = { viewModel.method.value = "email" },
                            label = { Text("Reset via Email") },
                            colors = FilterChipDefaults.filterChipColors(
                                selectedContainerColor = Color(0xFFE8F0FE),
                                selectedLabelColor = Color(0xFFFF6B35)
                            ),
                            modifier = Modifier.weight(1f)
                        )
                        FilterChip(
                            selected = method == "sms",
                            onClick = { viewModel.method.value = "sms" },
                            label = { Text("Reset via SMS") },
                            colors = FilterChipDefaults.filterChipColors(
                                selectedContainerColor = Color(0xFFE8F0FE),
                                selectedLabelColor = Color(0xFFFF6B35)
                            ),
                            modifier = Modifier.weight(1f)
                        )
                    }

                    if (method == "email") {
                        OutlinedTextField(
                            value = email,
                            onValueChange = { viewModel.email.value = it },
                            label = { Text("Email address") },
                            placeholder = { Text("Enter your registered email") },
                            singleLine = true,
                            shape = RoundedCornerShape(25.dp),
                            colors = OutlinedTextFieldDefaults.colors(
                                focusedBorderColor = Color(0xFFFF6B35),
                                unfocusedBorderColor = Color(0xFFDADCE0),
                                focusedContainerColor = Color(0xFFF0F4F9),
                                unfocusedContainerColor = Color(0xFFF0F4F9)
                            ),
                            modifier = Modifier
                                .fillMaxWidth()
                                .padding(bottom = 24.dp)
                        )
                    } else {
                        OutlinedTextField(
                            value = phone,
                            onValueChange = { viewModel.phone.value = it },
                            label = { Text("Phone Number") },
                            placeholder = { Text("e.g. 0712345678 or +254712345678") },
                            singleLine = true,
                            shape = RoundedCornerShape(25.dp),
                            colors = OutlinedTextFieldDefaults.colors(
                                focusedBorderColor = Color(0xFFFF6B35),
                                unfocusedBorderColor = Color(0xFFDADCE0),
                                focusedContainerColor = Color(0xFFF0F4F9),
                                unfocusedContainerColor = Color(0xFFF0F4F9)
                            ),
                            modifier = Modifier
                                .fillMaxWidth()
                                .padding(bottom = 24.dp)
                        )
                    }

                    Button(
                        onClick = { viewModel.submitStep1 { } },
                        enabled = !isLoading,
                        shape = RoundedCornerShape(25.dp),
                        colors = ButtonDefaults.buttonColors(containerColor = Color(0xFFFF6B35)),
                        modifier = Modifier
                            .fillMaxWidth()
                            .height(50.dp)
                    ) {
                        if (isLoading) {
                            CircularProgressIndicator(color = Color.White, modifier = Modifier.size(24.dp))
                        } else {
                            Text(
                                text = "Send Code",
                                fontSize = 16.sp,
                                fontWeight = FontWeight.Bold,
                                color = Color.White
                            )
                        }
                    }
                }

                2 -> {
                    // Step 2: Enter Verification Code
                    val sendTarget = if (method == "email") email else phone
                    Text(
                        text = "A reset code was sent to $sendTarget. Enter the code below:",
                        fontSize = 13.sp,
                        color = Color(0xFF5F6368),
                        textAlign = TextAlign.Center,
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(bottom = 20.dp)
                    )

                    OutlinedTextField(
                        value = code,
                        onValueChange = { viewModel.code.value = it },
                        label = { Text("Reset Code") },
                        placeholder = { Text("Enter 6-digit code") },
                        singleLine = true,
                        shape = RoundedCornerShape(25.dp),
                        colors = OutlinedTextFieldDefaults.colors(
                            focusedBorderColor = Color(0xFFFF6B35),
                            unfocusedBorderColor = Color(0xFFDADCE0),
                            focusedContainerColor = Color(0xFFF0F4F9),
                            unfocusedContainerColor = Color(0xFFF0F4F9)
                        ),
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(bottom = 16.dp)
                    )

                    Button(
                        onClick = { viewModel.submitStep2 { } },
                        enabled = !isLoading,
                        shape = RoundedCornerShape(25.dp),
                        colors = ButtonDefaults.buttonColors(containerColor = Color(0xFFFF6B35)),
                        modifier = Modifier
                            .fillMaxWidth()
                            .height(50.dp)
                    ) {
                        if (isLoading) {
                            CircularProgressIndicator(color = Color.White, modifier = Modifier.size(24.dp))
                        } else {
                            Text(
                                text = "Verify Code",
                                fontSize = 16.sp,
                                fontWeight = FontWeight.Bold,
                                color = Color.White
                            )
                        }
                    }

                    Spacer(modifier = Modifier.height(16.dp))

                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.SpaceBetween,
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        TextButton(
                            onClick = { if (canResend) viewModel.resendCode() },
                            enabled = canResend && !isLoading
                        ) {
                            Text(
                                text = "Resend Code",
                                color = if (canResend) Color(0xFFFF6B35) else Color(0xFF9AA0A6),
                                fontSize = 13.sp,
                                fontWeight = FontWeight.Medium
                            )
                        }
                        TextButton(onClick = { viewModel.goToStep1() }) {
                            Text("Change Details", color = Color(0xFF5F6368), fontSize = 13.sp)
                        }
                    }

                    if (!canResend) {
                        Spacer(modifier = Modifier.height(4.dp))
                        val minutes = cooldownSeconds / 60
                        val seconds = cooldownSeconds % 60
                        val formattedTime = if (minutes > 0) "${minutes} min ${seconds} sec" else "${seconds} sec"
                        Text(
                            text = "Please wait $formattedTime before resending",
                            fontSize = 12.sp,
                            color = Color(0xFF5F6368),
                            textAlign = TextAlign.Center
                        )
                    }
                }

                3 -> {
                    // Step 3: Enter New Password & Confirm
                    Text(
                        text = "Enter your new password below:",
                        fontSize = 13.sp,
                        color = Color(0xFF5F6368),
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(bottom = 20.dp)
                    )

                    OutlinedTextField(
                        value = newPassword,
                        onValueChange = { viewModel.newPassword.value = it },
                        label = { Text("New Password") },
                        placeholder = { Text("New Password") },
                        singleLine = true,
                        visualTransformation = if (newPasswordVisible) VisualTransformation.None else PasswordVisualTransformation(),
                        trailingIcon = {
                            TextButton(onClick = { newPasswordVisible = !newPasswordVisible }) {
                                Text(
                                    text = if (newPasswordVisible) "Hide" else "Show",
                                    color = Color(0xFF5F6368),
                                    fontSize = 12.sp,
                                    fontWeight = FontWeight.Medium
                                )
                            }
                        },
                        shape = RoundedCornerShape(25.dp),
                        colors = OutlinedTextFieldDefaults.colors(
                            focusedBorderColor = Color(0xFFFF6B35),
                            unfocusedBorderColor = Color(0xFFDADCE0),
                            focusedContainerColor = Color(0xFFF0F4F9),
                            unfocusedContainerColor = Color(0xFFF0F4F9)
                        ),
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(bottom = 16.dp)
                    )

                    val passwordsMatch = confirmPassword.isEmpty() || newPassword == confirmPassword
                    OutlinedTextField(
                        value = confirmPassword,
                        onValueChange = { viewModel.confirmPassword.value = it },
                        label = { Text("Confirm New Password") },
                        placeholder = { Text("Confirm New Password") },
                        singleLine = true,
                        visualTransformation = if (confirmPasswordVisible) VisualTransformation.None else PasswordVisualTransformation(),
                        trailingIcon = {
                            TextButton(onClick = { confirmPasswordVisible = !confirmPasswordVisible }) {
                                Text(
                                    text = if (confirmPasswordVisible) "Hide" else "Show",
                                    color = Color(0xFF5F6368),
                                    fontSize = 12.sp,
                                    fontWeight = FontWeight.Medium
                                )
                            }
                        },
                        shape = RoundedCornerShape(25.dp),
                        colors = OutlinedTextFieldDefaults.colors(
                            focusedBorderColor = if (passwordsMatch) Color(0xFFFF6B35) else Color(0xFFC5221F),
                            unfocusedBorderColor = if (passwordsMatch) Color(0xFFDADCE0) else Color(0xFFC5221F),
                            focusedContainerColor = Color(0xFFF0F4F9),
                            unfocusedContainerColor = Color(0xFFF0F4F9)
                        ),
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(bottom = if (!passwordsMatch) 4.dp else 24.dp)
                    )
                    if (!passwordsMatch) {
                        Text(
                            text = "Passwords do not match",
                            color = Color(0xFFC5221F),
                            fontSize = 12.sp,
                            modifier = Modifier
                                .fillMaxWidth()
                                .padding(start = 16.dp, bottom = 24.dp)
                        )
                    }

                    Button(
                        onClick = { viewModel.submitStep3(onResetSuccess) },
                        enabled = !isLoading,
                        shape = RoundedCornerShape(25.dp),
                        colors = ButtonDefaults.buttonColors(containerColor = Color(0xFFFF6B35)),
                        modifier = Modifier
                            .fillMaxWidth()
                            .height(50.dp)
                    ) {
                        if (isLoading) {
                            CircularProgressIndicator(color = Color.White, modifier = Modifier.size(24.dp))
                        } else {
                            Text(
                                text = "Reset Password",
                                fontSize = 16.sp,
                                fontWeight = FontWeight.Bold,
                                color = Color.White
                            )
                        }
                    }
                }
            }

            Spacer(modifier = Modifier.height(24.dp))

            // Back to Login Link
            Row(horizontalArrangement = Arrangement.spacedBy(4.dp)) {
                Text(text = "Remembered your password?", fontSize = 13.sp, color = Color(0xFF5F6368))
                Text(
                    text = "Login",
                    fontSize = 13.sp,
                    fontWeight = FontWeight.Medium,
                    color = Color(0xFFFF6B35),
                    modifier = Modifier.clickable { onNavigateLogin() }
                )
            }
        }
    }
}
