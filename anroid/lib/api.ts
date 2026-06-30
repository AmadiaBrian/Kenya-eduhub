import Constants from 'expo-constants';
import * as SecureStore from 'expo-secure-store';
import { Platform } from 'react-native';

export type ApiUser = {
  id: number | string;
  name: string;
  email: string;
  role: 'user' | 'admin' | string;
};

export type Resource = {
  id: number | string;
  title?: string | null;
  level?: string | null;
  subject?: string | null;
  type?: string | null;
  description?: string | null;
  filename?: string | null;
  downloads?: number | string | null;
  created_at?: string | null;
  user_id?: number | string | null;
  uploader_name?: string | null;
  uploader_email?: string | null;
  is_my_upload?: boolean;
};

export type AdminUser = {
  id: number | string;
  name: string;
  email: string;
  role: string;
  is_verified: number | string | boolean;
};

export type UserProfile = {
  id: number | string;
  name: string;
  email: string;
  role: string;
  is_verified: number | string | boolean;
  created_at?: string | null;
};

type ApiResponse<T = unknown> = {
  success: boolean;
  message?: string;
} & T;

function getApiHost() {
  if (Platform.OS === 'web') {
    return 'localhost';
  }

  const expoHost =
    Constants.expoConfig?.hostUri ||
    Constants.manifest2?.extra?.expoClient?.hostUri ||
    Constants.manifest?.debuggerHost;

  if (expoHost) {
    return expoHost.split(':')[0];
  }

  return Platform.OS === 'android' ? '10.0.2.2' : 'localhost';
}

export const API_BASE_URL = `https://agitated-silence-02871.pktriot.xyz/kenyaeduhub/api`;

async function request<T>(endpoint: string, options?: RequestInit): Promise<ApiResponse<T>> {
  const url = `${API_BASE_URL}/${endpoint}`;
  let response: Response;

  try {
    response = await fetch(url, {
      headers: {
        'Content-Type': 'application/json',
        ...(options?.headers ?? {}),
      },
      ...options,
    });
  } catch {
    throw new Error(
      `Cannot reach Kenya EduHub API at ${API_BASE_URL}. Make sure XAMPP Apache is running and your phone is on the same Wi-Fi as this computer.`,
    );
  }

  const text = await response.text();
  let json: ApiResponse<T>;

  try {
    json = JSON.parse(text);
  } catch {
    throw new Error('Unable to connect to the server. Please check your connection and try again.');
  }

  if (!response.ok || json.success === false) {
    throw new Error(json.message || 'Request failed. Please try again.');
  }

  return json;
}

export async function login(email: string, password: string) {
  const url = `${API_BASE_URL}/login.php`;
  
  const response = await fetch(url, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ email, password }),
  });
  
  const text = await response.text();
  console.log('Login response text:', text);
  console.log('Response status:', response.status);
  
  let json: ApiResponse<{ user: ApiUser; session_id?: string; csrf_token?: string }>;
  
  try {
    json = JSON.parse(text);
  } catch (error) {
    console.error('JSON parse error:', error);
    throw new Error('Unable to connect to the server. Please check your connection and try again.');
  }
  
  if (!response.ok || json.success === false) {
    throw new Error(json.message || 'Login failed. Please try again.');
  }
  
  // Store session ID from response body
  if (json.session_id) {
    console.log('Session ID from response:', json.session_id);
    await SecureStore.setItemAsync('kenya_eduhub_session_id', json.session_id);
  } else {
    console.warn('No session_id in login response');
  }
  
  // Store CSRF token from response body
  if (json.csrf_token) {
    console.log('CSRF token from response:', json.csrf_token);
    await SecureStore.setItemAsync('kenya_eduhub_csrf_token', json.csrf_token);
  } else {
    console.warn('No csrf_token in login response');
  }
  
  return json;
}

export function register(name: string, email: string, password: string) {
  return request<{ user: ApiUser }>('register.php', {
    method: 'POST',
    body: JSON.stringify({ name, email, password }),
  });
}

export function verifyAccount(email: string, code: string) {
  return request('verify.php', {
    method: 'POST',
    body: JSON.stringify({ email, code }),
  });
}

export function resendVerification(email: string) {
  return request('resend_verification.php', {
    method: 'POST',
    body: JSON.stringify({ email }),
  });
}

