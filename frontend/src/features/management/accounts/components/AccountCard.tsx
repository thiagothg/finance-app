import { useState } from "react";
import { Edit2, Trash2 } from "lucide-react";
import { cn } from "@/lib/utils";
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogHeader,
  AlertDialogTitle,
} from "@/components/ui/alert-dialog";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { banks } from "@/features/management/accounts/data/banks";
import { useDeleteAccount } from "../hooks/useAccounts";
import { type Account } from "../types";
import { BankLogo } from "./BankLogo";
import { EditAccountForm } from "./EditAccountForm";

interface AccountCardProps {
  account: Account;
}

export function AccountCard({ account }: AccountCardProps) {
  const bank = banks.find((b) => b.name === account.bank);
  const [editOpen, setEditOpen] = useState(false);
  const [deleteOpen, setDeleteOpen] = useState(false);
  const deleteAccount = useDeleteAccount();

  const handleDelete = () => {
    deleteAccount.mutate(account.id, {
      onSuccess: () => {
        setDeleteOpen(false);
      },
    });
  };

  return (
    <>
      <div className="flex items-center gap-4 rounded-lg border p-4">
        <div className="shrink-0">
          {bank && <BankLogo bank={bank} className="h-10 w-10" />}
        </div>
        <div className="grid flex-1 grid-cols-1 items-center sm:grid-cols-[1fr,auto] sm:gap-x-4">
          <div className="truncate">
            <div className="truncate text-sm font-medium">{account.name}</div>
            <div className="truncate text-xs text-muted-foreground">
              {account.institution} &middot; {account.type}
            </div>
          </div>
          <div className="mt-2 text-left">
            <div
              className={cn(
                "text-sm font-semibold",
                account.balance < 0 && "text-destructive"
              )}
            >
              {new Intl.NumberFormat("en-US", {
                style: "currency",
                currency: account.currency,
              }).format(account.balance)}
            </div>
          </div>
        </div>
        <div className="flex items-center gap-1 sm:gap-2">
          <button
            onClick={() => setEditOpen(true)}
            className="rounded-md p-1 transition-colors hover:bg-muted"
            aria-label="Edit account"
          >
            <Edit2 className="h-4 w-4 text-muted-foreground" />
          </button>
          <button
            onClick={() => setDeleteOpen(true)}
            className="rounded-md p-1 transition-colors hover:bg-muted"
            aria-label="Delete account"
          >
            <Trash2 className="h-4 w-4 text-destructive" />
          </button>
        </div>
      </div>

      {/* Edit Dialog */}
      <Dialog open={editOpen} onOpenChange={setEditOpen}>
        <DialogContent className="max-w-sm">
          <DialogHeader>
            <DialogTitle>Edit Account</DialogTitle>
            <DialogDescription>
              Make changes to your account here. Click save when you're done.
            </DialogDescription>
          </DialogHeader>
          <EditAccountForm
            account={account}
            onSuccess={() => setEditOpen(false)}
          />
        </DialogContent>
      </Dialog>

      {/* Delete Confirmation Dialog */}
      <AlertDialog open={deleteOpen} onOpenChange={setDeleteOpen}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Delete Account</AlertDialogTitle>
            <AlertDialogDescription>
              Are you sure you want to delete "{account.name}"? This action
              cannot be undone.
            </AlertDialogDescription>
          </AlertDialogHeader>
          <div className="flex gap-2">
            <AlertDialogCancel disabled={deleteAccount.isPending}>
              Cancel
            </AlertDialogCancel>
            <AlertDialogAction
              onClick={handleDelete}
              disabled={deleteAccount.isPending}
              className="text-destructive-foreground bg-destructive hover:bg-destructive/90 disabled:opacity-50"
            >
              {deleteAccount.isPending ? "Deleting..." : "Delete"}
            </AlertDialogAction>
          </div>
        </AlertDialogContent>
      </AlertDialog>
    </>
  );
}
