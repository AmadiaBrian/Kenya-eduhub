import { createContext, useContext, useState, useEffect, ReactNode } from 'react';

// Define theme colors
export const lightTheme = {
  background: '#f8f9fa',
  cardBackground: '#ffffff',
  text: '#202124',
  textSecondary: '#5f6368',
  border: '#dadce0',
  primary: '#FF6B35',
  secondary: '#008000',
  accent: '#1a73e8',
  error: '#c5221f',
  success: '#137333',
  warning: '#b06000',
  headerBackground: '#ffffff',
  logoPrimary: '#FF6B35',
  logoSecondary: '#008000',
  inputBackground: '#ffffff',
  buttonText: '#ffffff',
  placeholder: '#9aa0a6',
  divider: '#e0e0e0',
  shadow: 'rgba(0, 0, 0, 0.1)',
};

export const darkTheme = {
  background: '#1a1a1a',
  cardBackground: '#2d2d2d',
  text: '#e8eaed',
  textSecondary: '#9aa0a6',
  border: '#5f6368',
  primary: '#FF6B35',
  secondary: '#008000',
  accent: '#8ab4f8',
  error: '#f28b82',
  success: '#81c995',
  warning: '#fdd663',
  headerBackground: '#2d2d2d',
  logoPrimary: '#FF6B35',
  logoSecondary: '#008000',
  inputBackground: '#2d2d2d',
  buttonText: '#e8eaed',
  placeholder: '#5f6368',
  divider: '#3c4043',
  shadow: 'rgba(0, 0, 0, 0.3)',
};

export type Theme = typeof lightTheme;

interface ThemeContextType {
  theme: Theme;
  isDark: boolean;
  toggleTheme: () => void;
}

const ThemeContext = createContext<ThemeContextType | undefined>(undefined);

export const ThemeProvider = ({ children }: { children: ReactNode }) => {
  const [isDark, setIsDark] = useState(false);

  useEffect(() => {
    // Check system preference or saved preference
    const loadThemePreference = async () => {
      try {
        // For now, default to light mode
        // In the future, you could use AsyncStorage to save preference
        setIsDark(false);
      } catch (error) {
        console.error('Failed to load theme preference:', error);
      }
    };
    loadThemePreference();
  }, []);

  const toggleTheme = () => {
    setIsDark(!isDark);
  };

  const theme = isDark ? darkTheme : lightTheme;

  return (
    <ThemeContext.Provider value={{ theme, isDark, toggleTheme }}>
      {children}
    </ThemeContext.Provider>
  );
};

export const useTheme = () => {
  const context = useContext(ThemeContext);
  if (context === undefined) {
    throw new Error('useTheme must be used within a ThemeProvider');
  }
  return context;
};