export function requestPasswordReset(email: string) {
  return request('forgot_password.php', {
    method: 'POST',
    body: JSON.stringify({ email }),
  });
}

export function verifyResetCode(email: string, code: string) {
  return request('verify_code.php', {
    method: 'POST',
    body: JSON.stringify({ email, code }),
  });
}

export function resetPassword(email: string, code: string, newPassword: string) {
  return request('reset_password.php', {
    method: 'POST',
    body: JSON.stringify({ email, code, newPassword }),
  });
}

export async function getResources() {
  const json = await request<{ resources: Resource[] }>('resources.php');
  return json.resources ?? [];
}

export async function getUsers() {
  console.log('Fetching users...');
  const url = `${API_BASE_URL}/users.php`;
  console.log('Request URL:', url);
  
  try {
    const response = await fetch(url, {
      headers: {
        'Content-Type': 'application/json',
      },
    });
    
    const text = await response.text();
    console.log('Response text:', text);
    console.log('Response status:', response.status);
    
    let json: ApiResponse<{ users: AdminUser[] }>;
    try {
      json = JSON.parse(text);
    } catch {
      throw new Error('Unable to connect to the server. Please check your connection and try again.');
    }
    
    if (!response.ok || json.success === false) {
      throw new Error(json.message || 'Request failed. Please try again.');
    }
    
    return json.users ?? [];
  } catch (error) {
    console.error('Error fetching users:', error);
    throw error;
  }
}

export function updateResource(resource: Required<Pick<Resource, 'id'>> & Partial<Resource>) {
  return request('update_resource.php', {
    method: 'POST',
    body: JSON.stringify({
      id: resource.id,
      title: resource.title,
      level: resource.level,
      subject: resource.subject,
      type: resource.type,
      description: resource.description,
    }),
  });
}

export function deleteResource(id: number | string) {
  return request('delete_resource.php', {
    method: 'POST',
    body: JSON.stringify({ id }),
  });
}

export function deleteUser(id: number | string) {
  return request('delete_user.php', {
    method: 'POST',
    body: JSON.stringify({ id }),
  });
}

export function updateDownloadCount(resourceId: number | string) {
  return request<{ downloads: number }>('download.php', {
    method: 'POST',
    body: JSON.stringify({ id: resourceId }),
  });
}

export function resourceDownloadUrl(resourceId: number | string) {
  return `${API_BASE_URL}/download.php?id=${encodeURIComponent(String(resourceId))}&download=true`;
}

export async function getProfile() {
  return request<{ user: UserProfile }>('profile.php', {
    method: 'GET',
  });
}

export async function updateProfile(data: { name: string; email: string }) {
  const formData = new FormData();
  formData.append('action', 'update_profile');
  formData.append('name', data.name);
  formData.append('email', data.email);

  const url = `${API_BASE_URL}/profile.php`;
  const response = await fetch(url, {
    method: 'POST',
    body: formData,
  });

  const text = await response.text();
  const json = JSON.parse(text);

  if (!response.ok || json.success === false) {
    throw new Error(json.message || 'Failed to update profile');
  }

  return json;
}

export async function changePassword(data: {
  current_password: string;
  new_password: string;
  confirm_password: string;
}) {
  const formData = new FormData();
  formData.append('action', 'change_password');
  formData.append('current_password', data.current_password);
  formData.append('new_password', data.new_password);
  formData.append('confirm_password', data.confirm_password);

  const url = `${API_BASE_URL}/profile.php`;
  const response = await fetch(url, {
    method: 'POST',
    body: formData,
  });

  const text = await response.text();
  const json = JSON.parse(text);

  if (!response.ok || json.success === false) {
    throw new Error(json.message || 'Failed to change password');
  }

  return json;
}

export async function updateUserRole(userId: number | string, role: string) {
  console.log('Updating user role:', { userId, role });
  const formData = new FormData();
  formData.append('action', 'update_role');
  formData.append('user_id', String(userId));
  formData.append('role', role);

  const url = `${API_BASE_URL}/users.php`;
  console.log('Request URL:', url);
  const response = await fetch(url, {
    method: 'POST',
    body: formData,
  });

  const text = await response.text();
  console.log('Response text:', text);
  console.log('Response status:', response.status);
  const json = JSON.parse(text);

  if (!response.ok || json.success === false) {
    throw new Error(json.message || 'Failed to update user role');
  }

  return json;
}

