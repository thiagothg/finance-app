### Categories feature

feature of categories

- api response:

```json
{
  "data": {
    "Expense": [
      {
        "id": 29,
        "name": "cum Category",
        "type": "Expense",
        "icon": "sapiente",
        "color": "#ec8ddf",
        "budget": 4402.16,
        "user_id": 40,
        "total_spend": 4366.38
      },
      {
        "id": 27,
        "name": "dolor Category",
        "type": "Expense",
        "icon": "eligendi",
        "color": "#4c7773",
        "budget": 3327.77,
        "user_id": 38,
        "total_spend": 11131.75
      }
    ],
    "Income": [
      {
        "id": 26,
        "name": "mollitia Category",
        "type": "Income",
        "icon": "non",
        "color": "#13f0c8",
        "budget": 4351.78,
        "user_id": 37,
        "total_spend": 8834.69
      },
      {
        "id": 30,
        "name": "nisi Category",
        "type": "Income",
        "icon": "provident",
        "color": "#cd43c3",
        "budget": 9191.64,
        "user_id": 41,
        "total_spend": 9546.82
      }
    ]
  },
  "meta": {
    "total_count": 5,
    "total_by_type": {
      "Expense": {
        "total_spent": 20328.690000000002,
        "count": 3,
        "total_budget": 9892.49,
        "remaining_budget": -10436.200000000003
      },
      "Income": {
        "total_spent": 18381.510000000002,
        "count": 2,
        "total_budget": 13543.419999999998,
        "remaining_budget": -4838.090000000004
      }
    }
  }
}
```

- Similiar to this:

