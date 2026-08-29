// API configuration for Parents app
export const API_BASE_URL = 'https://unexplaining-lesli-nonabsolutely.ngrok-free.dev/kenyaeduhub/parents/api';

export interface LoginResponse {
  success: boolean;
  message?: string;
  session_id?: string;
  session_token?: string;
  parent?: {
    id: number;
    name: string;
    email: string;
    phone: string;
    school_id: number;
    school_name: string;
  };
  error?: string;
}

export interface LoginRequest {
  email: string;
  identifier: string; // Phone number or ID number
}

export interface DashboardResponse {
  success: boolean;
  parent?: {
    id: number;
    name: string;
    email: string;
    phone: string;
    school_name: string;
  };
  children?: Array<{
    id: number;
    name: string;
    admission_number: string;
    class: string;
    stream: string;
  }>;
  stats?: {
    total_children: number;
    total_fees_due: number;
    attendance_rate: number;
    performance_records: number;
  };
  current_term?: string;
  terms?: string[];
  notifications?: Array<{
    id: number;
    title: string;
    message: string;
    created_at: string;
  }>;
  error?: string;
}

export interface AssignmentsResponse {
  success: boolean;
  children?: Array<{
    id: number;
    name: string;
    admission_number: string;
    class: string;
    stream: string;
  }>;
  assignments?: Array<{
    id: number;
    title: string;
    description: string;
    subject: string;
    class: string;
    teacher: string;
    due_date: string;
    created_at: string;
    assignment_type: string;
    badge_type: string;
    file_name: string | null;
    attachment: string | null;
  }>;
  error?: string;
}

export interface PerformanceResponse {
  success: boolean;
  children?: Array<{
    id: number;
    name: string;
    admission_number: string;
    class: string;
    stream: string;
  }>;
  performance_data?: {
    [key: number]: {
      child: {
        id: number;
        name: string;
        admission_number: string;
        class: string;
        stream: string;
      };
      performance_by_exam_type?: {
        [key: string]: Array<{
          id: number;
          subject: string;
          marks: number;
          grade: string;
          grade_points: number | null;
          exam_type: string;
          remarks: string | null;
          term: string;
          year: string;
          created_at: string;
        }>;
      };
    };
  };
  current_term?: string;
  terms?: string[];
  years?: string[];
  current_year?: string;
  error?: string;
}

export interface FeesResponse {
  success: boolean;
  parent?: {
    id: number;
    name: string;
    email: string;
    phone: string;
  };
  children?: Array<{
    id: number;
    name: string;
    admission_number: string;
    class: string;
    stream: string;
  }>;
  fee_data?: {
    [key: number]: {
      child: {
        id: number;
        name: string;
        admission_number: string;
        class: string;
        stream: string;
      };
      fee_structures: Array<{
        id: number;
        fee_type: string;
        term: string;
        year: string;
        amount: number;
        description: string;
      }>;
      payments: Array<{
        id: number;
        amount: number;
        payment_date: string;
        term: string;
        fee_type: string;
        status: string;
      }>;
      term_balances: {
        [key: string]: {
          fees: number;
          paid: number;
          balance: number;
        };
      };
      year_total_fees: number;
      year_total_paid: number;
      year_balance: number;
      current_year: string;
      fee_structure_status: Array<{
        id: number;
        fee_type: string;
        term: string;
        year: string;
        amount: number;
        paid: number;
        balance: number;
        status: string;
        description: string;
      }>;
    };
  };
  current_term?: string;
  terms?: string[];
  current_year?: string;
  error?: string;
}

export interface ResultsResponse {
  success: boolean;
  children?: Array<{
    id: number;
    name: string;
    admission_number: string;
    class: string;
    stream: string;
  }>;
  class_performance_data?: {
    [key: number]: {
      student: {
        id: number;
        name: string;
        admission_number: string;
        class: string;
        stream: string;
      };
      performance: Array<{
        id: number;
        subject: string;
        marks: number;
        grade: string;
        grade_points: number | null;
        exam_type: string;
        remarks: string | null;
        term: string;
        year: string;
        created_at: string;
      }>;
    };
  };
  current_term?: string;
  terms?: string[];
  current_year?: string;
  school_settings?: {
    min_subjects: number;
    max_subjects: number;
  };
  grading_scales?: Array<{
    id: number;
    school_id: number;
    subject_id: number | null;
    subject_name: string | null;
    grade_name: string;
    min_score: number;
    max_score: number;
    points: number;
  }>;
  aggregate_distribution?: Array<{
    id: number;
    school_id: number;
    min_points: number;
    max_points: number;
    grade_name: string;
  }>;
  years?: string[];
  student_subject_assignments?: {
    [key: string]: Array<{
      subject_name: string;
      is_compulsory: number;
    }>;
  };
  error?: string;
}

