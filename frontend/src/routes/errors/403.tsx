import { createFileRoute } from "@tanstack/react-router";
import { ForbiddenError } from "@/features/errors/forbidden/ForbiddenPage";

export const Route = createFileRoute("/errors/403")({
  component: ForbiddenError,
});
