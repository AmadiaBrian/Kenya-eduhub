package com.example.kenyaeduhubparents.data.network

object ApiConfig {
    private const val API_ROOT_URL =
        "https://agitated-silence-02871.pktriot.xyz/kenyaeduhub"

    const val BASE_URL = "$API_ROOT_URL/parents/api"
    const val ASSIGNMENT_DOWNLOAD_URL = "$API_ROOT_URL/api/download_assignment.php"
    const val FEE_STATEMENT_DOWNLOAD_URL = "$BASE_URL/generate_fee_statement.php"
    const val FEE_STRUCTURE_DOWNLOAD_URL = "$BASE_URL/generate_fee_structure.php"
    const val PHP_SESSION_COOKIE = "PHPSESSID"
}