import { ApiUser, login as apiLogin } from '@/lib/api';
import { createContext, PropsWithChildren, useContext, useMemo, useState } from 'react';

type AuthContextValue = {
  user: ApiUser | null;
  isAuthenticated: boolean;
  signIn: (email: string, password: string) => Promise<ApiUser>;
  signOut: () => void;
};

const AuthContext = createContext<AuthContextValue | null>(null);

export function AuthProvider({ children }: PropsWithChildren) {
  const [user, setUser] = useState<ApiUser | null>(null);

  const value = useMemo<AuthContextValue>(
    () => ({
      user,
      isAuthenticated: Boolean(user),
      async signIn(email, password) {
        const response = await apiLogin(email, password);
        setUser(response.user);
        return response.user;
      },
      signOut() {
        setUser(null);
      },
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
