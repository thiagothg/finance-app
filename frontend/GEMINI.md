# GEMINI Project Context

This document provides a comprehensive overview of the frontend project to be used as instructional context for Gemini.

## Project Overview

This is a modern frontend application for a financial management dashboard. It is built using a robust and type-safe stack, including **React**, **Vite**, **TypeScript**, and **Tailwind CSS**.

The architecture is designed for scalability and maintainability, incorporating best-in-class libraries for common frontend tasks:

*   **UI Components:** The project uses **shadcn/ui**, a component library built on top of **Radix UI** primitives and styled with Tailwind CSS. Icons are provided by **Lucide React**.
*   **Routing:** Client-side routing is handled by **React Router DOM**, with separate layouts for authenticated users (`AppLayout`) and public-facing authentication pages (`AuthLayout`).
*   **Data Fetching:** Data is fetched from a backend API using **TanStack React Query**, which provides caching, synchronization, and server state management. HTTP requests are made using **Axios**.
*   **State Management:** Global client-side state, particularly for authentication, is managed with **Zustand**.
*   **Forms & Validation:** Forms are built using **React Hook Form** for performance and validated against schemas defined with **Zod**.
*   **Internationalization (i18n):** The application supports multiple languages using **react-i18next**.

## Building and Running

The project is managed with npm. Key commands are defined in `package.json`.

*   **To run the development server:**
    ```bash
    npm run dev
    ```

*   **To build the project for production:**
    ```bash
    npm run build
    ```
    This command first runs the TypeScript compiler (`tsc -b`) to check for type errors and then uses Vite to bundle the application.

*   **To lint the code:**
    ```bash
    npm run lint
    ```

*   **To preview the production build locally:**
    ```bash
    npm run preview
    ```

## Development Conventions

*   **Styling:** Styling is done exclusively with **Tailwind CSS**. The configuration in `tailwind.config.ts` includes a theming system based on CSS variables (HSL) for colors, radii, and fonts, which is characteristic of a shadcn/ui setup. The presence of financial category colors (e.g., `cat-housing`, `cat-food`) strongly indicates this is a finance-related application.
*   **API Communication:** All backend communication is handled through a pre-configured Axios instance found in `src/lib/api.ts`. This instance automatically intercepts requests to inject a JWT Bearer token from the Zustand auth store, simplifying authenticated API calls. The backend API URL is configured via the `VITE_API_URL` environment variable.
*   **Path Aliases:** A path alias `@{/}` is configured in `vite.config.ts` and `tsconfig.json` to point to the `src` directory, simplifying import statements (e.g., `import { AppLayout } from "@/layouts/AppLayout";`).
*   **Code Quality:** Code quality is enforced with ESLint. The configuration can be found in `eslint.config.js`.
*   **Type Safety:** The project is written entirely in TypeScript and includes strict type-checking as part of the build process.
