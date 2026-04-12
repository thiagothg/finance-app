// src/context/AppProviders.tsx
import { DirectionProvider } from "./direction-provider";
import { FontProvider } from "./font-provider";
import { ThemeProvider } from "./theme-provider";

interface AppProvidersProps {
  children: React.ReactNode;
}

export function AppProviders({ children }: AppProvidersProps) {
  return (
    <ThemeProvider>
      <DirectionProvider>
        <FontProvider>
          {children}
        </FontProvider>
      </DirectionProvider>
    </ThemeProvider>
  );
}
