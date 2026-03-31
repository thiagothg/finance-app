export interface Category {
  id: number;
  name: string;
  type: "Expense" | "Income";
  icon: string;
  color: string;
  budget: number;
  user_id: number;
  total_spend: number;
}

export interface CategoriesApiResponse {
  data: {
    Expense: Category[];
    Income: Category[];
  };
  meta: {
    total_count: number;
    total_by_type: {
      Expense: CategoryTypeSummary;
      Income: CategoryTypeSummary;
    };
  };
}

export interface CategoryTypeSummary {
  total_spent: number;
  count: number;
  total_budget: number;
  remaining_budget: number;
}
