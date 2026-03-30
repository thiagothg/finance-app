import { ShieldCheck } from "lucide-react";
import { useTranslation } from "react-i18next";
import { Navigate } from "react-router-dom";

import { Button } from "@/components/ui/button";
import { ValidateCodeForm } from "@/features/auth";
import { useAuthStore } from "@/store/authStore";

export default function ValidateCodePage(): React.JSX.Element {
  const { t } = useTranslation();
  const accessToken = useAuthStore((state) => state.accessToken);
  const pendingVerification = useAuthStore((state) => state.pendingVerification);
  const clearAuth = useAuthStore((state) => state.clearAuth);

  if (accessToken) {
    return <Navigate to="/dashboard" replace />;
  }

  if (!pendingVerification) {
    return <Navigate to="/login" replace />;
  }

  return (
    <section className="w-full">
      <div className="mb-10 max-w-lg">
        <div className="mb-5 flex size-14 items-center justify-center rounded-2xl bg-primary/10 text-primary">
          <ShieldCheck className="size-7" />
        </div>
        <p className="text-sm font-semibold uppercase tracking-[0.28em] text-primary">
          {t("auth.validate_code.eyebrow")}
        </p>
        <h1 className="mt-4 text-4xl font-semibold tracking-[-0.04em] text-foreground">
          {t("auth.validate_code.title")}
        </h1>
        <p className="mt-4 max-w-md text-base leading-7 text-muted-foreground">
          {t("auth.validate_code.subtitle", {
            email: pendingVerification.email,
          })}
        </p>
      </div>

      <section className="max-w-lg">
        <ValidateCodeForm />
        <Button
          type="button"
          variant="outline"
          className="mt-6 rounded-2xl"
          onClick={() => clearAuth()}
        >
          {t("auth.logout")}
        </Button>
      </section>
    </section>
  );
}
