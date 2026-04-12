import { createFileRoute } from "@tanstack/react-router";
import AccountsPage from "@/features/management/accounts/AccountsPage";

export const Route = createFileRoute("/app/management/accounts")({
  component: AccountsPage,
});
