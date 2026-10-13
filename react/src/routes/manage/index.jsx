import Title from "hooks/useTitle";
import { Spin } from "antd";
import MarkdownContent from "components/MarkdownContent";
import { useContent } from "services/content";

const Home = () => {
  const { data, isLoading } = useContent();

  return (
    <>
      <Title>首页</Title>
      <Spin spinning={isLoading}>
        <MarkdownContent html={data?.["manage.notice"]} />
      </Spin>
    </>
  );
};

export default Home;
