import { cn } from "@/lib/utils";
import { Card } from "@/components/ui/card";

interface WealthCardProps extends React.ComponentPropsWithoutRef<typeof Card> {
  children: React.ReactNode;
}

export default function WealthCard({
  children,
  className,
  ...props
}: WealthCardProps) {
  return (
    <Card
      className={cn(
        "flex items-center gap-3 md:gap-4 group cursor-default",
        className,
      )}
      {...props}
    >
      {children}
    </Card>
  );
}