// API configuration for Teachers app
export const API_BASE_URL = 'https://unexplaining-lesli-nonabsolutely.ngrok-free.dev/kenyaeduhub/teachers/api';

export interface LoginResponse {
  success: boolean;
  message?: string;
  session_token?: string;
  teacher?: {
    id: number;
    name: string;
    school_id: number;
    class_id: number | null;
    stream_id: number | null;
    class_name: string | null;
    stream_name: string | null;
  };
  error?: string;
}

export interface LoginRequest {
  email: string;
  password: string;
}

export interface DashboardResponse {
  success: boolean;
  teacher?: {
    id: number;
    name: string;
    school_name: string;
    class_name: string | null;
    stream_name: string | null;
    teacher_type: string;
  };
  calendar_status?: {
    is_holiday: boolean;
    school_status: string;
    current_term?: {
      term_name: string;
      start_date: string;
      end_date: string;
    };
    current_holiday?: {
      holiday_name: string;
      start_date: string;
      end_date: string;
    };
    current_year: number;
  };
  stats?: {
    total_students: number;
    attendance_today: number;
    present_today: number;
    attendance_rate: number;
    performance_records: number;
  };
  quick_actions?: Array<{
    id: string;
    title: string;
    icon: string;
    description: string;
  }>;
  error?: string;
}

export interface AssignmentAnalyticsResponse {
  success: boolean;
  total_downloads: number;
  unique_downloaders: number;
  last_download: string | null;
  downloads: Array<{
    full_name: string;
    user_type: string;
    download_date: string;
  }>;
  error?: string;
}

export interface ResultsResponse {
  success: boolean;
  teacher?: {
    id: number;
    name: string;
    school_name: string;
  };
  terms?: string[];
  grading_scales?: Array<{
    id: number;
    subject_id: number | null;
    subject_name: string | null;
    min_score: number;
    max_score: number;
    grade_name: string;
    grade_description: string;
    points: number;
  }>;
  aggregate_distribution?: Array<{
    id: number;
    min_points: number;
    max_points: number;
    grade_name: string;
    grade_description?: string;
  }>;
  school_limits?: {
    min_subjects: number;
    max_subjects: number;
  };
  streams?: Array<{
    id: number;
    stream_name: string;
    class_id: number;
  }>;
  exam_types?: Array<{
    id: number;
    exam_type_name: string;
    exam_type_code?: string;
  }>;
  student_subject_assignments?: Record<string, Array<{
    subject_name: string;
    is_compulsory: number;
  }>>;
  performance_records?: Array<{
    id: number;
    student_id: number;
    term: string;
    year: string;
    subject: string;
    exam_type_id: number;
    marks: number;
    grade: string;
    remarks: string;
    admission_number: string;
    first_name: string;
    last_name: string;
    exam_type_name: string;
    subject_name: string;
  }>;
  error?: string;
}

export interface PerformanceResponse {
  success: boolean;
  teacher?: {
    id: number;
    name: string;
    school_name: string;
    teacher_type: string;
    class_id: number | null;
    class_name: string | null;
  };
  calendar_status?: {
    is_holiday: boolean;
    school_status: string;
    current_term?: {
      term_name: string;
      start_date: string;
      end_date: string;
    };
    current_holiday?: {
      holiday_name: string;
      start_date: string;
      end_date: string;
    };
    current_year: number | null;
  };
  terms?: string[];
  current_term?: string;
  streams?: Array<{
    id: number;
    stream_name: string;
    class_id: number;
  }>;
  subject_assignments?: Array<{
    id: number;
    class_id: number;
    class_name: string;
    subject_name: string;
  }>;
  grading_scales?: Array<{
    id: number;
    subject_id: number | null;
    subject_name: string | null;
    min_score: number;
    max_score: number;
    grade_name: string;
    grade_description: string;
  }>;
  all_subjects?: Array<{
    id: number;
    subject_name: string;
    subject_code?: string;
  }>;
  exam_types?: Array<{
    id: number;
    exam_type_name: string;
    exam_type_code?: string;
    description?: string;
  }>;
  aggregate_distribution?: Array<{
    id: number;
    min_points: number;
    max_points: number;
    grade: string;
    grade_description?: string;
  }>;
  subjects_without_performance?: Array<{
    id: number;
    subject_name: string;
  }>;
  student_subject_assignments?: Record<string, Array<{
    subject_name: string;
    is_compulsory: number;
  }>>;
  school_limits?: {
    min_subjects: number;
    max_subjects: number;
  };
  performance_records?: Array<{
    id: number;
    student_id: number;
    term: string;
    year: string;
    subject: string;
    exam_type_id: number;
    marks: number;
    grade: string;
    remarks: string;
    admission_number: string;
    first_name: string;
    last_name: string;
    gender: string;
    class_name: string;
    subject_name: string;
    exam_type_name: string;
  }>;
  error?: string;
}

