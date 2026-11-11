package com.example.kenyaeduhubparents.ui.assignments

import android.content.ContentValues
import android.content.Context
import android.os.Environment
import android.provider.MediaStore
import android.widget.Toast
import androidx.compose.foundation.background
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
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.CalendarMonth
import androidx.compose.material.icons.filled.Download
import androidx.compose.material.icons.filled.Groups
import androidx.compose.material.icons.filled.MenuBook
import androidx.compose.material.icons.filled.Person
import androidx.compose.material3.Button
import androidx.compose.material3.ButtonDefaults
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.lifecycle.viewmodel.compose.viewModel
import com.example.kenyaeduhubparents.data.local.SessionManager
import com.example.kenyaeduhubparents.data.model.AssignmentItem
import com.example.kenyaeduhubparents.data.network.ApiConfig
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext
import okhttp3.OkHttpClient
import okhttp3.Request
import java.io.File

@Composable
fun AssignmentsScreen(
    modifier: Modifier = Modifier,
    viewModel: AssignmentsViewModel = viewModel(
        factory = AssignmentsViewModel.Factory(LocalContext.current)
    )
) {
    val uiState by viewModel.uiState.collectAsState()

    Column(
        modifier = modifier
            .fillMaxSize()
            .verticalScroll(rememberScrollState())
            .padding(16.dp),
        verticalArrangement = Arrangement.spacedBy(20.dp)
    ) {
        // Page Title
        Column(
            modifier = Modifier.fillMaxWidth(),
            horizontalAlignment = Alignment.CenterHorizontally,
            verticalArrangement = Arrangement.spacedBy(4.dp)
        ) {
            Text(
                text = "Assignments",
                style = MaterialTheme.typography.headlineMedium,
                fontWeight = FontWeight.Bold,
                color = MaterialTheme.colorScheme.onBackground
            )
            Text(
                text = "View syllabus, sentiments, notes, and holiday assignments",
                style = MaterialTheme.typography.bodyMedium,
                color = MaterialTheme.colorScheme.onSurfaceVariant,
                textAlign = TextAlign.Center
            )
        }

        when (val state = uiState) {
            is AssignmentsUiState.Loading -> {
                Box(
                    modifier = Modifier
                        .fillMaxWidth()
                        .height(250.dp),
                    contentAlignment = Alignment.Center
                ) {
                    CircularProgressIndicator(color = MaterialTheme.colorScheme.primary)
                }
            }
            is AssignmentsUiState.Error -> {
                Card(
                    modifier = Modifier.fillMaxWidth(),
                    colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.errorContainer),
                    shape = RoundedCornerShape(16.dp)
                ) {
                    Column(
                        modifier = Modifier.padding(16.dp),
                        verticalArrangement = Arrangement.spacedBy(12.dp)
                    ) {
                        Text(
                            text = state.message,
                            color = MaterialTheme.colorScheme.onErrorContainer,
                            style = MaterialTheme.typography.bodyMedium
                        )
                        OutlinedButton(onClick = { viewModel.loadAssignments() }) {
                            Text("Retry")
                        }
                    }
                }
            }
            is AssignmentsUiState.Success -> {
                val assignments = state.data.assignments

                if (assignments.isEmpty()) {
                    Card(
                        modifier = Modifier.fillMaxWidth(),
                        shape = RoundedCornerShape(16.dp),
                        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface)
                    ) {
                        Text(
                            text = "No assignments found for your children.",
                            style = MaterialTheme.typography.bodyMedium,
                            color = MaterialTheme.colorScheme.onSurfaceVariant,
                            modifier = Modifier.padding(24.dp),
                            textAlign = TextAlign.Center
                        )
                    }
                } else {
                    Column(verticalArrangement = Arrangement.spacedBy(16.dp)) {
                        assignments.forEach { assignment ->
                            AssignmentCard(assignment = assignment)
                        }
                    }
                }
            }
        }
    }
}

