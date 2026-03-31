import { useTranslation } from "react-i18next";
import { Navigate, Outlet, useLocation } from "react-router-dom";

import { AppSidebar } from "@/components/AppSidebar";
import { ThemeToggle } from "@/components/ThemeToggle";
import { SidebarProvider, SidebarTrigger } from "@/components/ui/sidebar";
import { useAuthStore } from "@/store/authStore";

export function AppLayout(): React.JSX.Element {
  const { t } = useTranslation();
  const location = useLocation();
  const accessToken = useAuthStore((state) => state.accessToken);
  const user = useAuthStore((state) => state.user);

  if (!accessToken) {
    return <Navigate to="/auth/login" replace />;
  }

  return (
    <SidebarProvider>
      <AppSidebar />
      <div className="relative flex flex-1 flex-col bg-background transition-all duration-200 ease-linear peer-data-[state=expanded]:ml-48">
        <header className="sticky top-0 border-b border-border/70 bg-background/70 px-4 py-4 backdrop-blur-xl sm:px-6 lg:px-8">
          <div className="flex items-center justify-between gap-4">
            <div className="flex items-center gap-3">
              <SidebarTrigger />

              <div>
                <p className="text-sm text-muted-foreground">
                  {t("app_shell.header.caption")}
                </p>
                <h1 className="text-xl font-semibold tracking-[-0.03em] text-foreground">
                  {location.pathname === "/dashboard"
                    ? t("app_shell.header.dashboard")
                    : t("app.name")}
                </h1>
              </div>
            </div>

            <div className="flex items-center gap-3">
              <ThemeToggle className="rounded-2xl border-border/70 bg-background/80" />
              <div className="hidden rounded-2xl border border-border/70 bg-card/90 px-4 py-3 shadow-sm backdrop-blur sm:block">
                <p className="text-xs uppercase tracking-[0.22em] text-muted-foreground">
                  {t("app_shell.header.active_user")}
                </p>
                <p className="mt-1 text-sm font-semibold text-foreground">
                  {user?.email ?? t("app_shell.secure_session")}
                </p>
              </div>
            </div>
          </div>
        </header>

        <main className="flex-1 overflow-y-auto px-4 py-6 sm:px-6 lg:px-8">
          <div className="mx-auto w-full max-w-7xl">
            <Outlet />
          </div>
        </main>
      </div>
    </SidebarProvider>
  );
}
