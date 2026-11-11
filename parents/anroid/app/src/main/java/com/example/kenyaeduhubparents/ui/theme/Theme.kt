package com.example.kenyaeduhubparents.ui.theme

import android.os.Build
import androidx.compose.foundation.isSystemInDarkTheme
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.darkColorScheme
import androidx.compose.material3.dynamicDarkColorScheme
import androidx.compose.material3.dynamicLightColorScheme
import androidx.compose.material3.lightColorScheme
import androidx.compose.runtime.Composable
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalContext

private val DarkColorScheme = darkColorScheme(
    primary = EduhubOrangeLight,
    secondary = EduhubMutedText,
    tertiary = EduhubOrange,
    background = Color(0xFF17191C),
    surface = Color(0xFF202124),
    onBackground = Color(0xFFF1F3F4),
    onSurface = Color(0xFFF1F3F4),
    onSurfaceVariant = Color(0xFFBDC1C6)
)

private val LightColorScheme = lightColorScheme(
    primary = EduhubOrange,
    onPrimary = Color.White,
    secondary = EduhubMutedText,
    tertiary = Color(0xFFDAA520),
    background = EduhubBackground,
    onBackground = EduhubText,
    surface = Color.White,
    onSurface = EduhubText,
    onSurfaceVariant = EduhubMutedText,
    outlineVariant = Color(0xFFDADCE0)
)

@Composable
fun KenyaEduhubParentsTheme(
    darkTheme: Boolean = isSystemInDarkTheme(),
    // Dynamic color is available on Android 12+
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

    MaterialTheme(
        colorScheme = colorScheme,
        typography = Typography,
        content = content
    )
}