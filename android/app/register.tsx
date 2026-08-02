import { router } from 'expo-router';
import { useState } from 'react';
import { ScrollView, StyleSheet, Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { AppButton, AppFooter, Field, Screen, TopBar, palette } from '@/components/app-ui';
import { AlertModal } from '@/components/alert-modal';
import { register } from '@/lib/api';

export default function RegisterScreen() {
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
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

  const submit = async () => {
    setLoading(true);
    try {
      const response = await register(name.trim(), email.trim(), password);
      showAlert(
        'Account Created Successfully',
        response.message || 'Great! Your account has been created. Please check your email for the verification code.',
        'success'
      );
      setTimeout(() => {
        router.replace({ pathname: '/verify', params: { email: email.trim() } });
      }, 2000);
    } catch (err) {
      showAlert(
        'Registration Failed',
        err instanceof Error ? err.message : 'We couldn\'t create your account. Please check your information and try again.',
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
            <Text style={styles.eyebrow}>New learner</Text>
            <Text style={styles.title}>
              <Text style={styles.white}>Create your </Text>
              <Text style={styles.orange}>account</Text>
            </Text>
            <Text style={styles.subtitle}>After registration, enter the email code sent by the backend.</Text>
          </View>

          <View style={styles.form}>
            <Field label="Full name" icon="person-outline" value={name} onChangeText={setName} autoCapitalize="words" placeholder="Your name" />
            <Field label="Email address" icon="mail-outline" value={email} onChangeText={setEmail} keyboardType="email-address" placeholder="you@example.com" />
            <Field label="Password" icon="lock-closed-outline" value={password} onChangeText={setPassword} secureTextEntry placeholder="At least 6 characters" />
            <AppButton icon="person-add-outline" loading={loading} disabled={!name || !email || password.length < 6} onPress={submit}>
              Create account
            </AppButton>
          </View>

          <AppButton variant="ghost" onPress={() => router.push('/login')}>Already have an account</AppButton>
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
