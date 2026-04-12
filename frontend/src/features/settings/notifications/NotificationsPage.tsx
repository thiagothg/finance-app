import { ContentSection } from "../components/ContentSection";
import { NotificationsForm } from "./components/NotificationsForm";

export function NotificationPage() {
  return (
    <ContentSection
      title="Notifications"
      desc="Configure how you receive notifications."
    >
      <NotificationsForm />
    </ContentSection>
  );
}
