# Kenya EduHub ProGuard / R8 Keep Rules for Release Build

# Keep Data Models and JSON Serialized Classes
-keep class com.example.kenyaeduhub.data.** { *; }
-keepclassmembers class com.example.kenyaeduhub.data.** { *; }

# Keep Utilities and Session Storage
-keep class com.example.kenyaeduhub.utils.** { *; }
-keepclassmembers class com.example.kenyaeduhub.utils.** { *; }

# Keep ViewModels and UI state
-keep class com.example.kenyaeduhub.ui.** { *; }
-keepclassmembers class com.example.kenyaeduhub.ui.** { *; }

# Keep Activity and Application Entry Points
-keep class com.example.kenyaeduhub.MainActivity { *; }

# Keep org.json classes
-keep class org.json.** { *; }

# Keep Coroutines
-keepclassmembers class * extends kotlin.coroutines.jvm.internal.ContinuationImpl {
    *** invokeSuspend(...);
}

# Keep Compose Composables
-keepclassmembers class * {
    @androidx.compose.runtime.Composable <methods>;
}
