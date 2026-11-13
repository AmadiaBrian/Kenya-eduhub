package com.example.kenyaeduhub.ui.dashboard

import android.app.Activity
import android.content.Intent
import android.net.Uri
import android.widget.Toast
import androidx.activity.compose.BackHandler
import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.Logout
import androidx.compose.material.icons.automirrored.filled.MenuBook
import androidx.compose.material.icons.filled.Dashboard
import androidx.compose.material.icons.filled.Download
import androidx.compose.material.icons.filled.Folder
import androidx.compose.material.icons.filled.FolderOpen
import androidx.compose.material.icons.filled.Lock
import androidx.compose.material.icons.filled.Menu
import androidx.compose.material.icons.filled.OpenInNew
import androidx.compose.material.icons.filled.Person
import androidx.compose.material.icons.filled.Search
import androidx.compose.material.icons.filled.Settings
import androidx.compose.material.icons.filled.Star
import androidx.compose.material.icons.filled.Upload
import androidx.compose.material3.*
import androidx.compose.material3.pulltorefresh.PullToRefreshBox
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.example.kenyaeduhub.data.ResourceItem
import com.example.kenyaeduhub.data.User
import com.example.kenyaeduhub.ui.profile.ProfileScreen
import com.example.kenyaeduhub.ui.settings.SettingsScreen
import com.example.kenyaeduhub.ui.theme.getAppThemeColors
import com.example.kenyaeduhub.ui.upload.UploadScreen
import com.example.kenyaeduhub.utils.downloadResourceFile
import kotlinx.coroutines.launch

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun DashboardScreen(
    user: User,
    viewModel: DashboardViewModel,
    onLogout: () -> Unit
) {
    val context = LocalContext.current
    val drawerState = rememberDrawerState(initialValue = DrawerValue.Closed)
    val scope = rememberCoroutineScope()

    val isLoading by viewModel.isLoading.collectAsState()
    val dashboardData by viewModel.dashboardData.collectAsState()
    val searchQuery by viewModel.searchQuery.collectAsState()

    // Screen State: 0 -> Dashboard, 1 -> My Resources, 2 -> Upload Resource, 3 -> Profile, 4 -> Settings
    var currentSection by remember { mutableIntStateOf(0) }
    var lastBackPressTime by remember { mutableLongStateOf(0L) }

    BackHandler {
        when {
            drawerState.isOpen -> {
                scope.launch { drawerState.close() }
            }
            currentSection != 0 -> {
                currentSection = 0
            }
            else -> {
                val currentTime = System.currentTimeMillis()
                if (currentTime - lastBackPressTime < 2000L) {
                    (context as? Activity)?.finish()
                } else {
                    lastBackPressTime = currentTime
                    com.example.kenyaeduhub.utils.triggerExitVibration(context)
                    Toast.makeText(context, "Press back again to exit", Toast.LENGTH_SHORT).show()
                }
            }
        }
    }

    LaunchedEffect(user) {
        viewModel.loadDashboard(context, user)
    }

    val themeColors = getAppThemeColors()
    val pageBgColor = themeColors.pageBgColor
    val cardBgColor = themeColors.cardBgColor
    val borderColor = themeColors.borderColor
    val activeBorder = if (themeColors.isDark) null else BorderStroke(1.dp, borderColor)

    ModalNavigationDrawer(
        drawerState = drawerState,
        drawerContent = {
            ModalDrawerSheet(
                drawerContainerColor = pageBgColor,
                drawerShape = RoundedCornerShape(topEnd = 0.dp, bottomEnd = 0.dp),
                windowInsets = WindowInsets(0, 0, 0, 0),
                modifier = Modifier
                    .width(280.dp)
                    .fillMaxHeight()
                    .background(pageBgColor)
            ) {
                Column(
                    modifier = Modifier
                        .fillMaxSize()
                        .background(pageBgColor)
                        .statusBarsPadding()
                        .navigationBarsPadding()
                        .padding(16.dp)
                ) {
                    // Drawer Header (Logo only)
                    Row(
                        verticalAlignment = Alignment.CenterVertically,
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(bottom = 20.dp, top = 8.dp)
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
                        Spacer(modifier = Modifier.width(12.dp))
                        Row {
                            Text("Kenya", fontSize = 18.sp, fontWeight = FontWeight.Bold, color = Color(0xFFFF6B35))
                            Spacer(modifier = Modifier.width(4.dp))
                            Text("EduHub", fontSize = 18.sp, fontWeight = FontWeight.Bold, color = Color(0xFF008000))
                        }
                    }

                    HorizontalDivider(color = if (themeColors.isDark) Color(0xFF2A2A2A) else borderColor, modifier = Modifier.padding(bottom = 12.dp))

                    // Sidebar Navigation Links
                    val drawerIconTint = if (themeColors.isDark) Color.White else Color(0xFF5F6368)

                    NavigationDrawerItem(
                        icon = { Icon(Icons.Default.Dashboard, contentDescription = "Dashboard", tint = drawerIconTint) },
                        label = { Text("Dashboard", fontWeight = if (currentSection == 0) FontWeight.Bold else FontWeight.Medium, color = themeColors.primaryText) },
                        selected = currentSection == 0,
                        onClick = {
                            currentSection = 0
                            scope.launch { drawerState.close() }
                        },
                        colors = NavigationDrawerItemDefaults.colors(selectedContainerColor = if (themeColors.isDark) Color(0xFF2A2A2A) else Color(0xFFE8F0FE))
                    )

                    NavigationDrawerItem(
                        icon = { Icon(Icons.AutoMirrored.Filled.MenuBook, contentDescription = "My Resources", tint = drawerIconTint) },
                        label = { Text("My Resources", fontWeight = if (currentSection == 1) FontWeight.Bold else FontWeight.Medium, color = themeColors.primaryText) },
                        selected = currentSection == 1,
                        onClick = {
                            currentSection = 1
                            scope.launch { drawerState.close() }
                        },
                        colors = NavigationDrawerItemDefaults.colors(selectedContainerColor = if (themeColors.isDark) Color(0xFF2A2A2A) else Color(0xFFE8F0FE))
                    )

                    NavigationDrawerItem(
                        icon = { Icon(Icons.Default.Upload, contentDescription = "Upload Resource", tint = drawerIconTint) },
                        label = { Text("Upload Resource", fontWeight = if (currentSection == 2) FontWeight.Bold else FontWeight.Medium, color = themeColors.primaryText) },
                        selected = currentSection == 2,
                        onClick = {
                            currentSection = 2
                            scope.launch { drawerState.close() }
                        },
                        colors = NavigationDrawerItemDefaults.colors(selectedContainerColor = if (themeColors.isDark) Color(0xFF2A2A2A) else Color(0xFFE8F0FE))
                    )

                    NavigationDrawerItem(
                        icon = { Icon(Icons.Default.OpenInNew, contentDescription = "More Resources", tint = drawerIconTint) },
                        label = { Text("More Resources", color = themeColors.primaryText) },
                        selected = false,
                        onClick = {
                            scope.launch { drawerState.close() }
                            val intent = Intent(Intent.ACTION_VIEW, Uri.parse("https://sites.google.com/view/noteselectricalengineering/home"))
                            context.startActivity(intent)
                        }
                    )

                    NavigationDrawerItem(
                        icon = { Icon(Icons.Default.Person, contentDescription = "Profile", tint = drawerIconTint) },
                        label = { Text("Profile", fontWeight = if (currentSection == 3) FontWeight.Bold else FontWeight.Medium, color = themeColors.primaryText) },
                        selected = currentSection == 3,
                        onClick = {
                            currentSection = 3
                            scope.launch { drawerState.close() }
                        },
                        colors = NavigationDrawerItemDefaults.colors(selectedContainerColor = if (themeColors.isDark) Color(0xFF2A2A2A) else Color(0xFFE8F0FE))
                    )

                    NavigationDrawerItem(
                        icon = { Icon(Icons.Default.Settings, contentDescription = "Settings", tint = drawerIconTint) },
                        label = { Text("Settings", fontWeight = if (currentSection == 4) FontWeight.Bold else FontWeight.Medium, color = themeColors.primaryText) },
                        selected = currentSection == 4,
                        onClick = {
                            currentSection = 4
                            scope.launch { drawerState.close() }
                        },
                        colors = NavigationDrawerItemDefaults.colors(selectedContainerColor = if (themeColors.isDark) Color(0xFF2A2A2A) else Color(0xFFE8F0FE))
                    )

                    Spacer(modifier = Modifier.weight(1f))

                    HorizontalDivider(color = if (themeColors.isDark) Color(0xFF2A2A2A) else borderColor, modifier = Modifier.padding(vertical = 12.dp))

                    NavigationDrawerItem(
                        icon = { Icon(Icons.AutoMirrored.Filled.Logout, contentDescription = "Logout", tint = Color(0xFFC5221F)) },
                        label = { Text("Logout", color = Color(0xFFC5221F), fontWeight = FontWeight.Bold) },
                        selected = false,
                        onClick = {
                            scope.launch { drawerState.close() }
                            onLogout()
                        }
                    )
                }
            }
        }
    ) {
        Scaffold(
            topBar = {
                Surface(
                    color = pageBgColor,
                    shadowElevation = 0.dp,
                    modifier = Modifier.statusBarsPadding()
                ) {
                    Row(
                        modifier = Modifier
                            .fillMaxWidth()
                            .background(pageBgColor)
                            .padding(horizontal = 16.dp, vertical = 8.dp),
                        verticalAlignment = Alignment.CenterVertically,
                        horizontalArrangement = Arrangement.SpaceBetween
                    ) {
                        // LEFT SIDE: Logo Badge
                        Row(verticalAlignment = Alignment.CenterVertically) {
                            Box(
                                modifier = Modifier
                                    .size(36.dp)
                                    .background(Color(0xFFffc107), CircleShape)
                                    .border(2.dp, Color(0xFFFF6B35), CircleShape),
                                contentAlignment = Alignment.Center
                            ) {
                                Row(
                                    verticalAlignment = Alignment.CenterVertically,
                                    horizontalArrangement = Arrangement.Center
                                ) {
                                    Text("K", color = Color(0xFFFF6B35), fontSize = 20.sp, fontWeight = FontWeight.Bold)
                                    Text("E", color = Color(0xFF008000), fontSize = 16.sp, fontWeight = FontWeight.Bold)
                                }
                            }
                            Spacer(modifier = Modifier.width(8.dp))
                            Text("Kenya", fontSize = 18.sp, fontWeight = FontWeight.Bold, color = Color(0xFFFF6B35))
                            Spacer(modifier = Modifier.width(4.dp))
                            Text("EduHub", fontSize = 18.sp, fontWeight = FontWeight.Bold, color = Color(0xFF008000))
                        }

                        // RIGHT SIDE: Sidebar Menu Hamburger Button
                        IconButton(onClick = { scope.launch { drawerState.open() } }) {
                            Icon(
                                imageVector = Icons.Default.Menu,
                                contentDescription = "Open Sidebar",
                                tint = themeColors.primaryText
                            )
                        }
                    }
                }
            },
            bottomBar = {
                NavigationBar(
                    containerColor = pageBgColor,
                    contentColor = Color(0xFFFF6B35),
                    modifier = Modifier
                        .border(if (themeColors.isDark) BorderStroke(0.dp, Color.Transparent) else BorderStroke(1.dp, borderColor))
                        .navigationBarsPadding()
                ) {
                    NavigationBarItem(
                        selected = currentSection == 0,
                        onClick = { currentSection = 0 },
                        icon = { Icon(Icons.Default.Dashboard, contentDescription = "Dashboard") },
                        label = { Text("Dashboard", fontSize = 10.sp, color = themeColors.primaryText) },
                        colors = NavigationBarItemDefaults.colors(
                            selectedIconColor = Color(0xFFFF6B35),
                            selectedTextColor = Color(0xFFFF6B35),
                            unselectedIconColor = themeColors.secondaryText,
                            unselectedTextColor = themeColors.secondaryText,
                            indicatorColor = if (themeColors.isDark) Color(0xFF2A2A2A) else Color(0xFFFFF0E6)
                        )
                    )
                    NavigationBarItem(
                        selected = currentSection == 1,
                        onClick = { currentSection = 1 },
                        icon = { Icon(Icons.AutoMirrored.Filled.MenuBook, contentDescription = "Resources") },
                        label = { Text("Resources", fontSize = 10.sp, color = themeColors.primaryText) },
                        colors = NavigationBarItemDefaults.colors(
                            selectedIconColor = Color(0xFFFF6B35),
                            selectedTextColor = Color(0xFFFF6B35),
                            unselectedIconColor = themeColors.secondaryText,
                            unselectedTextColor = themeColors.secondaryText,
                            indicatorColor = if (themeColors.isDark) Color(0xFF2A2A2A) else Color(0xFFFFF0E6)
                        )
                    )
                    NavigationBarItem(
                        selected = currentSection == 2,
                        onClick = { currentSection = 2 },
                        icon = { Icon(Icons.Default.Upload, contentDescription = "Upload") },
                        label = { Text("Upload", fontSize = 10.sp, color = themeColors.primaryText) },
                        colors = NavigationBarItemDefaults.colors(
                            selectedIconColor = Color(0xFFFF6B35),
                            selectedTextColor = Color(0xFFFF6B35),
                            unselectedIconColor = themeColors.secondaryText,
                            unselectedTextColor = themeColors.secondaryText,
                            indicatorColor = if (themeColors.isDark) Color(0xFF2A2A2A) else Color(0xFFFFF0E6)
                        )
                    )
                    NavigationBarItem(
                        selected = currentSection == 3,
                        onClick = { currentSection = 3 },
                        icon = { Icon(Icons.Default.Person, contentDescription = "Profile") },
                        label = { Text("Profile", fontSize = 10.sp, color = themeColors.primaryText) },
                        colors = NavigationBarItemDefaults.colors(
                            selectedIconColor = Color(0xFFFF6B35),
                            selectedTextColor = Color(0xFFFF6B35),
                            unselectedIconColor = themeColors.secondaryText,
                            unselectedTextColor = themeColors.secondaryText,
                            indicatorColor = if (themeColors.isDark) Color(0xFF2A2A2A) else Color(0xFFFFF0E6)
                        )
                    )
                    NavigationBarItem(
                        selected = currentSection == 4,
                        onClick = { currentSection = 4 },
                        icon = { Icon(Icons.Default.Settings, contentDescription = "Settings") },
                        label = { Text("Settings", fontSize = 10.sp, color = themeColors.primaryText) },
                        colors = NavigationBarItemDefaults.colors(
                            selectedIconColor = Color(0xFFFF6B35),
                            selectedTextColor = Color(0xFFFF6B35),
                            unselectedIconColor = themeColors.secondaryText,
                            unselectedTextColor = themeColors.secondaryText,
                            indicatorColor = if (themeColors.isDark) Color(0xFF2A2A2A) else Color(0xFFFFF0E6)
                        )
                    )
                }
            },
            containerColor = pageBgColor
        ) { innerPadding ->
            Box(
                modifier = Modifier
                    .fillMaxSize()
                    .padding(innerPadding)
            ) {
                val data = dashboardData
                val userUploadCount = data?.stats?.myResourcesCount ?: 0

                when (currentSection) {
                    2 -> {
                        // Independent Upload Screen
                        UploadScreen(
                            user = user,
                            viewModel = viewModel,
                            userUploadCount = userUploadCount,
                            cardBgColor = cardBgColor,
                            borderColor = borderColor
                        )
                    }
                    3 -> {
                        // Independent Profile Screen
                        ProfileScreen(
                            user = user,
                            viewModel = viewModel,
                            cardBgColor = cardBgColor,
                            borderColor = borderColor
                        )
                    }
                    4 -> {
                        // Independent Settings Screen
                        SettingsScreen(
                            user = user,
                            viewModel = viewModel,
                            onLogout = onLogout,
                            cardBgColor = cardBgColor,
                            borderColor = borderColor
                        )
                    }
                    else -> {
                        // Sections 0 & 1: Dashboard Overview and My Resources
                        PullToRefreshBox(
                            isRefreshing = isLoading,
                            onRefresh = { viewModel.loadDashboard(context, user) },
                            modifier = Modifier.fillMaxSize()
                        ) {
                            LazyColumn(
                                modifier = Modifier
                                    .fillMaxSize()
                                    .padding(horizontal = 16.dp),
                                contentPadding = PaddingValues(vertical = 16.dp),
                                verticalArrangement = Arrangement.spacedBy(16.dp)
                            ) {
                                // SECTION 0: Dashboard Main View
                                if (currentSection == 0) {
                                    // Welcome Banner Card
                                    item {
                                        Card(
                                            modifier = Modifier.fillMaxWidth(),
                                            shape = RoundedCornerShape(16.dp),
                                            colors = CardDefaults.cardColors(containerColor = cardBgColor),
                                            border = activeBorder
                                        ) {
                                            Column(
                                                modifier = Modifier
                                                    .fillMaxWidth()
                                                    .padding(20.dp)
                                            ) {
                                                Text(
                                                    text = "Welcome back,",
                                                    fontSize = 13.sp,
                                                    color = themeColors.secondaryText
                                                )
                                                Text(
                                                    text = data?.user?.name ?: user.name,
                                                    fontSize = 20.sp,
                                                    fontWeight = FontWeight.Bold,
                                                    color = themeColors.primaryText
                                                )

                                                Spacer(modifier = Modifier.height(12.dp))

                                                Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                                                    BadgeTag(
                                                        text = data?.activityLevel ?: "Member",
                                                        bgColor = if (themeColors.isDark) Color(0xFF1E2A3A) else Color(0xFFE8F0FE),
                                                        textColor = Color(0xFF1A73E8)
                                                    )
                                                    BadgeTag(
                                                        text = "Member: ${data?.accountAge ?: "New"}",
                                                        bgColor = if (themeColors.isDark) Color(0xFF1B382B) else Color(0xFFE6F4EA),
                                                        textColor = Color(0xFF137333)
                                                    )
                                                    BadgeTag(
                                                        text = (data?.user?.role ?: user.role).replaceFirstChar { it.uppercase() },
                                                        bgColor = if (themeColors.isDark) Color(0xFF332018) else Color(0xFFFFF0E6),
                                                        textColor = Color(0xFFFF6B35)
                                                    )
                                                }
                                            }
                                        }
                                    }

                                    // Statistics Cards Grid
                                    item {
                                        Column(verticalArrangement = Arrangement.spacedBy(12.dp)) {
                                            Row(
                                                modifier = Modifier.fillMaxWidth(),
                                                horizontalArrangement = Arrangement.spacedBy(12.dp)
                                            ) {
                                                StatCard(
                                                    modifier = Modifier.weight(1f),
                                                    icon = Icons.Default.FolderOpen,
                                                    title = "My Resources",
                                                    value = "${data?.stats?.myResourcesCount ?: 0}",
                                                    subtitle = "${data?.stats?.totalDownloads ?: 0} downloads",
                                                    accentColor = Color(0xFFFF6B35),
                                                    bgColor = cardBgColor,
                                                    borderColor = borderColor
                                                )
                                                StatCard(
                                                    modifier = Modifier.weight(1f),
                                                    icon = Icons.AutoMirrored.Filled.MenuBook,
                                                    title = "Recent Uploads",
                                                    value = "${data?.stats?.recentUploads ?: 0}",
                                                    subtitle = "This week",
                                                    accentColor = Color(0xFF008000),
                                                    bgColor = cardBgColor,
                                                    borderColor = borderColor
                                                )
                                            }
                                            Row(
                                                modifier = Modifier.fillMaxWidth(),
                                                horizontalArrangement = Arrangement.spacedBy(12.dp)
                                            ) {
                                                StatCard(
                                                    modifier = Modifier.weight(1f),
                                                    icon = Icons.Default.Star,
                                                    title = "All Resources",
                                                    value = "${data?.stats?.allResourcesCount ?: 0}",
                                                    subtitle = "Platform total",
                                                    accentColor = Color(0xFF1A73E8),
                                                    bgColor = cardBgColor,
                                                    borderColor = borderColor
                                                )
                                            }
                                        }
                                    }

                                    // Browse All Section Header
                                    item {
                                        Text(
                                            text = "Browse All Resources",
                                            fontSize = 18.sp,
                                            fontWeight = FontWeight.Bold,
                                            color = themeColors.primaryText
                                        )
                                    }

                                    // Search Filter Box
                                    item {
                                        OutlinedTextField(
                                            value = searchQuery,
                                            onValueChange = { viewModel.searchQuery.value = it },
                                            placeholder = { Text("Search all resources...") },
                                            leadingIcon = {
                                                Icon(
                                                    imageVector = Icons.Default.Search,
                                                    contentDescription = "Search",
                                                    tint = themeColors.secondaryText,
                                                    modifier = Modifier.size(20.dp)
                                                )
                                            },
                                            singleLine = true,
                                            shape = RoundedCornerShape(25.dp),
                                            colors = OutlinedTextFieldDefaults.colors(
                                                focusedBorderColor = Color(0xFFFF6B35),
                                                unfocusedBorderColor = themeColors.formBorderColor,
                                                focusedContainerColor = cardBgColor,
                                                unfocusedContainerColor = cardBgColor,
                                                focusedTextColor = themeColors.primaryText,
                                                unfocusedTextColor = themeColors.primaryText
                                            ),
                                            modifier = Modifier.fillMaxWidth()
                                        )
                                    }

                                    val allResList = (data?.allResources ?: emptyList()).filter { item ->
                                        searchQuery.isBlank() ||
                                                item.title.contains(searchQuery, ignoreCase = true) ||
                                                item.subject.contains(searchQuery, ignoreCase = true) ||
                                                item.uploaderName.contains(searchQuery, ignoreCase = true)
                                    }

                                    items(allResList) { resource ->
                                        ResourceCardItem(
                                            resource = resource,
                                            userUploadCount = userUploadCount,
                                            currentUser = user,
                                            cardBgColor = cardBgColor,
                                            borderColor = borderColor,
                                            onUploadRequiredClick = { currentSection = 2 }
                                        )
                                    }
                                }

                                // SECTION 1: My Resources View
                                if (currentSection == 1) {
                                    item {
                                        Text(
                                            text = "My Resources (${data?.myResources?.size ?: 0})",
                                            fontSize = 20.sp,
                                            fontWeight = FontWeight.Bold,
                                            color = themeColors.primaryText
                                        )
                                    }

                                    if (userUploadCount < 2) {
                                        item {
                                            Card(
                                                modifier = Modifier.fillMaxWidth(),
                                                shape = RoundedCornerShape(12.dp),
                                                colors = CardDefaults.cardColors(containerColor = if (themeColors.isDark) Color(0xFF1E2A3A) else Color(0xFFE8F0FE)),
                                                border = if (themeColors.isDark) null else BorderStroke(1.dp, Color(0xFF1967D2))
                                            ) {
                                                Row(
                                                    modifier = Modifier
                                                        .fillMaxWidth()
                                                        .padding(16.dp),
                                                    verticalAlignment = Alignment.CenterVertically,
                                                    horizontalArrangement = Arrangement.SpaceBetween
                                                ) {
                                                    Column(modifier = Modifier.weight(1f)) {
                                                        Text(
                                                            text = "Upload Requirement",
                                                            fontWeight = FontWeight.Bold,
                                                            fontSize = 14.sp,
                                                            color = Color(0xFF1967D2)
                                                        )
                                                        Spacer(modifier = Modifier.height(4.dp))
                                                        Text(
                                                            text = "Upload at least 2 resources to unlock downloads. You have uploaded $userUploadCount.",
                                                            fontSize = 12.sp,
                                                            color = themeColors.primaryText
                                                        )
                                                    }
                                                    Button(
                                                        onClick = { currentSection = 2 },
                                                        shape = RoundedCornerShape(25.dp),
                                                        colors = ButtonDefaults.buttonColors(containerColor = Color(0xFFFF6B35))
                                                    ) {
                                                        Text("Upload", fontSize = 12.sp, fontWeight = FontWeight.Bold)
                                                    }
                                                }
                                            }
                                        }
                                    }

                                    val myResList = (data?.myResources ?: emptyList()).filter { item ->
                                        searchQuery.isBlank() ||
                                                item.title.contains(searchQuery, ignoreCase = true) ||
                                                item.subject.contains(searchQuery, ignoreCase = true)
                                    }

                                    if (myResList.isEmpty()) {
                                        item {
                                            Card(
                                                modifier = Modifier.fillMaxWidth(),
                                                shape = RoundedCornerShape(12.dp),
                                                colors = CardDefaults.cardColors(containerColor = cardBgColor),
                                                border = activeBorder
                                            ) {
                                                Box(
                                                    modifier = Modifier
                                                        .fillMaxWidth()
                                                        .padding(32.dp),
                                                    contentAlignment = Alignment.Center
                                                ) {
                                                    Text(
                                                        text = "You haven't uploaded any resources yet.",
                                                        color = themeColors.secondaryText,
                                                        fontSize = 14.sp
                                                    )
                                                }
                                            }
                                        }
                                    } else {
                                        items(myResList) { resource ->
                                            ResourceCardItem(
                                                resource = resource,
                                                userUploadCount = userUploadCount,
                                                currentUser = user,
                                                cardBgColor = cardBgColor,
                                                borderColor = borderColor,
                                                onUploadRequiredClick = { currentSection = 2 }
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
    }
}

@Composable
fun BadgeTag(text: String, bgColor: Color, textColor: Color) {
    Box(
        modifier = Modifier
            .clip(RoundedCornerShape(12.dp))
            .background(bgColor)
            .padding(horizontal = 10.dp, vertical = 4.dp)
    ) {
        Text(
            text = text,
            color = textColor,
            fontSize = 11.sp,
            fontWeight = FontWeight.Medium
        )
    }
}

@Composable
fun StatCard(
    modifier: Modifier = Modifier,
    icon: ImageVector,
    title: String,
    value: String,
    subtitle: String,
    accentColor: Color,
    bgColor: Color,
    borderColor: Color
) {
    val themeColors = getAppThemeColors()
    Card(
        modifier = modifier,
        shape = RoundedCornerShape(16.dp),
        colors = CardDefaults.cardColors(containerColor = bgColor),
        border = if (themeColors.isDark) null else BorderStroke(1.dp, borderColor)
    ) {
        Column(
            modifier = Modifier
                .fillMaxWidth()
                .padding(16.dp)
        ) {
            Row(
                verticalAlignment = Alignment.CenterVertically,
                horizontalArrangement = Arrangement.SpaceBetween,
                modifier = Modifier.fillMaxWidth()
            ) {
                Text(text = title, fontSize = 12.sp, color = themeColors.secondaryText, fontWeight = FontWeight.Medium)
                Icon(
                    imageVector = icon,
                    contentDescription = title,
                    tint = accentColor,
                    modifier = Modifier.size(20.dp)
                )
            }
            Spacer(modifier = Modifier.height(6.dp))
            Text(text = value, fontSize = 24.sp, fontWeight = FontWeight.Bold, color = accentColor)
            Spacer(modifier = Modifier.height(4.dp))
            Text(text = subtitle, fontSize = 11.sp, color = themeColors.secondaryText)
        }
    }
}

@Composable
fun ResourceCardItem(
    resource: ResourceItem,
    userUploadCount: Int,
    currentUser: User,
    cardBgColor: Color,
    borderColor: Color,
    onUploadRequiredClick: () -> Unit
) {
    val themeColors = getAppThemeColors()
    val fileTypeColor = when (resource.fileType.uppercase()) {
        "PDF" -> Color(0xFFD32F2F)
        "DOC", "DOCX" -> Color(0xFF1976D2)
        "PPT", "PPTX" -> Color(0xFFF57C00)
        "XLS", "XLSX" -> Color(0xFF388E3C)
        else -> Color(0xFFFF6B35)
    }

    val isOwner = resource.uploaderName.equals(currentUser.name, ignoreCase = true)
    val canDownload = userUploadCount >= 2 || isOwner

    Card(
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(12.dp),
        colors = CardDefaults.cardColors(containerColor = cardBgColor),
        border = if (themeColors.isDark) null else BorderStroke(1.dp, borderColor)
    ) {
        Column(
            modifier = Modifier
                .fillMaxWidth()
                .padding(16.dp)
        ) {
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Text(
                    text = resource.title,
                    fontSize = 16.sp,
                    fontWeight = FontWeight.Bold,
                    color = themeColors.primaryText,
                    maxLines = 1,
                    overflow = TextOverflow.Ellipsis,
                    modifier = Modifier.weight(1f)
                )
                Spacer(modifier = Modifier.width(8.dp))
                Box(
                    modifier = Modifier
                        .clip(RoundedCornerShape(8.dp))
                        .background(fileTypeColor.copy(alpha = 0.15f))
                        .padding(horizontal = 8.dp, vertical = 2.dp)
                ) {
                    Text(
                        text = resource.fileType,
                        color = fileTypeColor,
                        fontSize = 11.sp,
                        fontWeight = FontWeight.Bold
                    )
                }
            }

            Spacer(modifier = Modifier.height(10.dp))

            // Display ALL document details using Google Material Icons
            Row(verticalAlignment = Alignment.CenterVertically) {
                Icon(
                    imageVector = Icons.Default.Folder,
                    contentDescription = "Subject",
                    tint = Color(0xFFFF6B35),
                    modifier = Modifier.size(16.dp)
                )
                Spacer(modifier = Modifier.width(6.dp))
                Text(
                    text = "Subject: ${resource.subject}",
                    fontSize = 13.sp,
                    fontWeight = FontWeight.Medium,
                    color = themeColors.secondaryText
                )
            }

            Spacer(modifier = Modifier.height(6.dp))

            Row(verticalAlignment = Alignment.CenterVertically) {
                Icon(
                    imageVector = Icons.Default.Person,
                    contentDescription = "Uploader",
                    tint = Color(0xFF008000),
                    modifier = Modifier.size(16.dp)
                )
                Spacer(modifier = Modifier.width(6.dp))
                Text(
                    text = "Uploaded by: ${resource.uploaderName}",
                    fontSize = 13.sp,
                    fontWeight = FontWeight.Medium,
                    color = Color(0xFF008000)
                )
            }

            if (resource.description.isNotBlank()) {
                Spacer(modifier = Modifier.height(8.dp))
                Text(
                    text = resource.description,
                    fontSize = 12.sp,
                    color = themeColors.secondaryText,
                    maxLines = 3,
                    overflow = TextOverflow.Ellipsis
                )
            }

            Spacer(modifier = Modifier.height(14.dp))

            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Row(verticalAlignment = Alignment.CenterVertically) {
                    Icon(
                        imageVector = Icons.Default.Download,
                        contentDescription = "Downloads",
                        tint = Color(0xFF1A73E8),
                        modifier = Modifier.size(16.dp)
                    )
                    Spacer(modifier = Modifier.width(4.dp))
                    Text(
                        text = "${resource.downloads} downloads",
                        fontSize = 13.sp,
                        fontWeight = FontWeight.Medium,
                        color = Color(0xFF1A73E8)
                    )
                }

                // Download / Locked Button
                val context = LocalContext.current
                if (canDownload) {
                    OutlinedButton(
                        onClick = { downloadResourceFile(context, resource) },
                        shape = RoundedCornerShape(25.dp),
                        colors = ButtonDefaults.outlinedButtonColors(
                            containerColor = if (themeColors.isDark) Color(0xFF2A2A2A) else Color(0xFFF1F3F4),
                            contentColor = themeColors.primaryText
                        ),
                        border = BorderStroke(1.dp, if (themeColors.isDark) Color(0xFF3C4043) else Color(0xFF000000)),
                        contentPadding = PaddingValues(horizontal = 16.dp, vertical = 6.dp)
                    ) {
                        Icon(
                            imageVector = Icons.Default.Download,
                            contentDescription = "Download",
                            tint = themeColors.primaryText,
                            modifier = Modifier.size(16.dp)
                        )
                        Spacer(modifier = Modifier.width(6.dp))
                        Text(
                            text = "Download",
                            fontSize = 13.sp,
                            fontWeight = FontWeight.Medium,
                            color = themeColors.primaryText
                        )
                    }
                } else if (userUploadCount == 1) {
                    OutlinedButton(
                        onClick = onUploadRequiredClick,
                        shape = RoundedCornerShape(25.dp),
                        colors = ButtonDefaults.outlinedButtonColors(
                            containerColor = if (themeColors.isDark) Color(0xFF2A2A2A) else Color(0xFFF1F3F4),
                            contentColor = themeColors.primaryText
                        ),
                        border = BorderStroke(1.dp, if (themeColors.isDark) Color(0xFF3C4043) else Color(0xFF000000)),
                        contentPadding = PaddingValues(horizontal = 12.dp, vertical = 6.dp)
                    ) {
                        Icon(
                            imageVector = Icons.Default.Lock,
                            contentDescription = "Locked",
                            tint = themeColors.secondaryText,
                            modifier = Modifier.size(16.dp)
                        )
                        Spacer(modifier = Modifier.width(6.dp))
                        Text(
                            text = "Upload 1 more resource to download",
                            fontSize = 11.sp,
                            fontWeight = FontWeight.Medium,
                            textAlign = TextAlign.Center,
                            color = themeColors.primaryText
                        )
                    }
                } else {
                    OutlinedButton(
                        onClick = onUploadRequiredClick,
                        shape = RoundedCornerShape(25.dp),
                        colors = ButtonDefaults.outlinedButtonColors(
                            containerColor = if (themeColors.isDark) Color(0xFF2A2A2A) else Color(0xFFF1F3F4),
                            contentColor = themeColors.primaryText
                        ),
                        border = BorderStroke(1.dp, if (themeColors.isDark) Color(0xFF3C4043) else Color(0xFF000000)),
                        contentPadding = PaddingValues(horizontal = 12.dp, vertical = 6.dp)
                    ) {
                        Icon(
                            imageVector = Icons.Default.Lock,
                            contentDescription = "Locked",
                            tint = themeColors.secondaryText,
                            modifier = Modifier.size(16.dp)
                        )
                        Spacer(modifier = Modifier.width(6.dp))
                        Text(
                            text = "Upload 2 resources to download",
                            fontSize = 11.sp,
                            fontWeight = FontWeight.Medium,
                            textAlign = TextAlign.Center,
                            color = themeColors.primaryText
                        )
                    }
                }
            }
        }
    }
}
