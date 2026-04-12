import { type BaseEntity } from "@/shared/types/base";

export type UserPreferences = {
  language?: string;
  date_format?: string;
  timezone?: string;
  currency_display?: string;
  display_name?: string;
};

export interface User extends BaseEntity {
  name: string;
  email: string;
  avatar?: string;
  preferences?: UserPreferences;
}

export type LoginInput = {
  email: string;
  password: string;
};

export type VerificationChallenge = {
  message: string;
  email: string;
  verification_expires_at: string;
};

export type ValidateCodeInput = {
  email: string;
  code: string;
};

export type AuthPayload = {
  user: User;
  access_token: string;
  refresh_token: string;
  access_expires_at: string;
  refresh_expires_at: string;
};

export type AuthResponse = {
  data: AuthPayload;
};
