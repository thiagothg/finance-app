import { useEffect } from "react";
import { z } from "zod";
import { type Resolver, useForm, useWatch } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { useTranslation } from "react-i18next";
import { useIsMobile } from "@/hooks/useMobile";
import { Button } from "@/components/ui/button";
import {
  Dialog,
  DialogContent,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import {
  Form,
  FormControl,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from "@/components/ui/form";
import { Input } from "@/components/ui/input";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import {
  Sheet,
  SheetContent,
  SheetFooter,
  SheetHeader,
  SheetTitle,
} from "@/components/ui/sheet";
import {
  useCreateCategory,
  useUpdateCategory,
} from "@/features/management/categories/hooks/useCategories";
import type { Category } from "@/features/management/categories/types";
import { CategoryIconPicker } from "./CategoryIconPicker";

const categoryFormSchema = z.object({
  name: z.string().min(1, "Name is required"),
  icon: z.string().min(1, "Icon is required"),
  color: z.string().min(1, "Color is required"),
  type: z.enum(["Expense", "Income"]),
  budget: z.coerce.number().positive().optional(),
});

type CategoryFormValues = z.infer<typeof categoryFormSchema>;

interface CategoryFormProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  category?: Category | null;
  type: "Expense" | "Income";
}

export function CategoryForm({
  open,
  onOpenChange,
  category,
  type,
}: CategoryFormProps) {
  const { t } = useTranslation();
  const isMobile = useIsMobile();
  const createCategory = useCreateCategory();
  const updateCategory = useUpdateCategory();

  const form = useForm<CategoryFormValues>({
    resolver: zodResolver(categoryFormSchema) as Resolver<CategoryFormValues>,
    defaultValues: {
      name: category?.name ?? "",
      icon: category?.icon ?? "",
      color: category?.color ?? "#000000",
      type,
      budget: category?.budget ?? undefined,
    },
  });

  const categoryType = useWatch({ control: form.control, name: "type" });

  useEffect(() => {
    form.reset({
      name: category?.name ?? "",
      icon: category?.icon ?? "Wallet",
      color: category?.color ?? "#0f766e",
      type,
      budget: category?.budget ?? undefined,
    });
  }, [category, form, type, open]);

  const onSubmit = (values: CategoryFormValues) => {
    const submittedData: Partial<Category> = {
      name: values.name,
      icon: values.icon,
      color: values.color,
      type: values.type,
      budget: values.budget ?? 0,
    };

    if (category) {
      updateCategory.mutate(
        { id: category.id, ...submittedData },
        {
          onSuccess: () => onOpenChange(false),
        }
      );
    } else {
      createCategory.mutate(submittedData, {
        onSuccess: () => onOpenChange(false),
      });
    }
  };

  const formContent = (
    <Form {...form}>
      <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-4">
        <FormField
          control={form.control}
          name="type"
          render={({ field }) => (
            <FormItem>
              <FormLabel>{t("categories.type")}</FormLabel>
              <Select
                onValueChange={field.onChange}
                defaultValue={field.value}
                disabled={!!category}
              >
                <FormControl>
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                </FormControl>
                <SelectContent>
                  <SelectItem value="Expense">
                    {t("categories.typeExpense")}
                  </SelectItem>
                  <SelectItem value="Income">
                    {t("categories.typeIncome")}
                  </SelectItem>
                </SelectContent>
              </Select>
              <FormMessage />
            </FormItem>
          )}
        />
        <FormField
          control={form.control}
          name="name"
          render={({ field }) => (
            <FormItem>
              <FormLabel>{t("categories.name")}</FormLabel>
              <FormControl>
                <Input {...field} />
              </FormControl>
              <FormMessage />
            </FormItem>
          )}
        />
        {categoryType === "Expense" && (
          <FormField
            control={form.control}
            name="budget"
            render={({ field }) => (
              <FormItem>
                <FormLabel>{t("categories.budget")}</FormLabel>
                <FormControl>
                  <Input type="number" {...field} />
                </FormControl>
                <FormMessage />
              </FormItem>
            )}
          />
        )}
        <FormField
          control={form.control}
          name="color"
          render={({ field }) => (
            <FormItem>
              <FormLabel>{t("categories.color")}</FormLabel>
              <FormControl>
                <div className="flex items-center gap-3">
                  <Input
                    type="color"
                    {...field}
                    className="h-12 w-20 cursor-pointer"
                  />
                  <span className="font-mono text-sm text-muted-foreground">
                    {field.value.toUpperCase()}
                  </span>
                </div>
              </FormControl>
              <FormMessage />
            </FormItem>
          )}
        />
        <FormField
          control={form.control}
          name="icon"
          render={({ field }) => (
            <FormItem>
              <FormLabel>{t("categories.icon")}</FormLabel>
              <FormControl>
                <CategoryIconPicker
                  currentIcon={field.value}
                  onSelectIcon={field.onChange}
                />
              </FormControl>
              <FormMessage />
            </FormItem>
          )}
        />
      </form>
    </Form>
  );

  const dialogTitle = category
    ? t("categories.editCategory")
    : t("categories.newCategory");

  const submitButtonLabel = category ? t("common.edit") : t("common.create");

  const submitButton = (
    <Button type="submit" onClick={() => form.handleSubmit(onSubmit)()}>
      {submitButtonLabel}
    </Button>
  );

  const cancelButton = (
    <Button type="button" variant="outline" onClick={() => onOpenChange(false)}>
      {t("common.cancel")}
    </Button>
  );

  if (isMobile) {
    return (
      <Sheet open={open} onOpenChange={onOpenChange}>
        <SheetContent side="bottom" className="rounded-t-2xl">
          <SheetHeader className="mb-4">
            <SheetTitle>{dialogTitle}</SheetTitle>
          </SheetHeader>
          <div className="max-h-[80vh] overflow-y-auto px-1">{formContent}</div>
          <SheetFooter className="mt-6 flex flex-row gap-2">
            {cancelButton}
            {submitButton}
          </SheetFooter>
        </SheetContent>
      </Sheet>
    );
  }

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>{dialogTitle}</DialogTitle>
        </DialogHeader>
        {formContent}
        <DialogFooter>
          {cancelButton}
          {submitButton}
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
