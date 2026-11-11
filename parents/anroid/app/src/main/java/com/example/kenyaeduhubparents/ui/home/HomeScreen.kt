package com.example.kenyaeduhubparents.ui.home

import com.example.kenyaeduhubparents.ui.attendance.AttendanceScreen

import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.runtime.Composable
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
import androidx.compose.material.icons.automirrored.filled.FactCheck
import androidx.compose.material.icons.automirrored.filled.Logout
import androidx.compose.material.icons.automirrored.filled.MenuBook
import androidx.compose.material.icons.automirrored.filled.TrendingUp
import androidx.compose.material.icons.filled.CalendarMonth
import androidx.compose.material.icons.filled.Dashboard
import androidx.compose.material.icons.filled.FamilyRestroom
import androidx.compose.material.icons.filled.Menu
import androidx.compose.material.icons.filled.Payment
import androidx.compose.material.icons.filled.Person
import androidx.compose.material.icons.filled.School
import androidx.compose.material3.ButtonDefaults
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.DrawerValue
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.HorizontalDivider
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.ModalDrawerSheet
import androidx.compose.material3.ModalNavigationDrawer
import androidx.compose.material3.NavigationBar
import androidx.compose.material3.NavigationBarItem
import androidx.compose.material3.NavigationDrawerItem
import androidx.compose.material3.NavigationDrawerItemDefaults
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Text
import androidx.compose.material3.TopAppBar
import androidx.compose.material3.TopAppBarDefaults
import com.example.kenyaeduhubparents.ui.calendar.CalendarScreen
import com.example.kenyaeduhubparents.ui.children.ChildrenScreen
import androidx.compose.material3.rememberDrawerState
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.lifecycle.viewmodel.compose.viewModel
import com.example.kenyaeduhubparents.data.model.Child
import com.example.kenyaeduhubparents.data.model.DashboardResponse
import com.example.kenyaeduhubparents.data.model.NotificationItem
import com.example.kenyaeduhubparents.data.model.Parent
import com.example.kenyaeduhubparents.ui.libraryfines.LibraryFinesScreen
import com.example.kenyaeduhubparents.ui.performance.PerformanceScreen
import com.example.kenyaeduhubparents.ui.profile.ProfileScreen
import kotlinx.coroutines.launch

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun HomeScreen(
    parent: Parent?,
    onLogout: () -> Unit,
    onParentUpdated: (com.example.kenyaeduhubparents.data.model.ParentProfile) -> Unit,
    modifier: Modifier = Modifier,
    viewModel: DashboardViewModel = viewModel(
        factory = DashboardViewModel.Factory(LocalContext.current)
    )
) {
    val uiState by viewModel.uiState.collectAsState()
    val drawerState = rememberDrawerState(initialValue = DrawerValue.Closed)
    val scope = rememberCoroutineScope()
    var currentRoute by remember { mutableStateOf("dashboard") }

    ModalNavigationDrawer(
        drawerState = drawerState,
        drawerContent = {
            AppDrawer(
                currentRoute = currentRoute,
                onRouteSelected = { route ->
                    currentRoute = route
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
            containerColor = MaterialTheme.colorScheme.background,
            topBar = {
                TopAppBar(
                    title = {
                        Row(
                            verticalAlignment = Alignment.CenterVertically,
                            horizontalArrangement = Arrangement.spacedBy(8.dp)
                        ) {
                            com.example.kenyaeduhubparents.ui.components.KenyaEduHubLogo()
                        }
                    },
                    actions = {
                        OutlinedButton(
                            onClick = { scope.launch { drawerState.open() } },
                            shape = RoundedCornerShape(20.dp),
                            colors = ButtonDefaults.outlinedButtonColors(
                                contentColor = MaterialTheme.colorScheme.primary,
                                containerColor = MaterialTheme.colorScheme.primaryContainer.copy(alpha = 0.3f)
                            ),
                            border = androidx.compose.foundation.BorderStroke(1.dp, MaterialTheme.colorScheme.primary.copy(alpha = 0.4f)),
                            modifier = Modifier.padding(end = 12.dp)
                        ) {
                            Icon(
                                imageVector = Icons.Filled.Menu,
                                contentDescription = "Menu",
                                modifier = Modifier.size(18.dp)
                            )
                            Spacer(modifier = Modifier.width(6.dp))
                            Text("Menu", fontWeight = FontWeight.Bold, fontSize = 13.sp)
                        }
                    },
                    colors = TopAppBarDefaults.topAppBarColors(
                        containerColor = MaterialTheme.colorScheme.background,
                        titleContentColor = MaterialTheme.colorScheme.onBackground
                    )
                )
            },
            bottomBar = {
                NavigationBar(
                    containerColor = MaterialTheme.colorScheme.surface,
                    tonalElevation = 3.dp
                ) {
                    val items = listOf(
                        Triple("dashboard", "Home", Icons.Filled.Dashboard),
                        Triple("children", "Children", Icons.Filled.FamilyRestroom),
                        Triple("fees", "Fees", Icons.Filled.Payment),
                        Triple("attendance", "Attendance", Icons.AutoMirrored.Filled.FactCheck)
                    )

                    items.forEach { (route, label, icon) ->
                        val selected = currentRoute == route
                        NavigationBarItem(
                            icon = { Icon(imageVector = icon, contentDescription = label) },
                            label = { Text(label) },
                            selected = selected,
                            onClick = { currentRoute = route }
                        )
                    }

                    NavigationBarItem(
                        icon = { Icon(imageVector = Icons.Filled.Menu, contentDescription = "Menu") },
                        label = { Text("Menu") },
                        selected = false,
                        onClick = { scope.launch { drawerState.open() } }
                    )
                }
            },
            modifier = modifier
        ) { innerPadding ->
            Box(
                modifier = Modifier
                    .fillMaxSize()
                    .padding(innerPadding)
            ) {
                when (currentRoute) {
                    "children" -> {
                        ChildrenScreen()
                    }
                    "attendance" -> {
                        AttendanceScreen()
                    }
                    "calendar" -> {
                        CalendarScreen()
                    }
                    "performance" -> {
                        PerformanceScreen()
                    }
                    "results" -> {
                        com.example.kenyaeduhubparents.ui.results.ResultsScreen()
                    }
                    "assignments" -> {
                        com.example.kenyaeduhubparents.ui.assignments.AssignmentsScreen()
                    }
                    "fines" -> {
                        LibraryFinesScreen()
                    }
                    "fees" -> {
                        com.example.kenyaeduhubparents.ui.fees.FeesScreen()
                    }
                    "profile" -> {
                        ProfileScreen(onParentUpdated = onParentUpdated)
                    }
                    else -> {
                        Column(
                            modifier = Modifier
                                .fillMaxSize()
                                .verticalScroll(rememberScrollState())
                                .padding(horizontal = 16.dp, vertical = 16.dp),
                            verticalArrangement = Arrangement.spacedBy(20.dp)
                        ) {
                            when (val state = uiState) {
                                is DashboardUiState.Loading -> {
                                    Box(
                                        modifier = Modifier
                                            .fillMaxWidth()
                                            .height(300.dp),
                                        contentAlignment = Alignment.Center
                                    ) {
                                        CircularProgressIndicator(color = MaterialTheme.colorScheme.primary)
                                    }
                                }
                                is DashboardUiState.Error -> {
                                    DashboardContent(
                                        parent = parent,
                                        dashboard = null,
                                        errorMessage = state.message,
                                        onRetry = { viewModel.loadDashboard() },
                                        onRouteSelected = { route -> currentRoute = route }
                                    )
                                }
                                is DashboardUiState.Success -> {
                                    DashboardContent(
                                        parent = state.data.parent ?: parent,
                                        dashboard = state.data,
                                        errorMessage = null,
                                        onRetry = { viewModel.loadDashboard() },
                                        onRouteSelected = { route -> currentRoute = route }
                                    )
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}

@Composable
fun AppDrawer(
    currentRoute: String,
    onRouteSelected: (String) -> Unit,
    onLogout: () -> Unit,
    modifier: Modifier = Modifier
) {
    ModalDrawerSheet(
        modifier = modifier.width(280.dp),
        drawerContainerColor = Color(0xFFF0F4F9)
    ) {
        Column(
            modifier = Modifier
                .fillMaxSize()
                .verticalScroll(rememberScrollState())
                .padding(vertical = 16.dp),
            verticalArrangement = Arrangement.spacedBy(4.dp)
        ) {
            Box(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(horizontal = 24.dp, vertical = 12.dp),
                contentAlignment = Alignment.CenterStart
            ) {
                com.example.kenyaeduhubparents.ui.components.KenyaEduHubLogo()
            }

            HorizontalDivider(modifier = Modifier.padding(vertical = 8.dp), color = Color(0xFFE8EAED))

            val navItems = listOf(
                Triple("dashboard", "Dashboard", Icons.Filled.Dashboard),
                Triple("children", "My Children", Icons.Filled.FamilyRestroom),
                Triple("performance", "Performance", Icons.AutoMirrored.Filled.TrendingUp),
                Triple("results", "Results", Icons.Filled.School),
                Triple("attendance", "Attendance", Icons.AutoMirrored.Filled.FactCheck),
                Triple("calendar", "Calendar", Icons.Filled.CalendarMonth),
                Triple("assignments", "Assignments", Icons.AutoMirrored.Filled.Assignment),
                Triple("fines", "Library Fines", Icons.AutoMirrored.Filled.MenuBook),
                Triple("fees", "Fee Payments", Icons.Filled.Payment),
                Triple("profile", "Profile", Icons.Filled.Person)
            )

            navItems.forEach { (route, label, icon) ->
                val isActive = currentRoute == route
                NavigationDrawerItem(
                    icon = { Icon(imageVector = icon, contentDescription = label, tint = if (isActive) Color(0xFF1A73E8) else Color(0xFF5F6368)) },
                    label = { Text(text = label, fontWeight = FontWeight.Medium, color = if (isActive) Color(0xFF1A73E8) else Color(0xFF5F6368)) },
                    selected = isActive,
                    onClick = { onRouteSelected(route) },
                    modifier = Modifier.padding(horizontal = 12.dp),
                    shape = RoundedCornerShape(24.dp),
                    colors = NavigationDrawerItemDefaults.colors(
                        selectedContainerColor = Color(0xFFE8F0FE),
                        unselectedContainerColor = Color.Transparent
                    )
                )
            }

            Spacer(modifier = Modifier.weight(1f))
            HorizontalDivider(modifier = Modifier.padding(vertical = 8.dp), color = Color(0xFFE8EAED))

            NavigationDrawerItem(
                icon = { Icon(imageVector = Icons.AutoMirrored.Filled.Logout, contentDescription = "Logout", tint = Color(0xFFD93025)) },
                label = { Text(text = "Logout", fontWeight = FontWeight.Medium, color = Color(0xFFD93025)) },
                selected = false,
                onClick = onLogout,
                modifier = Modifier.padding(horizontal = 12.dp),
                shape = RoundedCornerShape(24.dp),
                colors = NavigationDrawerItemDefaults.colors(
                    unselectedContainerColor = Color.Transparent
                )
            )
        }
    }
}

@Composable
private fun DashboardContent(
    parent: Parent?,
    dashboard: DashboardResponse?,
    errorMessage: String?,
    onRetry: () -> Unit,
    onRouteSelected: (String) -> Unit
) {
    val parentName = parent?.name?.takeIf { it.isNotBlank() } ?: dashboard?.parent?.name ?: "Parent"
    val schoolName = parent?.schoolName?.takeIf { it.isNotBlank() } ?: dashboard?.parent?.schoolName ?: ""

    Column(verticalArrangement = Arrangement.spacedBy(20.dp)) {
        Column(
            modifier = Modifier.fillMaxWidth(),
            horizontalAlignment = Alignment.CenterHorizontally,
            verticalArrangement = Arrangement.spacedBy(4.dp)
        ) {
            Text(
                text = "Dashboard",
                style = MaterialTheme.typography.headlineMedium,
                fontWeight = FontWeight.Bold,
                color = MaterialTheme.colorScheme.onBackground
            )
            val subText = buildString {
                append("Welcome, $parentName")
                if (schoolName.isNotBlank()) {
                    append(" | $schoolName")
                }
            }
            Text(
                text = subText,
                style = MaterialTheme.typography.bodyMedium,
                color = MaterialTheme.colorScheme.onSurfaceVariant,
                textAlign = androidx.compose.ui.text.style.TextAlign.Center
            )
        }

        if (errorMessage != null) {
            Card(
                modifier = Modifier.fillMaxWidth(),
                colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.errorContainer),
                shape = RoundedCornerShape(16.dp)
            ) {
                Row(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(16.dp),
                    horizontalArrangement = Arrangement.SpaceBetween,
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    Text(
                        text = "Could not sync latest data. Showing cached profile.",
                        color = MaterialTheme.colorScheme.onErrorContainer,
                        style = MaterialTheme.typography.bodySmall,
                        modifier = Modifier.weight(1f)
                    )
                    OutlinedButton(onClick = onRetry) {
                        Text("Retry")
                    }
                }
            }
        }

        // Statistics Grid (2x2)
        if (dashboard?.stats != null) {
            val stats = dashboard.stats
            Column(verticalArrangement = Arrangement.spacedBy(12.dp)) {
                SectionHeading(title = "Overview & Statistics")
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.spacedBy(12.dp)
                ) {
                    StatCard(
                        label = "Total Children",
                        value = stats.totalChildren.toString(),
                        change = "Enrolled",
                        modifier = Modifier.weight(1f)
                    )
                    StatCard(
                        label = "Total Fees Due",
                        value = "KES ${String.format(java.util.Locale.getDefault(), "%,.0f", stats.totalFeesDue)}",
                        change = if (stats.totalFeesDue > 0) "Outstanding" else "Paid",
                        isNegative = stats.totalFeesDue > 0,
                        modifier = Modifier.weight(1f)
                    )
                }
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.spacedBy(12.dp)
                ) {
                    StatCard(
                        label = "Attendance Rate",
                        value = "${stats.attendanceRate}%",
                        change = "Last 30 days",
                        isPositive = true,
                        modifier = Modifier.weight(1f)
                    )
                    StatCard(
                        label = "Performance Records",
                        value = stats.performanceRecords.toString(),
                        change = dashboard.currentTerm ?: "This term",
                        modifier = Modifier.weight(1f)
                    )
                }
            }
        }

        // Quick Access Grid
        Card(
            modifier = Modifier.fillMaxWidth(),
            shape = RoundedCornerShape(20.dp),
            colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
            elevation = CardDefaults.cardElevation(defaultElevation = 1.dp)
        ) {
            Column(modifier = Modifier.padding(18.dp), verticalArrangement = Arrangement.spacedBy(16.dp)) {
                Text(
                    text = "Quick Access",
                    style = MaterialTheme.typography.titleMedium,
                    fontWeight = FontWeight.Bold,
                    color = MaterialTheme.colorScheme.onSurface
                )
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.SpaceBetween
                ) {
                    QuickAccessItem(
                        label = "Performance",
                        icon = Icons.AutoMirrored.Filled.TrendingUp,
                        onClick = { onRouteSelected("performance") }
                    )
                    QuickAccessItem(
                        label = "Assignments",
                        icon = Icons.AutoMirrored.Filled.Assignment,
                        onClick = { onRouteSelected("assignments") }
                    )
                    QuickAccessItem(
                        label = "Fee Payments",
                        icon = Icons.Filled.Payment,
                        onClick = { onRouteSelected("fees") }
                    )
                }
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.SpaceBetween
                ) {
                    QuickAccessItem(
                        label = "My Children",
                        icon = Icons.Filled.FamilyRestroom,
                        onClick = { onRouteSelected("children") }
                    )
                    QuickAccessItem(
                        label = "Profile",
                        icon = Icons.Filled.Person,
                        onClick = { onRouteSelected("profile") }
                    )
                    QuickAccessItem(
                        label = "Calendar",
                        icon = Icons.Filled.CalendarMonth,
                        onClick = { onRouteSelected("calendar") }
                    )
                }
            }
        }

        // My Children Card
        Card(
            modifier = Modifier.fillMaxWidth(),
            shape = RoundedCornerShape(20.dp),
            colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
            elevation = CardDefaults.cardElevation(defaultElevation = 1.dp)
        ) {
            Column(modifier = Modifier.padding(18.dp), verticalArrangement = Arrangement.spacedBy(16.dp)) {
                Text(
                    text = "My Children",
                    style = MaterialTheme.typography.titleMedium,
                    fontWeight = FontWeight.Bold,
                    color = MaterialTheme.colorScheme.onSurface
                )
                val childrenList = dashboard?.children ?: emptyList()
                if (childrenList.isEmpty()) {
                    Text(
                        text = "No children enrolled yet.",
                        style = MaterialTheme.typography.bodyMedium,
                        color = MaterialTheme.colorScheme.onSurfaceVariant
                    )
                } else {
                    Column(verticalArrangement = Arrangement.spacedBy(12.dp)) {
                        childrenList.forEach { child ->
                            ChildCard(child = child)
                        }
                    }
                }
            }
        }

        // School Announcements / Recent Activity
        if (!dashboard?.notifications.isNullOrEmpty()) {
            Card(
                modifier = Modifier.fillMaxWidth(),
                shape = RoundedCornerShape(20.dp),
                colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                elevation = CardDefaults.cardElevation(defaultElevation = 1.dp)
            ) {
                Column(modifier = Modifier.padding(18.dp), verticalArrangement = Arrangement.spacedBy(16.dp)) {
                    Text(
                        text = "School Announcements",
                        style = MaterialTheme.typography.titleMedium,
                        fontWeight = FontWeight.Bold,
                        color = MaterialTheme.colorScheme.onSurface
                    )
                    Column(verticalArrangement = Arrangement.spacedBy(12.dp)) {
                        dashboard.notifications.forEach { notif ->
                            NotificationCard(notification = notif)
                        }
                    }
                }
            }
        }

        // Parent Profile Details Card
        Card(
            modifier = Modifier.fillMaxWidth(),
            shape = RoundedCornerShape(20.dp),
            colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
            elevation = CardDefaults.cardElevation(defaultElevation = 1.dp)
        ) {
            Column(
                modifier = Modifier.padding(18.dp),
                verticalArrangement = Arrangement.spacedBy(14.dp)
            ) {
                Text(
                    text = "Account & Contact Details",
                    style = MaterialTheme.typography.titleMedium,
                    fontWeight = FontWeight.Bold,
                    color = MaterialTheme.colorScheme.onSurface
                )
                HorizontalDivider(color = MaterialTheme.colorScheme.outlineVariant)
                InfoRow(label = "Full Name", value = parentName)
                HorizontalDivider(color = MaterialTheme.colorScheme.outlineVariant)
                InfoRow(label = "Email Address", value = parent?.email?.takeIf { it.isNotBlank() } ?: dashboard?.parent?.email ?: "—")
                HorizontalDivider(color = MaterialTheme.colorScheme.outlineVariant)
                InfoRow(label = "Phone Number", value = parent?.phone?.takeIf { it.isNotBlank() } ?: dashboard?.parent?.phone ?: "—")
                HorizontalDivider(color = MaterialTheme.colorScheme.outlineVariant)
                InfoRow(label = "School Name", value = schoolName.ifBlank { "—" })
            }
        }
    }
}

@Composable
private fun StatCard(
    label: String,
    value: String,
    change: String,
    isPositive: Boolean = false,
    isNegative: Boolean = false,
    modifier: Modifier = Modifier
) {
    Card(
        modifier = modifier,
        shape = RoundedCornerShape(16.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        elevation = CardDefaults.cardElevation(defaultElevation = 1.dp)
    ) {
        Column(
            modifier = Modifier.padding(16.dp),
            horizontalAlignment = Alignment.CenterHorizontally,
            verticalArrangement = Arrangement.spacedBy(6.dp)
        ) {
            Text(
                text = label,
                style = MaterialTheme.typography.bodySmall,
                color = MaterialTheme.colorScheme.onSurfaceVariant,
                textAlign = androidx.compose.ui.text.style.TextAlign.Center
            )
            Text(
                text = value,
                style = MaterialTheme.typography.titleLarge,
                fontWeight = FontWeight.Bold,
                color = MaterialTheme.colorScheme.onSurface,
                maxLines = 1,
                overflow = TextOverflow.Ellipsis
            )
            val changeColor = when {
                isPositive -> Color(0xFF1e8e3e)
                isNegative -> Color(0xFFd93025)
                else -> MaterialTheme.colorScheme.onSurfaceVariant
            }
            Text(
                text = change,
                style = MaterialTheme.typography.labelSmall,
                color = changeColor
            )
        }
    }
}

@Composable
private fun QuickAccessItem(
    label: String,
    icon: ImageVector,
    onClick: () -> Unit
) {
    Column(
        horizontalAlignment = Alignment.CenterHorizontally,
        modifier = Modifier
            .width(90.dp)
            .clip(RoundedCornerShape(14.dp))
            .clickable(onClick = onClick)
            .padding(vertical = 8.dp)
    ) {
        Box(
            modifier = Modifier
                .size(48.dp)
                .clip(CircleShape)
                .background(MaterialTheme.colorScheme.primaryContainer),
            contentAlignment = Alignment.Center
        ) {
            Icon(
                imageVector = icon,
                contentDescription = label,
                tint = MaterialTheme.colorScheme.primary,
                modifier = Modifier.size(24.dp)
            )
        }
        Spacer(modifier = Modifier.height(6.dp))
        Text(
            text = label,
            style = MaterialTheme.typography.bodySmall,
            fontWeight = FontWeight.Medium,
            color = MaterialTheme.colorScheme.onSurface,
            textAlign = androidx.compose.ui.text.style.TextAlign.Center,
            maxLines = 1,
            overflow = TextOverflow.Ellipsis
        )
    }
}

@Composable
private fun ChildCard(child: Child) {
    val initial = child.name.firstOrNull()?.uppercase() ?: "C"
    Card(
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(16.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.5f)),
        elevation = CardDefaults.cardElevation(defaultElevation = 0.dp)
    ) {
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .padding(14.dp),
            verticalAlignment = Alignment.CenterVertically
        ) {
            Box(
                modifier = Modifier
                    .size(44.dp)
                    .clip(CircleShape)
                    .background(MaterialTheme.colorScheme.primary),
                contentAlignment = Alignment.Center
            ) {
                Text(
                    text = initial,
                    color = MaterialTheme.colorScheme.onPrimary,
                    fontWeight = FontWeight.Bold,
                    fontSize = 18.sp
                )
            }
            Spacer(modifier = Modifier.width(14.dp))
            Column(modifier = Modifier.weight(1f)) {
                Text(
                    text = child.name,
                    style = MaterialTheme.typography.titleSmall,
                    fontWeight = FontWeight.Bold,
                    color = MaterialTheme.colorScheme.onSurface
                )
                Spacer(modifier = Modifier.height(2.dp))
                Text(
                    text = "Adm: ${child.admissionNumber} • ${child.className} (${child.streamName})",
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )
            }
        }
    }
}

@Composable
private fun NotificationCard(notification: NotificationItem) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(12.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.3f)),
        elevation = CardDefaults.cardElevation(defaultElevation = 0.dp)
    ) {
        Column(
            modifier = Modifier.padding(12.dp),
            verticalArrangement = Arrangement.spacedBy(4.dp)
        ) {
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Text(
                    text = notification.title,
                    style = MaterialTheme.typography.titleSmall,
                    fontWeight = FontWeight.Bold,
                    color = MaterialTheme.colorScheme.onSurface,
                    modifier = Modifier.weight(1f)
                )
                Text(
                    text = notification.createdAt,
                    style = MaterialTheme.typography.labelSmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )
            }
            Text(
                text = notification.message,
                style = MaterialTheme.typography.bodySmall,
                color = MaterialTheme.colorScheme.onSurfaceVariant
            )
        }
    }
}

@Composable
private fun SectionHeading(title: String) {
    Text(
        text = title,
        style = MaterialTheme.typography.titleMedium,
        fontWeight = FontWeight.Bold,
        color = MaterialTheme.colorScheme.onBackground
    )
}

@Composable
private fun InfoRow(label: String, value: String) {
    Row(
        modifier = Modifier.fillMaxWidth(),
        horizontalArrangement = Arrangement.SpaceBetween,
        verticalAlignment = Alignment.CenterVertically
    ) {
        Text(
            text = label,
            style = MaterialTheme.typography.bodyMedium,
            color = MaterialTheme.colorScheme.onSurfaceVariant
        )
        Spacer(modifier = Modifier.width(12.dp))
        Text(
            text = value,
            style = MaterialTheme.typography.bodyMedium,
            fontWeight = FontWeight.SemiBold,
            color = MaterialTheme.colorScheme.onSurface,
            maxLines = 2,
            overflow = TextOverflow.Ellipsis
        )
    }
}
