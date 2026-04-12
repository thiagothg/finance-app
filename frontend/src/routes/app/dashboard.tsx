import { createFileRoute } from "@tanstack/react-router";
import Dashboard from "@/features/dashboard/DashboardPage";

export const Route = createFileRoute("/app/dashboard")({
  component: Dashboard,
});
