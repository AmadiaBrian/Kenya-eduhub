import Ionicons from '@expo/vector-icons/Ionicons';
import { useState } from 'react';
import { ActivityIndicator, Modal, Pressable, ScrollView, StyleSheet, Text, TextInput, View } from 'react-native';

import { AppButton, Field, palette } from './app-ui';
import { Resource } from '@/lib/api';

type EditModalProps = {
  visible: boolean;
  resource: Resource;
  onClose: () => void;
  onUpdate: (data: {
    id: number | string;
    title: string;
    level: string;
    subject: string;
    type: string;
    description: string;
  }) => Promise<void>;
};

export function EditModal({ visible, resource, onClose, onUpdate }: EditModalProps) {
  const [title, setTitle] = useState(resource.title || '');
  const [level, setLevel] = useState(resource.level || '');
  const [subject, setSubject] = useState(resource.subject || '');
  const [type, setType] = useState(resource.type || '');
  const [description, setDescription] = useState(resource.description || '');
  const [loading, setLoading] = useState(false);

  const handleUpdate = async () => {
    if (!title || !level || !subject || !type || !description) {
      return;
    }

    setLoading(true);
    try {
      await onUpdate({
        id: resource.id,
        title,
        level,
        subject,
        type,
        description,
      });
      onClose();
    } catch (error) {
      console.error('Update error:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleClose = () => {
    if (loading) return;
    onClose();
    // Reset to original values
    setTitle(resource.title || '');
    setLevel(resource.level || '');
    setSubject(resource.subject || '');
    setType(resource.type || '');
    setDescription(resource.description || '');
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
            <View style={styles.iconContainer}>
              <Ionicons name="create-outline" size={48} color={palette.gold} />
            </View>
          </View>

          {/* Content */}
          <ScrollView style={styles.content} showsVerticalScrollIndicator={false}>
            <Text style={styles.title}>Edit Resource</Text>
            <Text style={styles.subtitle}>Update resource information</Text>

            <View style={styles.form}>
              <Field
                label="Resource title"
                icon="document-text-outline"
                value={title}
                onChangeText={setTitle}
                placeholder="e.g., KCSE Mathematics Paper 1"
              />
              
              <Field
                label="Education level"
                icon="school-outline"
                value={level}
                onChangeText={setLevel}
                placeholder="e.g., High School, College"
              />
              
              <Field
                label="Subject"
                icon="book-outline"
                value={subject}
                onChangeText={setSubject}
                placeholder="e.g., Mathematics, Physics"
              />
              
              <Field
                label="Resource type"
                icon="file-tray-outline"
                value={type}
                onChangeText={setType}
                placeholder="e.g., Past Paper, Notes, Guide"
              />

              <View style={styles.descriptionSection}>
                <Text style={styles.descriptionLabel}>Description</Text>
                <TextInput
                  style={styles.descriptionInput}
                  placeholder="Describe the resource content..."
                  value={description}
                  onChangeText={setDescription}
                  multiline
                  numberOfLines={4}
                  textAlignVertical="top"
                />
              </View>
            </View>
          </ScrollView>

          {/* Footer */}
          <View style={styles.footer}>
            <AppButton
              icon="checkmark-circle-outline"
              loading={loading}
              disabled={!title || !level || !subject || !type || !description}
              onPress={handleUpdate}
              style={styles.updateButton}
            >
              Update Resource
            </AppButton>
            <AppButton
              variant="ghost"
              onPress={handleClose}
              disabled={loading}
              style={styles.cancelButton}
            >
              Cancel
            </AppButton>
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
    maxWidth: 420,
    maxHeight: '90%',
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
    backgroundColor: palette.panel,
    borderColor: palette.gold,
    borderWidth: 2,
  },
  content: {
    padding: 24,
    gap: 16,
  },
  title: {
    color: palette.ink,
    fontWeight: '900',
    fontSize: 20,
    textAlign: 'center',
  },
  subtitle: {
    color: palette.muted,
    fontWeight: '600',
    fontSize: 14,
    textAlign: 'center',
  },
  form: {
    gap: 14,
  },
  descriptionSection: {
    gap: 8,
  },
  descriptionLabel: {
    color: palette.ink,
    fontWeight: '700',
    fontSize: 13,
  },
  descriptionInput: {
    backgroundColor: 'transparent',
    borderColor: palette.border,
    borderWidth: 1,
    borderRadius: 26,
    paddingHorizontal: 14,
    paddingVertical: 12,
    color: palette.ink,
    fontSize: 15,
    minHeight: 100,
  },
  footer: {
    padding: 20,
    backgroundColor: '#000000',
    borderTopColor: palette.border,
    borderTopWidth: 1,
    gap: 12,
  },
  updateButton: {
    width: '100%',
  },
  cancelButton: {
    width: '100%',
  },
});
