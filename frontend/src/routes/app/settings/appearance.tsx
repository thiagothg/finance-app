import { createFileRoute } from "@tanstack/react-router";
import { AppearancePage } from "@/features/settings/appearance/AppearancePage";

export const Route = createFileRoute("/app/settings/appearance")({
  component: AppearancePage,
});
