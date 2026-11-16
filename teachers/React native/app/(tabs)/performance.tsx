import { View, Text, StyleSheet, ScrollView, TouchableOpacity, ActivityIndicator, RefreshControl, Modal, Alert, TextInput, KeyboardAvoidingView, Platform } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useRouter } from 'expo-router';
import { useState, useEffect } from 'react';
import { Ionicons } from '@expo/vector-icons';
import { getPerformance, PerformanceResponse, getResults, ResultsResponse } from '../../lib/api';
import * as DocumentPicker from 'expo-document-picker';
import * as FileSystem from 'expo-file-system';
import * as Sharing from 'expo-sharing';

export default function Performance() {
  const router = useRouter();
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [performanceData, setPerformanceData] = useState<PerformanceResponse | null>(null);
  const [resultsData, setResultsData] = useState<ResultsResponse | null>(null);
  const [showResults, setShowResults] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [selectedExamType, setSelectedExamType] = useState<string | null>(null);
  const [showExamTypePicker, setShowExamTypePicker] = useState(false);
  const [showBulkUpload, setShowBulkUpload] = useState(false);
  const [selectedTerm, setSelectedTerm] = useState<string>('');
  const [selectedYear, setSelectedYear] = useState<string>('2026');
  const [selectedSubject, setSelectedSubject] = useState<string>('');
  const [selectedStream, setSelectedStream] = useState<string>('');
  const [selectedFile, setSelectedFile] = useState<any>(null);
  const [uploading, setUploading] = useState(false);
  const [showStreamPicker, setShowStreamPicker] = useState(false);
  const [showSubjectPicker, setShowSubjectPicker] = useState(false);
  const [showTermPicker, setShowTermPicker] = useState(false);
  const [showReports, setShowReports] = useState(false);
  const [showAnalytics, setShowAnalytics] = useState(false);
  const [reportTerm, setReportTerm] = useState<string>('');
  const [reportYear, setReportYear] = useState<string>('');
  const [reportSubject, setReportSubject] = useState<string>('');
  const [reportStream, setReportStream] = useState<string>('');
  const [reportExamType, setReportExamType] = useState<string>('');
  const [reportResults, setReportResults] = useState<any[]>([]);
  const [loadingReport, setLoadingReport] = useState(false);

  useEffect(() => {
    loadPerformance();
  }, []);

  const loadPerformance = async () => {
    try {
      const data = await getPerformance();
      console.log('Performance data loaded:', data);
      setPerformanceData(data);
      setError(null);
    } catch (error: any) {
      console.error('Performance load error:', error);
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

  const loadResults = async () => {
    try {
      const data = await getResults();
      console.log('Results data loaded:', data);
      setResultsData(data);
    } catch (error: any) {
      console.error('Failed to load results:', error);
      Alert.alert('Error', error.message || 'Failed to load results data');
    }
  };

  const handleGenerateReport = async () => {
    if (!reportTerm || !reportYear) {
      Alert.alert('Error', 'Please select term and year');
      return;
    }

    setLoadingReport(true);
    try {
      // Filter performance records based on report criteria
      const filtered = performanceData?.performance_records?.filter((r: any) => {
        if (reportTerm && r.term !== reportTerm) return false;
        if (reportYear && r.year !== parseInt(reportYear)) return false;
        if (reportSubject && r.subject_name !== reportSubject && r.subject !== reportSubject) return false;
        if (reportStream && r.stream_id !== parseInt(reportStream)) return false;
        if (reportExamType && r.exam_type_id !== parseInt(reportExamType)) return false;
        return true;
      }) || [];

      setReportResults(filtered);
    } catch (error: any) {
      Alert.alert('Error', error.message || 'Failed to generate report');
    } finally {
      setLoadingReport(false);
    }
  };

  const handleExportReport = async () => {
    if (reportResults.length === 0) {
      Alert.alert('Error', 'No data to export');
      return;
    }

    try {
      const csvHeader = 'Adm No,Name,Subject,Exam Type,Term,Year,Marks,Grade\n';
      const csvBody = reportResults.map((r: any) => 
        `${r.admission_number},${r.first_name} ${r.last_name},${r.subject_name || r.subject},${r.exam_type_name},${r.term},${r.year},${r.marks},${r.grade}`
      ).join('\n');
      
      const csvText = csvHeader + csvBody;
      const fileUri = (FileSystem as any).documentDirectory + 'performance_report.csv';
      await FileSystem.writeAsStringAsync(fileUri, csvText);
      
      if (await Sharing.isAvailableAsync()) {
        await Sharing.shareAsync(fileUri);
      } else {
        Alert.alert('Success', 'Report exported successfully');
      }
    } catch (error: any) {
      Alert.alert('Error', error.message || 'Failed to export report');
    }
  };

  const getGradeColor = (grade: string) => {
    const gradeUpper = grade.toUpperCase();
    if (gradeUpper.startsWith('A')) return '#4CAF50';
    if (gradeUpper.startsWith('B')) return '#2196F3';
    if (gradeUpper.startsWith('C')) return '#FF9800';
    if (gradeUpper.startsWith('D')) return '#FF5722';
    return '#9E9E9E';
  };

  const handleDownloadTemplate = async () => {
    if (!selectedStream) {
      Alert.alert('Error', 'Please select a stream first to generate the template');
      return;
    }

    try {
      const sessionData = await import('expo-secure-store').then(m => m.getItemAsync('teacherSession'));
      if (!sessionData) {
        throw new Error('NOT_AUTHENTICATED');
      }
      const session = JSON.parse(sessionData);
      const sessionToken = session.session_token;

      const response = await fetch(`${require('../../lib/api').API_BASE_URL}/performance.php?download_template=true&stream_id=${selectedStream}`, {
        method: 'GET',
        headers: {
          'Authorization': sessionToken,
        },
      });

      if (!response.ok) {
        throw new Error('Failed to download template');
      }

      const csvText = await response.text();
      const fileUri = (FileSystem as any).documentDirectory + 'performance_template.csv';
      await FileSystem.writeAsStringAsync(fileUri, csvText);
      
      if (await Sharing.isAvailableAsync()) {
        await Sharing.shareAsync(fileUri);
      } else {
        Alert.alert('Success', 'Template downloaded successfully');
      }
    } catch (error: any) {
      Alert.alert('Error', error.message || 'Failed to download template');
    }
  };

  const handlePickFile = async () => {
    try {
      const result = await DocumentPicker.getDocumentAsync({
        type: ['text/csv', 'application/vnd.ms-excel', 'application/csv'],
        copyToCacheDirectory: true,
      });

      console.log('Document picker result:', result);

      if (result.canceled) {
        console.log('File selection canceled');
        return;
      }

      if (result.assets && result.assets.length > 0) {
        setSelectedFile(result.assets[0]);
        console.log('File selected:', result.assets[0]);
      } else {
        Alert.alert('Error', 'No file selected');
      }
    } catch (error: any) {
      console.error('File picker error:', error);
      Alert.alert('Error', error.message || 'Failed to pick file');
    }
  };

  const handleBulkUpload = async () => {
    if (!selectedTerm || !selectedYear || !selectedExamType || !selectedSubject || !selectedStream || !selectedFile) {
      Alert.alert('Error', 'Please fill all fields and select a file');
      return;
    }

    setUploading(true);
    try {
      const sessionData = await import('expo-secure-store').then(m => m.getItemAsync('teacherSession'));
      if (!sessionData) {
        throw new Error('NOT_AUTHENTICATED');
      }
      const session = JSON.parse(sessionData);
      const sessionToken = session.session_token;

      const formData = new FormData();
      formData.append('term', selectedTerm);
      formData.append('year', selectedYear);
      formData.append('exam_type_id', selectedExamType);
      formData.append('subject', selectedSubject);
      formData.append('streamId', selectedStream);
      formData.append('performance_file', {
        uri: selectedFile.uri,
        type: 'text/csv',
        name: selectedFile.name,
      } as any);

      const response = await fetch(`${require('../../lib/api').API_BASE_URL}/performance.php`, {
        method: 'POST',
        headers: {
          'Authorization': sessionToken,
          'Content-Type': 'multipart/form-data',
        },
        body: formData,
      });

      const data = await response.json();

      if (data.success) {
        Alert.alert('Success', 'Performance records uploaded successfully');
        setShowBulkUpload(false);
        setSelectedFile(null);
        await loadPerformance();
      } else {
        Alert.alert('Error', data.error || 'Failed to upload performance records');
      }
    } catch (error: any) {
      Alert.alert('Error', error.message || 'Failed to upload performance records');
    } finally {
      setUploading(false);
    }
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
        <TouchableOpacity 
          style={styles.viewToggle}
          onPress={() => {
            if (!showResults) {
              loadResults();
            }
            setShowResults(!showResults);
          }}
        >
          <Text style={styles.viewToggleText}>{showResults ? 'Performance' : 'Results'}</Text>
        </TouchableOpacity>
      </View>

      <ScrollView 
        style={styles.content} 
        showsVerticalScrollIndicator={false}
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={onRefresh}
            colors={['#FF6B35']}
          />
        }
      >
        {showResults ? (
          // Results View
          <View style={styles.sectionContainer}>
            <Text style={styles.sectionTitle}>Aggregate Results</Text>
            {resultsData ? (
              (() => {
                // Calculate aggregate results
                const gradingScales = resultsData.grading_scales || [];
                const aggregateDistribution = resultsData.aggregate_distribution || [];
                const schoolLimits = resultsData.school_limits || { min_subjects: 7, max_subjects: 8 };
                const studentAssignments = resultsData.student_subject_assignments || {};
                const performanceRecords = resultsData.performance_records || [];

                // Function to get points from marks
                const getPointsFromMarks = (marks: number, subject: string) => {
                  const subjectUpper = subject.toUpperCase();
                  const subjectScales = gradingScales.filter((s: any) => 
                    s.subject_name && s.subject_name.toUpperCase() === subjectUpper
                  );
                  for (const scale of subjectScales) {
                    if (marks >= scale.min_score && marks <= scale.max_score) {
                      return scale.points || 0;
                    }
                  }
                  const generalScales = gradingScales.filter((s: any) => s.subject_id === null);
                  for (const scale of generalScales) {
                    if (marks >= scale.min_score && marks <= scale.max_score) {
                      return scale.points || 0;
                    }
                  }
                  return 0;
                };

                // Function to get aggregate grade from points
                const getAggregateGrade = (points: number) => {
                  for (const dist of aggregateDistribution) {
                    if (points >= dist.min_points && points <= dist.max_points) {
                      return dist.grade_name;
                    }
                  }
                  return '-';
                };

                // Group records by student
                const studentResults: any = {};
                performanceRecords.forEach((record: any) => {
                  const admNo = record.admission_number;
                  if (!studentResults[admNo]) {
                    studentResults[admNo] = {
                      admission_number: admNo,
                      first_name: record.first_name,
                      last_name: record.last_name,
                      subjects: [],
                      totalMarks: 0,
                      totalPoints: 0,
                      subjectCount: 0
                    };
                  }
                  const assignments = studentAssignments[admNo] || [];
                  const subjectName = record.subject_name || record.subject;
                  const isAssigned = assignments.some((a: any) => a.subject_name === subjectName);
                  
                  if (isAssigned) {
                    const marks = Number(record.marks);
                    const points = getPointsFromMarks(marks, subjectName);
                    studentResults[admNo].subjects.push({
                      subject: subjectName,
                      marks: marks,
                      grade: record.grade,
                      points: points
                    });
                    studentResults[admNo].totalMarks += marks;
                    studentResults[admNo].totalPoints += points;
                    studentResults[admNo].subjectCount++;
                  }
                });

                // Calculate averages and grades
                const resultsArray = Object.values(studentResults).map((student: any) => {
                  const average = student.subjectCount > 0 
                    ? (student.totalMarks / student.subjectCount).toFixed(1) 
                    : 0;
                  const aggregateGrade = getAggregateGrade(student.totalPoints);
                  return {
                    ...student,
                    average: Number(average),
                    aggregateGrade
                  };
                }).sort((a: any, b: any) => b.totalPoints - a.totalPoints);

                return (
                  <View style={styles.resultsTableContainer}>
                    <View style={styles.tableHeader}>
                      <Text style={[styles.tableHeaderCell, styles.tableRankCell]}>Rank</Text>
                      <Text style={[styles.tableHeaderCell, styles.tableAdmNoCell]}>Adm No</Text>
                      <Text style={[styles.tableHeaderCell, styles.tableNameCell]}>Name</Text>
                      <Text style={[styles.tableHeaderCell, styles.tableMarksCell]}>Total</Text>
                      <Text style={[styles.tableHeaderCell, styles.tableAvgCell]}>Avg</Text>
                      <Text style={[styles.tableHeaderCell, styles.tablePointsCell]}>Points</Text>
                      <Text style={[styles.tableHeaderCell, styles.tableGradeCell]}>Grade</Text>
                    </View>
                    {resultsArray.map((student: any, index: number) => (
                      <View key={student.admission_number} style={[styles.tableRow, index % 2 === 1 && styles.tableRowEven]}>
                        <Text style={[styles.tableCell, styles.tableRankCell]}>{index + 1}</Text>
                        <Text style={[styles.tableCell, styles.tableAdmNoCell]}>{student.admission_number}</Text>
                        <Text style={[styles.tableCell, styles.tableNameCell]}>{student.first_name} {student.last_name}</Text>
                        <Text style={[styles.tableCell, styles.tableMarksCell]}>{student.totalMarks}</Text>
                        <Text style={[styles.tableCell, styles.tableAvgCell]}>{student.average}</Text>
                        <Text style={[styles.tableCell, styles.tablePointsCell]}>{student.totalPoints}</Text>
                        <Text style={[styles.tableCell, styles.tableGradeCell, { color: getGradeColor(student.aggregateGrade), fontWeight: '700' }]}>{student.aggregateGrade}</Text>
                      </View>
                    ))}
                    {resultsArray.length === 0 && (
                      <View style={styles.noResultsContainer}>
                        <Text style={styles.noResultsText}>No results data available</Text>
                      </View>
                    )}
                  </View>
                );
              })()
            ) : (
              <ActivityIndicator size="large" color="#FF6B35" />
            )}
          </View>
        ) : (
          // Performance View
          <>
        {/* Welcome Section */}
        <View style={styles.welcomeSection}>
          <Text style={styles.welcomeText}>Performance</Text>
          <Text style={styles.teacherName}>{performanceData?.teacher?.name || 'Teacher'}</Text>
          {performanceData?.teacher?.school_name && (
            <Text style={styles.schoolName}>{performanceData.teacher.school_name}</Text>
          )}
        </View>

        {/* Calendar Status */}
        {performanceData?.calendar_status && (
          <View style={styles.statusContainer}>
            <View style={styles.statusIconContainer}>
              <Ionicons 
                name={performanceData.calendar_status.is_holiday ? 'calendar-outline' : 
                      performanceData.calendar_status.school_status === 'break' ? 'time-outline' : 
                      'school-outline'} 
                size={24} 
                color="#FF6B35" 
              />
            </View>
            <View style={styles.statusTextContainer}>
              <Text style={styles.statusTitle}>
                {performanceData.calendar_status.is_holiday ? 'School is on Holiday' :
                 performanceData.calendar_status.school_status === 'break' ? 'School is on Break' :
                 'School is In Session'}
              </Text>
              {performanceData.calendar_status.current_holiday && (
                <Text style={styles.statusText}>
                  {performanceData.calendar_status.current_holiday.holiday_name}
                </Text>
              )}
              {performanceData.calendar_status.current_term && (
                <Text style={styles.statusText}>
                  Active Term: {performanceData.calendar_status.current_term.term_name}
                </Text>
              )}
            </View>
          </View>
        )}

        {/* Grading Scales */}
        {performanceData?.grading_scales && performanceData.grading_scales.length > 0 && (
          <View style={styles.sectionContainer}>
            <Text style={styles.sectionTitle}>Grading Scales</Text>
            <ScrollView horizontal showsHorizontalScrollIndicator={false}>
              <View style={styles.horizontalScrollContent}>
                {performanceData.grading_scales.map((scale) => (
                  <View key={scale.id} style={styles.gradeCard}>
                    <Text style={styles.gradeName}>{scale.grade_name}</Text>
                    <Text style={styles.gradeDescription}>{scale.grade_description || '-'}</Text>
                    <Text style={styles.gradeRange}>{scale.min_score} - {scale.max_score}</Text>
                    {scale.subject_name && (
                      <Text style={styles.gradeSubject}>{scale.subject_name}</Text>
                    )}
                  </View>
                ))}
              </View>
            </ScrollView>
          </View>
        )}

        {/* Subjects */}
        {performanceData?.all_subjects && performanceData.all_subjects.length > 0 && (
          <View style={styles.sectionContainer}>
            <Text style={styles.sectionTitle}>Subjects</Text>
            <View style={styles.subjectsGrid}>
              {performanceData.all_subjects.map((subject) => (
                <View key={subject.id} style={styles.subjectCard}>
                  <Text style={styles.subjectName}>{subject.subject_name}</Text>
                  <Text style={styles.subjectCode}>{subject.subject_code}</Text>
                </View>
              ))}
            </View>
          </View>
        )}

        {/* Exam Types */}
        {performanceData?.exam_types && performanceData.exam_types.length > 0 && (
          <View style={styles.sectionContainer}>
            <Text style={styles.sectionTitle}>Exam Types</Text>
            <View style={styles.examTypesList}>
              {performanceData.exam_types.map((exam) => (
                <View key={exam.id} style={styles.examTypeCard}>
                  <Text style={styles.examTypeName}>{exam.exam_type_name}</Text>
                  <Text style={styles.examTypeCode}>{exam.exam_type_code}</Text>
                  {exam.description && (
                    <Text style={styles.examTypeDescription}>{exam.description}</Text>
                  )}
                </View>
              ))}
            </View>
          </View>
        )}

        {/* Exam Type Filter */}
        {performanceData?.exam_types && performanceData.exam_types.length > 0 && (
          <View style={styles.sectionContainer}>
            <Text style={styles.sectionTitle}>Filter by Exam Type</Text>
            <TouchableOpacity 
              style={styles.examTypeSelector}
              onPress={() => setShowExamTypePicker(true)}
            >
              <Text style={styles.examTypeSelectorText}>
                {selectedExamType 
                  ? performanceData.exam_types.find((e: any) => e.id === parseInt(selectedExamType))?.exam_type_name || 'All Exam Types'
                  : 'All Exam Types'}
              </Text>
              <Ionicons name="chevron-down" size={20} color="#5f6368" />
            </TouchableOpacity>
            {selectedExamType && (
              <TouchableOpacity 
                style={styles.clearFilterButton}
                onPress={() => setSelectedExamType(null)}
              >
                <Ionicons name="close-circle" size={16} color="#FF6B35" />
                <Text style={styles.clearFilterText}>Clear Filter</Text>
              </TouchableOpacity>
            )}
          </View>
        )}

        {/* Performance Records */}
        {performanceData?.performance_records && performanceData.performance_records.length > 0 && (
          <View style={styles.sectionContainer}>
            <Text style={styles.sectionTitle}>Performance Results</Text>
            
            {/* Performance Statistics Cards */}
            {(() => {
              const filteredRecords = selectedExamType 
                ? performanceData.performance_records.filter((r: any) => r.exam_type_id === parseInt(selectedExamType))
                : performanceData.performance_records.filter((r: any) => r.exam_type_id && r.exam_type_id > 0);
              
              console.log('Filtered records:', filteredRecords.length);
              console.log('Sample record:', filteredRecords[0]);
              
              const validRecords = filteredRecords.filter((r: any) => {
                if (!r || r.marks === null || r.marks === undefined || r.marks === '') return false;
                const mark = Number(r.marks);
                const isValid = !isNaN(mark) && isFinite(mark) && mark >= 0 && mark <= 100;
                if (!isValid) console.log('Invalid mark:', r.marks);
                return isValid;
              });
              
              console.log('Valid records:', validRecords.length);
              
              const totalRecords = validRecords.length;
              
              let averageScore = 0;
              let highestScore = 0;
              let lowestScore = 0;
              
              if (totalRecords > 0) {
                const marks = validRecords.map((r: any) => Number(r.marks));
                const sum = marks.reduce((sum: number, m: number) => sum + m, 0);
                averageScore = parseFloat((sum / totalRecords).toFixed(1));
                highestScore = Math.max(...marks);
                lowestScore = Math.min(...marks);
                console.log('Stats:', { sum, averageScore, highestScore, lowestScore });
              }
              
              const passCount = validRecords.filter((r: any) => Number(r.marks) >= 50).length;
              const passRate = totalRecords > 0 
                ? Math.round((passCount / totalRecords) * 100) 
                : 0;

              // Ensure no NaN values
              const displayAverage = isNaN(averageScore) || !isFinite(averageScore) ? 0 : averageScore;
              const displayHighest = isNaN(highestScore) || !isFinite(highestScore) ? 0 : highestScore;
              const displayLowest = isNaN(lowestScore) || !isFinite(lowestScore) ? 0 : lowestScore;

              return (
                <View style={styles.statsCardsContainer}>
                  <View style={[styles.statCard, { backgroundColor: '#e8f0fe' }]}>
                    <Text style={[styles.statValue, { color: '#1967d2' }]}>{totalRecords}</Text>
                    <Text style={styles.statLabel}>Total Records</Text>
                  </View>
                  <View style={[styles.statCard, { backgroundColor: '#e6f4ea' }]}>
                    <Text style={[styles.statValue, { color: '#137333' }]}>{displayAverage}</Text>
                    <Text style={styles.statLabel}>Average Score</Text>
                  </View>
                  <View style={[styles.statCard, { backgroundColor: '#fce8e6' }]}>
                    <Text style={[styles.statValue, { color: '#c5221f' }]}>{displayHighest}</Text>
                    <Text style={styles.statLabel}>Highest Score</Text>
                  </View>
                  <View style={[styles.statCard, { backgroundColor: '#fef7e0' }]}>
                    <Text style={[styles.statValue, { color: '#b06000' }]}>{displayLowest}</Text>
                    <Text style={styles.statLabel}>Lowest Score</Text>
                  </View>
                  <View style={[styles.statCard, { backgroundColor: '#f1f3f4' }]}>
                    <Text style={[styles.statValue, { color: '#5f6368' }]}>{passRate}%</Text>
                    <Text style={styles.statLabel}>Pass Rate (50%+)</Text>
                  </View>
                </View>
              );
            })()}
            
            {/* Group by subject */}
            {(() => {
              const filteredRecords = selectedExamType 
                ? performanceData.performance_records.filter((r: any) => r.exam_type_id === parseInt(selectedExamType))
                : performanceData.performance_records.filter((r: any) => r.exam_type_id && r.exam_type_id > 0);
              
              const groupedBySubject = filteredRecords.reduce((acc: any, record) => {
                const subjectName = record.subject_name || record.subject;
                if (!acc[subjectName]) {
                  acc[subjectName] = [];
                }
                acc[subjectName].push(record);
                return acc;
              }, {});

              return Object.entries(groupedBySubject).map(([subjectName, records]: [string, any]) => (
                <View key={subjectName} style={styles.subjectSection}>
                  <View style={styles.subjectHeader}>
                    <Ionicons name="book-outline" size={20} color="#FF6B35" />
                    <Text style={styles.subjectHeaderText}>{subjectName}</Text>
                  </View>
                  
                  <View style={styles.tableContainer}>
                    {/* Table Header */}
                    <View style={styles.tableHeader}>
                      <Text style={[styles.tableHeaderCell, styles.tableRankCell]}>Rank</Text>
                      <Text style={[styles.tableHeaderCell, styles.tableAdmNoCell]}>Adm No</Text>
                      <Text style={[styles.tableHeaderCell, styles.tableNameCell]}>Name</Text>
                      <Text style={[styles.tableHeaderCell, styles.tableMarksCell]}>Marks</Text>
                      <Text style={[styles.tableHeaderCell, styles.tableGradeCell]}>Grade</Text>
                    </View>
                    
                    {/* Table Body */}
                    {records.map((record: any, index: number) => (
                      <View key={record.id} style={[styles.tableRow, index % 2 === 1 && styles.tableRowEven]}>
                        <Text style={[styles.tableCell, styles.tableRankCell]}>{index + 1}</Text>
                        <Text style={[styles.tableCell, styles.tableAdmNoCell]}>{record.admission_number}</Text>
                        <Text style={[styles.tableCell, styles.tableNameCell]}>{record.first_name} {record.last_name}</Text>
                        <Text style={[styles.tableCell, styles.tableMarksCell]}>{record.marks}</Text>
                        <Text style={[styles.tableCell, styles.tableGradeCell, { color: getGradeColor(record.grade), fontWeight: '700' }]}>{record.grade}</Text>
                      </View>
                    ))}
                  </View>
                </View>
              ));
            })()}
          </View>
        )}

        {/* Quick Actions */}
        <View style={styles.quickActionsSection}>
          <Text style={styles.quickActionsTitle}>Quick Actions</Text>
          <View style={styles.quickActionsGrid}>
            <TouchableOpacity 
              style={styles.quickActionButton}
              onPress={() => setShowBulkUpload(true)}
            >
              <View style={styles.quickActionIcon}>
                <Ionicons name="cloud-upload-outline" size={24} color="#1967d2" />
              </View>
              <Text style={styles.quickActionText}>Bulk Upload</Text>
            </TouchableOpacity>
            
            <TouchableOpacity 
              style={styles.quickActionButton}
              onPress={() => setShowReports(true)}
            >
              <View style={styles.quickActionIcon}>
                <Ionicons name="document-text-outline" size={24} color="#1967d2" />
              </View>
              <Text style={styles.quickActionText}>Reports</Text>
            </TouchableOpacity>
            
            <TouchableOpacity 
              style={styles.quickActionButton}
              onPress={() => setShowAnalytics(true)}
            >
              <View style={styles.quickActionIcon}>
                <Ionicons name="analytics-outline" size={24} color="#188038" />
              </View>
              <Text style={styles.quickActionText}>Analytics</Text>
            </TouchableOpacity>
            
            <TouchableOpacity 
              style={styles.quickActionButton}
              onPress={() => Alert.alert('Export', 'Use the Reports feature to export performance data')}
            >
              <View style={styles.quickActionIcon}>
                <Ionicons name="download-outline" size={24} color="#f57c00" />
              </View>
              <Text style={styles.quickActionText}>Export</Text>
            </TouchableOpacity>
          </View>
        </View>

        {/* Quick Links */}
        <View style={styles.quickLinksSection}>
          <Text style={styles.quickLinksTitle}>Quick Links</Text>
          <View style={styles.quickLinksGrid}>
            <TouchableOpacity 
              style={styles.quickLinkButton}
              onPress={() => router.push('/(tabs)/students')}
            >
              <View style={styles.quickLinkIcon}>
                <Ionicons name="people-outline" size={24} color="#FF6B35" />
              </View>
              <Text style={styles.quickLinkText}>Students</Text>
            </TouchableOpacity>
            
            <TouchableOpacity 
              style={styles.quickLinkButton}
              onPress={() => router.push('/(tabs)/attendance')}
            >
              <View style={styles.quickLinkIcon}>
                <Ionicons name="checkmark-circle-outline" size={24} color="#1967d2" />
              </View>
              <Text style={styles.quickLinkText}>Attendance</Text>
            </TouchableOpacity>
            
            <TouchableOpacity 
              style={styles.quickLinkButton}
              onPress={() => router.push('/(tabs)/assignments')}
            >
              <View style={styles.quickLinkIcon}>
                <Ionicons name="book-outline" size={24} color="#188038" />
              </View>
              <Text style={styles.quickLinkText}>Assignments</Text>
            </TouchableOpacity>
            
            <TouchableOpacity 
              style={styles.quickLinkButton}
              onPress={() => router.push('/(tabs)/dashboard')}
            >
              <View style={styles.quickLinkIcon}>
                <Ionicons name="home-outline" size={24} color="#f57c00" />
              </View>
              <Text style={styles.quickLinkText}>Dashboard</Text>
            </TouchableOpacity>
          </View>
        </View>
        </>
      )}
      </ScrollView>

      {/* Exam Type Picker Modal */}
      <Modal
        visible={showExamTypePicker}
        transparent={true}
        animationType="slide"
        onRequestClose={() => setShowExamTypePicker(false)}
      >
        <View style={styles.modalOverlay}>
          <View style={styles.modalContent}>
            <View style={styles.modalHeader}>
              <Text style={styles.modalTitle}>Select Exam Type</Text>
              <TouchableOpacity onPress={() => setShowExamTypePicker(false)}>
                <Ionicons name="close" size={24} color="#5f6368" />
              </TouchableOpacity>
            </View>
            <ScrollView style={styles.modalScrollView}>
              <TouchableOpacity 
                style={styles.examTypeOption}
                onPress={() => {
                  setSelectedExamType(null);
                  setShowExamTypePicker(false);
                }}
              >
                <Text style={styles.examTypeOptionText}>All Exam Types</Text>
                {!selectedExamType && <Ionicons name="checkmark" size={20} color="#FF6B35" />}
              </TouchableOpacity>
              {performanceData?.exam_types?.map((exam: any) => (
                <TouchableOpacity 
                  key={exam.id}
                  style={styles.examTypeOption}
                  onPress={() => {
                    setSelectedExamType(exam.id.toString());
                    setShowExamTypePicker(false);
                  }}
                >
                  <Text style={styles.examTypeOptionText}>{exam.exam_type_name}</Text>
                  {selectedExamType === exam.id.toString() && <Ionicons name="checkmark" size={20} color="#FF6B35" />}
                </TouchableOpacity>
              ))}
            </ScrollView>
          </View>
        </View>
      </Modal>

      {/* Bulk Upload Modal */}
      <Modal
        visible={showBulkUpload}
        transparent={true}
        animationType="slide"
        onRequestClose={() => setShowBulkUpload(false)}
      >
        <KeyboardAvoidingView 
          behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
          style={{ flex: 1 }}
        >
          <SafeAreaView style={{ flex: 1 }}>
            <View style={styles.modalOverlay}>
              <View style={styles.modalContent}>
                <View style={styles.modalHeader}>
                  <Text style={styles.modalTitle}>Bulk Performance Upload</Text>
                  <TouchableOpacity onPress={() => setShowBulkUpload(false)}>
                    <Ionicons name="close" size={24} color="#5f6368" />
                  </TouchableOpacity>
                </View>
                <ScrollView style={styles.modalScrollViewContent} keyboardShouldPersistTaps="handled">
              <TouchableOpacity 
                style={styles.downloadTemplateButton}
                onPress={handleDownloadTemplate}
              >
                <Ionicons name="download-outline" size={20} color="#ffffff" />
                <Text style={styles.downloadTemplateText}>Download CSV Template</Text>
              </TouchableOpacity>

              <View style={styles.formGroup}>
                <Text style={styles.formLabel}>Term</Text>
                <TouchableOpacity 
                  style={styles.formSelector}
                  onPress={() => setShowTermPicker(true)}
                >
                  <Text style={styles.formSelectorText}>{selectedTerm || 'Select Term'}</Text>
                  <Ionicons name="chevron-down" size={20} color="#5f6368" />
                </TouchableOpacity>
              </View>

              <View style={styles.formGroup}>
                <Text style={styles.formLabel}>Year</Text>
                <TextInput 
                  style={styles.formInput}
                  value={selectedYear}
                  onChangeText={setSelectedYear}
                  keyboardType="numeric"
                  placeholder="2026"
                />
              </View>

              <View style={styles.formGroup}>
                <Text style={styles.formLabel}>Exam Type</Text>
                <TouchableOpacity 
                  style={styles.formSelector}
                  onPress={() => setShowExamTypePicker(true)}
                >
                  <Text style={styles.formSelectorText}>
                    {selectedExamType 
                      ? performanceData?.exam_types?.find((e: any) => e.id === parseInt(selectedExamType))?.exam_type_name || 'Select Exam Type'
                      : 'Select Exam Type'}
                  </Text>
                  <Ionicons name="chevron-down" size={20} color="#5f6368" />
                </TouchableOpacity>
              </View>

              <View style={styles.formGroup}>
                <Text style={styles.formLabel}>Subject</Text>
                <TouchableOpacity 
                  style={styles.formSelector}
                  onPress={() => setShowSubjectPicker(true)}
                >
                  <Text style={styles.formSelectorText}>{selectedSubject || 'Select Subject'}</Text>
                  <Ionicons name="chevron-down" size={20} color="#5f6368" />
                </TouchableOpacity>
              </View>

              <View style={styles.formGroup}>
                <Text style={styles.formLabel}>Stream</Text>
                <TouchableOpacity 
                  style={styles.formSelector}
                  onPress={() => setShowStreamPicker(true)}
                >
                  <Text style={styles.formSelectorText}>{selectedStream || 'Select Stream'}</Text>
                  <Ionicons name="chevron-down" size={20} color="#5f6368" />
                </TouchableOpacity>
              </View>

              <View style={styles.formGroup}>
                <Text style={styles.formLabel}>CSV File</Text>
                <TouchableOpacity 
                  style={styles.fileUploadButton}
                  onPress={handlePickFile}
                >
                  {selectedFile ? (
                    <>
                      <Ionicons name="document-text" size={24} color="#137333" />
                      <Text style={styles.fileNameText}>{selectedFile.name}</Text>
                      <TouchableOpacity onPress={() => setSelectedFile(null)}>
                        <Ionicons name="close-circle" size={20} color="#FF6B35" />
                      </TouchableOpacity>
                    </>
                  ) : (
                    <>
                      <Ionicons name="cloud-upload-outline" size={32} color="#dadce0" />
                      <Text style={styles.fileUploadText}>Tap to select CSV file</Text>
                    </>
                  )}
                </TouchableOpacity>
              </View>
              </ScrollView>

              <View style={styles.uploadButtonContainer}>
                <TouchableOpacity 
                  style={[styles.uploadButton, uploading && styles.uploadButtonDisabled]}
                  onPress={handleBulkUpload}
                  disabled={uploading}
                >
                  {uploading ? (
                    <ActivityIndicator color="#ffffff" />
                  ) : (
                    <>
                      <Ionicons name="cloud-upload" size={20} color="#ffffff" />
                      <Text style={styles.uploadButtonText}>Upload Performance Records</Text>
                    </>
                  )}
                </TouchableOpacity>
              </View>
          </View>
        </View>
          </SafeAreaView>
        </KeyboardAvoidingView>
      </Modal>

      {/* Stream Picker Modal */}
      <Modal
        visible={showStreamPicker}
        transparent={true}
        animationType="slide"
        onRequestClose={() => setShowStreamPicker(false)}
      >
        <View style={styles.modalOverlay}>
          <View style={styles.modalContent}>
            <View style={styles.modalHeader}>
              <Text style={styles.modalTitle}>Select Stream</Text>
              <TouchableOpacity onPress={() => setShowStreamPicker(false)}>
                <Ionicons name="close" size={24} color="#5f6368" />
              </TouchableOpacity>
            </View>
            <ScrollView style={styles.modalScrollView}>
              {performanceData?.streams?.map((stream: any) => (
                <TouchableOpacity 
                  key={stream.id}
                  style={styles.examTypeOption}
                  onPress={() => {
                    setSelectedStream(stream.id.toString());
                    setShowStreamPicker(false);
                  }}
                >
                  <Text style={styles.examTypeOptionText}>{stream.stream_name}</Text>
                  {selectedStream === stream.id.toString() && <Ionicons name="checkmark" size={20} color="#FF6B35" />}
                </TouchableOpacity>
              ))}
            </ScrollView>
          </View>
        </View>
      </Modal>

      {/* Subject Picker Modal */}
      <Modal
        visible={showSubjectPicker}
        transparent={true}
        animationType="slide"
        onRequestClose={() => setShowSubjectPicker(false)}
      >
        <View style={styles.modalOverlay}>
          <View style={styles.modalContent}>
            <View style={styles.modalHeader}>
              <Text style={styles.modalTitle}>Select Subject</Text>
              <TouchableOpacity onPress={() => setShowSubjectPicker(false)}>
                <Ionicons name="close" size={24} color="#5f6368" />
              </TouchableOpacity>
            </View>
            <ScrollView style={styles.modalScrollView}>
              {performanceData?.all_subjects?.map((subject: any) => (
                <TouchableOpacity 
                  key={subject.id}
                  style={styles.examTypeOption}
                  onPress={() => {
                    setSelectedSubject(subject.subject_name);
                    setShowSubjectPicker(false);
                  }}
                >
                  <Text style={styles.examTypeOptionText}>{subject.subject_name}</Text>
                  {selectedSubject === subject.subject_name && <Ionicons name="checkmark" size={20} color="#FF6B35" />}
                </TouchableOpacity>
              ))}
            </ScrollView>
          </View>
        </View>
      </Modal>

      {/* Term Picker Modal */}
      <Modal
        visible={showTermPicker}
        transparent={true}
        animationType="slide"
        onRequestClose={() => setShowTermPicker(false)}
      >
        <View style={styles.modalOverlay}>
          <View style={styles.modalContent}>
            <View style={styles.modalHeader}>
              <Text style={styles.modalTitle}>Select Term</Text>
              <TouchableOpacity onPress={() => setShowTermPicker(false)}>
                <Ionicons name="close" size={24} color="#5f6368" />
              </TouchableOpacity>
            </View>
            <ScrollView style={styles.modalScrollView}>
              {performanceData?.terms?.map((term: string) => (
                <TouchableOpacity 
                  key={term}
                  style={styles.examTypeOption}
                  onPress={() => {
                    setSelectedTerm(term);
                    setShowTermPicker(false);
                  }}
                >
                  <Text style={styles.examTypeOptionText}>{term}</Text>
                  {selectedTerm === term && <Ionicons name="checkmark" size={20} color="#FF6B35" />}
                </TouchableOpacity>
              ))}
            </ScrollView>
          </View>
        </View>
      </Modal>

      {/* Reports Modal */}
      <Modal
        visible={showReports}
        transparent={true}
        animationType="slide"
        onRequestClose={() => setShowReports(false)}
      >
        <KeyboardAvoidingView 
          behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
          style={{ flex: 1 }}
        >
          <SafeAreaView style={{ flex: 1 }}>
            <View style={styles.modalOverlay}>
              <View style={styles.modalContent}>
                <View style={styles.modalHeader}>
                  <Text style={styles.modalTitle}>Performance Reports</Text>
                  <TouchableOpacity onPress={() => setShowReports(false)}>
                    <Ionicons name="close" size={24} color="#5f6368" />
                  </TouchableOpacity>
                </View>
                <ScrollView style={styles.modalScrollViewContent}>
                  <View style={styles.formGroup}>
                    <Text style={styles.formLabel}>Term</Text>
                    <TouchableOpacity 
                      style={styles.formSelector}
                      onPress={() => setShowTermPicker(true)}
                    >
                      <Text style={styles.formSelectorText}>{reportTerm || 'Select Term'}</Text>
                      <Ionicons name="chevron-down" size={20} color="#5f6368" />
                    </TouchableOpacity>
                  </View>

                  <View style={styles.formGroup}>
                    <Text style={styles.formLabel}>Year</Text>
                    <TextInput 
                      style={styles.formInput}
                      value={reportYear}
                      onChangeText={setReportYear}
                      keyboardType="numeric"
                      placeholder="2026"
                    />
                  </View>

                  <View style={styles.formGroup}>
                    <Text style={styles.formLabel}>Subject (Optional)</Text>
                    <TouchableOpacity 
                      style={styles.formSelector}
                      onPress={() => setShowSubjectPicker(true)}
                    >
                      <Text style={styles.formSelectorText}>{reportSubject || 'All Subjects'}</Text>
                      <Ionicons name="chevron-down" size={20} color="#5f6368" />
                    </TouchableOpacity>
                  </View>

                  <View style={styles.formGroup}>
                    <Text style={styles.formLabel}>Stream (Optional)</Text>
                    <TouchableOpacity 
                      style={styles.formSelector}
                      onPress={() => setShowStreamPicker(true)}
                    >
                      <Text style={styles.formSelectorText}>{reportStream || 'All Streams'}</Text>
                      <Ionicons name="chevron-down" size={20} color="#5f6368" />
                    </TouchableOpacity>
                  </View>

                  <View style={styles.formGroup}>
                    <Text style={styles.formLabel}>Exam Type (Optional)</Text>
                    <TouchableOpacity 
                      style={styles.formSelector}
                      onPress={() => setShowExamTypePicker(true)}
                    >
                      <Text style={styles.formSelectorText}>
                        {reportExamType 
                          ? performanceData?.exam_types?.find((e: any) => e.id === parseInt(reportExamType))?.exam_type_name || 'All Exam Types'
                          : 'All Exam Types'}
                      </Text>
                      <Ionicons name="chevron-down" size={20} color="#5f6368" />
                    </TouchableOpacity>
                  </View>

                  <View style={styles.reportButtonsRow}>
                    <TouchableOpacity 
                      style={styles.generateReportButton}
                      onPress={handleGenerateReport}
                      disabled={loadingReport}
                    >
                      {loadingReport ? (
                        <ActivityIndicator color="#ffffff" />
                      ) : (
                        <>
                          <Ionicons name="document-text" size={20} color="#ffffff" />
                          <Text style={styles.generateReportButtonText}>Generate Report</Text>
                        </>
                      )}
                    </TouchableOpacity>

                    {reportResults.length > 0 && (
                      <TouchableOpacity 
                        style={styles.exportReportButton}
                        onPress={handleExportReport}
                      >
                        <Ionicons name="download" size={20} color="#ffffff" />
                        <Text style={styles.exportReportButtonText}>Export CSV</Text>
                      </TouchableOpacity>
                    )}
                  </View>

                  {reportResults.length > 0 && (
                    <View style={styles.reportResultsContainer}>
                      <Text style={styles.reportResultsTitle}>Results ({reportResults.length})</Text>
                      <View style={styles.tableContainer}>
                        <View style={styles.tableHeader}>
                          <Text style={[styles.tableHeaderCell, styles.tableAdmNoCell]}>Adm No</Text>
                          <Text style={[styles.tableHeaderCell, styles.tableNameCell]}>Name</Text>
                          <Text style={[styles.tableHeaderCell, styles.tableMarksCell]}>Marks</Text>
                          <Text style={[styles.tableHeaderCell, styles.tableGradeCell]}>Grade</Text>
                        </View>
                        {reportResults.map((record: any, index: number) => (
                          <View key={record.id} style={[styles.tableRow, index % 2 === 1 && styles.tableRowEven]}>
                            <Text style={[styles.tableCell, styles.tableAdmNoCell]}>{record.admission_number}</Text>
                            <Text style={[styles.tableCell, styles.tableNameCell]}>{record.first_name} {record.last_name}</Text>
                            <Text style={[styles.tableCell, styles.tableMarksCell]}>{record.marks}</Text>
                            <Text style={[styles.tableCell, styles.tableGradeCell, { color: getGradeColor(record.grade), fontWeight: '700' }]}>{record.grade}</Text>
                          </View>
                        ))}
                      </View>
                    </View>
                  )}
                </ScrollView>
              </View>
            </View>
          </SafeAreaView>
        </KeyboardAvoidingView>
      </Modal>

      {/* Analytics Modal */}
      <Modal
        visible={showAnalytics}
        transparent={true}
        animationType="slide"
        onRequestClose={() => setShowAnalytics(false)}
      >
        <KeyboardAvoidingView 
          behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
          style={{ flex: 1 }}
        >
          <SafeAreaView style={{ flex: 1 }}>
            <View style={styles.modalOverlay}>
              <View style={styles.modalContent}>
                <View style={styles.modalHeader}>
                  <Text style={styles.modalTitle}>Performance Analytics</Text>
                  <TouchableOpacity onPress={() => setShowAnalytics(false)}>
                    <Ionicons name="close" size={24} color="#5f6368" />
                  </TouchableOpacity>
                </View>
                <ScrollView style={styles.modalScrollViewContent}>
                  {(() => {
                    const allRecords = performanceData?.performance_records?.filter((r: any) => r.exam_type_id && r.exam_type_id > 0) || [];
                    
                    // Group by subject
                    const bySubject = allRecords.reduce((acc: any, r: any) => {
                      const subject = r.subject_name || r.subject;
                      if (!acc[subject]) acc[subject] = [];
                      acc[subject].push(r);
                      return acc;
                    }, {});

                    // Calculate subject averages
                    const subjectStats = Object.entries(bySubject).map(([subject, records]: [string, any]) => {
                      const validMarks = records.map((r: any) => Number(r.marks)).filter((m: number) => !isNaN(m));
                      const avg = validMarks.length > 0 ? (validMarks.reduce((a: number, b: number) => a + b, 0) / validMarks.length).toFixed(1) : 0;
                      return { subject, average: Number(avg), count: records.length };
                    }).sort((a: any, b: any) => b.average - a.average);

                    // Group by exam type
                    const byExamType = allRecords.reduce((acc: any, r: any) => {
                      const examType = r.exam_type_name || 'Unknown';
                      if (!acc[examType]) acc[examType] = [];
                      acc[examType].push(r);
                      return acc;
                    }, {});

                    const examTypeStats = Object.entries(byExamType).map(([examType, records]: [string, any]) => {
                      const validMarks = records.map((r: any) => Number(r.marks)).filter((m: number) => !isNaN(m));
                      const avg = validMarks.length > 0 ? (validMarks.reduce((a: number, b: number) => a + b, 0) / validMarks.length).toFixed(1) : 0;
                      return { examType, average: Number(avg), count: records.length };
                    });

                    // Grade distribution
                    const gradeDistribution = allRecords.reduce((acc: any, r: any) => {
                      const grade = r.grade?.toUpperCase() || 'N/A';
                      acc[grade] = (acc[grade] || 0) + 1;
                      return acc;
                    }, {});

                    return (
                      <>
                        <Text style={styles.analyticsSectionTitle}>Subject Performance</Text>
                        {subjectStats.map((stat: any) => (
                          <View key={stat.subject} style={styles.analyticsBarRow}>
                            <Text style={styles.analyticsBarLabel}>{stat.subject}</Text>
                            <View style={styles.analyticsBarContainer}>
                              <View style={[styles.analyticsBarFill, { width: `${Math.min(stat.average, 100)}%` }]} />
                            </View>
                            <Text style={styles.analyticsBarValue}>{stat.average}%</Text>
                          </View>
                        ))}

                        <Text style={styles.analyticsSectionTitle}>Exam Type Performance</Text>
                        {examTypeStats.map((stat: any) => (
                          <View key={stat.examType} style={styles.analyticsBarRow}>
                            <Text style={styles.analyticsBarLabel}>{stat.examType}</Text>
                            <View style={styles.analyticsBarContainer}>
                              <View style={[styles.analyticsBarFill, { width: `${Math.min(stat.average, 100)}%`, backgroundColor: '#FF6B35' }]} />
                            </View>
                            <Text style={styles.analyticsBarValue}>{stat.average}%</Text>
                          </View>
                        ))}

                        <Text style={styles.analyticsSectionTitle}>Grade Distribution</Text>
                        <View style={styles.gradeDistributionContainer}>
                          {Object.entries(gradeDistribution).map(([grade, count]: [string, number]) => (
                            <View key={grade} style={styles.gradeDistributionItem}>
                              <View style={[styles.gradeBadgeSmall, { backgroundColor: getGradeColor(grade) }]}>
                                <Text style={styles.gradeBadgeSmallText}>{grade}</Text>
                              </View>
                              <Text style={styles.gradeDistributionCount}>{count}</Text>
                            </View>
                          ))}
                        </View>
                      </>
                    );
                  })()}
                </ScrollView>
              </View>
            </View>
          </SafeAreaView>
        </KeyboardAvoidingView>
      </Modal>
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
  viewToggle: {
    backgroundColor: '#FF6B35',
    paddingHorizontal: 16,
    paddingVertical: 8,
    borderRadius: 20,
    marginTop: 15,
  },
  viewToggleText: {
    color: '#ffffff',
    fontSize: 14,
    fontWeight: '600',
  },
  content: {
    flex: 1,
  },
  sectionContainer: {
    paddingHorizontal: 16,
    paddingVertical: 16,
  },
  sectionTitle: {
    fontSize: 20,
    fontWeight: '600',
    color: '#202124',
    marginBottom: 16,
  },
  resultsTableContainer: {
    borderWidth: 1,
    borderColor: '#000',
    borderRadius: 0,
    overflow: 'hidden',
  },
  noResultsContainer: {
    padding: 40,
    alignItems: 'center',
  },
  noResultsText: {
    fontSize: 16,
    color: '#5f6368',
    textAlign: 'center',
  },
  welcomeSection: {
    paddingHorizontal: 16,
    paddingVertical: 16,
    marginBottom: 16,
  },
  welcomeText: {
    fontSize: 24,
    fontWeight: '400',
    color: '#202124',
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
  statusContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#f8f9fa',
    borderWidth: 1,
    borderColor: '#FF6B35',
    borderRadius: 8,
    padding: 16,
    marginBottom: 24,
    marginHorizontal: 16,
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
  subtitle: {
    fontSize: 14,
    color: '#5f6368',
  },
  statsCardsContainer: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 12,
    marginBottom: 20,
  },
  statCard: {
    flex: 1,
    minWidth: 140,
    padding: 16,
    borderRadius: 8,
    alignItems: 'center',
  },
  statValue: {
    fontSize: 24,
    fontWeight: '600',
    marginBottom: 4,
  },
  statLabel: {
    fontSize: 12,
    color: '#5f6368',
  },
  horizontalScrollContent: {
    flexDirection: 'row',
    gap: 12,
  },
  gradeCard: {
    backgroundColor: '#f8f9fa',
    borderRadius: 12,
    padding: 16,
    minWidth: 120,
    borderWidth: 1,
    borderColor: '#e8eaed',
  },
  gradeName: {
    fontSize: 24,
    fontWeight: '700',
    color: '#FF6B35',
    marginBottom: 4,
  },
  gradeDescription: {
    fontSize: 12,
    color: '#5f6368',
    marginBottom: 8,
  },
  gradeRange: {
    fontSize: 14,
    fontWeight: '600',
    color: '#202124',
    marginBottom: 4,
  },
  gradeSubject: {
    fontSize: 10,
    color: '#5f6368',
    fontStyle: 'italic',
  },
  subjectsGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 8,
  },
  subjectCard: {
    backgroundColor: '#f8f9fa',
    borderRadius: 8,
    padding: 12,
    borderWidth: 1,
    borderColor: '#e8eaed',
    minWidth: 100,
  },
  subjectName: {
    fontSize: 14,
    fontWeight: '600',
    color: '#202124',
    marginBottom: 4,
  },
  subjectCode: {
    fontSize: 12,
    color: '#5f6368',
  },
  examTypesList: {
    gap: 8,
  },
  examTypeCard: {
    backgroundColor: '#f8f9fa',
    borderRadius: 8,
    padding: 16,
    borderWidth: 1,
    borderColor: '#e8eaed',
  },
  examTypeName: {
    fontSize: 16,
    fontWeight: '600',
    color: '#202124',
    marginBottom: 4,
  },
  examTypeCode: {
    fontSize: 12,
    color: '#5f6368',
    marginBottom: 4,
  },
  examTypeDescription: {
    fontSize: 14,
    color: '#5f6368',
  },
  aggregateList: {
    gap: 8,
  },
  aggregateCard: {
    flexDirection: 'row',
    backgroundColor: '#f8f9fa',
    borderRadius: 8,
    padding: 16,
    borderWidth: 1,
    borderColor: '#e8eaed',
    alignItems: 'center',
  },
  aggregateGrade: {
    width: 48,
    height: 48,
    borderRadius: 24,
    backgroundColor: '#FF6B35',
    justifyContent: 'center',
    alignItems: 'center',
    marginRight: 12,
  },
  aggregateGradeText: {
    fontSize: 20,
    fontWeight: '700',
    color: '#ffffff',
  },
  aggregateInfo: {
    flex: 1,
  },
  aggregateRange: {
    fontSize: 14,
    fontWeight: '600',
    color: '#202124',
    marginBottom: 4,
  },
  aggregateDescription: {
    fontSize: 12,
    color: '#5f6368',
  },
  subjectSection: {
    marginBottom: 24,
  },
  subjectHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 12,
    gap: 8,
  },
  subjectHeaderText: {
    fontSize: 16,
    fontWeight: '600',
    color: '#202124',
  },
  tableContainer: {
    borderWidth: 2,
    borderColor: '#000',
    borderRadius: 0,
    overflow: 'hidden',
  },
  tableHeader: {
    flexDirection: 'row',
    backgroundColor: '#f5f5f5',
    borderBottomWidth: 2,
    borderBottomColor: '#000',
  },
  tableHeaderCell: {
    padding: 10,
    fontSize: 12,
    fontWeight: '700',
    color: '#000',
    textAlign: 'center',
    borderRightWidth: 1,
    borderRightColor: '#000',
  },
  tableRankCell: {
    width: 50,
  },
  tableAdmNoCell: {
    flex: 1,
  },
  tableNameCell: {
    flex: 2,
  },
  tableMarksCell: {
    width: 60,
  },
  tableAvgCell: {
    width: 60,
  },
  tablePointsCell: {
    width: 60,
  },
  tableGradeCell: {
    width: 60,
  },
  tableRow: {
    flexDirection: 'row',
    borderBottomWidth: 1,
    borderBottomColor: '#000',
    backgroundColor: '#ffffff',
  },
  tableRowEven: {
    backgroundColor: '#f9f9f9',
  },
  tableCell: {
    padding: 10,
    fontSize: 11,
    color: '#000',
    textAlign: 'center',
    borderRightWidth: 1,
    borderRightColor: '#000',
  },
  gradeBadge: {
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 4,
    alignSelf: 'center',
  },
  gradeBadgeText: {
    fontSize: 12,
    fontWeight: '600',
    color: '#ffffff',
  },
  examTypeSelector: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    backgroundColor: '#f8f9fa',
    borderWidth: 1,
    borderColor: '#e8eaed',
    borderRadius: 8,
    padding: 16,
  },
  examTypeSelectorText: {
    fontSize: 16,
    color: '#202124',
  },
  clearFilterButton: {
    flexDirection: 'row',
    alignItems: 'center',
    marginTop: 8,
    gap: 4,
  },
  clearFilterText: {
    fontSize: 14,
    color: '#FF6B35',
  },
  modalOverlay: {
    flex: 1,
    backgroundColor: 'rgba(0, 0, 0, 0.5)',
    justifyContent: 'flex-end',
  },
  modalContent: {
    backgroundColor: '#ffffff',
    borderTopLeftRadius: 20,
    borderTopRightRadius: 20,
    maxHeight: '85%',
    paddingBottom: 20,
  },
  modalHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    padding: 20,
    borderBottomWidth: 1,
    borderBottomColor: '#e8eaed',
  },
  modalTitle: {
    fontSize: 18,
    fontWeight: '600',
    color: '#202124',
  },
  modalScrollView: {
    padding: 16,
    paddingBottom: 150,
  },
  modalScrollViewContent: {
    padding: 16,
  },
  uploadButtonContainer: {
    padding: 16,
    borderTopWidth: 1,
    borderTopColor: '#e8eaed',
    backgroundColor: '#ffffff',
  },
  examTypeOption: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    padding: 16,
    borderBottomWidth: 1,
    borderBottomColor: '#e8eaed',
  },
  examTypeOptionText: {
    fontSize: 16,
    color: '#202124',
  },
  placeholderText: {
    fontSize: 16,
    color: '#5f6368',
    textAlign: 'center',
    padding: 40,
  },
  reportButtonsRow: {
    flexDirection: 'row',
    gap: 12,
    marginTop: 16,
  },
  generateReportButton: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: '#1967d2',
    padding: 16,
    borderRadius: 8,
    gap: 8,
  },
  generateReportButtonText: {
    color: '#ffffff',
    fontSize: 16,
    fontWeight: '600',
  },
  exportReportButton: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: '#137333',
    padding: 16,
    borderRadius: 8,
    gap: 8,
  },
  exportReportButtonText: {
    color: '#ffffff',
    fontSize: 16,
    fontWeight: '600',
  },
  reportResultsContainer: {
    marginTop: 24,
  },
  reportResultsTitle: {
    fontSize: 16,
    fontWeight: '600',
    color: '#202124',
    marginBottom: 12,
  },
  analyticsSectionTitle: {
    fontSize: 16,
    fontWeight: '600',
    color: '#202124',
    marginTop: 24,
    marginBottom: 12,
  },
  analyticsBarRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 12,
  },
  analyticsBarLabel: {
    width: 120,
    fontSize: 13,
    color: '#5f6368',
  },
  analyticsBarContainer: {
    flex: 1,
    height: 24,
    backgroundColor: '#e8eaed',
    borderRadius: 4,
    marginHorizontal: 12,
    overflow: 'hidden',
  },
  analyticsBarFill: {
    height: '100%',
    backgroundColor: '#1967d2',
    borderRadius: 4,
  },
  analyticsBarValue: {
    fontSize: 14,
    fontWeight: '600',
    color: '#202124',
    width: 50,
    textAlign: 'right',
  },
  gradeDistributionContainer: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 12,
    marginTop: 12,
  },
  gradeDistributionItem: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  gradeBadgeSmall: {
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 4,
  },
  gradeBadgeSmallText: {
    fontSize: 12,
    fontWeight: '600',
    color: '#ffffff',
  },
  gradeDistributionCount: {
    fontSize: 14,
    fontWeight: '600',
    color: '#202124',
  },
  downloadTemplateButton: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: '#1967d2',
    padding: 16,
    borderRadius: 8,
    marginBottom: 24,
    gap: 8,
  },
  downloadTemplateText: {
    color: '#ffffff',
    fontSize: 16,
    fontWeight: '600',
  },
  formGroup: {
    marginBottom: 16,
  },
  formLabel: {
    fontSize: 14,
    fontWeight: '600',
    color: '#202124',
    marginBottom: 8,
  },
  formSelector: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    backgroundColor: '#f8f9fa',
    borderWidth: 1,
    borderColor: '#e8eaed',
    borderRadius: 8,
    padding: 16,
  },
  formSelectorText: {
    fontSize: 16,
    color: '#202124',
  },
  formInput: {
    backgroundColor: '#f8f9fa',
    borderWidth: 1,
    borderColor: '#e8eaed',
    borderRadius: 8,
    padding: 16,
    fontSize: 16,
    color: '#202124',
  },
  fileUploadButton: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: '#f8f9fa',
    borderWidth: 2,
    borderColor: '#dadce0',
    borderRadius: 8,
    padding: 24,
    gap: 8,
  },
  fileNameText: {
    fontSize: 14,
    color: '#202124',
    fontWeight: '500',
    flex: 1,
  },
  fileUploadText: {
    fontSize: 13,
    color: '#5f6368',
  },
  uploadButton: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: '#FF6B35',
    padding: 16,
    borderRadius: 8,
    marginTop: 24,
    gap: 8,
  },
  uploadButtonDisabled: {
    backgroundColor: '#ccc',
  },
  uploadButtonText: {
    color: '#ffffff',
    fontSize: 16,
    fontWeight: '600',
  },
  quickActionsSection: {
    marginBottom: 24,
    paddingHorizontal: 16,
  },
  quickActionsTitle: {
    fontSize: 18,
    fontWeight: '600',
    color: '#202124',
    marginBottom: 16,
  },
  quickActionsGrid: {
    flexDirection: 'row',
    gap: 12,
  },
  quickActionButton: {
    flex: 1,
    backgroundColor: '#f8f9fa',
    borderRadius: 12,
    padding: 16,
    alignItems: 'center',
    borderWidth: 1,
    borderColor: '#e8eaed',
  },
  quickActionIcon: {
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
  quickActionText: {
    fontSize: 12,
    fontWeight: '500',
    color: '#5f6368',
    textAlign: 'center',
  },
  quickLinksSection: {
    marginBottom: 24,
    paddingBottom: 100,
    paddingHorizontal: 16,
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
