package com.example.kenyatduhubtechers.ui.navigation

import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.Assignment
import androidx.compose.material.icons.automirrored.filled.ExitToApp
import androidx.compose.material.icons.automirrored.filled.TrendingUp
import androidx.compose.material.icons.filled.Book
import androidx.compose.material.icons.filled.CalendarMonth
import androidx.compose.material.icons.filled.Dashboard
import androidx.compose.material.icons.filled.EmojiEvents
import androidx.compose.material.icons.filled.Groups
import androidx.compose.material.icons.filled.Payments
import androidx.compose.material.icons.filled.Person
import androidx.compose.material.icons.filled.Schedule
import androidx.compose.material.icons.filled.School
import androidx.compose.material.icons.filled.Work
import androidx.compose.ui.graphics.vector.ImageVector

enum class NavSection(val title: String) {
    MAIN("Main"),
    ACCOUNT("Account")
}

sealed class NavItem(
    val route: String,
    val title: String,
    val icon: ImageVector,
    val section: NavSection
) {
    data object Dashboard : NavItem("dashboard", "Dashboard", Icons.Default.Dashboard, NavSection.MAIN)
    data object Timetable : NavItem("timetable", "Timetable", Icons.Default.Schedule, NavSection.MAIN)
    data object Attendance : NavItem("attendance", "Attendance", Icons.AutoMirrored.Filled.Assignment, NavSection.MAIN)
    data object Calendar : NavItem("calendar", "Calendar", Icons.Default.CalendarMonth, NavSection.MAIN)
    data object Performance : NavItem("performance", "Performance", Icons.AutoMirrored.Filled.TrendingUp, NavSection.MAIN)
    data object Results : NavItem("results", "Results", Icons.Default.EmojiEvents, NavSection.MAIN)
    data object Students : NavItem("students", "Students", Icons.Default.School, NavSection.MAIN)
    data object StudentSubjects : NavItem("student-subjects", "Student Subjects", Icons.Default.Book, NavSection.MAIN)
    data object Assignments : NavItem("assignments", "Assignments", Icons.AutoMirrored.Filled.Assignment, NavSection.MAIN)
    data object Parents : NavItem("parents", "Parents", Icons.Default.Groups, NavSection.MAIN)
    data object Duty : NavItem("duty", "My Duties", Icons.Default.Work, NavSection.MAIN)
    data object Fees : NavItem("fees", "Fee Payments", Icons.Default.Payments, NavSection.MAIN)

    data object Profile : NavItem("profile", "Profile", Icons.Default.Person, NavSection.ACCOUNT)
    data object Logout : NavItem("logout", "Logout", Icons.AutoMirrored.Filled.ExitToApp, NavSection.ACCOUNT)
}

object NavItems {
    fun getMainItems(): List<NavItem> = listOf(
        NavItem.Dashboard,
        NavItem.Timetable,
        NavItem.Attendance,
        NavItem.Calendar,
        NavItem.Performance,
        NavItem.Results,
        NavItem.Students,
        NavItem.StudentSubjects,
        NavItem.Assignments,
        NavItem.Parents,
        NavItem.Duty,
        NavItem.Fees
    )

    fun getAccountItems(): List<NavItem> = listOf(
        NavItem.Profile,
        NavItem.Logout
    )
}
