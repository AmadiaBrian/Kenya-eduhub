import { router } from 'expo-router';
import { useState } from 'react';
import { Alert, ScrollView, StyleSheet, Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { AppButton, AppFooter, Field, Screen, TopBar, palette } from '@/components/app-ui';
import { register } from '@/lib/api';

export default function RegisterScreen() {
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [loading, setLoading] = useState(false);

  const submit = async () => {
    setLoading(true);
    try {
      const response = await register(name.trim(), email.trim(), password);
      Alert.alert('Account created', response.message || 'Check your email for the verification code.');
      router.replace({ pathname: '/verify', params: { email: email.trim() } });
    } catch (err) {
      Alert.alert('Registration failed', err instanceof Error ? err.message : 'Please try again.');
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
