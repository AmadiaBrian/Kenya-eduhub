package com.example.kenyatduhubtechers

import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.activity.enableEdgeToEdge
import androidx.activity.viewModels
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import com.example.kenyatduhubtechers.data.local.SessionManager
import com.example.kenyatduhubtechers.data.repository.AuthRepository
import com.example.kenyatduhubtechers.data.repository.CalendarRepository
import com.example.kenyatduhubtechers.data.repository.DashboardRepository
import com.example.kenyatduhubtechers.data.repository.FeesRepository
import com.example.kenyatduhubtechers.data.repository.ParentsRepository
import com.example.kenyatduhubtechers.data.repository.PerformanceRepository
import com.example.kenyatduhubtechers.data.repository.ProfileRepository
import com.example.kenyatduhubtechers.data.repository.ResultsRepository
import com.example.kenyatduhubtechers.data.repository.StudentsRepository
import com.example.kenyatduhubtechers.data.repository.TimetableRepository
import com.example.kenyatduhubtechers.ui.calendar.CalendarViewModel
import com.example.kenyatduhubtechers.ui.dashboard.DashboardScreen
import com.example.kenyatduhubtechers.ui.dashboard.DashboardViewModel
import com.example.kenyatduhubtechers.ui.fees.FeesViewModel
import com.example.kenyatduhubtechers.ui.login.LoginScreen
import com.example.kenyatduhubtechers.ui.login.LoginViewModel
import com.example.kenyatduhubtechers.ui.parents.ParentsViewModel
import com.example.kenyatduhubtechers.ui.performance.PerformanceViewModel
import com.example.kenyatduhubtechers.ui.profile.ProfileViewModel
import com.example.kenyatduhubtechers.ui.results.ResultsViewModel
import com.example.kenyatduhubtechers.ui.students.StudentsViewModel
import com.example.kenyatduhubtechers.ui.theme.KenyaTduhubTechersTheme
import com.example.kenyatduhubtechers.ui.timetable.TimetableViewModel

class MainActivity : ComponentActivity() {

    private val sessionManager by lazy { SessionManager(applicationContext) }
    private val authRepository by lazy { AuthRepository(sessionManager) }
    private val dashboardRepository by lazy { DashboardRepository(sessionManager) }
    private val calendarRepository by lazy { CalendarRepository(sessionManager) }
    private val timetableRepository by lazy { TimetableRepository(sessionManager) }
    private val performanceRepository by lazy { PerformanceRepository(sessionManager) }
    private val resultsRepository by lazy { ResultsRepository(sessionManager) }
    private val profileRepository by lazy { ProfileRepository(sessionManager) }
    private val studentsRepository by lazy { StudentsRepository(sessionManager) }
    private val parentsRepository by lazy { ParentsRepository(sessionManager) }
    private val feesRepository by lazy { FeesRepository(sessionManager) }

    private val loginViewModel: LoginViewModel by viewModels {
        LoginViewModel.Factory(application, authRepository)
    }

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        enableEdgeToEdge()
        setContent {
            KenyaTduhubTechersTheme {
                val uiState by loginViewModel.uiState.collectAsState()
                if (uiState.isLoggedIn) {
                    val dashboardViewModel: DashboardViewModel by viewModels {
                        DashboardViewModel.Factory(dashboardRepository, uiState.teacher)
                    }

                    val calendarViewModel: CalendarViewModel by viewModels {
                        CalendarViewModel.Factory(calendarRepository)
                    }

                    val timetableViewModel: TimetableViewModel by viewModels {
                        TimetableViewModel.Factory(timetableRepository)
                    }

                    val performanceViewModel: PerformanceViewModel by viewModels {
                        PerformanceViewModel.Factory(performanceRepository)
                    }

                    val resultsViewModel: ResultsViewModel by viewModels {
                        ResultsViewModel.Factory(resultsRepository)
                    }

                    val profileViewModel: ProfileViewModel by viewModels {
                        ProfileViewModel.Factory(profileRepository)
                    }

                    val studentsViewModel: StudentsViewModel by viewModels {
                        StudentsViewModel.Factory(studentsRepository)
                    }

                    val parentsViewModel: ParentsViewModel by viewModels {
                        ParentsViewModel.Factory(parentsRepository)
                    }

                    val feesViewModel: FeesViewModel by viewModels {
                        FeesViewModel.Factory(feesRepository)
                    }

                    DashboardScreen(
                        dashboardViewModel = dashboardViewModel,
                        calendarViewModel = calendarViewModel,
                        timetableViewModel = timetableViewModel,
                        performanceViewModel = performanceViewModel,
                        resultsViewModel = resultsViewModel,
                        profileViewModel = profileViewModel,
                        studentsViewModel = studentsViewModel,
                        parentsViewModel = parentsViewModel,
                        feesViewModel = feesViewModel,
                        onLogout = { loginViewModel.logout() }
                    )
                } else {
                    LoginScreen(viewModel = loginViewModel)
                }
            }
        }
    }
}
