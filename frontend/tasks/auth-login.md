# Feature: Login

Handles user authentication via **Laravel Sanctum** token-based login.  
On success, stores the Bearer token + user in Zustand (persisted), then redirects to `/valitate-code`.

---

## File Structure

```
src/features/auth/
├── components/
│   └── LoginForm.tsx          # Form UI — email + password fields
├── hooks/
│   └── useLogin.ts            # Mutation: POST /api/login
├── types.ts                   # LoginInput, AuthResponse, User
└── index.ts                   # Public API re-exports
```

```
src/pages/
└── LoginPage.tsx              # Route-level page, renders LoginForm
```

```
src/i18n/locales/
├── en.json                    # { "auth": { "login": { ... } } }
└── pt.json
```

---

## Types — `features/auth/types.ts`

```ts
export interface User {
  id: number;
  name: string;
  email: string;
  created_at: string;
}

export interface LoginInput {
  email: string;
  password: string;
}

export interface AuthResponse {
  token: string;
  user: User;
}
```

---

## Mutation Hook — `features/auth/hooks/useLogin.ts`

```ts
import { useMutation } from "@tanstack/react-query";
import { useNavigate } from "react-router-dom";
import { api } from "@/lib/api";
import { useAuthStore } from "@/store/authStore";
import type { AuthResponse, LoginInput } from "../types";

export function useLogin() {
  const navigate = useNavigate();
  const setAuth = useAuthStore((s) => s.setAuth);

  return useMutation({
    mutationFn: (body: LoginInput) =>
      api.post<AuthResponse>("/login", body).then((r) => r.data),

    onSuccess: ({ token, user }) => {
      setAuth(token, user); // persisted to localStorage via Zustand
      navigate("/", { replace: true });
    },
  });
}
```

> No `queryClient.invalidateQueries` needed here — login is a write-only mutation with no
> cached read to invalidate. The Zustand store is the source of truth for auth state.

---

## Zod Schema — inside `LoginForm.tsx`

```ts
import { z } from "zod";

const loginSchema = z.object({
  email: z.string().email("auth.login.errors.email_invalid"),
  password: z.string().min(8, "auth.login.errors.password_min"),
});

type LoginFormValues = z.infer<typeof loginSchema>;
```

> Pass the i18n key as the error message — `<FormMessage />` will call `t(message)` automatically
> if you wire it that way, or translate in the `onError` handler.

---

## Form Component — `features/auth/components/LoginForm.tsx`

```tsx
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { z } from "zod";
import axios from "axios";
import { useTranslation } from "react-i18next";
import { useLogin } from "../hooks/useLogin";
import {
  Form,
  FormControl,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from "@/components/ui/form";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { Alert, AlertDescription } from "@/components/ui/alert";

const loginSchema = z.object({
  email: z.string().email(),
  password: z.string().min(8),
});

type LoginFormValues = z.infer<typeof loginSchema>;

export function LoginForm() {
  const { t } = useTranslation();
  const login = useLogin();

  const form = useForm<LoginFormValues>({
    resolver: zodResolver(loginSchema),
    defaultValues: { email: "", password: "" },
  });

  function onSubmit(values: LoginFormValues) {
    login.mutate(values, {
      onError: (error) => {
        // Map Laravel 422 validation errors back to fields
        if (axios.isAxiosError(error) && error.response?.status === 422) {
          const errors = error.response.data.errors as Record<string, string[]>;
          Object.entries(errors).forEach(([field, messages]) => {
            form.setError(field as keyof LoginFormValues, {
              message: messages[0],
            });
          });
          return;
        }
        // 401 = wrong credentials — show a general alert
        if (axios.isAxiosError(error) && error.response?.status === 401) {
          form.setError("root", {
            message: t("auth.login.errors.invalid_credentials"),
          });
        }
      },
    });
  }

  return (
    <Form {...form}>
      <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-4">
        {/* Root-level error (wrong credentials) */}
        {form.formState.errors.root && (
          <Alert variant="destructive">
            <AlertDescription>
              {form.formState.errors.root.message}
            </AlertDescription>
          </Alert>
        )}

        <FormField
          control={form.control}
          name="email"
          render={({ field }) => (
            <FormItem>
              <FormLabel>{t("auth.login.email")}</FormLabel>
              <FormControl>
                <Input type="email" autoComplete="email" {...field} />
              </FormControl>
              <FormMessage />
            </FormItem>
          )}
        />

        <FormField
          control={form.control}
          name="password"
          render={({ field }) => (
            <FormItem>
              <FormLabel>{t("auth.login.password")}</FormLabel>
              <FormControl>
                <Input
                  type="password"
                  autoComplete="current-password"
                  {...field}
                />
              </FormControl>
              <FormMessage />
            </FormItem>
          )}
        />

        <Button type="submit" className="w-full" disabled={login.isPending}>
          {login.isPending ? t("common.loading") : t("auth.login.submit")}
        </Button>
      </form>
    </Form>
  );
}
```

---

## Page — `pages/LoginPage.tsx`

