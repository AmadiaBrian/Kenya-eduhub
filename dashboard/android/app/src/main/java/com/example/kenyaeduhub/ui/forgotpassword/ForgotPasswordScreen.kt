package com.example.kenyaeduhub.ui.forgotpassword

import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
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

    var newPasswordVisible by remember { mutableStateOf(false) }
    var confirmPasswordVisible by remember { mutableStateOf(false) }

    Box(
        modifier = Modifier
            .fillMaxSize()
            .background(Color(0xFFF0F4F9))
            .statusBarsPadding()
            .navigationBarsPadding()
            .padding(24.dp),
        contentAlignment = Alignment.Center
    ) {
        Column(
            modifier = Modifier
                .fillMaxWidth()
                .widthIn(max = 450.dp)
                .padding(horizontal = 16.dp, vertical = 24.dp),
            horizontalAlignment = Alignment.CenterHorizontally
        ) {
            // Brand Logo & Circle (kenya-eduhub-logo-unique-2026)
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
                text = "Reset Password",
                fontSize = 20.sp,
                fontWeight = FontWeight.Normal,
                color = Color(0xFF202124),
                modifier = Modifier.padding(bottom = 8.dp)
            )

            Text(
                text = when (step) {
                    1 -> "Enter your email or phone to receive a reset code."
                    2 -> "Enter the 6-digit verification code sent to you."
                    else -> "Enter your new password below."
                },
                fontSize = 14.sp,
                color = Color(0xFF5F6368),
                textAlign = TextAlign.Center,
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
                    if (method == "email") {
                        OutlinedTextField(
                            value = email,
                            onValueChange = { viewModel.email.value = it },
                            label = { Text("Email address") },
                            placeholder = { Text("Enter your email address") },
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
                                .padding(bottom = 8.dp)
                        )
                        Row(
                            modifier = Modifier
                                .fillMaxWidth()
                                .padding(horizontal = 8.dp)
                                .padding(bottom = 24.dp),
                            horizontalArrangement = Arrangement.End
                        ) {
                            Text(
                                text = "Send code via SMS instead",
                                color = Color(0xFF1A73E8),
                                fontSize = 13.sp,
                                modifier = Modifier.clickable { viewModel.method.value = "sms" }
                            )
                        }
                    } else {
                        OutlinedTextField(
                            value = phone,
                            onValueChange = { viewModel.phone.value = it },
                            label = { Text("Phone Number") },
                            placeholder = { Text("Enter your phone number") },
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
                                .padding(bottom = 4.dp)
                        )
                        Row(
                            modifier = Modifier
                                .fillMaxWidth()
                                .padding(horizontal = 8.dp)
                                .padding(bottom = 16.dp),
                            horizontalArrangement = Arrangement.SpaceBetween,
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            Text(
                                text = "Kenya format: 07XXXXXXXX",
                                color = Color(0xFF5F6368),
                                fontSize = 11.sp
                            )
                            Text(
                                text = "Send code via email instead",
                                color = Color(0xFF1A73E8),
                                fontSize = 13.sp,
                                modifier = Modifier.clickable { viewModel.method.value = "email" }
                            )
                        }
                    }

                    Button(
                        onClick = { viewModel.submitStep1 {} },
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
                    OutlinedTextField(
                        value = code,
                        onValueChange = { viewModel.code.value = it },
                        label = { Text("Verification Code") },
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
                            .padding(bottom = 24.dp)
                    )

                    Button(
                        onClick = { viewModel.submitStep2 {} },
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

                    Row(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(top = 16.dp)
                            .padding(horizontal = 8.dp),
                        horizontalArrangement = Arrangement.SpaceBetween,
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Text(
                            text = "Didn't receive code? Resend",
                            color = Color(0xFF1A73E8),
                            fontSize = 13.sp,
                            fontWeight = FontWeight.Medium,
                            modifier = Modifier.clickable(enabled = !isLoading) { viewModel.resendCode() }
                        )
                        Text(
                            text = "Change details",
                            color = Color(0xFF5F6368),
                            fontSize = 13.sp,
                            modifier = Modifier.clickable(enabled = !isLoading) { viewModel.goToStep1() }
                        )
                    }
                }

                else -> {
                    OutlinedTextField(
                        value = newPassword,
                        onValueChange = { viewModel.newPassword.value = it },
                        label = { Text("New Password") },
                        placeholder = { Text("Enter new password") },
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
                        label = { Text("Confirm Password") },
                        placeholder = { Text("Confirm new password") },
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

            // Link to Login
            Row(horizontalArrangement = Arrangement.spacedBy(4.dp)) {
                Text(
                    text = "Back to Login",
                    fontSize = 13.sp,
                    fontWeight = FontWeight.Medium,
                    color = Color(0xFFFF6B35),
                    modifier = Modifier.clickable { onNavigateLogin() }
                )
            }
        }
    }
}
