import { useNavigate, useLocation } from "@tanstack/react-router";
import { useAuthStore } from "@/stores/authStore";
import { ConfirmDialog } from "@/components/ConfirmDialog";

interface SignOutDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
}

export function SignOutDialog({ open, onOpenChange }: SignOutDialogProps) {
  const navigate = useNavigate();
  const location = useLocation();
  const auth = useAuthStore();

  const handleSignOut = () => {
    auth.clearAuth();
    // Preserve current location for redirect after sign-in
    const currentPath = location.href;
    navigate({
      to: "/auth/login",
      search: { redirect: currentPath },
      replace: true,
    });
  };

  return (
    <ConfirmDialog
      open={open}
      onOpenChange={onOpenChange}
      title="Sign out"
      desc="Are you sure you want to sign out? You will need to sign in again to access your account."
      confirmText="Sign out"
      destructive
      handleConfirm={handleSignOut}
      className="sm:max-w-sm"
    />
  );
}
