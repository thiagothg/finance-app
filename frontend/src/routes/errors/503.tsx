import { createFileRoute } from "@tanstack/react-router";
import { Maintenance } from "@/features/errors/maintenance/MaintenancePage";

export const Route = createFileRoute("/errors/503")({
  component: Maintenance,
});
