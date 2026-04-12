export type Plan = "free" | "pro" | "family";

export type Team = {
  name: string;
  logo: React.ElementType;
  plan: Plan;
};
