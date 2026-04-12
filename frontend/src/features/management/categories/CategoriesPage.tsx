import { useState } from "react";
import {
  AlertCircle,
  Plus,
  Search,
  TrendingDown,
  TrendingUp,
} from "lucide-react";
import { useTranslation } from "react-i18next";
import { Alert, AlertDescription } from "@/components/ui/alert";
import { Button } from "@/components/ui/button";
import { Skeleton } from "@/components/ui/skeleton";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { ConfigDrawer } from "@/components/ConfigDrawer";
import { ProfileDropdown } from "@/components/ProfileDropdown";
import ScreenLayout from "@/components/ScreenLayout";
import { ThemeSwitch } from "@/components/ThemeSwitch";
import WealthCard from "@/components/WealthCard";
import { Header } from "@/components/layout/Header";
import type { Category } from "../categories/types";
import { useGetCategories } from ".//hooks/useCategories";
import { CategoryForm } from "./components/CategoryForm";
import { CategoryList } from "./components/CategoryList";

type CategoryType = "Expense" | "Income";

function formatCurrency(value: number): string {
  return new Intl.NumberFormat("en-US", {
    style: "currency",
    currency: "USD",
    maximumFractionDigits: 0,
  }).format(value);
}

function SummaryCard({
  label,
  count,
  budget,
  spent,
  remaining,
  icon: Icon,
  tone,
}: {
  label: string;
  count: number;
  budget: number;
  spent: number;
  remaining: number;
  icon: React.ElementType;
  tone: "expense" | "income";
}) {
  const toneClasses =
    tone === "expense"
      ? {
          surface:
            "border-rose-200/70 bg-linear-to-br from-rose-50 via-background to-background dark:border-rose-900/40 dark:from-rose-950/30",
          icon: "bg-rose-500/12 text-rose-600 dark:text-rose-400",
          accent: "text-rose-600 dark:text-rose-400",
        }
      : {
          surface:
            "border-emerald-200/70 bg-linear-to-br from-emerald-50 via-background to-background dark:border-emerald-900/40 dark:from-emerald-950/30",
          icon: "bg-emerald-500/12 text-emerald-600 dark:text-emerald-400",
          accent: "text-emerald-600 dark:text-emerald-400",
        };

  return (
    <>
      <WealthCard
        className={`items-start gap-4 rounded-3xl border px-5 py-5 shadow-none ${toneClasses.surface}`}
      >
        <div
          className={`flex size-12 shrink-0 items-center justify-center rounded-2xl ${toneClasses.icon}`}
        >
          <Icon className="size-6" />
        </div>

        <div className="min-w-0 flex-1">
          <div className="flex items-start justify-between gap-3">
            <div>
              <p className="text-xs font-semibold tracking-[0.24em] text-muted-foreground uppercase">
                {label}
              </p>
              <p className="mt-2 text-2xl font-semibold tracking-[-0.04em] text-foreground">
                {formatCurrency(budget)}
              </p>
            </div>
            <div className="rounded-full bg-background/80 px-3 py-1 text-xs font-medium text-muted-foreground ring-1 ring-border/60">
              {count} total
            </div>
          </div>

          <div className="mt-4 grid grid-cols-3 gap-3">
            <div className="rounded-2xl bg-background/80 px-3 py-3 ring-1 ring-border/50">
              <p className="text-[11px] tracking-[0.2em] text-muted-foreground uppercase">
                Budget
              </p>
              <p className="mt-2 text-sm font-semibold text-foreground">
                {formatCurrency(budget)}
              </p>
            </div>
            <div className="rounded-2xl bg-background/80 px-3 py-3 ring-1 ring-border/50">
              <p className="text-[11px] tracking-[0.2em] text-muted-foreground uppercase">
                Spent
              </p>
              <p className="mt-2 text-sm font-semibold text-foreground">
                {formatCurrency(spent)}
              </p>
            </div>
            <div className="rounded-2xl bg-background/80 px-3 py-3 ring-1 ring-border/50">
              <p className="text-[11px] tracking-[0.2em] text-muted-foreground uppercase">
                Remaining
              </p>
              <p className={`mt-2 text-sm font-semibold ${toneClasses.accent}`}>
                {formatCurrency(remaining)}
              </p>
            </div>
          </div>
        </div>
      </WealthCard>
    </>
  );
}

