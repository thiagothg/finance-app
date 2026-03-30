---
name: react-vite
description: >
  Best practices for building React 19 applications with Vite, TypeScript, Tailwind CSS v4,
  TanStack Query, Zustand, React Hook Form, Zod, and shadcn/ui. Use this skill whenever the
  user asks to create, refactor, or extend any frontend code in this project — including
  components, pages, forms, API integration, state management, routing, or i18n. Trigger even
  for seemingly small tasks like "add a button" or "create a form" since they benefit from
  consistent patterns. Also trigger for questions about project structure, imports, or which
  library to use for a given frontend problem.
---

# React + Vite + TypeScript Skill

## Stack Overview

| Layer | Library | Version |
|---|---|---|
| Framework | React | 19 |
| Build tool | Vite | 8 |
| Language | TypeScript | 5.9 |
| Styling | Tailwind CSS | 4 (Vite plugin) |
| UI Components | shadcn/ui + Radix UI | latest |
| Server state | TanStack Query (React Query) | 5 |
| Client state | Zustand | 5 |
| Forms | React Hook Form + Zod | 7 / 4 |
| HTTP | Axios | 1 |
| Routing | React Router DOM | 7 |
| i18n | react-i18next | 17 |
| Icons | Lucide React | latest |
| Utilities | clsx + class-variance-authority | — |

---

## Project Structure

```
src/
├── assets/               # Static files (images, fonts)
├── components/
│   ├── ui/               # shadcn/ui primitives (Button, Input, Dialog…)
|   ├── common/           # Generic reusable components (e.g. Avatar, Badge)
│   └── [feature]/        # Feature-specific components that are not shared
├── features/             # Vertical slices: each feature owns its components, hooks, types
│   └── [feature]/
│       ├── components/
│       ├── hooks/
│       ├── types.ts
│       └── index.ts      # Public API of the feature
├── hooks/                # Shared custom hooks
├── lib/
│   ├── api.ts            # Axios instance + interceptors
│   ├── queryClient.ts    # TanStack Query client config
│   └── utils.ts          # cn() and other helpers
├── pages/                # Route-level components
│   ├── Auth/             # LoginPage, RegisterPage
│   ├── Dashboard/
│   └── Transactions/  
├── routes/               # React Router route definitions
├── store/                # Zustand stores
├── i18n/                 # i18next config + translation files
├── types/                # Global TypeScript types / interfaces
└── main.tsx
```

---

## TypeScript Rules

- **Always use explicit return types** on functions exported from a module.
- **Prefer `interface` over `type`** for object shapes; use `type` for unions/intersects.
- **Never use `any`** — use `unknown` and narrow, or a proper generic.
- **Use `satisfies`** to validate literals against a type without widening.
- Enable `strict: true` in `tsconfig.json` — it is already on.
- Path aliases are configured in `vite.config.ts` as `@/` → `src/`. Always use them.

```ts
// ✅ Good
import { Button } from '@/components/ui/button'
import type { Transaction } from '@/features/transactions/types'

// ❌ Bad
import { Button } from '../../components/ui/button'
```

---

## Components

### Functional components only — no class components.

```tsx
// ✅ Standard component shape
interface Props {
  title: string
  onConfirm: () => void
  children?: React.ReactNode
}

export function ConfirmDialog({ title, onConfirm, children }: Props) {
  return (
    <Dialog>
      <DialogHeader>
        <DialogTitle>{title}</DialogTitle>
      </DialogHeader>
      {children}
      <Button onClick={onConfirm}>Confirm</Button>
    </Dialog>
  )
}
```

### shadcn/ui Components

- Import from `@/components/ui/[component]` — never from `radix-ui` directly in pages/features.
- Add new shadcn components via `npx shadcn@latest add [component]` — do **not** hand-write them.
- Extend with `class-variance-authority` (CVA) when adding variants:

