import { createFileRoute } from "@tanstack/react-router";

import CategoriesPage from "@/features/management/categories/CategoriesPage";

export const Route = createFileRoute("/app/management/categories")({
  component: CategoriesPage,
});
