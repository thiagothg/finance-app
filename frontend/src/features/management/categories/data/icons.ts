import {
  Car,
  Home,
  Heart,
  GraduationCap,
  Briefcase,
  Plane,
  ShoppingCart,
  Receipt,
  Gift,
  Film,
  Dumbbell,
  Dog,
  CircleDollarSign,
  Landmark,
} from "lucide-react";

export const categoryIcons = {
  Home: Home,
  Transport: Car,
  Health: Heart,
  Education: GraduationCap,
  Work: Briefcase,
  Travel: Plane,
  Shopping: ShoppingCart,
  Bills: Receipt,
  Gifts: Gift,
  Entertainment: Film,
  Fitness: Dumbbell,
  Pets: Dog,
  "Extra income": CircleDollarSign,
  Investments: Landmark,
} as const;

export type CategoryIconName = keyof typeof categoryIcons;