// Login function
export const login = async (credentials: LoginRequest): Promise<LoginResponse> => {
  try {
    const response = await fetch(`${API_BASE_URL}/login.php`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(credentials),
    });

    const responseText = await response.text();
    console.log('Login response text:', responseText);
    console.log('Response status:', response.status);

    const data = JSON.parse(responseText) as LoginResponse;
    
    if (data.success && data.session_id && data.session_token) {
      console.log('Session ID from response:', data.session_id);
      console.log('Session token from response:', data.session_token);
      
      // Store session data
      // In a real app, you'd use AsyncStorage or SecureStore
      return data;
    } else {
      throw new Error(data.error || 'Login failed');
    }
  } catch (error) {
    throw error;
  }
};

// Dashboard function
export const getDashboard = async (): Promise<DashboardResponse> => {
  try {
    const response = await fetch(`${API_BASE_URL}/dashboard.php`, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
      },
    });

    const responseText = await response.text();
    
    // Check if response is HTML (like Packetriot warning page)
    if (responseText.trim().startsWith('<!DOCTYPE html>') || responseText.trim().startsWith('<html')) {
      throw new Error('NOT_AUTHENTICATED');
    }
    
    // Check if response is valid JSON
    let data;
    try {
      data = JSON.parse(responseText) as DashboardResponse;
    } catch (parseError) {
      console.error('Failed to parse dashboard response:', parseError);
      throw new Error('Server temporarily unavailable. Please try again later.');
    }
    
    if (data.success) {
      return data;
    } else {
      // Handle both "Not authenticated" and "Unauthorized" consistently
      if (data.error === 'Not authenticated' || data.error === 'Unauthorized') {
        throw new Error('NOT_AUTHENTICATED');
      }
      throw new Error(data.error || 'Failed to fetch dashboard data');
    }
  } catch (error) {
    if (error instanceof Error) {
      // Handle network errors specifically
      if (error.message === 'Network request failed' || error.message.includes('Network')) {
        throw new Error('No internet connection. Please check your network settings and try again.');
      }
      if (error.message === 'NOT_AUTHENTICATED') {
        throw error; // Re-throw for handling by components
      }
      console.error('Dashboard error:', error);
      throw new Error('Unable to connect to server. Please check your internet connection.');
    }
    throw new Error('An unexpected error occurred. Please try again.');
  }
};

// Assignments function
export const getAssignments = async (): Promise<AssignmentsResponse> => {
  try {
    const response = await fetch(`${API_BASE_URL}/assignments.php`, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
      },
    });

    const responseText = await response.text();
    
    // Check if response is HTML (like Packetriot warning page)
    if (responseText.trim().startsWith('<!DOCTYPE html>') || responseText.trim().startsWith('<html')) {
      throw new Error('NOT_AUTHENTICATED');
    }
    
    // Check if response is valid JSON
    let data;
    try {
      data = JSON.parse(responseText) as AssignmentsResponse;
    } catch (parseError) {
      console.error('Failed to parse assignments response:', parseError);
      throw new Error('Server temporarily unavailable. Please try again later.');
    }
    
    if (data.success) {
      return data;
    } else {
      // Handle both "Not authenticated" and "Unauthorized" consistently
      if (data.error === 'Not authenticated' || data.error === 'Unauthorized') {
        throw new Error('NOT_AUTHENTICATED');
      }
      throw new Error(data.error || 'Failed to fetch assignments data');
    }
  } catch (error) {
    if (error instanceof Error) {
      // Handle network errors specifically
      if (error.message === 'Network request failed' || error.message.includes('Network')) {
        throw new Error('No internet connection. Please check your network settings and try again.');
      }
      if (error.message === 'NOT_AUTHENTICATED') {
        throw error; // Re-throw for handling by components
      }
      console.error('Assignments error:', error);
      throw new Error('Unable to connect to server. Please check your internet connection.');
    }
    throw new Error('An unexpected error occurred. Please try again.');
  }
};

