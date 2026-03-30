import { create } from "zustand";
import { createJSONStorage, persist } from "zustand/middleware";

import type {
  AuthPayload,
  User,
  VerificationChallenge,
} from "@/features/auth/types";

interface PendingVerification {
  email: string;
  message: string;
  verificationExpiresAt: string;
}

interface AuthState {
  accessToken: string | null;
  refreshToken: string | null;
  accessTokenExpiresAt: string | null;
  refreshTokenExpiresAt: string | null;
  user: User | null;
  pendingVerification: PendingVerification | null;
  setAuth: (payload: AuthPayload) => void;
  setPendingVerification: (challenge: VerificationChallenge) => void;
  clearPendingVerification: () => void;
  clearAuth: () => void;
}

export const useAuthStore = create<AuthState>()(
  persist(
    (set) => ({
      accessToken: null,
      refreshToken: null,
      accessTokenExpiresAt: null,
      refreshTokenExpiresAt: null,
      user: null,
      pendingVerification: null,
      setAuth: (payload: AuthPayload) =>
        set({
          accessToken: payload.access_token,
          refreshToken: payload.refresh_token,
          accessTokenExpiresAt: payload.access_expires_at,
          refreshTokenExpiresAt: payload.refresh_expires_at,
          user: payload.user,
          pendingVerification: null,
        }),
      setPendingVerification: (challenge: VerificationChallenge) =>
        set({
          accessToken: null,
          refreshToken: null,
          accessTokenExpiresAt: null,
          refreshTokenExpiresAt: null,
          user: null,
          pendingVerification: {
            email: challenge.email,
            message: challenge.message,
            verificationExpiresAt: challenge.verification_expires_at,
          },
        }),
      clearPendingVerification: () => set({ pendingVerification: null }),
      clearAuth: () =>
        set({
          accessToken: null,
          refreshToken: null,
          accessTokenExpiresAt: null,
          refreshTokenExpiresAt: null,
          user: null,
          pendingVerification: null,
        }),
    }),
    {
      name: "auth-storage",
      storage: createJSONStorage(() => localStorage),
    },
  ),
);
