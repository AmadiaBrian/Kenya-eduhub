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
    onSecondary = EduHubTextDark,
    background = Color(0xFF121212),
    surface = Color(0xFF1E1E1E),
    error = EduHubError,
    errorContainer = EduHubErrorContainer
)

private val LightColorScheme = lightColorScheme(
    primary = EduHubOrange,
    onPrimary = Color.White,
    primaryContainer = EduHubOrange,
    secondary = EduHubGold,
    onSecondary = EduHubTextDark,
    background = EduHubBackground,
    surface = Color.White,
    onBackground = EduHubTextDark,
    onSurface = EduHubTextDark,
    error = EduHubError,
    errorContainer = EduHubErrorContainer
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