// Performance function
export const getPerformance = async (): Promise<PerformanceResponse> => {
  try {
    const response = await fetch(`${API_BASE_URL}/performance.php`, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
      },
    });

    const responseText = await response.text();
    
    // Check if response is HTML (like Packetriot warning page)
    if (responseText.trim().startsWith('<!DOCTYPE html>') || responseText.trim().startsWith('<html')) {
      console.log('Received HTML response instead of JSON');
      throw new Error('NOT_AUTHENTICATED');
    }
    
    // Check if response is valid JSON
    let data;
    try {
      data = JSON.parse(responseText) as PerformanceResponse;
    } catch (parseError) {
      console.error('Failed to parse performance response:', parseError);
      throw new Error('Server temporarily unavailable. Please try again later.');
    }
    
    if (data.success) {
      return data;
    } else {
      // Handle both "Not authenticated" and "Unauthorized" consistently
      if (data.error === 'Not authenticated' || data.error === 'Unauthorized') {
        throw new Error('NOT_AUTHENTICATED');
      }
      throw new Error(data.error || 'Failed to fetch performance data');
    }
  } catch (error) {
    if (error instanceof Error) {
      // Handle network errors specifically
      if (error.message === 'Network request failed' || error.message.includes('Network')) {
        throw new Error('No internet connection. Please check your network settings and try again.');
      }
      if (error.message === 'NOT_AUTHENTICATED') {
        throw error; // Re-throw for handling by components
      }
      console.error('Performance error:', error);
      throw new Error('Unable to connect to server. Please check your internet connection.');
    }
    throw new Error('An unexpected error occurred. Please try again.');
  }
};

// Results function
export const getResults = async (scope: 'class' | 'stream' = 'stream'): Promise<ResultsResponse> => {
  try {
    const response = await fetch(`${API_BASE_URL}/results.php?scope=${scope}`, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
      },
    });

    const responseText = await response.text();
    
    // Check if response is HTML (like Packetriot warning page)
    if (responseText.trim().startsWith('<!DOCTYPE html>') || responseText.trim().startsWith('<html')) {
      throw new Error('NOT_AUTHENTICATED');
    }
    
    // Check if response is valid JSON
    let data;
    try {
      data = JSON.parse(responseText) as ResultsResponse;
    } catch (parseError) {
      console.error('Failed to parse results response:', parseError);
      throw new Error('Server temporarily unavailable. Please try again later.');
    }
    
    if (data.success) {
      return data;
    } else {
      // Handle both "Not authenticated" and "Unauthorized" consistently
      if (data.error === 'Not authenticated' || data.error === 'Unauthorized') {
        throw new Error('NOT_AUTHENTICATED');
      }
      throw new Error(data.error || 'Failed to fetch results data');
    }
  } catch (error) {
    if (error instanceof Error) {
      // Handle network errors specifically
      if (error.message === 'Network request failed' || error.message.includes('Network')) {
        throw new Error('No internet connection. Please check your network settings and try again.');
      }
      if (error.message === 'NOT_AUTHENTICATED') {
        throw error; // Re-throw for handling by components
      }
      console.error('Results error:', error);
      throw new Error('Unable to connect to server. Please check your internet connection.');
    }
    throw new Error('An unexpected error occurred. Please try again.');
  }
};

