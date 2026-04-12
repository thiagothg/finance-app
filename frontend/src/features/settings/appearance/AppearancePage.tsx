import { ContentSection } from "../components/ContentSection";
import { AppearanceForm } from "./components/AppearanceForm";

export function AppearancePage() {
  return (
    <ContentSection
      title="Appearance"
      desc="Customize the appearance of the app. Automatically switch between day
          and night themes."
    >
      <AppearanceForm />
    </ContentSection>
  );
}
