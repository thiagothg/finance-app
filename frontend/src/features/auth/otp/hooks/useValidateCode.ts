import { useMutation } from "@tanstack/react-query";
import { useNavigate } from "@tanstack/react-router";

import { api } from "@/lib/api";
import { useAuthStore } from "@/stores/authStore";

import type { AuthResponse, ValidateCodeInput } from "../../types";

export function useValidateCode() {
  const navigate = useNavigate();
  const setAuth = useAuthStore((state) => state.setAuth);

  return useMutation({
    mutationFn: async (body: ValidateCodeInput): Promise<AuthResponse> => {
      const response = await api.post<AuthResponse>(
        "/auth/verify/validate-code",
        body
      );

      return response.data;
    },
    onSuccess: ({ data }) => {
      setAuth(data);
      navigate({ to: "/dashboard", replace: true });
    },
  });
}
