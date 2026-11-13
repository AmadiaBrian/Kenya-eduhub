package com.example.kenyaeduhub.ui.theme

import android.app.Activity
import android.graphics.Color as AndroidColor
import android.os.Build
import androidx.compose.foundation.isSystemInDarkTheme
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.darkColorScheme
import androidx.compose.material3.dynamicDarkColorScheme
import androidx.compose.material3.dynamicLightColorScheme
import androidx.compose.material3.lightColorScheme
import androidx.compose.runtime.Composable
import androidx.compose.runtime.SideEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.setValue
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.platform.LocalView
import androidx.core.view.WindowCompat

object ThemeState {
    var themePreference by mutableStateOf("System Default") // "Light Mode", "Dark Mode", "System Default"
}

@Composable
fun isDarkThemeActive(): Boolean {
    return when (ThemeState.themePreference) {
        "Dark Mode" -> true
        "Light Mode" -> false
        else -> isSystemInDarkTheme()
    }
}

data class AppThemeColors(
    val pageBgColor: Color,
    val cardBgColor: Color,
    val borderColor: Color,
    val primaryText: Color,
    val secondaryText: Color,
    val formBorderColor: Color,
    val inputBgColor: Color,
    val isDark: Boolean
)

@Composable
fun getAppThemeColors(darkTheme: Boolean = isDarkThemeActive()): AppThemeColors {
    return if (darkTheme) {
        AppThemeColors(
            pageBgColor = Color(0xFF121212),
            cardBgColor = Color(0xFF1E1E1E),
            borderColor = Color.Transparent, // No card borders in Dark Mode!
            primaryText = Color.White,       // Pure white text in Dark Mode!
            secondaryText = Color.White,     // Pure white text in Dark Mode!
            formBorderColor = Color(0xFF3C4043),
            inputBgColor = Color(0xFF252525),
            isDark = true
        )
    } else {
        AppThemeColors(
            pageBgColor = Color(0xFFF0F4F9),
            cardBgColor = Color(0xFFF0F4F9),
            borderColor = Color(0xFFE8EAED),
            primaryText = Color(0xFF202124),
            secondaryText = Color(0xFF5F6368),
            formBorderColor = Color(0xFFDADCE0),
            inputBgColor = Color(0xFFF0F4F9),
            isDark = false
        )
    }
}

private val DarkColorScheme = darkColorScheme(
    primary = Color(0xFFFF6B35),
    secondary = Color(0xFF008000),
    background = Color(0xFF121212),
    surface = Color(0xFF1E1E1E),
    onBackground = Color.White,
    onSurface = Color.White
)

private val LightColorScheme = lightColorScheme(
    primary = Color(0xFFFF6B35),
    secondary = Color(0xFF008000),
    background = Color(0xFFF0F4F9),
    surface = Color(0xFFF0F4F9),
    onBackground = Color(0xFF202124),
    onSurface = Color(0xFF202124)
)

@Composable
fun KenyaEduhubTheme(
    darkTheme: Boolean = isDarkThemeActive(),
    dynamicColor: Boolean = false,
    content: @Composable () -> Unit
) {
    val colorScheme = when {
        dynamicColor && Build.VERSION.SDK_INT >= Build.VERSION_CODES.S -> {
            val context = LocalContext.current
            if (darkTheme) dynamicDarkColorScheme(context) else dynamicLightColorScheme(context)
        }
        darkTheme -> DarkColorScheme
        else -> LightColorScheme
    }

    val view = LocalView.current
    if (!view.isInEditMode) {
        SideEffect {
            val window = (view.context as Activity).window
            val pageColor = if (darkTheme) AndroidColor.parseColor("#121212") else AndroidColor.parseColor("#F0F4F9")
            window.statusBarColor = pageColor
            window.navigationBarColor = pageColor
            WindowCompat.getInsetsController(window, view).apply {
                isAppearanceLightStatusBars = !darkTheme
                isAppearanceLightNavigationBars = !darkTheme
            }
        }
    }

    MaterialTheme(
        colorScheme = colorScheme,
        typography = Typography,
        content = content
    )
}