```ts
import { cva, type VariantProps } from 'class-variance-authority'

const badge = cva('rounded-full px-2 py-0.5 text-xs font-medium', {
  variants: {
    intent: {
      success: 'bg-green-100 text-green-800',
      danger: 'bg-red-100 text-red-800',
    },
  },
  defaultVariants: { intent: 'success' },
})

interface BadgeProps extends VariantProps<typeof badge> {
  label: string
}
```

### Styling with Tailwind CSS v4

- Tailwind v4 uses the Vite plugin (`@tailwindcss/vite`) — **no `tailwind.config.ts`** needed.
- CSS variables for design tokens live in `src/assets/globals.css` under `@theme { }`.
- Use `cn()` from `@/lib/utils` to merge classes conditionally:

```ts
import { cn } from '@/lib/utils'
// lib/utils.ts
import { clsx, type ClassValue } from 'clsx'
import { twMerge } from 'tailwind-merge'
export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs))
}
```

---

## Data Fetching — TanStack Query v5

### Setup

```ts
// src/lib/queryClient.ts
import { QueryClient } from '@tanstack/react-query'

export const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      staleTime: 1000 * 60 * 5, // 5 min
      retry: 1,
    },
  },
})
```

### Query hooks — co-locate with the feature

```ts
// features/transactions/hooks/useTransactions.ts
import { useQuery } from '@tanstack/react-query'
import { api } from '@/lib/api'
import type { Transaction } from '../types'

export const transactionKeys = {
  all: ['transactions'] as const,
  list: (params: Record<string, unknown>) => [...transactionKeys.all, 'list', params] as const,
  detail: (id: number) => [...transactionKeys.all, 'detail', id] as const,
}

export function useTransactions(params: { page: number }) {
  return useQuery({
    queryKey: transactionKeys.list(params),
    queryFn: () => api.get<Transaction[]>('/transactions', { params }).then(r => r.data),
  })
}
```

### Mutations

```ts
import { useMutation, useQueryClient } from '@tanstack/react-query'
import { api } from '@/lib/api'
import { transactionKeys } from './useTransactions'

export function useCreateTransaction() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (body: CreateTransactionInput) =>
      api.post<Transaction>('/transactions', body).then(r => r.data),
    onSuccess: () => qc.invalidateQueries({ queryKey: transactionKeys.all }),
  })
}
```

---

## HTTP Client — Axios + Laravel Sanctum

This project authenticates via **Laravel Sanctum token-based auth** (SPA or mobile flow).  
On login, the API returns a plain-text Bearer token — store it in Zustand + `localStorage` and attach it to every request.

```ts
// src/lib/api.ts
import axios from 'axios'
import { useAuthStore } from '@/store/authStore'

export const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL,          // e.g. http://localhost/api
  headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
})

// ── Request: attach Bearer token ──────────────────────────────────────────
api.interceptors.request.use(config => {
  const token = useAuthStore.getState().token
  if (token) config.headers.Authorization = `Bearer ${token}`
  return config
})

// ── Response: handle 401 globally ─────────────────────────────────────────
api.interceptors.response.use(
  response => response,
  error => {
    if (error.response?.status === 401) {
      useAuthStore.getState().logout()            // clear Zustand + persisted storage
      window.location.href = '/login'             // hard redirect — clears all query cache
    }
    return Promise.reject(error)
  },
)
```

> **Why `useAuthStore.getState()` and not a hook?**  
> Axios interceptors run outside React's render cycle, so hooks are not allowed. `.getState()` is Zustand's escape hatch for non-component code.

### Sanctum API contract (Laravel side)

| Endpoint | Method | Auth required | Description |
|---|---|---|---|
| `/api/login` | POST | ❌ | Returns `{ token, user }` |
| `/api/logout` | POST | ✅ Bearer | Revokes current token |
| `/api/user` | GET | ✅ Bearer | Returns authenticated user |
| `/api/register` | POST | ❌ | Creates account + returns `{ token, user }` |

