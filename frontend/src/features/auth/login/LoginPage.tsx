import { Navigate } from "@tanstack/react-router";
import { useAuthStore } from "@/stores/authStore";
import { LoginForm } from "./components/LoginForm";
import { AuthLayout } from "@/components/layout/AuthLayout";
import {
  Card,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { PATHS } from "@/config/paths";

export default function LoginPage(): React.JSX.Element {
  const accessToken = useAuthStore((state) => state.accessToken);
  const pendingVerification = useAuthStore(
    (state) => state.pendingVerification
  );

  if (accessToken) {
    return <Navigate to={PATHS.dashboard} replace />;
  }

  if (pendingVerification) {
    return <Navigate to={PATHS.auth.validateCode} replace />;
  }

  return (
    <AuthLayout>
      <Card className="gap-4">
        <CardHeader>
          <CardTitle className="text-lg tracking-tight">Sign in</CardTitle>
          <CardDescription>
            Enter your email and password below to <br />
            log into your account
          </CardDescription>
        </CardHeader>
        <CardContent>
          <LoginForm />
        </CardContent>

        <CardFooter>
          <p className="px-8 text-center text-sm text-muted-foreground">
            By clicking sign in, you agree to our{" "}
            <a
              href="/terms"
              className="underline underline-offset-4 hover:text-primary"
            >
              Terms of Service
            </a>{" "}
            and{" "}
            <a
              href="/privacy"
              className="underline underline-offset-4 hover:text-primary"
            >
              Privacy Policy
            </a>
            .
          </p>
        </CardFooter>
      </Card>
    </AuthLayout>
  );
}
