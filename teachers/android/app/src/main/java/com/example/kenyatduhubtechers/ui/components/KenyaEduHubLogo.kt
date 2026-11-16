package com.example.kenyatduhubtechers.ui.components

import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.Dp
import androidx.compose.ui.unit.TextUnit
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.example.kenyatduhubtechers.ui.theme.EduHubGold
import com.example.kenyatduhubtechers.ui.theme.EduHubGreen
import com.example.kenyatduhubtechers.ui.theme.EduHubOrange

@Composable
fun KenyaEduHubLogo(
    modifier: Modifier = Modifier,
    circleSize: Dp = 38.dp,
    borderWidth: Dp = 2.5.dp,
    kFontSize: TextUnit = 22.sp,
    eFontSize: TextUnit = 18.sp,
    showText: Boolean = true,
    textFontSize: TextUnit = 22.sp,
) {
    Row(
        modifier = modifier,
        verticalAlignment = Alignment.CenterVertically,
        horizontalArrangement = Arrangement.Center
    ) {
        // Circle Container with Gold background and Orange border
        Box(
            modifier = Modifier
                .size(circleSize)
                .clip(CircleShape)
                .background(EduHubGold)
                .border(borderWidth, EduHubOrange, CircleShape),
            contentAlignment = Alignment.Center
        ) {
            Row(
                verticalAlignment = Alignment.CenterVertically,
                horizontalArrangement = Arrangement.Center
            ) {
                Text(
                    text = "K",
                    color = EduHubOrange,
                    fontSize = kFontSize,
                    fontWeight = FontWeight.Bold
                )
                Text(
                    text = "E",
                    color = EduHubGreen,
                    fontSize = eFontSize,
                    fontWeight = FontWeight.Bold
                )
            }
        }

        if (showText) {
            Row(
                modifier = Modifier.padding(start = 8.dp),
                verticalAlignment = Alignment.CenterVertically
            ) {
                Text(
                    text = "Kenya",
                    color = EduHubOrange,
                    fontSize = textFontSize,
                    fontWeight = FontWeight.Bold
                )
                Text(
                    text = " EduHub",
                    color = EduHubGreen,
                    fontSize = textFontSize,
                    fontWeight = FontWeight.Bold
                )
            }
        }
    }
}
