import * as SecureStore from 'expo-secure-store';
import { ApiUser, login as apiLogin } from '@/lib/api';
import { createContext, PropsWithChildren, useContext, useEffect, useMemo, useState } from 'react';

type AuthContextValue = {
  user: ApiUser | null;
  isAuthenticated: boolean;
  signIn: (email: string, password: string) => Promise<ApiUser>;
  signOut: () => void;
  setUser: (user: ApiUser | null) => void;
};

const AuthContext = createContext<AuthContextValue | null>(null);

const SESSION_KEY = 'kenya_eduhub_session';
const SESSION_ID_KEY = 'kenya_eduhub_session_id';
const CSRF_TOKEN_KEY = 'kenya_eduhub_csrf_token';

export function AuthProvider({ children }: PropsWithChildren) {
  const [user, setUser] = useState<ApiUser | null>(null);

  // Load session from SecureStore on mount
  useEffect(() => {
    loadSession();
  }, []);

  const loadSession = async () => {
    try {
      const sessionData = await SecureStore.getItemAsync(SESSION_KEY);
      if (sessionData) {
        const parsedUser = JSON.parse(sessionData);
        setUser(parsedUser);
      }
    } catch (error) {
      console.error('Failed to load session:', error);
    }
  };

  const saveSession = async (userData: ApiUser) => {
    try {
      await SecureStore.setItemAsync(SESSION_KEY, JSON.stringify(userData));
    } catch (error) {
      console.error('Failed to save session:', error);
    }
  };

  const clearSession = async () => {
    try {
      await SecureStore.deleteItemAsync(SESSION_KEY);
      await SecureStore.deleteItemAsync(SESSION_ID_KEY);
      await SecureStore.deleteItemAsync(CSRF_TOKEN_KEY);
    } catch (error) {
      console.error('Failed to clear session:', error);
    }
  };

  const value = useMemo<AuthContextValue>(
    () => ({
      user,
      isAuthenticated: Boolean(user),
      async signIn(email, password) {
        const response = await apiLogin(email, password);
        setUser(response.user);
        await saveSession(response.user);
        return response.user;
      },
      signOut() {
        setUser(null);
        clearSession();
      },
      setUser,
    }),
    [user],
  );

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth() {
  const value = useContext(AuthContext);

  if (!value) {
    throw new Error('useAuth must be used inside AuthProvider');
  }

  return value;
}
