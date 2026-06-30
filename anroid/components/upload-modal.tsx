import Ionicons from '@expo/vector-icons/Ionicons';
import { useState } from 'react';
import { ActivityIndicator, Modal, Pressable, ScrollView, StyleSheet, Text, TextInput, View } from 'react-native';
import * as DocumentPicker from 'expo-document-picker';

import { AppButton, Field, palette } from './app-ui';

const LEVELS = ['Primary School', 'Secondary School', 'College', 'University'];
const TYPES = ['PDF', 'DOC', 'PPT', 'XLS'];

type PickerItem = {
  label: string;
  value: string;
};

function SimplePicker({
  label,
  icon,
  items,
  selectedValue,
  onSelect,
}: {
  label: string;
  icon: keyof typeof Ionicons.glyphMap;
  items: string[];
  selectedValue: string;
  onSelect: (value: string) => void;
}) {
  const [showPicker, setShowPicker] = useState(false);

  return (
    <View style={pickerStyles.container}>
      <Text style={pickerStyles.label}>{label}</Text>
      <Pressable
        style={pickerStyles.pickerButton}
        onPress={() => setShowPicker(!showPicker)}>
        <Ionicons name={icon} size={18} color={palette.muted} />
        <Text style={pickerStyles.pickerText}>
          {selectedValue || `Select ${label.toLowerCase()}`}
        </Text>
        <Ionicons name="chevron-down-outline" size={18} color={palette.muted} />
      </Pressable>
      {showPicker && (
        <View style={pickerStyles.pickerDropdown}>
          {items.map((item) => (
            <Pressable
              key={item}
              style={[
                pickerStyles.pickerItem,
                selectedValue === item && pickerStyles.pickerItemSelected,
              ]}
              onPress={() => {
                onSelect(item);
                setShowPicker(false);
              }}>
              <Text
                style={[
                  pickerStyles.pickerItemText,
                  selectedValue === item && pickerStyles.pickerItemTextSelected,
                ]}>
                {item}
              </Text>
              {selectedValue === item && (
                <Ionicons name="checkmark" size={16} color={palette.gold} />
              )}
            </Pressable>
          ))}
        </View>
      )}
    </View>
  );
}

const pickerStyles = StyleSheet.create({
  container: {
    gap: 8,
  },
  label: {
    color: palette.ink,
    fontWeight: '700',
    fontSize: 13,
  },
  pickerButton: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    backgroundColor: 'transparent',
    borderColor: palette.border,
    borderWidth: 1,
    borderRadius: 26,
    paddingHorizontal: 14,
    paddingVertical: 12,
  },
  pickerText: {
    flex: 1,
    color: palette.ink,
    fontSize: 15,
  },
  pickerDropdown: {
    backgroundColor: 'transparent',
    borderColor: palette.border,
    borderWidth: 1,
    borderRadius: 26,
    marginTop: 4,
    maxHeight: 200,
    overflow: 'hidden',
  },
  pickerItem: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 14,
    paddingVertical: 12,
    borderBottomWidth: 1,
    borderBottomColor: palette.border,
  },
  pickerItemSelected: {
    backgroundColor: 'rgba(255, 215, 0, 0.1)',
  },
  pickerItemText: {
    color: palette.ink,
    fontSize: 15,
  },
  pickerItemTextSelected: {
    color: palette.gold,
    fontWeight: '700',
  },
});

type UploadModalProps = {
  visible: boolean;
  onClose: () => void;
  onUpload: (data: {
    title: string;
    level: string;
    subject: string;
    type: string;
    description: string;
    file: DocumentPicker.DocumentPickerAsset;
  }) => Promise<void>;
};

