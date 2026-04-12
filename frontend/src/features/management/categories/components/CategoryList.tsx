import { useState } from "react";
import { Pencil, Trash2 } from "lucide-react";
import { useTranslation } from "react-i18next";
import { Button } from "@/components/ui/button";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { useDeleteCategory } from "../hooks/useCategories";
import type { Category } from "../types";
import { CategoryIcon } from "./CategoryIcon";

interface CategoryListProps {
  categories: Category[];
  onEdit: (category: Category) => void;
  tone?: "expense" | "income";
}

function formatCurrency(value: number): string {
  return new Intl.NumberFormat("en-US", {
    style: "currency",
    currency: "USD",
    maximumFractionDigits: 0,
  }).format(value);
}

export function CategoryList({
  categories,
  onEdit,
  tone = "expense",
}: CategoryListProps) {
  const { t } = useTranslation();
  const deleteCategory = useDeleteCategory();
  const [deletingCategoryId, setDeletingCategoryId] = useState<number | null>(
    null
  );

  const handleDeleteConfirm = () => {
    if (deletingCategoryId) {
      deleteCategory.mutate(deletingCategoryId);
      setDeletingCategoryId(null);
    }
  };

  const toneClass = tone === "expense" ? "bg-rose-500" : "bg-emerald-500";

  if (categories.length === 0) {
    return (
      <div className="rounded-3xl border border-dashed border-border bg-muted/30 px-6 py-10 text-center">
        <p className="text-sm text-muted-foreground">
          {t("categories.noCategories") || "No categories yet."}
        </p>
      </div>
    );
  }

  return (
    <>
      <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
        {categories.map((category) => {
          const spentPercentage =
            category.budget > 0
              ? Math.min((category.total_spend / category.budget) * 100, 100)
              : 0;
          const remaining = category.budget - category.total_spend;

          return (
            <div
              key={category.id}
              className="group flex flex-col rounded-3xl border border-border/70 bg-background/70 p-4 transition-all duration-200 hover:-translate-y-0.5 hover:border-border hover:shadow-sm"
            >
              <div className="flex items-start gap-4">
                <CategoryIcon
                  icon={category.icon}
                  color={category.color}
                  size="lg"
                />

                <div className="min-w-0 flex-1">
                  <div className="flex items-start justify-between gap-3">
                    <div className="min-w-0">
                      <p className="truncate text-base font-semibold text-foreground">
                        {category.name}
                      </p>
                      <p className="mt-1 text-sm text-muted-foreground">
                        {category.type} category
                      </p>
                    </div>

                    <div className="flex items-center gap-1 opacity-100 md:opacity-0 md:transition-opacity md:group-hover:opacity-100">
                      <Button
                        variant="ghost"
                        size="icon"
                        className="rounded-full"
                        onClick={() => onEdit(category)}
                      >
                        <Pencil className="size-4" />
                      </Button>
                      <Button
                        variant="ghost"
                        size="icon"
                        className="rounded-full text-red-500 hover:text-red-600"
                        onClick={() => setDeletingCategoryId(category.id)}
                      >
                        <Trash2 className="size-4" />
                      </Button>
                    </div>
                  </div>
                </div>
              </div>

              <div className="mt-4 flex-1 space-y-2">
                {tone === "expense" ? (
                  <div className="rounded-2xl bg-muted/40 px-4 py-3">
                    <div className="flex items-baseline justify-between">
                      <p className="text-xs tracking-wider text-muted-foreground uppercase">
                        Budget
                      </p>
                      <p className="text-base font-semibold text-foreground">
                        {formatCurrency(category.budget)}
                      </p>
                    </div>
                  </div>
                ) : null}

                <div className="rounded-2xl bg-muted/40 px-4 py-3">
                  <div className="flex items-baseline justify-between">
                    <p className="text-xs tracking-wider text-muted-foreground uppercase">
                      Spent
                    </p>
                    <p className="text-base font-semibold text-foreground">
                      {formatCurrency(category.total_spend)}
                    </p>
                  </div>
                </div>

                <div className="rounded-2xl bg-muted/40 px-4 py-3">
                  <div className="flex items-baseline justify-between">
                    <p className="text-xs tracking-wider text-muted-foreground uppercase">
                      Remaining
                    </p>
                    <p
                      className={`text-base font-semibold ${
                        remaining >= 0
                          ? "text-emerald-600 dark:text-emerald-400"
                          : "text-rose-600 dark:text-rose-400"
                      }`}
                    >
                      {formatCurrency(remaining)}
                    </p>
                  </div>
                </div>
              </div>

              <div className="mt-auto pt-4">
                {category.budget > 0 ? (
                  <div>
                    <div className="h-2 overflow-hidden rounded-full bg-muted">
                      <div
                        className={`h-full rounded-full transition-all ${toneClass}`}
                        style={{ width: `${spentPercentage}%` }}
                      />
                    </div>
                    <p className="mt-2 text-xs text-muted-foreground">
                      {spentPercentage.toFixed(0)}% of the planned budget used
                    </p>
                  </div>
                ) : null}
              </div>
            </div>
          );
        })}
      </div>

      <Dialog
        open={deletingCategoryId !== null}
        onOpenChange={(open) => {
          if (!open) setDeletingCategoryId(null);
        }}
      >
        <DialogContent>
          <DialogHeader>
            <DialogTitle>{t("categories.deleteTitle")}</DialogTitle>
            <DialogDescription>
              {t("categories.deleteDescription")}
            </DialogDescription>
          </DialogHeader>
          <DialogFooter className="flex gap-2">
            <Button
              variant="outline"
              onClick={() => setDeletingCategoryId(null)}
            >
              {t("common.cancel")}
            </Button>
            <Button variant="destructive" onClick={handleDeleteConfirm}>
              {t("common.delete")}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </>
  );
}
