import { View, Text, StyleSheet, TouchableOpacity, ScrollView, ActivityIndicator, TextInput, Alert, RefreshControl, Modal } from 'react-native';
import { useRouter } from 'expo-router';
import { useState, useEffect } from 'react';
import { Ionicons } from '@expo/vector-icons';
import { getProfile, updateProfile, logout, ProfileResponse } from '../../lib/api';
import * as SecureStore from 'expo-secure-store';

export default function Profile() {
  const router = useRouter();
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [saving, setSaving] = useState(false);
  const [profileData, setProfileData] = useState<ProfileResponse | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [form, setForm] = useState({
    first_name: '',
    last_name: '',
    phone: '',
    address: ''
  });
  const [showLogoutModal, setShowLogoutModal] = useState(false);
  const [loggingOut, setLoggingOut] = useState(false);

  useEffect(() => {
    loadProfile();
  }, []);

  const loadProfile = async () => {
    try {
      const data = await getProfile();
      setProfileData(data);
      setError(null);
      if (data.parent) {
        setForm({
          first_name: data.parent.first_name,
          last_name: data.parent.last_name,
          phone: data.parent.phone,
          address: data.parent.address
        });
      }
    } catch (error: any) {
      if (error.message === 'NOT_AUTHENTICATED') {
        router.replace('/login');
      } else {
        setError(error.message || 'Failed to load profile');
      }
    } finally {
      setLoading(false);
    }
  };

  const onRefresh = async () => {
    setRefreshing(true);
    await loadProfile();
    setRefreshing(false);
  };

  const handleUpdate = async () => {
    if (!form.first_name || !form.last_name || !form.phone) {
      Alert.alert('Error', 'Please fill in all required fields');
      return;
    }

    setSaving(true);
    try {
      const data = await updateProfile(form);
      setProfileData(data);
      Alert.alert('Success', data.message || 'Profile updated successfully');
    } catch (error) {
      Alert.alert('Error', 'Failed to update profile. Please try again.');
    } finally {
      setSaving(false);
    }
  };

  const handleLogout = async () => {
    setShowLogoutModal(true);
  };

  const confirmLogout = async () => {
    setLoggingOut(true);
    try {
      await logout();
      // Clear session from SecureStore
      await SecureStore.deleteItemAsync('userSession');
      setShowLogoutModal(false);
      router.replace('/login');
    } catch (error) {
      setLoggingOut(false);
      setShowLogoutModal(false);
      Alert.alert('Error', 'Logout failed. Please try again.');
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
              loadProfile();
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
          <Text style={styles.pageTitle}>My Profile</Text>
          <Text style={styles.pageSubtitle}>
            Manage your account information
          </Text>
        </View>

        {profileData?.parent && (
          <View style={styles.card}>
            <View style={styles.avatarSection}>
              <View style={styles.avatarLarge}>
                <Text style={styles.avatarLargeText}>{profileData.parent.first_name.charAt(0)}{profileData.parent.last_name.charAt(0)}</Text>
              </View>
              <View style={styles.avatarInfo}>
                <Text style={styles.avatarName}>{profileData.parent.first_name} {profileData.parent.last_name}</Text>
                <Text style={styles.avatarSchool}>{profileData.parent.school_name}</Text>
              </View>
            </View>

            <View style={styles.formSection}>
              <Text style={styles.sectionTitle}>Personal Information</Text>
              
              <View style={styles.formGroup}>
                <Text style={styles.label}>First Name *</Text>
                <TextInput
                  style={styles.input}
                  value={form.first_name}
                  onChangeText={(text) => setForm({ ...form, first_name: text })}
                  placeholder="Enter first name"
                />
              </View>

              <View style={styles.formGroup}>
                <Text style={styles.label}>Last Name *</Text>
                <TextInput
                  style={styles.input}
                  value={form.last_name}
                  onChangeText={(text) => setForm({ ...form, last_name: text })}
                  placeholder="Enter last name"
                />
              </View>

              <View style={styles.formGroup}>
                <Text style={styles.label}>Email</Text>
                <TextInput
                  style={styles.input}
                  value={profileData.parent.email}
                  editable={false}
                  placeholder="Email address"
                />
              </View>

              <View style={styles.formGroup}>
                <Text style={styles.label}>Phone Number *</Text>
                <TextInput
                  style={styles.input}
                  value={form.phone}
                  onChangeText={(text) => setForm({ ...form, phone: text })}
                  placeholder="Enter phone number"
                  keyboardType="phone-pad"
                />
              </View>

              <View style={styles.formGroup}>
                <Text style={styles.label}>ID Number</Text>
                <TextInput
                  style={styles.input}
                  value={profileData.parent.id_number}
                  editable={false}
                  placeholder="ID number"
                />
              </View>

              <View style={styles.formGroup}>
                <Text style={styles.label}>Address</Text>
                <TextInput
                  style={styles.input}
                  value={form.address}
                  onChangeText={(text) => setForm({ ...form, address: text })}
                  placeholder="Enter address"
                  multiline
                  numberOfLines={3}
                />
              </View>

              <TouchableOpacity 
                style={[styles.saveButton, saving && styles.saveButtonDisabled]}
                onPress={handleUpdate}
                disabled={saving}
              >
                {saving ? (
                  <ActivityIndicator color="#ffffff" />
                ) : (
                  <>
                    <Ionicons name="save-outline" size={20} color="#ffffff" />
                    <Text style={styles.saveButtonText}>Save Changes</Text>
                  </>
                )}
              </TouchableOpacity>
            </View>

            <View style={styles.infoSection}>
              <Text style={styles.sectionTitle}>Account Information</Text>
              
              <View style={styles.infoItem}>
                <Text style={styles.infoLabel}>Member Since</Text>
                <Text style={styles.infoValue}>
                  {profileData.parent.created_at ? new Date(profileData.parent.created_at).toLocaleDateString() : 'N/A'}
                </Text>
              </View>

              <View style={styles.infoItem}>
                <Text style={styles.infoLabel}>Email</Text>
                <Text style={styles.infoValue}>{profileData.parent.email}</Text>
              </View>

              <View style={styles.infoItem}>
                <Text style={styles.infoLabel}>School</Text>
                <Text style={styles.infoValue}>{profileData.parent.school_name}</Text>
              </View>

              <TouchableOpacity 
                style={styles.logoutButton}
                onPress={handleLogout}
              >
                <Ionicons name="log-out-outline" size={20} color="#c5221f" />
                <Text style={styles.logoutButtonText}>Logout</Text>
              </TouchableOpacity>
            </View>
          </View>
        )}
      </ScrollView>

      {/* Logout Confirmation Modal */}
      <Modal
        visible={showLogoutModal}
        transparent={true}
        animationType="fade"
        onRequestClose={() => setShowLogoutModal(false)}
      >
        <View style={styles.modalOverlay}>
          <View style={styles.modalContainer}>
            <View style={styles.modalHeader}>
              <View style={styles.modalIconContainer}>
                <Ionicons name="log-out-outline" size={32} color="#c5221f" />
              </View>
              <Text style={styles.modalTitle}>Logout</Text>
            </View>
            
            <View style={styles.modalBody}>
              <Text style={styles.modalMessage}>
                Are you sure you want to logout? You will need to login again to access your account.
              </Text>
            </View>

            <View style={styles.modalFooter}>
              <TouchableOpacity
                style={styles.modalCancelButton}
                onPress={() => setShowLogoutModal(false)}
                disabled={loggingOut}
              >
                <Text style={styles.modalCancelButtonText}>Cancel</Text>
              </TouchableOpacity>
              <TouchableOpacity
                style={[styles.modalConfirmButton, loggingOut && styles.modalConfirmButtonDisabled]}
                onPress={confirmLogout}
                disabled={loggingOut}
              >
                {loggingOut ? (
                  <ActivityIndicator color="#ffffff" size="small" />
                ) : (
                  <Text style={styles.modalConfirmButtonText}>Logout</Text>
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
  content: {
    flex: 1,
  },
  pageHeader: {
    padding: 24,
    backgroundColor: '#ffffff',
    borderBottomWidth: 1,
    borderBottomColor: '#e8eaed',
  },
  pageTitle: {
    fontSize: 24,
    fontWeight: '600',
    color: '#202124',
  },
  pageSubtitle: {
    fontSize: 14,
    color: '#5f6368',
    marginTop: 4,
  },
  card: {
    backgroundColor: '#f8f9fa',
    borderWidth: 1,
    borderColor: '#e8eaed',
    borderRadius: 8,
    padding: 24,
    marginBottom: 24,
    marginHorizontal: 8,
  },
  avatarSection: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 32,
  },
  avatarLarge: {
    width: 80,
    height: 80,
    borderRadius: 40,
    backgroundColor: '#FF6B35',
    justifyContent: 'center',
    alignItems: 'center',
    marginRight: 16,
  },
  avatarLargeText: {
    fontSize: 32,
    fontWeight: 'bold',
    color: '#ffffff',
  },
  avatarInfo: {
    flex: 1,
  },
  avatarName: {
    fontSize: 20,
    fontWeight: '600',
    color: '#202124',
    marginBottom: 4,
  },
  avatarSchool: {
    fontSize: 14,
    color: '#5f6368',
  },
  formSection: {
    marginBottom: 24,
  },
  sectionTitle: {
    fontSize: 18,
    fontWeight: '600',
    color: '#202124',
    marginBottom: 16,
  },
  formGroup: {
    marginBottom: 20,
  },
  label: {
    fontSize: 14,
    fontWeight: '500',
    color: '#5f6368',
    marginBottom: 8,
  },
  input: {
    backgroundColor: '#ffffff',
    borderWidth: 1,
    borderColor: '#dadce0',
    borderRadius: 25,
    padding: 12,
    fontSize: 16,
    color: '#202124',
  },
  saveButton: {
    backgroundColor: '#FF6B35',
    borderRadius: 25,
    padding: 16,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    marginTop: 8,
  },
  saveButtonDisabled: {
    backgroundColor: '#ccc',
  },
  saveButtonText: {
    color: '#ffffff',
    fontSize: 16,
    fontWeight: '600',
    marginLeft: 8,
  },
  infoSection: {
    marginTop: 32,
  },
  infoItem: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    paddingVertical: 12,
    borderBottomWidth: 1,
    borderBottomColor: '#e8eaed',
  },
  infoLabel: {
    fontSize: 14,
    color: '#5f6368',
  },
  infoValue: {
    fontSize: 14,
    fontWeight: '500',
    color: '#202124',
  },
  loadingContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
  },
  loadingText: {
    fontSize: 16,
    color: '#5f6368',
    marginTop: 12,
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
  logoutButton: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: '#fce8e6',
    borderWidth: 1,
    borderColor: '#f9dedc',
    borderRadius: 25,
    padding: 14,
    marginTop: 24,
    gap: 8,
  },
  logoutButtonText: {
    fontSize: 16,
    fontWeight: '600',
    color: '#c5221f',
  },
  modalOverlay: {
    flex: 1,
    backgroundColor: 'rgba(0, 0, 0, 0.5)',
    justifyContent: 'center',
    alignItems: 'center',
    padding: 20,
  },
  modalContainer: {
    backgroundColor: '#ffffff',
    borderRadius: 12,
    width: '100%',
    maxWidth: 400,
    elevation: 24,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 10 },
    shadowOpacity: 0.25,
    shadowRadius: 12,
  },
  modalHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    padding: 24,
    borderBottomWidth: 1,
    borderBottomColor: '#e8eaed',
  },
  modalIconContainer: {
    width: 48,
    height: 48,
    borderRadius: 24,
    backgroundColor: '#fce8e6',
    justifyContent: 'center',
    alignItems: 'center',
    marginRight: 16,
  },
  modalTitle: {
    fontSize: 20,
    fontWeight: '600',
    color: '#202124',
  },
  modalBody: {
    padding: 24,
  },
  modalMessage: {
    fontSize: 16,
    color: '#5f6368',
    lineHeight: 24,
  },
  modalFooter: {
    flexDirection: 'row',
    padding: 16,
    gap: 12,
    borderTopWidth: 1,
    borderTopColor: '#e8eaed',
  },
  modalCancelButton: {
    flex: 1,
    padding: 14,
    borderRadius: 8,
    borderWidth: 1,
    borderColor: '#dadce0',
    backgroundColor: '#ffffff',
    alignItems: 'center',
    justifyContent: 'center',
  },
  modalCancelButtonText: {
    fontSize: 14,
    fontWeight: '600',
    color: '#5f6368',
  },
  modalConfirmButton: {
    flex: 1,
    padding: 14,
    borderRadius: 8,
    backgroundColor: '#c5221f',
    alignItems: 'center',
    justifyContent: 'center',
  },
  modalConfirmButtonDisabled: {
    opacity: 0.6,
  },
  modalConfirmButtonText: {
    fontSize: 14,
    fontWeight: '600',
    color: '#ffffff',
  },
});
