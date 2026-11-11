import { View, Text, StyleSheet, TouchableOpacity, ScrollView, ActivityIndicator, TextInput, RefreshControl } from 'react-native';
import { useRouter } from 'expo-router';
import { useState, useEffect } from 'react';
import { Ionicons } from '@expo/vector-icons';
import * as FileSystem from 'expo-file-system/legacy';
import * as Sharing from 'expo-sharing';
import { getAssignments, AssignmentsResponse, API_BASE_URL } from '../../lib/api';

export default function Assignments() {
  const router = useRouter();
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [assignmentsData, setAssignmentsData] = useState<AssignmentsResponse | null>(null);
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedChild, setSelectedChild] = useState('');
  const [selectedType, setSelectedType] = useState('');
  const [sortBy, setSortBy] = useState('date-desc');
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    loadAssignments();
  }, []);

  const loadAssignments = async () => {
    try {
      const data = await getAssignments();
      setAssignmentsData(data);
      setError(null);
    } catch (error: any) {
      if (error.message === 'NOT_AUTHENTICATED') {
        router.replace('/login');
      } else {
        setError(error.message || 'Failed to load assignments');
      }
    } finally {
      setLoading(false);
    }
  };

  const onRefresh = async () => {
    setRefreshing(true);
    await loadAssignments();
    setRefreshing(false);
  };

  const features = [
    { id: 1, title: 'My Children', icon: 'person-outline', screen: 'children' },
    { id: 2, title: 'Performance', icon: 'trending-up-outline', screen: 'performance' },
    { id: 3, title: 'Fees', icon: 'wallet-outline', screen: 'fees' },
    { id: 4, title: 'Fines', icon: 'information-circle-outline', screen: 'fines' },
    { id: 5, title: 'Results', icon: 'trophy-outline', screen: 'results' },
    { id: 6, title: 'Profile', icon: 'person-circle-outline', screen: 'profile' },
  ];

  const handleDownload = async (assignment: any) => {
    try {
      if (!assignment.file_name && !assignment.attachment) {
        alert('No file attached to this assignment');
        return;
      }

      // Construct the download URL
      const assignmentId = assignment.id;
      const fileUrl = `${API_BASE_URL}/download_assignment.php?assignment_id=${assignmentId}`;
      
      console.log('Attempting to download from:', fileUrl);
      
      // Download the file to local storage first
      const filename = assignment.file_name || `assignment_${assignmentId}.pdf`;
      const localUri = FileSystem.documentDirectory + filename;
      
      const { uri } = await FileSystem.downloadAsync(fileUrl, localUri);
      console.log('File downloaded to:', uri);
      
      // Share the local file
      if (await Sharing.isAvailableAsync()) {
        await Sharing.shareAsync(uri);
      } else {
        alert('Cannot share files on this device');
      }
    } catch (error) {
      alert(`Failed to download: ${error instanceof Error ? error.message : 'Unknown error'}`);
    }
  };

  const getBadgeColor = (badgeType: string) => {
    switch (badgeType) {
      case 'Syllabus':
        return '#e8f0fe';
      case 'Sentiment':
        return '#fce8e6';
      case 'Notes':
        return '#e6f4ea';
      case 'Holiday':
        return '#fef7e0';
      default:
        return '#e8f0fe';
    }
  };

  const getBadgeTextColor = (badgeType: string) => {
    switch (badgeType) {
      case 'Syllabus':
        return '#1a73e8';
      case 'Sentiment':
        return '#c5221f';
      case 'Notes':
        return '#137333';
      case 'Holiday':
        return '#b06000';
      default:
        return '#1a73e8';
    }
  };

  const filterAndSortAssignments = (assignments: any[]) => {
    let filtered = [...assignments];
    
    // Filter by search term
    if (searchTerm) {
      filtered = filtered.filter(assignment =>
        assignment.title.toLowerCase().includes(searchTerm.toLowerCase()) ||
        assignment.description?.toLowerCase().includes(searchTerm.toLowerCase())
      );
    }
    
    // Filter by child (class)
    if (selectedChild) {
      filtered = filtered.filter(assignment => assignment.class === selectedChild);
    }
    
    // Filter by type
    if (selectedType) {
      filtered = filtered.filter(assignment => assignment.assignment_type === selectedType);
    }
    
    // Sort
    switch (sortBy) {
      case 'date-desc':
        filtered.sort((a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime());
        break;
      case 'date-asc':
        filtered.sort((a, b) => new Date(a.created_at).getTime() - new Date(b.created_at).getTime());
        break;
      case 'title-asc':
        filtered.sort((a, b) => a.title.localeCompare(b.title));
        break;
      case 'title-desc':
        filtered.sort((a, b) => b.title.localeCompare(a.title));
        break;
    }
    
    return filtered;
  };

  if (loading) {
    return (
      <View style={styles.container}>
        <View style={styles.header}>
          <TouchableOpacity onPress={() => router.back()}>
            <Ionicons name="arrow-back" size={24} color="#202124" />
          </TouchableOpacity>
          <Text style={styles.title}>Assignments</Text>
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
              loadAssignments();
            }}
          >
            <Ionicons name="refresh" size={20} color="#ffffff" />
            <Text style={styles.retryButtonText}>Try Again</Text>
          </TouchableOpacity>
        </View>
      </View>
    );
  }

  const children = assignmentsData?.children || [];
  const assignments = assignmentsData?.assignments || [];
  const filteredAssignments = filterAndSortAssignments(assignments);

  const parent = assignmentsData?.children?.[0]?.name || 'P';

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
        <View style={styles.pageHeader}>
          <Text style={styles.pageTitle}>Assignments</Text>
          <Text style={styles.pageSubtitle}>
            View syllabus, sentiments, notes, and holiday assignments for your children
          </Text>
        </View>
        
        <View style={styles.card}>
          <Text style={styles.cardTitle}>Recent Assignments</Text>
          
          {/* Search and Filter */}
          <View style={styles.filterContainer}>
            <View style={styles.searchContainer}>
              <Ionicons name="search-outline" size={20} color="#5f6368" style={styles.searchIcon} />
              <TextInput
                style={styles.searchInput}
                placeholder="Search assignments..."
                value={searchTerm}
                onChangeText={setSearchTerm}
              />
            </View>
            
            {children.length > 1 && (
              <View style={styles.filterSelect}>
                <Text style={styles.filterLabel}>Child:</Text>
                <TouchableOpacity 
                  style={styles.selectButton}
                  onPress={() => {
                    // For simplicity, just cycle through children
                    const childIndex = children.findIndex(c => c.class === selectedChild);
                    const nextIndex = (childIndex + 1) % (children.length + 1);
                    if (nextIndex === children.length) {
                      setSelectedChild('');
                    } else {
                      setSelectedChild(children[nextIndex].class);
                    }
                  }}
                >
                  <Text style={styles.selectText}>
                    {selectedChild ? children.find(c => c.class === selectedChild)?.name : 'All Children'}
                  </Text>
                  <Ionicons name="chevron-down" size={16} color="#5f6368" />
                </TouchableOpacity>
              </View>
            )}
          </View>
        </View>
        
        {filteredAssignments.length === 0 ? (
          <View style={styles.emptyState}>
            <Ionicons name="document-text-outline" size={48} color="#5f6368" />
            <Text style={styles.emptyText}>No assignments found</Text>
          </View>
        ) : (
          <View style={styles.assignmentsGrid}>
            {filteredAssignments.map((assignment) => (
              <View key={assignment.id} style={styles.assignmentCard}>
                <ScrollView style={styles.cardScroll}>
                  <View style={styles.assignmentCardHeader}>
                    <View style={styles.assignmentTitleContainer}>
                      <Text style={styles.assignmentTitle}>{assignment.title}</Text>
                      <View style={[
                        styles.badge,
                        { backgroundColor: getBadgeColor(assignment.badge_type) }
                      ]}>
                        <Text style={[
                          styles.badgeText,
                          { color: getBadgeTextColor(assignment.badge_type) }
                        ]}>
                          {assignment.badge_type}
                        </Text>
                      </View>
                    </View>
                  </View>
                  
                  {assignment.description && (
                    <Text style={styles.assignmentDescription}>
                      {assignment.description}
                    </Text>
                  )}
                  
                  <View style={styles.assignmentMeta}>
                    {assignment.class && (
                      <View style={styles.metaItem}>
                        <Ionicons name="people-outline" size={16} color="#5f6368" />
                        <Text style={styles.metaText}>{assignment.class}</Text>
                      </View>
                    )}
                    
                    {assignment.subject && (
                      <View style={styles.metaItem}>
                        <Ionicons name="book-outline" size={16} color="#5f6368" />
                        <Text style={styles.metaText}>{assignment.subject}</Text>
                      </View>
                    )}
                    
                    {assignment.due_date && (
                      <View style={styles.metaItem}>
                        <Ionicons name="calendar-outline" size={16} color="#5f6368" />
                        <Text style={styles.metaText}>
                          Due: {new Date(assignment.due_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}
                        </Text>
                      </View>
                    )}
                  </View>
                  
                  <View style={styles.assignmentFooter}>
                    <View style={styles.assignmentUploader}>
                      <Text style={styles.uploaderText}>{assignment.teacher}</Text>
                    </View>
                    <View style={styles.assignmentDate}>
                      <Text style={styles.dateText}>
                        {new Date(assignment.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}
                      </Text>
                    </View>
                  </View>
                  
                  <TouchableOpacity 
                    style={styles.downloadButton}
                    onPress={() => handleDownload(assignment)}
                  >
                    <Ionicons name="download-outline" size={18} color="white" />
                    <Text style={styles.downloadButtonText}>Download</Text>
                  </TouchableOpacity>
                </ScrollView>
              </View>
            ))}
          </View>
        )}
        
        <Text style={styles.totalCount}>
          Total assignments: {filteredAssignments.length}
        </Text>
        
        {/* Quick Actions */}
        <View style={[styles.quickActionsSection, styles.quickActionsSectionMargin]}>
          <Text style={styles.quickActionsTitle}>Quick Actions</Text>
          <View style={styles.featuresGrid}>
            {features.map((feature) => (
              <TouchableOpacity 
                key={feature.id} 
                style={styles.featureCard}
                onPress={() => {
                  if (feature.screen === 'children') {
                    router.push('/(tabs)/dashboard');
                  } else if (feature.screen === 'assignments') {
                    // Already on assignments page
                  } else if (feature.screen === 'fees') {
                    router.push('/(tabs)/fees');
                  } else if (feature.screen === 'fines') {
                    router.push('/(tabs)/fines');
                  } else if (feature.screen === 'performance') {
                    router.push('/(tabs)/performance');
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
  title: {
    fontSize: 20,
    fontWeight: '600',
    color: '#202124',
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
  content: {
    flex: 1,
    padding: 0,
  },
  pageHeader: {
    paddingHorizontal: 24,
    paddingTop: 24,
    paddingBottom: 16,
  },
  pageTitle: {
    fontSize: 24,
    fontWeight: '400',
    color: '#202124',
    marginBottom: 8,
  },
  pageSubtitle: {
    fontSize: 14,
    color: '#5f6368',
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
  card: {
    backgroundColor: '#f8f9fa',
    borderRadius: 8,
    padding: 24,
    marginBottom: 24,
    marginHorizontal: 24,
  },
  cardTitle: {
    fontSize: 18,
    fontWeight: '500',
    color: '#202124',
    marginBottom: 16,
  },
  filterContainer: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 12,
    marginBottom: 20,
  },
  searchContainer: {
    flex: 1,
    minWidth: 200,
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#f8f9fa',
    borderWidth: 1,
    borderColor: '#dadce0',
    borderRadius: 12,
    paddingHorizontal: 16,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.05,
    shadowRadius: 2,
    elevation: 2,
  },
  searchIcon: {
    marginRight: 12,
  },
  searchInput: {
    flex: 1,
    padding: 12,
    fontSize: 15,
    color: '#202124',
  },
  filterSelect: {
    minWidth: 150,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  filterLabel: {
    fontSize: 14,
    color: '#5f6368',
    fontWeight: '500',
  },
  selectButton: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#f8f9fa',
    borderWidth: 1,
    borderColor: '#dadce0',
    borderRadius: 12,
    paddingHorizontal: 16,
    paddingVertical: 12,
    gap: 8,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.05,
    shadowRadius: 2,
    elevation: 2,
  },
  selectText: {
    fontSize: 14,
    color: '#202124',
    fontWeight: '500',
  },
  emptyState: {
    alignItems: 'center',
    padding: 40,
  },
  emptyText: {
    marginTop: 16,
    color: '#5f6368',
    fontSize: 14,
  },
  assignmentsGrid: {
    gap: 16,
    paddingHorizontal: 16,
    width: '100%',
  },
  assignmentCard: {
    backgroundColor: '#f8f9fa',
    borderWidth: 1,
    borderColor: '#e8eaed',
    borderRadius: 8,
    padding: 20,
    width: '100%',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.05,
    shadowRadius: 2,
    elevation: 2,
  },
  cardScroll: {
    flex: 1,
  },
  assignmentCardHeader: {
    marginBottom: 12,
  },
  assignmentTitleContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    flexWrap: 'wrap',
  },
  assignmentTitle: {
    fontSize: 16,
    fontWeight: '500',
    color: '#202124',
    marginBottom: 8,
  },
  badge: {
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 12,
  },
  badgeText: {
    fontSize: 11,
    fontWeight: '500',
  },
  assignmentDescription: {
    fontSize: 14,
    color: '#202124',
    lineHeight: 20,
    marginBottom: 12,
  },
  assignmentMeta: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 16,
    marginBottom: 12,
  },
  metaItem: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
  },
  metaText: {
    fontSize: 13,
    color: '#5f6368',
  },
  assignmentFooter: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    borderTopWidth: 1,
    borderTopColor: '#e8eaed',
    paddingTop: 12,
    marginBottom: 12,
  },
  assignmentUploader: {
    flex: 1,
  },
  uploaderText: {
    fontSize: 13,
    color: '#5f6368',
  },
  assignmentDate: {
    marginLeft: 12,
  },
  dateText: {
    fontSize: 13,
    color: '#5f6368',
  },
  downloadButton: {
    backgroundColor: '#FF6B35',
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    padding: 12,
    borderRadius: 25,
    gap: 8,
  },
  downloadButtonText: {
    color: 'white',
    fontSize: 14,
    fontWeight: '500',
  },
  totalCount: {
    color: '#5f6368',
    fontSize: 14,
    fontWeight: '600',
    textAlign: 'center',
    paddingVertical: 20,
    paddingHorizontal: 16,
  },
  quickActionsSection: {
    paddingHorizontal: 24,
    paddingBottom: 24,
  },
  quickActionsSectionMargin: {
    marginBottom: 80,
    marginHorizontal: 24,
  },
  quickActionsTitle: {
    fontSize: 18,
    fontWeight: '500',
    color: '#202124',
    marginBottom: 16,
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
    fontSize: 15,
    fontWeight: '600',
    color: '#202124',
    textAlign: 'center',
    marginTop: 8,
  },
});