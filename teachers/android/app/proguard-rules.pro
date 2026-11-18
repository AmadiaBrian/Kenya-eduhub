# Keep App Data Models & DTOs from being stripped or obfuscated by R8
-keep class com.example.kenyatduhubtechers.data.model.** { *; }
-keepclassmembers class com.example.kenyatduhubtechers.data.model.** { *; }

# Keep App Network API Interfaces and Services
-keep interface com.example.kenyatduhubtechers.data.network.** { *; }
-keep class com.example.kenyatduhubtechers.data.network.** { *; }

# Keep App ViewModels and Local Managers
-keep class com.example.kenyatduhubtechers.ui.** { *; }
-keep class com.example.kenyatduhubtechers.data.local.** { *; }
-keep class com.example.kenyatduhubtechers.data.repository.** { *; }

# Gson Rules
-keepattributes Signature
-keepattributes *Annotation*
-keepattributes EnclosingMethod,InnerClasses
-keep class com.google.gson.** { *; }
-keepclassmembers class * {
    @com.google.gson.annotations.SerializedName <fields>;
}

# Retrofit Rules
-keep class retrofit2.** { *; }
-keepclasseswithmembers class * {
    @retrofit2.http.* <methods>;
}

# OkHttp Rules
-keep class okhttp3.** { *; }
-dontwarn okhttp3.**
-dontwarn okio.**
-dontwarn javax.annotation.**
-dontwarn org.codehaus.mojo.animal_sniffer.IgnoreJRERequirement

# Kotlin Coroutines Rules
-keepclassmembers class kotlinx.coroutines.** { *; }
-keep class kotlinx.coroutines.** { *; }
-dontwarn kotlinx.coroutines.**

# Android Jetpack & Compose Keep Rules
-keep class androidx.activity.** { *; }
-keep class androidx.lifecycle.** { *; }
-keep class androidx.compose.** { *; }
