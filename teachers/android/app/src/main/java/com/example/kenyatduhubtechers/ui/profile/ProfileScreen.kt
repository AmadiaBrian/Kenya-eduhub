package com.example.kenyatduhubtechers.ui.profile

import androidx.compose.animation.AnimatedVisibility
import androidx.compose.foundation.BorderStroke
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
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Close
import androidx.compose.material.icons.filled.Save
import androidx.compose.material3.Button
import androidx.compose.material3.ButtonDefaults
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.DropdownMenuItem
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.ExposedDropdownMenuBox
import androidx.compose.material3.ExposedDropdownMenuDefaults
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.OutlinedTextFieldDefaults
import androidx.compose.material3.Text
import androidx.compose.material3.pulltorefresh.PullToRefreshBox
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.example.kenyatduhubtechers.ui.theme.EduHubBackground
import com.example.kenyatduhubtechers.ui.theme.EduHubBorder
import com.example.kenyatduhubtechers.ui.theme.EduHubError
import com.example.kenyatduhubtechers.ui.theme.EduHubErrorContainer
import com.example.kenyatduhubtechers.ui.theme.EduHubOrange
import com.example.kenyatduhubtechers.ui.theme.EduHubSuccess
import com.example.kenyatduhubtechers.ui.theme.EduHubSuccessContainer
import com.example.kenyatduhubtechers.ui.theme.EduHubTextDark

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun ProfileScreen(
    viewModel: ProfileViewModel,
    modifier: Modifier = Modifier
) {
    val uiState by viewModel.uiState.collectAsState()

    PullToRefreshBox(
        isRefreshing = uiState.isLoading,
        onRefresh = { viewModel.loadProfileData() },
        modifier = modifier.fillMaxSize()
    ) {
        if (uiState.isLoading && uiState.teacher == null) {
            Box(
                modifier = Modifier.fillMaxSize(),
                contentAlignment = Alignment.Center
            ) {
                CircularProgressIndicator(color = EduHubOrange)
            }
        } else {
            Column(
                modifier = Modifier
                    .fillMaxSize()
                    .verticalScroll(rememberScrollState())
                    .padding(16.dp)
            ) {
        Text(
            text = "My Profile",
            fontSize = 22.sp,
            fontWeight = FontWeight.Bold,
            color = EduHubTextDark,
            modifier = Modifier.padding(bottom = 16.dp)
        )

        // Success / Error Alerts
        AnimatedVisibility(visible = uiState.successMessage != null) {
            uiState.successMessage?.let { msg ->
                Card(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(bottom = 12.dp),
                    colors = CardDefaults.cardColors(containerColor = EduHubSuccessContainer),
                    border = BorderStroke(1.dp, EduHubSuccess),
                    shape = RoundedCornerShape(8.dp)
                ) {
                    Row(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(12.dp),
                        verticalAlignment = Alignment.CenterVertically,
                        horizontalArrangement = Arrangement.SpaceBetween
                    ) {
                        Text(text = msg, color = EduHubSuccess, fontSize = 13.sp, modifier = Modifier.weight(1f))
                        IconButton(onClick = { viewModel.clearMessages() }, modifier = Modifier.size(24.dp)) {
                            Icon(Icons.Default.Close, contentDescription = null, tint = EduHubSuccess)
                        }
                    }
                }
            }
        }

        AnimatedVisibility(visible = uiState.errorMessage != null) {
            uiState.errorMessage?.let { error ->
                Card(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(bottom = 12.dp),
                    colors = CardDefaults.cardColors(containerColor = EduHubErrorContainer),
                    border = BorderStroke(1.dp, EduHubError),
                    shape = RoundedCornerShape(8.dp)
                ) {
                    Row(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(12.dp),
                        verticalAlignment = Alignment.CenterVertically,
                        horizontalArrangement = Arrangement.SpaceBetween
                    ) {
                        Text(text = error, color = EduHubError, fontSize = 13.sp, modifier = Modifier.weight(1f))
                        IconButton(onClick = { viewModel.clearMessages() }, modifier = Modifier.size(24.dp)) {
                            Icon(Icons.Default.Close, contentDescription = null, tint = EduHubError)
                        }
                    }
                }
            }
        }

        // Personal Information Card
        Card(
            modifier = Modifier.fillMaxWidth(),
            colors = CardDefaults.cardColors(containerColor = EduHubBackground),
            border = BorderStroke(1.dp, EduHubBorder),
            shape = RoundedCornerShape(8.dp),
            elevation = CardDefaults.cardElevation(defaultElevation = 0.dp)
        ) {
            Column(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(20.dp)
            ) {
                Text(
                    text = "Personal Information",
                    fontSize = 18.sp,
                    fontWeight = FontWeight.Bold,
                    color = EduHubTextDark,
                    modifier = Modifier.padding(bottom = 16.dp)
                )

                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.spacedBy(8.dp)
                ) {
                    OutlinedTextField(
                        value = uiState.firstName,
                        onValueChange = { viewModel.onFirstNameChanged(it) },
                        label = { Text("First Name *", fontSize = 12.sp) },
                        modifier = Modifier.weight(1f),
                        singleLine = true,
                        shape = RoundedCornerShape(25.dp),
                        colors = OutlinedTextFieldDefaults.colors(
                            focusedBorderColor = EduHubOrange,
                            unfocusedBorderColor = EduHubBorder
                        )
                    )

                    OutlinedTextField(
                        value = uiState.lastName,
                        onValueChange = { viewModel.onLastNameChanged(it) },
                        label = { Text("Last Name *", fontSize = 12.sp) },
                        modifier = Modifier.weight(1f),
                        singleLine = true,
                        shape = RoundedCornerShape(25.dp),
                        colors = OutlinedTextFieldDefaults.colors(
                            focusedBorderColor = EduHubOrange,
                            unfocusedBorderColor = EduHubBorder
                        )
                    )
                }

                Spacer(modifier = Modifier.height(12.dp))

                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.spacedBy(8.dp)
                ) {
                    OutlinedTextField(
                        value = uiState.email,
                        onValueChange = { viewModel.onEmailChanged(it) },
                        label = { Text("Email *", fontSize = 12.sp) },
                        modifier = Modifier.weight(1f),
                        singleLine = true,
                        keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Email),
                        shape = RoundedCornerShape(25.dp),
                        colors = OutlinedTextFieldDefaults.colors(
                            focusedBorderColor = EduHubOrange,
                            unfocusedBorderColor = EduHubBorder
                        )
                    )

                    OutlinedTextField(
                        value = uiState.phone,
                        onValueChange = { viewModel.onPhoneChanged(it) },
                        label = { Text("Phone *", fontSize = 12.sp) },
                        modifier = Modifier.weight(1f),
                        singleLine = true,
                        keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Phone),
                        shape = RoundedCornerShape(25.dp),
                        colors = OutlinedTextFieldDefaults.colors(
                            focusedBorderColor = EduHubOrange,
                            unfocusedBorderColor = EduHubBorder
                        )
                    )
                }

                Spacer(modifier = Modifier.height(12.dp))

                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.spacedBy(8.dp)
                ) {
                    OutlinedTextField(
                        value = uiState.idNumber,
                        onValueChange = { viewModel.onIdNumberChanged(it) },
                        label = { Text("ID Number *", fontSize = 12.sp) },
                        modifier = Modifier.weight(1f),
                        singleLine = true,
                        keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Number),
                        shape = RoundedCornerShape(25.dp),
                        colors = OutlinedTextFieldDefaults.colors(
                            focusedBorderColor = EduHubOrange,
                            unfocusedBorderColor = EduHubBorder
                        )
                    )

                    // Subject Dropdown
                    val subjectOptions = uiState.subjects.mapNotNull { it.subjectName }
                    FilterDropdown(
                        modifier = Modifier.weight(1f),
                        label = "Subject",
                        selectedOption = if (uiState.selectedSubject.isBlank()) "Select Subject" else uiState.selectedSubject,
                        options = listOf("Select Subject") + subjectOptions,
                        onOptionSelected = {
                            viewModel.onSubjectChanged(if (it == "Select Subject") "" else it)
                        }
                    )
                }

                Spacer(modifier = Modifier.height(12.dp))

                OutlinedTextField(
                    value = uiState.address,
                    onValueChange = { viewModel.onAddressChanged(it) },
                    label = { Text("Address", fontSize = 12.sp) },
                    modifier = Modifier.fillMaxWidth(),
                    minLines = 2,
                    maxLines = 3,
                    shape = RoundedCornerShape(12.dp),
                    colors = OutlinedTextFieldDefaults.colors(
                        focusedBorderColor = EduHubOrange,
                        unfocusedBorderColor = EduHubBorder
                    )
                )

                Spacer(modifier = Modifier.height(12.dp))

                OutlinedTextField(
                    value = uiState.teacher?.schoolName ?: "Kenya EduHub School",
                    onValueChange = {},
                    readOnly = true,
                    label = { Text("School", fontSize = 12.sp) },
                    modifier = Modifier.fillMaxWidth(),
                    singleLine = true,
                    shape = RoundedCornerShape(25.dp),
                    colors = OutlinedTextFieldDefaults.colors(
                        focusedBorderColor = EduHubBorder,
                        unfocusedBorderColor = EduHubBorder,
                        focusedContainerColor = Color(0xFFF2F4F8),
                        unfocusedContainerColor = Color(0xFFF2F4F8)
                    )
                )

                Spacer(modifier = Modifier.height(12.dp))

                val formattedTeacherType = (uiState.teacher?.teacherType ?: "teacher")
                    .replace("_", " ")
                    .split(" ")
                    .joinToString(" ") { it.replaceFirstChar { char -> char.uppercase() } }

                OutlinedTextField(
                    value = formattedTeacherType,
                    onValueChange = {},
                    readOnly = true,
                    label = { Text("Teacher Type", fontSize = 12.sp) },
                    modifier = Modifier.fillMaxWidth(),
                    singleLine = true,
                    shape = RoundedCornerShape(25.dp),
                    colors = OutlinedTextFieldDefaults.colors(
                        focusedBorderColor = EduHubBorder,
                        unfocusedBorderColor = EduHubBorder,
                        focusedContainerColor = Color(0xFFF2F4F8),
                        unfocusedContainerColor = Color(0xFFF2F4F8)
                    )
                )

                Spacer(modifier = Modifier.height(20.dp))

                Button(
                    onClick = { viewModel.updateProfile() },
                    enabled = !uiState.isUpdating,
                    modifier = Modifier.fillMaxWidth(),
                    shape = RoundedCornerShape(25.dp),
                    colors = ButtonDefaults.buttonColors(containerColor = EduHubOrange)
                ) {
                    if (uiState.isUpdating) {
                        CircularProgressIndicator(color = Color.White, modifier = Modifier.size(20.dp))
                    } else {
                        Icon(Icons.Default.Save, contentDescription = null, modifier = Modifier.size(18.dp))
                        Spacer(modifier = Modifier.width(8.dp))
                        Text("Update Profile", fontSize = 14.sp, fontWeight = FontWeight.Bold)
                    }
                }
            }
        }

        Spacer(modifier = Modifier.height(24.dp))
    }
    }
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
private fun FilterDropdown(
    modifier: Modifier = Modifier,
    label: String,
    selectedOption: String,
    options: List<String>,
    onOptionSelected: (String) -> Unit
) {
    var expanded by remember { mutableStateOf(false) }

    ExposedDropdownMenuBox(
        expanded = expanded,
        onExpandedChange = { expanded = !expanded },
        modifier = modifier
    ) {
        OutlinedTextField(
            value = selectedOption,
            onValueChange = {},
            readOnly = true,
            label = { Text(label, fontSize = 11.sp) },
            trailingIcon = { ExposedDropdownMenuDefaults.TrailingIcon(expanded = expanded) },
            modifier = Modifier
                .fillMaxWidth()
                .menuAnchor(),
            singleLine = true,
            shape = RoundedCornerShape(25.dp),
            colors = OutlinedTextFieldDefaults.colors(
                focusedBorderColor = EduHubOrange,
                unfocusedBorderColor = EduHubBorder,
                focusedContainerColor = EduHubBackground,
                unfocusedContainerColor = EduHubBackground,
                focusedTextColor = EduHubTextDark,
                unfocusedTextColor = EduHubTextDark
            )
        )

        ExposedDropdownMenu(
            expanded = expanded,
            onDismissRequest = { expanded = false }
        ) {
            options.forEach { option ->
                DropdownMenuItem(
                    text = { Text(option, fontSize = 13.sp) },
                    onClick = {
                        onOptionSelected(option)
                        expanded = false
                    }
                )
            }
        }
    }
}
