import Ionicons from '@expo/vector-icons/Ionicons';
import { router } from 'expo-router';
import { useEffect, useState } from 'react';
import { Modal, Pressable, ScrollView, StyleSheet, Text, TextInput, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { AppButton, AppFooter, BrandLogo, Field, Screen, TopBar, palette } from '@/components/app-ui';
import { useAuth } from '@/contexts/auth';
import { AdminUser, changePassword, deleteUserAccount, getProfile, getUsers, toggleUserVerification, updateProfile, updateUserRole } from '@/lib/api';

export default function AccountScreen() {
  const { isAuthenticated, user, signOut, setUser } = useAuth();
  const [users, setUsers] = useState<AdminUser[]>([]);
  const [usersError, setUsersError] = useState('');
  const [profileModalVisible, setProfileModalVisible] = useState(false);
  const [passwordModalVisible, setPasswordModalVisible] = useState(false);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  const [profileForm, setProfileForm] = useState({
    name: user?.name || '',
    email: user?.email || '',
  });

  const [passwordForm, setPasswordForm] = useState({
    current_password: '',
    new_password: '',
    confirm_password: '',
  });

  const handleUpdateProfile = async () => {
    setError('');
    setLoading(true);
    try {
      const response = await updateProfile(profileForm);
      if (response.user) {
        setUser(response.user);
        setProfileModalVisible(false);
      }
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to update profile');
    } finally {
      setLoading(false);
    }
  };

  const handleChangePassword = async () => {
    setError('');
    setLoading(true);
    try {
      await changePassword(passwordForm);
      setPasswordModalVisible(false);
      setPasswordForm({ current_password: '', new_password: '', confirm_password: '' });
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to change password');
    } finally {
      setLoading(false);
    }
  };

  const handleToggleRole = async (userId: number | string, currentRole: string) => {
    const newRole = currentRole === 'admin' ? 'user' : 'admin';
    try {
      await updateUserRole(userId, newRole);
      // Reload users
      const updatedUsers = await getUsers();
      setUsers(updatedUsers);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to update role');
    }
  };

  const handleToggleVerification = async (userId: number | string) => {
    try {
      await toggleUserVerification(userId);
      // Reload users
      const updatedUsers = await getUsers();
      setUsers(updatedUsers);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to toggle verification');
    }
  };

  const handleDeleteUser = async (userId: number | string, userName: string) => {
    try {
      await deleteUserAccount(userId);
      // Reload users
      const updatedUsers = await getUsers();
      setUsers(updatedUsers);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to delete user');
    }
  };

  useEffect(() => {
    if (user?.role !== 'admin') {
      setUsers([]);
      return;
    }

    let mounted = true;

    getUsers()
      .then((items) => {
        if (mounted) {
          setUsers(items);
          setUsersError('');
        }
      })
      .catch((err) => {
        if (mounted) {
          setUsersError(err instanceof Error ? err.message : 'Could not load users.');
        }
      });

    return () => {
      mounted = false;
    };
  }, [user?.role]);

  return (
    <Screen>
      <SafeAreaView style={styles.safe}>
        <TopBar />
        <ScrollView contentContainerStyle={styles.content}>
          <View style={styles.profile}>
            <BrandLogo compact />
            <Text style={styles.title}>
              <Text style={styles.white}>{isAuthenticated ? user?.name : 'Guest '}</Text>
              {!isAuthenticated ? <Text style={styles.orange}>account</Text> : null}
            </Text>
            <Text style={styles.subtitle}>{isAuthenticated ? user?.email : 'Sign in to personalize your dashboard.'}</Text>
          </View>

          <View style={styles.panel}>
            <InfoRow icon="ribbon-outline" label="Role" value={isAuthenticated ? user?.role ?? 'user' : 'guest'} />
            <InfoRow icon="shield-checkmark-outline" label="Account status" value={isAuthenticated ? 'Signed in' : 'Not signed in'} />
          </View>

          {isAuthenticated ? (
            <View style={styles.actions}>
              <AppButton icon="person-outline" onPress={() => setProfileModalVisible(true)}>Edit Profile</AppButton>
              <AppButton icon="lock-closed-outline" variant="secondary" onPress={() => setPasswordModalVisible(true)}>Change Password</AppButton>
            </View>
          ) : null}

          {user?.role === 'admin' ? (
            <View style={styles.panel}>
              <View style={styles.panelHeader}>
                <Text style={styles.panelTitle}>User Management</Text>
                <AppButton icon="refresh-outline" variant="ghost" onPress={() => getUsers().then(setUsers).catch(setUsersError)} style={styles.refreshButton}>
                  Refresh
                </AppButton>
              </View>
              {usersError ? (
                <Text style={styles.errorText}>{usersError}</Text>
              ) : users.length === 0 ? (
                <Text style={styles.emptyText}>No users found</Text>
              ) : (
                users.map((adminUser) => (
                  <View key={String(adminUser.id)} style={styles.userRow}>
                    <View style={styles.userInfo}>
                      <Text style={styles.userName}>{adminUser.name}</Text>
                      <Text style={styles.userEmail}>{adminUser.email}</Text>
                      <View style={styles.userBadges}>
                        <View style={[styles.badge, adminUser.role === 'admin' && styles.adminBadge]}>
                          <Text style={styles.badgeText}>{adminUser.role}</Text>
                        </View>
                        <View style={[styles.badge, (Number(adminUser.is_verified) === 1 || adminUser.is_verified === true) && styles.verifiedBadge]}>
                          <Text style={styles.badgeText}>
                            {(Number(adminUser.is_verified) === 1 || adminUser.is_verified === true) ? 'Verified' : 'Unverified'}
                          </Text>
                        </View>
                      </View>
                    </View>
                    <View style={styles.userActions}>
                      {String(adminUser.id) !== String(user?.id) && (
                        <>
                          <AppButton
                            icon="swap-horizontal-outline"
                            variant="ghost"
                            onPress={() => handleToggleRole(adminUser.id, adminUser.role)}
                            style={styles.actionButton}
                          >
                            {adminUser.role === 'admin' ? 'Make User' : 'Make Admin'}
                          </AppButton>
                          <AppButton
                            icon="checkmark-circle-outline"
                            variant="ghost"
                            onPress={() => handleToggleVerification(adminUser.id)}
                            style={styles.actionButton}
                          >
                            {Number(adminUser.is_verified) === 1 || adminUser.is_verified === true ? 'Unverify' : 'Verify'}
                          </AppButton>
                          <AppButton
                            icon="trash-outline"
                            variant="ghost"
                            onPress={() => handleDeleteUser(adminUser.id, adminUser.name)}
                            style={[styles.actionButton, styles.deleteButton]}
                          >
                            Delete
                          </AppButton>
                        </>
                      )}
                    </View>
                  </View>
                ))
              )}
            </View>
          ) : null}

          {isAuthenticated ? (
            <AppButton
              icon="log-out-outline"
              variant="secondary"
              onPress={() => {
                signOut();
                router.push('/');
              }}>
              Sign out
            </AppButton>
          ) : (
            <View style={styles.actions}>
              <AppButton icon="log-in-outline" onPress={() => router.push('/login')}>Sign in</AppButton>
              <AppButton icon="person-add-outline" variant="secondary" onPress={() => router.push('/register')}>
                Create account
              </AppButton>
            </View>
          )}
          <AppFooter />
        </ScrollView>
      </SafeAreaView>

      {/* Edit Profile Modal */}
      <Modal
        visible={profileModalVisible}
        transparent
        animationType="fade"
        onRequestClose={() => setProfileModalVisible(false)}>
        <Pressable style={styles.modalOverlay} onPress={() => setProfileModalVisible(false)}>
          <View style={styles.modalContainer} onStartShouldSetResponder={() => true}>
            <Text style={styles.modalTitle}>Edit Profile</Text>
            {error ? <Text style={styles.errorText}>{error}</Text> : null}
            <Field
              label="Name"
              icon="person-outline"
              placeholder="Your name"
              value={profileForm.name}
              onChangeText={(text) => setProfileForm({ ...profileForm, name: text })}
            />
            <Field
              label="Email"
              icon="mail-outline"
              placeholder="your@email.com"
              value={profileForm.email}
              onChangeText={(text) => setProfileForm({ ...profileForm, email: text })}
              keyboardType="email-address"
              autoCapitalize="none"
            />
            <View style={styles.modalActions}>
              <AppButton
                variant="ghost"
                onPress={() => setProfileModalVisible(false)}
                style={styles.modalButton}
              >
                Cancel
              </AppButton>
              <AppButton
                onPress={handleUpdateProfile}
                loading={loading}
                style={styles.modalButton}
              >
                Save
              </AppButton>
            </View>
          </View>
        </Pressable>
      </Modal>

      {/* Change Password Modal */}
      <Modal
        visible={passwordModalVisible}
        transparent
        animationType="fade"
        onRequestClose={() => setPasswordModalVisible(false)}>
        <Pressable style={styles.modalOverlay} onPress={() => setPasswordModalVisible(false)}>
          <View style={styles.modalContainer} onStartShouldSetResponder={() => true}>
            <Text style={styles.modalTitle}>Change Password</Text>
            {error ? <Text style={styles.errorText}>{error}</Text> : null}
            <Field
              label="Current Password"
              icon="lock-closed-outline"
              placeholder="Enter current password"
              value={passwordForm.current_password}
              onChangeText={(text) => setPasswordForm({ ...passwordForm, current_password: text })}
              secureTextEntry
            />
            <Field
              label="New Password"
              icon="lock-open-outline"
              placeholder="Enter new password"
              value={passwordForm.new_password}
              onChangeText={(text) => setPasswordForm({ ...passwordForm, new_password: text })}
              secureTextEntry
            />
            <Field
              label="Confirm Password"
              icon="checkmark-circle-outline"
              placeholder="Confirm new password"
              value={passwordForm.confirm_password}
              onChangeText={(text) => setPasswordForm({ ...passwordForm, confirm_password: text })}
              secureTextEntry
            />
            <View style={styles.modalActions}>
              <AppButton
                variant="ghost"
                onPress={() => setPasswordModalVisible(false)}
                style={styles.modalButton}
              >
                Cancel
              </AppButton>
              <AppButton
                onPress={handleChangePassword}
                loading={loading}
                style={styles.modalButton}
              >
                Change
              </AppButton>
            </View>
          </View>
        </Pressable>
      </Modal>
    </Screen>
  );
}

function InfoRow({ icon, label, value }: { icon: keyof typeof Ionicons.glyphMap; label: string; value?: string }) {
  return (
    <View style={styles.row}>
      <View style={styles.rowIcon}>
        <Ionicons name={icon} size={20} color={palette.green} />
      </View>
      <View style={styles.rowText}>
        <Text style={styles.rowLabel}>{label}</Text>
        <Text style={styles.rowValue}>{value}</Text>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  safe: {
    flex: 1,
  },
  content: {
    padding: 20,
    paddingBottom: 34,
    gap: 18,
  },
  profile: {
    alignItems: 'center',
    borderRadius: 8,
    backgroundColor: 'transparent',
    borderColor: palette.border,
    borderWidth: 0,
    padding: 22,
  },
  title: {
    color: palette.ink,
    fontSize: 24,
    fontWeight: '900',
    textAlign: 'center',
  },
  subtitle: {
    color: palette.muted,
    textAlign: 'center',
    marginTop: 6,
    lineHeight: 21,
  },
  panel: {
    borderRadius: 8,
    backgroundColor: 'transparent',
    borderColor: palette.border,
    borderWidth: 0,
  },
  row: {
    flexDirection: 'row',
    gap: 12,
    padding: 14,
    borderBottomColor: palette.border,
    borderBottomWidth: 1,
  },
  rowIcon: {
    width: 38,
    height: 38,
    borderRadius: 4,
    backgroundColor: '#000000',
    borderColor: palette.blue,
    borderWidth: 1,
    alignItems: 'center',
    justifyContent: 'center',
  },
  rowText: {
    flex: 1,
    gap: 2,
  },
  rowLabel: {
    color: palette.muted,
    fontWeight: '700',
    fontSize: 12,
  },
  rowValue: {
    color: palette.ink,
    fontWeight: '900',
    textTransform: 'capitalize',
  },
  actions: {
    gap: 10,
  },
  modalOverlay: {
    flex: 1,
    backgroundColor: 'rgba(0, 0, 0, 0.85)',
    alignItems: 'center',
    justifyContent: 'center',
    padding: 24,
  },
  modalContainer: {
    width: '100%',
    maxWidth: 380,
    borderRadius: 8,
    backgroundColor: 'transparent',
    borderColor: palette.border,
    borderWidth: 0,
    padding: 20,
    gap: 16,
  },
  modalTitle: {
    color: palette.ink,
    fontSize: 20,
    fontWeight: '900',
    textAlign: 'center',
  },
  errorText: {
    color: palette.red,
    fontSize: 14,
    textAlign: 'center',
  },
  modalActions: {
    flexDirection: 'row',
    gap: 10,
    marginTop: 8,
  },
  modalButton: {
    flex: 1,
  },
  panelHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    padding: 14,
    borderBottomColor: palette.border,
    borderBottomWidth: 1,
  },
  panelTitle: {
    color: palette.ink,
    fontSize: 16,
    fontWeight: '900',
  },
  refreshButton: {
    padding: 8,
  },
  userRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    padding: 14,
    borderBottomColor: palette.border,
    borderBottomWidth: 1,
    gap: 12,
  },
  userInfo: {
    flex: 1,
    gap: 4,
  },
  userName: {
    color: '#FFFFFF',
    fontWeight: '900',
    fontSize: 14,
  },
  userEmail: {
    color: palette.muted,
    fontSize: 12,
  },
  userBadges: {
    flexDirection: 'row',
    gap: 6,
    marginTop: 4,
  },
  badge: {
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 4,
    backgroundColor: '#1a1a1a',
    borderWidth: 1,
    borderColor: palette.border,
  },
  adminBadge: {
    borderColor: palette.orange,
    backgroundColor: 'rgba(255, 107, 53, 0.1)',
  },
  verifiedBadge: {
    borderColor: palette.green,
    backgroundColor: 'rgba(0, 135, 90, 0.1)',
  },
  badgeText: {
    color: palette.muted,
    fontSize: 10,
    fontWeight: '700',
    textTransform: 'uppercase',
  },
  userActions: {
    gap: 6,
  },
  actionButton: {
    padding: 8,
  },
  deleteButton: {
    borderColor: palette.red,
  },
  emptyText: {
    color: palette.muted,
    textAlign: 'center',
    padding: 20,
    fontSize: 14,
  },
  white: {
    color: '#FFFFFF',
  },
  orange: {
    color: palette.orange,
  },
});
