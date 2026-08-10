import { View, Text, StyleSheet, ScrollView, TouchableOpacity, ActivityIndicator, RefreshControl, Modal, TextInput, Alert, Platform } from 'react-native';
import { useRouter } from 'expo-router';
import { useState, useEffect } from 'react';
import { Ionicons } from '@expo/vector-icons';
import DateTimePicker from '@react-native-community/datetimepicker';
import { getAttendanceData, getAttendanceStudents, saveAttendance, autoMarkAbsent, AttendanceResponse, AttendanceStudentsResponse } from '../../lib/api';

export default function Attendance() {
  const router = useRouter();
  const [attendanceData, setAttendanceData] = useState<AttendanceResponse | null>(null);
  const [students, setStudents] = useState<any[]>([]);
  const [loading, setLoading] = useState(false);
  const [refreshing, setRefreshing] = useState(false);
  const [markAttendanceVisible, setMarkAttendanceVisible] = useState(false);
  const [selectedClassId, setSelectedClassId] = useState<number | null>(null);
  const [selectedDate, setSelectedDate] = useState(new Date().toISOString().split('T')[0]);
  const [saving, setSaving] = useState(false);
  const [searchQuery, setSearchQuery] = useState('');
  const [filterStartDate, setFilterStartDate] = useState(new Date().toISOString().split('T')[0]);
  const [filterEndDate, setFilterEndDate] = useState(new Date().toISOString().split('T')[0]);
  const [startDatePickerVisible, setStartDatePickerVisible] = useState(false);
  const [endDatePickerVisible, setEndDatePickerVisible] = useState(false);
  const [studentSearchQuery, setStudentSearchQuery] = useState('');
  
  // Custom alert modal state
  const [alertVisible, setAlertVisible] = useState(false);
  const [alertTitle, setAlertTitle] = useState('');
  const [alertMessage, setAlertMessage] = useState('');
  const [alertType, setAlertType] = useState<'error' | 'success' | 'info' | 'confirm'>('info');
  const [alertOnConfirm, setAlertOnConfirm] = useState<(() => void) | null>(null);

  const showAlert = (title: string, message: string, type: 'error' | 'success' | 'info' | 'confirm' = 'info', onConfirm?: () => void) => {
    setAlertTitle(title);
    setAlertMessage(message);
    setAlertType(type);
    setAlertOnConfirm(onConfirm || null);
    setAlertVisible(true);
  };

  const handleAlertConfirm = () => {
    if (alertOnConfirm) {
      alertOnConfirm();
    }
    setAlertVisible(false);
  };

  useEffect(() => {
    loadAttendanceData();
  }, []);

  const loadAttendanceData = async () => {
    setLoading(true);
    try {
      console.log('Loading attendance with dates:', filterStartDate, filterEndDate);
      const data = await getAttendanceData(filterStartDate, filterEndDate);
      console.log('Attendance data received:', data);
      console.log('Student attendance details:', data.student_attendance_details);
      setAttendanceData(data);
      // Auto-select class if teacher is class teacher
      if (data.teacher?.class_id) {
        setSelectedClassId(data.teacher.class_id);
      }
    } catch (error: any) {
      if (error.message === 'NOT_AUTHENTICATED') {
        router.replace('/login');
      } else {
        showAlert('Error', error.message || 'Failed to load attendance data', 'error');
      }
    } finally {
      setLoading(false);
    }
  };

  const onRefresh = async () => {
    setRefreshing(true);
    await loadAttendanceData();
    setRefreshing(false);
  };

  const loadStudentsForAttendance = async () => {
    if (!selectedClassId) {
      showAlert('Error', 'Please select a class first', 'error');
      return;
    }

    setLoading(true);
    try {
      const data = await getAttendanceStudents(selectedClassId, selectedDate);
      setStudents(data.students || []);
      setMarkAttendanceVisible(true);
    } catch (error: any) {
      if (error.message === 'NOT_AUTHENTICATED') {
        router.replace('/login');
      } else {
        showAlert('Error', error.message || 'Failed to load students', 'error');
      }
    } finally {
      setLoading(false);
    }
  };

  const markStudentStatus = (studentId: number, status: string) => {
    setStudents(students.map(student => 
      student.id === studentId ? { ...student, status } : student
    ));
  };

  const handleAutoMarkAbsent = async () => {
    if (!selectedClassId) {
      showAlert('Error', 'Please select a class first', 'error');
      return;
    }

    // Check if school is in session
    if (attendanceData?.calendar_status?.school_status !== 'in_session') {
      const status = attendanceData?.calendar_status?.school_status;
      if (status === 'break') {
        showAlert('Cannot Auto-Mark', 'School is currently on break. Auto-marking is only available during active school terms.', 'info');
      } else if (attendanceData?.calendar_status?.is_holiday) {
        showAlert('Cannot Auto-Mark', 'School is currently on holiday. Auto-marking is only available during active school terms.', 'info');
      } else {
        showAlert('Cannot Auto-Mark', 'School is not currently in session. Auto-marking is only available during active school terms.', 'info');
      }
      return;
    }

    showAlert(
      'Auto-Mark Absent',
      'This will mark all students as absent for unmarked days in the current term (excluding holidays and weekends). Continue?',
      'confirm',
      async () => {
        setLoading(true);
        try {
          const result = await autoMarkAbsent(selectedClassId);
          showAlert('Success', result.message || 'Attendance auto-marked successfully', 'success');
          loadAttendanceData();
        } catch (error: any) {
          if (error.message === 'NOT_AUTHENTICATED') {
            router.replace('/login');
          } else {
            let errorMessage = 'Failed to auto-mark absent';
            if (error.message.includes('No active term')) {
              errorMessage = 'No active term found. Please contact the school administrator to set up the current term.';
            } else if (error.message.includes('No active students')) {
              errorMessage = 'No active students found in this class.';
            } else if (error.message) {
              errorMessage = error.message;
            }
            showAlert('Error', errorMessage, 'error');
          }
        } finally {
          setLoading(false);
        }
      }
    );
  };

  const saveAttendanceData = async () => {
    const attendanceRecords = students
      .filter(s => s.status)
      .map(s => ({
        student_id: s.id,
        status: s.status,
        remarks: s.remarks || ''
      }));

    if (attendanceRecords.length === 0) {
      showAlert('Error', 'Please mark at least one student', 'error');
      return;
    }

    setSaving(true);
    try {
      await saveAttendance(selectedClassId!, selectedDate, attendanceRecords);
      showAlert('Success', 'Attendance saved successfully', 'success');
      setMarkAttendanceVisible(false);
      setStudents([]);
    } catch (error: any) {
      showAlert('Error', error.message || 'Failed to save attendance', 'error');
    } finally {
      setSaving(false);
    }
  };

  const filteredStudents = students.filter(student =>
    student.admission_number.toLowerCase().includes(searchQuery.toLowerCase()) ||
    `${student.first_name} ${student.last_name}`.toLowerCase().includes(searchQuery.toLowerCase())
  );

  const stats = {
    present: students.filter(s => s.status === 'present').length,
    absent: students.filter(s => s.status === 'absent').length,
    late: students.filter(s => s.status === 'late').length,
    excused: students.filter(s => s.status === 'excused').length,
    total: students.length
  };

  const attendanceRate = stats.total > 0 ? Math.round((stats.present / stats.total) * 100) : 0;

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

      <ScrollView style={styles.content} showsVerticalScrollIndicator={false}>
        {/* Welcome Section */}
        <View style={styles.welcomeSection}>
          <Text style={styles.welcomeText}>Attendance</Text>
          <Text style={styles.subtitle}>Mark and manage student attendance</Text>
        </View>
        {/* Quick Actions */}
        <View style={styles.quickActionsSection}>
          <Text style={styles.quickActionsTitle}>Quick Actions</Text>
          <View style={styles.quickActionsGrid}>
            <TouchableOpacity 
              style={styles.quickActionButton}
              onPress={() => loadStudentsForAttendance()}
            >
              <View style={styles.quickActionIcon}>
                <Ionicons name="checkmark-circle-outline" size={24} color="#FF6B35" />
              </View>
              <Text style={styles.quickActionText}>Mark Today</Text>
            </TouchableOpacity>
            
            <TouchableOpacity 
              style={styles.quickActionButton}
              onPress={() => handleAutoMarkAbsent()}
            >
              <View style={styles.quickActionIcon}>
                <Ionicons name="time-outline" size={24} color="#c5221f" />
              </View>
              <Text style={styles.quickActionText}>Auto-Mark Absent</Text>
            </TouchableOpacity>
            
            <TouchableOpacity 
              style={styles.quickActionButton}
              onPress={() => {/* Navigate to view reports */}}
            >
              <View style={styles.quickActionIcon}>
                <Ionicons name="document-text-outline" size={24} color="#1967d2" />
              </View>
              <Text style={styles.quickActionText}>Reports</Text>
            </TouchableOpacity>
            
            <TouchableOpacity 
              style={styles.quickActionButton}
              onPress={() => {/* Navigate to view history */}}
            >
              <View style={styles.quickActionIcon}>
                <Ionicons name="calendar-outline" size={24} color="#188038" />
              </View>
              <Text style={styles.quickActionText}>History</Text>
            </TouchableOpacity>
            
            <TouchableOpacity 
              style={styles.quickActionButton}
              onPress={() => onRefresh()}
            >
              <View style={styles.quickActionIcon}>
                <Ionicons name="refresh-outline" size={24} color="#f57c00" />
              </View>
              <Text style={styles.quickActionText}>Refresh</Text>
            </TouchableOpacity>
          </View>
        </View>

        {/* Calendar Status */}
        {attendanceData?.calendar_status && (
          <View style={[
            styles.statusCard,
            attendanceData.calendar_status.is_holiday ? styles.statusCardHoliday :
            attendanceData.calendar_status.school_status === 'break' ? styles.statusCardBreak :
            styles.statusCardSession
          ]}>
            <Ionicons 
              name={attendanceData.calendar_status.is_holiday ? 'warning' :
                    attendanceData.calendar_status.school_status === 'break' ? 'information-circle' : 'checkmark-circle'} 
              size={24} 
              color={attendanceData.calendar_status.is_holiday ? '#c5221f' :
                      attendanceData.calendar_status.school_status === 'break' ? '#f9ab00' : '#137333'} 
            />
            <View style={styles.statusCardContent}>
              <Text style={styles.statusCardTitle}>
                {attendanceData.calendar_status.is_holiday ? 'School is on Holiday' :
                 attendanceData.calendar_status.school_status === 'break' ? 'School is on Break' :
                 'School is In Session'}
              </Text>
              {attendanceData.calendar_status.current_holiday && (
                <Text style={styles.statusCardSubtitle}>
                  {attendanceData.calendar_status.current_holiday.holiday_name} 
                  ({new Date(attendanceData.calendar_status.current_holiday.start_date).toLocaleDateString()} - 
                  {new Date(attendanceData.calendar_status.current_holiday.end_date).toLocaleDateString()})
                </Text>
              )}
              {attendanceData.calendar_status.current_term && (
                <Text style={styles.statusCardSubtitle}>
                  Active Term: {attendanceData.calendar_status.current_term.term_name}
                </Text>
              )}
            </View>
          </View>
        )}

        {/* Attendance Statistics */}
        <View style={styles.statsSection}>
          <Text style={styles.sectionTitle}>Attendance Statistics</Text>
          
          {/* Date Range Filter */}
          <View style={styles.dateFilterContainer}>
            <TouchableOpacity 
              style={styles.dateFilterButton}
              onPress={() => setStartDatePickerVisible(true)}
            >
              <Ionicons name="calendar-outline" size={20} color="#FF6B35" />
              <Text style={styles.dateFilterText}>
                From: {filterStartDate}
              </Text>
              <Ionicons name="chevron-down" size={20} color="#5f6368" />
            </TouchableOpacity>
            
            <TouchableOpacity 
              style={styles.dateFilterButton}
              onPress={() => setEndDatePickerVisible(true)}
            >
              <Ionicons name="calendar-outline" size={20} color="#FF6B35" />
              <Text style={styles.dateFilterText}>
                To: {filterEndDate}
              </Text>
              <Ionicons name="chevron-down" size={20} color="#5f6368" />
            </TouchableOpacity>
            
            <TouchableOpacity 
              style={styles.applyFilterButton}
              onPress={() => {
                console.log('Apply clicked with dates:', filterStartDate, filterEndDate);
                loadAttendanceData();
              }}
            >
              <Ionicons name="filter" size={20} color="#ffffff" />
              <Text style={styles.applyFilterText}>Apply</Text>
            </TouchableOpacity>
            
            <TouchableOpacity 
              style={styles.clearFilterButton}
              onPress={() => {
                console.log('Clear filter clicked');
                const today = new Date().toISOString().split('T')[0];
                console.log('Setting dates to today:', today);
                setFilterStartDate(today);
                setFilterEndDate(today);
                setStudentSearchQuery('');
                loadAttendanceData();
              }}
            >
              <Ionicons name="refresh" size={20} color="#5f6368" />
              <Text style={styles.clearFilterText}>Clear</Text>
            </TouchableOpacity>
          </View>

          {attendanceData?.monthly_summary && attendanceData.monthly_summary.total_records > 0 ? (
            <>
              <View style={styles.statsGrid}>
                <View style={styles.statCard}>
                  <Ionicons name="checkmark-circle" size={24} color="#137333" />
                  <Text style={styles.statValue}>{attendanceData.monthly_summary.total_present}</Text>
                  <Text style={styles.statLabel}>Present</Text>
                </View>
                <View style={styles.statCard}>
                  <Ionicons name="close-circle" size={24} color="#c5221f" />
                  <Text style={[styles.statValue, styles.statValueAbsent]}>{attendanceData.monthly_summary.total_absent}</Text>
                  <Text style={styles.statLabel}>Absent</Text>
                </View>
                <View style={styles.statCard}>
                  <Ionicons name="time" size={24} color="#f9ab00" />
                  <Text style={[styles.statValue, styles.statValueLate]}>{attendanceData.monthly_summary.total_late}</Text>
                  <Text style={styles.statLabel}>Late</Text>
                </View>
                <View style={styles.statCard}>
                  <Ionicons name="information-circle" size={24} color="#1967d2" />
                  <Text style={[styles.statValue, styles.statValueExcused]}>{attendanceData.monthly_summary.total_excused}</Text>
                  <Text style={styles.statLabel}>Excused</Text>
                </View>
              </View>
              <View style={styles.attendanceRateCard}>
                <Text style={styles.attendanceRateLabel}>Days Recorded</Text>
                <Text style={styles.attendanceRateValue}>{attendanceData.monthly_summary.days_recorded}</Text>
              </View>
              <View style={styles.attendanceRateCard}>
                <Text style={styles.attendanceRateLabel}>Total Records</Text>
                <Text style={styles.attendanceRateValue}>{attendanceData.monthly_summary.total_records}</Text>
              </View>
            </>
          ) : (
            <View style={styles.emptyState}>
              <Ionicons name="calendar-outline" size={48} color="#9aa0a6" />
              <Text style={styles.emptyText}>No attendance records for selected period</Text>
            </View>
          )}

          {/* Student Attendance List */}
          {attendanceData?.student_attendance_details && attendanceData.student_attendance_details.length > 0 && (
            <View style={styles.studentListSection}>
              <Text style={styles.sectionTitle}>Student Attendance Details</Text>
              
              {/* Student Search */}
              <View style={styles.studentSearchContainer}>
                <Ionicons name="search" size={20} color="#5f6368" style={styles.studentSearchIcon} />
                <TextInput
                  style={styles.studentSearchInput}
                  placeholder="Search by name or admission number..."
                  placeholderTextColor="#9aa0a6"
                  value={studentSearchQuery}
                  onChangeText={setStudentSearchQuery}
                />
                {studentSearchQuery.length > 0 && (
                  <TouchableOpacity onPress={() => setStudentSearchQuery('')}>
                    <Ionicons name="close-circle" size={20} color="#5f6368" />
                  </TouchableOpacity>
                )}
              </View>
              
              {attendanceData.student_attendance_details
                .filter(student => {
                  const searchLower = studentSearchQuery.toLowerCase();
                  const fullName = `${student.first_name} ${student.last_name}`.toLowerCase();
                  const admissionNumber = student.admission_number.toLowerCase();
                  return fullName.includes(searchLower) || admissionNumber.includes(searchLower);
                })
                .map((student) => {
                const attendanceRecords = student.attendance_records ? student.attendance_records.split('|') : [];
                const presentCount = attendanceRecords.filter(r => r.includes(':present')).length;
                const absentCount = attendanceRecords.filter(r => r.includes(':absent')).length;
                const lateCount = attendanceRecords.filter(r => r.includes(':late')).length;
                const excusedCount = attendanceRecords.filter(r => r.includes(':excused')).length;
                
                // Parse attendance records into structured data
                const parsedRecords = attendanceRecords
                  .map(record => {
                    const [date, status] = record.split(':');
                    if (!date || !status) return null;
                    return { date, status };
                  })
                  .filter((r): r is { date: string; status: string } => r !== null)
                  .sort((a, b) => new Date(b.date).getTime() - new Date(a.date).getTime());
                
                const statusColors = {
                  present: '#137333',
                  absent: '#c5221f',
                  late: '#f9ab00',
                  excused: '#1967d2'
                };
                
                const statusLabels = {
                  present: 'Present',
                  absent: 'Absent',
                  late: 'Late',
                  excused: 'Excused'
                };
                
                return (
                  <View key={student.id} style={styles.studentAttendanceCard}>
                    <View style={styles.studentAttendanceHeader}>
                      <View style={styles.studentAttendanceInfo}>
                        <Text style={styles.studentAttendanceName}>
                          {student.first_name} {student.last_name}
                        </Text>
                        <Text style={styles.studentAttendanceAdm}>
                          {student.admission_number}
                        </Text>
                        {student.class_name && (
                          <Text style={styles.studentAttendanceClass}>
                            {student.class_name}
                          </Text>
                        )}
                      </View>
                      <View style={styles.studentAttendanceStats}>
                        <View style={styles.miniStat}>
                          <Ionicons name="checkmark-circle" size={14} color="#137333" />
                          <Text style={styles.miniStatText}>{presentCount}</Text>
                        </View>
                        <View style={styles.miniStat}>
                          <Ionicons name="close-circle" size={14} color="#c5221f" />
                          <Text style={styles.miniStatText}>{absentCount}</Text>
                        </View>
                        <View style={styles.miniStat}>
                          <Ionicons name="time" size={14} color="#f9ab00" />
                          <Text style={styles.miniStatText}>{lateCount}</Text>
                        </View>
                        <View style={styles.miniStat}>
                          <Ionicons name="information-circle" size={14} color="#1967d2" />
                          <Text style={styles.miniStatText}>{excusedCount}</Text>
                        </View>
                      </View>
                    </View>
                    
                    {/* Attendance Records Table */}
                    {parsedRecords.length > 0 ? (
                      <View style={styles.attendanceRecordsTable}>
                        <View style={styles.tableHeader}>
                          <Text style={styles.tableHeaderText}>Date</Text>
                          <Text style={styles.tableHeaderText}>Status</Text>
                        </View>
                        {parsedRecords.map((record, index) => (
                          <View key={index} style={[
                            styles.tableRow,
                            index % 2 === 0 ? styles.tableRowEven : styles.tableRowOdd
                          ]}>
                            <Text style={styles.tableCellText}>
                              {new Date(record.date).toLocaleDateString('en-US', { 
                                weekday: 'short',
                                month: 'short', 
                                day: 'numeric',
                                year: 'numeric'
                              })}
                            </Text>
                            <View style={[
                              styles.statusBadge,
                              { backgroundColor: statusColors[record.status as keyof typeof statusColors] || '#9aa0a6' }
                            ]}>
                              <Text style={styles.statusText}>
                                {statusLabels[record.status as keyof typeof statusLabels] || record.status}
                              </Text>
                            </View>
                          </View>
                        ))}
                      </View>
                    ) : (
                      <View style={styles.noAttendanceRecords}>
                        <Ionicons name="calendar-outline" size={32} color="#9aa0a6" />
                        <Text style={styles.noAttendanceText}>No attendance records for this period</Text>
                      </View>
                    )}
                  </View>
                );
              })}
            </View>
          )}
        </View>

        {/* Class Selection */}
        {attendanceData?.classes && attendanceData.classes.length > 0 && (
          <View style={styles.classSelectionSection}>
            <Text style={styles.sectionTitle}>Select Class</Text>
            <ScrollView horizontal showsHorizontalScrollIndicator={false} style={styles.classSelector}>
              {attendanceData.classes.map((classItem) => (
                <TouchableOpacity
                  key={classItem.id}
                  style={[
                    styles.classChip,
                    selectedClassId === classItem.id && styles.classChipActive
                  ]}
                  onPress={() => setSelectedClassId(classItem.id)}
                >
                  <Text style={[
                    styles.classChipText,
                    selectedClassId === classItem.id && styles.classChipTextActive
                  ]}>
                    {classItem.class_name}
                  </Text>
                </TouchableOpacity>
              ))}
            </ScrollView>
          </View>
        )}

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
              onPress={() => router.push('/(tabs)/dashboard')}
            >
              <View style={styles.quickLinkIcon}>
                <Ionicons name="home-outline" size={24} color="#1967d2" />
              </View>
              <Text style={styles.quickLinkText}>Dashboard</Text>
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

      {/* Mark Attendance Modal */}
      <Modal
        visible={markAttendanceVisible}
        animationType="slide"
        transparent={true}
        onRequestClose={() => setMarkAttendanceVisible(false)}
      >
        <View style={styles.modalOverlay}>
          <View style={styles.modalContent}>
            <View style={styles.modalHeader}>
              <Text style={styles.modalTitle}>Mark Attendance</Text>
              <TouchableOpacity onPress={() => setMarkAttendanceVisible(false)}>
                <Ionicons name="close" size={24} color="#5f6368" />
              </TouchableOpacity>
            </View>

            <ScrollView 
              style={styles.modalScrollView}
              showsVerticalScrollIndicator={false}
              keyboardShouldPersistTaps="handled"
              contentContainerStyle={styles.modalScrollContent}
            >
              {/* Date Display */}
              <View style={styles.dateDisplay}>
                <Ionicons name="calendar" size={20} color="#FF6B35" />
                <Text style={styles.dateText}>{selectedDate}</Text>
              </View>

              {/* Attendance Statistics */}
              <View style={styles.statsGrid}>
                <View style={styles.statCard}>
                  <Text style={styles.statValue}>{stats.present}</Text>
                  <Text style={styles.statLabel}>Present</Text>
                </View>
                <View style={styles.statCard}>
                  <Text style={[styles.statValue, styles.statValueAbsent]}>{stats.absent}</Text>
                  <Text style={styles.statLabel}>Absent</Text>
                </View>
                <View style={styles.statCard}>
                  <Text style={[styles.statValue, styles.statValueLate]}>{stats.late}</Text>
                  <Text style={styles.statLabel}>Late</Text>
                </View>
                <View style={styles.statCard}>
                  <Text style={[styles.statValue, styles.statValueExcused]}>{stats.excused}</Text>
                  <Text style={styles.statLabel}>Excused</Text>
                </View>
              </View>

              {/* Attendance Rate */}
              <View style={styles.attendanceRateCard}>
                <Text style={styles.attendanceRateLabel}>Attendance Rate</Text>
                <Text style={styles.attendanceRateValue}>{attendanceRate}%</Text>
              </View>

              {/* Bulk Actions */}
              <View style={styles.bulkActions}>
                <TouchableOpacity 
                  style={styles.bulkActionButton}
                  onPress={() => setStudents(students.map(s => ({ ...s, status: 'present' })))}
                >
                  <Ionicons name="checkmark-circle" size={18} color="#137333" />
                  <Text style={styles.bulkActionText}>Mark All Present</Text>
                </TouchableOpacity>
                <TouchableOpacity 
                  style={styles.bulkActionButton}
                  onPress={() => setStudents(students.map(s => ({ ...s, status: 'absent' })))}
                >
                  <Ionicons name="close-circle" size={18} color="#c5221f" />
                  <Text style={styles.bulkActionText}>Mark All Absent</Text>
                </TouchableOpacity>
                <TouchableOpacity 
                  style={styles.bulkActionButton}
                  onPress={() => setStudents(students.map(s => ({ ...s, status: 'late' })))}
                >
                  <Ionicons name="time" size={18} color="#f9ab00" />
                  <Text style={styles.bulkActionText}>Mark All Late</Text>
                </TouchableOpacity>
                <TouchableOpacity 
                  style={styles.bulkActionButton}
                  onPress={() => setStudents(students.map(s => ({ ...s, status: 'excused' })))}
                >
                  <Ionicons name="information-circle" size={18} color="#1967d2" />
                  <Text style={styles.bulkActionText}>Mark All Excused</Text>
                </TouchableOpacity>
                <TouchableOpacity 
                  style={styles.bulkActionButton}
                  onPress={() => setStudents(students.map(s => ({ ...s, status: null })))}
                >
                  <Ionicons name="refresh" size={18} color="#5f6368" />
                  <Text style={styles.bulkActionText}>Clear All</Text>
                </TouchableOpacity>
              </View>

              {/* Student Search */}
              <View style={styles.searchContainer}>
                <Ionicons name="search" size={20} color="#5f6368" style={styles.searchIcon} />
                <TextInput
                  style={styles.searchInput}
                  placeholder="Search by admission number or name..."
                  value={searchQuery}
                  onChangeText={setSearchQuery}
                />
              </View>

              {/* Student List */}
              <View style={styles.studentList}>
                {filteredStudents.map((student) => (
                  <View key={student.id} style={styles.studentItem}>
                    <View style={styles.studentInfo}>
                      <Text style={styles.studentAdmission}>{student.admission_number}</Text>
                      <Text style={styles.studentName}>
                        {student.first_name} {student.last_name}
                      </Text>
                      {student.stream_name && (
                        <Text style={styles.studentStream}>{student.stream_name}</Text>
                      )}
                    </View>
                    <View style={styles.statusButtons}>
                      <TouchableOpacity
                        style={[
                          styles.statusButton,
                          student.status === 'present' && styles.statusButtonPresent
                        ]}
                        onPress={() => markStudentStatus(student.id, 'present')}
                      >
                        <Ionicons name="checkmark" size={16} color={student.status === 'present' ? '#ffffff' : '#137333'} />
                      </TouchableOpacity>
                      <TouchableOpacity
                        style={[
                          styles.statusButton,
                          student.status === 'absent' && styles.statusButtonAbsent
                        ]}
                        onPress={() => markStudentStatus(student.id, 'absent')}
                      >
                        <Ionicons name="close" size={16} color={student.status === 'absent' ? '#ffffff' : '#c5221f'} />
                      </TouchableOpacity>
                      <TouchableOpacity
                        style={[
                          styles.statusButton,
                          student.status === 'late' && styles.statusButtonLate
                        ]}
                        onPress={() => markStudentStatus(student.id, 'late')}
                      >
                        <Ionicons name="time" size={16} color={student.status === 'late' ? '#ffffff' : '#f9ab00'} />
                      </TouchableOpacity>
                      <TouchableOpacity
                        style={[
                          styles.statusButton,
                          student.status === 'excused' && styles.statusButtonExcused
                        ]}
                        onPress={() => markStudentStatus(student.id, 'excused')}
                      >
                        <Ionicons name="information" size={16} color={student.status === 'excused' ? '#ffffff' : '#1967d2'} />
                      </TouchableOpacity>
                    </View>
                  </View>
                ))}
                {filteredStudents.length === 0 && (
                  <View style={styles.emptyState}>
                    <Ionicons name="people-outline" size={48} color="#9aa0a6" />
                    <Text style={styles.emptyText}>No students found</Text>
                  </View>
                )}
              </View>

              {/* Save Button */}
              <TouchableOpacity
                style={[styles.saveButton, saving && styles.saveButtonDisabled]}
                onPress={saveAttendanceData}
                disabled={saving}
              >
                {saving ? (
                  <ActivityIndicator size="small" color="#ffffff" />
                ) : (
                  <>
                    <Ionicons name="checkmark-circle" size={20} color="#ffffff" />
                    <Text style={styles.saveButtonText}>Save Attendance</Text>
                  </>
                )}
              </TouchableOpacity>
            </ScrollView>
          </View>
        </View>
      </Modal>

      {/* Start Date Picker Modal */}
      <Modal
        visible={startDatePickerVisible}
        animationType="slide"
        transparent={true}
        onRequestClose={() => setStartDatePickerVisible(false)}
      >
        <View style={styles.modalOverlay}>
          <View style={styles.datePickerModal}>
            <View style={styles.datePickerHeader}>
              <Text style={styles.datePickerTitle}>Select Start Date</Text>
              <TouchableOpacity onPress={() => setStartDatePickerVisible(false)}>
                <Ionicons name="close" size={24} color="#5f6368" />
              </TouchableOpacity>
            </View>
            <DateTimePicker
              value={filterStartDate ? new Date(filterStartDate) : new Date()}
              mode="date"
              display={Platform.OS === 'ios' ? 'compact' : 'default'}
              onChange={(event, selectedDate) => {
                console.log('Start date picker changed:', selectedDate);
                if (selectedDate) {
                  const formattedDate = selectedDate.toISOString().split('T')[0];
                  console.log('Setting filterStartDate to:', formattedDate);
                  setFilterStartDate(formattedDate);
                }
                if (Platform.OS === 'android') {
                  setStartDatePickerVisible(false);
                }
              }}
              style={styles.datePicker}
            />
            {Platform.OS === 'ios' && (
              <TouchableOpacity 
                style={styles.datePickerButton}
                onPress={() => setStartDatePickerVisible(false)}
              >
                <Text style={styles.datePickerButtonText}>Done</Text>
              </TouchableOpacity>
            )}
          </View>
        </View>
      </Modal>

      {/* End Date Picker Modal */}
      <Modal
        visible={endDatePickerVisible}
        animationType="slide"
        transparent={true}
        onRequestClose={() => setEndDatePickerVisible(false)}
      >
        <View style={styles.modalOverlay}>
          <View style={styles.datePickerModal}>
            <View style={styles.datePickerHeader}>
              <Text style={styles.datePickerTitle}>Select End Date</Text>
              <TouchableOpacity onPress={() => setEndDatePickerVisible(false)}>
                <Ionicons name="close" size={24} color="#5f6368" />
              </TouchableOpacity>
            </View>
            <DateTimePicker
              value={filterEndDate ? new Date(filterEndDate) : new Date()}
              mode="date"
              display={Platform.OS === 'ios' ? 'compact' : 'default'}
              onChange={(event, selectedDate) => {
                console.log('End date picker changed:', selectedDate);
                if (selectedDate) {
                  const formattedDate = selectedDate.toISOString().split('T')[0];
                  console.log('Setting filterEndDate to:', formattedDate);
                  setFilterEndDate(formattedDate);
                }
                if (Platform.OS === 'android') {
                  setEndDatePickerVisible(false);
                }
              }}
              style={styles.datePicker}
            />
            {Platform.OS === 'ios' && (
              <TouchableOpacity 
                style={styles.datePickerButton}
                onPress={() => setEndDatePickerVisible(false)}
              >
                <Text style={styles.datePickerButtonText}>Done</Text>
              </TouchableOpacity>
            )}
          </View>
        </View>
      </Modal>

      {/* Custom Alert Modal */}
      <Modal
        visible={alertVisible}
        transparent={true}
        animationType="fade"
        onRequestClose={() => setAlertVisible(false)}
      >
        <View style={styles.alertOverlay}>
          <View style={styles.alertModal}>
            <View style={styles.alertHeader}>
              <Ionicons 
                name={
                  alertType === 'error' ? 'close-circle' :
                  alertType === 'success' ? 'checkmark-circle' :
                  alertType === 'confirm' ? 'help-circle' : 'information-circle'
                }
                size={32}
                color={
                  alertType === 'error' ? '#c5221f' :
                  alertType === 'success' ? '#137333' :
                  alertType === 'confirm' ? '#FF6B35' : '#1967d2'
                }
              />
            </View>
            <Text style={styles.alertTitle}>{alertTitle}</Text>
            <Text style={styles.alertMessage}>{alertMessage}</Text>
            <View style={styles.alertButtons}>
              {alertType === 'confirm' && (
                <TouchableOpacity
                  style={[styles.alertButton, styles.alertButtonCancel]}
                  onPress={() => setAlertVisible(false)}
                >
                  <Text style={styles.alertButtonTextCancel}>Cancel</Text>
                </TouchableOpacity>
              )}
              <TouchableOpacity
                style={[
                  styles.alertButton,
                  alertType === 'confirm' ? styles.alertButtonConfirm : styles.alertButtonOK
                ]}
                onPress={handleAlertConfirm}
              >
                <Text style={[
                  alertType === 'confirm' ? styles.alertButtonTextConfirm : styles.alertButtonTextOK
                ]}>
                  {alertType === 'confirm' ? 'Continue' : 'OK'}
                </Text>
              </TouchableOpacity>
            </View>
          </View>
        </View>
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
  content: {
    flex: 1,
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
  subtitle: {
    fontSize: 14,
    color: '#5f6368',
  },
  quickActionsSection: {
    marginBottom: 24,
    paddingHorizontal: 16,
    paddingBottom: 100,
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
  statusCard: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#f8f9fa',
    borderRadius: 12,
    padding: 16,
    marginBottom: 24,
    marginHorizontal: 16,
  },
  statusCardHoliday: {
    backgroundColor: '#fce8e6',
    borderWidth: 1,
    borderColor: '#c5221f',
  },
  statusCardBreak: {
    backgroundColor: '#fef7e0',
    borderWidth: 1,
    borderColor: '#f9ab00',
  },
  statusCardSession: {
    backgroundColor: '#e6f4ea',
    borderWidth: 1,
    borderColor: '#137333',
  },
  statusCardContent: {
    flex: 1,
    marginLeft: 12,
  },
  statusCardTitle: {
    fontSize: 16,
    fontWeight: '600',
    color: '#202124',
    marginBottom: 4,
  },
  statusCardSubtitle: {
    fontSize: 14,
    color: '#5f6368',
  },
  statsSection: {
    paddingHorizontal: 16,
    marginBottom: 24,
  },
  dateFilterContainer: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 8,
    marginBottom: 16,
  },
  dateFilterButton: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 12,
    paddingVertical: 8,
    borderRadius: 8,
    backgroundColor: '#f8f9fa',
    borderWidth: 1,
    borderColor: '#e8eaed',
    flex: 1,
    minWidth: 120,
  },
  dateFilterText: {
    fontSize: 12,
    color: '#202124',
    marginLeft: 8,
    flex: 1,
  },
  applyFilterButton: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 16,
    paddingVertical: 8,
    borderRadius: 8,
    backgroundColor: '#FF6B35',
    minWidth: 80,
  },
  applyFilterText: {
    fontSize: 12,
    color: '#ffffff',
    marginLeft: 4,
    fontWeight: '500',
  },
  clearFilterButton: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 16,
    paddingVertical: 8,
    borderRadius: 8,
    backgroundColor: '#f8f9fa',
    borderWidth: 1,
    borderColor: '#e8eaed',
    minWidth: 80,
  },
  clearFilterText: {
    fontSize: 12,
    color: '#5f6368',
    marginLeft: 4,
    fontWeight: '500',
  },
  datePickerModal: {
    backgroundColor: '#ffffff',
    borderRadius: 16,
    width: '90%',
    padding: 16,
  },
  datePickerHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 16,
  },
  datePickerTitle: {
    fontSize: 18,
    fontWeight: '600',
    color: '#202124',
  },
  datePicker: {
    marginBottom: 16,
  },
  datePickerButton: {
    backgroundColor: '#FF6B35',
    paddingHorizontal: 24,
    paddingVertical: 12,
    borderRadius: 8,
    alignItems: 'center',
  },
  datePickerButtonText: {
    color: '#ffffff',
    fontSize: 16,
    fontWeight: '500',
  },
  classSelectionSection: {
    paddingHorizontal: 16,
    marginBottom: 24,
  },
  sectionTitle: {
    fontSize: 18,
    fontWeight: '600',
    color: '#202124',
    marginBottom: 12,
  },
  classSelector: {
    flexDirection: 'row',
  },
  classChip: {
    paddingHorizontal: 16,
    paddingVertical: 8,
    borderRadius: 20,
    backgroundColor: '#f8f9fa',
    borderWidth: 1,
    borderColor: '#e8eaed',
    marginRight: 8,
  },
  classChipActive: {
    backgroundColor: '#FF6B35',
    borderColor: '#FF6B35',
  },
  classChipText: {
    fontSize: 14,
    color: '#5f6368',
  },
  classChipTextActive: {
    color: '#ffffff',
    fontWeight: '500',
  },
  modalOverlay: {
    flex: 1,
    backgroundColor: 'rgba(0, 0, 0, 0.5)',
    justifyContent: 'center',
    alignItems: 'center',
  },
  modalContent: {
    backgroundColor: '#ffffff',
    borderRadius: 16,
    width: '90%',
    maxHeight: '90%',
  },
  modalHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    padding: 16,
    borderBottomWidth: 1,
    borderBottomColor: '#e8eaed',
  },
  modalTitle: {
    fontSize: 18,
    fontWeight: '600',
    color: '#202124',
  },
  modalScrollView: {
    maxHeight: '100%',
  },
  modalScrollContent: {
    paddingBottom: 100,
  },
  dateDisplay: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#f8f9fa',
    padding: 12,
    borderRadius: 8,
    marginBottom: 16,
  },
  dateText: {
    fontSize: 16,
    fontWeight: '500',
    color: '#202124',
    marginLeft: 8,
  },
  statsGrid: {
    flexDirection: 'row',
    gap: 12,
    marginBottom: 16,
  },
  statCard: {
    flex: 1,
    backgroundColor: '#f8f9fa',
    borderRadius: 8,
    padding: 12,
    alignItems: 'center',
  },
  statValue: {
    fontSize: 24,
    fontWeight: '700',
    color: '#137333',
  },
  statValueAbsent: {
    color: '#c5221f',
  },
  statValueLate: {
    color: '#f9ab00',
  },
  statValueExcused: {
    color: '#1967d2',
  },
  statLabel: {
    fontSize: 12,
    color: '#5f6368',
  },
  attendanceRateCard: {
    backgroundColor: '#e8f0fe',
    borderRadius: 8,
    padding: 16,
    marginBottom: 16,
    alignItems: 'center',
  },
  attendanceRateLabel: {
    fontSize: 14,
    color: '#1967d2',
    marginBottom: 4,
  },
  attendanceRateValue: {
    fontSize: 32,
    fontWeight: '700',
    color: '#1967d2',
  },
  bulkActions: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 8,
    marginBottom: 16,
  },
  bulkActionButton: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 12,
    paddingVertical: 8,
    borderRadius: 20,
    backgroundColor: '#f8f9fa',
    borderWidth: 1,
    borderColor: '#e8eaed',
  },
  bulkActionText: {
    fontSize: 12,
    color: '#5f6368',
    marginLeft: 4,
  },
  searchContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#f8f9fa',
    borderRadius: 8,
    paddingHorizontal: 12,
    marginBottom: 16,
  },
  searchIcon: {
    marginRight: 8,
  },
  searchInput: {
    flex: 1,
    paddingVertical: 12,
    fontSize: 14,
    color: '#202124',
  },
  studentList: {
    marginBottom: 16,
  },
  studentItem: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingVertical: 12,
    borderBottomWidth: 1,
    borderBottomColor: '#e8eaed',
  },
  studentInfo: {
    flex: 1,
  },
  studentAdmission: {
    fontSize: 12,
    color: '#5f6368',
    marginBottom: 2,
  },
  studentName: {
    fontSize: 16,
    fontWeight: '500',
    color: '#202124',
  },
  studentStream: {
    fontSize: 12,
    color: '#9aa0a6',
    marginTop: 2,
  },
  statusButtons: {
    flexDirection: 'row',
    gap: 8,
  },
  statusButton: {
    width: 36,
    height: 36,
    borderRadius: 18,
    backgroundColor: '#f8f9fa',
    borderWidth: 1,
    borderColor: '#e8eaed',
    justifyContent: 'center',
    alignItems: 'center',
  },
  statusButtonPresent: {
    backgroundColor: '#137333',
    borderColor: '#137333',
  },
  statusButtonAbsent: {
    backgroundColor: '#c5221f',
    borderColor: '#c5221f',
  },
  statusButtonLate: {
    backgroundColor: '#f9ab00',
    borderColor: '#f9ab00',
  },
  statusButtonExcused: {
    backgroundColor: '#1967d2',
    borderColor: '#1967d2',
  },
  emptyState: {
    alignItems: 'center',
    paddingVertical: 32,
  },
  emptyText: {
    fontSize: 14,
    color: '#5f6368',
    marginTop: 16,
  },
  saveButton: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    backgroundColor: '#FF6B35',
    paddingHorizontal: 24,
    paddingVertical: 12,
    borderRadius: 25,
  },
  saveButtonDisabled: {
    backgroundColor: '#ccc',
  },
  saveButtonText: {
    color: '#ffffff',
    fontSize: 14,
    fontWeight: '500',
  },
  studentListSection: {
    paddingHorizontal: 16,
    marginBottom: 24,
  },
  studentSearchContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#f8f9fa',
    borderRadius: 8,
    paddingHorizontal: 12,
    marginBottom: 16,
  },
  studentSearchIcon: {
    marginRight: 8,
  },
  studentSearchInput: {
    flex: 1,
    paddingVertical: 12,
    fontSize: 14,
    color: '#202124',
  },
  studentAttendanceCard: {
    backgroundColor: '#ffffff',
    borderRadius: 12,
    padding: 16,
    marginBottom: 12,
    borderWidth: 1,
    borderColor: '#e8eaed',
  },
  studentAttendanceHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 12,
  },
  studentAttendanceInfo: {
    flex: 1,
  },
  studentAttendanceName: {
    fontSize: 16,
    fontWeight: '600',
    color: '#202124',
    marginBottom: 4,
  },
  studentAttendanceAdm: {
    fontSize: 12,
    color: '#5f6368',
    marginBottom: 2,
  },
  studentAttendanceClass: {
    fontSize: 12,
    color: '#9aa0a6',
  },
  studentAttendanceStats: {
    flexDirection: 'row',
    gap: 12,
  },
  miniStat: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
  },
  miniStatText: {
    fontSize: 12,
    fontWeight: '600',
    color: '#202124',
  },
  attendanceRecordsScroll: {
    marginTop: 8,
  },
  attendanceRecordItem: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    paddingHorizontal: 8,
    paddingVertical: 4,
    backgroundColor: '#f8f9fa',
    borderRadius: 8,
    marginRight: 8,
  },
  attendanceRecordDate: {
    fontSize: 11,
    color: '#5f6368',
  },
  attendanceRecordDot: {
    width: 8,
    height: 8,
    borderRadius: 4,
  },
  attendanceRecordsList: {
    marginTop: 12,
  },
  attendanceRecordsTitle: {
    fontSize: 14,
    fontWeight: '600',
    color: '#5f6368',
    marginBottom: 8,
  },
  attendanceRecordRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingVertical: 8,
    paddingHorizontal: 12,
    backgroundColor: '#f8f9fa',
    borderRadius: 8,
    marginBottom: 6,
  },
  attendanceRecordDateText: {
    fontSize: 13,
    color: '#202124',
    fontWeight: '500',
  },
  attendanceStatusBadge: {
    paddingHorizontal: 10,
    paddingVertical: 4,
    borderRadius: 12,
  },
  attendanceStatusText: {
    fontSize: 11,
    fontWeight: '600',
    color: '#ffffff',
    textTransform: 'capitalize',
  },
  attendanceRecordsTable: {
    marginTop: 12,
    borderWidth: 1,
    borderColor: '#000',
    borderRadius: 0,
    overflow: 'hidden',
  },
  tableHeader: {
    flexDirection: 'row',
    backgroundColor: '#f0f0f0',
    borderBottomWidth: 2,
    borderBottomColor: '#000',
  },
  tableHeaderText: {
    flex: 1,
    padding: 12,
    fontSize: 13,
    fontWeight: '600',
    color: '#000',
    borderRightWidth: 1,
    borderRightColor: '#000',
  },
  tableRow: {
    flexDirection: 'row',
    borderBottomWidth: 1,
    borderBottomColor: '#000',
  },
  tableRowEven: {
    backgroundColor: '#ffffff',
  },
  tableRowOdd: {
    backgroundColor: '#f9f9f9',
  },
  tableCellText: {
    flex: 1,
    padding: 12,
    fontSize: 13,
    color: '#000',
    borderRightWidth: 1,
    borderRightColor: '#000',
  },
  statusBadge: {
    flex: 1,
    padding: 8,
    alignItems: 'center',
    justifyContent: 'center',
  },
  statusText: {
    fontSize: 12,
    fontWeight: '600',
    color: '#ffffff',
    textTransform: 'capitalize',
  },
  noAttendanceRecords: {
    alignItems: 'center',
    paddingVertical: 24,
  },
  noAttendanceText: {
    fontSize: 12,
    color: '#9aa0a6',
    marginTop: 8,
  },
  alertOverlay: {
    flex: 1,
    backgroundColor: 'rgba(0, 0, 0, 0.5)',
    justifyContent: 'center',
    alignItems: 'center',
  },
  alertModal: {
    backgroundColor: '#ffffff',
    borderRadius: 16,
    padding: 24,
    width: '85%',
    maxWidth: 400,
    alignItems: 'center',
  },
  alertHeader: {
    marginBottom: 16,
  },
  alertTitle: {
    fontSize: 18,
    fontWeight: '600',
    color: '#202124',
    marginBottom: 8,
    textAlign: 'center',
  },
  alertMessage: {
    fontSize: 14,
    color: '#5f6368',
    textAlign: 'center',
    marginBottom: 24,
    lineHeight: 20,
  },
  alertButtons: {
    flexDirection: 'row',
    gap: 12,
    width: '100%',
  },
  alertButton: {
    flex: 1,
    paddingVertical: 12,
    borderRadius: 8,
    alignItems: 'center',
    justifyContent: 'center',
  },
  alertButtonCancel: {
    backgroundColor: '#f1f3f4',
  },
  alertButtonConfirm: {
    backgroundColor: '#FF6B35',
  },
  alertButtonOK: {
    backgroundColor: '#FF6B35',
  },
  alertButtonTextCancel: {
    fontSize: 14,
    fontWeight: '500',
    color: '#5f6368',
  },
  alertButtonTextConfirm: {
    fontSize: 14,
    fontWeight: '500',
    color: '#ffffff',
  },
  alertButtonTextOK: {
    fontSize: 14,
    fontWeight: '500',
    color: '#ffffff',
  },
});
