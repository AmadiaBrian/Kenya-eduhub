import { router } from 'expo-router';
import { useState } from 'react';
import { Alert, ScrollView, StyleSheet, Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { AppButton, AppFooter, Field, Screen, TopBar, palette } from '@/components/app-ui';
import { requestPasswordReset, resetPassword, verifyResetCode } from '@/lib/api';

export default function ForgotPasswordScreen() {
  const [email, setEmail] = useState('');
  const [code, setCode] = useState('');
  const [password, setPassword] = useState('');
  const [codeSent, setCodeSent] = useState(false);
  const [codeVerified, setCodeVerified] = useState(false);
  const [loading, setLoading] = useState(false);

  const sendCode = async () => {
    setLoading(true);
    try {
      const response = await requestPasswordReset(email.trim());
      setCodeSent(true);
      Alert.alert('Check your email', response.message || 'Password reset code sent.');
    } catch (err) {
      Alert.alert('Request failed', err instanceof Error ? err.message : 'Please try again.');
    } finally {
      setLoading(false);
    }
  };

  const verifyCode = async () => {
    setLoading(true);
    try {
      const response = await verifyResetCode(email.trim(), code.trim());
      setCodeVerified(true);
      Alert.alert('Code verified', response.message || 'Enter your new password.');
    } catch (err) {
      Alert.alert('Verification failed', err instanceof Error ? err.message : 'Please try again.');
    } finally {
      setLoading(false);
    }
  };

  const submitReset = async () => {
    setLoading(true);
    try {
      const response = await resetPassword(email.trim(), code.trim(), password);
      Alert.alert('Password updated', response.message || 'You can now sign in.');
      router.replace('/login');
    } catch (err) {
      Alert.alert('Reset failed', err instanceof Error ? err.message : 'Please try again.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <Screen>
      <SafeAreaView style={styles.safe}>
        <TopBar />
        <ScrollView contentContainerStyle={styles.content} keyboardShouldPersistTaps="handled">
          <View style={styles.header}>
            <Text style={styles.eyebrow}>Account recovery</Text>
            <Text style={styles.title}>
              <Text style={styles.white}>Reset your </Text>
              <Text style={styles.orange}>password</Text>
            </Text>
            <Text style={styles.subtitle}>Request a code, then set a new password using the code from your email.</Text>
          </View>

          <View style={styles.form}>
            <Field label="Email address" icon="mail-outline" value={email} onChangeText={setEmail} keyboardType="email-address" placeholder="you@example.com" />
            {codeSent ? (
              <>
                <Field label="Reset code" icon="keypad-outline" value={code} onChangeText={setCode} keyboardType="number-pad" maxLength={6} placeholder="000000" />
                {codeVerified ? (
                  <>
                    <Field label="New password" icon="lock-closed-outline" value={password} onChangeText={setPassword} secureTextEntry placeholder="At least 6 characters" />
                    <AppButton icon="save-outline" loading={loading} disabled={!email || !code || password.length < 6} onPress={submitReset}>
                      Update password
                    </AppButton>
                  </>
                ) : (
                  <AppButton icon="checkmark-circle-outline" loading={loading} disabled={!email || code.length < 4} onPress={verifyCode}>
                    Verify reset code
                  </AppButton>
                )}
              </>
            ) : (
              <AppButton icon="mail-open-outline" loading={loading} disabled={!email} onPress={sendCode}>
                Send reset code
              </AppButton>
            )}
          </View>

          <AppButton variant="ghost" onPress={() => router.push('/login')}>Back to sign in</AppButton>
          <AppFooter />
        </ScrollView>
      </SafeAreaView>
    </Screen>
  );
}

const styles = StyleSheet.create({
  safe: { flex: 1 },
  content: {
    flexGrow: 1,
    padding: 20,
    justifyContent: 'center',
    gap: 22,
  },
  header: { gap: 8 },
  eyebrow: {
    color: palette.gold,
    fontWeight: '900',
    fontSize: 13,
  },
  title: {
    color: palette.ink,
    fontWeight: '900',
    fontSize: 32,
    lineHeight: 37,
  },
  subtitle: {
    color: palette.muted,
    fontSize: 16,
    lineHeight: 23,
  },
  form: {
    borderRadius: 4,
    backgroundColor: palette.panel,
    borderColor: palette.border,
    borderWidth: 1,
    padding: 16,
    gap: 14,
  },
  white: { color: '#FFFFFF' },
  orange: { color: palette.orange },
});
