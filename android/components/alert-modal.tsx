import Ionicons from '@expo/vector-icons/Ionicons';
import { Modal, Pressable, StyleSheet, Text, View } from 'react-native';

import { AppButton, palette } from './app-ui';

type AlertType = 'success' | 'error' | 'info' | 'warning';

type AlertModalProps = {
  visible: boolean;
  title: string;
  message: string;
  type?: AlertType;
  primaryButton?: {
    text: string;
    onPress: () => void;
  };
  secondaryButton?: {
    text: string;
    onPress: () => void;
  };
  onClose?: () => void;
};

export function AlertModal({
  visible,
  title,
  message,
  type = 'info',
  primaryButton,
  secondaryButton,
  onClose,
}: AlertModalProps) {
  const getIcon = () => {
    switch (type) {
      case 'success':
        return 'checkmark-circle';
      case 'error':
        return 'close-circle';
      case 'warning':
        return 'warning';
      default:
        return 'information-circle';
    }
  };

  const getIconColor = () => {
    switch (type) {
      case 'success':
        return palette.green;
      case 'error':
        return palette.red;
      case 'warning':
        return palette.gold;
      default:
        return palette.blue;
    }
  };

  const getIconBgColor = () => {
    switch (type) {
      case 'success':
        return 'rgba(0, 128, 0, 0.1)';
      case 'error':
        return 'rgba(209, 52, 56, 0.1)';
      case 'warning':
        return 'rgba(255, 215, 0, 0.1)';
      default:
        return 'rgba(0, 120, 212, 0.1)';
    }
  };

  const handlePrimaryPress = () => {
    primaryButton?.onPress();
    onClose?.();
  };

  const handleSecondaryPress = () => {
    secondaryButton?.onPress();
    onClose?.();
  };

  const handleClose = () => {
    if (!primaryButton && !secondaryButton) {
      onClose?.();
    }
  };

  return (
    <Modal
      visible={visible}
      transparent
      animationType="fade"
      onRequestClose={handleClose}>
      <Pressable style={styles.overlay} onPress={handleClose}>
        <View style={styles.container} onStartShouldSetResponder={() => true}>
          {/* Header */}
          <View style={styles.header}>
            <View style={[styles.iconContainer, { backgroundColor: getIconBgColor(), borderColor: getIconColor() }]}>
              <Ionicons name={getIcon()} size={48} color={getIconColor()} />
            </View>
          </View>

          {/* Content */}
          <View style={styles.content}>
            <Text style={styles.title}>{title}</Text>
            <Text style={styles.message}>{message}</Text>
          </View>

          {/* Footer */}
          <View style={styles.footer}>
            {secondaryButton && (
              <AppButton
                variant="ghost"
                onPress={handleSecondaryPress}
                style={styles.button}>
                {secondaryButton.text}
              </AppButton>
            )}
            {primaryButton && (
              <AppButton
                onPress={handlePrimaryPress}
                style={styles.button}>
                {primaryButton.text}
              </AppButton>
            )}
            {!primaryButton && !secondaryButton && (
              <AppButton onPress={handleClose} style={styles.button}>
                OK
              </AppButton>
            )}
          </View>
        </View>
      </Pressable>
    </Modal>
  );
}

const styles = StyleSheet.create({
  overlay: {
    flex: 1,
    backgroundColor: 'rgba(0, 0, 0, 0.85)',
    alignItems: 'center',
    justifyContent: 'center',
    padding: 24,
  },
  container: {
    width: '100%',
    maxWidth: 380,
    borderRadius: 8,
    backgroundColor: palette.panel,
    borderColor: palette.border,
    borderWidth: 1,
    overflow: 'hidden',
  },
  header: {
    backgroundColor: '#000000',
    borderBottomColor: palette.border,
    borderBottomWidth: 1,
    paddingVertical: 28,
    alignItems: 'center',
    justifyContent: 'center',
  },
  iconContainer: {
    width: 64,
    height: 64,
    borderRadius: 32,
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 2,
  },
  content: {
    padding: 24,
    gap: 12,
    alignItems: 'center',
  },
  title: {
    color: palette.ink,
    fontWeight: '900',
    fontSize: 20,
    textAlign: 'center',
  },
  message: {
    color: palette.muted,
    fontWeight: '600',
    fontSize: 14,
    textAlign: 'center',
    lineHeight: 22,
  },
  footer: {
    padding: 20,
    backgroundColor: '#000000',
    borderTopColor: palette.border,
    borderTopWidth: 1,
    gap: 12,
  },
  button: {
    width: '100%',
  },
});
