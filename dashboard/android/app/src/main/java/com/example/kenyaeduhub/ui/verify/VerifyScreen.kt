package com.example.kenyaeduhub.ui.verify

import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.CheckCircle
import androidx.compose.material.icons.filled.Mail
import androidx.compose.material.icons.filled.Smartphone
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
import com.example.kenyaeduhub.data.AuthRepository
import com.example.kenyaeduhub.data.ResendVerificationResult
import com.example.kenyaeduhub.data.VerifyResult
import kotlinx.coroutines.delay
import kotlinx.coroutines.launch

@Composable
fun VerifyScreen(
    email: String,
    phone: String,
    onVerifySuccess: (String) -> Unit,
    onNavigateLogin: () -> Unit,
    repository: AuthRepository = AuthRepository()
) {
    var code by remember { mutableStateOf("") }
    var isLoading by remember { mutableStateOf(false) }
    var errorMessage by remember { mutableStateOf<String?>(null) }
    var successMessage by remember { mutableStateOf<String?>(null) }

    // Resend SMS Cooldown State (2 mins for 2nd resend, 6 mins for 3rd+ resend)
    var resendAttemptCount by remember { mutableIntStateOf(0) }
    var cooldownSeconds by remember { mutableIntStateOf(0) }

    val coroutineScope = rememberCoroutineScope()
    val activeIdentifier = email.ifBlank { phone }

    // Countdown Timer Loop
    LaunchedEffect(cooldownSeconds) {
        if (cooldownSeconds > 0) {
            delay(1000L)
            cooldownSeconds -= 1
        }
    }

    val canResend = cooldownSeconds <= 0

    // Function to submit verification code
    fun doVerify() {
        if (code.isBlank()) {
            errorMessage = "Please enter the verification code"
        } else if (code.length != 6) {
            errorMessage = "Verification code must be 6 digits"
        } else {
            isLoading = true
            errorMessage = null
            coroutineScope.launch {
                when (val result = repository.verifyAccount(activeIdentifier, code.trim())) {
                    is VerifyResult.Success -> {
                        isLoading = false
                        successMessage = result.message
                        onVerifySuccess(result.message)
                    }
                    is VerifyResult.Error -> {
                        isLoading = false
                        errorMessage = result.message
                    }
                }
            }
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
                        Text("K", color = Color(0xFFFF6B35), fontSize = 22.sp, fontWeight = FontWeight.Bold)
                        Text("E", color = Color(0xFF008000), fontSize = 18.sp, fontWeight = FontWeight.Bold)
                    }
                }
                Spacer(modifier = Modifier.width(8.dp))
                Row(verticalAlignment = Alignment.CenterVertically) {
                    Text("Kenya", fontSize = 18.sp, fontWeight = FontWeight.Bold, color = Color(0xFFFF6B35))
                    Spacer(modifier = Modifier.width(4.dp))
                    Text("EduHub", fontSize = 18.sp, fontWeight = FontWeight.Bold, color = Color(0xFF008000))
                }
            }

            Text(
                text = "Verify Account",
                fontSize = 20.sp,
                fontWeight = FontWeight.Normal,
                color = Color(0xFF202124),
                modifier = Modifier.padding(bottom = 16.dp)
            )

            // User Welcome Details Card
            Card(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(bottom = 20.dp),
                shape = RoundedCornerShape(12.dp),
                colors = CardDefaults.cardColors(containerColor = Color(0xFFF0F4F9)),
                border = BorderStroke(1.dp, Color(0xFFE8EAED))
            ) {
                Column(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(16.dp),
                    horizontalAlignment = Alignment.CenterHorizontally
                ) {
                    Text(
                        text = "Welcome!",
                        fontSize = 18.sp,
                        fontWeight = FontWeight.Bold,
                        color = Color(0xFF202124)
                    )
                    Spacer(modifier = Modifier.height(10.dp))
                    if (email.isNotBlank()) {
                        Row(verticalAlignment = Alignment.CenterVertically) {
                            Icon(Icons.Default.Mail, contentDescription = "Email", tint = Color(0xFFFF6B35), modifier = Modifier.size(16.dp))
                            Spacer(modifier = Modifier.width(6.dp))
                            Text(email, fontSize = 13.sp, color = Color(0xFF5F6368), fontWeight = FontWeight.Medium)
                        }
                    }
                    if (phone.isNotBlank()) {
                        Spacer(modifier = Modifier.height(6.dp))
                        Row(verticalAlignment = Alignment.CenterVertically) {
                            Icon(Icons.Default.Smartphone, contentDescription = "Phone", tint = Color(0xFFFF6B35), modifier = Modifier.size(16.dp))
                            Spacer(modifier = Modifier.width(6.dp))
                            Text(phone, fontSize = 13.sp, color = Color(0xFF5F6368), fontWeight = FontWeight.Medium)
                        }
                    }
                    Spacer(modifier = Modifier.height(8.dp))
                    Text("Please verify your account to continue", fontSize = 12.sp, color = Color(0xFF5F6368))
                }
            }

            // Error Banner
            errorMessage?.let { err ->
                Box(
                    modifier = Modifier
                        .fillMaxWidth()
                        .clip(RoundedCornerShape(25.dp))
                        .background(Color(0xFFFCE8E6))
                        .border(1.dp, Color(0xFFFCE8E6), RoundedCornerShape(25.dp))
                        .padding(14.dp),
                    contentAlignment = Alignment.Center
                ) {
                    Text(text = err, color = Color(0xFFC5221F), fontSize = 13.sp, fontWeight = FontWeight.Medium, textAlign = TextAlign.Center)
                }
                Spacer(modifier = Modifier.height(16.dp))
            }

            // Success Banner
            successMessage?.let { msg ->
                Box(
                    modifier = Modifier
                        .fillMaxWidth()
                        .clip(RoundedCornerShape(25.dp))
                        .background(Color(0xFFE6F4EA))
                        .border(1.dp, Color(0xFFE6F4EA), RoundedCornerShape(25.dp))
                        .padding(14.dp),
                    contentAlignment = Alignment.Center
                ) {
                    Text(text = msg, color = Color(0xFF137333), fontSize = 13.sp, fontWeight = FontWeight.Medium, textAlign = TextAlign.Center)
                }
                Spacer(modifier = Modifier.height(16.dp))
            }

            // Verification Code Input Field
            OutlinedTextField(
                value = code,
                onValueChange = { input ->
                    val filtered = input.filter { it.isDigit() }
                    if (filtered.length <= 6) {
                        code = filtered
                        if (filtered.length == 6) {
                            doVerify()
                        }
                    }
                },
                label = { Text("Enter 6-digit verification code") },
                placeholder = { Text("000000") },
                singleLine = true,
                enabled = !isLoading,
                shape = RoundedCornerShape(25.dp),
                colors = OutlinedTextFieldDefaults.colors(
                    focusedBorderColor = Color(0xFFFF6B35),
                    unfocusedBorderColor = Color(0xFFDADCE0),
                    focusedContainerColor = Color(0xFFF0F4F9),
                    unfocusedContainerColor = Color(0xFFF0F4F9)
                ),
                modifier = Modifier.fillMaxWidth()
            )

            Spacer(modifier = Modifier.height(20.dp))

            // Verify Account Button
            Button(
                onClick = { doVerify() },
                enabled = !isLoading,
                shape = RoundedCornerShape(25.dp),
                colors = ButtonDefaults.buttonColors(containerColor = Color(0xFFFF6B35)),
                modifier = Modifier
                    .fillMaxWidth()
                    .height(48.dp)
            ) {
                if (isLoading) {
                    CircularProgressIndicator(color = Color.White, modifier = Modifier.size(20.dp), strokeWidth = 2.dp)
                    Spacer(modifier = Modifier.width(8.dp))
                    Text("Verifying...", fontSize = 14.sp, fontWeight = FontWeight.Bold, color = Color.White)
                } else {
                    Icon(Icons.Default.CheckCircle, contentDescription = "Verify", tint = Color.White, modifier = Modifier.size(18.dp))
                    Spacer(modifier = Modifier.width(8.dp))
                    Text("Verify Account", fontSize = 14.sp, fontWeight = FontWeight.Bold, color = Color.White)
                }
            }

            Spacer(modifier = Modifier.height(20.dp))

            // Resend Code Section via SMS / Email with Cooldown Timer
            Column(horizontalAlignment = Alignment.CenterHorizontally) {
                Text(text = "Didn't receive the code?", fontSize = 13.sp, color = Color(0xFF5F6368))
                Spacer(modifier = Modifier.height(4.dp))

                if (phone.isNotBlank()) {
                    Text(
                        text = "Resend via SMS",
                        fontSize = 14.sp,
                        fontWeight = FontWeight.Bold,
                        color = if (canResend && !isLoading) Color(0xFFFF6B35) else Color(0xFF9AA0A6),
                        modifier = Modifier.clickable(enabled = canResend && !isLoading) {
                            coroutineScope.launch {
                                isLoading = true
                                errorMessage = null
                                successMessage = null
                                when (val result = repository.resendVerificationCode(email, phone, "sms")) {
                                    is ResendVerificationResult.Success -> {
                                        isLoading = false
                                        successMessage = result.message
                                        resendAttemptCount += 1
                                        cooldownSeconds = when (resendAttemptCount) {
                                            1 -> 120 // 2 minutes for 2nd resend
                                            else -> 360 // 6 minutes for 3rd+ resends
                                        }
                                    }
                                    is ResendVerificationResult.Error -> {
                                        isLoading = false
                                        errorMessage = result.message
                                    }
                                }
                            }
                        }
                    )
                } else {
                    Text("No phone number provided for SMS resend", fontSize = 12.sp, color = Color(0xFF5F6368))
                }

                // Display Cooldown Countdown Timer Message
                if (!canResend) {
                    Spacer(modifier = Modifier.height(6.dp))
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

            Spacer(modifier = Modifier.height(20.dp))

            TextButton(
                onClick = onNavigateLogin,
                enabled = !isLoading
            ) {
                Text(text = "Back to Login", color = Color(0xFFFF6B35), fontSize = 14.sp, fontWeight = FontWeight.Medium)
            }
        }
    }
}