function LoadingState() {
  return (
    <div className="space-y-6">
      <div className="flex gap-3">
        <Skeleton className="h-12 w-32 rounded-full" />
        <Skeleton className="h-12 w-32 rounded-full" />
      </div>

      <div className="grid grid-cols-1 gap-5 xl:grid-cols-[1.15fr_1fr]">
        <Skeleton className="h-55 rounded-[32px]" />
        <Skeleton className="h-55 rounded-[32px]" />
      </div>

      <div className="rounded-[32px] border bg-card px-5 py-5">
        <div className="mb-5 flex items-center justify-between">
          <div className="space-y-2">
            <Skeleton className="h-6 w-32" />
            <Skeleton className="h-4 w-48" />
          </div>
          <Skeleton className="h-10 w-28 rounded-full" />
        </div>
        <div className="space-y-3">
          <Skeleton className="h-24 rounded-2xl" />
          <Skeleton className="h-24 rounded-2xl" />
          <Skeleton className="h-24 rounded-2xl" />
        </div>
      </div>
    </div>
  );
}

export default function CategoriesPage() {
  const { t } = useTranslation();
  const { data: categoriesData, isLoading, error } = useGetCategories();
  const [isFormOpen, setIsFormOpen] = useState(false);
  const [editingCategory, setEditingCategory] = useState<Category | null>(null);
  const [formType, setFormType] = useState<CategoryType>("Expense");
  const [activeTab, setActiveTab] = useState<CategoryType>("Expense");

  const handleOpenForm = (
    type: CategoryType,
    category: Category | null = null
  ) => {
    setFormType(type);
    setEditingCategory(category);
    setIsFormOpen(true);
  };

  if (error) {
    return (
      <ScreenLayout title={t("categories.title")}>
        <Alert variant="destructive" className="rounded-2xl">
          <AlertCircle className="h-4 w-4" />
          <AlertDescription>
            {t("common.error") ||
              "Failed to load categories. Please try again."}
          </AlertDescription>
        </Alert>
      </ScreenLayout>
    );
  }

  const expenseCategories = categoriesData?.data.Expense ?? [];
  const incomeCategories = categoriesData?.data.Income ?? [];
  const expenseMetadata = categoriesData?.meta.total_by_type.Expense;
  const incomeMetadata = categoriesData?.meta.total_by_type.Income;
  const activeCategories =
    activeTab === "Expense" ? expenseCategories : incomeCategories;
  const activeTone = activeTab === "Expense" ? "expense" : "income";
  const ActiveIcon = activeTab === "Expense" ? TrendingDown : TrendingUp;

  return (
    <>
      <Header fixed>
        <Search />
        <div className="ms-auto flex items-center space-x-4">
          <ThemeSwitch />
          <ConfigDrawer />
          <ProfileDropdown />
        </div>
      </Header>

      <ScreenLayout
        title={t("categories.title")}
        rightAction={
          <Button
            size="sm"
            className="rounded-full px-4"
            onClick={() => handleOpenForm(activeTab)}
          >
            <Plus className="size-4" />
            {t("categories.addCategory")}
          </Button>
        }
      >
        {isLoading ? (
          <LoadingState />
        ) : (
          <div className="space-y-6">
            <Tabs
              value={activeTab}
              onValueChange={(value) => setActiveTab(value as CategoryType)}
              className="w-full"
            >
              <TabsList className="mb-4 w-full">
                <TabsTrigger value="Expense" className="flex-1">
                  Expense
                </TabsTrigger>
                <TabsTrigger value="Income" className="flex-1">
                  Income
                </TabsTrigger>
              </TabsList>

              <TabsContent value="Expense">
                <div className="mb-4">
                  <SummaryCard
                    label={t("categories.expense")}
                    count={expenseMetadata?.count ?? 0}
                    budget={expenseMetadata?.total_budget ?? 0}
                    spent={expenseMetadata?.total_spent ?? 0}
                    remaining={expenseMetadata?.remaining_budget ?? 0}
                    icon={ActiveIcon}
                    tone={activeTone}
                  />
                </div>

                <CategoryList
                  categories={activeCategories}
                  onEdit={(category) => handleOpenForm(activeTab, category)}
                  tone={activeTone}
                />
              </TabsContent>

              <TabsContent value="Income">
                <div className="mb-4">
                  <SummaryCard
                    label={t("categories.income")}
                    count={incomeMetadata?.count ?? 0}
                    budget={incomeMetadata?.total_budget ?? 0}
                    spent={incomeMetadata?.total_spent ?? 0}
                    remaining={incomeMetadata?.remaining_budget ?? 0}
                    icon={ActiveIcon}
                    tone={activeTone}
                  />
                </div>

                <CategoryList
                  categories={activeCategories}
                  onEdit={(category) => handleOpenForm(activeTab, category)}
                  tone={activeTone}
                />
              </TabsContent>
            </Tabs>
          </div>
        )}

        <CategoryForm
          open={isFormOpen}
          onOpenChange={setIsFormOpen}
          category={editingCategory}
          type={formType}
        />
      </ScreenLayout>
    </>
  );
}
