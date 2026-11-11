package com.example.kenyaeduhubparents.data.model

import com.google.gson.annotations.SerializedName

data class GeneratePdfResponse(
    @SerializedName("success") val success: Boolean,
    @SerializedName("error") val error: String? = null,
    @SerializedName("statement_url") val statementUrl: String? = null,
    @SerializedName("structure_url") val structureUrl: String? = null,
    @SerializedName("pdf_url") val pdfUrl: String? = null,
    @SerializedName("url") val url: String? = null,
    @SerializedName("file_url") val fileUrl: String? = null,
    @SerializedName("statement_filename") val statementFilename: String? = null,
    @SerializedName("structure_filename") val structureFilename: String? = null,
    @SerializedName("filename") val filename: String? = null
) {
    val resolvedUrl: String?
        get() = statementUrl ?: structureUrl ?: pdfUrl ?: url ?: fileUrl

    val resolvedFilename: String?
        get() = statementFilename ?: structureFilename ?: filename
}