export async function toggleUserVerification(userId: number | string) {
  console.log('Toggling user verification:', { userId });
  const formData = new FormData();
  formData.append('action', 'toggle_verification');
  formData.append('user_id', String(userId));

  const url = `${API_BASE_URL}/users.php`;
  console.log('Request URL:', url);
  const response = await fetch(url, {
    method: 'POST',
    body: formData,
  });

  const text = await response.text();
  console.log('Response text:', text);
  console.log('Response status:', response.status);
  const json = JSON.parse(text);

  if (!response.ok || json.success === false) {
    throw new Error(json.message || 'Failed to toggle user verification');
  }

  return json;
}

export async function deleteUserAccount(userId: number | string) {
  console.log('Deleting user account:', { userId });
  const formData = new FormData();
  formData.append('action', 'delete_user');
  formData.append('user_id', String(userId));

  const url = `${API_BASE_URL}/users.php`;
  console.log('Request URL:', url);
  const response = await fetch(url, {
    method: 'POST',
    body: formData,
  });

  const text = await response.text();
  console.log('Response text:', text);
  console.log('Response status:', response.status);
  const json = JSON.parse(text);

  if (!response.ok || json.success === false) {
    throw new Error(json.message || 'Failed to delete user');
  }

  return json;
}

export async function uploadResource(formData: FormData) {
  const url = `${API_BASE_URL}/upload.php`;
  
  try {
    // Get session ID from SecureStore
    const sessionId = await SecureStore.getItemAsync('kenya_eduhub_session_id');
    if (!sessionId) {
      throw new Error('You must be logged in to upload resources.');
    }
    
    // Get CSRF token from SecureStore
    const csrfToken = await SecureStore.getItemAsync('kenya_eduhub_csrf_token');
    if (!csrfToken) {
      throw new Error('Security token not found. Please log in again.');
    }
    
    console.log('Using CSRF token:', csrfToken);
    
    // Add CSRF token to form data
    formData.append('csrf_token', csrfToken);
    
    const response = await fetch(url, {
      method: 'POST',
      body: formData,
      headers: {
        'Cookie': `PHPSESSID=${sessionId}`,
      },
    });
    
    const text = await response.text();
    console.log('Upload response:', text);
    let json: ApiResponse<{ file: string }>;
    
    try {
      // Strip PHP notices/errors from response before parsing JSON
      const jsonStart = text.indexOf('{');
      const jsonEnd = text.lastIndexOf('}');
      if (jsonStart !== -1 && jsonEnd !== -1) {
        const jsonText = text.substring(jsonStart, jsonEnd + 1);
        json = JSON.parse(jsonText);
      } else {
        json = JSON.parse(text);
      }
    } catch (e) {
      console.error('JSON parse error:', e);
      throw new Error('Unable to connect to the server. Please check your connection and try again.');
    }
    
    if (!response.ok || json.success === false) {
      // If CSRF token is invalid, clear it and ask user to log in again
      if (json.message && json.message.includes('security token')) {
        await SecureStore.deleteItemAsync('kenya_eduhub_csrf_token');
        throw new Error('Your security token has expired. Please log out and log in again.');
      }
      
      // Make error messages user-friendly
      let userMessage = json.message || 'Upload failed. Please try again.';
      
      if (userMessage.includes('already exists')) {
        userMessage = 'This file has already been uploaded. Please choose a different file.';
      } else if (userMessage.includes('Invalid file type')) {
        userMessage = 'This file type is not supported. Please upload a PDF, DOC, DOCX, PPT, PPTX, XLS, or XLSX file.';
      } else if (userMessage.includes('file size')) {
        userMessage = 'This file is too large. Please upload a smaller file.';
      } else if (userMessage.includes('security token')) {
        userMessage = 'Your security token has expired. Please log out and log in again.';
      }
      
      throw new Error(userMessage);
    }
    
    return json;
  } catch (error) {
    if (error instanceof Error) {
      throw error;
    }
    throw new Error('Upload failed. Please try again.');
  }
}