export interface AttendanceResponse {
  success: boolean;
  teacher?: {
    id: number;
    name: string;
    school_name: string;
    teacher_type: string;
    class_name: string | null;
    class_id: number | null;
  };
  calendar_status?: {
    is_holiday: boolean;
    school_status: string;
    current_holiday?: {
      holiday_name: string;
      start_date: string;
      end_date: string;
    };
    current_term?: {
      term_name: string;
      start_date: string;
      end_date: string;
    };
    current_year: number | null;
  };
  classes?: Array<{
    id: number;
    class_name: string;
  }>;
  subject_assignments?: Array<{
    id: number;
    class_id: number;
    class_name: string;
    subject: string;
  }>;
  attendance_stats?: Array<{
    date: string;
    status: string;
    count: number;
  }>;
  monthly_summary?: {
    total_present: number;
    total_absent: number;
    total_late: number;
    total_excused: number;
    total_records: number;
    days_recorded: number;
  };
  student_attendance_details?: Array<{
    id: number;
    admission_number: string;
    first_name: string;
    last_name: string;
    class_id?: number;
    class_name?: string;
    attendance_records: string; // Format: "date:status|date:status|..."
  }>;
  error?: string;
}

export interface AttendanceStudentsResponse {
  success: boolean;
  students?: Array<{
    id: number;
    admission_number: string;
    first_name: string;
    last_name: string;
    stream_id: number | null;
    stream_name: string | null;
    status: string | null;
    remarks: string | null;
  }>;
  date?: string;
  error?: string;
}

export interface AttendanceHistoryResponse {
  success: boolean;
  history?: Array<{
    attendance_date: string;
    status: string;
    count: number;
  }>;
  error?: string;
}

export interface SaveAttendanceResponse {
  success: boolean;
  message?: string;
  error?: string;
}

export interface AutoMarkAbsentResponse {
  success: boolean;
  message?: string;
  error?: string;
}

export interface TeacherProfile {
  id: number;
  first_name: string;
  last_name: string;
  email: string;
  phone: string;
  id_number: string;
  address: string;
  subject: string;
  teacher_type: string;
  school_id: number;
  school_name: string;
  class_id: number | null;
  class_name: string | null;
  stream_id: number | null;
  stream_name: string | null;
  status: string;
  created_at: string;
}

export interface Subject {
  id: number;
  subject_name: string;
  subject_code: string | null;
  school_id: number;
  status: string;
}

export interface ProfileResponse {
  success: boolean;
  teacher?: TeacherProfile;
  subjects?: Subject[];
  error?: string;
}

export interface UpdateProfileResponse {
  success: boolean;
  message?: string;
  error?: string;
}

export interface ChangePasswordResponse {
  success: boolean;
  message?: string;
  error?: string;
}

export const getTeacherProfile = async (): Promise<ProfileResponse> => {
  try {
    const sessionData = await import('expo-secure-store').then(m => m.getItemAsync('teacherSession'));
    if (!sessionData) {
      throw new Error('NOT_AUTHENTICATED');
    }

    const session = JSON.parse(sessionData);
    const sessionToken = session.session_token;

    if (!sessionToken) {
      throw new Error('NOT_AUTHENTICATED');
    }

    const response = await fetch(`${API_BASE_URL}/profile.php?action=get_profile`, {
      method: 'GET',
      headers: {
        'Authorization': sessionToken,
      },
    });

    const data = await response.json() as ProfileResponse;

    if (data.success) {
      return data;
    } else {
      throw new Error(data.error || 'Failed to fetch profile');
    }
  } catch (error: any) {
    console.error('Get profile error:', error);
    if (error.message === 'NOT_AUTHENTICATED') {
      throw error;
    }
    throw error;
  }
};

