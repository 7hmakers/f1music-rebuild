import MarkdownContent from "components/MarkdownContent";
import { useContent } from "services/content";

const TimePeriodDescription = () => {
  const { data } = useContent();

  return <MarkdownContent html={data?.["home.periods"]} />;
};

export default TimePeriodDescription;
