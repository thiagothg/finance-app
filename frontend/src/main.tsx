import { StrictMode } from "react";
import { Suspense } from "react";
import { createRoot } from "react-dom/client";
import { QueryClientProvider } from "@tanstack/react-query";
import { RouterProvider } from "@tanstack/react-router";
import "@/i18n";
import { Spinner } from "@/components/ui/spinner";
import { AppProviders } from "./context/AppProviders";
import { queryClient, router } from "@/router";
// Styles
import "./styles/index.css";

// Render the app
const rootElement = document.getElementById("root")!;
if (!rootElement.innerHTML) {
  const root = createRoot(rootElement);
  root.render(
    <StrictMode>
      <QueryClientProvider client={queryClient}>
        <Suspense
          fallback={
            <div className="flex min-h-screen items-center justify-center bg-background text-foreground">
              <Spinner className="size-6" />
            </div>
          }
        >
          <AppProviders>
            <RouterProvider router={router} />
          </AppProviders>
        </Suspense>
      </QueryClientProvider>
    </StrictMode>
  );
}