// Fees function
export const getFees = async (): Promise<FeesResponse> => {
  try {
    const response = await fetch(`${API_BASE_URL}/fees.php`, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
      },
    });

    const responseText = await response.text();
    
    // Check if response is HTML (like Packetriot warning page)
    if (responseText.trim().startsWith('<!DOCTYPE html>') || responseText.trim().startsWith('<html')) {
      throw new Error('NOT_AUTHENTICATED');
    }
    
    // Check if response is valid JSON
    let data;
    try {
      data = JSON.parse(responseText) as FeesResponse;
    } catch (parseError) {
      console.error('Failed to parse fees response:', parseError);
      throw new Error('Server temporarily unavailable. Please try again later.');
    }
    
    if (data.success) {
      return data;
    } else {
      // Handle both "Not authenticated" and "Unauthorized" consistently
      if (data.error === 'Not authenticated' || data.error === 'Unauthorized') {
        throw new Error('NOT_AUTHENTICATED');
      }
      throw new Error(data.error || 'Failed to fetch fees data');
    }
  } catch (error) {
    if (error instanceof Error) {
      // Handle network errors specifically
      if (error.message === 'Network request failed' || error.message.includes('Network')) {
        throw new Error('No internet connection. Please check your network settings and try again.');
      }
      if (error.message === 'NOT_AUTHENTICATED') {
        throw error; // Re-throw for handling by components
      }
      console.error('Fees error:', error);
      throw new Error('Unable to connect to server. Please check your internet connection.');
    }
    throw new Error('An unexpected error occurred. Please try again.');
  }
};

export interface FinesResponse {
  success: boolean;
  parent?: {
    id: number;
    name: string;
    email: string;
    phone: string;
  };
  children?: Array<{
    id: number;
    name: string;
    admission_number: string;
    class: string;
    stream: string;
  }>;
  fines_by_child?: {
    [key: number]: {
      child: {
        id: number;
        name: string;
        admission_number: string;
        class: string;
        stream: string;
      };
      fines: Array<{
        id: number;
        amount: number;
        amount_paid: number;
        status: string;
        issue_date: string;
        title: string;
        author: string;
        user_name: string;
        user_identifier: string;
      }>;
      overdue_books: Array<{
        id: number;
        title: string;
        author: string;
        due_date: string;
        days_overdue: number;
      }>;
      total_fines: number;
      total_amount: number;
      total_paid: number;
      unpaid_amount: number;
    };
  };
  fine_stats?: {
    total_fines: number;
    total_amount: number;
    total_paid: number;
    unpaid_amount: number;
    paid_count: number;
    unpaid_count: number;
    partial_count: number;
  };
  overdue_books?: Array<any>;
  error?: string;
}

// Fines function
export const getFines = async (): Promise<FinesResponse> => {
  try {
    const response = await fetch(`${API_BASE_URL}/fines.php`, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
      },
    });

    const responseText = await response.text();
    
    // Check if response is HTML (like Packetriot warning page)
    if (responseText.trim().startsWith('<!DOCTYPE html>') || responseText.trim().startsWith('<html')) {
      throw new Error('NOT_AUTHENTICATED');
    }
    
    // Check if response is valid JSON
    let data;
    try {
      data = JSON.parse(responseText) as FinesResponse;
    } catch (parseError) {
      console.error('Failed to parse fines response:', parseError);
      throw new Error('Server temporarily unavailable. Please try again later.');
    }
    
    if (data.success) {
      return data;
    } else {
      // Handle both "Not authenticated" and "Unauthorized" consistently
      if (data.error === 'Not authenticated' || data.error === 'Unauthorized') {
        throw new Error('NOT_AUTHENTICATED');
      }
      throw new Error(data.error || 'Failed to fetch fines data');
    }
  } catch (error) {
    if (error instanceof Error) {
      // Handle network errors specifically
      if (error.message === 'Network request failed' || error.message.includes('Network')) {
        throw new Error('No internet connection. Please check your network settings and try again.');
      }
      if (error.message === 'NOT_AUTHENTICATED') {
        throw error; // Re-throw for handling by components
      }
      console.error('Fines error:', error);
      throw new Error('Unable to connect to server. Please check your internet connection.');
    }
    throw new Error('An unexpected error occurred. Please try again.');
  }
};

export interface ProfileResponse {
  success: boolean;
  parent?: {
    id: number;
    first_name: string;
    last_name: string;
    email: string;
    phone: string;
    address: string;
    id_number: string;
    school_name: string;
    created_at: string;
  };
  message?: string;
  error?: string;
}

