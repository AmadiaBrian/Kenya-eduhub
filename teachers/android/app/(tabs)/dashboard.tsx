import { View, Text, StyleSheet, ScrollView, TouchableOpacity, ActivityIndicator, RefreshControl } from 'react-native';
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

  if (loading) {
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
        {/* Welcome Section */}
        <View style={styles.welcomeSection}>
          <Text style={styles.welcomeText}>Welcome back,</Text>
          <Text style={styles.teacherName}>{dashboardData?.teacher?.name || 'Teacher'}</Text>
          {dashboardData?.teacher?.school_name && (
            <Text style={styles.schoolName}>{dashboardData.teacher.school_name}</Text>
          )}
          {dashboardData?.teacher?.class_name && (
            <Text style={styles.classInfo}>
              Class: {dashboardData.teacher.class_name} {dashboardData.teacher.stream_name ? `(${dashboardData.teacher.stream_name})` : ''}
            </Text>
          )}
        </View>

      {/* Calendar Status */}
      {dashboardData?.calendar_status && (
        <View style={styles.statusContainer}>
          <View style={styles.statusIconContainer}>
            <Ionicons 
              name={dashboardData.calendar_status.is_holiday ? 'calendar-outline' : 
                    dashboardData.calendar_status.school_status === 'break' ? 'time-outline' : 
                    'school-outline'} 
              size={24} 
              color="#FF6B35" 
            />
          </View>
          <View style={styles.statusTextContainer}>
            <Text style={styles.statusTitle}>
              {dashboardData.calendar_status.is_holiday ? 'School is on Holiday' :
               dashboardData.calendar_status.school_status === 'break' ? 'School is on Break' :
               'School is In Session'}
            </Text>
            {dashboardData.calendar_status.current_holiday && (
              <Text style={styles.statusText}>
                {dashboardData.calendar_status.current_holiday.holiday_name}
              </Text>
            )}
            {dashboardData.calendar_status.current_term && (
              <Text style={styles.statusText}>
                Active Term: {dashboardData.calendar_status.current_term.term_name}
              </Text>
            )}
          </View>
        </View>
      )}

      {/* Stats Cards */}
      <View style={styles.statsGrid}>
        <View style={styles.statCard}>
          <Text style={styles.statValue}>{dashboardData?.stats?.total_students || 0}</Text>
          <Text style={styles.statLabel}>Students</Text>
        </View>
        <View style={styles.statCard}>
          <Text style={styles.statValue}>{dashboardData?.stats?.present_today || 0}</Text>
          <Text style={styles.statLabel}>Present Today</Text>
        </View>
        <View style={styles.statCard}>
          <Text style={styles.statValue}>{dashboardData?.stats?.attendance_rate || 0}%</Text>
          <Text style={styles.statLabel}>Attendance</Text>
        </View>
        <View style={styles.statCard}>
          <Text style={styles.statValue}>{dashboardData?.stats?.performance_records || 0}</Text>
          <Text style={styles.statLabel}>Performance</Text>
        </View>
      </View>

      {/* Quick Actions */}
      <View style={styles.menuContainer}>
        <Text style={styles.menuTitle}>Quick Actions</Text>
        <View style={styles.actionsGrid}>
          {dashboardData?.quick_actions?.map((action) => (
            <TouchableOpacity 
              key={action.id} 
              style={styles.actionCard}
              onPress={() => {
                if (action.id === 'assignments') {
                  router.push('/(tabs)/assignments');
                }
              }}
            >
              <View style={styles.actionIcon}>
                <Ionicons name={action.icon as any} size={24} color="#FF6B35" />
              </View>
              <Text style={styles.actionTitle}>{action.title}</Text>
              <Text style={styles.actionDescription}>{action.description}</Text>
            </TouchableOpacity>
          ))}
        </View>
      </View>

      {/* Additional Quick Links */}
      <View style={styles.quickLinksSection}>
        <Text style={styles.quickLinksTitle}>Quick Links</Text>
        <View style={styles.quickLinksGrid}>
          <TouchableOpacity 
            style={styles.quickLinkButton}
            onPress={() => {
              router.push('/(tabs)/assignments');
              // Note: The upload modal would need to be triggered via navigation params or state
              // For now, this navigates to assignments where upload is available
            }}
          >
            <View style={styles.quickLinkIcon}>
              <Ionicons name="cloud-upload-outline" size={24} color="#FF6B35" />
            </View>
            <Text style={styles.quickLinkText}>Upload</Text>
          </TouchableOpacity>
          
          <TouchableOpacity 
            style={styles.quickLinkButton}
            onPress={() => router.push('/(tabs)/attendance')}
          >
            <View style={styles.quickLinkIcon}>
              <Ionicons name="people-outline" size={24} color="#1967d2" />
            </View>
            <Text style={styles.quickLinkText}>Attendance</Text>
          </TouchableOpacity>
          
          <TouchableOpacity 
            style={styles.quickLinkButton}
            onPress={() => router.push('/(tabs)/students')}
          >
            <View style={styles.quickLinkIcon}>
              <Ionicons name="person-outline" size={24} color="#188038" />
            </View>
            <Text style={styles.quickLinkText}>Students</Text>
          </TouchableOpacity>
          
          <TouchableOpacity 
            style={styles.quickLinkButton}
            onPress={() => router.push('/(tabs)/performance')}
          >
            <View style={styles.quickLinkIcon}>
              <Ionicons name="bar-chart-outline" size={24} color="#f57c00" />
            </View>
            <Text style={styles.quickLinkText}>Performance</Text>
          </TouchableOpacity>
        </View>
      </View>
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#ffffff',
  },
  header: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'flex-start',
    paddingHorizontal: 20,
    paddingTop: 30,
    paddingBottom: 20,
    backgroundColor: '#ffffff',
    borderBottomWidth: 2,
    borderBottomColor: '#FF6B35',
  },
  logo: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    marginTop: 15,
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
  welcomeSection: {
    marginBottom: 24,
    padding: 16,
  },
  welcomeText: {
    fontSize: 14,
    color: '#5f6368',
    marginBottom: 4,
  },
  teacherName: {
    fontSize: 24,
    fontWeight: '400',
    color: '#202124',
    marginBottom: 4,
  },
  schoolName: {
    fontSize: 14,
    color: '#5f6368',
  },
  classInfo: {
    fontSize: 14,
    color: '#5f6368',
    marginTop: 4,
  },
  statusContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#f8f9fa',
    borderWidth: 1,
    borderColor: '#FF6B35',
    borderRadius: 8,
    padding: 16,
    marginBottom: 24,
  },
  statusIconContainer: {
    width: 48,
    height: 48,
    borderRadius: 24,
    backgroundColor: '#FFD700',
    justifyContent: 'center',
    alignItems: 'center',
    marginRight: 16,
  },
  statusTextContainer: {
    flex: 1,
  },
  statusTitle: {
    fontSize: 16,
    fontWeight: '600',
    color: '#202124',
    marginBottom: 4,
  },
  statusText: {
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
  menuContainer: {
    padding: 20,
    paddingBottom: 24,
  },
  menuTitle: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#202124',
    marginBottom: 16,
  },
  actionsGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 12,
  },
  actionCard: {
    width: '48%',
    backgroundColor: '#f8f9fa',
    borderWidth: 1,
    borderColor: '#FF6B35',
    borderRadius: 8,
    padding: 20,
    alignItems: 'center',
  },
  actionIcon: {
    marginBottom: 12,
  },
  actionTitle: {
    fontSize: 14,
    fontWeight: '500',
    color: '#202124',
    textAlign: 'center',
  },
  actionDescription: {
    fontSize: 12,
    color: '#5f6368',
    textAlign: 'center',
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
  quickLinksSection: {
    marginBottom: 24,
    padding: 16,
    paddingBottom: 100,
  },
  quickLinksTitle: {
    fontSize: 18,
    fontWeight: '600',
    color: '#202124',
    marginBottom: 16,
  },
  quickLinksGrid: {
    flexDirection: 'row',
    gap: 12,
  },
  quickLinkButton: {
    flex: 1,
    backgroundColor: '#f8f9fa',
    borderRadius: 12,
    padding: 16,
    alignItems: 'center',
    borderWidth: 1,
    borderColor: '#e8eaed',
  },
  quickLinkIcon: {
    width: 48,
    height: 48,
    borderRadius: 24,
    backgroundColor: '#ffffff',
    justifyContent: 'center',
    alignItems: 'center',
    marginBottom: 8,
    borderWidth: 1,
    borderColor: '#dadce0',
  },
  quickLinkText: {
    fontSize: 12,
    fontWeight: '500',
    color: '#5f6368',
    textAlign: 'center',
  },
});
