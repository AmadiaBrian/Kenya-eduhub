package com.example.kenyatduhubtechers.ui.navigation

import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxHeight
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material3.HorizontalDivider
import androidx.compose.material3.Icon
import androidx.compose.material3.ModalDrawerSheet
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.example.kenyatduhubtechers.data.model.TeacherDto
import com.example.kenyatduhubtechers.ui.components.KenyaEduHubLogo
import com.example.kenyatduhubtechers.ui.theme.EduHubBackground
import com.example.kenyatduhubtechers.ui.theme.EduHubBorder
import com.example.kenyatduhubtechers.ui.theme.EduHubOrange
import com.example.kenyatduhubtechers.ui.theme.EduHubTextDark
import com.example.kenyatduhubtechers.ui.theme.EduHubTextMuted

@Composable
fun AppDrawerContent(
    currentRoute: String,
    teacher: TeacherDto? = null,
    onItemSelected: (NavItem) -> Unit,
    onLogout: () -> Unit,
    modifier: Modifier = Modifier
) {
    ModalDrawerSheet(
        modifier = modifier.width(280.dp),
        drawerContainerColor = EduHubBackground,
        drawerShape = RoundedCornerShape(topEnd = 16.dp, bottomEnd = 16.dp)
    ) {
        Column(
            modifier = Modifier
                .fillMaxHeight()
                .verticalScroll(rememberScrollState())
                .padding(vertical = 16.dp)
        ) {
            // Header Logo inside Sidebar
            Column(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(horizontal = 20.dp, vertical = 12.dp)
            ) {
                KenyaEduHubLogo(
                    showText = true,
                    circleSize = 34.dp,
                    borderWidth = 2.dp,
                    kFontSize = 18.sp,
                    eFontSize = 15.sp,
                    textFontSize = 18.sp
                )
            }

            Spacer(modifier = Modifier.height(4.dp))
            HorizontalDivider(color = EduHubBorder.copy(alpha = 0.6f))
            Spacer(modifier = Modifier.height(8.dp))

            // Section: MAIN
            Text(
                text = NavSection.MAIN.title.uppercase(),
                fontSize = 11.sp,
                fontWeight = FontWeight.Bold,
                color = EduHubTextMuted,
                letterSpacing = 0.5.sp,
                modifier = Modifier.padding(horizontal = 24.dp, vertical = 8.dp)
            )

            NavItems.getMainItems().forEach { item ->
                val isActive = currentRoute == item.route
                DrawerItemRow(
                    item = item,
                    isActive = isActive,
                    onClick = { onItemSelected(item) }
                )
            }

            Spacer(modifier = Modifier.height(12.dp))
            HorizontalDivider(color = EduHubBorder.copy(alpha = 0.6f))
            Spacer(modifier = Modifier.height(8.dp))

            // Section: ACCOUNT
            Text(
                text = NavSection.ACCOUNT.title.uppercase(),
                fontSize = 11.sp,
                fontWeight = FontWeight.Bold,
                color = EduHubTextMuted,
                letterSpacing = 0.5.sp,
                modifier = Modifier.padding(horizontal = 24.dp, vertical = 8.dp)
            )

            NavItems.getAccountItems().forEach { item ->
                val isActive = currentRoute == item.route
                DrawerItemRow(
                    item = item,
                    isActive = isActive,
                    onClick = {
                        if (item == NavItem.Logout) {
                            onLogout()
                        } else {
                            onItemSelected(item)
                        }
                    }
                )
            }
        }
    }
}

@Composable
private fun DrawerItemRow(
    item: NavItem,
    isActive: Boolean,
    onClick: () -> Unit
) {
    val backgroundColor = if (isActive) Color(0xFFE8F0FE) else Color.Transparent

    Row(
        modifier = Modifier
            .fillMaxWidth()
            .padding(horizontal = 12.dp, vertical = 2.dp)
            .clip(RoundedCornerShape(24.dp))
            .background(backgroundColor)
            .clickable { onClick() }
            .padding(horizontal = 16.dp, vertical = 10.dp),
        verticalAlignment = Alignment.CenterVertically
    ) {
        Icon(
            imageVector = item.icon,
            contentDescription = item.title,
            tint = if (isActive) EduHubOrange else EduHubTextMuted,
            modifier = Modifier.size(20.dp)
        )
        Spacer(modifier = Modifier.width(16.dp))
        Text(
            text = item.title,
            fontSize = 14.sp,
            fontWeight = if (isActive) FontWeight.Bold else FontWeight.Normal,
            color = if (isActive) EduHubOrange else EduHubTextDark
        )
    }
}