### Auth store (Zustand)

```ts
// src/store/authStore.ts
import { create } from 'zustand'
import { persist } from 'zustand/middleware'
import type { User } from '@/features/auth/types'

interface AuthState {
  token: string | null
  user: User | null
  isAuthenticated: boolean
  setAuth: (token: string, user: User) => void
  logout: () => void
}

export const useAuthStore = create<AuthState>()(
  persist(
    set => ({
      token: null,
      user: null,
      isAuthenticated: false,
      setAuth: (token, user) => set({ token, user, isAuthenticated: true }),
      logout: () => set({ token: null, user: null, isAuthenticated: false }),
    }),
    { name: 'auth' },  // persisted to localStorage under key "auth"
  ),
)
```

### Protected routes

```tsx
// src/routes/ProtectedRoute.tsx
import { Navigate, Outlet } from 'react-router-dom'
import { useAuthStore } from '@/store/authStore'

export function ProtectedRoute() {
  const isAuthenticated = useAuthStore(s => s.isAuthenticated)
  return isAuthenticated ? <Outlet /> : <Navigate to="/login" replace />
}

// src/routes/GuestRoute.tsx — redirect away from /login if already authed
export function GuestRoute() {
  const isAuthenticated = useAuthStore(s => s.isAuthenticated)
  return isAuthenticated ? <Navigate to="/" replace /> : <Outlet />
}
```

```tsx
// src/routes/index.tsx
export const router = createBrowserRouter([
  {
    element: <GuestRoute />,
    children: [
      { path: '/login', element: <LoginPage /> },
      { path: '/register', element: <RegisterPage /> },
    ],
  },
  {
    element: <ProtectedRoute />,
    children: [
      {
        path: '/',
        element: <AppLayout />,
        children: [
          { index: true, element: <DashboardPage /> },
          { path: 'transactions', element: <TransactionsPage /> },
        ],
      },
    ],
  },
])
```

Environment variable naming: always prefix with `VITE_` for client-side exposure.

---

## Forms — React Hook Form + Zod

**Pattern: define schema → infer type → connect to RHF via `zodResolver`.**

```tsx
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import {
  Form, FormControl, FormField, FormItem, FormLabel, FormMessage,
} from '@/components/ui/form'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'

const schema = z.object({
  description: z.string().min(1, 'Required'),
  amount: z.coerce.number().positive('Must be positive'),
})

type FormValues = z.infer<typeof schema>

export function TransactionForm({ onSubmit }: { onSubmit: (v: FormValues) => void }) {
  const form = useForm<FormValues>({ resolver: zodResolver(schema) })

  return (
    <Form {...form}>
      <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-4">
        <FormField
          control={form.control}
          name="description"
          render={({ field }) => (
            <FormItem>
              <FormLabel>Description</FormLabel>
              <FormControl><Input {...field} /></FormControl>
              <FormMessage />
            </FormItem>
          )}
        />
        <Button type="submit" disabled={form.formState.isSubmitting}>
          Save
        </Button>
      </form>
    </Form>
  )
}
```

- **Always** use `z.coerce.number()` for numeric inputs (HTML inputs are always strings).
- Zod v4: use `z.string().min()`, `z.object()`, `z.discriminatedUnion()`, etc. — API is stable.

---

## Client State — Zustand v5

Use Zustand **only** for truly global UI state (auth session, theme, sidebar open/closed).  
Do **not** duplicate server state already managed by TanStack Query.

The **auth store** is defined in the Sanctum section above — it is the canonical example.  
Other stores follow the same shape:

```ts
// src/store/uiStore.ts
import { create } from 'zustand'

interface UiState {
  sidebarOpen: boolean
  toggleSidebar: () => void
}

export const useUiStore = create<UiState>()(set => ({
  sidebarOpen: true,
  toggleSidebar: () => set(s => ({ sidebarOpen: !s.sidebarOpen })),
}))
```

