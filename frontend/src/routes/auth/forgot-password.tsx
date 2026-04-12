import { createFileRoute } from "@tanstack/react-router";
import { ForgotPassword } from "@/features/auth/forgot-password/ForgotPasswordPage";

export const Route = createFileRoute("/auth/forgot-password")({
  component: ForgotPassword,
});
