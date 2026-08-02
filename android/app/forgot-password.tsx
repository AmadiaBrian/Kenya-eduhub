import { router } from 'expo-router';
import { useState } from 'react';
import { ScrollView, StyleSheet, Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { AppButton, AppFooter, Field, Screen, TopBar, palette } from '@/components/app-ui';
import { AlertModal } from '@/components/alert-modal';
import { requestPasswordReset, resetPassword, verifyResetCode } from '@/lib/api';

export default function ForgotPasswordScreen() {
  const [email, setEmail] = useState('');
  const [code, setCode] = useState('');
  const [password, setPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [codeSent, setCodeSent] = useState(false);
  const [codeVerified, setCodeVerified] = useState(false);
  const [loading, setLoading] = useState(false);
  const [alertModal, setAlertModal] = useState<{
    visible: boolean;
    title: string;
    message: string;
    type: 'success' | 'error' | 'info' | 'warning';
  }>({ visible: false, title: '', message: '', type: 'info' });

  const showAlert = (title: string, message: string, type: 'success' | 'error' | 'info' | 'warning' = 'info') => {
    setAlertModal({ visible: true, title, message, type });
  };

  const sendCode = async () => {
    setLoading(true);
    try {
      const response = await requestPasswordReset(email.trim());
      setCodeSent(true);
      showAlert(
        'Check Your Email',
        response.message || 'A password reset code has been sent to your email address.',
        'success'
      );
    } catch (err) {
      showAlert(
        'Request Failed',
        err instanceof Error ? err.message : 'We couldn\'t send the reset code. Please check your email and try again.',
        'error'
      );
    } finally {
      setLoading(false);
    }
  };

  const verifyCode = async () => {
    setLoading(true);
    try {
      const response = await verifyResetCode(email.trim(), code.trim());
      setCodeVerified(true);
      showAlert(
        'Code Verified',
        response.message || 'Great! Your code has been verified. Please enter your new password.',
        'success'
      );
    } catch (err) {
      showAlert(
        'Verification Failed',
        err instanceof Error ? err.message : 'The code you entered is incorrect. Please check and try again.',
        'error'
      );
    } finally {
      setLoading(false);
    }
  };

  const submitReset = async () => {
    if (password !== confirmPassword) {
      showAlert(
        'Passwords Do Not Match',
        'Please make sure your new password and confirm password match.',
        'error'
      );
      return;
    }

    if (password.length < 6) {
      showAlert(
        'Password Too Short',
        'Password must be at least 6 characters long.',
        'error'
      );
      return;
    }

    setLoading(true);
    try {
      const response = await resetPassword(email.trim(), code.trim(), password);
      showAlert(
        'Password Updated Successfully',
        response.message || 'Your password has been changed. You can now sign in with your new password.',
        'success'
      );
      setTimeout(() => {
        router.replace('/login');
      }, 2000);
    } catch (err) {
      showAlert(
        'Password Reset Failed',
        err instanceof Error ? err.message : 'We couldn\'t update your password. Please try again.',
        'error'
      );
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
                    <Field label="Confirm password" icon="lock-closed-outline" value={confirmPassword} onChangeText={setConfirmPassword} secureTextEntry placeholder="Re-enter your password" />
                    <AppButton icon="save-outline" loading={loading} disabled={!email || !code || password.length < 6 || password !== confirmPassword} onPress={submitReset}>
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
      <AlertModal
        visible={alertModal.visible}
        title={alertModal.title}
        message={alertModal.message}
        type={alertModal.type}
        onClose={() => setAlertModal({ ...alertModal, visible: false })}
      />
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
    backgroundColor: 'transparent',
    borderColor: palette.border,
    borderWidth: 0,
    padding: 16,
    gap: 14,
  },
  white: { color: '#FFFFFF' },
  orange: { color: palette.orange },
});
