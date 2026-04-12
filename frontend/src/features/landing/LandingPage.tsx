import { Link } from "@tanstack/react-router";
import { PATHS } from "@/config/paths";
import { X } from "lucide-react";
import { IconGithub } from "@/assets/brand-icons";
import { Button } from "@/components/ui/button";
import { AppLogo } from "@/components/layout/AppLogo";

export function LandingPage() {
  return (
    <div className="flex min-h-screen flex-col bg-gray-950 text-white">
      <header className="container mx-auto flex h-20 items-center justify-between px-4">
        <AppLogo />
        <div className="flex items-center gap-4">
          <Link to={PATHS.auth.login}>
            <Button
              variant="outline"
              className="border-white text-white transition-colors hover:bg-white hover:text-black"
            >
              Sign In
            </Button>
          </Link>
        </div>
      </header>

      <main className="flex-1">
        <section className="container mx-auto flex flex-col items-center justify-center px-4 py-20 text-center">
          <h1 className="bg-gradient-to-r from-white to-gray-400 bg-clip-text text-5xl leading-tight font-bold text-transparent md:text-6xl lg:text-7xl">
            Financial Clarity for a Brighter Future
          </h1>
          <p className="mt-6 max-w-2xl text-lg text-gray-300">
            Take control of your finances with a smart, simple, and secure
            platform. Join us and start your journey towards financial freedom.
          </p>
          <div className="mt-8 flex gap-4">
            <Link to={PATHS.auth.login}>
              <Button
                size="lg"
                className="bg-white text-black transition-colors hover:bg-gray-200"
              >
                Get Started
              </Button>
            </Link>
            <Button
              size="lg"
              variant="outline"
              className="border-white text-white transition-colors hover:bg-white hover:text-black"
            >
              Learn More
            </Button>
          </div>
        </section>

        <section className="container mx-auto px-4 py-16">
          <img
            src="/src/assets/hero.png"
            alt="Dashboard preview"
            className="mx-auto rounded-lg shadow-2xl"
          />
        </section>
      </main>

      <footer className="container mx-auto flex items-center justify-between px-4 py-6">
        <p className="text-sm text-gray-400">
          &copy; 2026 Your Company. All rights reserved.
        </p>
        <div className="flex gap-6">
          <a
            href="#"
            className="text-gray-400 transition-colors hover:text-white"
          >
            <IconGithub className="size-5" />
          </a>
          <a
            href="#"
            className="text-gray-400 transition-colors hover:text-white"
          >
            <X size={20} />
          </a>
        </div>
      </footer>
    </div>
  );
}
