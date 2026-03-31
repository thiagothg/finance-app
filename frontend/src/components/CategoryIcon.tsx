import {
  Home,
  Utensils,
  Car,
  ShoppingBag,
  Film,
  Heart,
  Zap,
  MoreHorizontal,
  Briefcase,
  GraduationCap,
  Plane,
  Gift,
  Wifi,
  Dumbbell,
  Coffee,
  Music,
} from "lucide-react";
import { cn } from "@/lib/utils";

export const categoryConfig: Record<
  string,
  { icon: any; colorClass: string; label: string }
> = {
  housing: {
    icon: Home,
    colorClass: "bg-cat-housing/15 text-cat-housing",
    label: "Housing",
  },
  food: {
    icon: Utensils,
    colorClass: "bg-cat-food/15 text-cat-food",
    label: "Food",
  },
  transport: {
    icon: Car,
    colorClass: "bg-cat-transport/15 text-cat-transport",
    label: "Transport",
  },
  shopping: {
    icon: ShoppingBag,
    colorClass: "bg-cat-shopping/15 text-cat-shopping",
    label: "Shopping",
  },
  entertainment: {
    icon: Film,
    colorClass: "bg-cat-entertainment/15 text-cat-entertainment",
    label: "Entertainment",
  },
  health: {
    icon: Heart,
    colorClass: "bg-cat-health/15 text-cat-health",
    label: "Health",
  },
  utilities: {
    icon: Zap,
    colorClass: "bg-cat-utilities/15 text-cat-utilities",
    label: "Utilities",
  },
  other: {
    icon: MoreHorizontal,
    colorClass: "bg-cat-other/15 text-cat-other",
    label: "Other",
  },
  work: {
    icon: Briefcase,
    colorClass: "bg-cat-housing/15 text-cat-housing",
    label: "Work",
  },
  education: {
    icon: GraduationCap,
    colorClass: "bg-cat-transport/15 text-cat-transport",
    label: "Education",
  },
  travel: {
    icon: Plane,
    colorClass: "bg-cat-entertainment/15 text-cat-entertainment",
    label: "Travel",
  },
  gifts: {
    icon: Gift,
    colorClass: "bg-cat-shopping/15 text-cat-shopping",
    label: "Gifts",
  },
  subscriptions: {
    icon: Wifi,
    colorClass: "bg-cat-utilities/15 text-cat-utilities",
    label: "Subscriptions",
  },
  fitness: {
    icon: Dumbbell,
    colorClass: "bg-cat-health/15 text-cat-health",
    label: "Fitness",
  },
  coffee: {
    icon: Coffee,
    colorClass: "bg-cat-food/15 text-cat-food",
    label: "Coffee",
  },
  music: {
    icon: Music,
    colorClass: "bg-cat-entertainment/15 text-cat-entertainment",
    label: "Music",
  },
};

interface CategoryIconProps {
  category: string;
  size?: "sm" | "md" | "lg";
  selected?: boolean;
  onClick?: () => void;
}

const sizes = {
  sm: "w-8 h-8",
  md: "w-10 h-10",
  lg: "w-12 h-12",
};

const iconSizes = {
  sm: "w-4 h-4",
  md: "w-5 h-5",
  lg: "w-6 h-6",
};

const CategoryIcon = ({
  category,
  size = "md",
  selected,
  onClick,
}: CategoryIconProps) => {
  const config = categoryConfig[category] || categoryConfig.other;
  const Icon = config.icon;

  return (
    <button
      onClick={onClick}
      className={cn(
        "rounded-xl flex items-center justify-center transition-all",
        sizes[size],
        config.colorClass,
        selected && "ring-2 ring-primary ring-offset-2 ring-offset-background",
        onClick && "cursor-pointer active:scale-95",
      )}
    >
      <Icon className={iconSizes[size]} />
    </button>
  );
};

export default CategoryIcon;
