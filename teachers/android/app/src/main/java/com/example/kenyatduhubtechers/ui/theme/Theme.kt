package com.example.kenyatduhubtechers.ui.theme

import android.app.Activity
import androidx.compose.foundation.isSystemInDarkTheme
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.darkColorScheme
import androidx.compose.material3.lightColorScheme
import androidx.compose.runtime.Composable
import androidx.compose.runtime.SideEffect
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.toArgb
import androidx.compose.ui.platform.LocalView
import androidx.core.view.WindowCompat

private val DarkColorScheme = darkColorScheme(
    primary = EduHubOrange,
    onPrimary = Color.White,
    primaryContainer = EduHubOrangeDark,
    secondary = EduHubGold,
    onSecondary = Color.White,
    background = Color(0xFF121212),
    surface = Color(0xFF1E1E1E),
    onBackground = Color.White,
    onSurface = Color.White,
    error = Color(0xFFFF6B6B),
    errorContainer = Color(0xFF421212)
)

private val LightColorScheme = lightColorScheme(
    primary = EduHubOrange,
    onPrimary = Color.White,
    primaryContainer = EduHubOrange,
    secondary = EduHubGold,
    onSecondary = Color(0xFF202124),
    background = Color(0xFFF0F4F9),
    surface = Color.White,
    onBackground = Color(0xFF202124),
    onSurface = Color(0xFF202124),
    error = Color(0xFFC5221F),
    errorContainer = Color(0xFFFCE8E6)
)

@Composable
fun KenyaTduhubTechersTheme(
    darkTheme: Boolean = isSystemInDarkTheme(),
    content: @Composable () -> Unit
) {
    val colorScheme = if (darkTheme) DarkColorScheme else LightColorScheme
    val view = LocalView.current

    if (!view.isInEditMode) {
        SideEffect {
            val window = (view.context as Activity).window
            window.statusBarColor = colorScheme.background.toArgb()
            WindowCompat.getInsetsController(window, view).isAppearanceLightStatusBars = !darkTheme
        }
    }

    MaterialTheme(
        colorScheme = colorScheme,
        typography = Typography,
        content = content
    )
}
