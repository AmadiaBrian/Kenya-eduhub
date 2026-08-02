import { View, Text, StyleSheet, TouchableOpacity, ScrollView, ActivityIndicator, TextInput, Modal, Alert, RefreshControl, KeyboardAvoidingView, Platform } from 'react-native';
import { useRouter } from 'expo-router';
import { useState, useEffect } from 'react';
import { Ionicons } from '@expo/vector-icons';
import { getFees, FeesResponse, initiateMpesaPayment, checkPaymentStatus } from '../../lib/api';

export default function Fees() {
  const router = useRouter();
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [feesData, setFeesData] = useState<FeesResponse | null>(null);
  const [selectedChild, setSelectedChild] = useState<number | null>(null);
  const [showPaymentModal, setShowPaymentModal] = useState(false);
  const [paymentLoading, setPaymentLoading] = useState(false);
  const [paymentStatus, setPaymentStatus] = useState<'idle' | 'initiated' | 'checking' | 'success' | 'failed'>('idle');
  const [checkoutRequestID, setCheckoutRequestID] = useState<string>('');
  const [showPaymentStatusModal, setShowPaymentStatusModal] = useState(false);
  const [paymentAmount, setPaymentAmount] = useState<string>('');
  const [error, setError] = useState<string | null>(null);
  const [paymentForm, setPaymentForm] = useState({
    fee_type: 'Tuition',
    term: '',
    year: '',
    amount: '',
    phone: ''
  });

  useEffect(() => {
    loadFees();
  }, []);

  const loadFees = async () => {
    try {
      const data = await getFees();
      setFeesData(data);
      setError(null);
      // Auto-select child if there's only one
      if (data.children && data.children.length === 1) {
        setSelectedChild(data.children[0].id);
      }
      // Set parent phone in payment form
      if (data.parent?.phone) {
        const parentPhone = data.parent.phone;
        setPaymentForm(prev => ({ ...prev, phone: parentPhone }));
      }
    } catch (error: any) {
      if (error.message === 'NOT_AUTHENTICATED') {
        router.replace('/login');
      } else {
        setError(error.message || 'Failed to load fees data');
      }
    } finally {
      setLoading(false);
    }
  };

  const onRefresh = async () => {
    setRefreshing(true);
    await loadFees();
    setRefreshing(false);
  };

  const handlePayment = async () => {
    if (!paymentForm.amount || !paymentForm.phone || !selectedChild) {
      Alert.alert('Error', 'Please fill in all required fields');
      return;
    }

    setPaymentLoading(true);
    setPaymentStatus('idle');
    
    try {
      const paymentData = {
        student_id: selectedChild,
        amount: paymentForm.amount,
        phone: paymentForm.phone,
        term: paymentForm.term,
        year: paymentForm.year,
        fee_type: paymentForm.fee_type
      };

      const response = await initiateMpesaPayment(paymentData);
      
      if (response.CheckoutRequestID) {
        setCheckoutRequestID(response.CheckoutRequestID);
        setPaymentAmount(paymentForm.amount);

        setShowPaymentModal(false);
        setShowPaymentStatusModal(true);
        setPaymentStatus('checking');

        // Start checking payment status
        if (response.CheckoutRequestID) {
          checkPaymentStatusLoop(response.CheckoutRequestID);
        }
      }
    } catch (error: any) {
      setPaymentLoading(false);
      Alert.alert('Error', error.message || 'Payment failed. Please try again.');
    } finally {
      setPaymentLoading(false);
    }
  };

  const checkPaymentStatusLoop = async (checkoutId: string) => {
    let attempts = 0;
    const maxAttempts = 20; // Check for up to 2 minutes (20 attempts * 6 seconds)
    
    const checkInterval = setInterval(async () => {
      attempts++;
      
      try {
        const status = await checkPaymentStatus(checkoutId);
        
        if (status.found) {
          if (status.status === 'success') {
            clearInterval(checkInterval);
            setPaymentStatus('success');
          } else if (status.status === 'failed') {
            clearInterval(checkInterval);
            setPaymentStatus('failed');
          }
        } else {
          // If there's an error but the API returned gracefully, continue checking
          if (status.error) {
            console.log('Payment status check error (continuing):', status.error);
          }
        }
        
        // Stop checking after max attempts
        if (attempts >= maxAttempts) {
          clearInterval(checkInterval);
          setPaymentStatus('idle');
          setShowPaymentStatusModal(false);
          Alert.alert('Payment Status', 'Unable to confirm payment status. Please check your M-Pesa messages for confirmation.');
        }
      } catch (error) {
        // Continue checking even on error
      }
    }, 6000); // Check every 6 seconds
  };

  const updatePaymentAmount = (newPaymentForm?: any) => {
    const formToUse = newPaymentForm || paymentForm;
    if (selectedChild && feesData?.fee_data && feesData.fee_data[selectedChild]) {
      const childFeeData = feesData.fee_data[selectedChild];
      const feeStructure = childFeeData.fee_structure_status?.find(
        (fs: any) => fs.fee_type === formToUse.fee_type && fs.term === formToUse.term && fs.year.toString() === formToUse.year
      );
      if (feeStructure) {
        const newAmount = feeStructure.balance.toString();
        if (newPaymentForm) {
          setPaymentForm({ ...newPaymentForm, amount: newAmount });
        } else {
          setPaymentForm({ ...paymentForm, amount: newAmount });
        }
      }
    }
  };

  const openPaymentModal = () => {
    if (selectedChild && feesData?.fee_data && feesData.fee_data[selectedChild]) {
      const currentTerm = feesData.current_term || 'Term 1';
      const currentYear = feesData.current_year ? feesData.current_year.toString() : new Date().getFullYear().toString();
      
      // Get unique fee types from fee structures
      const feeStructures = feesData.fee_data[selectedChild].fee_structures || [];
      const uniqueFeeTypes = Array.from(new Set(feeStructures.map((fs: any) => fs.fee_type)));
      
      console.log('Available fee types:', uniqueFeeTypes);
      console.log('Available terms:', feesData.terms);
      
      setPaymentForm({
        ...paymentForm,
        term: currentTerm,
        year: currentYear,
        fee_type: uniqueFeeTypes[0] || 'Tuition' // Default to first available fee type
      });
      updatePaymentAmount();
      setShowPaymentModal(true);
    }
  };

  const features = [
    { id: 1, title: 'My Children', icon: 'person-outline', screen: 'children' },
    { id: 2, title: 'Performance', icon: 'trending-up-outline', screen: 'performance' },
    { id: 3, title: 'Assignments', icon: 'document-text-outline', screen: 'assignments' },
    { id: 4, title: 'Fines', icon: 'information-circle-outline', screen: 'fines' },
    { id: 5, title: 'Results', icon: 'trophy-outline', screen: 'results' },
    { id: 6, title: 'Profile', icon: 'person-circle-outline', screen: 'profile' },
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

  const children = feesData?.children || [];
  const fee_data = feesData?.fee_data || {};
  const current_term = feesData?.current_term || 'Term 1';
  const current_year = feesData?.current_year || '2026';
  const parentPhone = feesData?.parent?.phone || '';

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
              loadFees();
            }}
          >
            <Ionicons name="refresh" size={20} color="#ffffff" />
            <Text style={styles.retryButtonText}>Try Again</Text>
          </TouchableOpacity>
        </View>
      </View>
    );
  }

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
          <Text style={styles.pageTitle}>Fee Payments</Text>
          <Text style={styles.pageSubtitle}>
            View fee payment status for your children
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

        {/* Fee Summary */}
        {selectedChild && fee_data[selectedChild] ? (
          <View style={styles.card}>
            <Text style={styles.cardTitle}>
              {fee_data[selectedChild].child.name} - Class: {fee_data[selectedChild].child.class}
            </Text>
            <Text style={styles.cardSubtitle}>
              Year: {current_year} | Term: {current_term}
            </Text>

            {/* Year Summary */}
            <View style={styles.summaryCard}>
              <View style={styles.summaryItem}>
                <Text style={styles.summaryLabel}>Total Fees</Text>
                <Text style={styles.summaryValue}>KES {fee_data[selectedChild].year_total_fees.toLocaleString()}</Text>
              </View>
              <View style={styles.summaryItem}>
                <Text style={styles.summaryLabel}>Total Paid</Text>
                <Text style={[styles.summaryValue, { color: '#137333' }]}>KES {fee_data[selectedChild].year_total_paid.toLocaleString()}</Text>
              </View>
              <View style={styles.summaryItem}>
                <Text style={styles.summaryLabel}>Balance</Text>
                <Text style={[styles.summaryValue, { color: fee_data[selectedChild].year_balance > 0 ? '#c5221f' : '#137333' }]}>
                  KES {fee_data[selectedChild].year_balance.toLocaleString()}
                </Text>
              </View>
            </View>

            {/* Payment Progress Bar */}
            <View style={styles.progressCard}>
              <Text style={styles.progressLabel}>Payment Progress</Text>
              <View style={styles.progressBarContainer}>
                <View style={[
                  styles.progressBar,
                  { 
                    width: `${Math.min((fee_data[selectedChild].year_total_paid / fee_data[selectedChild].year_total_fees) * 100, 100)}%`,
                    backgroundColor: '#FFD700'
                  }
                ]} />
              </View>
              <Text style={styles.progressText}>
                {Math.round((fee_data[selectedChild].year_total_paid / fee_data[selectedChild].year_total_fees) * 100)}% Paid
              </Text>
            </View>

            {/* Term Balances */}
            <Text style={styles.sectionTitle}>Term Balances</Text>
            {Object.entries(fee_data[selectedChild]?.term_balances || {}).map(([term, balance]) => (
              <View key={term} style={[
                styles.termBalanceCard,
                { borderColor: balance.balance > 0 ? '#c5221f' : '#137333' }
              ]}>
                <View style={styles.termBalanceHeader}>
                  <Text style={styles.termName}>{term}</Text>
                  <View style={[
                    styles.balanceBadge,
                    { backgroundColor: balance.balance > 0 ? '#fce8e6' : '#e6f4ea' }
                  ]}>
                    <Text style={[
                      styles.balanceText,
                      { color: balance.balance > 0 ? '#c5221f' : '#137333' }
                    ]}>
                      {balance.balance > 0 ? 'Due' : 'Paid'}
                    </Text>
                  </View>
                </View>
                <View style={styles.termBalanceDetails}>
                  <View style={styles.termBalanceItem}>
                    <Text style={styles.termBalanceLabel}>Fees</Text>
                    <Text style={styles.termBalanceValue}>KES {balance.fees.toLocaleString()}</Text>
                  </View>
                  <View style={styles.termBalanceItem}>
                    <Text style={styles.termBalanceLabel}>Paid</Text>
                    <Text style={styles.termBalanceValue}>KES {balance.paid.toLocaleString()}</Text>
                  </View>
                  <View style={styles.termBalanceItem}>
                    <Text style={styles.termBalanceLabel}>Balance</Text>
                    <Text style={[
                      styles.termBalanceValue,
                      { color: balance.balance > 0 ? '#c5221f' : '#137333' }
                    ]}>
                      KES {balance.balance.toLocaleString()}
                    </Text>
                  </View>
                </View>
              </View>
            ))}

            {/* Fee Structure Status */}
            <Text style={styles.sectionTitle}>Fee Structure Status</Text>
            <ScrollView horizontal showsHorizontalScrollIndicator={true} style={styles.tableScroll}>
              <View style={styles.tableContainer}>
                <View style={styles.table}>
                  <View style={styles.headerRow}>
                    <View style={styles.headerCell}>
                      <Text style={styles.headerText}>Fee Type</Text>
                    </View>
                    <View style={styles.headerCell}>
                      <Text style={styles.headerText}>Term</Text>
                    </View>
                    <View style={styles.headerCell}>
                      <Text style={styles.headerText}>Amount</Text>
                    </View>
                    <View style={styles.headerCell}>
                      <Text style={styles.headerText}>Paid</Text>
                    </View>
                    <View style={styles.headerCell}>
                      <Text style={styles.headerText}>Balance</Text>
                    </View>
                    <View style={styles.headerCellLast}>
                      <Text style={styles.headerText}>Status</Text>
                    </View>
                  </View>
                  {fee_data[selectedChild]?.fee_structure_status?.map((fee, index) => (
                    <View key={fee.id || index} style={[styles.dataRow, index % 2 === 1 && styles.dataRowAlternate]}>
                      <View style={styles.dataCell}>
                        <Text style={styles.dataText}>{fee.fee_type}</Text>
                      </View>
                      <View style={styles.dataCell}>
                        <Text style={styles.dataText}>{fee.term}</Text>
                      </View>
                      <View style={styles.dataCell}>
                        <Text style={[styles.dataText, { color: '#137333' }]}>KES {fee.amount.toLocaleString()}</Text>
                      </View>
                      <View style={styles.dataCell}>
                        <Text style={styles.dataText}>KES {fee.paid.toLocaleString()}</Text>
                      </View>
                      <View style={styles.dataCell}>
                        <Text style={[styles.dataText, { color: '#c5221f' }]}>KES {fee.balance.toLocaleString()}</Text>
                      </View>
                      <View style={styles.dataCellLast}>
                        <View style={[
                          styles.statusBadge,
                          { 
                            backgroundColor: fee.status === 'Paid' ? '#e6f4ea' : 
                                          fee.status === 'Partial' ? '#fff8e1' : '#fce8e6'
                          }
                        ]}>
                          <Text style={[
                            styles.statusText,
                            { 
                              color: fee.status === 'Paid' ? '#137333' : 
                                    fee.status === 'Partial' ? '#f57c00' : '#c5221f'
                            }
                          ]}>
                            {fee.status}
                          </Text>
                        </View>
                      </View>
                    </View>
                  ))}
                </View>
              </View>
            </ScrollView>

            {/* Pay Fees Button */}
            <TouchableOpacity style={styles.payButton} onPress={openPaymentModal}>
              <View style={styles.mpesaLogoContainer}>
                <Text style={styles.mpesaGreen}>M</Text>
                <Text style={styles.mpesaDash}>-</Text>
                <Text style={styles.mpesaGreen}>PESA</Text>
              </View>
              <Text style={styles.payButtonText}>Pay Fees via M-Pesa</Text>
            </TouchableOpacity>
          </View>
        ) : (
          <View style={styles.card}>
            <View style={styles.emptyState}>
              <Ionicons name="wallet-outline" size={48} color="#5f6368" />
              <Text style={styles.emptyText}>Select a child to view fee details</Text>
            </View>
          </View>
        )}
        
        {/* Quick Actions */}
        <View style={[styles.quickActionsSection, styles.quickActionsSectionMargin]}>
          <Text style={styles.quickActionsTitle}>Quick Actions</Text>
          <View style={styles.featuresGrid}>
            {features.map((feature) => (
              <TouchableOpacity 
                key={feature.id} 
                style={styles.featureCard}
                onPress={() => {
                  if (feature.screen === 'children') {
                    router.push('/(tabs)/dashboard');
                  } else if (feature.screen === 'assignments') {
                    router.push('/(tabs)/assignments');
                  } else if (feature.screen === 'performance') {
                    router.push('/(tabs)/performance');
                  } else if (feature.screen === 'fines') {
                    router.push('/(tabs)/fines');
                  } else if (feature.screen === 'results') {
                    router.push('/(tabs)/results');
                  } else if (feature.screen === 'profile') {
                    router.push('/(tabs)/profile');
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
        <KeyboardAvoidingView
          behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
          style={styles.modalOverlay}
        >
          <View style={styles.modalContent}>
            <View style={styles.modalHeader}>
              <View style={styles.modalHeaderLeft}>
                <View style={styles.modalIconContainer}>
                  <Ionicons name="wallet" size={24} color="#FF6B35" />
                </View>
                <View>
                  <Text style={styles.modalTitle}>Pay Fees</Text>
                  <Text style={styles.modalSubtitle}>Complete your payment securely via M-Pesa</Text>
                </View>
              </View>
              <TouchableOpacity 
                style={styles.modalCloseButton}
                onPress={() => setShowPaymentModal(false)}
              >
                <Ionicons name="close" size={24} color="#5f6368" />
              </TouchableOpacity>
            </View>

            <ScrollView style={styles.modalScrollView} showsVerticalScrollIndicator={false}>
              <View style={styles.formGroup}>
                <Text style={styles.formLabel}>Fee Type</Text>
                <View style={styles.pickerContainer}>
                  {selectedChild && feesData?.fee_data && feesData.fee_data[selectedChild]?.fee_structures && (() => {
                    const uniqueFeeTypes = Array.from(new Set(feesData.fee_data[selectedChild].fee_structures.map((fs: any) => fs.fee_type)));
                    return uniqueFeeTypes.map((feeType) => (
                      <TouchableOpacity
                        key={feeType}
                        style={[
                          styles.pickerOption,
                          paymentForm.fee_type === feeType && styles.pickerOptionActive
                        ]}
                        onPress={() => {
                          const newForm = { ...paymentForm, fee_type: feeType };
                          setPaymentForm(newForm);
                          updatePaymentAmount(newForm);
                        }}
                      >
                        <Text style={[
                          styles.pickerOptionText,
                          paymentForm.fee_type === feeType && styles.pickerOptionTextActive
                        ]}>
                          {feeType}
                        </Text>
                      </TouchableOpacity>
                    ));
                  })()}
                </View>
              </View>

              <View style={styles.formGroup}>
                <Text style={styles.formLabel}>Term</Text>
                <View style={styles.pickerContainer}>
                  {feesData?.terms?.map((term) => (
                    <TouchableOpacity
                      key={term}
                      style={[
                        styles.pickerOption,
                        paymentForm.term === term && styles.pickerOptionActive
                      ]}
                      onPress={() => {
                        const newForm = { ...paymentForm, term };
                        setPaymentForm(newForm);
                        updatePaymentAmount(newForm);
                      }}
                    >
                      <Text style={[
                        styles.pickerOptionText,
                        paymentForm.term === term && styles.pickerOptionTextActive
                      ]}>
                        {term}
                      </Text>
                    </TouchableOpacity>
                  ))}
                </View>
              </View>

              <View style={styles.formGroup}>
                <Text style={styles.formLabel}>Year</Text>
                <TextInput
                  style={styles.formInput}
                  value={paymentForm.year}
                  onChangeText={(text) => {
                    setPaymentForm({ ...paymentForm, year: text });
                    updatePaymentAmount();
                  }}
                  keyboardType="numeric"
                  placeholder="2026"
                  placeholderTextColor="#9aa0a6"
                />
              </View>

              <View style={styles.formGroup}>
                <Text style={styles.formLabel}>Amount (KES)</Text>
                <View style={styles.amountInputContainer}>
                  <Text style={styles.amountPrefix}>KES</Text>
                  <TextInput
                    style={styles.amountInput}
                    value={paymentForm.amount}
                    onChangeText={(text) => setPaymentForm({ ...paymentForm, amount: text })}
                    keyboardType="numeric"
                    placeholder="0"
                    placeholderTextColor="#9aa0a6"
                  />
                </View>
                <Text style={styles.formHint}>Amount auto-filled based on balance. You can change it if needed.</Text>
              </View>

              <View style={styles.formGroup}>
                <Text style={styles.formLabel}>M-Pesa Phone Number</Text>
                <View style={styles.phoneInputContainer}>
                  <Ionicons name="call-outline" size={20} color="#5f6368" style={styles.phoneIcon} />
                  <TextInput
                    style={styles.phoneInput}
                    value={paymentForm.phone}
                    onChangeText={(text) => setPaymentForm({ ...paymentForm, phone: text })}
                    keyboardType="phone-pad"
                    placeholder="2547XXXXXXXX"
                    placeholderTextColor="#9aa0a6"
                  />
                </View>
                <Text style={styles.formHint}>For sandbox testing, use a registered test number (e.g., 254700000000)</Text>
              </View>
            </ScrollView>

            <View style={styles.modalFooter}>
              <TouchableOpacity
                style={[styles.payModalButton, paymentLoading && styles.payModalButtonDisabled]}
                onPress={handlePayment}
                disabled={paymentLoading}
                activeOpacity={0.8}
              >
                {paymentLoading ? (
                  <ActivityIndicator color="#ffffff" size="small" />
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
        </KeyboardAvoidingView>
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
                  Your payment has been processed successfully
                </Text>
                <Text style={styles.paymentStatusAmountText}>
                  Amount: KES {paymentAmount}
                </Text>

                <TouchableOpacity
                  style={styles.paymentStatusButton}
                  onPress={() => {
                    setShowPaymentStatusModal(false);
                    setPaymentStatus('idle');
                    loadFees(); // Reload fees to show updated balance
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
    justifyContent: 'flex-start',
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
    borderRadius: 8,
    padding: 16,
    marginBottom: 24,
    marginHorizontal: 8,
  },
  cardTitle: {
    fontSize: 18,
    fontWeight: '600',
    color: '#202124',
    marginBottom: 12,
  },
  cardSubtitle: {
    fontSize: 14,
    color: '#5f6368',
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
  summaryCard: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    backgroundColor: '#ffffff',
    borderRadius: 8,
    padding: 16,
    marginBottom: 16,
    borderWidth: 2,
    borderColor: '#FFD700',
  },
  progressCard: {
    backgroundColor: '#ffffff',
    borderRadius: 8,
    padding: 16,
    marginBottom: 16,
    borderWidth: 1,
    borderColor: '#FFD700',
  },
  progressLabel: {
    fontSize: 14,
    fontWeight: '600',
    color: '#202124',
    marginBottom: 8,
  },
  progressBarContainer: {
    height: 8,
    backgroundColor: '#e8eaed',
    borderRadius: 4,
    marginBottom: 8,
    overflow: 'hidden',
  },
  progressBar: {
    height: '100%',
    borderRadius: 4,
  },
  progressText: {
    fontSize: 12,
    color: '#5f6368',
    textAlign: 'right',
  },
  summaryItem: {
    alignItems: 'center',
  },
  summaryLabel: {
    fontSize: 12,
    color: '#5f6368',
    marginBottom: 4,
  },
  summaryValue: {
    fontSize: 16,
    fontWeight: '600',
    color: '#202124',
  },
  sectionTitle: {
    fontSize: 16,
    fontWeight: '600',
    color: '#202124',
    marginBottom: 12,
  },
  termBalanceCard: {
    backgroundColor: '#ffffff',
    borderRadius: 8,
    borderWidth: 1,
    borderColor: '#e8eaed',
    marginBottom: 12,
    overflow: 'hidden',
  },
  termBalanceHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    padding: 12,
    backgroundColor: '#f8f9fa',
    borderBottomWidth: 1,
    borderBottomColor: '#e8eaed',
  },
  termName: {
    fontSize: 14,
    fontWeight: '600',
    color: '#202124',
  },
  balanceBadge: {
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 12,
  },
  balanceText: {
    fontSize: 12,
    fontWeight: '600',
  },
  termBalanceDetails: {
    flexDirection: 'row',
    justifyContent: 'space-around',
    padding: 12,
  },
  termBalanceItem: {
    alignItems: 'center',
  },
  termBalanceLabel: {
    fontSize: 11,
    color: '#5f6368',
    marginBottom: 4,
  },
  termBalanceValue: {
    fontSize: 14,
    fontWeight: '600',
    color: '#202124',
  },
  tableScroll: {
    marginHorizontal: -24,
    paddingHorizontal: 24,
  },
  tableContainer: {
    borderWidth: 1,
    borderColor: '#dadce0',
    borderRadius: 8,
    overflow: 'hidden',
    minWidth: 600,
  },
  table: {
    flexDirection: 'column',
    backgroundColor: '#ffffff',
  },
  headerRow: {
    flexDirection: 'row',
    backgroundColor: '#e8f0fe',
    width: 600,
  },
  headerCell: {
    width: 100,
    padding: 12,
    borderRightWidth: 1,
    borderRightColor: '#dadce0',
  },
  headerCellLast: {
    width: 100,
    borderRightWidth: 0,
  },
  headerText: {
    fontSize: 14,
    fontWeight: '600',
    color: '#202124',
    textAlign: 'left',
  },
  dataRow: {
    flexDirection: 'row',
    backgroundColor: '#ffffff',
    borderBottomWidth: 1,
    borderBottomColor: '#dadce0',
    width: 600,
  },
  dataRowAlternate: {
    backgroundColor: '#f8f9fa',
  },
  dataCell: {
    width: 100,
    padding: 12,
    borderRightWidth: 1,
    borderRightColor: '#dadce0',
  },
  dataCellLast: {
    width: 100,
    borderRightWidth: 0,
  },
  dataText: {
    fontSize: 14,
    color: '#202124',
    textAlign: 'left',
  },
  statusBadge: {
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 4,
    alignSelf: 'flex-start',
  },
  statusText: {
    fontSize: 12,
    fontWeight: '600',
  },
  emptyState: {
    alignItems: 'center',
    padding: 48,
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
  quickActionsSection: {
    marginTop: 24,
  },
  quickActionsSectionMargin: {
    marginBottom: 80,
    marginHorizontal: 24,
  },
  quickActionsTitle: {
    fontSize: 18,
    fontWeight: '600',
    color: '#202124',
    marginBottom: 16,
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
  },
  modalOverlay: {
    flex: 1,
    backgroundColor: 'rgba(0, 0, 0, 0.6)',
    justifyContent: 'flex-end',
  },
  modalContent: {
    backgroundColor: '#ffffff',
    borderTopLeftRadius: 28,
    borderTopRightRadius: 28,
    paddingBottom: 28,
    maxHeight: '90%',
  },
  modalHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    padding: 24,
    paddingBottom: 16,
    borderBottomWidth: 1,
    borderBottomColor: '#f1f3f4',
  },
  modalHeaderLeft: {
    flexDirection: 'row',
    alignItems: 'center',
    flex: 1,
  },
  modalIconContainer: {
    width: 48,
    height: 48,
    borderRadius: 16,
    backgroundColor: '#FFF3E0',
    justifyContent: 'center',
    alignItems: 'center',
    marginRight: 12,
  },
  modalTitle: {
    fontSize: 20,
    fontWeight: '700',
    color: '#202124',
    marginBottom: 2,
  },
  modalSubtitle: {
    fontSize: 13,
    color: '#5f6368',
    fontWeight: '400',
  },
  modalCloseButton: {
    width: 40,
    height: 40,
    borderRadius: 20,
    backgroundColor: '#f1f3f4',
    justifyContent: 'center',
    alignItems: 'center',
  },
  modalScrollView: {
    paddingHorizontal: 24,
    maxHeight: '60%',
  },
  formGroup: {
    marginBottom: 20,
  },
  formLabel: {
    fontSize: 14,
    fontWeight: '600',
    color: '#202124',
    marginBottom: 10,
    letterSpacing: 0.2,
  },
  formInput: {
    borderWidth: 1.5,
    borderColor: '#e8eaed',
    borderRadius: 12,
    padding: 14,
    fontSize: 16,
    color: '#202124',
    backgroundColor: '#f8f9fa',
  },
  amountInputContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    borderWidth: 1.5,
    borderColor: '#e8eaed',
    borderRadius: 12,
    backgroundColor: '#f8f9fa',
    overflow: 'hidden',
  },
  amountPrefix: {
    paddingLeft: 16,
    paddingRight: 8,
    fontSize: 16,
    fontWeight: '600',
    color: '#5f6368',
  },
  amountInput: {
    flex: 1,
    padding: 14,
    fontSize: 18,
    fontWeight: '600',
    color: '#202124',
  },
  phoneInputContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    borderWidth: 1.5,
    borderColor: '#e8eaed',
    borderRadius: 12,
    backgroundColor: '#f8f9fa',
    overflow: 'hidden',
  },
  phoneIcon: {
    paddingLeft: 16,
    paddingRight: 8,
  },
  phoneInput: {
    flex: 1,
    padding: 14,
    fontSize: 16,
    color: '#202124',
  },
  formHint: {
    fontSize: 12,
    color: '#5f6368',
    marginTop: 6,
    lineHeight: 16,
  },
  pickerContainer: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 10,
  },
  pickerOption: {
    borderWidth: 1.5,
    borderColor: '#e8eaed',
    borderRadius: 24,
    paddingHorizontal: 18,
    paddingVertical: 10,
    backgroundColor: '#ffffff',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.05,
    shadowRadius: 2,
    elevation: 2,
  },
  pickerOptionActive: {
    backgroundColor: '#FF6B35',
    borderColor: '#FF6B35',
    shadowColor: '#FF6B35',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.3,
    shadowRadius: 4,
    elevation: 4,
  },
  pickerOptionText: {
    fontSize: 14,
    fontWeight: '500',
    color: '#202124',
  },
  pickerOptionTextActive: {
    color: '#ffffff',
    fontWeight: '600',
  },
  paymentStatusContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    padding: 14,
    backgroundColor: '#f8f9fa',
    borderRadius: 12,
    marginTop: 16,
    borderWidth: 1,
    borderColor: '#e8eaed',
  },
  paymentStatusSuccess: {
    backgroundColor: '#E8F5E9',
    borderColor: '#4CAF50',
  },
  paymentStatusFailed: {
    backgroundColor: '#FFEBEE',
    borderColor: '#c5221f',
  },
  paymentStatusText: {
    marginLeft: 10,
    fontSize: 14,
    fontWeight: '600',
    color: '#5f6368',
  },
  modalFooter: {
    paddingHorizontal: 24,
    paddingTop: 16,
    borderTopWidth: 1,
    borderTopColor: '#f1f3f4',
  },
  payModalButton: {
    backgroundColor: '#FF6B35',
    borderRadius: 16,
    padding: 16,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    shadowColor: '#FF6B35',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.3,
    shadowRadius: 8,
    elevation: 8,
  },
  payModalButtonDisabled: {
    backgroundColor: '#ccc',
    shadowOpacity: 0,
    elevation: 0,
  },
  mpesaLogoContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    marginRight: 10,
  },
  mpesaGreen: {
    color: '#4CAF50',
    fontSize: 16,
    fontWeight: '800',
    letterSpacing: 0.5,
  },
  mpesaDash: {
    color: '#ED1C24',
    fontSize: 16,
    fontWeight: '800',
  },
  payModalButtonText: {
    color: '#ffffff',
    fontSize: 16,
    fontWeight: '700',
    letterSpacing: 0.3,
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
  paymentStatusIconCircleBlue: {
    width: 48,
    height: 48,
    borderRadius: 24,
    backgroundColor: '#1a73e8',
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
  paymentStatusReceiptText: {
    fontSize: 14,
    color: '#5f6368',
    textAlign: 'center',
    marginBottom: 4,
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
});
