import Ionicons from '@expo/vector-icons/Ionicons';
import { router } from 'expo-router';
import { useCallback, useEffect, useMemo, useState } from 'react';
import { ActivityIndicator, AppState, AppStateStatus, RefreshControl, ScrollView, StyleSheet, Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import * as SecureStore from 'expo-secure-store';
import * as WebBrowser from 'expo-web-browser';

import { AppButton, AppFooter, Field, Screen, Stat, TopBar, palette } from '@/components/app-ui';
import { DownloadModal } from '@/components/download-modal';
import { AlertModal } from '@/components/alert-modal';
import { UploadModal } from '@/components/upload-modal';
import { EditModal } from '@/components/edit-modal';
import { useAuth } from '@/contexts/auth';
import { Resource, deleteResource, getResources, resourceDownloadUrl, updateDownloadCount, uploadResource, updateResource } from '@/lib/api';

export default function DashboardScreen() {
  const { isAuthenticated, user } = useAuth();
  const [resources, setResources] = useState<Resource[]>([]);
  const [query, setQuery] = useState('');
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState('');
  const [uploadModalVisible, setUploadModalVisible] = useState(false);
  const [alertModal, setAlertModal] = useState<{
    visible: boolean;
    title: string;
    message: string;
    type: 'success' | 'error' | 'info' | 'warning';
    primaryButton?: { text: string; onPress: () => void };
    secondaryButton?: { text: string; onPress: () => void };
  }>({ visible: false, title: '', message: '', type: 'info' });

  const showAlert = (title: string, message: string, type: 'success' | 'error' | 'info' | 'warning' = 'info', primaryButton?: { text: string; onPress: () => void }, secondaryButton?: { text: string; onPress: () => void }) => {
    setAlertModal({ visible: true, title, message, type, primaryButton, secondaryButton });
  };

  const checkCSRFToken = useCallback(async () => {
    const csrfToken = await SecureStore.getItemAsync('kenya_eduhub_csrf_token');
    if (!csrfToken) {
      showAlert(
        'Session Expired',
        'Your session has expired. Please log in again.',
        'warning',
        { text: 'Log In', onPress: () => router.replace('/login') }
      );
      return false;
    }
    return true;
  }, []);

  useEffect(() => {
    if (!isAuthenticated) {
      router.replace('/login');
      return;
    }
    
    // Check CSRF token when dashboard loads
    checkCSRFToken();
  }, [isAuthenticated, checkCSRFToken]);

  const loadResources = useCallback(async () => {
    setError('');
    try {
      const fetchedResources = await getResources();
      // Mark resources as my upload and add uploader info
      const resourcesWithUploadInfo = fetchedResources.map(resource => ({
        ...resource,
        is_my_upload: resource.user_id === user?.id,
        uploader_name: resource.is_my_upload ? 'You' : resource.uploader_name || 'Unknown',
      }));
      setResources(resourcesWithUploadInfo);
    } catch (err) {
      const errorMessage = err instanceof Error ? err.message : 'Could not load resources.';
      
      // Check if it's a network error
      const isNetworkError = errorMessage.includes('Network request failed') || 
                            errorMessage.includes('fetch') ||
                            errorMessage.includes('connection') ||
                            errorMessage.includes('timeout');
      
      if (isNetworkError) {
        showAlert(
          'Network Error',
          'Please check your connection and try again.',
          'error',
          { text: 'Retry', onPress: () => loadResources() },
          { text: 'Cancel', onPress: () => {} }
        );
      } else {
        setError(errorMessage);
      }
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, [user?.id]);

  useEffect(() => {
    loadResources();
    
    let pollingInterval: ReturnType<typeof setInterval> | null = null;
    
    const startPolling = () => {
      // Only poll when app is active - every 2 minutes for scalability
      pollingInterval = setInterval(() => {
        loadResources();
      }, 120000); // 2 minutes
    };
    
    const stopPolling = () => {
      if (pollingInterval) {
        clearInterval(pollingInterval);
        pollingInterval = null;
      }
    };
    
    // Handle app state changes
    const subscription = AppState.addEventListener('change', (nextAppState: AppStateStatus) => {
      if (nextAppState === 'active') {
        loadResources(); // Refresh immediately when app becomes active
        startPolling();
      } else {
        stopPolling(); // Stop polling when app goes to background
      }
    });
    
    // Start initial polling
    startPolling();
    
    return () => {
      stopPolling();
      subscription.remove();
    };
  }, [loadResources]);

  const filteredResources = useMemo(() => {
    const term = query.trim().toLowerCase();

    if (!term) {
      return resources;
    }

    return resources.filter((resource) =>
      [resource.title, resource.subject, resource.level, resource.type, resource.description]
        .filter(Boolean)
        .some((value) => String(value).toLowerCase().includes(term)),
    );
  }, [query, resources]);

  const subjects = new Set(resources.map((resource) => resource.subject).filter(Boolean)).size;
  const downloads = resources.reduce((total, resource) => total + Number(resource.downloads ?? 0), 0);
  const myUploadCount = resources.filter(r => r.is_my_upload).length;

  const handleUpload = async (data: {
    title: string;
    level: string;
    subject: string;
    type: string;
    description: string;
    file: any;
  }) => {
    // Check CSRF token before upload
    const hasValidToken = await checkCSRFToken();
    if (!hasValidToken) {
      return;
    }

    const formData = new FormData();
    formData.append('title', data.title);
    formData.append('level', data.level);
    formData.append('subject', data.subject);
    formData.append('type', data.type);
    formData.append('description', data.description);
    formData.append('file', {
      uri: data.file.uri,
      type: data.file.mimeType,
      name: data.file.name,
    } as any);

    try {
      await uploadResource(formData);
      await loadResources();
      showAlert(
        'Upload Successful',
        'Your resource has been uploaded successfully.',
        'success',
        { text: 'OK', onPress: () => {} }
      );
    } catch (error) {
      const errorMessage = error instanceof Error ? error.message : 'Upload failed. Please try again.';
      
      // Check if it's a CSRF token error
      if (errorMessage.includes('security token') || errorMessage.includes('expired')) {
        await SecureStore.deleteItemAsync('kenya_eduhub_csrf_token');
        showAlert(
          'Session Expired',
          'Your security token has expired. Please log in again.',
          'warning',
          { text: 'Log In', onPress: () => router.replace('/login') }
        );
        return;
      }
      
      // Check if it's a network error
      const isNetworkError = errorMessage.includes('Network request failed') || 
                            errorMessage.includes('fetch') ||
                            errorMessage.includes('connection') ||
                            errorMessage.includes('timeout');
      
      if (isNetworkError) {
        showAlert(
          'Network Error',
          'Please check your connection and try again.',
          'error',
          { text: 'Retry', onPress: () => handleUpload(data) },
          { text: 'Cancel', onPress: () => {} }
        );
      } else {
        showAlert(
          'Upload Failed',
          errorMessage,
          'error',
          { text: 'OK', onPress: () => {} }
        );
      }
    }
  };

  if (!isAuthenticated) {
    return (
      <Screen>
        <SafeAreaView style={styles.safe}>
          <TopBar />
          <View style={styles.centerState}>
            <ActivityIndicator color={palette.gold} />
            <Text style={styles.stateText}>Redirecting to sign in...</Text>
          </View>
        </SafeAreaView>
      </Screen>
    );
  }

  return (
    <Screen>
      <SafeAreaView style={styles.safe}>
        <TopBar
          right={undefined}
        />
        <ScrollView
          contentContainerStyle={styles.content}
          refreshControl={
            <RefreshControl
              refreshing={refreshing}
              onRefresh={() => {
                setRefreshing(true);
                loadResources();
              }}
            />
          }>
          <View style={styles.header}>
            <View>
              <Text style={styles.kicker}>{isAuthenticated ? `Welcome, ${user?.name}` : 'Guest dashboard'}</Text>
              <Text style={styles.title}>
                <Text style={styles.white}>Resource </Text>
                <Text style={styles.orange}>Dashboard</Text>
              </Text>
            </View>
          </View>

          <View style={styles.statsRow}>
            <Stat value={resources.length} label="resources" />
            <Stat value={subjects} label="subjects" />
            <Stat value={downloads} label="downloads" />
          </View>

          <AppButton
            icon="cloud-upload-outline"
            variant="secondary"
            onPress={() => setUploadModalVisible(true)}
            style={styles.uploadButton}
          >
            Upload New Resource
          </AppButton>

          <Field
            label="Search library"
            icon="search-outline"
            placeholder="Subject, level, title..."
            value={query}
            onChangeText={setQuery}
          />

          {loading ? (
            <View style={styles.centerState}>
              <ActivityIndicator color={palette.green} />
              <Text style={styles.stateText}>Loading resources...</Text>
            </View>
          ) : error ? (
            <View style={styles.centerState}>
              <Ionicons name="alert-circle-outline" size={28} color={palette.red} />
              <Text style={styles.errorText}>{error}</Text>
              <AppButton variant="secondary" onPress={loadResources}>Try again</AppButton>
            </View>
          ) : (
            <View style={styles.resourceList}>
              {filteredResources.map((resource) => (
                <ResourceCard
                  key={String(resource.id)}
                  canManage={user?.role === 'admin'}
                  resource={resource}
                  onChanged={loadResources}
                  myUploadCount={myUploadCount}
                />
              ))}
              {filteredResources.length === 0 ? (
                <Text style={styles.stateText}>No resources match your search.</Text>
              ) : null}
            </View>
          )}
          <AppFooter />
        </ScrollView>
      </SafeAreaView>
      <UploadModal
        visible={uploadModalVisible}
        onClose={() => setUploadModalVisible(false)}
        onUpload={handleUpload}
      />
      <AlertModal
        visible={alertModal.visible}
        title={alertModal.title}
        message={alertModal.message}
        type={alertModal.type}
        primaryButton={alertModal.primaryButton}
        secondaryButton={alertModal.secondaryButton}
        onClose={() => setAlertModal({ visible: false, title: '', message: '', type: 'info' })}
      />
    </Screen>
  );
}

function ResourceCard({
  resource,
  canManage,
  onChanged,
  myUploadCount,
}: {
  resource: Resource;
  canManage: boolean;
  onChanged: () => void;
  myUploadCount: number;
}) {
  const [opening, setOpening] = useState(false);
  const [deleting, setDeleting] = useState(false);
  const [downloadModalVisible, setDownloadModalVisible] = useState(false);
  const [editModalVisible, setEditModalVisible] = useState(false);
  const [alertModal, setAlertModal] = useState<{
    visible: boolean;
    title: string;
    message: string;
    type: 'success' | 'error' | 'info' | 'warning';
    primaryButton?: { text: string; onPress: () => void };
    secondaryButton?: { text: string; onPress: () => void };
  }>({ visible: false, title: '', message: '', type: 'info' });

  const showAlert = (title: string, message: string, type: 'success' | 'error' | 'info' | 'warning' = 'info', primaryButton?: { text: string; onPress: () => void }, secondaryButton?: { text: string; onPress: () => void }) => {
    setAlertModal({ visible: true, title, message, type, primaryButton, secondaryButton });
  };

  const openResource = async () => {
    // Check if user has uploaded at least 2 resources or if this is their own upload
    if (myUploadCount < 2 && !resource.is_my_upload) {
      showAlert(
        'Download Restricted',
        'You need to upload at least 2 resources before you can download. Upload more resources to unlock downloads.',
        'warning',
        { text: 'Upload Resource', onPress: () => {} }
      );
      return;
    }
    
    setOpening(true);
    setDownloadModalVisible(true);
  };

  const handleDownloadModalClose = () => {
    setDownloadModalVisible(false);
    setOpening(false);
  };

  const openFileDirectly = async () => {
    setOpening(true);
    try {
      await WebBrowser.openBrowserAsync(resourceDownloadUrl(resource.id), {
        presentationStyle: WebBrowser.WebBrowserPresentationStyle.FULL_SCREEN,
      });
    } catch (error) {
      console.error('Failed to open file:', error);
    } finally {
      setOpening(false);
    }
  };

  const handleUpdate = async (data: {
    id: number | string;
    title: string;
    level: string;
    subject: string;
    type: string;
    description: string;
  }) => {
    await updateResource(data);
    onChanged();
  };

  const confirmDelete = () => {
    showAlert(
      'Delete Resource',
      `Are you sure you want to delete "${resource.title || 'this resource'}"? This action cannot be undone.`,
      'warning',
      {
        text: 'Delete',
        onPress: async () => {
          setDeleting(true);
          try {
            const response = await deleteResource(resource.id);
            showAlert(
              'Resource Deleted',
              response.message || 'The resource has been successfully deleted.',
              'success'
            );
            onChanged();
          } catch (err) {
            showAlert(
              'Delete Failed',
              err instanceof Error ? err.message : 'We couldn\'t delete the resource. Please try again.',
              'error'
            );
          } finally {
            setDeleting(false);
          }
        }
      },
      {
        text: 'Cancel',
        onPress: () => {}
      }
    );
  };

  return (
    <>
      <View style={styles.card}>
        <View style={styles.cardTop}>
          <View style={styles.fileIcon}>
            <Ionicons name="document-text-outline" size={22} color={palette.green} />
          </View>
          <View style={styles.cardTitleWrap}>
            <Text style={styles.cardTitle}>{resource.title || 'Untitled resource'}</Text>
            <Text style={styles.meta}>
              {[resource.level, resource.subject, resource.type].filter(Boolean).join(' / ') || 'Learning resource'}
            </Text>
            <Text style={styles.uploaderInfo}>
              Uploaded by <Text style={styles.uploaderName}>{resource.uploader_name || 'Unknown'}</Text>
            </Text>
          </View>
          {resource.is_my_upload && (
            <View style={styles.myUploadBadge}>
              <Text style={styles.myUploadBadgeText}>My Upload</Text>
            </View>
          )}
        </View>
        {resource.description ? <Text style={styles.description} numberOfLines={3}>{resource.description}</Text> : null}
        <View style={styles.cardFooter}>
          <Text style={styles.downloads}>{Number(resource.downloads ?? 0)} downloads</Text>
          <View style={styles.cardActions}>
            {canManage ? (
              <>
                <AppButton icon="create-outline" variant="ghost" onPress={() => setEditModalVisible(true)} style={styles.smallButton}>
                  Edit
                </AppButton>
                <AppButton icon="trash-outline" variant="ghost" loading={deleting} onPress={confirmDelete} style={styles.smallButton}>
                  Delete
                </AppButton>
              </>
            ) : null}
            <AppButton icon="eye-outline" variant="ghost" onPress={openFileDirectly} style={styles.smallButton}>
              Open
            </AppButton>
            <AppButton icon="cloud-download-outline" variant="secondary" loading={opening} onPress={openResource} style={styles.downloadButton}>
              Download
            </AppButton>
          </View>
        </View>
      </View>
      <DownloadModal
        visible={downloadModalVisible}
        downloadUrl={resourceDownloadUrl(resource.id)}
        filename={resource.filename || 'download'}
        resourceTitle={resource.title || undefined}
        onClose={handleDownloadModalClose}
        onComplete={() => {
          handleDownloadModalClose();
          onChanged();
        }}
      />
      <AlertModal
        visible={alertModal.visible}
        title={alertModal.title}
        message={alertModal.message}
        type={alertModal.type}
        primaryButton={alertModal.primaryButton}
        secondaryButton={alertModal.secondaryButton}
        onClose={() => setAlertModal({ ...alertModal, visible: false })}
      />
      <EditModal
        visible={editModalVisible}
        resource={resource}
        onClose={() => setEditModalVisible(false)}
        onUpdate={handleUpdate}
      />
    </>
  );
}

const styles = StyleSheet.create({
  safe: {
    flex: 1,
  },
  content: {
    padding: 20,
    paddingBottom: 34,
    gap: 18,
  },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: 14,
  },
  kicker: {
    color: palette.gold,
    fontWeight: '900',
    fontSize: 13,
  },
  title: {
    color: palette.ink,
    fontSize: 28,
    fontWeight: '900',
    marginTop: 4,
  },
  iconButton: {
    width: 46,
    height: 46,
    borderRadius: 4,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: '#1A1A1A',
    borderWidth: 1,
    borderColor: palette.border,
  },
  statsRow: {
    flexDirection: 'row',
    gap: 10,
  },
  centerState: {
    minHeight: 220,
    alignItems: 'center',
    justifyContent: 'center',
    gap: 12,
  },
  stateText: {
    color: palette.muted,
    textAlign: 'center',
  },
  errorText: {
    color: palette.red,
    textAlign: 'center',
    lineHeight: 21,
  },
  resourceList: {
    gap: 12,
  },
  card: {
    borderRadius: 4,
    backgroundColor: 'transparent',
    borderWidth: 0,
    borderColor: palette.border,
    padding: 14,
    gap: 12,
  },
  cardTop: {
    flexDirection: 'row',
    gap: 12,
  },
  fileIcon: {
    width: 42,
    height: 42,
    borderRadius: 4,
    backgroundColor: '#000000',
    borderColor: palette.blue,
    borderWidth: 1,
    alignItems: 'center',
    justifyContent: 'center',
  },
  cardTitleWrap: {
    flex: 1,
    gap: 3,
  },
  cardTitle: {
    color: palette.ink,
    fontWeight: '900',
    fontSize: 16,
  },
  meta: {
    color: palette.muted,
    fontSize: 12,
    fontWeight: '700',
  },
  uploaderInfo: {
    color: palette.muted,
    fontSize: 11,
    fontWeight: '600',
  },
  uploaderName: {
    color: palette.gold,
    fontWeight: '800',
  },
  myUploadBadge: {
    backgroundColor: palette.green,
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 4,
  },
  myUploadBadgeText: {
    color: '#FFFFFF',
    fontSize: 10,
    fontWeight: '800',
  },
  description: {
    color: palette.muted,
    lineHeight: 20,
  },
  cardFooter: {
    gap: 12,
  },
  cardActions: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'flex-end',
    gap: 12,
  },
  downloads: {
    color: palette.gold,
    fontWeight: '900',
  },
  downloadButton: {
    minHeight: 42,
    paddingHorizontal: 14,
  },
  smallButton: {
    minHeight: 42,
    paddingHorizontal: 12,
  },
  white: {
    color: '#FFFFFF',
  },
  orange: {
    color: palette.orange,
  },
  uploadButton: {
    width: '100%',
  },
});
