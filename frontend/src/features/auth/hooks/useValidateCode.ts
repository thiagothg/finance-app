import { useMutation } from "@tanstack/react-query";
import { useNavigate } from "react-router-dom";

import { api } from "@/lib/api";
import { useAuthStore } from "@/store/authStore";

import type { AuthResponse, ValidateCodeInput } from "../types";

export function useValidateCode() {
  const navigate = useNavigate();
  const setAuth = useAuthStore((state) => state.setAuth);

  return useMutation({
    mutationFn: async (body: ValidateCodeInput): Promise<AuthResponse> => {
      const response = await api.post<AuthResponse>(
        "/auth/verify/validate-code",
        body,
      );

      return response.data;
    },
    onSuccess: ({ data }) => {
      setAuth(data);
      navigate("/dashboard", { replace: true });
    },
  });
}
