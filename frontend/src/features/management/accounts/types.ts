export type Account = {
  id: string;
  userId: string;
  userName: string;
  name: string;
  balance: number;
  initialBalance: number;
  type: "Checking" | "Savings";
  currency: string;
  isClosed: boolean;
  closeAt: string | null;
  institution: string;
  bank: string;
  createdAt: string;
  updatedAt: string;
};

export type ApiAccount = {
  id: number | string;
  user_id: number | string;
  user_name: string;
  name: string;
  balance: number;
  initial_balance: number;
  type: "Checking" | "Savings";
  currency: string;
  is_closed: boolean;
  close_at: string | null;
  institution?: string;
  bank: string;
  created_at: string;
  updated_at: string;
};
