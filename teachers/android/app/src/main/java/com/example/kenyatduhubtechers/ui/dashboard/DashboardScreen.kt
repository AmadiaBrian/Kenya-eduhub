package com.example.kenyatduhubtechers.ui.dashboard

import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.Assignment
import androidx.compose.material.icons.automirrored.filled.TrendingUp
import androidx.compose.material.icons.filled.BeachAccess
import androidx.compose.material.icons.filled.CalendarMonth
import androidx.compose.material.icons.filled.Event
import androidx.compose.material.icons.filled.EventBusy
import androidx.compose.material.icons.filled.FactCheck
import androidx.compose.material.icons.filled.Groups
import androidx.compose.material.icons.filled.HowToReg
import androidx.compose.material.icons.filled.Menu
import androidx.compose.material.icons.filled.School
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.DrawerValue
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.HorizontalDivider
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.ModalNavigationDrawer
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Text
import androidx.compose.material3.TopAppBar
import androidx.compose.material3.TopAppBarDefaults
import androidx.compose.material3.rememberDrawerState
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.example.kenyatduhubtechers.data.model.TeacherDto
import com.example.kenyatduhubtechers.ui.calendar.CalendarScreen
import com.example.kenyatduhubtechers.ui.calendar.CalendarViewModel
import com.example.kenyatduhubtechers.ui.components.KenyaEduHubLogo
import com.example.kenyatduhubtechers.ui.fees.FeesScreen
import com.example.kenyatduhubtechers.ui.fees.FeesViewModel
import com.example.kenyatduhubtechers.ui.navigation.AppDrawerContent
import com.example.kenyatduhubtechers.ui.navigation.NavItem
import com.example.kenyatduhubtechers.ui.navigation.NavItems
import com.example.kenyatduhubtechers.ui.parents.ParentsScreen
import com.example.kenyatduhubtechers.ui.parents.ParentsViewModel
import com.example.kenyatduhubtechers.ui.performance.PerformanceScreen
import com.example.kenyatduhubtechers.ui.performance.PerformanceViewModel
import com.example.kenyatduhubtechers.ui.profile.ProfileScreen
import com.example.kenyatduhubtechers.ui.profile.ProfileViewModel
import com.example.kenyatduhubtechers.ui.results.ResultsScreen
import com.example.kenyatduhubtechers.ui.results.ResultsViewModel
import com.example.kenyatduhubtechers.ui.students.StudentsScreen
import com.example.kenyatduhubtechers.ui.students.StudentsViewModel
import com.example.kenyatduhubtechers.ui.theme.EduHubBackground
import com.example.kenyatduhubtechers.ui.theme.EduHubBorder
import com.example.kenyatduhubtechers.ui.theme.EduHubOrange
import com.example.kenyatduhubtechers.ui.theme.EduHubSuccess
import com.example.kenyatduhubtechers.ui.theme.EduHubTextDark
import com.example.kenyatduhubtechers.ui.theme.EduHubTextMuted
import com.example.kenyatduhubtechers.ui.timetable.TimetableScreen
import com.example.kenyatduhubtechers.ui.timetable.TimetableViewModel
import kotlinx.coroutines.launch
import java.text.SimpleDateFormat
import java.util.Date
import java.util.Locale

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun DashboardScreen(
    dashboardViewModel: DashboardViewModel,
    calendarViewModel: CalendarViewModel,
    timetableViewModel: TimetableViewModel,
    performanceViewModel: PerformanceViewModel,
    resultsViewModel: ResultsViewModel,
    profileViewModel: ProfileViewModel,
    studentsViewModel: StudentsViewModel,
    parentsViewModel: ParentsViewModel,
    feesViewModel: FeesViewModel,
    onLogout: () -> Unit,
    modifier: Modifier = Modifier
) {
    val dashboardUiState by dashboardViewModel.uiState.collectAsState()
    var selectedRoute by remember { mutableStateOf(NavItem.Dashboard.route) }
    val drawerState = rememberDrawerState(initialValue = DrawerValue.Closed)
    val scope = rememberCoroutineScope()

    ModalNavigationDrawer(
        drawerState = drawerState,
        gesturesEnabled = true,
        drawerContent = {
            AppDrawerContent(
                currentRoute = selectedRoute,
                teacher = dashboardUiState.teacher,
                onItemSelected = { item ->
                    selectedRoute = item.route
                    scope.launch { drawerState.close() }
                },
                onLogout = {
                    scope.launch { drawerState.close() }
                    onLogout()
                }
            )
        }
    ) {
        Scaffold(
            modifier = modifier.fillMaxSize(),
            containerColor = EduHubBackground,
            topBar = {
                TopAppBar(
                    title = {
                        KenyaEduHubLogo(
                            showText = true,
                            circleSize = 34.dp,
                            borderWidth = 2.dp,
                            kFontSize = 18.sp,
                            eFontSize = 15.sp,
                            textFontSize = 18.sp
                        )
                    },
                    colors = TopAppBarDefaults.topAppBarColors(
                        containerColor = EduHubBackground
                    ),
                    actions = {
                        IconButton(onClick = { scope.launch { drawerState.open() } }) {
                            Icon(
                                imageVector = Icons.Default.Menu,
                                contentDescription = "Menu Sidebar",
                                tint = EduHubTextDark
                            )
                        }
                    }
                )
            }
        ) { innerPadding ->
            Box(
                modifier = Modifier
                    .fillMaxSize()
                    .padding(innerPadding)
            ) {
                when (selectedRoute) {
                    NavItem.Dashboard.route -> DashboardMainContent(
                        uiState = dashboardUiState,
                        onNavigate = { route -> selectedRoute = route }
                    )
                    NavItem.Calendar.route -> CalendarScreen(
                        viewModel = calendarViewModel
                    )
                    NavItem.Timetable.route -> TimetableScreen(
                        viewModel = timetableViewModel
                    )
                    NavItem.Performance.route -> PerformanceScreen(
                        viewModel = performanceViewModel
                    )
                    NavItem.Results.route -> ResultsScreen(
                        viewModel = resultsViewModel
                    )
                    NavItem.Profile.route -> ProfileScreen(
                        viewModel = profileViewModel
                    )
                    NavItem.Students.route -> StudentsScreen(
                        viewModel = studentsViewModel
                    )
                    NavItem.Parents.route -> ParentsScreen(
                        viewModel = parentsViewModel
                    )
                    NavItem.Fees.route -> FeesScreen(
                        viewModel = feesViewModel
                    )
                    else -> GenericScreenContent(
                        title = NavItems.getMainItems().find { it.route == selectedRoute }?.title
                            ?: NavItems.getAccountItems().find { it.route == selectedRoute }?.title
                            ?: "Screen",
                        teacher = dashboardUiState.teacher
                    )
                }
            }
        }
    }
}