export const getProfile = async (): Promise<ProfileResponse> => {
  try {
    const response = await fetch(`${API_BASE_URL}/profile.php`, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
      },
    });

    const responseText = await response.text();
    
    // Check if response is HTML (like Packetriot warning page)
    if (responseText.trim().startsWith('<!DOCTYPE html>') || responseText.trim().startsWith('<html')) {
      throw new Error('NOT_AUTHENTICATED');
    }
    
    // Check if response is valid JSON
    let data;
    try {
      data = JSON.parse(responseText) as ProfileResponse;
    } catch (parseError) {
      console.error('Failed to parse profile response:', parseError);
      throw new Error('Server temporarily unavailable. Please try again later.');
    }
    
    if (data.success) {
      return data;
    } else {
      // Handle both "Not authenticated" and "Unauthorized" consistently
      if (data.error === 'Not authenticated' || data.error === 'Unauthorized') {
        throw new Error('NOT_AUTHENTICATED');
      }
      throw new Error(data.error || 'Failed to fetch profile data');
    }
  } catch (error) {
    if (error instanceof Error) {
      // Handle network errors specifically
      if (error.message === 'Network request failed' || error.message.includes('Network')) {
        throw new Error('No internet connection. Please check your network settings and try again.');
      }
      if (error.message === 'NOT_AUTHENTICATED') {
        throw error; // Re-throw for handling by components
      }
      console.error('Profile error:', error);
      throw new Error('Unable to connect to server. Please check your internet connection.');
    }
    throw new Error('An unexpected error occurred. Please try again.');
  }
};

export const updateProfile = async (profileData: {
  first_name: string;
  last_name: string;
  phone: string;
  address: string;
}): Promise<ProfileResponse> => {
  try {
    const response = await fetch(`${API_BASE_URL}/profile.php`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(profileData),
    });

    const responseText = await response.text();
    console.log('Profile update response text:', responseText);
    console.log('Response status:', response.status);

    const data = JSON.parse(responseText) as ProfileResponse;
    
    if (data.success) {
      return data;
    } else {
      throw new Error(data.error || 'Failed to update profile');
    }
  } catch (error) {
    console.error('Profile update error:', error);
    throw error;
  }
};

// Logout function
export const logout = async (): Promise<{ success: boolean; message?: string; error?: string }> => {
  try {
    const response = await fetch(`${API_BASE_URL}/logout.php`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
    });

    const responseText = await response.text();
    console.log('Logout response text:', responseText);
    console.log('Response status:', response.status);

    const data = JSON.parse(responseText) as { success: boolean; message?: string; error?: string };
    
    if (data.success) {
      return data;
    } else {
      throw new Error(data.error || 'Logout failed');
    }
  } catch (error) {
    console.error('Logout error:', error);
    throw error;
  }
};

// M-Pesa Payment interfaces
export interface MpesaPaymentRequest {
  student_id: number;
  amount: string;
  phone: string;
  term: string;
  year: string;
  fee_type: string;
}

export interface MpesaPaymentResponse {
  success: boolean;
  message?: string;
  payment_id?: number;
  receipt_number?: string;
  CheckoutRequestID?: string;
  MerchantRequestID?: string;
  student_name?: string;
  error?: string;
  details?: any;
}

export interface PaymentStatusResponse {
  success: boolean;
  found: boolean;
  status: string;
  error_message?: string;
  mpesa_receipt?: string;
  amount?: number;
  result_code?: string;
  message?: string;
  error?: string;
}

// M-Pesa Fee Payment function
export const initiateMpesaPayment = async (paymentData: MpesaPaymentRequest): Promise<MpesaPaymentResponse> => {
  try {
    const formData = new FormData();
    formData.append('student_id', paymentData.student_id.toString());
    formData.append('amount', paymentData.amount);
    formData.append('phone', paymentData.phone);
    formData.append('term', paymentData.term);
    formData.append('year', paymentData.year);
    formData.append('fee_type', paymentData.fee_type);

    const response = await fetch(`${API_BASE_URL}/mpesa_payment.php`, {
      method: 'POST',
      body: formData,
    });

    const responseText = await response.text();
    console.log('M-Pesa payment response text:', responseText);
    console.log('Response status:', response.status);

    const data = JSON.parse(responseText) as MpesaPaymentResponse;
    
    if (data.success) {
      return data;
    } else {
      throw new Error(data.error || 'Failed to initiate payment');
    }
  } catch (error) {
    throw error;
  }
};