export function UploadModal({ visible, onClose, onUpload }: UploadModalProps) {
  const [title, setTitle] = useState('');
  const [level, setLevel] = useState('');
  const [subject, setSubject] = useState('');
  const [type, setType] = useState('');
  const [description, setDescription] = useState('');
  const [file, setFile] = useState<DocumentPicker.DocumentPickerAsset | null>(null);
  const [loading, setLoading] = useState(false);

  const selectFile = async () => {
    try {
      const result = await DocumentPicker.getDocumentAsync({
        type: [
          'application/pdf',
          'application/msword',
          'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
          'application/vnd.ms-powerpoint',
          'application/vnd.openxmlformats-officedocument.presentationml.presentation',
          'application/vnd.ms-excel',
          'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ],
        copyToCacheDirectory: true,
      });

      if (!result.canceled && result.assets.length > 0) {
        setFile(result.assets[0]);
      }
    } catch (error) {
      console.error('Error selecting file:', error);
    }
  };

  const handleUpload = async () => {
    if (!file) {
      return;
    }

    if (!title || !level || !subject || !type || !description) {
      return;
    }

    setLoading(true);
    try {
      await onUpload({
        title,
        level,
        subject,
        type,
        description,
        file,
      });
      onClose();
      resetForm();
    } catch (error) {
      console.error('Upload error:', error);
    } finally {
      setLoading(false);
    }
  };

  const resetForm = () => {
    setTitle('');
    setLevel('');
    setSubject('');
    setType('');
    setDescription('');
    setFile(null);
  };

  const handleClose = () => {
    if (loading) return;
    onClose();
    resetForm();
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
              <Ionicons name="cloud-upload-outline" size={48} color={palette.gold} />
            </View>
          </View>

          {/* Content */}
          <ScrollView style={styles.content} showsVerticalScrollIndicator={false}>
            <Text style={styles.title}>Upload Resource</Text>
            <Text style={styles.subtitle}>Share educational materials with the community</Text>

            <View style={styles.form}>
              <Field
                label="Resource title"
                icon="document-text-outline"
                value={title}
                onChangeText={setTitle}
                placeholder="e.g., KCSE Mathematics Paper 1"
              />
              
              <SimplePicker
                label="Education level"
                icon="school-outline"
                items={LEVELS}
                selectedValue={level}
                onSelect={setLevel}
              />

              <Field
                label="Subject"
                icon="book-outline"
                value={subject}
                onChangeText={setSubject}
                placeholder="e.g., Mathematics, Physics"
              />

              <SimplePicker
                label="Resource type"
                icon="file-tray-outline"
                items={TYPES}
                selectedValue={type}
                onSelect={setType}
              />

              <View style={styles.fileSection}>
                <Text style={styles.fileLabel}>Select file</Text>
                {file ? (
                  <View style={styles.selectedFile}>
                    <Ionicons name="document-text" size={20} color={palette.green} />
                    <Text style={styles.fileName} numberOfLines={1}>{file.name}</Text>
                    <AppButton
                      icon="close-circle"
                      variant="ghost"
                      onPress={() => setFile(null)}
                      style={styles.removeButton}
                    >
                      Remove
                    </AppButton>
                  </View>
                ) : (
                  <AppButton
                    icon="folder-open-outline"
                    variant="secondary"
                    onPress={selectFile}
                    style={styles.selectButton}
                  >
                    Choose File (PDF, DOC, PPT, XLS)
                  </AppButton>
                )}
              </View>

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
              icon="cloud-upload-outline"
              loading={loading}
              disabled={!file || !title || !level || !subject || !type || !description}
              onPress={handleUpload}
              style={styles.uploadButton}
            >
              Upload Resource
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
    backgroundColor: 'transparent',
    borderColor: palette.border,
    borderWidth: 0,
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
    backgroundColor: 'transparent',
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
  fileSection: {
    gap: 8,
  },
  fileLabel: {
    color: palette.ink,
    fontWeight: '700',
    fontSize: 13,
  },
  selectedFile: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    backgroundColor: '#000000',
    paddingHorizontal: 12,
    paddingVertical: 10,
    borderRadius: 4,
    borderWidth: 1,
    borderColor: palette.border,
  },
  fileName: {
    color: '#FFFFFF',
    fontWeight: '700',
    fontSize: 13,
    flex: 1,
  },
  removeButton: {
    minHeight: 32,
    paddingHorizontal: 8,
  },
  selectButton: {
    width: '100%',
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
    backgroundColor: palette.panel,
    borderColor: palette.border,
    borderWidth: 1,
    borderRadius: 4,
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
  uploadButton: {
    width: '100%',
  },
  cancelButton: {
    width: '100%',
  },
});
