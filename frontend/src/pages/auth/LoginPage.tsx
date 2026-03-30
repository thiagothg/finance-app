import { useTranslation } from "react-i18next";
import { Navigate } from "react-router-dom";

import { AppLogo } from "@/components/AppLogo";
import { LoginForm } from "@/features/auth";
import { useAuthStore } from "@/store/authStore";

export default function LoginPage(): React.JSX.Element {
  const { t } = useTranslation();
  const accessToken = useAuthStore((state) => state.accessToken);
  const pendingVerification = useAuthStore(
    (state) => state.pendingVerification,
  );

  if (accessToken) {
    return <Navigate to="/dashboard" replace />;
  }

  if (pendingVerification) {
    return <Navigate to="/validate-code" replace />;
  }

  return (
    <section className="mx-auto w-full max-w-md">
      <div className="mb-10">
        <AppLogo compact className="mb-6 lg:hidden" />
        <p className="text-sm font-semibold uppercase tracking-[0.28em] text-primary">
          {t("auth.login.eyebrow")}
        </p>
        <h1 className="mt-4 text-4xl leading-[0.95] font-semibold tracking-[-0.05em] text-foreground sm:text-5xl">
          {t("auth.login.title_line_1")}
          <br />
          {t("auth.login.title_line_2")}
        </h1>
        <p className="mt-3 text-base leading-7 text-muted-foreground">
          {t("auth.login.subtitle")}
        </p>
      </div>

      <LoginForm />
    </section>
  );
}
