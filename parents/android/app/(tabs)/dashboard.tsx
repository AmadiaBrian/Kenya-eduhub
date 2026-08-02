import { View, Text, StyleSheet, TouchableOpacity, ScrollView, ActivityIndicator, RefreshControl } from 'react-native';
import { useRouter } from 'expo-router';
import { useState, useEffect } from 'react';
import { Ionicons } from '@expo/vector-icons';
import { getDashboard, DashboardResponse } from '../../lib/api';

export default function Dashboard() {
  const router = useRouter();
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [dashboardData, setDashboardData] = useState<DashboardResponse | null>(null);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    loadDashboard();
  }, []);

  const loadDashboard = async () => {
    try {
      const data = await getDashboard();
      setDashboardData(data);
      setError(null);
    } catch (error: any) {
      if (error.message === 'NOT_AUTHENTICATED') {
        router.replace('/login');
      } else {
        setError(error.message || 'Failed to load dashboard');
      }
    } finally {
      setLoading(false);
    }
  };

  const onRefresh = async () => {
    setRefreshing(true);
    await loadDashboard();
    setRefreshing(false);
  };

  const features = [
    { id: 1, title: 'My Children', icon: 'person-outline', screen: 'children' },
    { id: 2, title: 'Performance', icon: 'trending-up-outline', screen: 'performance' },
    { id: 3, title: 'Fees', icon: 'wallet-outline', screen: 'fees' },
    { id: 4, title: 'Fines', icon: 'information-circle-outline', screen: 'fines' },
    { id: 5, title: 'Assignments', icon: 'list-outline', screen: 'assignments' },
    { id: 6, title: 'Results', icon: 'trophy-outline', screen: 'results' },
    { id: 7, title: 'Profile', icon: 'person-circle-outline', screen: 'profile' },
  ];

  if (loading) {
    return (
      <View style={styles.container}>
        <View style={styles.header}>
          <Text style={styles.title}>Dashboard</Text>
        </View>
        <View style={styles.loadingContainer}>
          <ActivityIndicator size="large" color="#FF6B35" />
          <Text style={styles.loadingText}>Loading...</Text>
        </View>
      </View>
    );
  }

  if (error) {
    return (
      <View style={styles.container}>
        <View style={styles.header}>
          <View style={styles.logo}>
            <View style={styles.logoIcon}>
              <Text style={styles.logoText}>
                <Text style={[styles.logoKE, { color: '#FF6B35', fontSize: 24 }]}>K</Text>
                <Text style={[styles.logoKE, { color: '#008000', fontSize: 20 }]}>E</Text>
              </Text>
            </View>
            <Text style={styles.kenyaText}>Kenya</Text>
            <Text style={styles.eduhubText}>EduHub</Text>
          </View>
        </View>
        <View style={styles.errorContainer}>
          <View style={styles.errorIconContainer}>
            <Ionicons name="cloud-offline" size={48} color="#5f6368" />
          </View>
          <Text style={styles.errorTitle}>Something went wrong</Text>
          <Text style={styles.errorMessage}>{error}</Text>
          <TouchableOpacity 
            style={styles.retryButton}
            onPress={() => {
              setError(null);
              setLoading(true);
              loadDashboard();
            }}
          >
            <Ionicons name="refresh" size={20} color="#ffffff" />
            <Text style={styles.retryButtonText}>Try Again</Text>
          </TouchableOpacity>
        </View>
      </View>
    );
  }

  const parent = dashboardData?.parent;
  const children = dashboardData?.children || [];
  const stats = dashboardData?.stats;
  const notifications = dashboardData?.notifications || [];

  return (
    <View style={styles.container}>
      <View style={styles.header}>
        <View style={styles.logo}>
          <View style={styles.logoIcon}>
            <Text style={styles.logoText}>
              <Text style={[styles.logoKE, { color: '#FF6B35', fontSize: 24 }]}>K</Text>
              <Text style={[styles.logoKE, { color: '#008000', fontSize: 20 }]}>E</Text>
            </Text>
          </View>
          <Text style={styles.kenyaText}>Kenya</Text>
          <Text style={styles.eduhubText}>EduHub</Text>
        </View>
      </View>

      <ScrollView 
        style={styles.content}
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={onRefresh}
            colors={['#FF6B35']}
          />
        }
      >
        <View style={styles.welcomeSection}>
          <Text style={styles.welcomeText}>Welcome back,</Text>
          <Text style={styles.parentName}>{parent?.name || 'Parent'}</Text>
          <Text style={styles.schoolName}>{parent?.school_name || 'School'}</Text>
        </View>

        {/* Stats Section */}
        {stats && (
          <View style={styles.statsGrid}>
            <View style={styles.statCard}>
              <Ionicons name="person-outline" size={32} color="#FF6B35" />
              <Text style={styles.statValue}>{stats.total_children}</Text>
              <Text style={styles.statLabel}>Children</Text>
            </View>
            <View style={styles.statCard}>
              <Ionicons name="wallet-outline" size={32} color="#FF6B35" />
              <Text style={styles.statValue}>KES {stats.total_fees_due.toLocaleString()}</Text>
              <Text style={styles.statLabel}>Fees Due</Text>
            </View>
            <View style={styles.statCard}>
              <Ionicons name="calendar-outline" size={32} color="#FF6B35" />
              <Text style={styles.statValue}>{stats.attendance_rate}%</Text>
              <Text style={styles.statLabel}>Attendance</Text>
            </View>
            <View style={styles.statCard}>
              <Ionicons name="trending-up-outline" size={32} color="#FF6B35" />
              <Text style={styles.statValue}>{stats.performance_records}</Text>
              <Text style={styles.statLabel}>Records</Text>
            </View>
          </View>
        )}

        {/* Children Section */}
        {children.length > 0 && (
          <View style={styles.card}>
            <Text style={styles.cardTitle}>My Children</Text>
            {children.map((child) => (
              <View key={child.id} style={styles.childCard}>
                <View style={styles.childHeader}>
                  <View style={styles.childAvatar}>
                    <Text style={styles.childAvatarText}>{child.name.charAt(0)}</Text>
                  </View>
                  <View style={styles.childInfo}>
                    <Text style={styles.childName}>{child.name}</Text>
                    <Text style={styles.childDetails}>{child.admission_number}</Text>
                  </View>
                </View>
                <View style={styles.childStats}>
                  <View style={styles.childStat}>
                    <Text style={styles.childStatLabel}>Class</Text>
                    <Text style={styles.childStatValue}>{child.class}</Text>
                  </View>
                  <View style={styles.childStat}>
                    <Text style={styles.childStatLabel}>Stream</Text>
                    <Text style={styles.childStatValue}>{child.stream}</Text>
                  </View>
                </View>
              </View>
            ))}
          </View>
        )}

        {/* Notifications Section */}
        {notifications.length > 0 && (
          <View style={styles.card}>
            <Text style={styles.cardTitle}>Recent Notifications</Text>
            {notifications.map((notif) => (
              <View key={notif.id} style={styles.notificationCard}>
                <Text style={styles.notificationTitle}>{notif.title}</Text>
                <Text style={styles.notificationMessage}>{notif.message}</Text>
              </View>
            ))}
          </View>
        )}

        {/* Quick Actions Section */}
        <View style={[styles.card, styles.quickActionsCard]}>
          <Text style={styles.cardTitle}>Quick Actions</Text>
          <View style={styles.featuresGrid}>
            {features.map((feature) => (
              <TouchableOpacity 
                key={feature.id} 
                style={styles.featureCard}
                onPress={() => {
                  if (feature.screen === 'children') {
                    router.push('/(tabs)/dashboard');
                  } else if (feature.screen === 'assignments') {
                    router.push('/(tabs)/assignments');
                  } else if (feature.screen === 'performance') {
                    router.push('/(tabs)/performance');
                  } else if (feature.screen === 'fees') {
                    router.push('/(tabs)/fees');
                  } else if (feature.screen === 'fines') {
                    router.push('/(tabs)/fines');
                  } else if (feature.screen === 'results') {
                    router.push('/(tabs)/results');
                  } else if (feature.screen === 'profile') {
                    router.push('/(tabs)/profile');
                  } else {
                    console.log(`Navigate to ${feature.screen}`);
                  }
                }}
              >
                <Ionicons name={feature.icon as any} size={32} color="#FF6B35" />
                <Text style={styles.featureTitle}>{feature.title}</Text>
              </TouchableOpacity>
            ))}
          </View>
        </View>
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#f8f9fa',
  },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'flex-start',
    padding: 16,
    paddingTop: 40,
    backgroundColor: '#ffffff',
    borderBottomWidth: 2,
    borderBottomColor: '#FF6B35',
    gap: 16,
  },
  headerLeft: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 16,
  },
  logo: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
  },
  logoIcon: {
    width: 40,
    height: 40,
    backgroundColor: '#FFD700',
    borderWidth: 3,
    borderColor: '#FF6B35',
    borderRadius: 20,
    justifyContent: 'center',
    alignItems: 'center',
  },
  logoText: {
    fontWeight: 'bold',
    fontSize: 20,
  },
  logoKE: {
    fontWeight: 'bold',
  },
  kenyaText: {
    color: '#FF6B35',
    fontWeight: 'bold',
    fontSize: 16,
  },
  eduhubText: {
    color: '#008000',
    fontWeight: 'bold',
    fontSize: 16,
  },
  title: {
    fontSize: 20,
    fontWeight: 'bold',
    color: '#202124',
  },
  userAvatar: {
    width: 32,
    height: 32,
    borderRadius: 16,
    backgroundColor: '#FF6B35',
    justifyContent: 'center',
    alignItems: 'center',
  },
  avatarText: {
    color: 'white',
    fontWeight: '500',
    fontSize: 14,
  },
  headerRight: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
  },
  profileButton: {
    padding: 4,
  },
  content: {
    flex: 1,
    padding: 16,
  },
  loadingContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
  },
  loadingText: {
    marginTop: 16,
    color: '#5f6368',
    fontSize: 14,
  },
  errorContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    padding: 32,
    backgroundColor: '#ffffff',
  },
  errorIconContainer: {
    marginBottom: 24,
  },
  errorTitle: {
    fontSize: 24,
    fontWeight: '500',
    color: '#202124',
    marginBottom: 8,
  },
  errorMessage: {
    fontSize: 14,
    color: '#5f6368',
    textAlign: 'center',
    marginBottom: 32,
  },
  retryButton: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#FF6B35',
    paddingHorizontal: 24,
    paddingVertical: 12,
    borderRadius: 8,
    gap: 8,
  },
  retryButtonText: {
    color: '#ffffff',
    fontSize: 14,
    fontWeight: '500',
  },
  welcomeSection: {
    marginBottom: 24,
    padding: 16,
  },
  welcomeText: {
    fontSize: 14,
    color: '#5f6368',
    marginBottom: 4,
  },
  parentName: {
    fontSize: 24,
    fontWeight: '400',
    color: '#202124',
    marginBottom: 4,
  },
  schoolName: {
    fontSize: 14,
    color: '#5f6368',
  },
  statsGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    marginBottom: 24,
    gap: 12,
  },
  statCard: {
    width: '48%',
    backgroundColor: '#f8f9fa',
    borderWidth: 1,
    borderColor: '#FF6B35',
    borderRadius: 8,
    padding: 24,
    alignItems: 'center',
    justifyContent: 'center',
  },
  goldCard: {
    borderColor: '#FFD700',
    borderWidth: 2,
    backgroundColor: '#fffef0',
  },
  statValue: {
    fontSize: 32,
    fontWeight: '400',
    color: '#202124',
    marginBottom: 8,
  },
  statLabel: {
    fontSize: 14,
    color: '#5f6368',
  },
  card: {
    backgroundColor: '#f8f9fa',
    borderWidth: 1,
    borderColor: '#e8eaed',
    borderRadius: 8,
    padding: 24,
  },
  childrenCard: {
    backgroundColor: '#f8f9fa',
    borderWidth: 1,
    borderColor: '#FFD700',
    borderRadius: 8,
    padding: 24,
    marginBottom: 24,
  },
  quickActionsCard: {
    marginBottom: 80,
    marginHorizontal: 24,
  },
  cardTitle: {
    fontSize: 18,
    fontWeight: '500',
    color: '#202124',
    marginBottom: 16,
  },
  childCard: {
    backgroundColor: '#f8f9fa',
    borderWidth: 1,
    borderColor: '#FFD700',
    borderRadius: 8,
    padding: 16,
    marginBottom: 12,
  },
  childHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 16,
  },
  childAvatar: {
    width: 50,
    height: 50,
    borderRadius: 25,
    backgroundColor: '#FF6B35',
    justifyContent: 'center',
    alignItems: 'center',
    marginRight: 16,
  },
  childAvatarText: {
    color: 'white',
    fontWeight: '500',
    fontSize: 18,
  },
  childInfo: {
    flex: 1,
  },
  childName: {
    fontSize: 16,
    fontWeight: '500',
    color: '#202124',
    marginBottom: 4,
  },
  childDetails: {
    fontSize: 13,
    color: '#5f6368',
  },
  childStats: {
    flexDirection: 'row',
    gap: 12,
  },
  childStat: {
    flex: 1,
    backgroundColor: '#f8f9fa',
    borderRadius: 8,
    padding: 12,
    alignItems: 'center',
  },
  childStatLabel: {
    fontSize: 12,
    color: '#5f6368',
    marginBottom: 4,
  },
  childStatValue: {
    fontSize: 18,
    fontWeight: '500',
    color: '#202124',
  },
  notificationCard: {
    backgroundColor: '#fff3cd',
    borderRadius: 8,
    padding: 16,
    marginBottom: 12,
    borderLeftWidth: 4,
    borderLeftColor: '#FF6B35',
  },
  notificationTitle: {
    fontSize: 16,
    fontWeight: '500',
    color: '#202124',
    marginBottom: 4,
  },
  notificationMessage: {
    fontSize: 14,
    color: '#5f6368',
  },
  featuresGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 12,
  },
  featureCard: {
    width: '48%',
    backgroundColor: '#f8f9fa',
    borderWidth: 1,
    borderColor: '#FF6B35',
    borderRadius: 8,
    padding: 20,
    alignItems: 'center',
  },
  featureTitle: {
    fontSize: 14,
    fontWeight: '500',
    color: '#202124',
    textAlign: 'center',
  },
});
