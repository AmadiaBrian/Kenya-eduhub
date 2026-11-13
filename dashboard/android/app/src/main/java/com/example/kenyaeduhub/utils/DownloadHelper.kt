package com.example.kenyaeduhub.utils

import android.app.DownloadManager
import android.content.Context
import android.net.Uri
import android.os.Environment
import android.widget.Toast
import com.example.kenyaeduhub.data.ApiConfig
import com.example.kenyaeduhub.data.ResourceItem

fun downloadResourceFile(context: Context, resource: ResourceItem) {
    try {
        val downloadManager = context.getSystemService(Context.DOWNLOAD_SERVICE) as? DownloadManager
        if (downloadManager == null) {
            Toast.makeText(context, "Download manager unavailable", Toast.LENGTH_SHORT).show()
            return
        }

        val downloadUrl = "${ApiConfig.DOWNLOAD_URL}?id=${resource.id}&download=true"
        val extension = resource.fileType.lowercase().trim().ifBlank { "pdf" }
        val sanitizedTitle = resource.title.replace("[^a-zA-Z0-9_.-]".toRegex(), "_")
        val fileName = "$sanitizedTitle.$extension"

        val request = DownloadManager.Request(Uri.parse(downloadUrl)).apply {
            setTitle(resource.title)
            setDescription("Downloading resource from Kenya EduHub")
            setNotificationVisibility(DownloadManager.Request.VISIBILITY_VISIBLE_NOTIFY_COMPLETED)
            setDestinationInExternalPublicDir(Environment.DIRECTORY_DOWNLOADS, fileName)
            setAllowedOverMetered(true)
            setAllowedOverRoaming(true)
        }

        downloadManager.enqueue(request)
        Toast.makeText(context, "Downloading '$fileName' to Downloads folder...", Toast.LENGTH_LONG).show()
    } catch (e: Exception) {
        Toast.makeText(context, "Download failed: ${e.localizedMessage ?: "Error starting download"}", Toast.LENGTH_SHORT).show()
    }
}
