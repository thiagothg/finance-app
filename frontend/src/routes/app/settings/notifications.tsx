import { createFileRoute } from "@tanstack/react-router";
import { NotificationPage } from "@/features/settings/notifications/NotificationsPage";

export const Route = createFileRoute("/app/settings/notifications")({
  component: NotificationPage,
});
