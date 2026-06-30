import { router, useLocalSearchParams } from 'expo-router';
import { useState } from 'react';
import { ScrollView, StyleSheet, Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { AppButton, AppFooter, Field, Screen, TopBar, palette } from '@/components/app-ui';
import { AlertModal } from '@/components/alert-modal';
import { resendVerification, verifyAccount } from '@/lib/api';

export default function VerifyScreen() {
  const params = useLocalSearchParams<{ email?: string }>();
  const [email, setEmail] = useState(params.email ?? '');
  const [code, setCode] = useState('');
  const [loading, setLoading] = useState(false);
  const [resending, setResending] = useState(false);
  const [alertModal, setAlertModal] = useState<{
    visible: boolean;
    title: string;
    message: string;
    type: 'success' | 'error' | 'info' | 'warning';
  }>({ visible: false, title: '', message: '', type: 'info' });

  const showAlert = (title: string, message: string, type: 'success' | 'error' | 'info' | 'warning' = 'info') => {
    setAlertModal({ visible: true, title, message, type });
  };

  const submit = async () => {
    setLoading(true);
    try {
      const response = await verifyAccount(email.trim(), code.trim());
      showAlert(
        'Account Verified Successfully',
        response.message || 'Great! Your account has been verified. You can now sign in.',
        'success'
      );
      setTimeout(() => {
        router.replace('/login');
      }, 2000);
    } catch (err) {
      showAlert(
        'Verification Failed',
        err instanceof Error ? err.message : 'We couldn\'t verify your account. Please check the code and try again.',
        'error'
      );
    } finally {
      setLoading(false);
    }
  };

  const resend = async () => {
    setResending(true);
    try {
      const response = await resendVerification(email.trim());
      showAlert(
        'Verification Code Sent',
        response.message || 'A new verification code has been sent to your email address.',
        'success'
      );
    } catch (err) {
      showAlert(
        'Could Not Resend Code',
        err instanceof Error ? err.message : 'We couldn\'t send a new code. Please try again later.',
        'error'
      );
    } finally {
      setResending(false);
    }
  };

  return (
    <Screen>
      <SafeAreaView style={styles.safe}>
        <TopBar />
        <ScrollView contentContainerStyle={styles.content} keyboardShouldPersistTaps="handled">
          <View style={styles.header}>
            <Text style={styles.eyebrow}>Email check</Text>
            <Text style={styles.title}>
              <Text style={styles.white}>Verify your </Text>
              <Text style={styles.orange}>account</Text>
            </Text>
            <Text style={styles.subtitle}>Enter the six digit code sent to your email address.</Text>
          </View>

          <View style={styles.form}>
            <Field label="Email address" icon="mail-outline" value={email} onChangeText={setEmail} keyboardType="email-address" placeholder="you@example.com" />
            <Field label="Verification code" icon="keypad-outline" value={code} onChangeText={setCode} keyboardType="number-pad" maxLength={6} placeholder="000000" />
            <AppButton icon="checkmark-circle-outline" loading={loading} disabled={!email || code.length < 4} onPress={submit}>
              Verify account
            </AppButton>
            <AppButton icon="refresh-outline" variant="secondary" loading={resending} disabled={!email} onPress={resend}>
              Resend code
            </AppButton>
          </View>
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