@Composable
private fun DashboardMainContent(
    uiState: DashboardUiState,
    onNavigate: (String) -> Unit
) {
    if (uiState.isLoading) {
        Box(
            modifier = Modifier.fillMaxSize(),
            contentAlignment = Alignment.Center
        ) {
            CircularProgressIndicator(color = EduHubOrange)
        }
        return
    }

    val teacher = uiState.teacher
    val stats = uiState.stats
    val calendarStatus = uiState.calendarStatus

    Column(
        modifier = Modifier
            .fillMaxSize()
            .verticalScroll(rememberScrollState())
            .padding(16.dp)
    ) {
        // 1. Welcome Container Card
        Card(
            modifier = Modifier
                .fillMaxWidth()
                .padding(bottom = 16.dp),
            shape = RoundedCornerShape(8.dp),
            colors = CardDefaults.cardColors(containerColor = EduHubBackground),
            border = BorderStroke(1.dp, EduHubBorder),
            elevation = CardDefaults.cardElevation(defaultElevation = 0.dp)
        ) {
            Column(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(20.dp),
                horizontalAlignment = Alignment.CenterHorizontally
            ) {
                Text(
                    text = "Dashboard",
                    fontSize = 22.sp,
                    fontWeight = FontWeight.Normal,
                    color = EduHubTextDark,
                    textAlign = TextAlign.Center
                )

                Spacer(modifier = Modifier.height(6.dp))

                val subtitle = buildString {
                    append("Welcome, ${teacher?.name ?: "Teacher"}")
                    if (!teacher?.schoolName.isNull_or_empty()) {
                        append(" | ${teacher?.schoolName}")
                    }
                    if (!teacher?.className.isNull_or_empty()) {
                        append(" | Class: ${teacher?.className}")
                        if (!teacher?.streamName.isNull_or_empty()) {
                            append(" - ${teacher?.streamName}")
                        }
                    }
                }

                Text(
                    text = subtitle,
                    fontSize = 13.sp,
                    color = EduHubTextMuted,
                    textAlign = TextAlign.Center
                )
            }
        }

        // 2. School Session Status Card (Orange Translucent Card)
        Card(
            modifier = Modifier
                .fillMaxWidth()
                .padding(bottom = 20.dp),
            shape = RoundedCornerShape(12.dp),
            colors = CardDefaults.cardColors(containerColor = EduHubOrange),
            elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)
        ) {
            Column(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(20.dp)
            ) {
                Text(
                    text = "School Session Status",
                    fontSize = 18.sp,
                    fontWeight = FontWeight.Bold,
                    color = Color.White
                )

                Spacer(modifier = Modifier.height(14.dp))

                when (calendarStatus?.schoolStatus) {
                    "holiday" -> {
                        Row(verticalAlignment = Alignment.CenterVertically) {
                            Icon(Icons.Default.BeachAccess, contentDescription = null, tint = Color.White, modifier = Modifier.size(28.dp))
                            Spacer(modifier = Modifier.width(12.dp))
                            Column {
                                Text("School is on Holiday", fontSize = 15.sp, fontWeight = FontWeight.Bold, color = Color.White)
                                Text(
                                    text = "${calendarStatus.currentHoliday?.holidayName ?: "Holiday"} (${formatDate(calendarStatus.currentHoliday?.startDate)} - ${formatDate(calendarStatus.currentHoliday?.endDate)})",
                                    fontSize = 13.sp,
                                    color = Color.White.copy(alpha = 0.9f)
                                )
                            }
                        }
                    }
                    "break" -> {
                        Row(verticalAlignment = Alignment.CenterVertically) {
                            Icon(Icons.Default.EventBusy, contentDescription = null, tint = Color.White, modifier = Modifier.size(28.dp))
                            Spacer(modifier = Modifier.width(12.dp))
                            Column {
                                Text("School is on Break", fontSize = 15.sp, fontWeight = FontWeight.Bold, color = Color.White)
                                Text("No active term for current date", fontSize = 13.sp, color = Color.White.copy(alpha = 0.9f))
                            }
                        }
                    }
                    else -> {
                        Row(verticalAlignment = Alignment.CenterVertically) {
                            Icon(Icons.Default.School, contentDescription = null, tint = Color.White, modifier = Modifier.size(28.dp))
                            Spacer(modifier = Modifier.width(12.dp))
                            Column {
                                Text("School is in Session", fontSize = 15.sp, fontWeight = FontWeight.Bold, color = Color.White)
                                Text(calendarStatus?.currentTerm?.termName ?: "Term 1", fontSize = 13.sp, fontWeight = FontWeight.SemiBold, color = Color.White)
                                if (calendarStatus?.currentTerm?.startDate != null) {
                                    Text(
                                        text = "${formatDate(calendarStatus.currentTerm.startDate)} - ${formatDate(calendarStatus.currentTerm.endDate)}",
                                        fontSize = 12.sp,
                                        color = Color.White.copy(alpha = 0.85f)
                                    )
                                }
                            }
                        }
                    }
                }

                Spacer(modifier = Modifier.height(14.dp))
                HorizontalDivider(color = Color.White.copy(alpha = 0.35f))
                Spacer(modifier = Modifier.height(12.dp))

                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.SpaceBetween,
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    Row(verticalAlignment = Alignment.CenterVertically) {
                        Icon(Icons.Default.CalendarMonth, contentDescription = null, tint = Color.White, modifier = Modifier.size(16.dp))
                        Spacer(modifier = Modifier.width(6.dp))
                        Text("Academic Year: ${calendarStatus?.currentYear ?: dateYear()}", fontSize = 12.sp, color = Color.White)
                    }

                    Row(verticalAlignment = Alignment.CenterVertically) {
                        Icon(Icons.Default.Event, contentDescription = null, tint = Color.White, modifier = Modifier.size(16.dp))
                        Spacer(modifier = Modifier.width(6.dp))
                        Text("Today: ${formatTodayDate()}", fontSize = 12.sp, color = Color.White)
                    }
                }
            }
        }

        // 3. Stats Grid (Matching web .stat-card)
        Column(
            verticalArrangement = Arrangement.spacedBy(12.dp),
            modifier = Modifier.padding(bottom = 20.dp)
        ) {
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.spacedBy(12.dp)
            ) {
                WebStatCard(
                    modifier = Modifier.weight(1f),
                    label = "Total Students",
                    value = stats?.totalStudents?.toString() ?: "0",
                    changeText = "In your class",
                    icon = Icons.Default.School,
                    iconTint = EduHubOrange
                )
                WebStatCard(
                    modifier = Modifier.weight(1f),
                    label = "Attendance Today",
                    value = stats?.attendanceToday?.toString() ?: "0",
                    changeText = "Records taken",
                    icon = Icons.Default.FactCheck,
                    iconTint = EduHubOrange
                )
            }

            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.spacedBy(12.dp)
            ) {
                val attendanceRate = stats?.attendanceRate ?: 0.0
                WebStatCard(
                    modifier = Modifier.weight(1f),
                    label = "Present Today",
                    value = stats?.presentToday?.toString() ?: "0",
                    changeText = "$attendanceRate%",
                    isPositiveChange = true,
                    icon = Icons.Default.HowToReg,
                    iconTint = EduHubOrange
                )
                WebStatCard(
                    modifier = Modifier.weight(1f),
                    label = "Performance Records",
                    value = stats?.performanceRecords?.toString() ?: "0",
                    changeText = "This term",
                    icon = Icons.AutoMirrored.Filled.TrendingUp,
                    iconTint = EduHubOrange
                )
            }
        }

        // 4. Quick Actions Card (Matching web layout)
        Card(
            modifier = Modifier
                .fillMaxWidth()
                .padding(bottom = 16.dp),
            shape = RoundedCornerShape(8.dp),
            colors = CardDefaults.cardColors(containerColor = EduHubBackground),
            border = BorderStroke(1.dp, EduHubBorder),
            elevation = CardDefaults.cardElevation(defaultElevation = 0.dp)
        ) {
            Column(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(16.dp)
            ) {
                Text(
                    text = "Quick Actions",
                    fontSize = 18.sp,
                    fontWeight = FontWeight.Medium,
                    color = EduHubTextDark,
                    modifier = Modifier.padding(bottom = 14.dp)
                )

                Column(verticalArrangement = Arrangement.spacedBy(10.dp)) {
                    Row(horizontalArrangement = Arrangement.spacedBy(10.dp)) {
                        WebActionCard(
                            modifier = Modifier.weight(1f),
                            title = "Take Attendance",
                            description = "Mark daily attendance",
                            icon = Icons.Default.FactCheck,
                            isPrimary = true,
                            onClick = { onNavigate(NavItem.Attendance.route) }
                        )
                        WebActionCard(
                            modifier = Modifier.weight(1f),
                            title = "Record Performance",
                            description = "Update student grades",
                            icon = Icons.AutoMirrored.Filled.TrendingUp,
                            isPrimary = false,
                            onClick = { onNavigate(NavItem.Performance.route) }
                        )
                    }

                    Row(horizontalArrangement = Arrangement.spacedBy(10.dp)) {
                        WebActionCard(
                            modifier = Modifier.weight(1f),
                            title = "View Students",
                            description = "Manage student records",
                            icon = Icons.Default.School,
                            isPrimary = false,
                            onClick = { onNavigate(NavItem.Students.route) }
                        )
                        WebActionCard(
                            modifier = Modifier.weight(1f),
                            title = "View Parents",
                            description = "Contact parents",
                            icon = Icons.Default.Groups,
                            isPrimary = false,
                            onClick = { onNavigate(NavItem.Parents.route) }
                        )
                    }
                }
            }
        }

        // 5. Recent Activity Card
        Card(
            modifier = Modifier.fillMaxWidth(),
            shape = RoundedCornerShape(8.dp),
            colors = CardDefaults.cardColors(containerColor = EduHubBackground),
            border = BorderStroke(1.dp, EduHubBorder),
            elevation = CardDefaults.cardElevation(defaultElevation = 0.dp)
        ) {
            Column(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(16.dp)
            ) {
                Text(
                    text = "Recent Activity",
                    fontSize = 18.sp,
                    fontWeight = FontWeight.Medium,
                    color = EduHubTextDark,
                    modifier = Modifier.padding(bottom = 12.dp)
                )

                ActivityRowItem(
                    icon = Icons.Default.FactCheck,
                    title = "Attendance taken for your class",
                    timeText = "Today"
                )
                HorizontalDivider(color = EduHubBorder.copy(alpha = 0.5f), modifier = Modifier.padding(vertical = 8.dp))

                ActivityRowItem(
                    icon = Icons.AutoMirrored.Filled.TrendingUp,
                    title = "Performance records updated",
                    timeText = "Yesterday"
                )
                HorizontalDivider(color = EduHubBorder.copy(alpha = 0.5f), modifier = Modifier.padding(vertical = 8.dp))

                ActivityRowItem(
                    icon = Icons.Default.School,
                    title = "New student enrolled in your class",
                    timeText = "2 days ago"
                )
            }
        }
    }
}

