import { View, Text, StyleSheet, TouchableOpacity, TextInput, ActivityIndicator, Alert, KeyboardAvoidingView, Platform } from 'react-native';
import { useRouter } from 'expo-router';
import { useState } from 'react';
import * as SecureStore from 'expo-secure-store';
import { login } from '../lib/api';

export default function Login() {
  const router = useRouter();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  const handleLogin = async () => {
    if (!email || !password) {
      setError('Please enter both email and password');
      return;
    }

    setLoading(true);
    setError('');

    try {
      const response = await login({ email, password });
      
      if (response.success && response.teacher) {
        // Save session data to SecureStore
        await SecureStore.setItemAsync('teacherSession', JSON.stringify({
          isLoggedIn: true,
          teacher_id: response.teacher.id,
          teacher_name: response.teacher.name,
          session_token: response.session_token,
          school_id: response.teacher.school_id,
          class_id: response.teacher.class_id,
          stream_id: response.teacher.stream_id,
          class_name: response.teacher.class_name,
          stream_name: response.teacher.stream_name
        }));
        
        router.replace('/dashboard');
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
    >
      <View style={styles.loginContainer}>
        {/* Logo */}
        <View style={styles.logo}>
          <View style={styles.logoCircle}>
            <Text style={styles.logoText}>
              <Text style={[styles.logoKE, { color: '#FF6B35', fontSize: 28 }]}>K</Text>
              <Text style={[styles.logoKE, { color: '#008000', fontSize: 24 }]}>E</Text>
            </Text>
          </View>
          <Text style={[styles.logoText, { color: '#FF6B35' }]}>Kenya</Text>
          <Text style={[styles.logoText, { color: '#008000' }]}>EduHub</Text>
        </View>

        {/* Header */}
        <Text style={styles.heading}>Teacher Portal</Text>
        <Text style={styles.subheading}>Kenya EduHub</Text>

        {/* Error Alert */}
        {error ? (
          <View style={styles.alert}>
            <Text style={styles.alertText}>{error}</Text>
            <TouchableOpacity onPress={() => setError('')}>
              <Text style={styles.alertClose}>×</Text>
            </TouchableOpacity>
          </View>
        ) : null}

        {/* Form */}
        <View style={styles.form}>
          <View style={styles.formGroup}>
            <Text style={styles.label}>Email</Text>
            <TextInput
              style={styles.input}
              placeholder="Email"
              value={email}
              onChangeText={setEmail}
              keyboardType="email-address"
              autoCapitalize="none"
              autoComplete="email"
            />
          </View>

          <View style={styles.formGroup}>
            <Text style={styles.label}>Password</Text>
            <TextInput
              style={styles.input}
              placeholder="Password"
              value={password}
              onChangeText={setPassword}
              secureTextEntry
              autoComplete="current-password"
            />
          </View>

          <TouchableOpacity 
            style={styles.button}
            onPress={handleLogin}
            disabled={loading}
          >
            {loading ? (
              <ActivityIndicator color="white" />
            ) : (
              <Text style={styles.buttonText}>Next</Text>
            )}
          </TouchableOpacity>
        </View>

        {/* Back Link */}
        <TouchableOpacity style={styles.backLink}>
          <Text style={styles.backLinkText}>← Back to Home</Text>
        </TouchableOpacity>
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
    padding: 40,
    justifyContent: 'center',
    alignItems: 'center',
  },
  logo: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 8,
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
    marginRight: 4,
  },
  logoText: {
    fontSize: 20,
    fontWeight: 'bold',
  },
  logoKE: {
    fontWeight: 'bold',
  },
  heading: {
    fontSize: 24,
    fontWeight: '400',
    color: '#202124',
    marginBottom: 8,
    textAlign: 'center',
  },
  subheading: {
    fontSize: 16,
    color: '#5f6368',
    marginBottom: 40,
    textAlign: 'center',
  },
  alert: {
    width: '100%',
    backgroundColor: '#fce8e6',
    borderWidth: 1,
    borderColor: '#f9dedc',
    borderRadius: 4,
    padding: 12,
    marginBottom: 24,
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  alertText: {
    color: '#c5221f',
    fontSize: 14,
    flex: 1,
  },
  alertClose: {
    color: '#c5221f',
    fontSize: 18,
    marginLeft: 8,
  },
  form: {
    width: '100%',
  },
  formGroup: {
    marginBottom: 24,
  },
  label: {
    fontSize: 12,
    fontWeight: '500',
    color: '#5f6368',
    marginBottom: 8,
  },
  input: {
    width: '100%',
    borderWidth: 1,
    borderColor: '#dadce0',
    borderRadius: 25,
    padding: 13,
    fontSize: 16,
    color: '#202124',
  },
  button: {
    width: '100%',
    backgroundColor: '#FF6B35',
    borderWidth: 2,
    borderColor: '#FF6B35',
    borderRadius: 25,
    padding: 12,
    alignItems: 'center',
    marginTop: 8,
  },
  buttonText: {
    color: 'white',
    fontSize: 14,
    fontWeight: '600',
  },
  backLink: {
    marginTop: 48,
  },
  backLinkText: {
    color: '#1a73e8',
    fontSize: 14,
  },
});
