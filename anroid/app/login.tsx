import { router } from 'expo-router';
import { useState } from 'react';
import { Alert, ScrollView, StyleSheet, Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { AppButton, AppFooter, Field, Screen, TopBar, palette } from '@/components/app-ui';
import { useAuth } from '@/contexts/auth';

export default function LoginScreen() {
  const { signIn } = useAuth();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [loading, setLoading] = useState(false);

  const submit = async () => {
    setLoading(true);
    try {
      await signIn(email.trim(), password);
      router.replace('/dashboard');
    } catch (err) {
      Alert.alert('Sign in failed', err instanceof Error ? err.message : 'Please try again.');
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
            <Text style={styles.eyebrow}>Welcome back</Text>
            <Text style={styles.title}>
              <Text style={styles.white}>Sign in to </Text>
              <Text style={styles.orange}>Kenya </Text>
              <Text style={styles.green}>EduHub</Text>
            </Text>
            <Text style={styles.subtitle}>Use your verified account to access the mobile dashboard.</Text>
          </View>

          <View style={styles.form}>
            <Field label="Email address" icon="mail-outline" value={email} onChangeText={setEmail} keyboardType="email-address" placeholder="you@example.com" />
            <Field label="Password" icon="lock-closed-outline" value={password} onChangeText={setPassword} secureTextEntry placeholder="Your password" />
            <AppButton icon="log-in-outline" loading={loading} disabled={!email || !password} onPress={submit}>Sign in</AppButton>
          </View>

          <View style={styles.links}>
            <AppButton variant="ghost" onPress={() => router.push('/forgot-password')}>Forgot password</AppButton>
            <AppButton variant="secondary" icon="person-add-outline" onPress={() => router.push('/register')}>Create account</AppButton>
          </View>
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
  header: {
    gap: 8,
  },
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
  links: {
    gap: 10,
  },
  white: { color: '#FFFFFF' },
  orange: { color: palette.orange },
  green: { color: palette.green },
});