@Composable
private fun WebStatCard(
    modifier: Modifier = Modifier,
    label: String,
    value: String,
    changeText: String,
    isPositiveChange: Boolean = false,
    icon: ImageVector,
    iconTint: Color
) {
    Card(
        modifier = modifier,
        shape = RoundedCornerShape(8.dp),
        colors = CardDefaults.cardColors(containerColor = EduHubBackground),
        border = BorderStroke(1.dp, EduHubBorder),
        elevation = CardDefaults.cardElevation(defaultElevation = 0.dp)
    ) {
        Column(
            modifier = Modifier
                .fillMaxWidth()
                .padding(16.dp),
            horizontalAlignment = Alignment.CenterHorizontally
        ) {
            Icon(
                imageVector = icon,
                contentDescription = null,
                tint = iconTint,
                modifier = Modifier.size(32.dp)
            )
            Spacer(modifier = Modifier.height(8.dp))
            Text(text = label, fontSize = 13.sp, color = EduHubTextMuted, textAlign = TextAlign.Center)
            Spacer(modifier = Modifier.height(4.dp))
            Text(text = value, fontSize = 28.sp, fontWeight = FontWeight.Normal, color = EduHubTextDark, textAlign = TextAlign.Center)
            Spacer(modifier = Modifier.height(4.dp))
            Text(
                text = changeText,
                fontSize = 11.sp,
                color = if (isPositiveChange) EduHubSuccess else EduHubTextMuted,
                textAlign = TextAlign.Center
            )
        }
    }
}

