export interface User {
  id: number;
  name: string;
  email: string;
  created_at: string;
}

export interface LoginInput {
  email: string;
  password: string;
}

export interface VerificationChallenge {
  message: string;
  email: string;
  verification_expires_at: string;
}

export interface ValidateCodeInput {
  email: string;
  code: string;
}

export interface AuthPayload {
  user: User;
  access_token: string;
  refresh_token: string;
  access_expires_at: string;
  refresh_expires_at: string;
}

export interface AuthResponse {
  data: AuthPayload;
}
