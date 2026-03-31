import { ArrowLeft } from "lucide-react";
import { useNavigate } from "react-router-dom";
import { cn } from "@/lib/utils";

interface ScreenLayoutProps {
  title: string;
  leftAction?: React.ReactNode;
  rightAction?: React.ReactNode;
  children: React.ReactNode;
  className?: string;
}

export default function ScreenLayout({
  title,
  leftAction,
  rightAction,
  children,
  className,
}: ScreenLayoutProps) {
  const navigate = useNavigate();

  const defaultLeftAction = (
    <button
      onClick={() => navigate(-1)}
      className="w-9 h-9 rounded-full bg-secondary flex items-center justify-center md:hidden"
    >
      <ArrowLeft className="w-4 h-4 text-foreground" />
    </button>
  );

  return (
    <div className={cn("flex flex-col h-full", className)}>
      <header className="sticky top-0 border-b border-border/70 bg-background/70 px-4 py-4 backdrop-blur-xl sm:px-6 lg:px-8">
        <div className="flex items-center justify-between gap-4">
          <div className="flex items-center gap-3">
            {leftAction ?? defaultLeftAction}
            <h1 className="text-xl font-semibold tracking-[-0.03em] text-foreground">
              {title}
            </h1>
          </div>
          <div className="flex items-center gap-3">{rightAction}</div>
        </div>
      </header>
      <main className="flex-1 overflow-y-auto p-4 sm:px-6 lg:px-8">
        {children}
      </main>
    </div>
  );
}