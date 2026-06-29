import Ionicons from '@expo/vector-icons/Ionicons';
import { router } from 'expo-router';
import { useEffect, useMemo, useState } from 'react';
import { ScrollView, StyleSheet, Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { AppButton, AppFooter, Badge, BrandLogo, Screen, Stat, TopBar, palette } from '@/components/app-ui';
import { useAuth } from '@/contexts/auth';
import { Resource, getResources } from '@/lib/api';

const highlights = [
  { icon: 'library-outline', title: 'Study library', body: 'Browse notes, past papers, schemes, guides, and classroom materials.' },
  { icon: 'search-outline', title: 'Smart discovery', body: 'Search by title, subject, level, document type, or description.' },
  { icon: 'cloud-download-outline', title: 'Direct access', body: 'Open resources from the same PHP backend used by the web dashboard.' },
] as const;

export default function HomeScreen() {
  const { isAuthenticated, user } = useAuth();
  const [resources, setResources] = useState<Resource[]>([]);
  const [apiStatus, setApiStatus] = useState<'loading' | 'online' | 'offline'>('loading');

  useEffect(() => {
    let mounted = true;

    getResources()
      .then((items) => {
        if (mounted) {
          setResources(items);
          setApiStatus('online');
        }
      })
      .catch(() => {
        if (mounted) {
          setApiStatus('offline');
        }
      });

    return () => {
      mounted = false;
    };
  }, []);

  const subjects = useMemo(
    () => [...new Set(resources.map((resource) => resource.subject).filter(Boolean).map(String))].slice(0, 6),
    [resources],
  );
  const downloads = resources.reduce((total, resource) => total + Number(resource.downloads ?? 0), 0);
  const latestResources = resources.slice(0, 3);

  return (
    <Screen>
      <SafeAreaView style={styles.safe}>
        <TopBar
          right={
            <View style={[styles.statusPill, apiStatus === 'offline' && styles.statusOffline]}>
              <View style={[styles.statusDot, apiStatus === 'offline' && styles.statusDotOffline]} />
              <Text style={styles.statusText}>{apiStatus === 'online' ? 'API online' : apiStatus === 'offline' ? 'API offline' : 'Checking'}</Text>
            </View>
          }
        />
        <ScrollView contentContainerStyle={styles.content} showsVerticalScrollIndicator={false}>
          <View style={styles.hero}>
            <View style={styles.topRow}>
              <BrandLogo compact />
              <Badge>{isAuthenticated ? user?.role ?? 'Dashboard' : 'Mobile learning'}</Badge>
            </View>

            <Text style={styles.title}>
              <Text style={styles.white}>Free </Text>
              <Text style={styles.orange}>Educational </Text>
              <Text style={styles.gold}>Resources</Text>
              <Text style={styles.white}> & Past Papers in </Text>
              <Text style={styles.orange}>Kenya</Text>
            </Text>
            <Text style={styles.subtitle}>
              Kenya EduHub gives students and teachers a fast mobile path to free KCSE, KCPE, college, and technical learning resources.
            </Text>

            <View style={styles.actions}>
              <AppButton
                icon="grid-outline"
                onPress={() => router.push('/dashboard')}>
                Browse resources
              </AppButton>
              <AppButton
                icon={isAuthenticated ? 'person-circle-outline' : 'log-in-outline'}
                variant="secondary"
                onPress={() => router.push(isAuthenticated ? '/account' : '/login')}>
                {isAuthenticated ? 'My account' : 'Sign in'}
              </AppButton>
            </View>
          </View>

          <View style={styles.statsRow}>
            <Stat value={resources.length || '--'} label="resources" />
            <Stat value={downloads || '--'} label="downloads" />
            <Stat value={subjects.length || '--'} label="subjects" />
          </View>

          <View style={styles.quickPanel}>
            <View style={styles.quickItem}>
              <Ionicons name="school-outline" size={22} color={palette.gold} />
              <Text style={styles.quickTitle}>For learners</Text>
              <Text style={styles.quickText}>Find notes and revision files quickly.</Text>
            </View>
            <View style={styles.quickItem}>
              <Ionicons name="people-outline" size={22} color={palette.gold} />
              <Text style={styles.quickTitle}>For teachers</Text>
              <Text style={styles.quickText}>Share and organize class resources.</Text>
            </View>
          </View>

          <View style={styles.sectionHeader}>
            <Text style={styles.sectionTitle}>
              <Text style={styles.gold}>Access </Text>
              <Text style={styles.orange}>thousands </Text>
              <Text style={styles.white}>of resources</Text>
            </Text>
            <Text style={styles.sectionText}>Live data comes directly from your `api/resources.php` backend endpoint.</Text>
          </View>

          {subjects.length > 0 ? (
            <View style={styles.chipWrap}>
              {subjects.map((subject) => (
                <View key={subject} style={styles.chip}>
                  <Text style={styles.chipText}>{subject}</Text>
                </View>
              ))}
            </View>
          ) : null}

          {latestResources.length > 0 ? (
            <View style={styles.previewPanel}>
              <View style={styles.previewHeader}>
                <Text style={styles.previewTitle}>Latest uploads</Text>
                <Text style={styles.previewLink} onPress={() => router.push('/dashboard')}>View all</Text>
              </View>
              {latestResources.map((resource) => (
                <View key={String(resource.id)} style={styles.previewRow}>
                  <View style={styles.previewIcon}>
                    <Ionicons name="document-text-outline" size={18} color={palette.gold} />
                  </View>
                  <View style={styles.previewText}>
                    <Text style={styles.previewName} numberOfLines={1}>{resource.title || 'Untitled resource'}</Text>
                    <Text style={styles.previewMeta} numberOfLines={1}>
                      {[resource.level, resource.subject, resource.type].filter(Boolean).join(' / ') || 'Learning material'}
                    </Text>
                  </View>
                </View>
              ))}
            </View>
          ) : null}

          <View style={styles.sectionHeader}>
            <Text style={styles.sectionTitle}>
              <Text style={styles.white}>Why </Text>
              <Text style={styles.orange}>Kenya</Text>
              <Text style={styles.green}> EduHub</Text>
            </Text>
          </View>

          <View style={styles.featureList}>
            {highlights.map((item) => (
              <View key={item.title} style={styles.feature}>
                <View style={styles.featureIcon}>
                  <Ionicons name={item.icon} size={22} color={palette.green} />
                </View>
                <View style={styles.featureText}>
                  <Text style={styles.featureTitle}>{item.title}</Text>
                  <Text style={styles.featureBody}>{item.body}</Text>
                </View>
              </View>
            ))}
          </View>
          <AppFooter />
        </ScrollView>
      </SafeAreaView>
    </Screen>
  );
}

const styles = StyleSheet.create({
  safe: {
    flex: 1,
  },
  content: {
    padding: 18,
    paddingBottom: 34,
    gap: 18,
  },
  hero: {
    backgroundColor: palette.panel,
    borderRadius: 4,
    borderWidth: 1,
    borderColor: palette.border,
    padding: 18,
    gap: 18,
  },
  topRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: 12,
  },
  title: {
    color: palette.ink,
    fontSize: 31,
    lineHeight: 36,
    fontWeight: '900',
  },
  subtitle: {
    color: palette.muted,
    fontSize: 16,
    lineHeight: 24,
  },
  actions: {
    gap: 10,
  },
  statsRow: {
    flexDirection: 'row',
    gap: 10,
  },
  statusPill: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    borderWidth: 1,
    borderColor: palette.border,
    backgroundColor: palette.panel,
    borderRadius: 999,
    paddingHorizontal: 10,
    paddingVertical: 6,
  },
  statusOffline: {
    borderColor: palette.red,
  },
  statusDot: {
    width: 7,
    height: 7,
    borderRadius: 4,
    backgroundColor: palette.green,
  },
  statusDotOffline: {
    backgroundColor: palette.red,
  },
  statusText: {
    color: '#FFFFFF',
    fontSize: 11,
    fontWeight: '800',
  },
  quickPanel: {
    flexDirection: 'row',
    gap: 10,
  },
  quickItem: {
    flex: 1,
    minHeight: 126,
    borderRadius: 4,
    borderWidth: 1,
    borderColor: palette.border,
    backgroundColor: palette.panel,
    padding: 14,
    gap: 8,
  },
  quickTitle: {
    color: '#FFFFFF',
    fontWeight: '900',
    fontSize: 15,
  },
  quickText: {
    color: palette.muted,
    lineHeight: 19,
    fontSize: 13,
  },
  sectionHeader: {
    gap: 6,
  },
  sectionTitle: {
    color: palette.ink,
    fontSize: 21,
    fontWeight: '900',
  },
  sectionText: {
    color: palette.muted,
    lineHeight: 21,
  },
  featureList: {
    gap: 12,
  },
  chipWrap: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 8,
  },
  chip: {
    borderRadius: 999,
    borderWidth: 1,
    borderColor: palette.border,
    backgroundColor: '#0B0B0B',
    paddingHorizontal: 12,
    paddingVertical: 8,
  },
  chipText: {
    color: palette.gold,
    fontSize: 12,
    fontWeight: '800',
    textTransform: 'uppercase',
  },
  previewPanel: {
    borderRadius: 4,
    borderWidth: 1,
    borderColor: palette.border,
    backgroundColor: palette.panel,
    padding: 14,
    gap: 12,
  },
  previewHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    gap: 12,
  },
  previewTitle: {
    color: '#FFFFFF',
    fontWeight: '900',
    fontSize: 18,
  },
  previewLink: {
    color: palette.gold,
    fontWeight: '900',
    fontSize: 13,
  },
  previewRow: {
    flexDirection: 'row',
    gap: 10,
    alignItems: 'center',
    borderTopWidth: 1,
    borderTopColor: palette.border,
    paddingTop: 12,
  },
  previewIcon: {
    width: 36,
    height: 36,
    borderRadius: 4,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: '#000000',
    borderColor: palette.blue,
    borderWidth: 1,
  },
  previewText: {
    flex: 1,
    gap: 2,
  },
  previewName: {
    color: '#FFFFFF',
    fontWeight: '900',
  },
  previewMeta: {
    color: palette.muted,
    fontSize: 12,
    fontWeight: '700',
    textTransform: 'uppercase',
  },
  feature: {
    flexDirection: 'row',
    gap: 12,
    borderRadius: 4,
    backgroundColor: palette.panel,
    borderColor: palette.border,
    borderWidth: 1,
    padding: 14,
  },
  featureIcon: {
    width: 42,
    height: 42,
    borderRadius: 4,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: '#000000',
    borderColor: palette.blue,
    borderWidth: 1,
  },
  featureText: {
    flex: 1,
    gap: 3,
  },
  featureTitle: {
    color: palette.ink,
    fontWeight: '900',
    fontSize: 15,
  },
  featureBody: {
    color: palette.muted,
    lineHeight: 20,
  },
  white: {
    color: '#FFFFFF',
  },
  orange: {
    color: palette.orange,
  },
  gold: {
    color: palette.gold,
  },
  green: {
    color: palette.green,
  },
});
