import FontAwesome5 from '@expo/vector-icons/FontAwesome5';
import Ionicons from '@expo/vector-icons/Ionicons';
import { PropsWithChildren, ReactNode } from 'react';
import {
  ActivityIndicator,
  Pressable,
  StyleProp,
  StyleSheet,
  Text,
  TextInput,
  TextInputProps,
  View,
  ViewStyle,
} from 'react-native';

export const palette = {
  ink: '#FFFFFF',
  muted: '#CCCCCC',
  paper: '#000000',
  panel: '#1A1A1A',
  border: '#333333',
  orange: '#FF6B35',
  green: '#008000',
  gold: '#FFD700',
  goldDark: '#FFA500',
  red: '#D13438',
  blue: '#0078D4',
};

type ButtonProps = PropsWithChildren<{
  onPress?: () => void;
  variant?: 'primary' | 'secondary' | 'ghost';
  loading?: boolean;
  icon?: keyof typeof Ionicons.glyphMap;
  disabled?: boolean;
  style?: StyleProp<ViewStyle>;
}>;

export function AppButton({
  children,
  onPress,
  variant = 'primary',
  loading,
  icon,
  disabled,
  style,
}: ButtonProps) {
  return (
    <Pressable
      accessibilityRole="button"
      disabled={disabled || loading}
      onPress={onPress}
      style={({ pressed }) => [
        styles.button,
        variant === 'secondary' && styles.secondaryButton,
        variant === 'ghost' && styles.ghostButton,
        (pressed || disabled || loading) && styles.pressed,
        style,
      ]}>
      {loading ? (
        <ActivityIndicator color={variant === 'primary' ? '#FFFFFF' : palette.gold} />
      ) : (
        <>
          {icon ? (
            <Ionicons
              name={icon}
              size={18}
              color={variant === 'primary' ? '#FFFFFF' : palette.gold}
            />
          ) : null}
          <Text
            style={[
              styles.buttonText,
              variant !== 'primary' && styles.secondaryButtonText,
              variant === 'ghost' && styles.ghostButtonText,
            ]}>
            {children}
          </Text>
        </>
      )}
    </Pressable>
  );
}

export function Field({
  label,
  icon,
  style,
  ...props
}: TextInputProps & { label: string; icon?: keyof typeof Ionicons.glyphMap }) {
  return (
    <View style={styles.field}>
      <Text style={styles.label}>{label}</Text>
      <View style={styles.inputWrap}>
        {icon ? <Ionicons name={icon} size={18} color={palette.muted} /> : null}
        <TextInput
          placeholderTextColor="#9A9388"
          autoCapitalize="none"
          style={[styles.input, style]}
          {...props}
        />
      </View>
    </View>
  );
}

export function Screen({ children, style }: PropsWithChildren<{ style?: StyleProp<ViewStyle> }>) {
  return <View style={[styles.screen, style]}>{children}</View>;
}

export function BrandLogo({ compact = false }: { compact?: boolean }) {
  return (
    <View style={styles.logo}>
      <View style={[styles.logoMark, compact && styles.logoMarkCompact]}>
        <Text style={styles.logoLetters}>
          <Text style={styles.logoK}>K</Text>
          <Text style={styles.logoE}>E</Text>
        </Text>
      </View>
      {!compact ? (
        <Text style={styles.brandName}>
          <Text style={styles.brandKenya}>Kenya</Text>
          <Text> </Text>
          <Text style={styles.brandEduhub}>EduHub</Text>
        </Text>
      ) : null}
    </View>
  );
}

export function TopBar({ right }: { right?: ReactNode }) {
  return (
    <View style={styles.topBar}>
      <BrandLogo />
      {right}
    </View>
  );
}

export function AppFooter() {
  return (
    <View style={styles.footer}>
      <View style={styles.footerBrandRow}>
        <BrandLogo compact />
        <View style={styles.footerIconRow}>
          <View style={styles.footerIcon}>
            <FontAwesome5 name="book-open" size={14} color={palette.gold} />
          </View>
          <View style={styles.footerIcon}>
            <FontAwesome5 name="graduation-cap" size={14} color={palette.gold} />
          </View>
          <View style={styles.footerIcon}>
            <FontAwesome5 name="cloud-download-alt" size={14} color={palette.gold} />
          </View>
        </View>
      </View>
      <Text style={styles.footerText}>
        <Text style={styles.footerWhite}>Empowering education across </Text>
        <Text style={styles.brandKenya}>Kenya</Text>
      </Text>
      <Text style={styles.footerMeta}>Resources / Past Papers / Study Notes</Text>
    </View>
  );
}

export function FullScreenLoader() {
  return (
    <View style={styles.loaderScreen}>
      <BrandLogo />
      <View style={styles.loaderCard}>
        <ActivityIndicator color={palette.gold} size="large" />
        <Text style={styles.loaderTitle}>Loading Kenya EduHub</Text>
        <Text style={styles.loaderText}>Preparing your learning dashboard</Text>
      </View>
    </View>
  );
}