// Check Payment Status function
export const checkPaymentStatus = async (checkoutRequestID: string): Promise<PaymentStatusResponse> => {
  try {
    const formData = new FormData();
    formData.append('CheckoutRequestID', checkoutRequestID);

    const response = await fetch(`${API_BASE_URL}/check_fee_payment_status.php`, {
      method: 'POST',
      body: formData,
    });

    const responseText = await response.text();
    console.log('Payment status response text:', responseText);
    console.log('Response status:', response.status);

    // Check if response is valid JSON
    let data;
    try {
      data = JSON.parse(responseText) as PaymentStatusResponse;
    } catch (parseError) {
      console.error('Failed to parse payment status response:', parseError);
      // If the response contains error codes from M-Pesa, handle gracefully
      if (responseText.includes('error code')) {
        return {
          success: false,
          found: false,
          status: 'failed',
          error: 'Payment status temporarily unavailable. Please check your M-Pesa messages.'
        };
      }
      throw new Error('Unable to check payment status. Please try again later.');
    }
    
    if (data.success) {
      return data;
    } else {
      throw new Error(data.error || 'Failed to check payment status');
    }
  } catch (error) {
    // Return a graceful error response instead of throwing
    return {
      success: false,
      found: false,
      status: 'failed',
      error: error instanceof Error ? error.message : 'Unable to check payment status'
    };
  }
};

// M-Pesa Fine Payment interfaces
export interface MpesaFinePaymentRequest {
  fine_id: number;
  amount: string;
  phone: string;
}

export interface MpesaFinePaymentResponse {
  success: boolean;
  message?: string;
  CheckoutRequestID?: string;
  MerchantRequestID?: string;
  fine_id?: number;
  student_name?: string;
  error?: string;
  details?: any;
}

// M-Pesa Fine Payment function
export const initiateMpesaFinePayment = async (paymentData: MpesaFinePaymentRequest): Promise<MpesaFinePaymentResponse> => {
  try {
    const formData = new FormData();
    formData.append('fine_id', paymentData.fine_id.toString());
    formData.append('amount', paymentData.amount);
    formData.append('phone', paymentData.phone);

    const response = await fetch(`${API_BASE_URL}/mpesa_fine_payment.php`, {
      method: 'POST',
      body: formData,
    });

    const responseText = await response.text();
    console.log('M-Pesa fine payment response text:', responseText);
    console.log('Response status:', response.status);

    const data = JSON.parse(responseText) as MpesaFinePaymentResponse;
    
    if (data.success) {
      return data;
    } else {
      throw new Error(data.error || 'Failed to initiate fine payment');
    }
  } catch (error) {
    throw error;
  }
};

// Check Fine Payment Status function
export const checkFinePaymentStatus = async (checkoutRequestID: string): Promise<PaymentStatusResponse> => {
  try {
    const formData = new FormData();
    formData.append('checkoutRequestID', checkoutRequestID);

    const response = await fetch(`${API_BASE_URL}/check_fine_payment_status.php`, {
      method: 'POST',
      body: formData,
    });

    const responseText = await response.text();
    console.log('Fine payment status response text:', responseText);
    console.log('Response status:', response.status);

    // Check if response is valid JSON
    let data;
    try {
      data = JSON.parse(responseText) as PaymentStatusResponse;
    } catch (parseError) {
      console.error('Failed to parse fine payment status response:', parseError);
      // If the response contains error codes from M-Pesa, handle gracefully
      if (responseText.includes('error code')) {
        return {
          success: false,
          found: false,
          status: 'failed',
          error: 'Payment status temporarily unavailable. Please check your M-Pesa messages.'
        };
      }
      throw new Error('Unable to check payment status. Please try again later.');
    }
    
    if (data.success) {
      return data;
    } else {
      throw new Error(data.error || 'Failed to check fine payment status');
    }
  } catch (error) {
    // Return a graceful error response instead of throwing
    return {
      success: false,
      found: false,
      status: 'failed',
      error: error instanceof Error ? error.message : 'Unable to check payment status'
    };
  }
};

// Generic API request function
export const apiRequest = async (
  endpoint: string,
  options: RequestInit = {}
): Promise<any> => {
  try {
    const response = await fetch(`${API_BASE_URL}${endpoint}`, {
      ...options,
      headers: {
        'Content-Type': 'application/json',
        ...options.headers,
      },
    });

    const data = await response.json();
    return data;
  } catch (error) {
    console.error('API request error:', error);
    throw error;
  }
};
