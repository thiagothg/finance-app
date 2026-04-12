import { ContentSection } from "../components/ContentSection";
import { ProfileForm } from "./components/ProfileForm";

export function ProfilePage() {
  return (
    <ContentSection
      title="Profile"
      desc="This is how others will see you on the site."
    >
      <ProfileForm />
    </ContentSection>
  );
}
