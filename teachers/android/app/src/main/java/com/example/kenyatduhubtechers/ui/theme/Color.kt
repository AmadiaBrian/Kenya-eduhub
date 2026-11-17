package com.example.kenyatduhubtechers.ui.theme

import androidx.compose.foundation.isSystemInDarkTheme
import androidx.compose.runtime.Composable
import androidx.compose.ui.graphics.Color

val EduHubOrange = Color(0xFFFF6B35)
val EduHubOrangeDark = Color(0xFFE05320)
val EduHubGold = Color(0xFFFFC107)
val EduHubGreen = Color(0xFF008000)
val EduHubError = Color(0xFFC5221F)
val EduHubErrorContainer = Color(0xFFFCE8E6)
val EduHubSuccess = Color(0xFF137333)
val EduHubSuccessContainer = Color(0xFFE6F4EA)

val EduHubBackground: Color
    @Composable get() = if (isSystemInDarkTheme()) Color(0xFF121212) else Color(0xFFF0F4F9)

val EduHubTextDark: Color
    @Composable get() = if (isSystemInDarkTheme()) Color.White else Color(0xFF202124)

val EduHubTextMuted: Color
    @Composable get() = if (isSystemInDarkTheme()) Color(0xFFF0F0F0) else Color(0xFF5F6368)

val EduHubBorder: Color
    @Composable get() = if (isSystemInDarkTheme()) Color(0xFF333333) else Color(0xFFDADCE0)

val EduHubCardSurface: Color
    @Composable get() = if (isSystemInDarkTheme()) Color(0xFF1E1E1E) else Color.White

val EduHubTableHeaderBg: Color
    @Composable get() = if (isSystemInDarkTheme()) Color(0xFF2D2D2D) else Color(0xFFF0F0F0)

val EduHubRowEvenBg: Color
    @Composable get() = if (isSystemInDarkTheme()) Color(0xFF1E1E1E) else Color.White

val EduHubRowOddBg: Color
    @Composable get() = if (isSystemInDarkTheme()) Color(0xFF252525) else Color(0xFFFAFAFA)
