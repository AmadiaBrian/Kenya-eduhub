import { View, Text, StyleSheet, TouchableOpacity, ActivityIndicator, ScrollView, TextInput, Modal, Alert } from 'react-native';
import { useRouter } from 'expo-router';
import { useState, useEffect } from 'react';
import { logout, getTeacherProfile, updateTeacherProfile, changePassword, TeacherProfile, Subject } from '../../lib/api';
import * as SecureStore from 'expo-secure-store';
import { Ionicons } from '@expo/vector-icons';

export default function Profile() {
  const router = useRouter();
  const [teacherData, setTeacherData] = useState<TeacherProfile | null>(null);
  const [subjects, setSubjects] = useState<Subject[]>([]);
  const [loading, setLoading] = useState(false);
  const [loggingOut, setLoggingOut] = useState(false);
  const [editModalVisible, setEditModalVisible] = useState(false);
  const [passwordModalVisible, setPasswordModalVisible] = useState(false);
  const [saving, setSaving] = useState(false);
  
  // Form state
  const [firstName, setFirstName] = useState('');
  const [lastName, setLastName] = useState('');
  const [email, setEmail] = useState('');
  const [phone, setPhone] = useState('');
  const [idNumber, setIdNumber] = useState('');
  const [address, setAddress] = useState('');
  const [subject, setSubject] = useState('');
  
  // Password form state
  const [currentPassword, setCurrentPassword] = useState('');
  const [newPassword, setNewPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');

  useEffect(() => {
    loadProfileData();
  }, []);

  const loadProfileData = async () => {
    setLoading(true);
    try {
      const data = await getTeacherProfile();
      if (data.teacher) {
        setTeacherData(data.teacher);
        setFirstName(data.teacher.first_name);
        setLastName(data.teacher.last_name);
        setEmail(data.teacher.email);
        setPhone(data.teacher.phone);
        setIdNumber(data.teacher.id_number);
        setAddress(data.teacher.address);
        setSubject(data.teacher.subject);
      }
      if (data.subjects) {
        setSubjects(data.subjects);
      }
    } catch (error: any) {
      if (error.message === 'NOT_AUTHENTICATED') {
        router.replace('/login');
      } else {
        Alert.alert('Error', 'Failed to load profile data');
      }
    } finally {
      setLoading(false);
    }
  };

  const handleEditProfile = () => {
    if (teacherData) {
      setFirstName(teacherData.first_name);
      setLastName(teacherData.last_name);
      setEmail(teacherData.email);
      setPhone(teacherData.phone);
      setIdNumber(teacherData.id_number);
      setAddress(teacherData.address);
      setSubject(teacherData.subject);
      setEditModalVisible(true);
    }
  };

  const handleSaveProfile = async () => {
    if (!firstName || !lastName || !email || !phone || !idNumber) {
      Alert.alert('Error', 'Please fill in all required fields');
      return;
    }

    if (!email.includes('@') || !email.includes('.')) {
      Alert.alert('Error', 'Please enter a valid email');
      return;
    }

    setSaving(true);
    try {
      const result = await updateTeacherProfile({
        first_name: firstName,
        last_name: lastName,
        email,
        phone,
        id_number: idNumber,
        address,
        subject
      });
      Alert.alert('Success', result.message || 'Profile updated successfully');
      setEditModalVisible(false);
      loadProfileData();
    } catch (error: any) {
      if (error.message === 'NOT_AUTHENTICATED') {
        router.replace('/login');
      } else {
        Alert.alert('Error', error.message || 'Failed to update profile');
      }
    } finally {
      setSaving(false);
    }
  };

  const handleChangePassword = () => {
    setCurrentPassword('');
    setNewPassword('');
    setConfirmPassword('');
    setPasswordModalVisible(true);
  };

  const handleSavePassword = async () => {
    if (!currentPassword || !newPassword || !confirmPassword) {
      Alert.alert('Error', 'Please fill in all password fields');
      return;
    }

    if (newPassword.length < 8) {
      Alert.alert('Error', 'New password must be at least 8 characters');
      return;
    }

    if (newPassword !== confirmPassword) {
      Alert.alert('Error', 'Passwords do not match');
      return;
    }

    setSaving(true);
    try {
      const result = await changePassword({
        current_password: currentPassword,
        new_password: newPassword,
        confirm_password: confirmPassword
      });
      Alert.alert('Success', result.message || 'Password changed successfully');
      setPasswordModalVisible(false);
    } catch (error: any) {
      if (error.message === 'NOT_AUTHENTICATED') {
        router.replace('/login');
      } else {
        Alert.alert('Error', error.message || 'Failed to change password');
      }
    } finally {
      setSaving(false);
    }
  };

  const handleLogout = async () => {
    try {
      setLoggingOut(true);
      await logout();
      await SecureStore.deleteItemAsync('teacherSession');
      router.replace('/login');
    } catch (err) {
      console.error('Logout error:', err);
      // Even if API fails, clear local session
      await SecureStore.deleteItemAsync('teacherSession');
      router.replace('/login');
    } finally {
      setLoggingOut(false);
    }
  };

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
          <Text style={styles.welcomeText}>Profile</Text>
          <Text style={styles.subtitle}>Manage your account settings</Text>
        </View>
        {/* Profile Info Card */}
        <View style={styles.profileCard}>
          <View style={styles.profileIcon}>
            <Ionicons name="person" size={48} color="#FF6B35" />
          </View>
          {teacherData && (
            <View style={styles.profileInfo}>
              <Text style={styles.profileName}>{teacherData.first_name} {teacherData.last_name}</Text>
              <Text style={styles.profileEmail}>{teacherData.email || 'N/A'}</Text>
              {teacherData.school_name && (
                <Text style={styles.profileSchool}>{teacherData.school_name}</Text>
              )}
            </View>
          )}
        </View>

        {/* Quick Actions */}
        <View style={styles.quickActionsSection}>
          <Text style={styles.quickActionsTitle}>Quick Actions</Text>
          <View style={styles.quickActionsGrid}>
            <TouchableOpacity 
              style={styles.quickActionButton}
              onPress={() => handleEditProfile()}
            >
              <View style={styles.quickActionIcon}>
                <Ionicons name="create-outline" size={24} color="#FF6B35" />
              </View>
              <Text style={styles.quickActionText}>Edit Profile</Text>
            </TouchableOpacity>
            
            <TouchableOpacity 
              style={styles.quickActionButton}
              onPress={() => handleChangePassword()}
            >
              <View style={styles.quickActionIcon}>
                <Ionicons name="lock-closed-outline" size={24} color="#1967d2" />
              </View>
              <Text style={styles.quickActionText}>Password</Text>
            </TouchableOpacity>
            
            <TouchableOpacity 
              style={styles.quickActionButton}
              onPress={() => {/* Navigate to notifications */}}
            >
              <View style={styles.quickActionIcon}>
                <Ionicons name="notifications-outline" size={24} color="#188038" />
              </View>
              <Text style={styles.quickActionText}>Notifications</Text>
            </TouchableOpacity>
            
            <TouchableOpacity 
              style={styles.quickActionButton}
              onPress={() => {/* Navigate to help */}}
            >
              <View style={styles.quickActionIcon}>
                <Ionicons name="help-circle-outline" size={24} color="#f57c00" />
              </View>
              <Text style={styles.quickActionText}>Help</Text>
            </TouchableOpacity>
          </View>
        </View>

        {/* Quick Links */}
        <View style={styles.quickLinksSection}>
          <Text style={styles.quickLinksTitle}>Quick Links</Text>
          <View style={styles.quickLinksGrid}>
            <TouchableOpacity 
              style={styles.quickLinkButton}
              onPress={() => router.push('/(tabs)/dashboard')}
            >
              <View style={styles.quickLinkIcon}>
                <Ionicons name="home-outline" size={24} color="#FF6B35" />
              </View>
              <Text style={styles.quickLinkText}>Dashboard</Text>
            </TouchableOpacity>
            
            <TouchableOpacity 
              style={styles.quickLinkButton}
              onPress={() => router.push('/(tabs)/assignments')}
            >
              <View style={styles.quickLinkIcon}>
                <Ionicons name="book-outline" size={24} color="#1967d2" />
              </View>
              <Text style={styles.quickLinkText}>Assignments</Text>
            </TouchableOpacity>
            
            <TouchableOpacity 
              style={styles.quickLinkButton}
              onPress={() => router.push('/(tabs)/students')}
            >
              <View style={styles.quickLinkIcon}>
                <Ionicons name="people-outline" size={24} color="#188038" />
              </View>
              <Text style={styles.quickLinkText}>Students</Text>
            </TouchableOpacity>
            
            <TouchableOpacity 
              style={styles.quickLinkButton}
              onPress={() => router.push('/(tabs)/attendance')}
            >
              <View style={styles.quickLinkIcon}>
                <Ionicons name="checkmark-circle-outline" size={24} color="#f57c00" />
              </View>
              <Text style={styles.quickLinkText}>Attendance</Text>
            </TouchableOpacity>
          </View>
        </View>

        {/* Logout Button */}
        <TouchableOpacity style={styles.logoutButton} onPress={handleLogout} disabled={loggingOut}>
          {loggingOut ? (
            <ActivityIndicator color="#ffffff" />
          ) : (
            <>
              <Ionicons name="log-out-outline" size={20} color="#ffffff" />
              <Text style={styles.logoutText}>Logout</Text>
            </>
          )}
        </TouchableOpacity>
      </ScrollView>

      {/* Edit Profile Modal */}
      <Modal
        visible={editModalVisible}
        animationType="slide"
        transparent={true}
        onRequestClose={() => setEditModalVisible(false)}
      >
        <View style={styles.modalOverlay}>
          <View style={styles.modalContent}>
            <View style={styles.modalHeader}>
              <Text style={styles.modalTitle}>Edit Profile</Text>
              <TouchableOpacity onPress={() => setEditModalVisible(false)}>
                <Ionicons name="close" size={24} color="#5f6368" />
              </TouchableOpacity>
            </View>
            
            <ScrollView style={styles.modalBody}>
              <View style={styles.formGroup}>
                <Text style={styles.formLabel}>First Name *</Text>
                <TextInput
                  style={styles.formInput}
                  value={firstName}
                  onChangeText={setFirstName}
                  placeholder="Enter first name"
                />
              </View>
              
              <View style={styles.formGroup}>
                <Text style={styles.formLabel}>Last Name *</Text>
                <TextInput
                  style={styles.formInput}
                  value={lastName}
                  onChangeText={setLastName}
                  placeholder="Enter last name"
                />
              </View>
              
              <View style={styles.formGroup}>
                <Text style={styles.formLabel}>Email *</Text>
                <TextInput
                  style={styles.formInput}
                  value={email}
                  onChangeText={setEmail}
                  placeholder="Enter email"
                  keyboardType="email-address"
                  autoCapitalize="none"
                />
              </View>
              
              <View style={styles.formGroup}>
                <Text style={styles.formLabel}>Phone *</Text>
                <TextInput
                  style={styles.formInput}
                  value={phone}
                  onChangeText={setPhone}
                  placeholder="Enter phone number"
                  keyboardType="phone-pad"
                />
              </View>
              
              <View style={styles.formGroup}>
                <Text style={styles.formLabel}>ID Number *</Text>
                <TextInput
                  style={styles.formInput}
                  value={idNumber}
                  onChangeText={setIdNumber}
                  placeholder="Enter ID number"
                />
              </View>
              
              <View style={styles.formGroup}>
                <Text style={styles.formLabel}>Subject</Text>
                <TextInput
                  style={styles.formInput}
                  value={subject}
                  onChangeText={setSubject}
                  placeholder="Enter subject"
                />
              </View>
              
              <View style={styles.formGroup}>
                <Text style={styles.formLabel}>Address</Text>
                <TextInput
                  style={[styles.formInput, styles.formInputMultiline]}
                  value={address}
                  onChangeText={setAddress}
                  placeholder="Enter address"
                  multiline
                  numberOfLines={3}
                />
              </View>
              
              {teacherData && (
                <View style={styles.formGroup}>
                  <Text style={styles.formLabel}>School</Text>
                  <TextInput
                    style={[styles.formInput, styles.formInputDisabled]}
                    value={teacherData.school_name}
                    editable={false}
                  />
                </View>
              )}
              
              {teacherData && (
                <View style={styles.formGroup}>
                  <Text style={styles.formLabel}>Teacher Type</Text>
                  <TextInput
                    style={[styles.formInput, styles.formInputDisabled]}
                    value={teacherData.teacher_type.replace('_', ' ').replace(/\b\w/g, c => c.toUpperCase())}
                    editable={false}
                  />
                </View>
              )}
            </ScrollView>
            
            <View style={styles.modalFooter}>
              <TouchableOpacity 
                style={[styles.modalButton, styles.modalButtonCancel]}
                onPress={() => setEditModalVisible(false)}
              >
                <Text style={styles.modalButtonTextCancel}>Cancel</Text>
              </TouchableOpacity>
              <TouchableOpacity 
                style={[styles.modalButton, styles.modalButtonSave]}
                onPress={handleSaveProfile}
                disabled={saving}
              >
                {saving ? (
                  <ActivityIndicator color="#ffffff" />
                ) : (
                  <Text style={styles.modalButtonTextSave}>Save</Text>
                )}
              </TouchableOpacity>
            </View>
          </View>
        </View>
      </Modal>

      {/* Change Password Modal */}
      <Modal
        visible={passwordModalVisible}
        animationType="slide"
        transparent={true}
        onRequestClose={() => setPasswordModalVisible(false)}
      >
        <View style={styles.modalOverlay}>
          <View style={styles.modalContent}>
            <View style={styles.modalHeader}>
              <Text style={styles.modalTitle}>Change Password</Text>
              <TouchableOpacity onPress={() => setPasswordModalVisible(false)}>
                <Ionicons name="close" size={24} color="#5f6368" />
              </TouchableOpacity>
            </View>
            
            <ScrollView style={styles.modalBody}>
              <View style={styles.formGroup}>
                <Text style={styles.formLabel}>Current Password *</Text>
                <TextInput
                  style={styles.formInput}
                  value={currentPassword}
                  onChangeText={setCurrentPassword}
                  placeholder="Enter current password"
                  secureTextEntry
                />
              </View>
              
              <View style={styles.formGroup}>
                <Text style={styles.formLabel}>New Password *</Text>
                <TextInput
                  style={styles.formInput}
                  value={newPassword}
                  onChangeText={setNewPassword}
                  placeholder="Enter new password (min 8 characters)"
                  secureTextEntry
                />
              </View>
              
              <View style={styles.formGroup}>
                <Text style={styles.formLabel}>Confirm New Password *</Text>
                <TextInput
                  style={styles.formInput}
                  value={confirmPassword}
                  onChangeText={setConfirmPassword}
                  placeholder="Confirm new password"
                  secureTextEntry
                />
              </View>
              
              <View style={styles.passwordInfo}>
                <Ionicons name="information-circle" size={16} color="#5f6368" />
                <Text style={styles.passwordInfoText}>Password must be at least 8 characters long</Text>
              </View>
            </ScrollView>
            
            <View style={styles.modalFooter}>
              <TouchableOpacity 
                style={[styles.modalButton, styles.modalButtonCancel]}
                onPress={() => setPasswordModalVisible(false)}
              >
                <Text style={styles.modalButtonTextCancel}>Cancel</Text>
              </TouchableOpacity>
              <TouchableOpacity 
                style={[styles.modalButton, styles.modalButtonSave]}
                onPress={handleSavePassword}
                disabled={saving}
              >
                {saving ? (
                  <ActivityIndicator color="#ffffff" />
                ) : (
                  <Text style={styles.modalButtonTextSave}>Change Password</Text>
                )}
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
  profileCard: {
    backgroundColor: '#f8f9fa',
    borderRadius: 12,
    padding: 24,
    alignItems: 'center',
    marginBottom: 24,
    borderWidth: 1,
    borderColor: '#e8eaed',
    marginHorizontal: 16,
  },
  profileIcon: {
    width: 80,
    height: 80,
    borderRadius: 40,
    backgroundColor: '#ffffff',
    justifyContent: 'center',
    alignItems: 'center',
    marginBottom: 16,
    borderWidth: 2,
    borderColor: '#FF6B35',
  },
  profileInfo: {
    alignItems: 'center',
  },
  profileName: {
    fontSize: 20,
    fontWeight: '600',
    color: '#202124',
    marginBottom: 4,
  },
  profileEmail: {
    fontSize: 14,
    color: '#5f6368',
    marginBottom: 4,
  },
  profileSchool: {
    fontSize: 12,
    color: '#9aa0a6',
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
  logoutButton: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    backgroundColor: '#FF6B35',
    paddingHorizontal: 32,
    paddingVertical: 12,
    borderRadius: 25,
    marginBottom: 100,
    marginHorizontal: 16,
  },
  logoutText: {
    color: '#ffffff',
    fontSize: 16,
    fontWeight: '600',
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
    paddingHorizontal: 20,
    paddingVertical: 16,
    borderBottomWidth: 1,
    borderBottomColor: '#e8eaed',
  },
  modalTitle: {
    fontSize: 18,
    fontWeight: '600',
    color: '#202124',
  },
  modalBody: {
    paddingHorizontal: 20,
    paddingVertical: 16,
  },
  formGroup: {
    marginBottom: 16,
  },
  formLabel: {
    fontSize: 14,
    fontWeight: '500',
    color: '#5f6368',
    marginBottom: 8,
  },
  formInput: {
    backgroundColor: '#f8f9fa',
    borderWidth: 1,
    borderColor: '#dadce0',
    borderRadius: 8,
    paddingHorizontal: 12,
    paddingVertical: 12,
    fontSize: 14,
    color: '#202124',
  },
  formInputMultiline: {
    minHeight: 80,
    textAlignVertical: 'top',
  },
  formInputDisabled: {
    backgroundColor: '#e8eaed',
    color: '#5f6368',
  },
  passwordInfo: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    marginTop: 8,
    padding: 12,
    backgroundColor: '#e8f0fe',
    borderRadius: 8,
  },
  passwordInfoText: {
    fontSize: 12,
    color: '#1967d2',
    flex: 1,
  },
  modalFooter: {
    flexDirection: 'row',
    paddingHorizontal: 20,
    paddingVertical: 16,
    borderTopWidth: 1,
    borderTopColor: '#e8eaed',
    gap: 12,
  },
  modalButton: {
    flex: 1,
    paddingVertical: 12,
    borderRadius: 8,
    alignItems: 'center',
    justifyContent: 'center',
  },
  modalButtonCancel: {
    backgroundColor: '#f8f9fa',
    borderWidth: 1,
    borderColor: '#dadce0',
  },
  modalButtonSave: {
    backgroundColor: '#FF6B35',
  },
  modalButtonTextCancel: {
    fontSize: 14,
    fontWeight: '500',
    color: '#5f6368',
  },
  modalButtonTextSave: {
    fontSize: 14,
    fontWeight: '500',
    color: '#ffffff',
  },
});
