import { lazy, Suspense } from "react";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { BrowserRouter, Navigate, Route, Routes } from "react-router-dom";

import { AppLayout } from "@/layouts/AppLayout";
import { AuthLayout } from "@/layouts/AuthLayout";
import { Spinner } from "@/components/ui/spinner";
import { useAuthStore } from "@/store/authStore";
import { Toaster } from "./components/ui/sonner";

const LoginPage = lazy(() => import("@/pages/auth/LoginPage"));
const ValidateCodePage = lazy(() => import("@/pages/auth/ValidateCodePage"));
const DashboardPage = lazy(() => import("@/pages/DashboardPage"));
const CategoriesPage = lazy(() => import("@/pages/CategoriesPage"));

const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      retry: 1,
      refetchOnWindowFocus: false,
    },
  },
});

function HomeRedirect(): React.JSX.Element {
  const accessToken = useAuthStore((state) => state.accessToken);
  const pendingVerification = useAuthStore(
    (state) => state.pendingVerification,
  );

  if (accessToken) {
    return <Navigate to="/dashboard" replace />;
  }

  if (pendingVerification) {
    return <Navigate to="/auth/validate-code" replace />;
  }

  return <Navigate to="/auth/login" replace />;
}

function App(): React.JSX.Element {
  return (
    <QueryClientProvider client={queryClient}>
      <Suspense
        fallback={
          <div className="flex min-h-screen items-center justify-center bg-background text-foreground">
            <Spinner className="size-6" />
          </div>
        }
      >
        <BrowserRouter>
          <Routes>
            <Route element={<AuthLayout />}>
              <Route path="/auth/login" element={<LoginPage />} />
              <Route
                path="/auth/validate-code"
                element={<ValidateCodePage />}
              />
            </Route>

            <Route element={<AppLayout />}>
              <Route path="/dashboard" element={<DashboardPage />} />
              <Route path="/categories" element={<CategoriesPage />} />
            </Route>

            <Route path="/" element={<HomeRedirect />} />
          </Routes>
        </BrowserRouter>
      </Suspense>
      <Toaster />
    </QueryClientProvider>
  );
}

export default App;