export const updateTeacherProfile = async (profileData: {
  first_name: string;
  last_name: string;
  email: string;
  phone: string;
  id_number: string;
  address: string;
  subject: string;
}): Promise<UpdateProfileResponse> => {
  try {
    const sessionData = await import('expo-secure-store').then(m => m.getItemAsync('teacherSession'));
    if (!sessionData) {
      throw new Error('NOT_AUTHENTICATED');
    }

    const session = JSON.parse(sessionData);
    const sessionToken = session.session_token;

    if (!sessionToken) {
      throw new Error('NOT_AUTHENTICATED');
    }

    const formData = new FormData();
    formData.append('action', 'update_profile');
    formData.append('first_name', profileData.first_name);
    formData.append('last_name', profileData.last_name);
    formData.append('email', profileData.email);
    formData.append('phone', profileData.phone);
    formData.append('id_number', profileData.id_number);
    formData.append('address', profileData.address);
    formData.append('subject', profileData.subject);

    const response = await fetch(`${API_BASE_URL}/profile.php`, {
      method: 'POST',
      headers: {
        'Authorization': sessionToken,
      },
      body: formData,
    });

    const data = await response.json() as UpdateProfileResponse;

    if (data.success) {
      return data;
    } else {
      throw new Error(data.error || data.message || 'Failed to update profile');
    }
  } catch (error: any) {
    console.error('Update profile error:', error);
    if (error.message === 'NOT_AUTHENTICATED') {
      throw error;
    }
    throw error;
  }
};

export const changePassword = async (passwordData: {
  current_password: string;
  new_password: string;
  confirm_password: string;
}): Promise<ChangePasswordResponse> => {
  try {
    const sessionData = await import('expo-secure-store').then(m => m.getItemAsync('teacherSession'));
    if (!sessionData) {
      throw new Error('NOT_AUTHENTICATED');
    }

    const session = JSON.parse(sessionData);
    const sessionToken = session.session_token;

    if (!sessionToken) {
      throw new Error('NOT_AUTHENTICATED');
    }

    const formData = new FormData();
    formData.append('action', 'change_password');
    formData.append('current_password', passwordData.current_password);
    formData.append('new_password', passwordData.new_password);
    formData.append('confirm_password', passwordData.confirm_password);

    const response = await fetch(`${API_BASE_URL}/profile.php`, {
      method: 'POST',
      headers: {
        'Authorization': sessionToken,
      },
      body: formData,
    });

    const data = await response.json() as ChangePasswordResponse;

    if (data.success) {
      return data;
    } else {
      throw new Error(data.error || data.message || 'Failed to change password');
    }
  } catch (error: any) {
    console.error('Change password error:', error);
    if (error.message === 'NOT_AUTHENTICATED') {
      throw error;
    }
    throw error;
  }
};

export const autoMarkAbsent = async (classId: number): Promise<AutoMarkAbsentResponse> => {
  try {
    const sessionData = await import('expo-secure-store').then(m => m.getItemAsync('teacherSession'));
    if (!sessionData) {
      throw new Error('NOT_AUTHENTICATED');
    }

    const session = JSON.parse(sessionData);
    const sessionToken = session.session_token;

    if (!sessionToken) {
      throw new Error('NOT_AUTHENTICATED');
    }

    const formData = new FormData();
    formData.append('action', 'auto_mark_absent');
    formData.append('class_id', classId.toString());

    const response = await fetch(`${API_BASE_URL}/attendance.php`, {
      method: 'POST',
      headers: {
        'Authorization': sessionToken,
      },
      body: formData,
    });

    const data = await response.json() as AutoMarkAbsentResponse;

    if (data.success) {
      return data;
    } else {
      throw new Error(data.error || data.message || 'Failed to auto-mark absent');
    }
  } catch (error: any) {
    console.error('Auto-mark absent error:', error);
    if (error.message === 'NOT_AUTHENTICATED') {
      throw error;
    }
    throw error;
  }
};

export interface AssignmentsResponse {
  success: boolean;
  teacher?: {
    id: number;
    name: string;
    school_name: string;
    teacher_type: string;
    class_name: string | null;
  };
  calendar_status?: {
    is_holiday: boolean;
    school_status: string;
    current_holiday?: {
      holiday_name: string;
      start_date: string;
      end_date: string;
    };
    current_term?: {
      term_name: string;
      start_date: string;
      end_date: string;
    };
    current_year: number | null;
  };
  classes?: Array<{
    id: number;
    class_name: string;
  }>;
  subjects?: Array<{
    id: number;
    subject_name: string;
  }>;
  assignments?: Array<{
    id: number;
    title: string;
    description: string;
    assignment_type: string;
    file_name: string;
    file_path: string;
    class_id: number | null;
    subject_id: number | null;
    class_name: string | null;
    subject_name: string | null;
    teacher_name: string;
    due_date: string | null;
    created_at: string;
    download_count: number;
    comment_count: number;
  }>;
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
    