export function Badge({ children }: PropsWithChildren) {
  return (
    <View style={styles.badge}>
      <Text style={styles.badgeText}>{children}</Text>
    </View>
  );
}

export function Stat({ value, label }: { value: string | number; label: string }) {
  return (
    <View style={styles.stat}>
      <Text style={styles.statValue}>{value}</Text>
      <Text style={styles.statLabel}>{label}</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  screen: {
    flex: 1,
    backgroundColor: palette.paper,
  },
  loaderScreen: {
    flex: 1,
    backgroundColor: '#000000',
    alignItems: 'center',
    justifyContent: 'center',
    padding: 24,
    gap: 26,
  },
  loaderCard: {
    width: '100%',
    maxWidth: 340,
    borderRadius: 4,
    backgroundColor: palette.panel,
    borderColor: palette.border,
    borderWidth: 1,
    padding: 22,
    alignItems: 'center',
    gap: 10,
  },
  loaderTitle: {
    color: '#FFFFFF',
    fontWeight: '900',
    fontSize: 17,
    marginTop: 4,
  },
  loaderText: {
    color: palette.muted,
    fontWeight: '700',
    fontSize: 13,
    textAlign: 'center',
  },
  button: {
    minHeight: 52,
    borderRadius: 4,
    paddingHorizontal: 18,
    alignItems: 'center',
    justifyContent: 'center',
    flexDirection: 'row',
    gap: 8,
    backgroundColor: palette.blue,
  },
  secondaryButton: {
    backgroundColor: '#000000',
    borderColor: '#FFFFFF',
    borderWidth: 1,
  },
  ghostButton: {
    backgroundColor: 'transparent',
  },
  pressed: {
    opacity: 0.72,
  },
  buttonText: {
    color: '#FFFFFF',
    fontWeight: '800',
    fontSize: 15,
  },
  secondaryButtonText: {
    color: '#FFFFFF',
  },
  ghostButtonText: {
    color: palette.gold,
  },
  field: {
    gap: 8,
  },
  label: {
    color: palette.ink,
    fontWeight: '700',
    fontSize: 13,
  },
  inputWrap: {
    minHeight: 52,
    borderRadius: 4,
    borderWidth: 1,
    borderColor: palette.border,
    backgroundColor: palette.panel,
    paddingHorizontal: 14,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
  },
  input: {
    flex: 1,
    color: palette.ink,
    fontSize: 15,
  },
  topBar: {
    backgroundColor: '#000000',
    borderBottomColor: palette.gold,
    borderBottomWidth: 3,
    paddingHorizontal: 20,
    paddingVertical: 14,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: 12,
  },
  logo: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
  },
  logoMark: {
    width: 50,
    height: 50,
    borderRadius: 25,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: palette.gold,
    borderColor: palette.orange,
    borderWidth: 3,
  },
  logoMarkCompact: {
    width: 42,
    height: 42,
    borderRadius: 21,
  },
  logoLetters: {
    fontWeight: '900',
    lineHeight: 30,
  },
  logoK: {
    color: palette.orange,
    fontSize: 28,
    fontWeight: '900',
  },
  logoE: {
    color: palette.green,
    fontSize: 24,
    fontWeight: '900',
  },
  brandName: {
    fontWeight: '900',
    fontSize: 22,
  },
  brandKenya: {
    color: palette.orange,
    fontWeight: '900',
  },
  brandEduhub: {
    color: palette.green,
    fontWeight: '900',
  },
  footer: {
    backgroundColor: '#000000',
    borderTopColor: palette.border,
    borderTopWidth: 1,
    paddingTop: 18,
    paddingBottom: 4,
    gap: 10,
    alignItems: 'center',
  },
  footerBrandRow: {
    alignItems: 'center',
    gap: 12,
  },
  footerIconRow: {
    flexDirection: 'row',
    gap: 10,
  },
  footerIcon: {
    width: 32,
    height: 32,
    borderRadius: 16,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: palette.panel,
    borderColor: palette.border,
    borderWidth: 1,
  },
  footerText: {
    fontSize: 13,
    textAlign: 'center',
  },
  footerMeta: {
    color: '#B0B0B0',
    fontSize: 11,
    fontWeight: '700',
    letterSpacing: 0,
  },
  footerWhite: {
    color: '#FFFFFF',
  },
  badge: {
    alignSelf: 'flex-start',
    borderRadius: 999,
    backgroundColor: '#000000',
    borderColor: palette.gold,
    borderWidth: 1,
    paddingHorizontal: 10,
    paddingVertical: 5,
  },
  badgeText: {
    color: palette.gold,
    fontWeight: '800',
    fontSize: 12,
  },
  stat: {
    flex: 1,
    minHeight: 78,
    borderRadius: 4,
    backgroundColor: palette.panel,
    borderColor: palette.border,
    borderWidth: 1,
    padding: 12,
    justifyContent: 'center',
  },
  statValue: {
    color: palette.ink,
    fontWeight: '900',
    fontSize: 22,
  },
  statLabel: {
    color: palette.muted,
    fontWeight: '700',
    fontSize: 12,
    marginTop: 3,
  },
});
