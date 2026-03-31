import { useMutation } from "@tanstack/react-query";
import { useNavigate } from "react-router-dom";

import { api } from "@/lib/api";
import { useAuthStore } from "@/store/authStore";

import type { LoginInput, VerificationChallenge } from "../types";

export function useLogin() {
  const navigate = useNavigate();
  const setPendingVerification = useAuthStore(
    (state) => state.setPendingVerification,
  );

  return useMutation({
    mutationFn: async (body: LoginInput): Promise<VerificationChallenge> => {
      const response = await api.post<VerificationChallenge>(
        "/auth/login",
        body,
      );

      return response.data;
    },
    onSuccess: (challenge) => {
      setPendingVerification(challenge);
      navigate("/auth/validate-code", { replace: true });
    },
  });
}
