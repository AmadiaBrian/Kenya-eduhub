import Ionicons from '@expo/vector-icons/Ionicons';
import { router } from 'expo-router';
import { useCallback, useEffect, useMemo, useState } from 'react';
import { ActivityIndicator, Alert, Linking, RefreshControl, ScrollView, StyleSheet, Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { AppButton, AppFooter, Field, Screen, Stat, TopBar, palette } from '@/components/app-ui';
import { useAuth } from '@/contexts/auth';
import { Resource, deleteResource, getResources, resourceDownloadUrl, updateDownloadCount } from '@/lib/api';

export default function DashboardScreen() {
  const { isAuthenticated, user } = useAuth();
  const [resources, setResources] = useState<Resource[]>([]);
  const [query, setQuery] = useState('');
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState('');

  useEffect(() => {
    if (!isAuthenticated) {
      router.replace('/login');
    }
  }, [isAuthenticated]);

  const loadResources = useCallback(async () => {
    setError('');
    try {
      setResources(await getResources());
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Could not load resources.');
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, []);

  useEffect(() => {
    loadResources();
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
    </Screen>
  );
}

function ResourceCard({
  resource,
  canManage,
  onChanged,
}: {
  resource: Resource;
  canManage: boolean;
  onChanged: () => void;
}) {
  const [opening, setOpening] = useState(false);
  const [deleting, setDeleting] = useState(false);

  const openResource = async () => {
    setOpening(true);
    try {
      await updateDownloadCount(resource.id);
      await Linking.openURL(resourceDownloadUrl(resource.id));
      onChanged();
    } catch {
      await Linking.openURL(resourceDownloadUrl(resource.id));
    } finally {
      setOpening(false);
    }
  };

  const confirmDelete = () => {
    Alert.alert('Delete resource', `Delete "${resource.title || 'this resource'}"?`, [
      { text: 'Cancel', style: 'cancel' },
      {
        text: 'Delete',
        style: 'destructive',
        onPress: async () => {
          setDeleting(true);
          try {
            const response = await deleteResource(resource.id);
            Alert.alert('Resource deleted', response.message || 'Resource deleted successfully.');
            onChanged();
          } catch (err) {
            Alert.alert('Delete failed', err instanceof Error ? err.message : 'Please try again.');
          } finally {
            setDeleting(false);
          }
        },
      },
    ]);
  };

  return (
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
        </View>
      </View>
      {resource.description ? <Text style={styles.description} numberOfLines={3}>{resource.description}</Text> : null}
      <View style={styles.cardFooter}>
        <Text style={styles.downloads}>{Number(resource.downloads ?? 0)} downloads</Text>
        <View style={styles.cardActions}>
          {canManage ? (
            <AppButton icon="trash-outline" variant="ghost" loading={deleting} onPress={confirmDelete} style={styles.smallButton}>
              Delete
            </AppButton>
          ) : null}
          <AppButton icon="cloud-download-outline" variant="secondary" loading={opening} onPress={openResource} style={styles.downloadButton}>
            Open
          </AppButton>
        </View>
      </View>
    </View>
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
    backgroundColor: palette.panel,
    borderWidth: 1,
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
});