    if (data.success && data.session_token) {
      console.log('Session token from response:', data.session_token);
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
    // Get session token from SecureStore
    const sessionData = await import('expo-secure-store').then(m => m.getItemAsync('teacherSession'));
    if (!sessionData) {
      throw new Error('NOT_AUTHENTICATED');
    }
    
    const session = JSON.parse(sessionData);
    const sessionToken = session.session_token;
    
    if (!sessionToken) {
      throw new Error('NOT_AUTHENTICATED');
    }

    const response = await fetch(`${API_BASE_URL}/dashboard.php`, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': sessionToken,
      },
    });

    const data = await response.json() as DashboardResponse;
    
    if (data.success) {
      return data;
    } else {
      throw new Error(data.error || 'Failed to load dashboard');
    }
  } catch (error: any) {
    if (error.message === 'NOT_AUTHENTICATED') {
      throw error;
    }
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

    const data = await response.json() as { success: boolean; message?: string; error?: string };

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

// Assignments function
export const getAssignments = async (): Promise<AssignmentsResponse> => {
  try {
    // Get session token from SecureStore
    const sessionData = await import('expo-secure-store').then(m => m.getItemAsync('teacherSession'));
    if (!sessionData) {
      throw new Error('NOT_AUTHENTICATED');
    }

    const session = JSON.parse(sessionData);
    const sessionToken = session.session_token;

    if (!sessionToken) {
      throw new Error('NOT_AUTHENTICATED');
    }

    console.log('Fetching assignments with token:', sessionToken);

    const response = await fetch(`${API_BASE_URL}/assignments.php`, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': sessionToken,
      },
    });

    const responseText = await response.text();
    console.log('Assignments response text:', responseText);
    console.log('Response status:', response.status);

    const data = JSON.parse(responseText) as AssignmentsResponse;

    if (data.success && data.teacher) {
      return data;
    } else {
      throw new Error(data.error || 'Failed to load assignments');
    }
  } catch (error: any) {
    console.error('Assignments error:', error);
    if (error.message === 'NOT_AUTHENTICATED') {
      throw error;
    }
    throw error;
  }
};

// Results function
export const getResults = async (): Promise<ResultsResponse> => {
  try {
    const sessionData = await import('expo-secure-store').then(m => m.getItemAsync('teacherSession'));
    if (!sessionData) {
      throw new Error('NOT_AUTHENTICATED');
    }

    const session = JSON.parse(sessionData);
    const sessionToken = session.session_token;

    if (!sessionToken) {
      throw new Error('NOT_AUTHENTICATED');
    }

    const response = await fetch(`${API_BASE_URL}/results.php`, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': sessionToken,
      },
    });

    const data = await response.json() as ResultsResponse;

    if (data.success) {
      return data;
    } else {
      throw new Error(data.error || 'Failed to load results data');
    }
  } catch (error: any) {
    console.error('Results error:', error);
    if (error.message === 'NOT_AUTHENTICATED') {
      throw error;
    }
    throw error;
  }
};

// Performance function
export const getPerformance = async (): Promise<PerformanceResponse> => {
  try {
    // Get session token from SecureStore
    const sessionData = await import('expo-secure-store').then(m => m.getItemAsync('teacherSession'));
    if (!sessionData) {
      throw new Error('NOT_AUTHENTICATED');
    }

    const session = JSON.parse(sessionData);
    const sessionToken = session.session_token;

    if (!sessionToken) {
      throw new Error('NOT_AUTHENTICATED');
    }

    console.log('Fetching performance with token:', sessionToken);

    const response = await fetch(`${API_BASE_URL}/performance.php`, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': sessionToken,
      },
    });

    const responseText = await response.text();
    console.log('Performance response text:', responseText);
    console.log('Response status:', response.status);

    const data = JSON.parse(responseText) as PerformanceResponse;

    if (data.success && data.teacher) {
      return data;
    } else {
      throw new Error(data.error || 'Failed to load performance data');
    }
  } catch (error: any) {
    console.error('Performance error:', error);
    if (error.message === 'NOT_AUTHENTICATED') {
      throw error;
    }
    throw error;
  }
};

