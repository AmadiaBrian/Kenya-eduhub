package com.example.kenyaeduhub

import android.os.Bundle
import android.widget.Toast
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.activity.enableEdgeToEdge
import androidx.activity.viewModels
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.runtime.*
import androidx.compose.ui.Modifier
import com.example.kenyaeduhub.data.User
import com.example.kenyaeduhub.ui.dashboard.DashboardScreen
import com.example.kenyaeduhub.ui.dashboard.DashboardViewModel
import com.example.kenyaeduhub.ui.forgotpassword.ForgotPasswordScreen
import com.example.kenyaeduhub.ui.forgotpassword.ForgotPasswordViewModel
import com.example.kenyaeduhub.ui.login.LoginScreen
import com.example.kenyaeduhub.ui.login.LoginViewModel
import com.example.kenyaeduhub.ui.register.RegisterScreen
import com.example.kenyaeduhub.ui.register.RegisterViewModel
import com.example.kenyaeduhub.ui.theme.KenyaEduhubTheme
import com.example.kenyaeduhub.ui.theme.getAppThemeColors
import com.example.kenyaeduhub.ui.verify.VerifyScreen
import com.example.kenyaeduhub.utils.SessionManager

class MainActivity : ComponentActivity() {
    private val loginViewModel: LoginViewModel by viewModels()
    private val registerViewModel: RegisterViewModel by viewModels()
    private val forgotPasswordViewModel: ForgotPasswordViewModel by viewModels()
    private val dashboardViewModel: DashboardViewModel by viewModels()

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        enableEdgeToEdge()

        val sessionManager = SessionManager(applicationContext)

        setContent {
            KenyaEduhubTheme {
                val themeColors = getAppThemeColors()
                Box(
                    modifier = Modifier
                        .fillMaxSize()
                        .background(themeColors.pageBgColor)
                ) {
                    var currentUser by remember { mutableStateOf<User?>(sessionManager.getSavedUser()) }
                    var currentScreen by remember { mutableStateOf("login") }
                    var verificationEmail by remember { mutableStateOf("") }
                    var verificationPhone by remember { mutableStateOf("") }

                    when {
                        currentUser != null -> {
                            DashboardScreen(
                                user = currentUser!!,
                                viewModel = dashboardViewModel,
                                onLogout = {
                                    sessionManager.clearSession()
                                    loginViewModel.logout()
                                    currentUser = null
                                    currentScreen = "login"
                                    Toast.makeText(this@MainActivity, "Logged out successfully", Toast.LENGTH_SHORT).show()
                                }
                            )
                        }
                        currentScreen == "register" -> {
                            RegisterScreen(
                                viewModel = registerViewModel,
                                onRegisterSuccess = { email, phone, msg ->
                                    verificationEmail = email
                                    verificationPhone = phone
                                    currentScreen = "verify"
                                    Toast.makeText(this@MainActivity, "Registration successful! Verification code sent.", Toast.LENGTH_LONG).show()
                                },
                                onNavigateLogin = {
                                    currentScreen = "login"
                                }
                            )
                        }
                        currentScreen == "verify" -> {
                            VerifyScreen(
                                email = verificationEmail,
                                phone = verificationPhone,
                                onVerifySuccess = { msg ->
                                    Toast.makeText(this@MainActivity, msg, Toast.LENGTH_LONG).show()
                                    currentScreen = "login"
                                },
                                onNavigateLogin = {
                                    currentScreen = "login"
                                }
                            )
                        }
                        currentScreen == "forgot_password" -> {
                            ForgotPasswordScreen(
                                viewModel = forgotPasswordViewModel,
                                onResetSuccess = { msg ->
                                    Toast.makeText(this@MainActivity, msg, Toast.LENGTH_LONG).show()
                                    forgotPasswordViewModel.resetState()
                                    currentScreen = "login"
                                },
                                onNavigateLogin = {
                                    forgotPasswordViewModel.resetState()
                                    currentScreen = "login"
                                }
                            )
                        }
                        else -> {
                            LoginScreen(
                                viewModel = loginViewModel,
                                onLoginSuccess = { user ->
                                    sessionManager.saveUserSession(user)
                                    currentUser = user
                                    Toast.makeText(this@MainActivity, "Welcome, ${user.name}!", Toast.LENGTH_SHORT).show()
                                },
                                onNavigateRegister = {
                                    currentScreen = "register"
                                },
                                onNavigateForgotPassword = {
                                    forgotPasswordViewModel.resetState()
                                    currentScreen = "forgot_password"
                                },
                                onNavigateResend = {
                                    currentScreen = "verify"
                                }
                            )
                        }
                    }
                }
            }
        }
    }
}
