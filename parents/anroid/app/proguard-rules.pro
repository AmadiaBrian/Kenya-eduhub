# Keep Gson models and serialization
-keepattributes *Annotation*,Signature,InnerClasses,EnclosingMethod
-keepclassmembers class * {
    @com.google.gson.annotations.SerializedName <fields>;
}
-keep class com.example.kenyaeduhubparents.data.model.** { *; }

# Retrofit & OkHttp
-keepattributes Signature
-keepattributes Exceptions
-keepclassmembers class * {
    @retrofit2.http.* <methods>;
}
-dontwarn retrofit2.**
-dontwarn okhttp3.**
-dontwarn okio.**
-dontwarn retrofit2.Platform$Java8
-dontwarn com.google.gson.**
