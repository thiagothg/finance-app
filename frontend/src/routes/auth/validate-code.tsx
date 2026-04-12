import { createFileRoute } from "@tanstack/react-router";
import ValidateCodePage from "@/features/auth/otp/OtpPage";

export const Route = createFileRoute("/auth/validate-code")({
  component: ValidateCodePage,
});
