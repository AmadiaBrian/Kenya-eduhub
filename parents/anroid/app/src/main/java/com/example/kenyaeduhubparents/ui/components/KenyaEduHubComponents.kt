package com.example.kenyaeduhubparents.ui.components

import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import java.util.Calendar

@Composable
fun KenyaEduHubLogo(
    modifier: Modifier = Modifier
) {
    Row(
        modifier = modifier,
        verticalAlignment = Alignment.CenterVertically,
        horizontalArrangement = Arrangement.Center
    ) {
        // Circle with K and E
        Box(
            modifier = Modifier
                .size(40.dp)
                .background(Color(0xFFffc107), CircleShape)
                .border(3.dp, Color(0xFFFF6B35), CircleShape),
            contentAlignment = Alignment.Center
        ) {
            Row(
                verticalAlignment = Alignment.CenterVertically
            ) {
                Text(
                    text = "K",
                    color = Color(0xFFFF6B35),
                    fontSize = 22.sp,
                    fontWeight = FontWeight.Bold
                )
                Text(
                    text = "E",
                    color = Color(0xFF008000),
                    fontSize = 18.sp,
                    fontWeight = FontWeight.Bold
                )
            }
        }
        Spacer(modifier = Modifier.width(8.dp))
        // Text: Kenya EduHub
        Row(
            verticalAlignment = Alignment.CenterVertically
        ) {
            Text(
                text = "Kenya",
                color = Color(0xFFFF6B35),
                fontSize = 20.sp,
                fontWeight = FontWeight.Bold
            )
            Spacer(modifier = Modifier.width(4.dp))
            Text(
                text = "EduHub",
                color = Color(0xFF008000),
                fontSize = 20.sp,
                fontWeight = FontWeight.Bold
            )
        }
    }
}

@Composable
fun KenyaEduHubFooter(
    modifier: Modifier = Modifier
) {
    val currentYear = Calendar.getInstance().get(Calendar.YEAR)
    Text(
        text = "© $currentYear Kenya EduHub. All rights reserved.",
        color = MaterialTheme.colorScheme.onSurfaceVariant,
        fontSize = 12.sp,
        textAlign = TextAlign.Center,
        modifier = modifier
            .fillMaxWidth()
            .padding(vertical = 16.dp)
    )
}
