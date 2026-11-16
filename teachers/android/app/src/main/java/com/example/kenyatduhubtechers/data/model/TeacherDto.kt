package com.example.kenyatduhubtechers.data.model

import com.google.gson.annotations.SerializedName

data class TeacherDto(
    @SerializedName("id")
    val id: Int? = null,
    @SerializedName("first_name")
    val firstName: String? = null,
    @SerializedName("last_name")
    val lastName: String? = null,
    @SerializedName("name")
    val name: String? = null,
    @SerializedName("email")
    val email: String? = null,
    @SerializedName("phone")
    val phone: String? = null,
    @SerializedName("id_number")
    val idNumber: String? = null,
    @SerializedName("address")
    val address: String? = null,
    @SerializedName("subject")
    val subject: String? = null,
    @SerializedName("teacher_type")
    val teacherType: String? = null,
    @SerializedName("school_id")
    val schoolId: Int? = null,
    @SerializedName("school_name")
    val schoolName: String? = null,
    @SerializedName("class_id")
    val classId: Int? = null,
    @SerializedName("stream_id")
    val streamId: Int? = null,
    @SerializedName("class_name")
    val className: String? = null,
    @SerializedName("stream_name")
    val streamName: String? = null
)
