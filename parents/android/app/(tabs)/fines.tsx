import { View, Text, StyleSheet, TouchableOpacity, ScrollView, ActivityIndicator, TextInput, Modal, Alert, RefreshControl } from 'react-native';
import { useRouter } from 'expo-router';
import { useState, useEffect } from 'react';
import { Ionicons } from '@expo/vector-icons';
import { getFines, initiateMpesaFinePayment, checkFinePaymentStatus, FinesResponse } from '../../lib/api';

export default function Fines() {
  const router = useRouter();
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [finesData, setFinesData] = useState<FinesResponse | null>(null);
  const [selectedChild, setSelectedChild] = useState<number | null>(null);
  const [showPaymentModal, setShowPaymentModal] = useState(false);
  const [paymentLoading, setPaymentLoading] = useState(false);
  const [paymentStatus, setPaymentStatus] = useState<'idle' | 'checking' | 'success' | 'failed'>('idle');
  const [checkoutRequestID, setCheckoutRequestID] = useState<string>('');
  const [showPaymentStatusModal, setShowPaymentStatusModal] = useState(false);
  const [paymentAmount, setPaymentAmount] = useState<string>('');
  const [imageError, setImageError] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [paymentForm, setPaymentForm] = useState({
    fine_id: null as number | null,
    amount: '',
    phone: ''
  });

  useEffect(() => {
    loadFines();
  }, []);

  const loadFines = async () => {
    try {
      const data = await getFines();
      console.log('Fines data loaded:', data);
      setFinesData(data);
      setError(null);
      // Auto-select child if there's only one
      if (data.children && data.children.length === 1) {
        setSelectedChild(data.children[0].id);
      }
      // Set parent phone in payment form
      const parent = data.parent;
      if (parent?.phone) {
        console.log('Setting parent phone:', parent.phone);
        setPaymentForm(prev => ({ ...prev, phone: parent.phone }));
      } else {
        console.log('No parent phone found in data');
      }
    } catch (error: any) {
      if (error.message === 'NOT_AUTHENTICATED') {
        router.replace('/login');
      } else {
        setError(error.message || 'Failed to load fines data');
      }
    } finally {
      setLoading(false);
    }
  };

  const onRefresh = async () => {
    setRefreshing(true);
    await loadFines();
    setRefreshing(false);
  };

  const handlePayment = async () => {
    console.log('Payment form data:', paymentForm);
    if (!paymentForm.amount || !paymentForm.phone || !paymentForm.fine_id) {
      Alert.alert('Error', 'Please fill in all required fields');
      return;
    }

    setPaymentLoading(true);
    setPaymentStatus('idle');

    try {
      const paymentData = {
        fine_id: paymentForm.fine_id,
        amount: paymentForm.amount,
        phone: paymentForm.phone
      };

      const response = await initiateMpesaFinePayment(paymentData);

      if (response.CheckoutRequestID) {
        setCheckoutRequestID(response.CheckoutRequestID);
        setPaymentAmount(paymentForm.amount);

        setShowPaymentModal(false);
        setShowPaymentStatusModal(true);
        setPaymentStatus('checking');

        // Start checking payment status
        if (response.CheckoutRequestID) {
          checkFinePaymentStatusLoop(response.CheckoutRequestID);
        }
      }
    } catch (error: any) {
      setPaymentLoading(false);
      Alert.alert('Error', error.message || 'Payment failed. Please try again.');
    } finally {
      setPaymentLoading(false);
    }
  };

  const checkFinePaymentStatusLoop = async (checkoutId: string) => {
    let attempts = 0;
    const maxAttempts = 20; // Check for up to 2 minutes (20 attempts * 6 seconds)

    const checkInterval = setInterval(async () => {
      attempts++;

      try {
        const status = await checkFinePaymentStatus(checkoutId);

        if (status.found) {
          if (status.status === 'success') {
            clearInterval(checkInterval);
            setPaymentStatus('success');
          } else if (status.status === 'failed') {
            clearInterval(checkInterval);
            setPaymentStatus('failed');
          }
        } else {
          // Keep showing checking status while waiting
        }

        // Stop checking after max attempts
        if (attempts >= maxAttempts) {
          clearInterval(checkInterval);
          setPaymentStatus('idle');
          setShowPaymentStatusModal(false);
        }
      } catch (error) {
      }
    }, 6000); // Check every 6 seconds
  };

  const openPaymentModal = (fineId: number, remainingAmount: number) => {
    const parentPhone = finesData?.parent?.phone || '';
    console.log('Opening payment modal with fine_id:', fineId, 'amount:', remainingAmount, 'phone:', parentPhone);
    setPaymentForm({
      fine_id: fineId,
      amount: remainingAmount.toString(),
      phone: parentPhone
    });
    setShowPaymentModal(true);
  };

  const features = [
    { id: 1, title: 'My Children', icon: 'person-outline', screen: 'children' },
    { id: 2, title: 'Performance', icon: 'trending-up-outline', screen: 'performance' },
    { id: 3, title: 'Fees', icon: 'wallet-outline', screen: 'fees' },
    { id: 4, title: 'Results', icon: 'trophy-outline', screen: 'results' },
  ];

  if (loading) {
    return (
      <View style={styles.container}>
        <View style={styles.header}>
          <View style={styles.logo}>
            <View style={styles.logoIcon}>
              <Text style={styles.logoText}>
                <Text style={[styles.logoKE, { color: '#FF6B35', fontSize: 24 }]}>K</Text>
                <Text style={[styles.logoKE, { color: '#008000', fontSize: 20 }]}>E</Text>
              </Text>
            </View>
            <Text style={styles.kenyaText}>Kenya</Text>
            <Text style={styles.eduhubText}>EduHub</Text>
          </View>
        </View>
        <View style={styles.loadingContainer}>
          <ActivityIndicator size="large" color="#FF6B35" />
          <Text style={styles.loadingText}>Loading...</Text>
        </View>
      </View>
    );
  }

  if (error) {
    return (
      <View style={styles.container}>
        <View style={styles.header}>
          <View style={styles.logo}>
            <View style={styles.logoIcon}>
              <Text style={styles.logoText}>
                <Text style={[styles.logoKE, { color: '#FF6B35', fontSize: 24 }]}>K</Text>
                <Text style={[styles.logoKE, { color: '#008000', fontSize: 20 }]}>E</Text>
              </Text>
            </View>
            <Text style={styles.kenyaText}>Kenya</Text>
            <Text style={styles.eduhubText}>EduHub</Text>
          </View>
        </View>
        <View style={styles.errorContainer}>
          <View style={styles.errorIconContainer}>
            <Ionicons name="cloud-offline" size={48} color="#5f6368" />
          </View>
          <Text style={styles.errorTitle}>Something went wrong</Text>
          <Text style={styles.errorMessage}>{error}</Text>
          <TouchableOpacity 
            style={styles.retryButton}
            onPress={() => {
              setError(null);
              setLoading(true);
              loadFines();
            }}
          >
            <Ionicons name="refresh" size={20} color="#ffffff" />
            <Text style={styles.retryButtonText}>Try Again</Text>
          </TouchableOpacity>
        </View>
      </View>
    );
  }

  const children = finesData?.children || [];
  const fines_by_child = finesData?.fines_by_child || {};
  const fine_stats = finesData?.fine_stats;

  return (
    <View style={styles.container}>
      <View style={styles.header}>
        <View style={styles.logo}>
          <View style={styles.logoIcon}>
            <Text style={styles.logoText}>
              <Text style={[styles.logoKE, { color: '#FF6B35', fontSize: 24 }]}>K</Text>
              <Text style={[styles.logoKE, { color: '#008000', fontSize: 20 }]}>E</Text>
            </Text>
          </View>
          <Text style={styles.kenyaText}>Kenya</Text>
          <Text style={styles.eduhubText}>EduHub</Text>
        </View>
      </View>

      <ScrollView 
        style={styles.content}
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={onRefresh}
            colors={['#FF6B35']}
          />
        }
      >
        <View style={styles.pageHeader}>
          <Text style={styles.pageTitle}>Library Fines</Text>
          <Text style={styles.pageSubtitle}>
            View library fines and overdue books for your children
          </Text>
        </View>

        {/* Child Selection */}
        {children.length > 1 && (
          <View style={styles.card}>
            <Text style={styles.cardTitle}>Select Child</Text>
            <ScrollView horizontal showsHorizontalScrollIndicator={false} style={styles.childrenScroll}>
              {children.map((child) => (
                <TouchableOpacity 
                  key={child.id} 
                  style={[
                    styles.childChip,
                    selectedChild === child.id && styles.childChipActive
                  ]}
                  onPress={() => setSelectedChild(child.id)}
                >
                  <Text style={[
                    styles.childChipText,
                    selectedChild === child.id && styles.childChipTextActive
                  ]}>
                    {child.name}
                  </Text>
                </TouchableOpacity>
              ))}
            </ScrollView>
          </View>
        )}

        {/* Fine Statistics */}
        {fine_stats && (
          <View style={styles.card}>
            <Text style={styles.cardTitle}>Overall Statistics</Text>
            <View style={styles.statsGrid}>
              <View style={styles.statItem}>
                <Text style={styles.statValue}>{fine_stats.total_fines}</Text>
                <Text style={styles.statLabel}>Total Fines</Text>
              </View>
              <View style={styles.statItem}>
                <Text style={styles.statValue}>KES {fine_stats.total_amount.toLocaleString()}</Text>
                <Text style={styles.statLabel}>Total Amount</Text>
              </View>
              <View style={styles.statItem}>
                <Text style={[styles.statValue, { color: '#137333' }]}>KES {fine_stats.total_paid.toLocaleString()}</Text>
                <Text style={styles.statLabel}>Paid</Text>
              </View>
              <View style={styles.statItem}>
                <Text style={[styles.statValue, { color: fine_stats.unpaid_amount > 0 ? '#c5221f' : '#137333' }]}>
                  KES {fine_stats.unpaid_amount.toLocaleString()}
                </Text>
                <Text style={styles.statLabel}>Balance</Text>
              </View>
            </View>
          </View>
        )}

        {/* Child Fines */}
        {selectedChild && fines_by_child[selectedChild] ? (
          <View style={styles.card}>
            <Text style={styles.cardTitle}>
              {fines_by_child[selectedChild].child.name} - Class: {fines_by_child[selectedChild].child.class}
            </Text>

            {/* Fines List */}
            <Text style={styles.sectionTitle}>Fines</Text>
            {fines_by_child[selectedChild].fines.length > 0 ? (
              fines_by_child[selectedChild].fines.map((fine, index) => (
                <View key={fine.id || index} style={styles.fineCard}>
                  <View style={styles.fineHeader}>
                    <Text style={styles.fineTitle}>{fine.title}</Text>
                    <View style={[
                      styles.statusBadge,
                      { backgroundColor: fine.status === 'paid' ? '#e6f4ea' : fine.status === 'partial' ? '#fef7e0' : '#fce8e6' }
                    ]}>
                      <Text style={[
                        styles.statusText,
                        { color: fine.status === 'paid' ? '#137333' : fine.status === 'partial' ? '#b06000' : '#c5221f' }
                      ]}>
                        {fine.status.charAt(0).toUpperCase() + fine.status.slice(1)}
                      </Text>
                    </View>
                  </View>
                  <Text style={styles.fineAuthor}>by {fine.author}</Text>
                  <View style={styles.fineDetails}>
                    <View style={styles.fineDetailItem}>
                      <Text style={styles.fineDetailLabel}>Amount</Text>
                      <Text style={styles.fineDetailValue}>KES {fine.amount.toLocaleString()}</Text>
                    </View>
                    <View style={styles.fineDetailItem}>
                      <Text style={styles.fineDetailLabel}>Paid</Text>
                      <Text style={styles.fineDetailValue}>KES {fine.amount_paid.toLocaleString()}</Text>
                    </View>
                    <View style={styles.fineDetailItem}>
                      <Text style={styles.fineDetailLabel}>Balance</Text>
                      <Text style={[
                        styles.fineDetailValue,
                        { color: (fine.amount - fine.amount_paid) > 0 ? '#c5221f' : '#137333' }
                      ]}>
                        KES {(fine.amount - fine.amount_paid).toLocaleString()}
                      </Text>
                    </View>
                  </View>
                  <Text style={styles.fineDate}>Issued: {new Date(fine.issue_date).toLocaleDateString()}</Text>
                  
                  {/* Pay Button for individual fine */}
                  {(fine.status === 'unpaid' || fine.status === 'partial' || fine.status === 'pending') && (
                    <TouchableOpacity 
                      style={styles.payButton} 
                      onPress={() => openPaymentModal(fine.id, fine.amount - fine.amount_paid)}
                    >
                      <Ionicons name="wallet-outline" size={20} color="#ffffff" />
                      <Text style={styles.payButtonText}>Pay KES {(fine.amount - fine.amount_paid).toLocaleString()}</Text>
                    </TouchableOpacity>
                  )}
                </View>
              ))
            ) : (
              <View style={styles.emptyState}>
                <Ionicons name="checkmark-circle-outline" size={48} color="#137333" />
                <Text style={styles.emptyText}>No fines recorded</Text>
              </View>
            )}

            {/* Overdue Books */}
            <Text style={styles.sectionTitle}>Overdue Books</Text>
            {fines_by_child[selectedChild].overdue_books.length > 0 ? (
              fines_by_child[selectedChild].overdue_books.map((book, index) => (
                <View key={book.id || index} style={styles.bookCard}>
                  <View style={styles.bookHeader}>
                    <Text style={styles.bookTitle}>{book.title}</Text>
                    <Text style={styles.bookAuthor}>by {book.author}</Text>
                  </View>
                  <View style={styles.bookDetails}>
                    <View style={styles.bookDetailItem}>
                      <Text style={styles.bookDetailLabel}>Due Date</Text>
                      <Text style={styles.bookDetailValue}>{new Date(book.due_date).toLocaleDateString()}</Text>
                    </View>
                    <View style={styles.bookDetailItem}>
                      <Text style={styles.bookDetailLabel}>Days Overdue</Text>
                      <Text style={[styles.bookDetailValue, { color: '#c5221f' }]}>{book.days_overdue} days</Text>
                    </View>
                  </View>
                </View>
              ))
            ) : (
              <View style={styles.emptyState}>
                <Ionicons name="book-outline" size={48} color="#137333" />
                <Text style={styles.emptyText}>No overdue books</Text>
              </View>
            )}
          </View>
        ) : (
          <View style={styles.card}>
            <View style={styles.emptyState}>
              <Ionicons name="person-outline" size={48} color="#5f6368" />
              <Text style={styles.emptyText}>Select a child to view fines</Text>
            </View>
          </View>
        )}
        
        {/* Quick Actions */}
        <View style={[styles.card, styles.quickActionsCard]}>
          <Text style={styles.cardTitle}>Quick Actions</Text>
          <View style={styles.featuresGrid}>
            {features.map((feature) => (
              <TouchableOpacity 
                key={feature.id} 
                style={styles.featureCard}
                onPress={() => {
                  if (feature.screen === 'children') {
                    router.push('/(tabs)/dashboard');
                  } else if (feature.screen === 'performance') {
                    router.push('/(tabs)/performance');
                  } else if (feature.screen === 'fees') {
                    router.push('/(tabs)/fees');
                  } else if (feature.screen === 'results') {
                    router.push('/(tabs)/results');
                  } else {
                    console.log(`Navigate to ${feature.screen}`);
                  }
                }}
              >
                <Ionicons name={feature.icon as any} size={32} color="#FF6B35" />
                <Text style={styles.featureTitle}>{feature.title}</Text>
              </TouchableOpacity>
            ))}
          </View>
        </View>
      </ScrollView>

      {/* Payment Modal */}
      <Modal
        visible={showPaymentModal}
        animationType="slide"
        transparent={true}
        onRequestClose={() => setShowPaymentModal(false)}
      >
        <View style={styles.modalOverlay}>
          <View style={styles.modalContent}>
            <View style={styles.modalHeader}>
              <Text style={styles.modalTitle}>Pay Fine via M-Pesa</Text>
              <TouchableOpacity onPress={() => setShowPaymentModal(false)}>
                <Ionicons name="close" size={24} color="#202124" />
              </TouchableOpacity>
            </View>

            <View style={styles.formGroup}>
              <Text style={styles.formLabel}>Payment Method</Text>
              <View style={styles.pickerContainer}>
                <TouchableOpacity style={[styles.pickerOption, styles.pickerOptionActive]}>
                  <Text style={[styles.pickerOptionText, styles.pickerOptionTextActive]}>MPESA</Text>
                </TouchableOpacity>
              </View>
            </View>

            <View style={styles.formGroup}>
              <Text style={styles.formLabel}>M-Pesa Phone Number</Text>
              <TextInput
                style={styles.formInput}
                placeholder="2547XXXXXXXX"
                placeholderTextColor="#9aa0a6"
                value={paymentForm.phone}
                onChangeText={(text) => setPaymentForm({ ...paymentForm, phone: text })}
                keyboardType="phone-pad"
              />
            </View>

            <View style={styles.formGroup}>
              <Text style={styles.formLabel}>Payment Amount (KES)</Text>
              <TextInput
                style={styles.formInput}
                placeholder="Enter amount"
                placeholderTextColor="#9aa0a6"
                value={paymentForm.amount}
                onChangeText={(text) => setPaymentForm({ ...paymentForm, amount: text })}
                keyboardType="numeric"
              />
            </View>

            <TouchableOpacity
              style={[styles.payModalButton, paymentLoading && styles.payModalButtonDisabled]}
              onPress={handlePayment}
              disabled={paymentLoading}
            >
              {paymentLoading ? (
                <ActivityIndicator color="#ffffff" />
              ) : (
                <>
                  <View style={styles.mpesaLogoContainer}>
                    <Text style={styles.mpesaGreen}>M</Text>
                    <Text style={styles.mpesaDash}>-</Text>
                    <Text style={styles.mpesaGreen}>PESA</Text>
                  </View>
                  <Text style={styles.payModalButtonText}>Pay with M-Pesa</Text>
                </>
              )}
            </TouchableOpacity>
          </View>
        </View>
      </Modal>

      {/* Payment Status Modal */}
      <Modal
        visible={showPaymentStatusModal}
        animationType="fade"
        transparent={true}
        onRequestClose={() => setShowPaymentStatusModal(false)}
      >
        <View style={styles.paymentStatusModalOverlay}>
          <View style={styles.paymentStatusModalContent}>
            {paymentStatus === 'checking' ? (
              <>
                <View style={styles.paymentStatusIconContainer}>
                  <ActivityIndicator size={48} color="#FF6B35" />
                </View>
                <Text style={styles.paymentStatusTitle}>Processing Payment</Text>
                <Text style={styles.paymentStatusMessage}>
                  Please check your phone and enter your M-Pesa PIN to complete the payment.
                </Text>
              </>
            ) : paymentStatus === 'success' ? (
              <>
                <View style={styles.paymentStatusIconContainer}>
                  <View style={styles.paymentStatusIconCircleGreen}>
                    <Ionicons name="checkmark" size={32} color="#ffffff" />
                  </View>
                </View>
                <Text style={styles.paymentStatusTitle}>Payment Successful!</Text>
                <Text style={styles.paymentStatusMessage}>
                  Your fine payment has been processed successfully
                </Text>
                <Text style={styles.paymentStatusAmountText}>
                  Amount: KES {paymentAmount}
                </Text>

                <TouchableOpacity
                  style={styles.paymentStatusButton}
                  onPress={() => {
                    setShowPaymentStatusModal(false);
                    setPaymentStatus('idle');
                    loadFines(); // Reload fines to show updated balance
                  }}
                  activeOpacity={0.7}
                >
                  <Text style={styles.paymentStatusButtonText}>CLOSE</Text>
                </TouchableOpacity>
              </>
            ) : paymentStatus === 'failed' ? (
              <>
                <View style={styles.paymentStatusIconContainer}>
                  <View style={styles.paymentStatusIconCircleRed}>
                    <Ionicons name="close" size={32} color="#ffffff" />
                  </View>
                </View>
                <Text style={styles.paymentStatusTitle}>Payment Failed</Text>
                <Text style={styles.paymentStatusMessage}>
                  Payment could not be completed. Please try again.
                </Text>

                <TouchableOpacity
                  style={styles.paymentStatusButton}
                  onPress={() => {
                    setShowPaymentStatusModal(false);
                    setPaymentStatus('idle');
                  }}
                  activeOpacity={0.7}
                >
                  <Text style={styles.paymentStatusButtonText}>CLOSE</Text>
                </TouchableOpacity>
              </>
            ) : null}
          </View>
        </View>
      </Modal>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#f8f9fa',
  },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    padding: 16,
    paddingTop: 40,
    backgroundColor: '#ffffff',
    borderBottomWidth: 2,
    borderBottomColor: '#FF6B35',
  },
  logo: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
  },
  logoIcon: {
    width: 40,
    height: 40,
    backgroundColor: '#FFD700',
    borderWidth: 3,
    borderColor: '#FF6B35',
    borderRadius: 20,
    justifyContent: 'center',
    alignItems: 'center',
  },
  logoText: {
    fontWeight: 'bold',
    fontSize: 20,
  },
  logoKE: {
    fontWeight: 'bold',
  },
  kenyaText: {
    color: '#FF6B35',
    fontWeight: 'bold',
    fontSize: 16,
  },
  eduhubText: {
    color: '#008000',
    fontWeight: 'bold',
    fontSize: 16,
  },
  userAvatar: {
    width: 32,
    height: 32,
    borderRadius: 16,
    backgroundColor: '#FF6B35',
    justifyContent: 'center',
    alignItems: 'center',
  },
  avatarText: {
    color: '#ffffff',
    fontSize: 14,
    fontWeight: '600',
  },
  content: {
    flex: 1,
  },
  pageHeader: {
    padding: 24,
    backgroundColor: '#ffffff',
    borderBottomWidth: 1,
    borderBottomColor: '#e8eaed',
  },
  pageTitle: {
    fontSize: 24,
    fontWeight: '600',
    color: '#202124',
  },
  pageSubtitle: {
    fontSize: 14,
    color: '#5f6368',
    marginTop: 4,
  },
  card: {
    backgroundColor: '#f8f9fa',
    borderWidth: 1,
    borderColor: '#FF6B35',
    borderRadius: 8,
    padding: 16,
    marginBottom: 16,
    marginHorizontal: 8,
  },
  quickActionsCard: {
    marginBottom: 80,
  },
  cardTitle: {
    fontSize: 18,
    fontWeight: '600',
    color: '#202124',
    marginBottom: 16,
  },
  childrenScroll: {
    flexDirection: 'row',
  },
  childChip: {
    paddingHorizontal: 16,
    paddingVertical: 8,
    borderRadius: 20,
    backgroundColor: '#e8eaed',
    marginRight: 8,
  },
  childChipActive: {
    backgroundColor: '#FF6B35',
  },
  childChipText: {
    fontSize: 14,
    color: '#202124',
    fontWeight: '500',
  },
  childChipTextActive: {
    color: '#ffffff',
  },
  statsGrid: {
    flexDirection: 'row',
    justifyContent: 'space-around',
  },
  statItem: {
    alignItems: 'center',
  },
  statValue: {
    fontSize: 20,
    fontWeight: '600',
    color: '#202124',
    marginBottom: 4,
  },
  statLabel: {
    fontSize: 12,
    color: '#5f6368',
  },
  sectionTitle: {
    fontSize: 16,
    fontWeight: '600',
    color: '#202124',
    marginBottom: 12,
    marginTop: 20,
  },
  fineCard: {
    backgroundColor: '#ffffff',
    borderRadius: 8,
    borderWidth: 1,
    borderColor: '#FF6B35',
    padding: 16,
    marginBottom: 12,
  },
  fineHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 8,
  },
  fineTitle: {
    fontSize: 16,
    fontWeight: '600',
    color: '#202124',
    flex: 1,
  },
  statusBadge: {
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 12,
  },
  statusText: {
    fontSize: 12,
    fontWeight: '600',
  },
  fineAuthor: {
    fontSize: 14,
    color: '#5f6368',
    marginBottom: 12,
  },
  fineDetails: {
    flexDirection: 'row',
    justifyContent: 'space-around',
  },
  fineDetailItem: {
    alignItems: 'center',
  },
  fineDetailLabel: {
    fontSize: 11,
    color: '#5f6368',
    marginBottom: 4,
  },
  fineDetailValue: {
    fontSize: 14,
    fontWeight: '600',
    color: '#202124',
  },
  fineDate: {
    fontSize: 12,
    color: '#5f6368',
    marginTop: 8,
  },
  bookCard: {
    backgroundColor: '#ffffff',
    borderRadius: 8,
    borderWidth: 1,
    borderColor: '#e8eaed',
    padding: 16,
    marginBottom: 12,
  },
  bookHeader: {
    marginBottom: 12,
  },
  bookTitle: {
    fontSize: 16,
    fontWeight: '600',
    color: '#202124',
    marginBottom: 4,
  },
  bookAuthor: {
    fontSize: 14,
    color: '#5f6368',
  },
  bookDetails: {
    flexDirection: 'row',
    justifyContent: 'space-around',
  },
  bookDetailItem: {
    alignItems: 'center',
  },
  bookDetailLabel: {
    fontSize: 11,
    color: '#5f6368',
    marginBottom: 4,
  },
  bookDetailValue: {
    fontSize: 14,
    fontWeight: '600',
    color: '#202124',
  },
  emptyState: {
    alignItems: 'center',
    padding: 32,
  },
  emptyText: {
    fontSize: 14,
    color: '#5f6368',
    marginTop: 12,
  },
  loadingContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
  },
  loadingText: {
    fontSize: 16,
    color: '#5f6368',
    marginTop: 12,
  },
  errorContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    padding: 32,
    backgroundColor: '#ffffff',
  },
  errorIconContainer: {
    marginBottom: 24,
  },
  errorTitle: {
    fontSize: 24,
    fontWeight: '500',
    color: '#202124',
    marginBottom: 8,
  },
  errorMessage: {
    fontSize: 14,
    color: '#5f6368',
    textAlign: 'center',
    marginBottom: 32,
  },
  retryButton: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#FF6B35',
    paddingHorizontal: 24,
    paddingVertical: 12,
    borderRadius: 8,
    gap: 8,
  },
  retryButtonText: {
    color: '#ffffff',
    fontSize: 14,
    fontWeight: '500',
  },
  featuresGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 12,
  },
  featureCard: {
    width: '48%',
    backgroundColor: '#f8f9fa',
    borderWidth: 1,
    borderColor: '#FF6B35',
    borderRadius: 8,
    padding: 20,
    alignItems: 'center',
  },
  featureTitle: {
    fontSize: 14,
    fontWeight: '500',
    color: '#202124',
    textAlign: 'center',
  },
  payButton: {
    backgroundColor: '#FF6B35',
    borderRadius: 25,
    padding: 16,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    marginTop: 16,
  },
  payButtonText: {
    color: '#ffffff',
    fontSize: 16,
    fontWeight: '600',
    marginLeft: 8,
  },
  modalOverlay: {
    flex: 1,
    backgroundColor: 'rgba(0, 0, 0, 0.5)',
    justifyContent: 'center',
    alignItems: 'center',
    padding: 20,
  },
  modalContent: {
    backgroundColor: '#ffffff',
    borderRadius: 16,
    padding: 24,
    width: '100%',
    maxHeight: '80%',
  },
  modalHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 20,
  },
  modalTitle: {
    fontSize: 20,
    fontWeight: '600',
    color: '#202124',
  },
  formGroup: {
    marginBottom: 16,
  },
  formLabel: {
    fontSize: 14,
    fontWeight: '500',
    color: '#5f6368',
    marginBottom: 8,
  },
  formInput: {
    borderWidth: 1,
    borderColor: '#dadce0',
    borderRadius: 8,
    padding: 12,
    fontSize: 16,
    color: '#202124',
  },
  pickerContainer: {
    flexDirection: 'row',
  },
  pickerOption: {
    borderWidth: 1,
    borderColor: '#dadce0',
    borderRadius: 8,
    paddingHorizontal: 16,
    paddingVertical: 12,
    backgroundColor: '#ffffff',
  },
  pickerOptionActive: {
    backgroundColor: '#FF6B35',
    borderColor: '#FF6B35',
  },
  pickerOptionText: {
    fontSize: 14,
    color: '#202124',
  },
  pickerOptionTextActive: {
    color: '#ffffff',
  },
  payModalButton: {
    backgroundColor: '#FF6B35',
    borderRadius: 25,
    padding: 12,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    marginTop: 16,
  },
  payModalButtonDisabled: {
    backgroundColor: '#ccc',
  },
  mpesaLogoContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    marginRight: 8,
  },
  mpesaGreen: {
    color: '#4CAF50',
    fontSize: 14,
    fontWeight: 'bold',
  },
  mpesaDash: {
    color: '#ED1C24',
    fontSize: 14,
    fontWeight: 'bold',
  },
  payModalButtonText: {
    color: '#ffffff',
    fontSize: 14,
    fontWeight: '600',
  },
  paymentStatusModalOverlay: {
    flex: 1,
    backgroundColor: 'rgba(0, 0, 0, 0.4)',
    justifyContent: 'center',
    alignItems: 'center',
    padding: 24,
  },
  paymentStatusModalContent: {
    backgroundColor: '#ffffff',
    borderRadius: 24,
    padding: 32,
    width: '100%',
    maxWidth: 400,
    alignItems: 'center',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 24 },
    shadowOpacity: 0.14,
    shadowRadius: 38,
    elevation: 24,
  },
  paymentStatusIconContainer: {
    marginBottom: 20,
  },
  paymentStatusIconCircleGreen: {
    width: 48,
    height: 48,
    borderRadius: 24,
    backgroundColor: '#34A853',
    justifyContent: 'center',
    alignItems: 'center',
  },
  paymentStatusIconCircleRed: {
    width: 48,
    height: 48,
    borderRadius: 24,
    backgroundColor: '#EA4335',
    justifyContent: 'center',
    alignItems: 'center',
  },
  paymentStatusTitle: {
    fontSize: 22,
    fontWeight: '400',
    color: '#202124',
    marginBottom: 12,
    textAlign: 'center',
    lineHeight: 28,
  },
  paymentStatusMessage: {
    fontSize: 14,
    color: '#5f6368',
    textAlign: 'center',
    marginBottom: 8,
    lineHeight: 20,
  },
  paymentStatusAmountText: {
    fontSize: 14,
    color: '#5f6368',
    textAlign: 'center',
    marginBottom: 24,
    fontWeight: '500',
  },
  paymentStatusButton: {
    backgroundColor: '#1a73e8',
    borderRadius: 4,
    padding: 10,
    paddingHorizontal: 24,
    width: 'auto',
    alignItems: 'center',
    alignSelf: 'flex-end',
  },
  paymentStatusButtonText: {
    color: '#ffffff',
    fontSize: 14,
    fontWeight: '500',
    letterSpacing: 0.25,
    textTransform: 'uppercase',
  },
});
