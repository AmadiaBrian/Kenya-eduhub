import { View, Text, StyleSheet, TouchableOpacity, ScrollView, ActivityIndicator, RefreshControl } from 'react-native';
import { useRouter } from 'expo-router';
import { useState, useEffect } from 'react';
import { Ionicons } from '@expo/vector-icons';
import { getPerformance, PerformanceResponse } from '../../lib/api';

export default function Performance() {
  const router = useRouter();
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [performanceData, setPerformanceData] = useState<PerformanceResponse | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [selectedChild, setSelectedChild] = useState<number | null>(null);
  const [selectedYear, setSelectedYear] = useState<string>('');
  const [selectedTerm, setSelectedTerm] = useState<string>('');

  useEffect(() => {
    loadPerformance();
  }, []);

  const loadPerformance = async () => {
    try {
      const data = await getPerformance();
      setPerformanceData(data);
      setError(null);
      // Auto-select child if there's only one
      if (data.children && data.children.length === 1) {
        setSelectedChild(data.children[0].id);
      }
      // Auto-select current year and term
      if (data.current_year) {
        setSelectedYear(data.current_year);
      }
      if (data.current_term) {
        setSelectedTerm(data.current_term);
      }
    } catch (error: any) {
      if (error.message === 'NOT_AUTHENTICATED') {
        router.replace('/login');
      } else {
        setError(error.message || 'Failed to load performance data');
      }
    } finally {
      setLoading(false);
    }
  };

  const onRefresh = async () => {
    setRefreshing(true);
    await loadPerformance();
    setRefreshing(false);
  };

  const getGradeColor = (grade: string) => {
    const upperGrade = grade.toUpperCase();
    switch (upperGrade) {
      case 'A':
        return { backgroundColor: '#e6f4ea', color: '#137333' };
      case 'B':
        return { backgroundColor: '#e8f0fe', color: '#1a73e8' };
      case 'C':
        return { backgroundColor: '#fef7e0', color: '#b06000' };
      case 'D':
        return { backgroundColor: '#fce8e6', color: '#c5221f' };
      case 'E':
      case 'F':
        return { backgroundColor: '#fce8e6', color: '#c5221f' };
      default:
        return { backgroundColor: '#f1f3f4', color: '#5f6368' };
    }
  };

  const features = [
    { id: 1, title: 'My Children', icon: 'person-outline', screen: 'children' },
    { id: 2, title: 'Assignments', icon: 'document-text-outline', screen: 'assignments' },
    { id: 3, title: 'Fees', icon: 'wallet-outline', screen: 'fees' },
    { id: 4, title: 'Fines', icon: 'information-circle-outline', screen: 'fines' },
    { id: 5, title: 'Results', icon: 'trophy-outline', screen: 'results' },
    { id: 6, title: 'Profile', icon: 'person-circle-outline', screen: 'profile' },
  ];

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
              loadPerformance();
            }}
          >
            <Ionicons name="refresh" size={20} color="#ffffff" />
            <Text style={styles.retryButtonText}>Try Again</Text>
          </TouchableOpacity>
        </View>
      </View>
    );
  }

  const children = performanceData?.children || [];
  const performance_data = performanceData?.performance_data || {};
  const current_term = performanceData?.current_term || 'Term 1';
  
  // Get all unique exam types from the selected child's performance
  const examTypes = selectedChild && performance_data[selectedChild]?.performance_by_exam_type 
    ? Object.keys(performance_data[selectedChild].performance_by_exam_type).sort()
    : [];

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
          <Text style={styles.pageTitle}>Performance</Text>
          <Text style={styles.pageSubtitle}>
            View academic performance for your children
          </Text>
        </View>

        {/* Child Selection */}
        {children.length > 1 && (
          <View style={styles.card}>
            <Text style={styles.cardTitle}>Select Child</Text>
            <ScrollView horizontal showsHorizontalScrollIndicator={false} style={styles.childrenScroll}>
              {children.map((child) => (
                <TouchableOpacity 
                  key={child.id} 
                  style={[
                    styles.childChip,
                    selectedChild === child.id && styles.childChipActive
                  ]}
                  onPress={() => setSelectedChild(child.id)}
                >
                  <Text style={[
                    styles.childChipText,
                    selectedChild === child.id && styles.childChipTextActive
                  ]}>
                    {child.name}
                  </Text>
                </TouchableOpacity>
              ))}
            </ScrollView>
          </View>
        )}

        {/* Year and Term Selection */}
        <View style={styles.card}>
          <Text style={styles.cardTitle}>Filter by Year and Term</Text>
          
          <View style={styles.filterSection}>
            <Text style={styles.filterLabel}>Year</Text>
            <ScrollView horizontal showsHorizontalScrollIndicator={false} style={styles.filterScroll}>
              {performanceData?.years?.map((year) => (
                <TouchableOpacity 
                  key={year} 
                  style={[
                    styles.filterChip,
                    selectedYear === year && styles.filterChipActive
                  ]}
                  onPress={() => setSelectedYear(year)}
                >
                  <Text style={[
                    styles.filterChipText,
                    selectedYear === year && styles.filterChipTextActive
                  ]}>
                    {year}
                  </Text>
                </TouchableOpacity>
              ))}
            </ScrollView>
          </View>

          <View style={styles.filterSection}>
            <Text style={styles.filterLabel}>Term</Text>
            <ScrollView horizontal showsHorizontalScrollIndicator={false} style={styles.filterScroll}>
              {performanceData?.terms?.map((term) => (
                <TouchableOpacity 
                  key={term} 
                  style={[
                    styles.filterChip,
                    selectedTerm === term && styles.filterChipActive
                  ]}
                  onPress={() => setSelectedTerm(term)}
                >
                  <Text style={[
                    styles.filterChipText,
                    selectedTerm === term && styles.filterChipTextActive
                  ]}>
                    {term}
                  </Text>
                </TouchableOpacity>
              ))}
            </ScrollView>
          </View>
        </View>

        {/* Performance Display */}
        {selectedChild && performance_data[selectedChild] ? (
          <View style={styles.card}>
            <Text style={styles.cardTitle}>
              {performance_data[selectedChild].child.name} - Class: {performance_data[selectedChild].child.class}
            </Text>
            <Text style={styles.cardSubtitle}>
              Year: {selectedYear || performanceData?.current_year} | Term: {selectedTerm || current_term}
            </Text>
            
            {performance_data[selectedChild].performance_by_exam_type && Object.keys(performance_data[selectedChild].performance_by_exam_type).length > 0 ? (
              Object.entries(performance_data[selectedChild].performance_by_exam_type)
                .filter(([examType]) => examType !== 'Regular')
                .map(([examType, performance]) => {
                  // Filter performance data by selected year and term
                  const filteredPerformance = performance.filter(
                    (result) => {
                      const yearMatch = !selectedYear || String(result.year) === String(selectedYear);
                      const termMatch = !selectedTerm || result.term === selectedTerm;
                      console.log('Filter check:', { subject: result.subject, resultYear: result.year, selectedYear, yearMatch, resultTerm: result.term, selectedTerm, termMatch });
                      return yearMatch && termMatch;
                    }
                  );
                  
                  console.log('Filtered results for', examType, ':', filteredPerformance.length, 'of', performance.length, 'records');
                  
                  if (filteredPerformance.length === 0) return null;
                  
                  return (
                    <View key={examType} style={styles.examSection}>
                      <Text style={styles.examTitle}>
                        <Ionicons name="list" size={20} color="#FF6B35" />
                        {examType}
                      </Text>
                      
                      <ScrollView horizontal showsHorizontalScrollIndicator={true} style={styles.tableScroll}>
                        <View style={styles.tableContainer}>
                          <View style={styles.table}>
                            <View style={styles.headerRow}>
                              <View style={styles.headerCell}>
                                <Text style={styles.headerText}>Subject</Text>
                              </View>
                              <View style={styles.headerCell}>
                                <Text style={styles.headerText}>Grade</Text>
                              </View>
                              <View style={styles.headerCell}>
                                <Text style={styles.headerText}>Marks</Text>
                              </View>
                              <View style={styles.headerCell}>
                                <Text style={styles.headerText}>Points</Text>
                              </View>
                              <View style={styles.headerCellLast}>
                                <Text style={styles.headerText}>Remarks</Text>
                              </View>
                            </View>
                            {filteredPerformance.map((result, index) => (
                              <View key={result.id || index} style={[styles.dataRow, index % 2 === 1 && styles.dataRowAlternate]}>
                                <View style={styles.dataCell}>
                                  <Text style={styles.dataText}>{result.subject}</Text>
                                </View>
                                <View style={styles.dataCell}>
                                <View style={[styles.gradeBadge, { backgroundColor: getGradeColor(result.grade).backgroundColor }]}>
                                  <Text style={[styles.gradeText, { color: getGradeColor(result.grade).color }]}>{result.grade}</Text>
                                </View>
                              </View>
                              <View style={styles.dataCell}>
                                <Text style={styles.dataText}>{result.marks}</Text>
                              </View>
                              <View style={styles.dataCell}>
                                <Text style={styles.dataText}>{result.grade_points || 'N/A'}</Text>
                              </View>
                              <View style={styles.dataCellLast}>
                                <Text style={styles.remarksText} numberOfLines={2} ellipsizeMode="tail">{result.remarks || '-'}</Text>
                              </View>
                            </View>
                          ))}
                        </View>
                      </View>
                    </ScrollView>
                  </View>
                );
              }).filter(Boolean)
            ) : (
              <View style={styles.emptyState}>
                <Ionicons name="document-text-outline" size={48} color="#5f6368" />
                <Text style={styles.emptyText}>No performance data available</Text>
              </View>
            )}
          </View>
        ) : (
          <View style={styles.card}>
            <View style={styles.emptyState}>
              <Ionicons name="person-outline" size={48} color="#5f6368" />
              <Text style={styles.emptyText}>Select a child to view performance</Text>
            </View>
          </View>
        )}
        
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
                    router.push('/(tabs)/assignments');
                  } else if (feature.screen === 'fees') {
                    router.push('/(tabs)/fees');
                  } else if (feature.screen === 'fines') {
                    router.push('/(tabs)/fines');
                  } else if (feature.screen === 'results') {
                    router.push('/(tabs)/results');
                  } else if (feature.screen === 'profile') {
                    router.push('/(tabs)/profile');
                  } else if (feature.screen === 'performance') {
                    // Already on performance page
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
    marginBottom: 8,
  },
  cardSubtitle: {
    fontSize: 14,
    color: '#5f6368',
    marginBottom: 16,
  },
  childrenScroll: {
    flexDirection: 'row',
  },
  childChip: {
    backgroundColor: '#e8f0fe',
    paddingHorizontal: 16,
    paddingVertical: 8,
    borderRadius: 16,
    marginRight: 8,
  },
  childChipActive: {
    backgroundColor: '#FF6B35',
  },
  childChipText: {
    color: '#1a73e8',
    fontSize: 14,
    fontWeight: '500',
  },
  childChipTextActive: {
    color: 'white',
  },
  filterSection: {
    marginBottom: 16,
  },
  filterLabel: {
    fontSize: 14,
    fontWeight: '500',
    color: '#5f6368',
    marginBottom: 8,
  },
  filterScroll: {
    flexDirection: 'row',
  },
  filterChip: {
    backgroundColor: '#e8f0fe',
    paddingHorizontal: 16,
    paddingVertical: 8,
    borderRadius: 16,
    marginRight: 8,
  },
  filterChipActive: {
    backgroundColor: '#FF6B35',
  },
  filterChipText: {
    color: '#1a73e8',
    fontSize: 14,
    fontWeight: '500',
  },
  filterChipTextActive: {
    color: 'white',
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
  tableScroll: {
    marginHorizontal: -24,
    paddingHorizontal: 24,
  },
  tableContainer: {
    borderWidth: 1,
    borderColor: '#dadce0',
    borderRadius: 8,
    overflow: 'hidden',
    minWidth: 600,
  },
  table: {
    flexDirection: 'column',
    backgroundColor: '#ffffff',
  },
  headerRow: {
    flexDirection: 'row',
    backgroundColor: '#e8f0fe',
    width: 600,
  },
  headerCell: {
    width: 100,
    padding: 12,
    borderRightWidth: 1,
    borderRightColor: '#dadce0',
  },
  headerCellLast: {
    width: 100,
    borderRightWidth: 0,
  },
  headerText: {
    fontSize: 14,
    fontWeight: '600',
    color: '#202124',
    textAlign: 'left',
  },
  dataRow: {
    flexDirection: 'row',
    backgroundColor: '#ffffff',
    borderBottomWidth: 1,
    borderBottomColor: '#dadce0',
    width: 600,
  },
  dataRowAlternate: {
    backgroundColor: '#f8f9fa',
  },
  dataCell: {
    width: 100,
    padding: 12,
    borderRightWidth: 1,
    borderRightColor: '#dadce0',
  },
  dataCellLast: {
    width: 100,
    borderRightWidth: 0,
  },
  dataText: {
    fontSize: 14,
    color: '#202124',
    textAlign: 'left',
  },
  remarksText: {
    fontSize: 11,
    color: '#5f6368',
    textAlign: 'center',
    maxWidth: 60,
  },
  gradeBadge: {
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 4,
    alignSelf: 'flex-start',
  },
  gradeText: {
    fontSize: 13,
    fontWeight: '600',
  },
  examSection: {
    marginBottom: 24,
  },
  examTitle: {
    fontSize: 18,
    fontWeight: '600',
    color: '#FF6B35',
    marginBottom: 12,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
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
    fontSize: 14,
    fontWeight: '500',
    color: '#202124',
    textAlign: 'center',
    marginTop: 8,
  },
});
