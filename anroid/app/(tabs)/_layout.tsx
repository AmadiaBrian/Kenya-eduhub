import FontAwesome5 from '@expo/vector-icons/FontAwesome5';
import { Tabs } from 'expo-router';
import React from 'react';
import { useAuth } from '@/contexts/auth';

import { HapticTab } from '@/components/haptic-tab';
import { palette } from '@/components/app-ui';

export default function TabLayout() {
  const { isAuthenticated } = useAuth();

  return (
    <Tabs
      screenOptions={{
        tabBarActiveTintColor: palette.gold,
        tabBarInactiveTintColor: '#B0B0B0',
        tabBarStyle: {
          backgroundColor: '#000000',
          borderTopColor: palette.gold,
          borderTopWidth: 2,
          height: 66,
          paddingTop: 7,
          paddingBottom: 9,
        },
        tabBarLabelStyle: {
          fontSize: 11,
          fontWeight: '800',
        },
        headerShown: false,
        tabBarButton: HapticTab,
      }}>
      <Tabs.Screen
        name="index"
        options={{
          title: 'Home',
          tabBarIcon: ({ color, focused }) => (
            <FontAwesome5 name="home" size={focused ? 21 : 19} color={color} solid />
          ),
        }}
      />
      <Tabs.Screen
        name="dashboard"
        options={{
          href: isAuthenticated ? '/dashboard' : null,
          title: 'Dashboard',
          tabBarIcon: ({ color, focused }) => (
            <FontAwesome5 name="tachometer-alt" size={focused ? 21 : 19} color={color} solid />
          ),
        }}
      />
      <Tabs.Screen
        name="account"
        options={{
          title: 'Account',
          tabBarIcon: ({ color, focused }) => (
            <FontAwesome5 name="user-circle" size={focused ? 21 : 19} color={color} solid />
          ),
        }}
      />
      <Tabs.Screen
        name="explore"
        options={{
          href: null,
        }}
      />
    </Tabs>
  );
}
