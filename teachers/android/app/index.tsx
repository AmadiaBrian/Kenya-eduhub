import { View, Text, StyleSheet, TouchableOpacity, ActivityIndicator, Animated } from 'react-native';
import { useRouter } from 'expo-router';
import { useState, useEffect, useRef } from 'react';
import * as SecureStore from 'expo-secure-store';

export default function Index() {
  const router = useRouter();
  const [loading, setLoading] = useState(true);
  const [showWelcome, setShowWelcome] = useState(false);
  const fadeAnim = useRef(new Animated.Value(0)).current;
  const slideAnim = useRef(new Animated.Value(50)).current;

  useEffect(() => {
    // Check for existing session
    const checkSession = async () => {
      try {
        const sessionData = await SecureStore.getItemAsync('teacherSession');
        if (sessionData) {
          const session = JSON.parse(sessionData);
          if (session.isLoggedIn) {
            // Valid session exists, go directly to dashboard
            router.replace('/(tabs)/dashboard');
            return;
          }
        }
      } catch (error) {
        // Ignore errors
      }
      
      // No valid session, show loading then welcome screen
      const timer = setTimeout(() => {
        setLoading(false);
        setShowWelcome(true);
        // Trigger animations when loading completes
        Animated.parallel([
          Animated.timing(fadeAnim, {
            toValue: 1,
            duration: 800,
            useNativeDriver: true,
          }),
          Animated.timing(slideAnim, {
            toValue: 0,
            duration: 800,
            useNativeDriver: true,
          }),
        ]).start();
      }, 3000);

      return () => clearTimeout(timer);
    };

    checkSession();
  }, [fadeAnim, slideAnim, router]);

  if (loading) {
    return (
      <View style={styles.loadingContainer}>
        <View style={styles.loadingContent}>
          <View style={styles.loadingLogoContainer}>
            <View style={styles.loadingLogo}>
              <View style={styles.loadingLogoIcon}>
                <Text style={styles.loadingLogoText}>
                  <Text style={[styles.loadingLogoKE, { color: '#FF6B35', fontSize: 28 }]}>K</Text>
                  <Text style={[styles.loadingLogoKE, { color: '#008000', fontSize: 24 }]}>E</Text>
                </Text>
              </View>
              <Text style={styles.loadingKenyaText}>Kenya</Text>
              <Text style={styles.loadingEduhubText}>EduHub</Text>
            </View>
          </View>
          <ActivityIndicator size="large" color="#FF6B35" style={styles.spinner} />
          <Text style={styles.loadingText}>Loading your teaching portal...</Text>
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
    );
  }

  if (!showWelcome) {
    return null; // Session exists, redirecting to dashboard
  }

  return (
    <View style={styles.container}>
      <Animated.View style={[
        styles.content,
        {
          opacity: fadeAnim,
          transform: [{ translateX: slideAnim }]
        }
      ]}>
        <View style={styles.logoContainer}>
          <View style={styles.logo}>
            <View style={styles.logoIcon}>
              <Text style={styles.logoText}>
                <Text style={[styles.logoKE, { color: '#FF6B35', fontSize: 28 }]}>K</Text>
                <Text style={[styles.logoKE, { color: '#008000', fontSize: 24 }]}>E</Text>
              </Text>
            </View>
            <Text style={styles.kenyaText}>Kenya</Text>
            <Text style={styles.eduhubText}>EduHub</Text>
          </View>
        </View>

        <Text style={styles.welcomeText}>Your gateway to classroom management</Text>

        <TouchableOpacity 
          style={styles.button}
          onPress={() => router.push('/login')}
        >
          <Text style={styles.buttonText}>Login</Text>
        </TouchableOpacity>
      </Animated.View>

      <View style={styles.footer}>
        <Text style={styles.footerText}>
          <Text style={styles.copyright}>&copy; 2026</Text>
          <Text style={styles.kenyaFooter}> Kenya</Text>
          <Text style={styles.eduhubFooter}>EduHub</Text>
          <Text style={styles.eduhubFooter}>. All rights reserved.</Text>
        </Text>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#ffffff',
  },
  loadingContainer: {
    flex: 1,
    backgroundColor: '#ffffff',
    justifyContent: 'center',
    alignItems: 'center',
  },
  loadingContent: {
    alignItems: 'center',
    flex: 1,
    justifyContent: 'center',
  },
  loadingLogoContainer: {
    alignItems: 'center',
    marginBottom: 32,
  },
  loadingLogo: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
  },
  loadingLogoIcon: {
    width: 50,
    height: 50,
    backgroundColor: '#FFD700',
    borderWidth: 3,
    borderColor: '#FF6B35',
    borderRadius: 25,
    justifyContent: 'center',
    alignItems: 'center',
  },
  loadingLogoText: {
    fontWeight: 'bold',
    fontSize: 20,
  },
  loadingLogoKE: {
    fontWeight: 'bold',
  },
  loadingKenyaText: {
    color: '#FF6B35',
    fontWeight: 'bold',
    fontSize: 16,
  },
  loadingEduhubText: {
    color: '#008000',
    fontWeight: 'bold',
    fontSize: 16,
  },
  spinner: {
    marginTop: 20,
  },
  loadingText: {
    fontSize: 16,
    color: '#5f6368',
    marginTop: 16,
  },
  content: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    width: '100%',
  },
  logoContainer: {
    alignItems: 'center',
    marginBottom: 48,
  },
  logo: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
  },
  logoIcon: {
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
    fontSize: 20,
  },
  logoKE: {
    fontWeight: 'bold',
  },
  kenyaText: {
    color: '#FF6B35',
    fontWeight: 'bold',
    fontSize: 16,
  },
  eduhubText: {
    color: '#008000',
    fontWeight: 'bold',
    fontSize: 16,
  },
  welcomeText: {
    fontSize: 16,
    color: '#5f6368',
    marginBottom: 32,
  },
  button: {
    backgroundColor: '#FF6B35',
    paddingHorizontal: 40,
    paddingVertical: 15,
    borderRadius: 25,
    marginBottom: 24,
    width: '80%',
    alignItems: 'center',
  },
  buttonText: {
    color: '#ffffff',
    fontSize: 16,
    fontWeight: '600',
  },
  footer: {
    padding: 16,
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
    fontWeight: 'bold',
  },
  eduhubFooter: {
    color: '#008000',
    fontWeight: 'bold',
  },
});