// Analytics function
export const getAssignmentAnalytics = async (assignmentId: number): Promise<AssignmentAnalyticsResponse> => {
  try {
    const sessionData = await import('expo-secure-store').then(m => m.getItemAsync('teacherSession'));
    if (!sessionData) {
      throw new Error('NOT_AUTHENTICATED');
    }

    const session = JSON.parse(sessionData);
    const sessionToken = session.session_token;

    if (!sessionToken) {
      throw new Error('NOT_AUTHENTICATED');
    }

    const response = await fetch(`${API_BASE_URL}/get_assignment_analytics.php?assignment_id=${assignmentId}`, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': sessionToken,
      },
    });

    const data = await response.json() as AssignmentAnalyticsResponse;

    if (data.success) {
      return data;
    } else {
      throw new Error(data.error || 'Failed to load analytics');
    }
  } catch (error: any) {
    console.error('Analytics error:', error);
    if (error.message === 'NOT_AUTHENTICATED') {
      throw error;
    }
    throw error;
  }
};

// Attendance functions
export const getAttendanceData = async (startDate?: string, endDate?: string): Promise<AttendanceResponse> => {
  try {
    const sessionData = await import('expo-secure-store').then(m => m.getItemAsync('teacherSession'));
    if (!sessionData) {
      throw new Error('NOT_AUTHENTICATED');
    }

    const session = JSON.parse(sessionData);
    const sessionToken = session.session_token;

    if (!sessionToken) {
      throw new Error('NOT_AUTHENTICATED');
    }

    let url = `${API_BASE_URL}/attendance.php`;
    if (startDate && endDate) {
      url += `?start_date=${startDate}&end_date=${endDate}`;
    }
    
    console.log('Fetching attendance data from:', url);

    const response = await fetch(url, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': sessionToken,
      },
    });

    const data = await response.json() as AttendanceResponse;
    console.log('Raw API response:', data);

    if (data.success) {
      return data;
    } else {
      throw new Error(data.error || 'Failed to load attendance data');
    }
  } catch (error: any) {
    console.error('Attendance data error:', error);
    if (error.message === 'NOT_AUTHENTICATED') {
      throw error;
    }
    throw error;
  }
};

export const getAttendanceStudents = async (classId: number, date: string): Promise<AttendanceStudentsResponse> => {
  try {
    const sessionData = await import('expo-secure-store').then(m => m.getItemAsync('teacherSession'));
    if (!sessionData) {
      throw new Error('NOT_AUTHENTICATED');
    }

    const session = JSON.parse(sessionData);
    const sessionToken = session.session_token;

    if (!sessionToken) {
      throw new Error('NOT_AUTHENTICATED');
    }

    const response = await fetch(`${API_BASE_URL}/attendance.php?action=get_students&class_id=${classId}&date=${date}`, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': sessionToken,
      },
    });

    const data = await response.json() as AttendanceStudentsResponse;

    if (data.success) {
      return data;
    } else {
      throw new Error(data.error || 'Failed to load students');
    }
  } catch (error: any) {
    console.error('Attendance students error:', error);
    if (error.message === 'NOT_AUTHENTICATED') {
      throw error;
    }
    throw error;
  }
};

export const saveAttendance = async (classId: number, date: string, attendance: Array<{student_id: number, status: string, remarks?: string}>): Promise<SaveAttendanceResponse> => {
  try {
    const sessionData = await import('expo-secure-store').then(m => m.getItemAsync('teacherSession'));
    if (!sessionData) {
      throw new Error('NOT_AUTHENTICATED');
    }

    const session = JSON.parse(sessionData);
    const sessionToken = session.session_token;

    if (!sessionToken) {
      throw new Error('NOT_AUTHENTICATED');
    }

    const formData = new FormData();
    formData.append('action', 'save_attendance');
    formData.append('class_id', String(classId));
    formData.append('date', date);
    formData.append('attendance', JSON.stringify(attendance));

    const response = await fetch(`${API_BASE_URL}/attendance.php`, {
      method: 'POST',
      headers: {
        'Authorization': sessionToken,
      },
      body: formData,
    });

    const data = await response.json() as SaveAttendanceResponse;

    if (data.success) {
      return data;
    } else {
      throw new Error(data.error || 'Failed to save attendance');
    }
  } catch (error: any) {
    console.error('Save attendance error:', error);
    if (error.message === 'NOT_AUTHENTICATED') {
      throw error;
    }
    throw error;
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