@Composable
fun AssignmentCard(assignment: AssignmentItem) {
    val context = LocalContext.current

    val badgeColor = when (assignment.badgeType.lowercase()) {
        "sentiment" -> Color(0xFFEA4335)
        "notes" -> Color(0xFF137333)
        "holiday" -> Color(0xFFB06000)
        else -> Color(0xFF1A73E8) // Syllabus
    }

    val badgeBg = when (assignment.badgeType.lowercase()) {
        "sentiment" -> Color(0xFFFCE8E6)
        "notes" -> Color(0xFFE6F4EA)
        "holiday" -> Color(0xFFFEF7E0)
        else -> Color(0xFFE8F0FE) // Syllabus
    }

    Card(
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(16.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        elevation = CardDefaults.cardElevation(defaultElevation = 1.dp)
    ) {
        Column(
            modifier = Modifier.padding(18.dp),
            verticalArrangement = Arrangement.spacedBy(12.dp)
        ) {
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Text(
                    text = assignment.title,
                    style = MaterialTheme.typography.titleMedium,
                    fontWeight = FontWeight.Bold,
                    color = MaterialTheme.colorScheme.onSurface,
                    modifier = Modifier.weight(1f)
                )
                Box(
                    modifier = Modifier
                        .clip(RoundedCornerShape(12.dp))
                        .background(badgeBg)
                        .padding(horizontal = 10.dp, vertical = 4.dp)
                ) {
                    Text(
                        text = assignment.badgeType,
                        color = badgeColor,
                        fontSize = 11.sp,
                        fontWeight = FontWeight.Bold
                    )
                }
            }

            if (assignment.description.isNotBlank()) {
                Text(
                    text = assignment.description,
                    style = MaterialTheme.typography.bodyMedium,
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )
            }

            Column(verticalArrangement = Arrangement.spacedBy(6.dp)) {
                if (assignment.className.isNotBlank()) {
                    Row(verticalAlignment = Alignment.CenterVertically) {
                        Icon(imageVector = Icons.Filled.Groups, contentDescription = null, tint = MaterialTheme.colorScheme.primary, modifier = Modifier.size(16.dp))
                        Spacer(modifier = Modifier.width(8.dp))
                        Text(text = "Class: ${assignment.className}", style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
                    }
                }
                if (assignment.subject.isNotBlank()) {
                    Row(verticalAlignment = Alignment.CenterVertically) {
                        Icon(imageVector = Icons.Filled.MenuBook, contentDescription = null, tint = MaterialTheme.colorScheme.primary, modifier = Modifier.size(16.dp))
                        Spacer(modifier = Modifier.width(8.dp))
                        Text(text = "Subject: ${assignment.subject}", style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
                    }
                }
                if (assignment.teacher.isNotBlank()) {
                    Row(verticalAlignment = Alignment.CenterVertically) {
                        Icon(imageVector = Icons.Filled.Person, contentDescription = null, tint = MaterialTheme.colorScheme.primary, modifier = Modifier.size(16.dp))
                        Spacer(modifier = Modifier.width(8.dp))
                        Text(text = "Teacher: ${assignment.teacher}", style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
                    }
                }
                if (assignment.dueDate.isNotBlank()) {
                    Row(verticalAlignment = Alignment.CenterVertically) {
                        Icon(imageVector = Icons.Filled.CalendarMonth, contentDescription = null, tint = MaterialTheme.colorScheme.error, modifier = Modifier.size(16.dp))
                        Spacer(modifier = Modifier.width(8.dp))
                        Text(text = "Due: ${assignment.dueDate}", style = MaterialTheme.typography.bodySmall, fontWeight = FontWeight.SemiBold, color = MaterialTheme.colorScheme.error)
                    }
                }
            }

            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Text(
                    text = "Posted: ${assignment.createdAt}",
                    style = MaterialTheme.typography.labelSmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )
            }

            Button(
                onClick = {
                    Toast.makeText(context, "Downloading assignment...", Toast.LENGTH_SHORT).show()
                    CoroutineScope(Dispatchers.IO).launch {
                        try {
                            val sessionManager = SessionManager(context)
                            val sessionId = sessionManager.getSessionId()
                            if (sessionId.isNullOrBlank()) {
                                withContext(Dispatchers.Main) {
                                    Toast.makeText(
                                        context,
                                        "Your session is unavailable. Sign out and sign in again.",
                                        Toast.LENGTH_LONG
                                    ).show()
                                }
                                return@launch
                            }

                            val downloadUrl =
                                "${ApiConfig.ASSIGNMENT_DOWNLOAD_URL}?assignment_id=${assignment.id}"
                            val client = OkHttpClient.Builder().build()
                            val request = Request.Builder()
                                .url(downloadUrl)
                                .header("ngrok-skip-browser-warning", "true")
                                .header(
                                    "Cookie",
                                    "${ApiConfig.PHP_SESSION_COOKIE}=$sessionId"
                                )
                                .build()

                            client.newCall(request).execute().use { response ->
                                if (!response.isSuccessful) {
                                    val message = when (response.code) {
                                        401 -> "Your session has expired. Sign out and sign in again."
                                        403 -> "You don't have access to this assignment."
                                        else -> "Download failed (Server Error ${response.code})"
                                    }
                                    withContext(Dispatchers.Main) {
                                        Toast.makeText(context, message, Toast.LENGTH_LONG).show()
                                    }
                                    return@launch
                                }

                                val responseBody = response.body
                                    ?: throw IllegalStateException("The server returned an empty file.")
                                val bytes = responseBody.bytes()
                                val fileName = assignment.fileName?.takeIf { it.isNotBlank() } ?: "assignment_${assignment.id}.pdf"

                                val saved = saveFileToDownloads(context, bytes, fileName)
                                withContext(Dispatchers.Main) {
                                    if (saved) {
                                        Toast.makeText(context, "Saved to Downloads/KenyaEduHub/$fileName", Toast.LENGTH_LONG).show()
                                    } else {
                                        Toast.makeText(context, "Failed to save file", Toast.LENGTH_SHORT).show()
                                    }
                                }
                            }
                        } catch (e: Exception) {
                            withContext(Dispatchers.Main) {
                                Toast.makeText(context, "Download error: ${e.localizedMessage}", Toast.LENGTH_SHORT).show()
                            }
                        }
                    }
                },
                modifier = Modifier
                    .fillMaxWidth()
                    .height(42.dp),
                shape = RoundedCornerShape(25.dp),
                colors = ButtonDefaults.buttonColors(containerColor = MaterialTheme.colorScheme.primary)
            ) {
                Icon(imageVector = Icons.Filled.Download, contentDescription = null, tint = Color.White, modifier = Modifier.size(18.dp))
                Spacer(modifier = Modifier.width(8.dp))
                Text("Download Assignment", fontWeight = FontWeight.Bold, fontSize = 13.sp, color = Color.White)
            }
        }
    }
}

fun saveFileToDownloads(context: Context, bytes: ByteArray, fileName: String): Boolean {
    return try {
        if (android.os.Build.VERSION.SDK_INT >= android.os.Build.VERSION_CODES.Q) {
            val resolver = context.contentResolver
            val contentValues = ContentValues().apply {
                put(MediaStore.MediaColumns.DISPLAY_NAME, fileName)
                put(MediaStore.MediaColumns.MIME_TYPE, "application/octet-stream")
                put(MediaStore.MediaColumns.RELATIVE_PATH, Environment.DIRECTORY_DOWNLOADS + "/KenyaEduHub")
            }
            val uri = resolver.insert(MediaStore.Files.getContentUri("external"), contentValues)
            if (uri != null) {
                resolver.openOutputStream(uri)?.use { outputStream ->
                    outputStream.write(bytes)
                }
                true
            } else {
                false
            }
        } else {
            val downloadsDir = Environment.getExternalStoragePublicDirectory(Environment.DIRECTORY_DOWNLOADS)
            val appDir = File(downloadsDir, "KenyaEduHub")
            if (!appDir.exists()) appDir.mkdirs()
            val file = File(appDir, fileName)
            file.writeBytes(bytes)
            true
        }
    } catch (e: Exception) {
        e.printStackTrace()
        false
    }
}