```tsx
import { LoginForm } from "@/features/auth/components/LoginForm";
import { useTranslation } from "react-i18next";

export default function LoginPage() {
  const { t } = useTranslation();

  return (
    <div className="flex min-h-screen items-center justify-center bg-muted/40">
      <div className="w-full max-w-sm space-y-6 rounded-xl border bg-card p-8 shadow-sm">
        <div className="space-y-1 text-center">
          <h1 className="text-2xl font-bold">{t("auth.login.title")}</h1>
          <p className="text-sm text-muted-foreground">
            {t("auth.login.subtitle")}
          </p>
        </div>
        <LoginForm />
      </div>
    </div>
  );
}
```

---

## Route Registration — `routes/index.tsx`

```tsx
import { lazy } from 'react'
import { GuestRoute } from './GuestRoute'

const LoginPage = lazy(() => import('@/pages/LoginPage'))

// Inside createBrowserRouter:
{
  element: <GuestRoute />,
  children: [
    { path: '/login', element: <LoginPage /> },
  ],
}
```

---

## i18n Keys

```json
// en.json
{
  "auth": {
    "login": {
      "title": "Welcome back",
      "subtitle": "Sign in to your account",
      "email": "Email",
      "password": "Password",
      "submit": "Sign in",
      "errors": {
        "email_invalid": "Enter a valid email address",
        "password_min": "Password must be at least 8 characters",
        "invalid_credentials": "Incorrect email or password"
      }
    }
  }
}
```

```json
// pt.json
{
  "auth": {
    "login": {
      "title": "Bem-vindo de volta",
      "subtitle": "Entre na sua conta",
      "email": "E-mail",
      "password": "Senha",
      "submit": "Entrar",
      "errors": {
        "email_invalid": "Digite um e-mail válido",
        "password_min": "A senha deve ter pelo menos 8 caracteres",
        "invalid_credentials": "E-mail ou senha incorretos"
      }
    }
  }
}
```

---

## Error Handling Summary

| HTTP status | Cause                     | Handling                                          |
| ----------- | ------------------------- | ------------------------------------------------- |
| `422`       | Laravel validation failed | Map `errors` to form fields via `form.setError`   |
| `401`       | Wrong credentials         | Set `form.setError('root', ...)` → shown as Alert |
| `429`       | Too many attempts         | Show generic toast / root error                   |
| `5xx`       | Server error              | Caught by Axios interceptor → toast notification  |

---

## Checklist

- [ ] `features/auth/types.ts` — `User`, `LoginInput`, `AuthResponse`
- [ ] `features/auth/hooks/useLogin.ts` — mutation + `setAuth` + navigate
- [ ] `features/auth/components/LoginForm.tsx` — RHF + Zod + error handling
- [ ] `pages/LoginPage.tsx` — centered card layout
- [ ] Route registered under `<GuestRoute />`
- [ ] i18n keys added to `en.json` and `pt.json`
- [ ] `autoComplete` attributes set on inputs (accessibility + password managers)
- [ ] Loading state on submit button (`login.isPending`)

# Design

Follow a similiar design

```tsx
<div className="min-h-screen bg-background flex items-center justify-center px-5 py-10">
  <div className="w-full max-w-md">
    <div className="mb-10">
      <div className="w-14 h-14 rounded-2xl bg-primary flex items-center justify-center mb-6">
        <span className="text-primary-foreground text-2xl font-bold">C</span>
      </div>
      <h1 className="text-display text-foreground">
        Welcome
        <br />
        back.
      </h1>
      <p className="text-muted-foreground mt-2">
        Sign in to manage your family finances.
      </p>
    </div>

    <form onSubmit={handleLogin} className="space-y-4">
      <div className="relative">
        <Mail className="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-muted-foreground" />
        <input
          type="email"
          placeholder="Email address"
          value={email}
          onChange={(e) => setEmail(e.target.value)}
          className="w-full h-14 pl-12 pr-4 rounded-lg bg-secondary text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary text-sm"
        />
      </div>

      <div className="relative">
        <Lock className="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-muted-foreground" />
        <input
          type={showPassword ? "text" : "password"}
          placeholder="Password"
          value={password}
          onChange={(e) => setPassword(e.target.value)}
          className="w-full h-14 pl-12 pr-12 rounded-lg bg-secondary text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary text-sm"
        />
        <button
          type="button"
          onClick={() => setShowPassword(!showPassword)}
          className="absolute right-4 top-1/2 -translate-y-1/2 text-muted-foreground"
        >
          {showPassword ? (
            <EyeOff className="w-5 h-5" />
          ) : (
            <Eye className="w-5 h-5" />
          )}
        </button>
      </div>

      <div className="text-right">
        <button type="button" className="text-sm text-primary font-semibold">
          Forgot password?
        </button>
      </div>

      <button
        type="submit"
        className="w-full h-14 rounded-lg bg-primary text-primary-foreground font-bold text-base flex items-center justify-center gap-2 active:scale-[0.98] transition-transform"
      >
        Sign In <ArrowRight className="w-5 h-5" />
      </button>
    </form>

    <p className="text-center mt-8 text-sm text-muted-foreground">
      Don't have an account?{" "}
      <button
        onClick={() => navigate("/register")}
        className="text-primary font-semibold"
      >
        Sign up
      </button>
    </p>
  </div>
</div>
```
