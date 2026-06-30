import Ionicons from '@expo/vector-icons/Ionicons';
import { useEffect, useState } from 'react';
import { ActivityIndicator, Linking, Modal, Pressable, StyleSheet, Text, View } from 'react-native';
import * as FileSystem from 'expo-file-system/legacy';
import * as Sharing from 'expo-sharing';

import { AppButton, palette } from './app-ui';

type DownloadModalProps = {
  visible: boolean;
  downloadUrl: string;
  filename: string;
  onClose: () => void;
  onComplete?: (uri: string) => void;
  onError?: (error: string) => void;
  resourceTitle?: string | null;
};

export function DownloadModal({
  visible,
  downloadUrl,
  filename,
  onClose,
  onComplete,
  onError,
  resourceTitle,
}: DownloadModalProps) {
  const [progress, setProgress] = useState(0);
  const [status, setStatus] = useState<'downloading' | 'success' | 'error'>('downloading');
  const [downloadedUri, setDownloadedUri] = useState<string | null>(null);
  const [errorMessage, setErrorMessage] = useState('');

  useEffect(() => {
    if (visible) {
      setStatus('downloading');
      setProgress(0);
      setDownloadedUri(null);
      setErrorMessage('');
      downloadToDownloads();
    }
  }, [visible]);

  const downloadToDownloads = async () => {
    setProgress(0);
    setErrorMessage('');

    try {
      console.log('Starting download:', { downloadUrl, filename });
      
      // Extract just the filename (basename) from the path
      const cleanFilename = filename.split('/').pop() || filename;
      console.log('Clean filename:', cleanFilename);
      
      // Download to cache with proper filename
      const cacheUri = FileSystem.documentDirectory + cleanFilename;
      console.log('Cache URI:', cacheUri);
      
      const downloadResumable = FileSystem.createDownloadResumable(
        downloadUrl,
        cacheUri,
        {
          headers: {
            'Accept': 'application/octet-stream',
          },
        },
        (downloadProgressData) => {
          const progress = downloadProgressData.totalBytesWritten / downloadProgressData.totalBytesExpectedToWrite;
          setProgress(Math.round(progress * 100));
          console.log('Download progress:', Math.round(progress * 100) + '%');
        }
      );

      const result = await downloadResumable.downloadAsync();
      console.log('Download result:', result);
      
      if (!result) {
        throw new Error('Download failed');
      }

      setDownloadedUri(result.uri);
      setStatus('success');
      onComplete?.(result.uri);
      console.log('Download complete, file saved at:', result.uri);

      // Check if sharing is available
      const isAvailable = await Sharing.isAvailableAsync();
      console.log('Sharing available:', isAvailable);
      
      if (!isAvailable) {
        throw new Error('Sharing is not available on this device');
      }

      // Open share sheet so user can save to Downloads
      console.log('Opening share sheet...');
      await Sharing.shareAsync(result.uri, {
        mimeType: 'application/octet-stream',
        dialogTitle: `Save ${cleanFilename}`,
      });
      console.log('Share sheet opened successfully');
    } catch (error) {
      console.error('Download error:', error);
      const errorMsg = error instanceof Error ? error.message : 'Download failed';
      setErrorMessage(errorMsg);
      setStatus('error');
      onError?.(errorMsg);
    }
  };

  const handleClose = () => {
    if (status === 'downloading') {
      return;
    }
    onClose();
    setProgress(0);
    setStatus('downloading');
    setDownloadedUri(null);
    setErrorMessage('');
  };

  return (
    <Modal
      visible={visible}
      transparent
      animationType="fade"
      onRequestClose={handleClose}>
      <Pressable style={styles.overlay} onPress={status === 'downloading' ? undefined : handleClose}>
        <View style={styles.container} onStartShouldSetResponder={() => true}>
          {/* Header */}
          <View style={styles.header}>
            {status === 'downloading' && (
              <View style={styles.iconContainer}>
                <ActivityIndicator color={palette.gold} size="large" />
              </View>
            )}
            {status === 'success' && (
              <View style={[styles.iconContainer, styles.successIcon]}>
                <Ionicons name="checkmark-circle" size={48} color={palette.green} />
              </View>
            )}
            {status === 'error' && (
              <View style={[styles.iconContainer, styles.errorIcon]}>
                <Ionicons name="close-circle" size={48} color={palette.red} />
              </View>
            )}
          </View>

          {/* Content */}
          <View style={styles.content}>
            <Text style={styles.title}>
              {status === 'downloading' && 'Downloading Your Resource'}
              {status === 'success' && 'Download Complete!'}
              {status === 'error' && 'Download Failed'}
            </Text>

            {status === 'downloading' && (
              <>
                <Text style={styles.filename}>{resourceTitle ?? filename}</Text>
                <View style={styles.progressContainer}>
                  <View style={styles.progressBar}>
                    <View style={[styles.progressFill, { width: `${progress}%` }]} />
                  </View>
                  <Text style={styles.progressText}>{progress}%</Text>
                </View>
                <Text style={styles.hintText}>Preparing to share...</Text>
              </>
            )}

            {status === 'success' && (
              <>
                <Text style={styles.message}>
                  Download complete! Choose where to save your file.
                </Text>
                <View style={styles.fileInfo}>
                  <Ionicons name="document-text-outline" size={16} color={palette.muted} />
                  <Text style={styles.fileInfoText}>{filename}</Text>
                </View>
              </>
            )}

            {status === 'error' && (
              <>
                <Text style={styles.errorMessage}>
                  We couldn't complete the download. Please check your internet connection and try again.
                </Text>
                {errorMessage && <Text style={styles.errorDetail}>{errorMessage}</Text>}
              </>
            )}
          </View>

          {/* Footer */}
          <View style={styles.footer}>
            {status !== 'downloading' && (
              <AppButton onPress={handleClose} style={styles.closeButton}>
                {status === 'success' ? 'Done' : 'Try Again'}
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
    backgroundColor: palette.panel,
    borderColor: palette.border,
    borderWidth: 2,
  },
  successIcon: {
    borderColor: palette.green,
    backgroundColor: 'rgba(0, 128, 0, 0.1)',
  },
  errorIcon: {
    borderColor: palette.red,
    backgroundColor: 'rgba(209, 52, 56, 0.1)',
  },
  content: {
    padding: 24,
    gap: 16,
    alignItems: 'center',
  },
  title: {
    color: palette.ink,
    fontWeight: '900',
    fontSize: 20,
    textAlign: 'center',
  },
  filename: {
    color: palette.muted,
    fontWeight: '700',
    fontSize: 14,
    textAlign: 'center',
  },
  progressContainer: {
    width: '100%',
    gap: 8,
  },
  progressBar: {
    height: 8,
    borderRadius: 4,
    backgroundColor: '#000000',
    overflow: 'hidden',
  },
  progressFill: {
    height: '100%',
    backgroundColor: palette.gold,
    borderRadius: 4,
  },
  progressText: {
    color: palette.gold,
    fontWeight: '900',
    fontSize: 16,
    textAlign: 'center',
  },
  message: {
    color: palette.muted,
    fontWeight: '600',
    fontSize: 14,
    textAlign: 'center',
    lineHeight: 22,
  },
  fileInfo: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    backgroundColor: '#000000',
    paddingHorizontal: 12,
    paddingVertical: 10,
    borderRadius: 4,
    borderWidth: 1,
    borderColor: palette.border,
  },
  fileInfoText: {
    color: palette.ink,
    fontWeight: '700',
    fontSize: 13,
    flex: 1,
  },
  errorMessage: {
    color: palette.red,
    fontWeight: '600',
    fontSize: 14,
    textAlign: 'center',
  },
  footer: {
    padding: 20,
    backgroundColor: '#000000',
    borderTopColor: palette.border,
    borderTopWidth: 1,
    alignItems: 'center',
    gap: 12,
  },
  hintText: {
    color: palette.muted,
    fontWeight: '700',
    fontSize: 13,
    textAlign: 'center',
  },
  directoryOptions: {
    width: '100%',
    gap: 12,
  },
  directoryButton: {
    width: '100%',
    minHeight: 56,
    justifyContent: 'flex-start',
    paddingHorizontal: 16,
    gap: 12,
  },
  directoryButtonText: {
    color: '#FFFFFF',
    fontWeight: '800',
    fontSize: 15,
  },
  errorDetail: {
    color: palette.muted,
    fontWeight: '600',
    fontSize: 12,
    textAlign: 'center',
    marginTop: 8,
  },
  viewButton: {
    width: '100%',
  },
  closeButton: {
    width: '100%',
  },
  cancelButton: {
    width: '100%',
  },
});