@Composable
private fun WebActionCard(
    modifier: Modifier = Modifier,
    title: String,
    description: String,
    icon: ImageVector,
    isPrimary: Boolean,
    onClick: () -> Unit
) {
    val bgModifier = if (isPrimary) {
        Modifier.background(
            brush = Brush.linearGradient(
                colors = listOf(EduHubOrange, Color(0xFFFF8C00))
            ),
            shape = RoundedCornerShape(8.dp)
        )
    } else {
        Modifier.background(
            color = EduHubBackground,
            shape = RoundedCornerShape(8.dp)
        ).border(1.dp, EduHubBorder, RoundedCornerShape(8.dp))
    }

    Box(
        modifier = modifier
            .clip(RoundedCornerShape(8.dp))
            .then(bgModifier)
            .clickable { onClick() }
            .padding(16.dp)
    ) {
        Column(
            modifier = Modifier.fillMaxWidth(),
            horizontalAlignment = Alignment.CenterHorizontally
        ) {
            Box(
                modifier = Modifier
                    .size(44.dp)
                    .clip(CircleShape)
                    .background(if (isPrimary) Color.White.copy(alpha = 0.2f) else Color(0xFFE8F0FE)),
                contentAlignment = Alignment.Center
            ) {
                Icon(
                    imageVector = icon,
                    contentDescription = null,
                    tint = if (isPrimary) Color.White else EduHubOrange,
                    modifier = Modifier.size(22.dp)
                )
            }

            Spacer(modifier = Modifier.height(10.dp))

            Text(
                text = title,
                fontSize = 13.sp,
                fontWeight = FontWeight.Medium,
                color = if (isPrimary) Color.White else EduHubTextDark,
                textAlign = TextAlign.Center
            )

            Text(
                text = description,
                fontSize = 11.sp,
                color = if (isPrimary) Color.White.copy(alpha = 0.85f) else EduHubTextMuted,
                textAlign = TextAlign.Center
            )
        }
    }
}

