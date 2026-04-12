import { createFileRoute } from "@tanstack/react-router";
import { ProfilePage } from "@/features/settings/profile/ProfilePage";

export const Route = createFileRoute("/app/settings/profile")({
  component: ProfilePage,
});
