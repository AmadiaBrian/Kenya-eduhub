import { View, Text, StyleSheet, ScrollView, TouchableOpacity, ActivityIndicator, RefreshControl, Modal, TextInput, Alert, KeyboardAvoidingView, Platform } from 'react-native';
import DateTimePicker from '@react-native-community/datetimepicker';
import { useRouter } from 'expo-router';
import { useState, useEffect } from 'react';
import { Ionicons } from '@expo/vector-icons';
import * as DocumentPicker from 'expo-document-picker';
import * as FileSystem from 'expo-file-system/legacy';
import * as Sharing from 'expo-sharing';
import { getAssignments, getAssignmentAnalytics, AssignmentsResponse, AssignmentAnalyticsResponse, API_BASE_URL } from '../../lib/api';

export default function Assignments() {
  const router = useRouter();
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [assignmentsData, setAssignmentsData] = useState<AssignmentsResponse | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [uploadModalVisible, setUploadModalVisible] = useState(false);
  const [uploadTitle, setUploadTitle] = useState('');
  const [uploadDescription, setUploadDescription] = useState('');
  const [uploadType, setUploadType] = useState('syllabus');
  const [uploadClassId, setUploadClassId] = useState('');
  const [uploadSubjectId, setUploadSubjectId] = useState('');
  const [uploadDueDate, setUploadDueDate] = useState('');
  const [uploadFile, setUploadFile] = useState<any>(null);
  const [uploading, setUploading] = useState(false);
  const [editModalVisible, setEditModalVisible] = useState(false);
  const [editingAssignment, setEditingAssignment] = useState<any>(null);
  const [editTitle, setEditTitle] = useState('');
  const [editDescription, setEditDescription] = useState('');
  const [editType, setEditType] = useState('syllabus');
  const [editClassId, setEditClassId] = useState('');
  const [editSubjectId, setEditSubjectId] = useState('');
  const [editDueDate, setEditDueDate] = useState('');
  const [editFile, setEditFile] = useState<any>(null);
  const [updating, setUpdating] = useState(false);
  const [classPickerVisible, setClassPickerVisible] = useState(false);
  const [subjectPickerVisible, setSubjectPickerVisible] = useState(false);
  const [analyticsModalVisible, setAnalyticsModalVisible] = useState(false);
  const [analyticsData, setAnalyticsData] = useState<AssignmentAnalyticsResponse | null>(null);
  const [analyticsLoading, setAnalyticsLoading] = useState(false);
  const [selectedAssignmentForAnalytics, setSelectedAssignmentForAnalytics] = useState<any>(null);
  const [uploadDatePickerVisible, setUploadDatePickerVisible] = useState(false);
  const [editDatePickerVisible, setEditDatePickerVisible] = useState(false);
  const [filterType, setFilterType] = useState('all');
  const [sortBy, setSortBy] = useState('newest');
  const [filterModalVisible, setFilterModalVisible] = useState(false);

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

  const handleFilePick = async () => {
    try {
      const result = await DocumentPicker.getDocumentAsync({
        type: ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation', 'text/plain', 'image/jpeg', 'image/png'],
      });
      
      if (!result.canceled && result.assets && result.assets[0]) {
        setUploadFile(result.assets[0]);
      }
    } catch (error) {
      console.error('Error picking file:', error);
    }
  };

  const handleUpload = async () => {
    if (!uploadTitle || !uploadFile) {
      alert('Please fill in all required fields');
      return;
    }

    setUploading(true);
    try {
      const formData = new FormData();
      formData.append('title', uploadTitle);
      formData.append('description', uploadDescription);
      formData.append('assignment_type', uploadType);
      formData.append('class_id', uploadClassId);
      formData.append('subject_id', uploadSubjectId);
      formData.append('due_date', uploadDueDate);
      formData.append('file', {
        uri: uploadFile.uri,
        type: uploadFile.mimeType || 'application/octet-stream',
        name: uploadFile.name,
      } as any);

      const sessionData = await import('expo-secure-store').then(m => m.getItemAsync('teacherSession'));
      const session = JSON.parse(sessionData!);
      const sessionToken = session.session_token;

      const response = await fetch('https://03e9-129-222-147-23.ngrok-free.app/kenyaeduhub/teachers/api/upload_assignment.php', {
        method: 'POST',
        headers: {
          'Authorization': sessionToken,
          'Content-Type': 'multipart/form-data',
        },
        body: formData,
      });

      const data = await response.json();
      
      if (data.success) {
        setUploadModalVisible(false);
        setUploadTitle('');
        setUploadDescription('');
        setUploadType('syllabus');
        setUploadClassId('');
        setUploadSubjectId('');
        setUploadDueDate('');
        setUploadFile(null);
        await loadAssignments();
      } else {
        alert(data.error || 'Upload failed');
      }
    } catch (error) {
      console.error('Upload error:', error);
      alert('Upload failed');
    } finally {
      setUploading(false);
    }
  };

  const handleEdit = (assignment: any) => {
    setEditingAssignment(assignment);
    setEditTitle(assignment.title);
    setEditDescription(assignment.description || '');
    setEditType(assignment.assignment_type);
    setEditClassId(assignment.class_id || '');
    setEditSubjectId(assignment.subject_id || '');
    setEditDueDate(assignment.due_date || '');
    setEditFile(null);
    setEditModalVisible(true);
  };

  const handleEditFilePick = async () => {
    try {
      const result = await DocumentPicker.getDocumentAsync({
        type: ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation', 'text/plain', 'image/jpeg', 'image/png'],
      });
      
      if (!result.canceled && result.assets && result.assets[0]) {
        setEditFile(result.assets[0]);
      }
    } catch (error) {
      console.error('Error picking file:', error);
    }
  };

  const handleUpdate = async () => {
    if (!editTitle) {
      alert('Please fill in all required fields');
      return;
    }

    setUpdating(true);
    try {
      const formData = new FormData();
      formData.append('assignment_id', editingAssignment.id);
      formData.append('title', editTitle);
      formData.append('description', editDescription);
      formData.append('assignment_type', editType);
      formData.append('class_id', editClassId);
      formData.append('subject_id', editSubjectId);
      formData.append('due_date', editDueDate);
      
      if (editFile) {
        formData.append('file', {
          uri: editFile.uri,
          type: editFile.mimeType || 'application/octet-stream',
          name: editFile.name,
        } as any);
      }

      const sessionData = await import('expo-secure-store').then(m => m.getItemAsync('teacherSession'));
      const session = JSON.parse(sessionData!);
      const sessionToken = session.session_token;

      const response = await fetch('https://03e9-129-222-147-23.ngrok-free.app/kenyaeduhub/teachers/api/update_assignment.php', {
        method: 'POST',
        headers: {
          'Authorization': sessionToken,
          'Content-Type': 'multipart/form-data',
        },
        body: formData,
      });

      const data = await response.json();
      
      if (data.success) {
        setEditModalVisible(false);
        setEditingAssignment(null);
        setEditTitle('');
        setEditDescription('');
        setEditType('syllabus');
        setEditClassId('');
        setEditSubjectId('');
        setEditDueDate('');
        setEditFile(null);
        await loadAssignments();
      } else {
        alert(data.error || 'Update failed');
      }
    } catch (error) {
      console.error('Update error:', error);
      alert('Update failed');
    } finally {
      setUpdating(false);
    }
  };

  const handleDownload = async (assignment: any) => {
    try {
      if (!assignment.file_name) {
        alert('No file attached to this assignment');
        return;
      }

      const assignmentId = assignment.id;
      const fileUrl = `${API_BASE_URL}/download_assignment.php?assignment_id=${assignmentId}`;
      
      const filename = assignment.file_name || `assignment_${assignmentId}.pdf`;
      const localUri = FileSystem.documentDirectory + filename;
      
      const { uri } = await FileSystem.downloadAsync(fileUrl, localUri);
      
      if (await Sharing.isAvailableAsync()) {
        await Sharing.shareAsync(uri);
      } else {
        alert('Cannot share files on this device');
      }
    } catch (error) {
      alert(`Failed to download: ${error instanceof Error ? error.message : 'Unknown error'}`);
    }
  };

  const handlePreview = (assignment: any) => {
    alert('Preview functionality coming soon');
  };

  const handleAnalytics = async (assignment: any) => {
    setSelectedAssignmentForAnalytics(assignment);
    setAnalyticsModalVisible(true);
    setAnalyticsLoading(true);
    
    try {
      const data = await getAssignmentAnalytics(assignment.id);
      setAnalyticsData(data);
    } catch (error: any) {
      if (error.message === 'NOT_AUTHENTICATED') {
        router.replace('/login');
      } else {
        alert(error.message || 'Failed to load analytics');
      }
    } finally {
      setAnalyticsLoading(false);
    }
  };

  const handleDuplicate = async (assignment: any) => {
    Alert.alert(
      'Duplicate Assignment',
      `Are you sure you want to duplicate "${assignment.title}"?`,
      [
        { text: 'Cancel', style: 'cancel' },
        { 
          text: 'Duplicate', 
          onPress: async () => {
            try {
              const sessionData = await import('expo-secure-store').then(m => m.getItemAsync('teacherSession'));
              const session = JSON.parse(sessionData!);
              const sessionToken = session.session_token;

              const formData = new FormData();
              formData.append('title', `${assignment.title} (Copy)`);
              formData.append('description', assignment.description || '');
              formData.append('assignment_type', assignment.assignment_type);

              const response = await fetch('https://03e9-129-222-147-23.ngrok-free.app/kenyaeduhub/teachers/api/upload_assignment.php', {
                method: 'POST',
                headers: {
                  'Authorization': sessionToken,
                  'Content-Type': 'multipart/form-data',
                },
                body: formData,
              });

              const data = await response.json();
              
              if (data.success) {
                alert('Assignment duplicated successfully');
                await loadAssignments();
              } else {
                alert(data.error || 'Duplication failed');
              }
            } catch (error) {
              alert('Duplication failed');
            }
          }
        },
      ]
    );
  };

  const handleDelete = (assignment: any) => {
    Alert.alert(
      'Delete Assignment',
      `Are you sure you want to delete "${assignment.title}"? This action cannot be undone.`,
      [
        { text: 'Cancel', style: 'cancel' },
        { 
          text: 'Delete', 
          style: 'destructive',
          onPress: async () => {
            try {
              const sessionData = await import('expo-secure-store').then(m => m.getItemAsync('teacherSession'));
              const session = JSON.parse(sessionData!);
              const sessionToken = session.session_token;

              const formData = new FormData();
              formData.append('assignment_id', assignment.id);

              const response = await fetch('https://03e9-129-222-147-23.ngrok-free.app/kenyaeduhub/teachers/api/delete_assignment.php', {
                method: 'POST',
                headers: {
                  'Authorization': sessionToken,
                  'Content-Type': 'multipart/form-data',
                },
                body: formData,
              });

              const data = await response.json();
              
              if (data.success) {
                alert('Assignment deleted successfully');
                await loadAssignments();
              } else {
                alert(data.error || 'Deletion failed');
              }
            } catch (error) {
              alert('Deletion failed');
            }
          }
        },
      ]
    );
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
        style={styles.scrollView}
        contentContainerStyle={styles.content}
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
          <Text style={styles.welcomeText}>Assignments</Text>
          <Text style={styles.teacherName}>{assignmentsData?.teacher?.name || 'Teacher'}</Text>
          {assignmentsData?.teacher?.school_name && (
            <Text style={styles.schoolName}>{assignmentsData.teacher.school_name}</Text>
          )}
        </View>

        {/* Calendar Status */}
        {assignmentsData?.calendar_status && (
          <View style={styles.statusContainer}>
            <View style={styles.statusIconContainer}>
              <Ionicons 
                name={assignmentsData.calendar_status.is_holiday ? 'calendar-outline' : 
                      assignmentsData.calendar_status.school_status === 'break' ? 'time-outline' : 
                      'school-outline'} 
                size={24} 
                color="#FF6B35" 
              />
            </View>
            <View style={styles.statusTextContainer}>
              <Text style={styles.statusTitle}>
                {assignmentsData.calendar_status.is_holiday ? 'School is on Holiday' :
                 assignmentsData.calendar_status.school_status === 'break' ? 'School is on Break' :
                 'School is In Session'}
              </Text>
              {assignmentsData.calendar_status.current_holiday && (
                <Text style={styles.statusText}>
                  {assignmentsData.calendar_status.current_holiday.holiday_name}
                </Text>
              )}
              {assignmentsData.calendar_status.current_term && (
                <Text style={styles.statusText}>
                  Active Term: {assignmentsData.calendar_status.current_term.term_name}
                </Text>
              )}
            </View>
          </View>
        )}

        {/* Quick Actions */}
        <View style={styles.quickActionsSection}>
          <Text style={styles.quickActionsTitle}>Quick Actions</Text>
          <View style={styles.quickActionsGrid}>
            <TouchableOpacity 
              style={styles.quickActionButton}
              onPress={() => setUploadModalVisible(true)}
            >
              <View style={styles.quickActionIcon}>
                <Ionicons name="cloud-upload-outline" size={24} color="#FF6B35" />
              </View>
              <Text style={styles.quickActionText}>Upload</Text>
            </TouchableOpacity>
            
            <TouchableOpacity 
              style={styles.quickActionButton}
              onPress={() => setFilterModalVisible(true)}
            >
              <View style={styles.quickActionIcon}>
                <Ionicons name="filter-outline" size={24} color="#1967d2" />
              </View>
              <Text style={styles.quickActionText}>Filter</Text>
            </TouchableOpacity>
            
            <TouchableOpacity 
              style={styles.quickActionButton}
              onPress={() => setSortBy(sortBy === 'newest' ? 'oldest' : 'newest')}
            >
              <View style={styles.quickActionIcon}>
                <Ionicons name="swap-vertical-outline" size={24} color="#188038" />
              </View>
              <Text style={styles.quickActionText}>Sort</Text>
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

        {/* Assignments List */}
        <View style={styles.assignmentsContainer}>
          <View style={styles.sectionHeader}>
            <Text style={styles.sectionTitle}>Assignments</Text>
            {filterType !== 'all' && (
              <TouchableOpacity onPress={() => setFilterType('all')}>
                <Text style={styles.clearFilterText}>Clear Filter</Text>
              </TouchableOpacity>
            )}
          </View>
          {assignmentsData?.assignments && assignmentsData.assignments.length > 0 ? (
            assignmentsData.assignments
              .filter(assignment => filterType === 'all' || assignment.assignment_type === filterType)
              .sort((a, b) => {
                if (sortBy === 'newest') {
                  return new Date(b.created_at).getTime() - new Date(a.created_at).getTime();
                } else {
                  return new Date(a.created_at).getTime() - new Date(b.created_at).getTime();
                }
              })
              .map((assignment) => (
              <View key={assignment.id} style={styles.assignmentCard}>
                <View style={styles.assignmentCardHeader}>
                  <View style={styles.assignmentHeaderLeft}>
                    <Text style={styles.assignmentTitle}>{assignment.title}</Text>
                    <View style={[
                      styles.badge,
                      assignment.assignment_type === 'syllabus' ? styles.syllabusBadge :
                      assignment.assignment_type === 'sentiment' ? styles.sentimentBadge :
                      assignment.assignment_type === 'notes' ? styles.notesBadge :
                      styles.holidayBadge
                    ]}>
                      <Text style={styles.badgeText}>
                        {assignment.assignment_type.charAt(0).toUpperCase() + assignment.assignment_type.slice(1)}
                      </Text>
                    </View>
                  </View>
                </View>
                
                <Text style={styles.assignmentDescription}>
                  {assignment.description || 'No description provided'}
                </Text>
                
                <View style={styles.assignmentMeta}>
                  {assignment.class_name && (
                    <View style={styles.assignmentMetaItem}>
                      <Ionicons name="people-outline" size={12} color="#FF6B35" />
                      <Text style={styles.metaText}>{assignment.class_name}</Text>
                    </View>
                  )}
                  
                  {assignment.subject_name && (
                    <View style={styles.assignmentMetaItem}>
                      <Ionicons name="book-outline" size={12} color="#FF6B35" />
                      <Text style={styles.metaText}>{assignment.subject_name}</Text>
                    </View>
                  )}
                  
                  {assignment.due_date && (
                    <View style={styles.assignmentMetaItem}>
                      <Ionicons name="calendar-outline" size={12} color="#FF6B35" />
                      <Text style={styles.metaText}>Due: {new Date(assignment.due_date).toLocaleDateString()}</Text>
                    </View>
                  )}
                </View>
                
                <View style={styles.assignmentFooter}>
                  <Text style={styles.assignmentUploader}>{assignment.teacher_name}</Text>
                  <Text style={styles.assignmentDate}>
                    {new Date(assignment.created_at).toLocaleDateString()}
                  </Text>
                </View>
                
                <View style={styles.actionButtons}>
                  <TouchableOpacity style={styles.actionButtonDownload} onPress={() => handleDownload(assignment)}>
                    <Ionicons name="download-outline" size={16} color="#ffffff" />
                    <Text style={styles.actionButtonText}>Download</Text>
                  </TouchableOpacity>
                  <TouchableOpacity style={styles.actionButtonIcon} onPress={() => handlePreview(assignment)}>
                    <Ionicons name="eye-outline" size={18} color="#f57c00" />
                  </TouchableOpacity>
                  <TouchableOpacity style={styles.actionButtonIcon} onPress={() => handleAnalytics(assignment)}>
                    <Ionicons name="bar-chart-outline" size={18} color="#7b1fa2" />
                  </TouchableOpacity>
                  <TouchableOpacity style={styles.actionButtonIcon} onPress={() => handleEdit(assignment)}>
                    <Ionicons name="create-outline" size={18} color="#1967d2" />
                  </TouchableOpacity>
                  <TouchableOpacity style={styles.actionButtonIcon} onPress={() => handleDuplicate(assignment)}>
                    <Ionicons name="copy-outline" size={18} color="#137333" />
                  </TouchableOpacity>
                  <TouchableOpacity style={styles.actionButtonIcon} onPress={() => handleDelete(assignment)}>
                    <Ionicons name="trash-outline" size={18} color="#c5221f" />
                  </TouchableOpacity>
                </View>
              </View>
            ))
          ) : (
            <View style={styles.emptyState}>
              <Ionicons name="document-outline" size={48} color="#9aa0a6" />
              <Text style={styles.emptyText}>No assignments available</Text>
            </View>
          )}
        </View>

        {/* Quick Links */}
        <View style={styles.quickLinksSection}>
          <Text style={styles.quickLinksTitle}>Quick Links</Text>
          <View style={styles.quickLinksGrid}>
            <TouchableOpacity 
              style={styles.quickLinkButton}
              onPress={() => router.push('/(tabs)/attendance')}
            >
              <View style={styles.quickLinkIcon}>
                <Ionicons name="people-outline" size={24} color="#FF6B35" />
              </View>
              <Text style={styles.quickLinkText}>Attendance</Text>
            </TouchableOpacity>
            
            <TouchableOpacity 
              style={styles.quickLinkButton}
              onPress={() => router.push('/(tabs)/students')}
            >
              <View style={styles.quickLinkIcon}>
                <Ionicons name="person-outline" size={24} color="#1967d2" />
              </View>
              <Text style={styles.quickLinkText}>Students</Text>
            </TouchableOpacity>
            
            <TouchableOpacity 
              style={styles.quickLinkButton}
              onPress={() => router.push('/(tabs)/performance')}
            >
              <View style={styles.quickLinkIcon}>
                <Ionicons name="bar-chart-outline" size={24} color="#188038" />
              </View>
              <Text style={styles.quickLinkText}>Performance</Text>
            </TouchableOpacity>
            
            <TouchableOpacity 
              style={styles.quickLinkButton}
              onPress={() => router.push('/(tabs)/profile')}
            >
              <View style={styles.quickLinkIcon}>
                <Ionicons name="person-circle-outline" size={24} color="#f57c00" />
              </View>
              <Text style={styles.quickLinkText}>Profile</Text>
            </TouchableOpacity>
          </View>
        </View>
      </ScrollView>

      {/* Upload Modal */}
      <Modal
        visible={uploadModalVisible}
        animationType="slide"
        transparent={true}
        onRequestClose={() => setUploadModalVisible(false)}
      >
        <KeyboardAvoidingView 
          behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
          style={styles.modalOverlay}
        >
          <View style={styles.modalContent}>
            <View style={styles.modalHeader}>
              <Text style={styles.modalTitle}>Upload Assignment</Text>
              <TouchableOpacity onPress={() => setUploadModalVisible(false)}>
                <Ionicons name="close" size={24} color="#5f6368" />
              </TouchableOpacity>
            </View>

            <ScrollView 
              style={styles.modalScrollView}
              showsVerticalScrollIndicator={false}
              keyboardShouldPersistTaps="handled"
              contentContainerStyle={styles.modalScrollContent}
            >
              <View style={styles.formGroup}>
                <Text style={styles.formLabel}>Assignment Type *</Text>
                <View style={styles.typeSelector}>
                  {['syllabus', 'sentiment', 'notes', 'holiday'].map((type) => (
                    <TouchableOpacity
                      key={type}
                      style={[
                        styles.typeButton,
                        uploadType === type && styles.typeButtonActive,
                      ]}
                      onPress={() => setUploadType(type)}
                    >
                      <Text style={[
                        styles.typeButtonText,
                        uploadType === type && styles.typeButtonTextActive,
                      ]}>
                        {type.charAt(0).toUpperCase() + type.slice(1)}
                      </Text>
                    </TouchableOpacity>
                  ))}
                </View>
              </View>

              <View style={styles.formGroup}>
                <Text style={styles.formLabel}>Title *</Text>
                <TextInput
                  style={styles.formInput}
                  placeholder="Enter assignment title"
                  value={uploadTitle}
                  onChangeText={setUploadTitle}
                />
              </View>

              <View style={styles.formGroup}>
                <Text style={styles.formLabel}>Description</Text>
                <TextInput
                  style={[styles.formInput, styles.formInputMultiline]}
                  placeholder="Enter assignment description"
                  value={uploadDescription}
                  onChangeText={setUploadDescription}
                  multiline
                  numberOfLines={3}
                />
              </View>

              <View style={styles.formGroup}>
                <Text style={styles.formLabel}>Class (Optional)</Text>
                <TouchableOpacity 
                  style={styles.pickerButton}
                  onPress={() => setClassPickerVisible(true)}
                >
                  <Ionicons name="people-outline" size={20} color="#FF6B35" style={styles.pickerIcon} />
                  <Text style={styles.pickerText}>
                    {uploadClassId ? assignmentsData?.classes?.find(c => c.id === parseInt(uploadClassId))?.class_name || 'Selected Class' : 'Select a class'}
                  </Text>
                  <Ionicons name="chevron-down" size={20} color="#5f6368" />
                </TouchableOpacity>
              </View>

              <View style={styles.formGroup}>
                <Text style={styles.formLabel}>Subject (Optional)</Text>
                <TouchableOpacity 
                  style={styles.pickerButton}
                  onPress={() => setSubjectPickerVisible(true)}
                >
                  <Ionicons name="book-outline" size={20} color="#FF6B35" style={styles.pickerIcon} />
                  <Text style={styles.pickerText}>
                    {uploadSubjectId ? assignmentsData?.subjects?.find(s => s.id === parseInt(uploadSubjectId))?.subject_name || 'Selected Subject' : 'Select a subject'}
                  </Text>
                  <Ionicons name="chevron-down" size={20} color="#5f6368" />
                </TouchableOpacity>
              </View>

              <View style={styles.formGroup}>
                <Text style={styles.formLabel}>Due Date (Optional)</Text>
                <TouchableOpacity 
                  style={styles.pickerButton}
                  onPress={() => setUploadDatePickerVisible(true)}
                >
                  <Ionicons name="calendar-outline" size={20} color="#FF6B35" style={styles.pickerIcon} />
                  <Text style={styles.pickerText}>
                    {uploadDueDate ? uploadDueDate : 'Select due date'}
                  </Text>
                  <Ionicons name="chevron-down" size={20} color="#5f6368" />
                </TouchableOpacity>
              </View>

              <View style={styles.formGroup}>
                <Text style={styles.formLabel}>File *</Text>
                <TouchableOpacity style={styles.filePickerButton} onPress={handleFilePick}>
                  <Ionicons name="document-outline" size={20} color="#FF6B35" />
                  <Text style={styles.filePickerText}>
                    {uploadFile ? uploadFile.name : 'Select a file'}
                  </Text>
                </TouchableOpacity>
              </View>

              <TouchableOpacity
                style={[styles.uploadButton, uploading && styles.uploadButtonDisabled]}
                onPress={handleUpload}
                disabled={uploading}
              >
                {uploading ? (
                  <ActivityIndicator size="small" color="#ffffff" />
                ) : (
                  <>
                    <Ionicons name="cloud-upload-outline" size={20} color="#ffffff" />
                    <Text style={styles.uploadButtonText}>Upload Assignment</Text>
                  </>
                )}
              </TouchableOpacity>
            </ScrollView>
          </View>
        </KeyboardAvoidingView>
      </Modal>

      {/* Edit Modal */}
      <Modal
        visible={editModalVisible}
        animationType="slide"
        transparent={true}
        onRequestClose={() => setEditModalVisible(false)}
      >
        <KeyboardAvoidingView 
          behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
          style={styles.modalOverlay}
        >
          <View style={styles.modalContent}>
            <View style={styles.modalHeader}>
              <Text style={styles.modalTitle}>Edit Assignment</Text>
              <TouchableOpacity onPress={() => setEditModalVisible(false)}>
                <Ionicons name="close" size={24} color="#5f6368" />
              </TouchableOpacity>
            </View>

            <ScrollView 
              style={styles.modalScrollView}
              showsVerticalScrollIndicator={false}
              keyboardShouldPersistTaps="handled"
              contentContainerStyle={styles.modalScrollContent}
            >
              <View style={styles.formGroup}>
                <Text style={styles.formLabel}>Assignment Type *</Text>
                <View style={styles.typeSelector}>
                  {['syllabus', 'sentiment', 'notes', 'holiday'].map((type) => (
                    <TouchableOpacity
                      key={type}
                      style={[
                        styles.typeButton,
                        editType === type && styles.typeButtonActive,
                      ]}
                      onPress={() => setEditType(type)}
                    >
                      <Text style={[
                        styles.typeButtonText,
                        editType === type && styles.typeButtonTextActive,
                      ]}>
                        {type.charAt(0).toUpperCase() + type.slice(1)}
                      </Text>
                    </TouchableOpacity>
                  ))}
                </View>
              </View>

              <View style={styles.formGroup}>
                <Text style={styles.formLabel}>Title *</Text>
                <TextInput
                  style={styles.formInput}
                  placeholder="Enter assignment title"
                  value={editTitle}
                  onChangeText={setEditTitle}
                />
              </View>

              <View style={styles.formGroup}>
                <Text style={styles.formLabel}>Description</Text>
                <TextInput
                  style={[styles.formInput, styles.formInputMultiline]}
                  placeholder="Enter assignment description"
                  value={editDescription}
                  onChangeText={setEditDescription}
                  multiline
                  numberOfLines={3}
                />
              </View>

              <View style={styles.formGroup}>
                <Text style={styles.formLabel}>Class (Optional)</Text>
                <TouchableOpacity 
                  style={styles.pickerButton}
                  onPress={() => setClassPickerVisible(true)}
                >
                  <Ionicons name="people-outline" size={20} color="#FF6B35" style={styles.pickerIcon} />
                  <Text style={styles.pickerText}>
                    {editClassId ? assignmentsData?.classes?.find(c => c.id === parseInt(editClassId))?.class_name || 'Selected Class' : 'Select a class'}
                  </Text>
                  <Ionicons name="chevron-down" size={20} color="#5f6368" />
                </TouchableOpacity>
              </View>

              <View style={styles.formGroup}>
                <Text style={styles.formLabel}>Subject (Optional)</Text>
                <TouchableOpacity 
                  style={styles.pickerButton}
                  onPress={() => setSubjectPickerVisible(true)}
                >
                  <Ionicons name="book-outline" size={20} color="#FF6B35" style={styles.pickerIcon} />
                  <Text style={styles.pickerText}>
                    {editSubjectId ? assignmentsData?.subjects?.find(s => s.id === parseInt(editSubjectId))?.subject_name || 'Selected Subject' : 'Select a subject'}
                  </Text>
                  <Ionicons name="chevron-down" size={20} color="#5f6368" />
                </TouchableOpacity>
              </View>

              <View style={styles.formGroup}>
                <Text style={styles.formLabel}>Due Date (Optional)</Text>
                <TouchableOpacity 
                  style={styles.pickerButton}
                  onPress={() => setEditDatePickerVisible(true)}
                >
                  <Ionicons name="calendar-outline" size={20} color="#FF6B35" style={styles.pickerIcon} />
                  <Text style={styles.pickerText}>
                    {editDueDate ? editDueDate : 'Select due date'}
                  </Text>
                  <Ionicons name="chevron-down" size={20} color="#5f6368" />
                </TouchableOpacity>
              </View>

              <View style={styles.formGroup}>
                <Text style={styles.formLabel}>File (Optional)</Text>
                <TouchableOpacity style={styles.filePickerButton} onPress={handleEditFilePick}>
                  <Ionicons name="document-outline" size={20} color="#FF6B35" />
                  <Text style={styles.filePickerText}>
                    {editFile ? editFile.name : (editingAssignment?.file_name || 'Select a new file')}
                  </Text>
                </TouchableOpacity>
              </View>

              <TouchableOpacity
                style={[styles.uploadButton, updating && styles.uploadButtonDisabled]}
                onPress={handleUpdate}
                disabled={updating}
              >
                {updating ? (
                  <ActivityIndicator size="small" color="#ffffff" />
                ) : (
                  <>
                    <Ionicons name="save-outline" size={20} color="#ffffff" />
                    <Text style={styles.uploadButtonText}>Update Assignment</Text>
                  </>
                )}
              </TouchableOpacity>
            </ScrollView>
          </View>
        </KeyboardAvoidingView>
      </Modal>

      {/* Class Picker Modal */}
      <Modal
        visible={classPickerVisible}
        animationType="slide"
        transparent={true}
        onRequestClose={() => setClassPickerVisible(false)}
      >
        <KeyboardAvoidingView 
          behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
          style={styles.modalOverlay}
        >
          <View style={styles.modalContent}>
            <View style={styles.modalHeader}>
              <Text style={styles.modalTitle}>Select Class</Text>
              <TouchableOpacity onPress={() => setClassPickerVisible(false)}>
                <Ionicons name="close" size={24} color="#5f6368" />
              </TouchableOpacity>
            </View>
            <ScrollView style={styles.pickerList}>
              {assignmentsData?.classes?.map((classItem) => (
                <TouchableOpacity
                  key={classItem.id}
                  style={[
                    styles.pickerItem,
                    uploadClassId === String(classItem.id) || editClassId === String(classItem.id) ? styles.pickerItemSelected : null
                  ]}
                  onPress={() => {
                    setUploadClassId(String(classItem.id));
                    setEditClassId(String(classItem.id));
                    setClassPickerVisible(false);
                  }}
                >
                  <Text style={styles.pickerItemText}>{classItem.class_name}</Text>
                  {uploadClassId === String(classItem.id) || editClassId === String(classItem.id) && (
                    <Ionicons name="checkmark" size={20} color="#FF6B35" />
                  )}
                </TouchableOpacity>
              ))}
              {(!assignmentsData?.classes || assignmentsData.classes.length === 0) && (
                <Text style={styles.pickerEmptyText}>No classes available</Text>
              )}
            </ScrollView>
          </View>
        </KeyboardAvoidingView>
      </Modal>

      {/* Subject Picker Modal */}
      <Modal
        visible={subjectPickerVisible}
        animationType="slide"
        transparent={true}
        onRequestClose={() => setSubjectPickerVisible(false)}
      >
        <KeyboardAvoidingView 
          behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
          style={styles.modalOverlay}
        >
          <View style={styles.modalContent}>
            <View style={styles.modalHeader}>
              <Text style={styles.modalTitle}>Select Subject</Text>
              <TouchableOpacity onPress={() => setSubjectPickerVisible(false)}>
                <Ionicons name="close" size={24} color="#5f6368" />
              </TouchableOpacity>
            </View>
            <ScrollView style={styles.pickerList}>
              {assignmentsData?.subjects?.map((subject) => (
                <TouchableOpacity
                  key={subject.id}
                  style={[
                    styles.pickerItem,
                    uploadSubjectId === String(subject.id) || editSubjectId === String(subject.id) ? styles.pickerItemSelected : null
                  ]}
                  onPress={() => {
                    setUploadSubjectId(String(subject.id));
                    setEditSubjectId(String(subject.id));
                    setSubjectPickerVisible(false);
                  }}
                >
                  <Text style={styles.pickerItemText}>{subject.subject_name}</Text>
                  {uploadSubjectId === String(subject.id) || editSubjectId === String(subject.id) && (
                    <Ionicons name="checkmark" size={20} color="#FF6B35" />
                  )}
                </TouchableOpacity>
              ))}
              {(!assignmentsData?.subjects || assignmentsData.subjects.length === 0) && (
                <Text style={styles.pickerEmptyText}>No subjects available</Text>
              )}
            </ScrollView>
          </View>
        </KeyboardAvoidingView>
      </Modal>

      {/* Upload Date Picker Modal */}
      <Modal
        visible={uploadDatePickerVisible}
        animationType="slide"
        transparent={true}
        onRequestClose={() => setUploadDatePickerVisible(false)}
      >
        <View style={styles.modalOverlay}>
          <View style={styles.datePickerModal}>
            <View style={styles.datePickerHeader}>
              <Text style={styles.datePickerTitle}>Select Due Date</Text>
              <TouchableOpacity onPress={() => setUploadDatePickerVisible(false)}>
                <Ionicons name="close" size={24} color="#5f6368" />
              </TouchableOpacity>
            </View>
            <DateTimePicker
              value={uploadDueDate ? new Date(uploadDueDate) : new Date()}
              mode="date"
              display={Platform.OS === 'ios' ? 'compact' : 'default'}
              onChange={(event, selectedDate) => {
                if (selectedDate) {
                  const formattedDate = selectedDate.toISOString().split('T')[0];
                  setUploadDueDate(formattedDate);
                }
                if (Platform.OS === 'android') {
                  setUploadDatePickerVisible(false);
                }
              }}
              style={styles.datePicker}
            />
            {Platform.OS === 'ios' && (
              <TouchableOpacity 
                style={styles.datePickerButton}
                onPress={() => setUploadDatePickerVisible(false)}
              >
                <Text style={styles.datePickerButtonText}>Done</Text>
              </TouchableOpacity>
            )}
          </View>
        </View>
      </Modal>

      {/* Edit Date Picker Modal */}
      <Modal
        visible={editDatePickerVisible}
        animationType="slide"
        transparent={true}
        onRequestClose={() => setEditDatePickerVisible(false)}
      >
        <View style={styles.modalOverlay}>
          <View style={styles.datePickerModal}>
            <View style={styles.datePickerHeader}>
              <Text style={styles.datePickerTitle}>Select Due Date</Text>
              <TouchableOpacity onPress={() => setEditDatePickerVisible(false)}>
                <Ionicons name="close" size={24} color="#5f6368" />
              </TouchableOpacity>
            </View>
            <DateTimePicker
              value={editDueDate ? new Date(editDueDate) : new Date()}
              mode="date"
              display={Platform.OS === 'ios' ? 'compact' : 'default'}
              onChange={(event, selectedDate) => {
                if (selectedDate) {
                  const formattedDate = selectedDate.toISOString().split('T')[0];
                  setEditDueDate(formattedDate);
                }
                if (Platform.OS === 'android') {
                  setEditDatePickerVisible(false);
                }
              }}
              style={styles.datePicker}
            />
            {Platform.OS === 'ios' && (
              <TouchableOpacity 
                style={styles.datePickerButton}
                onPress={() => setEditDatePickerVisible(false)}
              >
                <Text style={styles.datePickerButtonText}>Done</Text>
              </TouchableOpacity>
            )}
          </View>
        </View>
      </Modal>

      {/* Filter Modal */}
      <Modal
        visible={filterModalVisible}
        animationType="slide"
        transparent={true}
        onRequestClose={() => setFilterModalVisible(false)}
      >
        <KeyboardAvoidingView 
          behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
          style={styles.modalOverlay}
        >
          <View style={styles.modalContent}>
            <View style={styles.modalHeader}>
              <Text style={styles.modalTitle}>Filter Assignments</Text>
              <TouchableOpacity onPress={() => setFilterModalVisible(false)}>
                <Ionicons name="close" size={24} color="#5f6368" />
              </TouchableOpacity>
            </View>

            <ScrollView 
              style={styles.modalScrollView}
              showsVerticalScrollIndicator={false}
              keyboardShouldPersistTaps="handled"
              contentContainerStyle={styles.modalScrollContent}
            >
              <TouchableOpacity 
                style={[styles.filterOption, filterType === 'all' && styles.filterOptionActive]}
                onPress={() => {
                  setFilterType('all');
                  setFilterModalVisible(false);
                }}
              >
                <View style={styles.filterOptionLeft}>
                  <Ionicons name="apps-outline" size={20} color={filterType === 'all' ? '#FF6B35' : '#5f6368'} />
                  <Text style={[styles.filterOptionText, filterType === 'all' && styles.filterOptionTextActive]}>All Assignments</Text>
                </View>
                {filterType === 'all' && <Ionicons name="checkmark" size={20} color="#FF6B35" />}
              </TouchableOpacity>

              <TouchableOpacity 
                style={[styles.filterOption, filterType === 'syllabus' && styles.filterOptionActive]}
                onPress={() => {
                  setFilterType('syllabus');
                  setFilterModalVisible(false);
                }}
              >
                <View style={styles.filterOptionLeft}>
                  <Ionicons name="book-outline" size={20} color={filterType === 'syllabus' ? '#FF6B35' : '#5f6368'} />
                  <Text style={[styles.filterOptionText, filterType === 'syllabus' && styles.filterOptionTextActive]}>Syllabus</Text>
                </View>
                {filterType === 'syllabus' && <Ionicons name="checkmark" size={20} color="#FF6B35" />}
              </TouchableOpacity>

              <TouchableOpacity 
                style={[styles.filterOption, filterType === 'sentiment' && styles.filterOptionActive]}
                onPress={() => {
                  setFilterType('sentiment');
                  setFilterModalVisible(false);
                }}
              >
                <View style={styles.filterOptionLeft}>
                  <Ionicons name="heart-outline" size={20} color={filterType === 'sentiment' ? '#FF6B35' : '#5f6368'} />
                  <Text style={[styles.filterOptionText, filterType === 'sentiment' && styles.filterOptionTextActive]}>Sentiment</Text>
                </View>
                {filterType === 'sentiment' && <Ionicons name="checkmark" size={20} color="#FF6B35" />}
              </TouchableOpacity>

              <TouchableOpacity 
                style={[styles.filterOption, filterType === 'notes' && styles.filterOptionActive]}
                onPress={() => {
                  setFilterType('notes');
                  setFilterModalVisible(false);
                }}
              >
                <View style={styles.filterOptionLeft}>
                  <Ionicons name="document-text-outline" size={20} color={filterType === 'notes' ? '#FF6B35' : '#5f6368'} />
                  <Text style={[styles.filterOptionText, filterType === 'notes' && styles.filterOptionTextActive]}>Notes</Text>
                </View>
                {filterType === 'notes' && <Ionicons name="checkmark" size={20} color="#FF6B35" />}
              </TouchableOpacity>

              <TouchableOpacity 
                style={[styles.filterOption, filterType === 'holiday' && styles.filterOptionActive]}
                onPress={() => {
                  setFilterType('holiday');
                  setFilterModalVisible(false);
                }}
              >
                <View style={styles.filterOptionLeft}>
                  <Ionicons name="sunny-outline" size={20} color={filterType === 'holiday' ? '#FF6B35' : '#5f6368'} />
                  <Text style={[styles.filterOptionText, filterType === 'holiday' && styles.filterOptionTextActive]}>Holiday</Text>
                </View>
                {filterType === 'holiday' && <Ionicons name="checkmark" size={20} color="#FF6B35" />}
              </TouchableOpacity>
            </ScrollView>
          </View>
        </KeyboardAvoidingView>
      </Modal>

      {/* Analytics Modal */}
      <Modal
        visible={analyticsModalVisible}
        animationType="slide"
        transparent={true}
        onRequestClose={() => setAnalyticsModalVisible(false)}
      >
        <KeyboardAvoidingView 
          behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
          style={styles.modalOverlay}
        >
          <View style={styles.modalContent}>
            <View style={styles.modalHeader}>
              <Text style={styles.modalTitle}>Assignment Analytics</Text>
              <TouchableOpacity onPress={() => setAnalyticsModalVisible(false)}>
                <Ionicons name="close" size={24} color="#5f6368" />
              </TouchableOpacity>
            </View>

            {analyticsLoading ? (
              <View style={styles.analyticsLoadingContainer}>
                <ActivityIndicator size="large" color="#FF6B35" />
                <Text style={styles.analyticsLoadingText}>Loading analytics...</Text>
              </View>
            ) : analyticsData ? (
              <ScrollView 
                style={styles.modalScrollView}
                showsVerticalScrollIndicator={false}
                keyboardShouldPersistTaps="handled"
                contentContainerStyle={styles.modalScrollContent}
              >
                <View style={styles.analyticsCard}>
                  <Text style={styles.analyticsCardTitle}>{selectedAssignmentForAnalytics?.title}</Text>
                  <Text style={styles.analyticsCardSubtitle}>
                    {selectedAssignmentForAnalytics?.assignment_type?.charAt(0).toUpperCase() + selectedAssignmentForAnalytics?.assignment_type?.slice(1)}
                  </Text>
                </View>

                <View style={styles.statsGrid}>
                  <View style={styles.statCard}>
                    <Ionicons name="download-outline" size={32} color="#FF6B35" />
                    <Text style={styles.statValue}>{analyticsData.total_downloads}</Text>
                    <Text style={styles.statLabel}>Total Downloads</Text>
                  </View>
                  <View style={styles.statCard}>
                    <Ionicons name="people-outline" size={32} color="#FF6B35" />
                    <Text style={styles.statValue}>{analyticsData.unique_downloaders}</Text>
                    <Text style={styles.statLabel}>Unique Users</Text>
                  </View>
                </View>

                {analyticsData.last_download && (
                  <View style={styles.lastDownloadCard}>
                    <Ionicons name="time-outline" size={20} color="#5f6368" />
                    <Text style={styles.lastDownloadText}>
                      Last download: {analyticsData.last_download}
                    </Text>
                  </View>
                )}

                <View style={styles.downloadsSection}>
                  <Text style={styles.downloadsSectionTitle}>Download History</Text>
                  {analyticsData.downloads.length > 0 ? (
                    analyticsData.downloads.map((download, index) => (
                      <View key={index} style={styles.downloadItem}>
                        <View style={styles.downloadItemLeft}>
                          <View style={styles.userTypeBadge}>
                            <Text style={styles.userTypeText}>{download.user_type}</Text>
                          </View>
                          <Text style={styles.downloadUserName}>{download.full_name}</Text>
                        </View>
                        <Text style={styles.downloadDate}>{download.download_date}</Text>
                      </View>
                    ))
                  ) : (
                    <View style={styles.emptyDownloads}>
                      <Ionicons name="document-outline" size={48} color="#9aa0a6" />
                      <Text style={styles.emptyDownloadsText}>No downloads yet</Text>
                    </View>
                  )}
                </View>
              </ScrollView>
            ) : (
              <View style={styles.analyticsErrorContainer}>
                <Ionicons name="alert-circle-outline" size={48} color="#c5221f" />
                <Text style={styles.analyticsErrorText}>Failed to load analytics</Text>
              </View>
            )}
          </View>
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
  content: {
    padding: 16,
    paddingBottom: 100,
  },
  scrollView: {
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
  assignmentsContainer: {
    paddingBottom: 100,
  },
  sectionTitle: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#202124',
    marginBottom: 16,
  },
  assignmentCard: {
    backgroundColor: '#f8f9fa',
    borderWidth: 1,
    borderColor: '#e8eaed',
    borderRadius: 8,
    padding: 24,
    marginBottom: 24,
  },
  cardScroll: {
    flex: 1,
  },
  assignmentCardHeader: {
    marginBottom: 12,
  },
  assignmentHeaderLeft: {
    flexDirection: 'column',
    gap: 8,
  },
  assignmentTitle: {
    fontSize: 16,
    fontWeight: '500',
    color: '#202124',
  },
  badge: {
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 12,
    alignSelf: 'flex-start',
  },
  syllabusBadge: {
    backgroundColor: '#e8f0fe',
  },
  sentimentBadge: {
    backgroundColor: '#fce8e6',
  },
  notesBadge: {
    backgroundColor: '#e6f4ea',
  },
  holidayBadge: {
    backgroundColor: '#fef7e0',
  },
  badgeText: {
    fontSize: 11,
    fontWeight: '500',
    color: '#202124',
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
    gap: 12,
    marginBottom: 12,
  },
  assignmentMetaItem: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
  },
  metaText: {
    fontSize: 12,
    color: '#5f6368',
  },
  assignmentFooter: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 12,
  },
  assignmentUploader: {
    fontSize: 12,
    color: '#5f6368',
  },
  assignmentDate: {
    fontSize: 12,
    color: '#5f6368',
  },
  actionButtons: {
    flexDirection: 'row',
    gap: 8,
  },
  actionButtonDownload: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    flex: 1,
    backgroundColor: '#FF6B35',
    paddingHorizontal: 20,
    paddingVertical: 10,
    borderRadius: 25,
  },
  actionButtonText: {
    color: '#ffffff',
    fontSize: 13,
    fontWeight: '500',
  },
  actionButtonIcon: {
    width: 40,
    height: 40,
    borderRadius: 20,
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: '#f8f9fa',
    borderWidth: 1,
    borderColor: '#e8eaed',
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
    padding: 24,
    width: '90%',
    maxHeight: '90%',
  },
  modalScrollView: {
    maxHeight: '100%',
  },
  modalScrollContent: {
    paddingBottom: 100,
  },
  modalHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 24,
  },
  modalTitle: {
    fontSize: 20,
    fontWeight: '600',
    color: '#202124',
  },
  formGroup: {
    marginBottom: 20,
  },
  formLabel: {
    fontSize: 14,
    fontWeight: '500',
    color: '#5f6368',
    marginBottom: 8,
  },
  formInput: {
    borderWidth: 1,
    borderColor: '#dadce0',
    borderRadius: 8,
    paddingHorizontal: 16,
    paddingVertical: 12,
    fontSize: 14,
    color: '#202124',
  },
  formInputMultiline: {
    height: 80,
    textAlignVertical: 'top',
  },
  typeSelector: {
    flexDirection: 'row',
    gap: 8,
  },
  typeButton: {
    flex: 1,
    paddingVertical: 10,
    paddingHorizontal: 12,
    borderRadius: 8,
    borderWidth: 1,
    borderColor: '#dadce0',
    backgroundColor: '#ffffff',
  },
  typeButtonActive: {
    backgroundColor: '#FF6B35',
    borderColor: '#FF6B35',
  },
  typeButtonText: {
    fontSize: 12,
    fontWeight: '500',
    color: '#5f6368',
    textAlign: 'center',
  },
  typeButtonTextActive: {
    color: '#ffffff',
  },
  filePickerButton: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    borderWidth: 1,
    borderColor: '#dadce0',
    borderRadius: 8,
    paddingVertical: 12,
    backgroundColor: '#ffffff',
  },
  filePickerText: {
    fontSize: 14,
    color: '#5f6368',
  },
  uploadButton: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    backgroundColor: '#FF6B35',
    paddingHorizontal: 24,
    paddingVertical: 12,
    borderRadius: 25,
  },
  uploadButtonText: {
    color: '#ffffff',
    fontSize: 14,
    fontWeight: '500',
  },
  uploadButtonDisabled: {
    backgroundColor: '#ccc',
  },
  pickerButton: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    borderWidth: 1,
    borderColor: '#dadce0',
    borderRadius: 8,
    paddingHorizontal: 16,
    paddingVertical: 12,
    backgroundColor: '#ffffff',
  },
  pickerIcon: {
    marginRight: 12,
  },
  pickerText: {
    flex: 1,
    fontSize: 14,
    color: '#202124',
  },
  pickerList: {
    maxHeight: 300,
  },
  pickerItem: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingVertical: 16,
    paddingHorizontal: 16,
    borderBottomWidth: 1,
    borderBottomColor: '#e8eaed',
  },
  pickerItemSelected: {
    backgroundColor: '#fff3e0',
  },
  pickerItemText: {
    fontSize: 16,
    color: '#202124',
  },
  pickerEmptyText: {
    fontSize: 14,
    color: '#5f6368',
    textAlign: 'center',
    paddingVertical: 32,
  },
  emptyState: {
    alignItems: 'center',
    padding: 48,
  },
  emptyText: {
    fontSize: 14,
    color: '#5f6368',
    marginTop: 16,
  },
  analyticsLoadingContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    paddingVertical: 48,
  },
  analyticsLoadingText: {
    fontSize: 16,
    color: '#5f6368',
    marginTop: 16,
  },
  analyticsErrorContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    paddingVertical: 48,
  },
  analyticsErrorText: {
    fontSize: 16,
    color: '#c5221f',
    marginTop: 16,
  },
  analyticsCard: {
    backgroundColor: '#f8f9fa',
    borderRadius: 12,
    padding: 20,
    marginBottom: 24,
  },
  analyticsCardTitle: {
    fontSize: 18,
    fontWeight: '600',
    color: '#202124',
    marginBottom: 4,
  },
  analyticsCardSubtitle: {
    fontSize: 14,
    color: '#5f6368',
  },
  statsGrid: {
    flexDirection: 'row',
    gap: 16,
    marginBottom: 24,
  },
  statCard: {
    flex: 1,
    backgroundColor: '#f8f9fa',
    borderRadius: 12,
    padding: 20,
    alignItems: 'center',
  },
  statValue: {
    fontSize: 32,
    fontWeight: '700',
    color: '#FF6B35',
    marginTop: 8,
    marginBottom: 4,
  },
  statLabel: {
    fontSize: 12,
    color: '#5f6368',
    textAlign: 'center',
  },
  lastDownloadCard: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#e8f0fe',
    borderRadius: 8,
    padding: 12,
    marginBottom: 24,
  },
  lastDownloadText: {
    fontSize: 14,
    color: '#1967d2',
    marginLeft: 8,
  },
  downloadsSection: {
    marginBottom: 24,
  },
  downloadsSectionTitle: {
    fontSize: 16,
    fontWeight: '600',
    color: '#202124',
    marginBottom: 16,
  },
  downloadItem: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingVertical: 12,
    borderBottomWidth: 1,
    borderBottomColor: '#e8eaed',
  },
  downloadItemLeft: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
  },
  userTypeBadge: {
    backgroundColor: '#e8f0fe',
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 12,
  },
  userTypeText: {
    fontSize: 11,
    fontWeight: '500',
    color: '#1967d2',
  },
  downloadUserName: {
    fontSize: 14,
    color: '#202124',
  },
  downloadDate: {
    fontSize: 12,
    color: '#5f6368',
  },
  emptyDownloads: {
    alignItems: 'center',
    paddingVertical: 32,
  },
  emptyDownloadsText: {
    fontSize: 14,
    color: '#5f6368',
    marginTop: 16,
  },
  datePickerModal: {
    backgroundColor: '#ffffff',
    borderRadius: 16,
    padding: 24,
    width: '90%',
    maxHeight: '80%',
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
    width: '100%',
    height: 200,
    marginBottom: 16,
  },
  datePickerButton: {
    backgroundColor: '#FF6B35',
    borderRadius: 8,
    paddingVertical: 12,
    alignItems: 'center',
  },
  datePickerButtonText: {
    color: '#ffffff',
    fontSize: 16,
    fontWeight: '600',
  },
  quickActionsSection: {
    marginBottom: 24,
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
  sectionHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 16,
  },
  clearFilterText: {
    fontSize: 14,
    color: '#1967d2',
    fontWeight: '500',
  },
  filterOption: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingVertical: 16,
    paddingHorizontal: 16,
    borderBottomWidth: 1,
    borderBottomColor: '#e8eaed',
    backgroundColor: '#ffffff',
  },
  filterOptionActive: {
    backgroundColor: '#fff3e0',
  },
  filterOptionLeft: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
  },
  filterOptionText: {
    fontSize: 16,
    color: '#202124',
  },
  filterOptionTextActive: {
    fontWeight: '600',
    color: '#FF6B35',
  },
  quickLinksSection: {
    marginBottom: 16,
    padding: 16,
    paddingBottom: 0,
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
    padding: 20,
    alignItems: 'center',
    borderWidth: 1,
    borderColor: '#e8eaed',
  },
  quickLinkIcon: {
    width: 56,
    height: 56,
    borderRadius: 28,
    backgroundColor: '#ffffff',
    justifyContent: 'center',
    alignItems: 'center',
    marginBottom: 12,
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
