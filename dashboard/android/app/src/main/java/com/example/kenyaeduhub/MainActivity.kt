package com.example.kenyaeduhub

import android.os.Bundle
import android.widget.Toast
import androidx.activity.ComponentActivity
import androidx.activity.SystemBarStyle
import androidx.activity.compose.setContent
import androidx.activity.enableEdgeToEdge
import androidx.activity.viewModels
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.runtime.*
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
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

class MainActivity : ComponentActivity() {
    private val loginViewModel: LoginViewModel by viewModels()
    private val registerViewModel: RegisterViewModel by viewModels()
    private val forgotPasswordViewModel: ForgotPasswordViewModel by viewModels()
    private val dashboardViewModel: DashboardViewModel by viewModels()

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)

        val pageBgColorInt = android.graphics.Color.parseColor("#F0F4F9")
        enableEdgeToEdge(
            statusBarStyle = SystemBarStyle.light(pageBgColorInt, pageBgColorInt),
            navigationBarStyle = SystemBarStyle.light(pageBgColorInt, pageBgColorInt)
        )

        setContent {
            KenyaEduhubTheme {
                Box(
                    modifier = Modifier
                        .fillMaxSize()
                        .background(Color(0xFFF0F4F9))
                ) {
                    var currentUser by remember { mutableStateOf<User?>(null) }
                    var currentScreen by remember { mutableStateOf("login") } // "login", "register", "forgot_password", "dashboard"

                    when {
                        currentUser != null -> {
                            DashboardScreen(
                                user = currentUser!!,
                                viewModel = dashboardViewModel,
                                onLogout = {
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
                                onRegisterSuccess = { msg ->
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
                                    Toast.makeText(this@MainActivity, "Navigate to Resend Code page", Toast.LENGTH_SHORT).show()
                                }
                            )
                        }
                    }
                }
            }
        }
    }
}
