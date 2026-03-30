import { Outlet } from "react-router-dom";

import { AppLogo } from "@/components/AppLogo";
import { ThemeToggle } from "@/components/ThemeToggle";

export function AuthLayout(): React.JSX.Element {
  return (
    <main className="grid min-h-screen lg:grid-cols-2">
      <section
        className="relative hidden min-h-screen items-center justify-center overflow-hidden border-r border-border/60 bg-[radial-
        gradient(circle_at_top,hsl(var(--primary)/0.12),transparent_34%),linear-gradient(180deg,hsl(44_52%_96%)_0%,hsl(147_28%_90%)_100%)] px-10
         py-10 lg:flex"
      >
        <div className="relative z-10 flex items-center justify-center">
          <AppLogo className="scale-110" />
        </div>
      </section>

      <section className="flex min-h-screen px-5 py-8 sm:px-8 lg:px-12 xl:px-16">
        <div className="mx-auto flex w-full max-w-xl flex-col">
          <div className="mb-10 flex items-center justify-between gap-4">
            <div className="lg:hidden">
              <AppLogo />
            </div>
            <ThemeToggle className="rounded-2xl" />
          </div>

          <Outlet />
        </div>
      </section>
    </main>
  );
}