- Use the `persist` middleware for anything that must survive a page refresh.
- Keep stores small — one concern per store.
- Selectors avoid re-renders: `const token = useAuthStore(s => s.token)`.
- Outside React (interceptors, utils), use `.getState()` / `.setState()` directly.

---

## Routing — React Router v7

```tsx
// src/routes/index.tsx
import { createBrowserRouter } from 'react-router-dom'
import { AppLayout } from '@/components/AppLayout'
import { DashboardPage } from '@/pages/DashboardPage'
import { TransactionsPage } from '@/pages/TransactionsPage'

export const router = createBrowserRouter([
  {
    path: '/',
    element: <AppLayout />,
    children: [
      { index: true, element: <DashboardPage /> },
      { path: 'transactions', element: <TransactionsPage /> },
    ],
  },
])
```

- Use `<Outlet />` in layout components.
- Use `useNavigate`, `useParams`, `useSearchParams` from `react-router-dom`.
- Lazy-load heavy pages: `const Page = lazy(() => import('@/pages/HeavyPage'))`.

---

## Internationalisation — react-i18next

```ts
// src/i18n/index.ts
import i18n from 'i18next'
import { initReactI18next } from 'react-i18next'
import en from './locales/en.json'
import pt from './locales/pt.json'

i18n.use(initReactI18next).init({
  lng: 'pt',
  fallbackLng: 'en',
  resources: { en: { translation: en }, pt: { translation: pt } },
  interpolation: { escapeValue: false },
})

export default i18n
```

```tsx
// Usage in components
import { useTranslation } from 'react-i18next'

function AccountCard() {
  const { t } = useTranslation()
  return <h2>{t('accounts.title')}</h2>
}
```

- Keep translation keys namespaced: `accounts.title`, `transactions.empty_state`, etc.
- Never hardcode user-facing strings — always use `t()`.

---

## Error Handling

### API errors from Laravel

Laravel validation errors return `422` with `{ errors: { field: string[] } }`.  
Map them back to RHF:

```ts
mutation.mutate(data, {
  onError: (error) => {
    if (axios.isAxiosError(error) && error.response?.status === 422) {
      const errors = error.response.data.errors as Record<string, string[]>
      Object.entries(errors).forEach(([field, messages]) => {
        form.setError(field as keyof FormValues, { message: messages[0] })
      })
    }
  },
})
```

### Error Boundaries

Wrap route-level components in an `<ErrorBoundary>` using `react-error-boundary`:

```tsx
<ErrorBoundary fallback={<ErrorPage />}>
  <Outlet />
</ErrorBoundary>
```

---

## Performance Patterns

- **`React.memo`**: only when a component receives stable props and rerenders are measurably expensive.
- **`useMemo` / `useCallback`**: use sparingly — only when computation is costly or referential stability is required (e.g., passing callbacks to memoised children).
- **Code splitting**: use `React.lazy` + `Suspense` for route-level code splitting.
- **Query prefetching**: use `queryClient.prefetchQuery` in route loaders for critical data.

---

## Code Style

- **Prettier** config already present — always format before committing.
- **ESLint** with `react-hooks` and `import` plugins — fix all warnings.
- Named exports for components; default export only for pages (route-level).
- Avoid barrel files (`index.ts` that re-exports everything) except for feature public APIs.
- Keep components under ~150 lines — extract sub-components or hooks when larger.

---

## Checklist for New Features

1. Define Zod schemas and TypeScript types in `features/[name]/types.ts`
2. Create Axios query/mutation hooks in `features/[name]/hooks/`
3. Build UI components using shadcn/ui primitives + Tailwind
4. Wire forms with RHF + Zod
5. Add route in `src/routes/index.tsx` if it's a new page
6. Add i18n keys to all locale files
7. Handle loading, empty, and error states explicitly