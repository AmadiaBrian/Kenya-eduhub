import { View, Text, StyleSheet, TouchableOpacity, TextInput, ActivityIndicator, Alert, KeyboardAvoidingView, Platform } from 'react-native';
import { useRouter } from 'expo-router';
import { useState } from 'react';
import * as SecureStore from 'expo-secure-store';
import { login } from '../lib/api';

export default function Login() {
  const router = useRouter();
  const [email, setEmail] = useState('');
  const [identifier, setIdentifier] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  const handleLogin = async () => {
    if (!email || !identifier) {
      setError('Please enter both email and phone number/ID number');
      return;
    }

    setLoading(true);
    setError('');

    try {
      const response = await login({ email, identifier });
      
      if (response.success) {
        // Save session data to SecureStore
        await SecureStore.setItemAsync('userSession', JSON.stringify({
          isLoggedIn: true,
          email: email,
          identifier: identifier,
          session_id: response.session_id,
          session_token: response.session_token,
          parent_name: response.parent?.name,
          school_name: response.parent?.school_name
        }));
        
        router.replace('/(tabs)/dashboard');
      } else {
        setError(response.error || 'Login failed');
      }
    } catch (err) {
      setError(err instanceof Error ? err.message : 'An error occurred. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <KeyboardAvoidingView 
      style={styles.container}
      behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
      keyboardVerticalOffset={Platform.OS === 'ios' ? 0 : 20}
    >
      <View style={styles.loginContainer}>
        <View style={styles.content}>
          <View style={styles.logo}>
            <View style={styles.logoCircle}>
              <Text style={styles.logoText}>
                <Text style={[styles.logoKE, { color: '#FF6B35', fontSize: 28 }]}>K</Text>
                <Text style={[styles.logoKE, { color: '#008000', fontSize: 24 }]}>E</Text>
              </Text>
            </View>
            <Text style={[styles.logoBrand, { color: '#FF6B35' }]}>Kenya</Text>
            <Text style={[styles.logoBrand, { color: '#008000' }]}>EduHub</Text>
          </View>

          <Text style={styles.subtitle}>Log in to continue to your account</Text>

          {error ? (
            <View style={styles.alert}>
              <Text style={styles.alertText}>{error}</Text>
              <TouchableOpacity onPress={() => setError('')}>
                <Text style={styles.alertClose}>×</Text>
              </TouchableOpacity>
            </View>
          ) : null}

          <View style={styles.form}>
            <View style={styles.formGroup}>
              <Text style={styles.label}>Email Address</Text>
              <TextInput
                style={styles.input}
                placeholder="Enter your email"
                placeholderTextColor="#9aa0a6"
                value={email}
                onChangeText={setEmail}
                keyboardType="email-address"
                autoCapitalize="none"
                autoCorrect={false}
              />
            </View>

            <View style={styles.formGroup}>
              <Text style={styles.label}>Phone Number or ID Number</Text>
              <TextInput
                style={styles.input}
                placeholder="Enter phone number or ID number"
                placeholderTextColor="#9aa0a6"
                value={identifier}
                onChangeText={setIdentifier}
                keyboardType="default"
                autoCapitalize="none"
                autoCorrect={false}
              />
            </View>

            <TouchableOpacity 
              style={styles.button}
              onPress={handleLogin}
              disabled={loading}
            >
              <Text style={styles.buttonText}>
                {loading ? 'Loading...' : 'Login'}
              </Text>
            </TouchableOpacity>

            <TouchableOpacity onPress={() => router.replace('/')}>
              <Text style={styles.link}>← Back to Home</Text>
            </TouchableOpacity>
          </View>
        </View>

        <View style={styles.footer}>
          <Text style={styles.footerText}>
            <Text style={styles.copyright}>&copy; 2026</Text>
            <Text style={styles.kenyaFooter}> Kenya</Text>
            <Text style={styles.eduhubFooter}>EduHub</Text>
            <Text style={styles.eduhubFooter}>. All rights reserved.</Text>
          </Text>
        </View>
      </View>
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#ffffff',
  },
  loginContainer: {
    flex: 1,
    paddingHorizontal: 40,
    paddingTop: 48,
    paddingBottom: 0,
    maxWidth: 450,
    width: '100%',
    alignSelf: 'center',
  },
  content: {
    flex: 1,
    justifyContent: 'center',
  },
  logo: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    marginBottom: 8,
    alignSelf: 'center',
  },
  logoCircle: {
    width: 50,
    height: 50,
    backgroundColor: '#FFD700',
    borderWidth: 3,
    borderColor: '#FF6B35',
    borderRadius: 25,
    justifyContent: 'center',
    alignItems: 'center',
  },
  logoText: {
    fontWeight: 'bold',
    fontSize: 24,
  },
  logoKE: {
    fontWeight: 'bold',
  },
  logoBrand: {
    fontSize: 16,
    fontWeight: 'bold',
  },
  title: {
    fontSize: 24,
    fontWeight: '400',
    color: '#202124',
    textAlign: 'center',
    marginBottom: 8,
  },
  subtitle: {
    fontSize: 16,
    color: '#5f6368',
    textAlign: 'center',
    marginBottom: 40,
  },
  form: {
    width: '100%',
  },
  formGroup: {
    marginBottom: 24,
    width: '100%',
  },
  label: {
    fontSize: 12,
    fontWeight: '500',
    color: '#5f6368',
    marginBottom: 8,
    letterSpacing: 0.3,
    textAlign: 'left',
  },
  input: {
    width: '100%',
    padding: 13,
    fontSize: 16,
    borderWidth: 1,
    borderColor: '#dadce0',
    borderRadius: 25,
    color: '#202124',
  },
  button: {
    width: '100%',
    padding: 12,
    backgroundColor: '#FF6B35',
    borderRadius: 25,
    alignItems: 'center',
    marginTop: 8,
    borderWidth: 2,
    borderColor: '#FF6B35',
  },
  buttonText: {
    color: '#ffffff',
    fontSize: 14,
    fontWeight: '600',
  },
  link: {
    color: '#FF6B35',
    fontSize: 14,
    textAlign: 'center',
    marginTop: 48,
  },
  alert: {
    width: '100%',
    padding: 12,
    borderRadius: 25,
    marginBottom: 24,
    backgroundColor: '#fce8e6',
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    borderWidth: 1,
    borderColor: '#f9dedc',
  },
  alertText: {
    fontSize: 14,
    color: '#c5221f',
    flex: 1,
  },
  alertClose: {
    fontSize: 18,
    color: '#c5221f',
    opacity: 0.7,
  },
  footer: {
    padding: 16,
    paddingBottom: 32,
  },
  footerText: {
    fontSize: 12,
    textAlign: 'center',
  },
  copyright: {
    color: '#FF6B35',
  },
  kenyaFooter: {
    color: '#FF6B35',
  },
  eduhubFooter: {
    color: '#008000',
  },
});
