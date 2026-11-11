package com.example.kenyaeduhubparents

import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.activity.enableEdgeToEdge
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Modifier
import androidx.lifecycle.viewmodel.compose.viewModel
import com.example.kenyaeduhubparents.data.local.SessionManager
import com.example.kenyaeduhubparents.data.network.RetrofitClient
import com.example.kenyaeduhubparents.data.repository.AuthRepository
import com.example.kenyaeduhubparents.ui.home.HomeScreen
import com.example.kenyaeduhubparents.ui.login.LoginScreen
import com.example.kenyaeduhubparents.ui.login.LoginViewModel
import com.example.kenyaeduhubparents.ui.theme.KenyaEduhubParentsTheme

class MainActivity : ComponentActivity() {

    private lateinit var sessionManager: SessionManager
    private lateinit var authRepository: AuthRepository

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        enableEdgeToEdge()

        RetrofitClient.init(applicationContext)
        sessionManager = SessionManager(applicationContext)
        authRepository = AuthRepository(sessionManager)

        setContent {
            KenyaEduhubParentsTheme {
                Surface(
                    modifier = Modifier.fillMaxSize(),
                    color = MaterialTheme.colorScheme.background
                ) {
                    AppNavigation(authRepository = authRepository)
                }
            }
        }
    }
}

@Composable
fun AppNavigation(authRepository: AuthRepository) {
    var isLoggedIn by remember { mutableStateOf(authRepository.isLoggedIn()) }
    var parentData by remember { mutableStateOf(authRepository.getParentData()) }

    val loginViewModel: LoginViewModel = viewModel(
        factory = LoginViewModel.Factory(authRepository)
    )

    if (isLoggedIn) {
        HomeScreen(
            parent = parentData,
            onParentUpdated = { updatedProfile ->
                parentData = authRepository.updateParentData(updatedProfile)
            },
            onLogout = {
                authRepository.logout()
                isLoggedIn = false
                parentData = null
                loginViewModel.resetState()
            }
        )
    } else {
        LoginScreen(
            viewModel = loginViewModel,
            onLoginSuccess = { loggedInParent ->
                parentData = loggedInParent ?: authRepository.getParentData()
                isLoggedIn = true
            }
        )
    }
}
