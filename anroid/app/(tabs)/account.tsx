import Ionicons from '@expo/vector-icons/Ionicons';
import { router } from 'expo-router';
import { useEffect, useState } from 'react';
import { ScrollView, StyleSheet, Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { AppButton, AppFooter, BrandLogo, Screen, TopBar, palette } from '@/components/app-ui';
import { useAuth } from '@/contexts/auth';
import { AdminUser, getUsers } from '@/lib/api';

export default function AccountScreen() {
  const { isAuthenticated, user, signOut } = useAuth();
  const [users, setUsers] = useState<AdminUser[]>([]);
  const [usersError, setUsersError] = useState('');

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
            <InfoRow icon="server-outline" label="Backend" value="Local Kenya EduHub API" />
          </View>

          {user?.role === 'admin' ? (
            <View style={styles.panel}>
              <InfoRow icon="people-outline" label="Registered users" value={usersError || String(users.length)} />
              <InfoRow
                icon="checkmark-done-outline"
                label="Verified accounts"
                value={String(users.filter((item) => Number(item.is_verified) === 1 || item.is_verified === true).length)}
              />
              <InfoRow icon="person-circle-outline" label="Admin accounts" value={String(users.filter((item) => item.role === 'admin').length)} />
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
    backgroundColor: palette.panel,
    borderColor: palette.border,
    borderWidth: 1,
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
    backgroundColor: palette.panel,
    borderColor: palette.border,
    borderWidth: 1,
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
  white: {
    color: '#FFFFFF',
  },
  orange: {
    color: palette.orange,
  },
});