```tsx
iimport { useState } from "react";
import { useNavigate } from "react-router-dom";
import { ArrowLeft, Plus, Edit2, Check, X, DollarSign, TrendingUp, TrendingDown } from "lucide-react";
import ScreenLayout from "@/components/ScreenLayout";
import WealthCard from "@/components/WealthCard";
import CategoryIcon, { categoryConfig } from "@/components/CategoryIcon";
import { mockCategories } from "@/data/mockData";
import { useIsMobile } from "@/hooks/use-mobile";
import { Tabs, TabsList, TabsTrigger, TabsContent } from "@/components/ui/tabs";
import { Dialog, DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Sheet, SheetContent, SheetHeader, SheetTitle } from "@/components/ui/sheet";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Button } from "@/components/ui/button";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { toast } from "@/hooks/use-toast";

type CategoryItem = { id: string; name: string; budget: number; type: "expense" | "income" };
type FormState = { name: string; budget: string; type: "expense" | "income"; icon: string };

const availableIcons = Object.keys(categoryConfig);

const emptyForm = (type: "expense" | "income"): FormState => ({ name: "", budget: "", type, icon: "other" });

const CategoryForm = ({ form, setForm, onSubmit, isEdit }: {
  form: FormState;
  setForm: React.Dispatch<React.SetStateAction<FormState>>;
  onSubmit: () => void;
  isEdit: boolean;
}) => (
  <div className="space-y-4 pt-2">
    <div className="space-y-2">
      <Label>Type</Label>
      <Select value={form.type} onValueChange={v => setForm(f => ({ ...f, type: v as "expense" | "income" }))}>
        <SelectTrigger><SelectValue /></SelectTrigger>
        <SelectContent>
          <SelectItem value="expense">Expense</SelectItem>
          <SelectItem value="income">Income</SelectItem>
        </SelectContent>
      </Select>
    </div>
    <div className="space-y-2">
      <Label>Name</Label>
      <Input placeholder="e.g. Subscriptions" value={form.name} onChange={e => setForm(f => ({ ...f, name: e.target.value }))} />
    </div>
    <div className="space-y-2">
      <Label>Monthly Budget</Label>
      <Input type="number" placeholder="500" value={form.budget} onChange={e => setForm(f => ({ ...f, budget: e.target.value }))} />
    </div>
    <div className="space-y-2">
      <Label>Icon</Label>
      <div className="flex flex-wrap gap-2">
        {availableIcons.map(key => (
          <CategoryIcon key={key} category={key} size="sm" selected={form.icon === key} onClick={() => setForm(f => ({ ...f, icon: key }))} />
        ))}
      </div>
    </div>
    <Button onClick={onSubmit} className="w-full" disabled={!form.name.trim()}>
      {isEdit ? "Save Changes" : "Add Category"}
    </Button>
  </div>
);

const CategoriesScreen = () => {
  const navigate = useNavigate();
  const isMobile = useIsMobile();
  const [categories, setCategories] = useState<CategoryItem[]>(mockCategories);
  const [open, setOpen] = useState(false);
  const [activeTab, setActiveTab] = useState("expense");
  const [form, setForm] = useState<FormState>(emptyForm("expense"));
  const [editingId, setEditingId] = useState<string | null>(null);

  const expenseCategories = categories.filter(c => c.type === "expense");
  const incomeCategories = categories.filter(c => c.type === "income");

  const totalExpenseBudget = expenseCategories.reduce((s, c) => s + c.budget, 0);
  const totalIncomeBudget = incomeCategories.reduce((s, c) => s + c.budget, 0);

  const handleSubmit = () => {
    if (!form.name.trim()) return;
    if (editingId) {
      setCategories(prev => prev.map(c => c.id === editingId ? { ...c, id: form.icon, name: form.name.trim(), budget: parseFloat(form.budget) || 0, type: form.type } : c));
      toast({ title: "Category updated", description: `${form.name.trim()} has been updated.` });
    } else {
      setCategories(prev => [...prev, { id: form.icon, name: form.name.trim(), budget: parseFloat(form.budget) || 0, type: form.type }]);
      toast({ title: "Category added", description: `${form.name.trim()} has been added.` });
    }
    resetAndClose();
  };

  const resetAndClose = () => {
    setForm(emptyForm(activeTab as "expense" | "income"));
    setEditingId(null);
    setOpen(false);
  };

  const handleOpen = () => {
    setEditingId(null);
    setForm(emptyForm(activeTab as "expense" | "income"));
    setOpen(true);
  };

  const handleEdit = (cat: CategoryItem) => {
    setEditingId(cat.id);
    setForm({ name: cat.name, budget: String(cat.budget), type: cat.type, icon: cat.id });
    setOpen(true);
  };

  const formTitle = editingId ? "Edit Category" : "Add Category";

  const formContent = (
    <CategoryForm form={form} setForm={setForm} onSubmit={handleSubmit} isEdit={!!editingId} />
  );

  // Mobile: bottom sheet. Desktop: dialog.
  const formModal = isMobile ? (
    <Sheet open={open} onOpenChange={v => { if (!v) resetAndClose(); else setOpen(true); }}>
      <SheetContent side="bottom" className="rounded-t-2xl px-5 pb-8">
        <SheetHeader className="pb-2">
          <SheetTitle>{formTitle}</SheetTitle>
        </SheetHeader>
        {formContent}
      </SheetContent>
    </Sheet>
  ) : (
    <Dialog open={open} onOpenChange={v => { if (!v) resetAndClose(); else setOpen(true); }}>
      <DialogContent className="max-w-md">
        <DialogHeader>
          <DialogTitle>{formTitle}</DialogTitle>
        </DialogHeader>
        {formContent}
      </DialogContent>
    </Dialog>
  );

  // --- Category card ---
  const CategoryCard = ({ cat }: { cat: CategoryItem }) => {
    const config = categoryConfig[cat.id] || categoryConfig.other;
    return (
      <WealthCard
        key={cat.id}
        className="flex items-center gap-3 md:gap-4 group cursor-default"
      >
        <CategoryIcon category={cat.id} size={isMobile ? "md" : "lg"} />
        <div className="flex-1 min-w-0">
          <p className="text-sm md:text-base font-bold text-foreground truncate">{cat.name}</p>
          <div className="flex items-center gap-1 mt-0.5">
            <DollarSign className="w-3 h-3 text-muted-foreground" />
            <p className="text-xs md:text-sm text-muted-foreground">{cat.budget.toLocaleString()}/mo</p>
          </div>
        </div>
        <button
          onClick={() => handleEdit(cat)}
          className="w-8 h-8 md:w-9 md:h-9 rounded-full bg-secondary hover:bg-accent flex items-center justify-center transition-colors md:opacity-0 md:group-hover:opacity-100"
        >
          <Edit2 className="w-3.5 h-3.5 md:w-4 md:h-4 text-muted-foreground" />
        </button>
      </WealthCard>
    );
  };

  // --- Summary card ---
  const SummaryCard = ({ label, total, icon: Icon, count }: { label: string; total: number; icon: React.ElementType; count: number }) => (
    <div className="rounded-xl bg-card border border-border p-4 md:p-5 flex items-center gap-4">
      <div className="w-10 h-10 md:w-12 md:h-12 rounded-xl bg-primary/10 flex items-center justify-center">
        <Icon className="w-5 h-5 md:w-6 md:h-6 text-primary" />
      </div>
      <div>
        <p className="text-xs text-muted-foreground">{label}</p>
        <p className="text-lg md:text-xl font-bold text-foreground">${total.toLocaleString()}</p>
        <p className="text-xs text-muted-foreground">{count} categories</p>
      </div>
    </div>
  );

  return (
    <ScreenLayout
      title="Categories"
      leftAction={
        <button onClick={() => navigate(-1)} className="w-9 h-9 rounded-full bg-secondary flex items-center justify-center md:hidden">
          <ArrowLeft className="w-4 h-4 text-foreground" />
        </button>
      }
      rightAction={
        <>
          {/* Mobile: icon-only FAB style */}
          <button onClick={handleOpen} className="w-9 h-9 rounded-full bg-primary flex items-center justify-center md:hidden">
            <Plus className="w-4 h-4 text-primary-foreground" />
          </button>
          {/* Desktop: labeled button */}
          <Button onClick={handleOpen} size="sm" className="hidden md:inline-flex gap-1.5">
            <Plus className="w-4 h-4" />
            Add Category
          </Button>
        </>
      }
    >
      <Tabs value={activeTab} onValueChange={setActiveTab} className="w-full">
        <TabsList className="w-full mb-4">
          <TabsTrigger value="expense" className="flex-1">Expense</TabsTrigger>
          <TabsTrigger value="income" className="flex-1">Income</TabsTrigger>
        </TabsList>

        <TabsContent value="expense">
          <div className="mb-4">
            <SummaryCard label="Total Expense Budget" total={totalExpenseBudget} icon={TrendingDown} count={expenseCategories.length} />
          </div>
          {/* Mobile: stacked list | Desktop: 2-col grid */}
          <div className="space-y-2 md:space-y-0 md:grid md:grid-cols-2 md:gap-3">
            {expenseCategories.map(cat => <CategoryCard key={cat.id} cat={cat} />)}
          </div>
        </TabsContent>

        <TabsContent value="income">
          <div className="mb-4">
            <SummaryCard label="Total Income Budget" total={totalIncomeBudget} icon={TrendingUp} count={incomeCategories.length} />
          </div>
          <div className="space-y-2 md:space-y-0 md:grid md:grid-cols-2 md:gap-3">
            {incomeCategories.map(cat => <CategoryCard key={cat.id} cat={cat} />)}
          </div>
        </TabsContent>
      </Tabs>

      {formModal}
    </ScreenLayout>
  );
};

export default CategoriesScreen;

```
