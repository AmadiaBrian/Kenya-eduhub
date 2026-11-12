package com.example.kenyaeduhub.ui.dashboard

import android.content.Intent
import android.net.Uri
import android.widget.Toast
import androidx.compose.animation.core.*
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
import androidx.compose.material.icons.filled.Delete
import androidx.compose.material.icons.filled.Download
import androidx.compose.material.icons.filled.Folder
import androidx.compose.material.icons.filled.FolderOpen
import androidx.compose.material.icons.filled.Key
import androidx.compose.material.icons.filled.Lock
import androidx.compose.material.icons.filled.Menu
import androidx.compose.material.icons.filled.OpenInNew
import androidx.compose.material.icons.filled.Person
import androidx.compose.material.icons.filled.Refresh
import androidx.compose.material.icons.filled.Save
import androidx.compose.material.icons.filled.Search
import androidx.compose.material.icons.filled.Settings
import androidx.compose.material.icons.filled.Star
import androidx.compose.material.icons.filled.Upload
import androidx.compose.material.icons.filled.Warning
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.graphicsLayer
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.PasswordVisualTransformation
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.example.kenyaeduhub.data.ResourceItem
import com.example.kenyaeduhub.data.User
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
    val errorMessage by viewModel.errorMessage.collectAsState()
    val searchQuery by viewModel.searchQuery.collectAsState()

    val infiniteTransition = rememberInfiniteTransition(label = "RefreshSpin")
    val rotationAngle by infiniteTransition.animateFloat(
        initialValue = 0f,
        targetValue = 360f,
        animationSpec = infiniteRepeatable(
            animation = tween(1000, easing = LinearEasing),
            repeatMode = RepeatMode.Restart
        ),
        label = "Rotation"
    )

    // Screen State: 0 -> Dashboard, 1 -> My Resources, 2 -> Upload Resource, 3 -> Profile, 4 -> Settings
    var currentSection by remember { mutableIntStateOf(0) }

    // Upload Form State
    var uploadTitle by remember { mutableStateOf("") }
    var uploadLevel by remember { mutableStateOf("Secondary School") }
    var uploadSubject by remember { mutableStateOf("") }
    var uploadFileType by remember { mutableStateOf("PDF") }
    var uploadDescription by remember { mutableStateOf("") }
    var uploadMessage by remember { mutableStateOf<String?>(null) }
    var isUploadSuccess by remember { mutableStateOf(false) }

    val levelOptions = listOf("Primary School", "Secondary School", "College", "University")
    val fileTypeOptions = listOf("PDF", "DOC", "DOCX", "PPT", "PPTX", "XLS", "XLSX", "TXT")

    var levelExpanded by remember { mutableStateOf(false) }
    var typeExpanded by remember { mutableStateOf(false) }

    // Profile Form State
    val activeUser = dashboardData?.user ?: user
    var profileName by remember(activeUser) { mutableStateOf(activeUser.name) }
    var profileEmail by remember(activeUser) { mutableStateOf(activeUser.email) }
    var profilePhone by remember(activeUser) { mutableStateOf(activeUser.phone) }
    var profileMessage by remember { mutableStateOf<String?>(null) }
    var isProfileSuccess by remember { mutableStateOf(false) }

    // Change Password Form State
    var currentPassword by remember { mutableStateOf("") }
    var newPassword by remember { mutableStateOf("") }
    var confirmNewPassword by remember { mutableStateOf("") }
    var passwordMessage by remember { mutableStateOf<String?>(null) }
    var isPasswordSuccess by remember { mutableStateOf(false) }

    // Settings Form State
    var emailUploads by remember { mutableStateOf(true) }
    var emailComments by remember { mutableStateOf(true) }
    var emailUpdates by remember { mutableStateOf(false) }
    var showProfile by remember { mutableStateOf(true) }
    var showEmail by remember { mutableStateOf(false) }

    var selectedTheme by remember { mutableStateOf("Light Mode") }
    var selectedLanguage by remember { mutableStateOf("English") }
    var themeExpanded by remember { mutableStateOf(false) }
    var langExpanded by remember { mutableStateOf(false) }

    val themeOptions = listOf("Light Mode", "Dark Mode", "System Default")
    val langOptions = listOf("English", "Kiswahili")

    var settingsMessage by remember { mutableStateOf<String?>(null) }

    // Delete Account Modal Dialog State
    var showDeleteDialog by remember { mutableStateOf(false) }
    var confirmDeleteEmail by remember { mutableStateOf("") }
    var deletionReason by remember { mutableStateOf("") }
    var deleteAgreementChecked by remember { mutableStateOf(false) }
    var deleteErrorMsg by remember { mutableStateOf<String?>(null) }

    LaunchedEffect(user) {
        viewModel.loadDashboard(user)
    }

    val pageBgColor = Color(0xFFF0F4F9)
    val cardBgColor = Color(0xFFF0F4F9)
    val borderColor = Color(0xFFE8EAED)

    ModalNavigationDrawer(
        drawerState = drawerState,
        drawerContent = {
            ModalDrawerSheet(
                drawerContainerColor = pageBgColor,
                drawerShape = RoundedCornerShape(topEnd = 16.dp, bottomEnd = 16.dp),
                windowInsets = WindowInsets(0, 0, 0, 0),
                modifier = Modifier
                    .width(280.dp)
                    .fillMaxHeight()
            ) {
                Column(
                    modifier = Modifier
                        .fillMaxSize()
                        .statusBarsPadding()
                        .navigationBarsPadding()
                        .padding(16.dp)
                ) {
                    // Drawer Header (Logo only, user name removed)
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

                    HorizontalDivider(color = borderColor, modifier = Modifier.padding(bottom = 12.dp))

                    // Sidebar Navigation Links with Gray Icons (Matching PHP Sidebar)
                    NavigationDrawerItem(
                        icon = { Icon(Icons.Default.Dashboard, contentDescription = "Dashboard", tint = Color(0xFF5F6368)) },
                        label = { Text("Dashboard", fontWeight = if (currentSection == 0) FontWeight.Bold else FontWeight.Medium) },
                        selected = currentSection == 0,
                        onClick = {
                            currentSection = 0
                            scope.launch { drawerState.close() }
                        },
                        colors = NavigationDrawerItemDefaults.colors(selectedContainerColor = Color(0xFFE8F0FE))
                    )

                    NavigationDrawerItem(
                        icon = { Icon(Icons.AutoMirrored.Filled.MenuBook, contentDescription = "My Resources", tint = Color(0xFF5F6368)) },
                        label = { Text("My Resources", fontWeight = if (currentSection == 1) FontWeight.Bold else FontWeight.Medium) },
                        selected = currentSection == 1,
                        onClick = {
                            currentSection = 1
                            scope.launch { drawerState.close() }
                        },
                        colors = NavigationDrawerItemDefaults.colors(selectedContainerColor = Color(0xFFE8F0FE))
                    )

                    NavigationDrawerItem(
                        icon = { Icon(Icons.Default.Upload, contentDescription = "Upload Resource", tint = Color(0xFF5F6368)) },
                        label = { Text("Upload Resource", fontWeight = if (currentSection == 2) FontWeight.Bold else FontWeight.Medium) },
                        selected = currentSection == 2,
                        onClick = {
                            currentSection = 2
                            scope.launch { drawerState.close() }
                        },
                        colors = NavigationDrawerItemDefaults.colors(selectedContainerColor = Color(0xFFE8F0FE))
                    )

                    NavigationDrawerItem(
                        icon = { Icon(Icons.Default.OpenInNew, contentDescription = "More Resources", tint = Color(0xFF5F6368)) },
                        label = { Text("More Resources") },
                        selected = false,
                        onClick = {
                            scope.launch { drawerState.close() }
                            val intent = Intent(Intent.ACTION_VIEW, Uri.parse("https://sites.google.com/view/noteselectricalengineering/home"))
                            context.startActivity(intent)
                        }
                    )

                    NavigationDrawerItem(
                        icon = { Icon(Icons.Default.Person, contentDescription = "Profile", tint = Color(0xFF5F6368)) },
                        label = { Text("Profile", fontWeight = if (currentSection == 3) FontWeight.Bold else FontWeight.Medium) },
                        selected = currentSection == 3,
                        onClick = {
                            currentSection = 3
                            scope.launch { drawerState.close() }
                        },
                        colors = NavigationDrawerItemDefaults.colors(selectedContainerColor = Color(0xFFE8F0FE))
                    )

                    NavigationDrawerItem(
                        icon = { Icon(Icons.Default.Settings, contentDescription = "Settings", tint = Color(0xFF5F6368)) },
                        label = { Text("Settings", fontWeight = if (currentSection == 4) FontWeight.Bold else FontWeight.Medium) },
                        selected = currentSection == 4,
                        onClick = {
                            currentSection = 4
                            scope.launch { drawerState.close() }
                        },
                        colors = NavigationDrawerItemDefaults.colors(selectedContainerColor = Color(0xFFE8F0FE))
                    )

                    Spacer(modifier = Modifier.weight(1f))

                    HorizontalDivider(color = borderColor, modifier = Modifier.padding(vertical = 12.dp))

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
                            .padding(horizontal = 12.dp, vertical = 8.dp),
                        verticalAlignment = Alignment.CenterVertically,
                        horizontalArrangement = Arrangement.SpaceBetween
                    ) {
                        Row(verticalAlignment = Alignment.CenterVertically) {
                            IconButton(onClick = { scope.launch { drawerState.open() } }) {
                                Icon(Icons.Default.Menu, contentDescription = "Open Sidebar", tint = Color(0xFF202124))
                            }

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

                        IconButton(
                            onClick = {
                                viewModel.loadDashboard(user)
                                Toast.makeText(context, "Refreshing data...", Toast.LENGTH_SHORT).show()
                            }
                        ) {
                            Icon(
                                imageVector = Icons.Default.Refresh,
                                contentDescription = "Refresh",
                                tint = Color(0xFFFF6B35),
                                modifier = Modifier.graphicsLayer {
                                    rotationZ = if (isLoading) rotationAngle else 0f
                                }
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
                        .border(BorderStroke(1.dp, borderColor))
                        .navigationBarsPadding()
                ) {
                    NavigationBarItem(
                        selected = currentSection == 0,
                        onClick = { currentSection = 0 },
                        icon = { Icon(Icons.Default.Dashboard, contentDescription = "Dashboard") },
                        label = { Text("Dashboard", fontSize = 10.sp) },
                        colors = NavigationBarItemDefaults.colors(
                            selectedIconColor = Color(0xFFFF6B35),
                            selectedTextColor = Color(0xFFFF6B35),
                            indicatorColor = Color(0xFFFFF0E6)
                        )
                    )
                    NavigationBarItem(
                        selected = currentSection == 1,
                        onClick = { currentSection = 1 },
                        icon = { Icon(Icons.AutoMirrored.Filled.MenuBook, contentDescription = "Resources") },
                        label = { Text("Resources", fontSize = 10.sp) },
                        colors = NavigationBarItemDefaults.colors(
                            selectedIconColor = Color(0xFFFF6B35),
                            selectedTextColor = Color(0xFFFF6B35),
                            indicatorColor = Color(0xFFFFF0E6)
                        )
                    )
                    NavigationBarItem(
                        selected = currentSection == 2,
                        onClick = { currentSection = 2 },
                        icon = { Icon(Icons.Default.Upload, contentDescription = "Upload") },
                        label = { Text("Upload", fontSize = 10.sp) },
                        colors = NavigationBarItemDefaults.colors(
                            selectedIconColor = Color(0xFFFF6B35),
                            selectedTextColor = Color(0xFFFF6B35),
                            indicatorColor = Color(0xFFFFF0E6)
                        )
                    )
                    NavigationBarItem(
                        selected = currentSection == 3,
                        onClick = { currentSection = 3 },
                        icon = { Icon(Icons.Default.Person, contentDescription = "Profile") },
                        label = { Text("Profile", fontSize = 10.sp) },
                        colors = NavigationBarItemDefaults.colors(
                            selectedIconColor = Color(0xFFFF6B35),
                            selectedTextColor = Color(0xFFFF6B35),
                            indicatorColor = Color(0xFFFFF0E6)
                        )
                    )
                    NavigationBarItem(
                        selected = currentSection == 4,
                        onClick = { currentSection = 4 },
                        icon = { Icon(Icons.Default.Settings, contentDescription = "Settings") },
                        label = { Text("Settings", fontSize = 10.sp) },
                        colors = NavigationBarItemDefaults.colors(
                            selectedIconColor = Color(0xFFFF6B35),
                            selectedTextColor = Color(0xFFFF6B35),
                            indicatorColor = Color(0xFFFFF0E6)
                        )
                    )
                }
            },
            containerColor = pageBgColor
        ) { innerPadding ->
            Column(
                modifier = Modifier
                    .fillMaxSize()
                    .padding(innerPadding)
            ) {
                if (isLoading && dashboardData == null) {
                    Box(
                        modifier = Modifier.fillMaxSize(),
                        contentAlignment = Alignment.Center
                    ) {
                        Column(horizontalAlignment = Alignment.CenterHorizontally) {
                            CircularProgressIndicator(color = Color(0xFFFF6B35))
                            Spacer(modifier = Modifier.height(16.dp))
                            Text(text = "Loading dashboard...", color = Color(0xFF5F6368), fontSize = 14.sp)
                        }
                    }
                } else {
                    val data = dashboardData
                    val userUploadCount = data?.stats?.myResourcesCount ?: 0

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
                                    border = BorderStroke(1.dp, borderColor)
                                ) {
                                    Column(
                                        modifier = Modifier
                                            .fillMaxWidth()
                                            .padding(20.dp)
                                    ) {
                                        Text(
                                            text = "Welcome back,",
                                            fontSize = 13.sp,
                                            color = Color(0xFF5F6368)
                                        )
                                        Text(
                                            text = data?.user?.name ?: user.name,
                                            fontSize = 20.sp,
                                            fontWeight = FontWeight.Bold,
                                            color = Color(0xFF202124)
                                        )

                                        Spacer(modifier = Modifier.height(12.dp))

                                        Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                                            BadgeTag(
                                                text = data?.activityLevel ?: "Member",
                                                bgColor = Color(0xFFE8F0FE),
                                                textColor = Color(0xFF1A73E8)
                                            )
                                            BadgeTag(
                                                text = "Member: ${data?.accountAge ?: "New"}",
                                                bgColor = Color(0xFFE6F4EA),
                                                textColor = Color(0xFF137333)
                                            )
                                            BadgeTag(
                                                text = (data?.user?.role ?: user.role).replaceFirstChar { it.uppercase() },
                                                bgColor = Color(0xFFFFF0E6),
                                                textColor = Color(0xFFFF6B35)
                                            )
                                        }
                                    }
                                }
                            }

                            // Statistics Cards Grid using Google Material Icons
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
                                    color = Color(0xFF202124)
                                )
                            }

                            // Search Filter Box using Google Material Search Icon
                            item {
                                OutlinedTextField(
                                    value = searchQuery,
                                    onValueChange = { viewModel.searchQuery.value = it },
                                    placeholder = { Text("Search all resources...") },
                                    leadingIcon = {
                                        Icon(
                                            imageVector = Icons.Default.Search,
                                            contentDescription = "Search",
                                            tint = Color(0xFF5F6368),
                                            modifier = Modifier.size(20.dp)
                                        )
                                    },
                                    singleLine = true,
                                    shape = RoundedCornerShape(25.dp),
                                    colors = OutlinedTextFieldDefaults.colors(
                                        focusedBorderColor = Color(0xFFFF6B35),
                                        unfocusedBorderColor = Color(0xFFDADCE0),
                                        focusedContainerColor = cardBgColor,
                                        unfocusedContainerColor = cardBgColor
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
                                    color = Color(0xFF202124)
                                )
                            }

                            if (userUploadCount < 2) {
                                item {
                                    Card(
                                        modifier = Modifier.fillMaxWidth(),
                                        shape = RoundedCornerShape(12.dp),
                                        colors = CardDefaults.cardColors(containerColor = Color(0xFFE8F0FE)),
                                        border = BorderStroke(1.dp, Color(0xFF1967D2))
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
                                                    color = Color(0xFF202124)
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
                                        border = BorderStroke(1.dp, borderColor)
                                    ) {
                                        Box(
                                            modifier = Modifier
                                                .fillMaxWidth()
                                                .padding(32.dp),
                                            contentAlignment = Alignment.Center
                                        ) {
                                            Text(
                                                text = "You haven't uploaded any resources yet.",
                                                color = Color(0xFF5F6368),
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

                        // SECTION 2: Upload Resource Form View
                        if (currentSection == 2) {
                            item {
                                Card(
                                    modifier = Modifier.fillMaxWidth(),
                                    shape = RoundedCornerShape(16.dp),
                                    colors = CardDefaults.cardColors(containerColor = cardBgColor),
                                    border = BorderStroke(1.dp, borderColor)
                                ) {
                                    Column(
                                        modifier = Modifier
                                            .fillMaxWidth()
                                            .padding(20.dp),
                                        verticalArrangement = Arrangement.spacedBy(16.dp)
                                    ) {
                                        Text(
                                            text = "Upload Resource",
                                            fontSize = 20.sp,
                                            fontWeight = FontWeight.Bold,
                                            color = Color(0xFF202124)
                                        )

                                        // Title Input
                                        OutlinedTextField(
                                            value = uploadTitle,
                                            onValueChange = { uploadTitle = it },
                                            label = { Text("Resource Title *") },
                                            placeholder = { Text("Enter resource title") },
                                            singleLine = true,
                                            shape = RoundedCornerShape(25.dp),
                                            colors = OutlinedTextFieldDefaults.colors(
                                                focusedBorderColor = Color(0xFFFF6B35),
                                                unfocusedBorderColor = Color(0xFFDADCE0),
                                                focusedContainerColor = cardBgColor,
                                                unfocusedContainerColor = cardBgColor
                                            ),
                                            modifier = Modifier.fillMaxWidth()
                                        )

                                        // Education Level Selector
                                        ExposedDropdownMenuBox(
                                            expanded = levelExpanded,
                                            onExpandedChange = { levelExpanded = !levelExpanded },
                                            modifier = Modifier.fillMaxWidth()
                                        ) {
                                            OutlinedTextField(
                                                value = uploadLevel,
                                                onValueChange = {},
                                                readOnly = true,
                                                label = { Text("Education Level *") },
                                                trailingIcon = { ExposedDropdownMenuDefaults.TrailingIcon(expanded = levelExpanded) },
                                                shape = RoundedCornerShape(25.dp),
                                                colors = OutlinedTextFieldDefaults.colors(
                                                    focusedBorderColor = Color(0xFFFF6B35),
                                                    unfocusedBorderColor = Color(0xFFDADCE0),
                                                    focusedContainerColor = cardBgColor,
                                                    unfocusedContainerColor = cardBgColor
                                                ),
                                                modifier = Modifier
                                                    .menuAnchor(ExposedDropdownMenuAnchorType.PrimaryNotEditable)
                                                    .fillMaxWidth()
                                            )
                                            ExposedDropdownMenu(
                                                expanded = levelExpanded,
                                                onDismissRequest = { levelExpanded = false }
                                            ) {
                                                levelOptions.forEach { option ->
                                                    DropdownMenuItem(
                                                        text = { Text(option) },
                                                        onClick = {
                                                            uploadLevel = option
                                                            levelExpanded = false
                                                        }
                                                    )
                                                }
                                            }
                                        }

                                        // Subject Input
                                        OutlinedTextField(
                                            value = uploadSubject,
                                            onValueChange = { uploadSubject = it },
                                            label = { Text("Subject *") },
                                            placeholder = { Text("Enter subject name") },
                                            singleLine = true,
                                            shape = RoundedCornerShape(25.dp),
                                            colors = OutlinedTextFieldDefaults.colors(
                                                focusedBorderColor = Color(0xFFFF6B35),
                                                unfocusedBorderColor = Color(0xFFDADCE0),
                                                focusedContainerColor = cardBgColor,
                                                unfocusedContainerColor = cardBgColor
                                            ),
                                            modifier = Modifier.fillMaxWidth()
                                        )

                                        // File Type Selector
                                        ExposedDropdownMenuBox(
                                            expanded = typeExpanded,
                                            onExpandedChange = { typeExpanded = !typeExpanded },
                                            modifier = Modifier.fillMaxWidth()
                                        ) {
                                            OutlinedTextField(
                                                value = uploadFileType,
                                                onValueChange = {},
                                                readOnly = true,
                                                label = { Text("File Type *") },
                                                trailingIcon = { ExposedDropdownMenuDefaults.TrailingIcon(expanded = typeExpanded) },
                                                shape = RoundedCornerShape(25.dp),
                                                colors = OutlinedTextFieldDefaults.colors(
                                                    focusedBorderColor = Color(0xFFFF6B35),
                                                    unfocusedBorderColor = Color(0xFFDADCE0),
                                                    focusedContainerColor = cardBgColor,
                                                    unfocusedContainerColor = cardBgColor
                                                ),
                                                modifier = Modifier
                                                    .menuAnchor(ExposedDropdownMenuAnchorType.PrimaryNotEditable)
                                                    .fillMaxWidth()
                                            )
                                            ExposedDropdownMenu(
                                                expanded = typeExpanded,
                                                onDismissRequest = { typeExpanded = false }
                                            ) {
                                                fileTypeOptions.forEach { option ->
                                                    DropdownMenuItem(
                                                        text = { Text(option) },
                                                        onClick = {
                                                            uploadFileType = option
                                                            typeExpanded = false
                                                        }
                                                    )
                                                }
                                            }
                                        }

                                        // Description Input
                                        OutlinedTextField(
                                            value = uploadDescription,
                                            onValueChange = { uploadDescription = it },
                                            label = { Text("Description *") },
                                            placeholder = { Text("Brief description of the resource...") },
                                            maxLines = 3,
                                            shape = RoundedCornerShape(20.dp),
                                            colors = OutlinedTextFieldDefaults.colors(
                                                focusedBorderColor = Color(0xFFFF6B35),
                                                unfocusedBorderColor = Color(0xFFDADCE0),
                                                focusedContainerColor = cardBgColor,
                                                unfocusedContainerColor = cardBgColor
                                            ),
                                            modifier = Modifier.fillMaxWidth()
                                        )

                                        // Dashed Upload Drop Box with Google Material Icon
                                        Box(
                                            modifier = Modifier
                                                .fillMaxWidth()
                                                .clip(RoundedCornerShape(25.dp))
                                                .background(cardBgColor)
                                                .border(2.dp, Color(0xFFDADCE0), RoundedCornerShape(25.dp))
                                                .padding(24.dp),
                                            contentAlignment = Alignment.Center
                                        ) {
                                            Column(horizontalAlignment = Alignment.CenterHorizontally) {
                                                Icon(
                                                    imageVector = Icons.Default.FolderOpen,
                                                    contentDescription = "Upload Document",
                                                    tint = Color(0xFFFF6B35),
                                                    modifier = Modifier.size(36.dp)
                                                )
                                                Spacer(modifier = Modifier.height(8.dp))
                                                Text(
                                                    text = "Select resource document file",
                                                    fontWeight = FontWeight.Medium,
                                                    fontSize = 14.sp,
                                                    color = Color(0xFF202124)
                                                )
                                                Text(
                                                    text = "Supported: PDF, DOC, DOCX, PPT, PPTX, XLS, XLSX, TXT (Max 20MB)",
                                                    fontSize = 11.sp,
                                                    color = Color(0xFF5F6368),
                                                    textAlign = TextAlign.Center
                                                )
                                            }
                                        }

                                        // Upload Message Feedback
                                        uploadMessage?.let { msg ->
                                            Box(
                                                modifier = Modifier
                                                    .fillMaxWidth()
                                                    .clip(RoundedCornerShape(20.dp))
                                                    .background(if (isUploadSuccess) Color(0xFFE6F4EA) else Color(0xFFFCE8E6))
                                                    .padding(12.dp),
                                                contentAlignment = Alignment.Center
                                            ) {
                                                Text(
                                                    text = msg,
                                                    color = if (isUploadSuccess) Color(0xFF137333) else Color(0xFFC5221F),
                                                    fontSize = 13.sp,
                                                    fontWeight = FontWeight.Medium,
                                                    textAlign = TextAlign.Center
                                                )
                                            }
                                        }

                                        // Upload & Clear Buttons
                                        Row(
                                            modifier = Modifier.fillMaxWidth(),
                                            horizontalArrangement = Arrangement.spacedBy(12.dp)
                                        ) {
                                            Button(
                                                onClick = {
                                                    if (uploadTitle.isBlank() || uploadSubject.isBlank() || uploadDescription.isBlank()) {
                                                        uploadMessage = "Please fill in all required fields"
                                                        isUploadSuccess = false
                                                    } else {
                                                        uploadMessage = "Resource details ready for upload!"
                                                        isUploadSuccess = true
                                                    }
                                                },
                                                shape = RoundedCornerShape(25.dp),
                                                colors = ButtonDefaults.buttonColors(containerColor = Color(0xFFFF6B35)),
                                                modifier = Modifier
                                                    .weight(1f)
                                                    .height(48.dp)
                                            ) {
                                                Text("Upload Resource", fontSize = 14.sp, fontWeight = FontWeight.Bold, color = Color.White)
                                            }

                                            OutlinedButton(
                                                onClick = {
                                                    uploadTitle = ""
                                                    uploadSubject = ""
                                                    uploadDescription = ""
                                                    uploadMessage = null
                                                },
                                                shape = RoundedCornerShape(25.dp),
                                                colors = ButtonDefaults.outlinedButtonColors(
                                                    containerColor = Color(0xFFF1F3F4),
                                                    contentColor = Color(0xFF202124)
                                                ),
                                                border = BorderStroke(1.dp, Color(0xFF000000)),
                                                modifier = Modifier
                                                    .weight(1f)
                                                    .height(48.dp)
                                            ) {
                                                Text("Clear", fontSize = 14.sp, fontWeight = FontWeight.Medium)
                                            }
                                        }
                                    }
                                }
                            }
                        }

                        // SECTION 3: Profile View (Matching Web Profile Page Layout)
                        if (currentSection == 3) {
                            // 1. Profile Header with Large Circular Avatar
                            item {
                                Row(
                                    modifier = Modifier
                                        .fillMaxWidth()
                                        .padding(vertical = 12.dp),
                                    verticalAlignment = Alignment.CenterVertically
                                ) {
                                    Box(
                                        modifier = Modifier
                                            .size(80.dp)
                                            .clip(CircleShape)
                                            .background(Color(0xFFFF6B35)),
                                        contentAlignment = Alignment.Center
                                    ) {
                                        Text(
                                            text = (data?.user?.name ?: user.name).take(1).uppercase(),
                                            color = Color.White,
                                            fontSize = 36.sp,
                                            fontWeight = FontWeight.Bold
                                        )
                                    }

                                    Spacer(modifier = Modifier.width(16.dp))

                                    Column {
                                        Text(
                                            text = data?.user?.name ?: user.name,
                                            fontSize = 22.sp,
                                            fontWeight = FontWeight.Bold,
                                            color = Color(0xFF202124)
                                        )
                                        Spacer(modifier = Modifier.height(4.dp))
                                        Text(
                                            text = data?.user?.email ?: user.email,
                                            fontSize = 14.sp,
                                            color = Color(0xFF5F6368)
                                        )
                                    }
                                }
                            }

                            // 2. Card 1: Update Profile Form
                            item {
                                Card(
                                    modifier = Modifier.fillMaxWidth(),
                                    shape = RoundedCornerShape(16.dp),
                                    colors = CardDefaults.cardColors(containerColor = cardBgColor),
                                    border = BorderStroke(1.dp, borderColor)
                                ) {
                                    Column(
                                        modifier = Modifier
                                            .fillMaxWidth()
                                            .padding(20.dp),
                                        verticalArrangement = Arrangement.spacedBy(16.dp)
                                    ) {
                                        Text(
                                            text = "Update Profile",
                                            fontSize = 18.sp,
                                            fontWeight = FontWeight.Bold,
                                            color = Color(0xFF202124)
                                        )

                                        HorizontalDivider(color = borderColor)

                                        // Full Name Input
                                        OutlinedTextField(
                                            value = profileName,
                                            onValueChange = { profileName = it },
                                            label = { Text("Full Name *") },
                                            placeholder = { Text("Enter your full name") },
                                            singleLine = true,
                                            shape = RoundedCornerShape(25.dp),
                                            colors = OutlinedTextFieldDefaults.colors(
                                                focusedBorderColor = Color(0xFFFF6B35),
                                                unfocusedBorderColor = Color(0xFFDADCE0),
                                                focusedContainerColor = cardBgColor,
                                                unfocusedContainerColor = cardBgColor
                                            ),
                                            modifier = Modifier.fillMaxWidth()
                                        )

                                        // Email Address Input
                                        OutlinedTextField(
                                            value = profileEmail,
                                            onValueChange = { profileEmail = it },
                                            label = { Text("Email Address *") },
                                            placeholder = { Text("Enter your email address") },
                                            singleLine = true,
                                            shape = RoundedCornerShape(25.dp),
                                            colors = OutlinedTextFieldDefaults.colors(
                                                focusedBorderColor = Color(0xFFFF6B35),
                                                unfocusedBorderColor = Color(0xFFDADCE0),
                                                focusedContainerColor = cardBgColor,
                                                unfocusedContainerColor = cardBgColor
                                            ),
                                            modifier = Modifier.fillMaxWidth()
                                        )

                                        // Phone Number (Optional) Input
                                        Column {
                                            OutlinedTextField(
                                                value = profilePhone,
                                                onValueChange = { profilePhone = it },
                                                label = { Text("Phone Number (Optional)") },
                                                placeholder = { Text("07XXXXXXXX") },
                                                singleLine = true,
                                                shape = RoundedCornerShape(25.dp),
                                                colors = OutlinedTextFieldDefaults.colors(
                                                    focusedBorderColor = Color(0xFFFF6B35),
                                                    unfocusedBorderColor = Color(0xFFDADCE0),
                                                    focusedContainerColor = cardBgColor,
                                                    unfocusedContainerColor = cardBgColor
                                                ),
                                                modifier = Modifier.fillMaxWidth()
                                            )
                                            Spacer(modifier = Modifier.height(4.dp))
                                            Text(
                                                text = "Kenya format: 07XXXXXXXX, 01XXXXXXXX, +2547XXXXXXXX, or +2541XXXXXXXX (for SMS verification)",
                                                fontSize = 11.sp,
                                                color = Color(0xFF5F6368)
                                            )
                                        }

                                        // Update Profile Message Alert
                                        profileMessage?.let { msg ->
                                            Box(
                                                modifier = Modifier
                                                    .fillMaxWidth()
                                                    .clip(RoundedCornerShape(20.dp))
                                                    .background(if (isProfileSuccess) Color(0xFFE6F4EA) else Color(0xFFFCE8E6))
                                                    .padding(12.dp),
                                                contentAlignment = Alignment.Center
                                            ) {
                                                Text(
                                                    text = msg,
                                                    color = if (isProfileSuccess) Color(0xFF137333) else Color(0xFFC5221F),
                                                    fontSize = 13.sp,
                                                    fontWeight = FontWeight.Medium,
                                                    textAlign = TextAlign.Center
                                                )
                                            }
                                        }

                                        // Update Profile Button
                                        Button(
                                            onClick = {
                                                if (profileName.isBlank() || profileEmail.isBlank()) {
                                                    profileMessage = "Name and Email are required"
                                                    isProfileSuccess = false
                                                } else {
                                                    viewModel.updateProfile(user.id, profileName, profileEmail, profilePhone) { success, msg ->
                                                        profileMessage = msg
                                                        isProfileSuccess = success
                                                    }
                                                }
                                            },
                                            shape = RoundedCornerShape(25.dp),
                                            colors = ButtonDefaults.buttonColors(containerColor = Color(0xFFFF6B35)),
                                            modifier = Modifier
                                                .fillMaxWidth()
                                                .height(48.dp)
                                        ) {
                                            Icon(Icons.Default.Save, contentDescription = "Save", tint = Color.White, modifier = Modifier.size(18.dp))
                                            Spacer(modifier = Modifier.width(8.dp))
                                            Text("Update Profile", fontSize = 14.sp, fontWeight = FontWeight.Bold, color = Color.White)
                                        }
                                    }
                                }
                            }

                            // 3. Card 2: Change Password Form
                            item {
                                Card(
                                    modifier = Modifier.fillMaxWidth(),
                                    shape = RoundedCornerShape(16.dp),
                                    colors = CardDefaults.cardColors(containerColor = cardBgColor),
                                    border = BorderStroke(1.dp, borderColor)
                                ) {
                                    Column(
                                        modifier = Modifier
                                            .fillMaxWidth()
                                            .padding(20.dp),
                                        verticalArrangement = Arrangement.spacedBy(16.dp)
                                    ) {
                                        Text(
                                            text = "Change Password",
                                            fontSize = 18.sp,
                                            fontWeight = FontWeight.Bold,
                                            color = Color(0xFF202124)
                                        )

                                        HorizontalDivider(color = borderColor)

                                        // Current Password Input
                                        OutlinedTextField(
                                            value = currentPassword,
                                            onValueChange = { currentPassword = it },
                                            label = { Text("Current Password *") },
                                            placeholder = { Text("Enter your current password") },
                                            singleLine = true,
                                            visualTransformation = PasswordVisualTransformation(),
                                            shape = RoundedCornerShape(25.dp),
                                            colors = OutlinedTextFieldDefaults.colors(
                                                focusedBorderColor = Color(0xFFFF6B35),
                                                unfocusedBorderColor = Color(0xFFDADCE0),
                                                focusedContainerColor = cardBgColor,
                                                unfocusedContainerColor = cardBgColor
                                            ),
                                            modifier = Modifier.fillMaxWidth()
                                        )

                                        // New Password Input
                                        OutlinedTextField(
                                            value = newPassword,
                                            onValueChange = { newPassword = it },
                                            label = { Text("New Password *") },
                                            placeholder = { Text("Enter your new password") },
                                            singleLine = true,
                                            visualTransformation = PasswordVisualTransformation(),
                                            shape = RoundedCornerShape(25.dp),
                                            colors = OutlinedTextFieldDefaults.colors(
                                                focusedBorderColor = Color(0xFFFF6B35),
                                                unfocusedBorderColor = Color(0xFFDADCE0),
                                                focusedContainerColor = cardBgColor,
                                                unfocusedContainerColor = cardBgColor
                                            ),
                                            modifier = Modifier.fillMaxWidth()
                                        )

                                        // Confirm New Password Input
                                        OutlinedTextField(
                                            value = confirmNewPassword,
                                            onValueChange = { confirmNewPassword = it },
                                            label = { Text("Confirm New Password *") },
                                            placeholder = { Text("Confirm your new password") },
                                            singleLine = true,
                                            visualTransformation = PasswordVisualTransformation(),
                                            shape = RoundedCornerShape(25.dp),
                                            colors = OutlinedTextFieldDefaults.colors(
                                                focusedBorderColor = Color(0xFFFF6B35),
                                                unfocusedBorderColor = Color(0xFFDADCE0),
                                                focusedContainerColor = cardBgColor,
                                                unfocusedContainerColor = cardBgColor
                                            ),
                                            modifier = Modifier.fillMaxWidth()
                                        )

                                        // Password Message Feedback
                                        passwordMessage?.let { msg ->
                                            Box(
                                                modifier = Modifier
                                                    .fillMaxWidth()
                                                    .clip(RoundedCornerShape(20.dp))
                                                    .background(if (isPasswordSuccess) Color(0xFFE6F4EA) else Color(0xFFFCE8E6))
                                                    .padding(12.dp),
                                                contentAlignment = Alignment.Center
                                            ) {
                                                Text(
                                                    text = msg,
                                                    color = if (isPasswordSuccess) Color(0xFF137333) else Color(0xFFC5221F),
                                                    fontSize = 13.sp,
                                                    fontWeight = FontWeight.Medium,
                                                    textAlign = TextAlign.Center
                                                )
                                            }
                                        }

                                        // Change Password Button
                                        Button(
                                            onClick = {
                                                if (currentPassword.isBlank() || newPassword.isBlank()) {
                                                    passwordMessage = "Please fill in all password fields"
                                                    isPasswordSuccess = false
                                                } else if (newPassword != confirmNewPassword) {
                                                    passwordMessage = "Passwords do not match"
                                                    isPasswordSuccess = false
                                                } else if (newPassword.length < 6) {
                                                    passwordMessage = "New password must be at least 6 characters"
                                                    isPasswordSuccess = false
                                                } else {
                                                    viewModel.changePassword(user.id, currentPassword, newPassword, confirmNewPassword) { success, msg ->
                                                        passwordMessage = msg
                                                        isPasswordSuccess = success
                                                        if (success) {
                                                            currentPassword = ""
                                                            newPassword = ""
                                                            confirmNewPassword = ""
                                                        }
                                                    }
                                                }
                                            },
                                            shape = RoundedCornerShape(25.dp),
                                            colors = ButtonDefaults.buttonColors(containerColor = Color(0xFFFF6B35)),
                                            modifier = Modifier
                                                .fillMaxWidth()
                                                .height(48.dp)
                                        ) {
                                            Icon(Icons.Default.Key, contentDescription = "Key", tint = Color.White, modifier = Modifier.size(18.dp))
                                            Spacer(modifier = Modifier.width(8.dp))
                                            Text("Change Password", fontSize = 14.sp, fontWeight = FontWeight.Bold, color = Color.White)
                                        }
                                    }
                                }
                            }
                        }

                        // SECTION 4: Settings View (Matching Web Settings Page Layout)
                        if (currentSection == 4) {
                            // Card 1: Account Settings (Email Notifications & Privacy)
                            item {
                                Card(
                                    modifier = Modifier.fillMaxWidth(),
                                    shape = RoundedCornerShape(16.dp),
                                    colors = CardDefaults.cardColors(containerColor = cardBgColor),
                                    border = BorderStroke(1.dp, borderColor)
                                ) {
                                    Column(
                                        modifier = Modifier
                                            .fillMaxWidth()
                                            .padding(20.dp),
                                        verticalArrangement = Arrangement.spacedBy(16.dp)
                                    ) {
                                        Text(
                                            text = "Account Settings",
                                            fontSize = 20.sp,
                                            fontWeight = FontWeight.Bold,
                                            color = Color(0xFF202124)
                                        )

                                        HorizontalDivider(color = borderColor)

                                        // Email Notifications Subsection
                                        Text("Email Notifications", fontSize = 16.sp, fontWeight = FontWeight.Bold, color = Color(0xFF202124))
                                        Text("Choose which email notifications you want to receive.", fontSize = 12.sp, color = Color(0xFF5F6368))

                                        Row(
                                            modifier = Modifier.fillMaxWidth(),
                                            horizontalArrangement = Arrangement.SpaceBetween,
                                            verticalAlignment = Alignment.CenterVertically
                                        ) {
                                            Text("Notify me when my resources are downloaded", fontSize = 13.sp, color = Color(0xFF202124), modifier = Modifier.weight(1f))
                                            Switch(checked = emailUploads, onCheckedChange = { emailUploads = it })
                                        }

                                        Row(
                                            modifier = Modifier.fillMaxWidth(),
                                            horizontalArrangement = Arrangement.SpaceBetween,
                                            verticalAlignment = Alignment.CenterVertically
                                        ) {
                                            Text("Notify me of new comments on my resources", fontSize = 13.sp, color = Color(0xFF202124), modifier = Modifier.weight(1f))
                                            Switch(checked = emailComments, onCheckedChange = { emailComments = it })
                                        }

                                        Row(
                                            modifier = Modifier.fillMaxWidth(),
                                            horizontalArrangement = Arrangement.SpaceBetween,
                                            verticalAlignment = Alignment.CenterVertically
                                        ) {
                                            Text("Notify me about platform updates and new features", fontSize = 13.sp, color = Color(0xFF202124), modifier = Modifier.weight(1f))
                                            Switch(checked = emailUpdates, onCheckedChange = { emailUpdates = it })
                                        }

                                        HorizontalDivider(color = borderColor, modifier = Modifier.padding(vertical = 4.dp))

                                        // Privacy Settings Subsection
                                        Text("Privacy Settings", fontSize = 16.sp, fontWeight = FontWeight.Bold, color = Color(0xFF202124))
                                        Text("Control your privacy preferences.", fontSize = 12.sp, color = Color(0xFF5F6368))

                                        Row(
                                            modifier = Modifier.fillMaxWidth(),
                                            horizontalArrangement = Arrangement.SpaceBetween,
                                            verticalAlignment = Alignment.CenterVertically
                                        ) {
                                            Text("Make my profile visible to other users", fontSize = 13.sp, color = Color(0xFF202124), modifier = Modifier.weight(1f))
                                            Switch(checked = showProfile, onCheckedChange = { showProfile = it })
                                        }

                                        Row(
                                            modifier = Modifier.fillMaxWidth(),
                                            horizontalArrangement = Arrangement.SpaceBetween,
                                            verticalAlignment = Alignment.CenterVertically
                                        ) {
                                            Text("Show my email address on my profile", fontSize = 13.sp, color = Color(0xFF202124), modifier = Modifier.weight(1f))
                                            Switch(checked = showEmail, onCheckedChange = { showEmail = it })
                                        }
                                    }
                                }
                            }

                            // Card 2: Display Settings (Theme & Language)
                            item {
                                Card(
                                    modifier = Modifier.fillMaxWidth(),
                                    shape = RoundedCornerShape(16.dp),
                                    colors = CardDefaults.cardColors(containerColor = cardBgColor),
                                    border = BorderStroke(1.dp, borderColor)
                                ) {
                                    Column(
                                        modifier = Modifier
                                            .fillMaxWidth()
                                            .padding(20.dp),
                                        verticalArrangement = Arrangement.spacedBy(16.dp)
                                    ) {
                                        Text(
                                            text = "Display Settings",
                                            fontSize = 20.sp,
                                            fontWeight = FontWeight.Bold,
                                            color = Color(0xFF202124)
                                        )

                                        HorizontalDivider(color = borderColor)

                                        // Theme Preference Selector
                                        Text("Theme Preference", fontSize = 14.sp, fontWeight = FontWeight.Medium, color = Color(0xFF202124))
                                        ExposedDropdownMenuBox(
                                            expanded = themeExpanded,
                                            onExpandedChange = { themeExpanded = !themeExpanded },
                                            modifier = Modifier.fillMaxWidth()
                                        ) {
                                            OutlinedTextField(
                                                value = selectedTheme,
                                                onValueChange = {},
                                                readOnly = true,
                                                trailingIcon = { ExposedDropdownMenuDefaults.TrailingIcon(expanded = themeExpanded) },
                                                shape = RoundedCornerShape(25.dp),
                                                colors = OutlinedTextFieldDefaults.colors(
                                                    focusedBorderColor = Color(0xFFFF6B35),
                                                    unfocusedBorderColor = Color(0xFFDADCE0),
                                                    focusedContainerColor = cardBgColor,
                                                    unfocusedContainerColor = cardBgColor
                                                ),
                                                modifier = Modifier
                                                    .menuAnchor(ExposedDropdownMenuAnchorType.PrimaryNotEditable)
                                                    .fillMaxWidth()
                                            )
                                            ExposedDropdownMenu(
                                                expanded = themeExpanded,
                                                onDismissRequest = { themeExpanded = false }
                                            ) {
                                                themeOptions.forEach { option ->
                                                    DropdownMenuItem(
                                                        text = { Text(option) },
                                                        onClick = {
                                                            selectedTheme = option
                                                            themeExpanded = false
                                                        }
                                                    )
                                                }
                                            }
                                        }

                                        Spacer(modifier = Modifier.height(4.dp))

                                        // Language Selector
                                        Text("Language Preference", fontSize = 14.sp, fontWeight = FontWeight.Medium, color = Color(0xFF202124))
                                        ExposedDropdownMenuBox(
                                            expanded = langExpanded,
                                            onExpandedChange = { langExpanded = !langExpanded },
                                            modifier = Modifier.fillMaxWidth()
                                        ) {
                                            OutlinedTextField(
                                                value = selectedLanguage,
                                                onValueChange = {},
                                                readOnly = true,
                                                trailingIcon = { ExposedDropdownMenuDefaults.TrailingIcon(expanded = langExpanded) },
                                                shape = RoundedCornerShape(25.dp),
                                                colors = OutlinedTextFieldDefaults.colors(
                                                    focusedBorderColor = Color(0xFFFF6B35),
                                                    unfocusedBorderColor = Color(0xFFDADCE0),
                                                    focusedContainerColor = cardBgColor,
                                                    unfocusedContainerColor = cardBgColor
                                                ),
                                                modifier = Modifier
                                                    .menuAnchor(ExposedDropdownMenuAnchorType.PrimaryNotEditable)
                                                    .fillMaxWidth()
                                            )
                                            ExposedDropdownMenu(
                                                expanded = langExpanded,
                                                onDismissRequest = { langExpanded = false }
                                            ) {
                                                langOptions.forEach { option ->
                                                    DropdownMenuItem(
                                                        text = { Text(option) },
                                                        onClick = {
                                                            selectedLanguage = option
                                                            langExpanded = false
                                                        }
                                                    )
                                                }
                                            }
                                        }
                                    }
                                }
                            }

                            // Card 3: Danger Zone (Delete Account)
                            item {
                                Card(
                                    modifier = Modifier.fillMaxWidth(),
                                    shape = RoundedCornerShape(16.dp),
                                    colors = CardDefaults.cardColors(containerColor = cardBgColor),
                                    border = BorderStroke(1.dp, Color(0xFFFCE8E6))
                                ) {
                                    Column(
                                        modifier = Modifier
                                            .fillMaxWidth()
                                            .padding(20.dp),
                                        verticalArrangement = Arrangement.spacedBy(12.dp)
                                    ) {
                                        Text(
                                            text = "Danger Zone",
                                            fontSize = 20.sp,
                                            fontWeight = FontWeight.Bold,
                                            color = Color(0xFFC5221F)
                                        )

                                        HorizontalDivider(color = Color(0xFFFCE8E6))

                                        Text(
                                            text = "Delete Account",
                                            fontSize = 16.sp,
                                            fontWeight = FontWeight.Bold,
                                            color = Color(0xFFC5221F)
                                        )

                                        Text(
                                            text = "Once you delete your account, all your data will be archived for 30 days before permanent deletion. Please be certain.",
                                            fontSize = 13.sp,
                                            color = Color(0xFF5F6368)
                                        )

                                        Spacer(modifier = Modifier.height(4.dp))

                                        OutlinedButton(
                                            onClick = {
                                                confirmDeleteEmail = ""
                                                deletionReason = ""
                                                deleteAgreementChecked = false
                                                deleteErrorMsg = null
                                                showDeleteDialog = true
                                            },
                                            shape = RoundedCornerShape(25.dp),
                                            colors = ButtonDefaults.outlinedButtonColors(
                                                containerColor = Color(0xFFFCE8E6),
                                                contentColor = Color(0xFFC5221F)
                                            ),
                                            border = BorderStroke(1.dp, Color(0xFFC5221F)),
                                            modifier = Modifier
                                                .fillMaxWidth()
                                                .height(48.dp)
                                        ) {
                                            Icon(Icons.Default.Delete, contentDescription = "Delete Account", tint = Color(0xFFC5221F), modifier = Modifier.size(18.dp))
                                            Spacer(modifier = Modifier.width(8.dp))
                                            Text("Delete My Account", fontSize = 14.sp, fontWeight = FontWeight.Bold, color = Color(0xFFC5221F))
                                        }
                                    }
                                }
                            }

                            // Settings Feedback Alert & Save Settings Button
                            item {
                                Column(verticalArrangement = Arrangement.spacedBy(12.dp)) {
                                    settingsMessage?.let { msg ->
                                        Box(
                                            modifier = Modifier
                                                .fillMaxWidth()
                                                .clip(RoundedCornerShape(20.dp))
                                                .background(Color(0xFFE6F4EA))
                                                .padding(12.dp),
                                            contentAlignment = Alignment.Center
                                        ) {
                                            Text(
                                                text = msg,
                                                color = Color(0xFF137333),
                                                fontSize = 13.sp,
                                                fontWeight = FontWeight.Medium,
                                                textAlign = TextAlign.Center
                                            )
                                        }
                                    }

                                    Button(
                                        onClick = {
                                            viewModel.saveSettings(
                                                userId = user.id,
                                                emailUploads = emailUploads,
                                                emailComments = emailComments,
                                                emailUpdates = emailUpdates,
                                                showProfile = showProfile,
                                                showEmail = showEmail,
                                                theme = selectedTheme,
                                                language = selectedLanguage
                                            ) { _, msg ->
                                                settingsMessage = msg
                                            }
                                        },
                                        shape = RoundedCornerShape(25.dp),
                                        colors = ButtonDefaults.buttonColors(containerColor = Color(0xFFFF6B35)),
                                        modifier = Modifier
                                            .fillMaxWidth()
                                            .height(48.dp)
                                    ) {
                                        Icon(Icons.Default.Save, contentDescription = "Save", tint = Color.White, modifier = Modifier.size(18.dp))
                                        Spacer(modifier = Modifier.width(8.dp))
                                        Text("Save Settings", fontSize = 14.sp, fontWeight = FontWeight.Bold, color = Color.White)
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    // Custom Delete Account Confirmation Dialog (Matching Web Modal)
    if (showDeleteDialog) {
        AlertDialog(
            onDismissRequest = { showDeleteDialog = false },
            containerColor = cardBgColor,
            title = {
                Row(verticalAlignment = Alignment.CenterVertically) {
                    Icon(Icons.Default.Warning, contentDescription = "Warning", tint = Color(0xFFF9AB00), modifier = Modifier.size(24.dp))
                    Spacer(modifier = Modifier.width(8.dp))
                    Text("Delete Account", fontWeight = FontWeight.Bold, fontSize = 20.sp, color = Color(0xFF202124))
                }
            },
            text = {
                Column(verticalArrangement = Arrangement.spacedBy(12.dp)) {
                    Box(
                        modifier = Modifier
                            .fillMaxWidth()
                            .clip(RoundedCornerShape(8.dp))
                            .background(Color(0xFFFCE8E6))
                            .padding(12.dp)
                    ) {
                        Text(
                            text = "Warning: This action cannot be undone. Your account and all associated data will be deleted.",
                            fontSize = 12.sp,
                            color = Color(0xFFC5221F),
                            fontWeight = FontWeight.Medium
                        )
                    }

                    OutlinedTextField(
                        value = confirmDeleteEmail,
                        onValueChange = { confirmDeleteEmail = it },
                        label = { Text("Confirm your email address") },
                        placeholder = { Text(dashboardData?.user?.email ?: user.email) },
                        singleLine = true,
                        shape = RoundedCornerShape(25.dp),
                        colors = OutlinedTextFieldDefaults.colors(
                            focusedBorderColor = Color(0xFFC5221F),
                            unfocusedBorderColor = Color(0xFFDADCE0)
                        ),
                        modifier = Modifier.fillMaxWidth()
                    )

                    OutlinedTextField(
                        value = deletionReason,
                        onValueChange = { deletionReason = it },
                        label = { Text("Reason for deletion (optional)") },
                        placeholder = { Text("Why are you leaving?") },
                        maxLines = 3,
                        shape = RoundedCornerShape(20.dp),
                        colors = OutlinedTextFieldDefaults.colors(
                            focusedBorderColor = Color(0xFFFF6B35),
                            unfocusedBorderColor = Color(0xFFDADCE0)
                        ),
                        modifier = Modifier.fillMaxWidth()
                    )

                    Row(
                        verticalAlignment = Alignment.CenterVertically,
                        modifier = Modifier.fillMaxWidth()
                    ) {
                        Checkbox(
                            checked = deleteAgreementChecked,
                            onCheckedChange = { deleteAgreementChecked = it }
                        )
                        Text(
                            text = "I understand that my account will be deleted and this action cannot be undone",
                            fontSize = 11.sp,
                            color = Color(0xFF202124)
                        )
                    }

                    deleteErrorMsg?.let { err ->
                        Text(text = err, color = Color(0xFFC5221F), fontSize = 12.sp, fontWeight = FontWeight.Bold)
                    }
                }
            },
            confirmButton = {
                Button(
                    onClick = {
                        val targetEmail = dashboardData?.user?.email ?: user.email
                        if (!deleteAgreementChecked) {
                            deleteErrorMsg = "Please confirm that you understand this action cannot be undone."
                        } else if (!confirmDeleteEmail.equals(targetEmail, ignoreCase = true)) {
                            deleteErrorMsg = "Email confirmation does not match. Please enter $targetEmail."
                        } else {
                            viewModel.deleteAccount(user.id, confirmDeleteEmail, deletionReason) { success, msg ->
                                if (success) {
                                    showDeleteDialog = false
                                    onLogout()
                                } else {
                                    deleteErrorMsg = msg
                                }
                            }
                        }
                    },
                    colors = ButtonDefaults.buttonColors(containerColor = Color(0xFFC5221F)),
                    shape = RoundedCornerShape(25.dp)
                ) {
                    Text("Delete Account", color = Color.White, fontWeight = FontWeight.Bold)
                }
            },
            dismissButton = {
                OutlinedButton(
                    onClick = { showDeleteDialog = false },
                    shape = RoundedCornerShape(25.dp)
                ) {
                    Text("Cancel", color = Color(0xFF202124))
                }
            }
        )
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
    Card(
        modifier = modifier,
        shape = RoundedCornerShape(16.dp),
        colors = CardDefaults.cardColors(containerColor = bgColor),
        border = BorderStroke(1.dp, borderColor)
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
                Text(text = title, fontSize = 12.sp, color = Color(0xFF5F6368), fontWeight = FontWeight.Medium)
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
            Text(text = subtitle, fontSize = 11.sp, color = Color(0xFF9AA0A6))
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
        border = BorderStroke(1.dp, borderColor)
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
                    color = Color(0xFF202124),
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
                    color = Color(0xFF5F6368)
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
                    color = Color(0xFF5F6368),
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

                // Download / Locked Button using Google Material Icons
                if (canDownload) {
                    OutlinedButton(
                        onClick = { /* Handle download */ },
                        shape = RoundedCornerShape(25.dp),
                        colors = ButtonDefaults.outlinedButtonColors(
                            containerColor = Color(0xFFF1F3F4),
                            contentColor = Color(0xFF202124)
                        ),
                        border = BorderStroke(1.dp, Color(0xFF000000)),
                        contentPadding = PaddingValues(horizontal = 16.dp, vertical = 6.dp)
                    ) {
                        Icon(
                            imageVector = Icons.Default.Download,
                            contentDescription = "Download",
                            modifier = Modifier.size(16.dp)
                        )
                        Spacer(modifier = Modifier.width(6.dp))
                        Text(
                            text = "Download",
                            fontSize = 13.sp,
                            fontWeight = FontWeight.Medium
                        )
                    }
                } else if (userUploadCount == 1) {
                    OutlinedButton(
                        onClick = onUploadRequiredClick,
                        shape = RoundedCornerShape(25.dp),
                        colors = ButtonDefaults.outlinedButtonColors(
                            containerColor = Color(0xFFF1F3F4),
                            contentColor = Color(0xFF202124)
                        ),
                        border = BorderStroke(1.dp, Color(0xFF000000)),
                        contentPadding = PaddingValues(horizontal = 12.dp, vertical = 6.dp)
                    ) {
                        Icon(
                            imageVector = Icons.Default.Lock,
                            contentDescription = "Locked",
                            tint = Color(0xFF5F6368),
                            modifier = Modifier.size(16.dp)
                        )
                        Spacer(modifier = Modifier.width(6.dp))
                        Text(
                            text = "Upload 1 more resource to download",
                            fontSize = 11.sp,
                            fontWeight = FontWeight.Medium,
                            textAlign = TextAlign.Center
                        )
                    }
                } else {
                    OutlinedButton(
                        onClick = onUploadRequiredClick,
                        shape = RoundedCornerShape(25.dp),
                        colors = ButtonDefaults.outlinedButtonColors(
                            containerColor = Color(0xFFF1F3F4),
                            contentColor = Color(0xFF202124)
                        ),
                        border = BorderStroke(1.dp, Color(0xFF000000)),
                        contentPadding = PaddingValues(horizontal = 12.dp, vertical = 6.dp)
                    ) {
                        Icon(
                            imageVector = Icons.Default.Lock,
                            contentDescription = "Locked",
                            tint = Color(0xFF5F6368),
                            modifier = Modifier.size(16.dp)
                        )
                        Spacer(modifier = Modifier.width(6.dp))
                        Text(
                            text = "Upload 2 resources to download",
                            fontSize = 11.sp,
                            fontWeight = FontWeight.Medium,
                            textAlign = TextAlign.Center
                        )
                    }
                }
            }
        }
    }
}
