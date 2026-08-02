import { View, Text, StyleSheet, TouchableOpacity, ScrollView, ActivityIndicator, RefreshControl } from 'react-native';
import { useRouter } from 'expo-router';
import { useState, useEffect } from 'react';
import { Ionicons } from '@expo/vector-icons';
import { getResults, ResultsResponse } from '../../lib/api';

export default function Results() {
  const router = useRouter();
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [resultsData, setResultsData] = useState<ResultsResponse | null>(null);
  const [selectedChild, setSelectedChild] = useState<number | null>(null);
  const [selectedTerm, setSelectedTerm] = useState<string>('');
  const [selectedYear, setSelectedYear] = useState<string>('');
  const [selectedExamType, setSelectedExamType] = useState<string>('');
  const [performanceScope, setPerformanceScope] = useState<'class' | 'stream'>('stream');
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    loadResults();
  }, []);

  const loadResults = async () => {
    try {
      const data = await getResults(performanceScope);
      setResultsData(data);
      setError(null);
      if (data.current_term) {
        setSelectedTerm(data.current_term);
      }
      if (data.current_year) {
        setSelectedYear(data.current_year);
      }
      // Auto-select child if there's only one
      if (data.children && data.children.length === 1) {
        setSelectedChild(data.children[0].id);
      }
      // Auto-select first exam type if available
      if (data.class_performance_data) {
        const availableExamTypes = Array.from(new Set(
          Object.values(data.class_performance_data).flatMap((studentData: any) =>
            studentData.performance.map((record: any) => record.exam_type || 'Regular')
          )
        )).sort();
        if (availableExamTypes.length > 0 && !selectedExamType) {
          setSelectedExamType(availableExamTypes[0]);
        }
      }
    } catch (error: any) {
      if (error.message === 'NOT_AUTHENTICATED') {
        router.replace('/login');
      } else {
        setError(error.message || 'Failed to load results data');
      }
    } finally {
      setLoading(false);
    }
  };

  const onRefresh = async () => {
    setRefreshing(true);
    await loadResults();
    setRefreshing(false);
  };

  const handleScopeChange = async (scope: 'class' | 'stream') => {
    setPerformanceScope(scope);
    setLoading(true);
    try {
      const data = await getResults(scope);
      setResultsData(data);
      setError(null);
    } catch (error: any) {
      if (error.message === 'NOT_AUTHENTICATED') {
        router.replace('/login');
      } else {
        setError(error.message || 'Failed to load results data');
      }
    } finally {
      setLoading(false);
    }
  };

  // Helper function to get points from marks using grading scales
  const getPointsFromMarks = (marks: number, subject: string, gradingScales: any[]) => {
    const upperSubject = subject.toUpperCase();
    
    // Filter grading scales for the specific subject
    const subjectScales = gradingScales.filter((scale: any) => {
      if (!scale.subject_name) return false;
      return scale.subject_name.toUpperCase() === upperSubject;
    });
    
    // Try subject-specific scales first
    for (const scale of subjectScales) {
      if (marks >= scale.min_score && marks <= scale.max_score) {
        return scale.points || 0;
      }
    }
    
    // If no subject-specific match, try general scales (subject_id is null)
    const generalScales = gradingScales.filter((scale: any) => scale.subject_id === null);
    for (const scale of generalScales) {
      if (marks >= scale.min_score && marks <= scale.max_score) {
        return scale.points || 0;
      }
    }
    
    // Fallback to any matching scale if still no match
    for (const scale of gradingScales) {
      if (marks >= scale.min_score && marks <= scale.max_score) {
        return scale.points || 0;
      }
    }
    
    return 0;
  };

  // Function to get aggregate grade based on total points
  const getAggregateGrade = (totalPoints: number, aggregateDistribution: any[]) => {
    if (!aggregateDistribution || aggregateDistribution.length === 0) {
      return '-';
    }
    for (const dist of aggregateDistribution) {
      if (totalPoints >= dist.min_points && totalPoints <= dist.max_points) {
        return dist.grade_name;
      }
    }
    return '-';
  };

  // Calculate class rankings
  const calculateClassRankings = () => {
    if (!resultsData || !resultsData.class_performance_data) return [];
    
    const classPerformance = resultsData.class_performance_data;
    const gradingScales = resultsData.grading_scales || [];
    const aggregateDistribution = resultsData.aggregate_distribution || [];
    const studentSubjectAssignments = resultsData.student_subject_assignments || {};
    const schoolMinSubjects = resultsData.school_settings?.min_subjects || 7;
    
    // Group by exam type
    const groupedByExamType: { [key: string]: any[] } = {};
    
    Object.values(classPerformance).forEach((studentData: any) => {
      studentData.performance.forEach((record: any) => {
        const examType = record.exam_type || 'Regular';
        if (!groupedByExamType[examType]) {
          groupedByExamType[examType] = [];
        }
        groupedByExamType[examType].push({
          ...record,
          student: studentData.student
        });
      });
    });
    
    // Process each exam type
    const examTypeResults: any[] = [];
    
    Object.entries(groupedByExamType).forEach(([examType, records]) => {
      // Get selected child's assigned subjects to determine which subjects to show
      // If no child selected, use the first child's subjects
      const targetChildId = selectedChild || (children.length > 0 ? children[0].id : null);
      const selectedChildData = children.find((c: any) => c.id === targetChildId);
      const selectedChildAdmission = selectedChildData?.admission_number;
      const childAssignedSubjects = selectedChildAdmission ? (studentSubjectAssignments[selectedChildAdmission] || []) : [];
      const childSubjectNames = childAssignedSubjects.map((s: any) => s.subject_name);
      
      console.log('Selected child admission:', selectedChildAdmission);
      console.log('Child assigned subjects:', childAssignedSubjects);
      console.log('Child subject names:', childSubjectNames);
      
      // Group by student
      const groupedByStudent: { [key: string]: any } = {};
      const allSubjects = new Set<string>();
      
      // First, add all assigned subjects to the subject list
      childSubjectNames.forEach((subjectName: string) => {
        allSubjects.add(subjectName);
      });
      
      console.log('Initial allSubjects:', Array.from(allSubjects));
      
      records.forEach((record: any) => {
        // Filter by selected term and year
        const yearMatch = !selectedYear || String(record.year) === String(selectedYear);
        const termMatch = !selectedTerm || record.term === selectedTerm;
        
        if (!yearMatch || !termMatch) {
          return; // Skip records that don't match the selected term/year
        }
        
        const studentKey = `${record.student.admission_number}_${record.student.name}_${record.student.class}_${record.student.stream}`;
        if (!groupedByStudent[studentKey]) {
          groupedByStudent[studentKey] = {
            ...record.student,
            subjects: {}
          };
        }
        // Add all performance records, but filter later by assigned subjects
        groupedByStudent[studentKey].subjects[record.subject] = {
          ...record,
          grade_points: record.grade_points
        };
        allSubjects.add(record.subject);
      });
      
      console.log('Final allSubjects:', Array.from(allSubjects));
      
      // Filter the final subject list to only include subjects assigned to the selected child
      const filteredSubjects = Array.from(allSubjects).filter((subject: string) => 
        childSubjectNames.some((assignedSubject: string) => 
          assignedSubject.toUpperCase() === subject.toUpperCase()
        )
      );
      
      console.log('Filtered subjects:', filteredSubjects);
      
      // Sort subjects alphabetically
      const sortedSubjects = filteredSubjects.sort();
      
      console.log('Sorted subjects:', sortedSubjects);
      
      // Calculate totals for each student
      const studentsWithTotal = Object.values(groupedByStudent).map((student: any) => {
        let totalMarks = 0;
        let totalPoints = 0;
        let count = 0;
        
        // Get assigned subjects for this student
        const studentId = student.admission_number;
        const assignedSubjects = studentSubjectAssignments[studentId] || [];
        
        // Calculate points for each assigned subject
        const subjectPoints: any[] = [];
        Object.values(student.subjects).forEach((record: any) => {
          const assigned = assignedSubjects.find((s: any) => s.subject_name === record.subject);
          if (assigned) {
            const marks = parseFloat(record.marks) || 0;
            const points = record.grade_points || getPointsFromMarks(marks, record.subject, gradingScales);
            subjectPoints.push({
              subject: record.subject,
              marks: marks,
              points: points,
              is_compulsory: assigned.is_compulsory
            });
          }
        });
        
        // Separate compulsory and non-compulsory subjects
        const compulsorySubjects = subjectPoints.filter((s: any) => s.is_compulsory === 1);
        const nonCompulsorySubjects = subjectPoints.filter((s: any) => s.is_compulsory !== 1);
        
        // Sort non-compulsory by points descending, then by marks descending
        nonCompulsorySubjects.sort((a: any, b: any) => {
          if (b.points !== a.points) {
            return b.points - a.points;
          }
          return b.marks - a.marks;
        });
        
        // Calculate how many non-compulsory subjects we need to reach minimum
        const compulsoryCount = compulsorySubjects.length;
        const neededNonCompulsory = Math.max(0, schoolMinSubjects - compulsoryCount);
        
        // Take top non-compulsory subjects to reach minimum
        const selectedNonCompulsory = nonCompulsorySubjects.slice(0, neededNonCompulsory);
        
        // Combine compulsory + selected non-compulsory for grading
        const gradingSubjects = [...compulsorySubjects, ...selectedNonCompulsory];
        
        // Calculate totals from grading subjects
        gradingSubjects.forEach((sub: any) => {
          totalMarks += sub.marks;
          totalPoints += sub.points;
          count++;
        });
        
        // Get all subject marks for display (not just grading subjects)
        const allSubjectMarks = sortedSubjects.map((subject: string) => {
          const record = student.subjects[subject];
          
          if (record) {
            const isGradingSubject = gradingSubjects.find((s: any) => s.subject === subject);
            return {
              subject: subject,
              marks: record.marks,
              grade: record.grade,
              grade_points: record.grade_points,
              isGradingSubject: !!isGradingSubject
            };
          }
          // Subject is in the list (because selected child is assigned) but this student has no marks
          return {
            subject: subject,
            marks: '-',
            grade: '-',
            grade_points: '-',
            isGradingSubject: false
          };
        });
        
        console.log('Student:', student.name, 'All subject marks:', allSubjectMarks);
        
        return {
          ...student,
          totalMarks,
          average: count > 0 ? (totalMarks / count).toFixed(1) : 0,
          totalPoints,
          subjectCount: count,
          assignedSubjectCount: assignedSubjects.length,
          compulsoryCount,
          gradingSubjects,
          allSubjectMarks
        };
      });
      
      // Filter students to only include those with minimum subjects for grading
      const eligibleStudents = studentsWithTotal.filter((student: any) => 
        student.assignedSubjectCount >= schoolMinSubjects
      );
      
      // Sort eligible students by total points descending for ranking
      eligibleStudents.sort((a: any, b: any) => b.totalPoints - a.totalPoints);
      
      examTypeResults.push({
        examType,
        students: eligibleStudents,
        subjects: sortedSubjects,
        ineligibleCount: studentsWithTotal.length - eligibleStudents.length
      });
    });
    
    return examTypeResults;
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
    { id: 2, title: 'Performance', icon: 'trending-up-outline', screen: 'performance' },
    { id: 3, title: 'Fees', icon: 'wallet-outline', screen: 'fees' },
    { id: 4, title: 'Fines', icon: 'information-circle-outline', screen: 'fines' },
    { id: 5, title: 'Assignments', icon: 'document-text-outline', screen: 'assignments' },
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

  const children = resultsData?.children || [];
  const class_performance_data = resultsData?.class_performance_data || {};
  const terms = resultsData?.terms || [];
  const years = resultsData?.years || [];
  const current_year = resultsData?.current_year || '';
  
  // Get available exam types from performance data
  const examTypes = Array.from(new Set(
    Object.values(class_performance_data).flatMap((studentData: any) =>
      studentData.performance.map((record: any) => record.exam_type || 'Regular')
    )
  )).sort();
  
  const classRankings = calculateClassRankings();
  
  // Filter rankings by selected exam type
  const filteredRankings = selectedExamType 
    ? classRankings.filter((ranking: any) => ranking.examType === selectedExamType)
    : classRankings;

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
              loadResults();
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
        <View style={styles.pageHeader}>
          <Text style={styles.pageTitle}>Results</Text>
          <Text style={styles.pageSubtitle}>
            View examination results and rankings for your children
          </Text>
        </View>

        {/* Performance Scope Selection */}
        <View style={styles.card}>
          <Text style={styles.cardTitle}>Performance Scope</Text>
          <View style={styles.scopeToggle}>
            <TouchableOpacity 
              style={[styles.scopeButton, performanceScope === 'stream' && styles.scopeButtonActive]}
              onPress={() => handleScopeChange('stream')}
            >
              <Text style={[styles.scopeButtonText, performanceScope === 'stream' && styles.scopeButtonTextActive]}>
                Stream Performance
              </Text>
            </TouchableOpacity>
            <TouchableOpacity 
              style={[styles.scopeButton, performanceScope === 'class' && styles.scopeButtonActive]}
              onPress={() => handleScopeChange('class')}
            >
              <Text style={[styles.scopeButtonText, performanceScope === 'class' && styles.scopeButtonTextActive]}>
                Class Performance
              </Text>
            </TouchableOpacity>
          </View>
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

        {/* Term Selection */}
        {terms.length > 0 && (
          <View style={styles.card}>
            <Text style={styles.cardTitle}>Select Term</Text>
            <ScrollView horizontal showsHorizontalScrollIndicator={false} style={styles.childrenScroll}>
              {terms.map((term) => (
                <TouchableOpacity 
                  key={term} 
                  style={[
                    styles.childChip,
                    selectedTerm === term && styles.childChipActive
                  ]}
                  onPress={() => setSelectedTerm(term)}
                >
                  <Text style={[
                    styles.childChipText,
                    selectedTerm === term && styles.childChipTextActive
                  ]}>
                    {term}
                  </Text>
                </TouchableOpacity>
              ))}
            </ScrollView>
          </View>
        )}

        {/* Year Selection */}
        {years && years.length > 0 && (
          <View style={styles.card}>
            <Text style={styles.cardTitle}>Select Year</Text>
            <ScrollView horizontal showsHorizontalScrollIndicator={false} style={styles.childrenScroll}>
              {years.map((year: string) => (
                <TouchableOpacity 
                  key={year} 
                  style={[
                    styles.childChip,
                    selectedYear === year && styles.childChipActive
                  ]}
                  onPress={() => setSelectedYear(year)}
                >
                  <Text style={[
                    styles.childChipText,
                    selectedYear === year && styles.childChipTextActive
                  ]}>
                    {year}
                  </Text>
                </TouchableOpacity>
              ))}
            </ScrollView>
          </View>
        )}
        
        {/* Exam Type Selection */}
        {examTypes.length > 0 && (
          <View style={styles.card}>
            <Text style={styles.cardTitle}>Select Exam Type</Text>
            <ScrollView horizontal showsHorizontalScrollIndicator={false} style={styles.childrenScroll}>
              {examTypes.map((examType) => (
                <TouchableOpacity 
                  key={examType} 
                  style={[
                    styles.childChip,
                    selectedExamType === examType && styles.childChipActive
                  ]}
                  onPress={() => setSelectedExamType(examType)}
                >
                  <Text style={[
                    styles.childChipText,
                    selectedExamType === examType && styles.childChipTextActive
                  ]}>
                    {examType}
                  </Text>
                </TouchableOpacity>
              ))}
            </ScrollView>
          </View>
        )}

        {/* Results Display */}
        {filteredRankings.length > 0 ? (
          <View style={styles.card}>
            <Text style={styles.cardTitle}>
              Class Rankings - {selectedChild ? children.find(c => c.id === selectedChild)?.name : 'All Students'}
            </Text>
            <Text style={styles.cardSubtitle}>
              Year: {current_year} | Term: {selectedTerm} | Exam: {selectedExamType}
            </Text>
            
            {filteredRankings.map((examResult: any, examIndex: number) => (
              <View key={examIndex} style={styles.examSection}>
                <Text style={styles.examTitle}>
                  <Ionicons name="list" size={20} color="#FF6B35" />
                  {examResult.examType}
                </Text>
                
                {examResult.ineligibleCount > 0 && (
                  <View style={styles.warningBox}>
                    <Ionicons name="warning" size={16} color="#b06000" />
                    <Text style={styles.warningText}>
                      {examResult.ineligibleCount} student(s) not shown (fewer than {resultsData?.school_settings?.min_subjects} subjects)
                    </Text>
                  </View>
                )}
                
                <View style={styles.infoBox}>
                  <Ionicons name="information-circle" size={16} color="#1a73e8" />
                  <Text style={styles.infoText}>
                    Grading uses all compulsory subjects plus best non-compulsory subjects
                  </Text>
                </View>
                
                <ScrollView style={styles.verticalTableScroll}>
                  {examResult.students.map((student: any, index: number) => (
                    <View key={student.id || index} style={styles.studentCard}>
                      <View style={styles.studentCardHeader}>
                        <View style={[styles.rankBadge, index === 0 && styles.goldRankBadge]}>
                          <Text style={[styles.rankBadgeText, index === 0 && styles.goldRankText]}>#{index + 1}</Text>
                        </View>
                        <View style={styles.studentInfo}>
                          <Text style={styles.studentName}>{student.name}</Text>
                          <Text style={styles.studentClass}>{student.class}</Text>
                        </View>
                      </View>
                      
                      <ScrollView horizontal showsHorizontalScrollIndicator={true} style={styles.tableScroll}>
                        <View style={styles.tableContainer}>
                          <View style={styles.table}>
                            <View style={styles.headerRow}>
                              <View style={styles.headerCellFirst}>
                                <Text style={styles.headerText}>Subject</Text>
                              </View>
                              <View style={styles.headerCell}>
                                <Text style={styles.headerText}>Marks</Text>
                              </View>
                              <View style={styles.headerCell}>
                                <Text style={styles.headerText}>Points</Text>
                              </View>
                              <View style={styles.headerCellLast}>
                                <Text style={styles.headerText}>Grade</Text>
                              </View>
                            </View>
                            {examResult.subjects.map((subject: string) => {
                              const subjectMark = student.allSubjectMarks.find((s: any) => s.subject === subject);
                              const marks = subjectMark?.marks || '-';
                              // Use grade_points from the database if available, otherwise show '-'
                              const points = subjectMark?.grade_points ?? '-';
                              return (
                                <View key={subject} style={styles.dataRow}>
                                  <View style={styles.dataCellFirst}>
                                    <View style={styles.subjectInfo}>
                                      <Text style={styles.dataTextLeft}>{subject}</Text>
                                      {subjectMark?.isGradingSubject && (
                                        <Text style={styles.gradingIndicator}>✓</Text>
                                      )}
                                    </View>
                                  </View>
                                  <View style={styles.dataCell}>
                                    <Text style={styles.dataText}>{marks}</Text>
                                  </View>
                                  <View style={styles.dataCell}>
                                    <Text style={styles.dataText}>{points}</Text>
                                  </View>
                                  <View style={styles.dataCellLast}>
                                    <View style={[styles.gradeBadge, { backgroundColor: getGradeColor(subjectMark?.grade || '-').backgroundColor }]}>
                                      <Text style={[styles.gradeText, { color: getGradeColor(subjectMark?.grade || '-').color }]}>
                                        {subjectMark?.grade || '-'}
                                      </Text>
                                    </View>
                                  </View>
                                </View>
                              );
                            })}
                          </View>
                        </View>
                      </ScrollView>
                      
                      {/* Performance Summary */}
                      <View style={styles.performanceSummary}>
                        <View style={styles.summaryRow}>
                          <View style={styles.summaryItem}>
                            <Text style={styles.summaryLabel}>Class Position</Text>
                            <Text style={styles.summaryValue}>#{index + 1} / {examResult.students.length}</Text>
                          </View>
                          <View style={styles.summaryItem}>
                            <Text style={styles.summaryLabel}>Total Marks</Text>
                            <Text style={styles.summaryValue}>{student.totalMarks}</Text>
                          </View>
                          <View style={styles.summaryItem}>
                            <Text style={styles.summaryLabel}>Average</Text>
                            <Text style={styles.summaryValue}>{student.average}</Text>
                          </View>
                        </View>
                        <View style={styles.summaryRow}>
                          <View style={styles.summaryItem}>
                            <Text style={styles.summaryLabel}>Total Points</Text>
                            <Text style={styles.summaryValue}>{student.totalPoints}</Text>
                          </View>
                          <View style={styles.summaryItem}>
                            <Text style={styles.summaryLabel}>Aggregate Grade</Text>
                            <View style={[styles.gradeBadge, { backgroundColor: getGradeColor(getAggregateGrade(student.totalPoints, resultsData?.aggregate_distribution || [])).backgroundColor }]}>
                              <Text style={[styles.gradeText, { color: getGradeColor(getAggregateGrade(student.totalPoints, resultsData?.aggregate_distribution || [])).color }]}>
                                {getAggregateGrade(student.totalPoints, resultsData?.aggregate_distribution || [])}
                              </Text>
                            </View>
                          </View>
                          <View style={styles.summaryItem}>
                            <Text style={styles.summaryLabel}>Subjects</Text>
                            <Text style={styles.summaryValue}>{student.subjectCount} / {student.assignedSubjectCount}</Text>
                          </View>
                        </View>
                      </View>
                    </View>
                  ))}
                </ScrollView>
              </View>
            ))}
          </View>
        ) : (
          <View style={styles.card}>
            <View style={styles.emptyState}>
              <Ionicons name="document-text-outline" size={48} color="#5f6368" />
              <Text style={styles.emptyText}>No results data available</Text>
            </View>
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
                  } else if (feature.screen === 'fees') {
                    router.push('/(tabs)/fees');
                  } else if (feature.screen === 'fines') {
                    router.push('/(tabs)/fines');
                  } else if (feature.screen === 'performance') {
                    router.push('/(tabs)/performance');
                  } else if (feature.screen === 'profile') {
                    router.push('/(tabs)/profile');
                  } else if (feature.screen === 'results') {
                    // Already on results page
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
  scopeToggle: {
    flexDirection: 'row',
    backgroundColor: '#f1f3f4',
    borderRadius: 8,
    padding: 4,
  },
  scopeButton: {
    flex: 1,
    padding: 12,
    borderRadius: 6,
    alignItems: 'center',
  },
  scopeButtonActive: {
    backgroundColor: '#FF6B35',
  },
  scopeButtonText: {
    fontSize: 14,
    color: '#5f6368',
    fontWeight: '500',
  },
  scopeButtonTextActive: {
    color: '#ffffff',
    fontWeight: '600',
  },
  card: {
    backgroundColor: '#f8f9fa',
    borderRadius: 8,
    padding: 16,
    marginBottom: 24,
    marginHorizontal: 8,
  },
  quickActionsCard: {
    marginBottom: 80,
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
  emptyState: {
    alignItems: 'center',
    padding: 40,
  },
  emptyText: {
    marginTop: 16,
    color: '#5f6368',
    fontSize: 14,
  },
  verticalTableScroll: {
    marginTop: 16,
  },
  tableScroll: {
    marginTop: 12,
  },
  tableContainer: {
    backgroundColor: '#ffffff',
    borderRadius: 8,
    overflow: 'hidden',
  },
  table: {
    flexDirection: 'column',
    backgroundColor: '#ffffff',
  },
  headerRow: {
    flexDirection: 'row',
    backgroundColor: '#e8f0fe',
    width: 450,
  },
  headerCell: {
    width: 100,
    padding: 12,
    borderRightWidth: 1,
    borderRightColor: '#dadce0',
    alignItems: 'center',
    justifyContent: 'center',
  },
  headerCellFirst: {
    width: 150,
    padding: 12,
    borderRightWidth: 1,
    borderRightColor: '#dadce0',
    alignItems: 'flex-start',
    justifyContent: 'flex-start',
  },
  headerCellLast: {
    width: 100,
    borderRightWidth: 0,
    alignItems: 'center',
    justifyContent: 'center',
  },
  headerText: {
    fontSize: 14,
    fontWeight: '600',
    color: '#202124',
    textAlign: 'center',
  },
  dataRow: {
    flexDirection: 'row',
    backgroundColor: '#ffffff',
    borderBottomWidth: 1,
    borderBottomColor: '#dadce0',
    width: 450,
  },
  dataCell: {
    width: 100,
    padding: 12,
    borderRightWidth: 1,
    borderRightColor: '#dadce0',
    alignItems: 'center',
    justifyContent: 'center',
  },
  dataCellFirst: {
    width: 150,
    padding: 12,
    borderRightWidth: 1,
    borderRightColor: '#dadce0',
    alignItems: 'flex-start',
    justifyContent: 'flex-start',
  },
  dataCellLast: {
    width: 100,
    borderRightWidth: 0,
    alignItems: 'center',
    justifyContent: 'center',
  },
  dataText: {
    fontSize: 14,
    color: '#202124',
    textAlign: 'center',
  },
  dataTextLeft: {
    fontSize: 14,
    color: '#202124',
    textAlign: 'left',
  },
  subjectInfo: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    justifyContent: 'center',
  },
  gradingIndicator: {
    fontSize: 12,
    color: '#137333',
    fontWeight: '600',
  },
  performanceSummary: {
    marginTop: 16,
    padding: 16,
    backgroundColor: '#f8f9fa',
    borderRadius: 8,
    borderWidth: 1,
    borderColor: '#e8eaed',
  },
  summaryRow: {
    flexDirection: 'row',
    justifyContent: 'space-around',
    marginBottom: 12,
  },
  summaryItem: {
    alignItems: 'center',
  },
  summaryLabel: {
    fontSize: 12,
    color: '#5f6368',
    marginBottom: 4,
  },
  summaryValue: {
    fontSize: 16,
    fontWeight: '600',
    color: '#202124',
  },
  studentCard: {
    backgroundColor: '#ffffff',
    borderRadius: 8,
    borderWidth: 1,
    borderColor: '#e8eaed',
    marginBottom: 16,
    overflow: 'hidden',
    width: '100%',
  },
  studentCardHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    padding: 16,
    backgroundColor: '#f8f9fa',
    borderBottomWidth: 1,
    borderBottomColor: '#e8eaed',
  },
  rankBadge: {
    backgroundColor: '#FF6B35',
    borderRadius: 12,
    paddingHorizontal: 8,
    paddingVertical: 4,
    marginRight: 12,
  },
  rankBadgeText: {
    color: '#ffffff',
    fontSize: 14,
    fontWeight: '600',
  },
  goldCard: {
    borderColor: '#FFD700',
    borderWidth: 2,
    backgroundColor: '#fffef0',
  },
  goldRankBadge: {
    backgroundColor: '#FFD700',
  },
  goldRankText: {
    color: '#000000',
  },
  studentInfo: {
    alignItems: 'center',
    flex: 1,
  },
  studentName: {
    fontSize: 16,
    fontWeight: '600',
    color: '#202124',
  },
  studentClass: {
    fontSize: 14,
    color: '#5f6368',
  },
  studentTotals: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
  },
  totalItem: {
    alignItems: 'center',
  },
  totalLabel: {
    fontSize: 11,
    color: '#5f6368',
  },
  totalValue: {
    fontSize: 14,
    fontWeight: '600',
    color: '#202124',
  },
  subjectMarksVertical: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  subjectMarksText: {
    fontSize: 14,
    fontWeight: '500',
    color: '#202124',
    minWidth: 40,
    textAlign: 'right',
  },
  miniGradeBadge: {
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 4,
    minWidth: 32,
    alignItems: 'center',
  },
  miniGradeText: {
    fontSize: 12,
    fontWeight: '600',
  },
  gradeBadge: {
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 4,
    alignSelf: 'center',
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
  warningBox: {
    backgroundColor: '#fef7e0',
    borderRadius: 8,
    padding: 12,
    marginBottom: 12,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  warningText: {
    fontSize: 13,
    color: '#b06000',
    flex: 1,
  },
  infoBox: {
    backgroundColor: '#e8f0fe',
    borderRadius: 8,
    padding: 12,
    marginBottom: 12,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  infoText: {
    fontSize: 13,
    color: '#1a73e8',
    flex: 1,
  },
  rankText: {
    fontSize: 14,
    fontWeight: '600',
    color: '#202124',
  },
  quickActionsSection: {
    marginTop: 24,
  },
  quickActionsTitle: {
    fontSize: 18,
    fontWeight: '600',
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
  },
});
