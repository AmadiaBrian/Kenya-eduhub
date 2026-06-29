import Constants from 'expo-constants';
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
};

export type AdminUser = {
  id: number | string;
  name: string;
  email: string;
  role: string;
  is_verified: number | string | boolean;
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

export const API_BASE_URL = `http://${getApiHost()}/kenyaeduhub/api`;

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
    throw new Error('The server returned an unexpected response.');
  }

  if (!response.ok || json.success === false) {
    throw new Error(json.message || 'Request failed. Please try again.');
  }

  return json;
}

export function login(email: string, password: string) {
  return request<{ user: ApiUser }>('login.php', {
    method: 'POST',
    body: JSON.stringify({ email, password }),
  });
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
  const json = await request<{ users: AdminUser[] }>('users.php');
  return json.users ?? [];
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
