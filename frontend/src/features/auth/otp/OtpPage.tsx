import { Navigate } from "@tanstack/react-router";
import { useAuthStore } from "@/stores/authStore";
import { Button } from "@/components/ui/button";
import {
  Card,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { AuthLayout } from "@/components/layout/AuthLayout";
import { OtpForm } from "./components/OtpForm";
import { useResendValidationCode } from "./hooks/useResendValidationCode";
import { PATHS } from "@/config/paths";

export default function ValidateCodePage(): React.JSX.Element {
  const accessToken = useAuthStore((state) => state.accessToken);
  const pendingVerification = useAuthStore(
    (state) => state.pendingVerification
  );
  const resendValidationCode = useResendValidationCode();

  if (accessToken) {
    return <Navigate to={PATHS.dashboard} replace />;
  }

  if (!pendingVerification) {
    return <Navigate to={PATHS.auth.login} replace />;
  }

  return (
    <AuthLayout>
      <Card className="gap-4">
        <CardHeader>
          <CardTitle className="text-base tracking-tight">
            Two-factor Authentication
          </CardTitle>
          <CardDescription>
            Please enter the authentication code. <br /> We have sent the
            authentication code to{" "}
            <span className="font-medium">{pendingVerification.email}</span>.
          </CardDescription>
        </CardHeader>
        <CardContent>
          <OtpForm />
        </CardContent>
        <CardFooter>
          <div className="w-full px-8 text-center text-sm text-muted-foreground">
            {resendValidationCode.isSuccess ? (
              <p className="mb-2 text-primary">
                {resendValidationCode.data.message}
              </p>
            ) : null}
            <Button
              type="button"
              variant="link"
              className="h-auto p-0 text-sm text-muted-foreground underline underline-offset-4 hover:text-primary"
              disabled={resendValidationCode.isPending}
              onClick={() => {
                resendValidationCode.mutate({
                  email: pendingVerification.email,
                });
              }}
            >
              Resend a new code.
            </Button>
          </div>
        </CardFooter>
      </Card>
    </AuthLayout>
  );
}
