package com.example.kenyaeduhub.utils

import android.content.Context
import android.os.Build
import android.os.VibrationEffect
import android.os.Vibrator
import android.os.VibratorManager

fun formatNetworkError(e: Exception): String {
    val msg = e.localizedMessage ?: ""
    return when {
        e is java.io.EOFException || msg.contains("unexpected end of stream", ignoreCase = true) ->
            "Server connection reset. Please try again."
        e is java.net.UnknownHostException ->
            "Unable to reach Kenya EduHub servers. Please check your internet connection."
        e is java.net.ConnectException ->
            "Unable to connect to Kenya EduHub servers. Please check your network connection and try again."
        e is java.net.SocketTimeoutException ->
            "Connection timed out. Server took too long to respond."
        e is javax.net.ssl.SSLException ->
            "Secure connection error. Please check your network settings."
        else -> {
            if (msg.isBlank() || msg.contains("Host", ignoreCase = true) || msg.contains("Unable to resolve", ignoreCase = true) || msg.contains("Failed to connect", ignoreCase = true)) {
                "Unable to reach Kenya EduHub server. Please check your internet connection."
            } else {
                "Network error: $msg"
            }
        }
    }
}

fun triggerExitVibration(context: Context) {
    try {
        val vibrator = if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.S) {
            val vibratorManager = context.getSystemService(Context.VIBRATOR_MANAGER_SERVICE) as VibratorManager
            vibratorManager.defaultVibrator
        } else {
            @Suppress("DEPRECATION")
            context.getSystemService(Context.VIBRATOR_SERVICE) as Vibrator
        }

        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            vibrator.vibrate(VibrationEffect.createOneShot(50L, VibrationEffect.DEFAULT_AMPLITUDE))
        } else {
            @Suppress("DEPRECATION")
            vibrator.vibrate(50L)
        }
    } catch (e: Exception) {
        // Ignore if device has no vibrator motor or permission
    }
}