@Composable
private fun ActivityRowItem(
    icon: ImageVector,
    title: String,
    timeText: String
) {
    Row(
        modifier = Modifier.fillMaxWidth(),
        verticalAlignment = Alignment.CenterVertically
    ) {
        Box(
            modifier = Modifier
                .size(36.dp)
                .clip(CircleShape)
                .background(Color(0xFFE8F0FE)),
            contentAlignment = Alignment.Center
        ) {
            Icon(imageVector = icon, contentDescription = null, tint = EduHubOrange, modifier = Modifier.size(18.dp))
        }

        Spacer(modifier = Modifier.width(12.dp))

        Column(modifier = Modifier.weight(1f)) {
            Text(text = title, fontSize = 13.sp, color = EduHubTextDark)
            Text(text = timeText, fontSize = 11.sp, color = EduHubTextMuted)
        }
    }
}

@Composable
private fun GenericScreenContent(
    title: String,
    teacher: TeacherDto?
) {
    Box(
        modifier = Modifier
            .fillMaxSize()
            .padding(16.dp),
        contentAlignment = Alignment.Center
    ) {
        Column(horizontalAlignment = Alignment.CenterHorizontally) {
            Text(
                text = title,
                fontSize = 22.sp,
                fontWeight = FontWeight.Bold,
                color = EduHubTextDark
            )
            Spacer(modifier = Modifier.height(8.dp))
            Text(
                text = "Feature module for $title is connected.",
                fontSize = 14.sp,
                color = EduHubTextMuted
            )
        }
    }
}

private fun String?.isNull_or_empty(): Boolean = this == null || this.trim().isEmpty()

private fun dateYear(): String {
    return SimpleDateFormat("yyyy", Locale.getDefault()).format(Date())
}

private fun formatTodayDate(): String {
    return SimpleDateFormat("MMMM d, yyyy", Locale.getDefault()).format(Date())
}

private fun formatDate(dateStr: String?): String {
    if (dateStr.isNull_or_empty()) return ""
    return try {
        val parsed = SimpleDateFormat("yyyy-MM-dd", Locale.getDefault()).parse(dateStr)
        if (parsed != null) SimpleDateFormat("MMM d, yyyy", Locale.getDefault()).format(parsed) else dateStr!!
    } catch (e: Exception) {
        dateStr!!
    }
}
