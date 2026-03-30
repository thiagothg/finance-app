import { useMutation } from "@tanstack/react-query";

import { api } from "@/lib/api";

interface ResendValidationCodeInput {
  email: string;
}

interface ResendValidationCodeResponse {
  message: string;
}

export function useResendValidationCode() {
  return useMutation({
    mutationFn: async (
      body: ResendValidationCodeInput,
    ): Promise<ResendValidationCodeResponse> => {
      const response = await api.post<ResendValidationCodeResponse>(
        "/auth/verify/resend-code",
        body,
      );

      return response.data;
    },
  });
}
