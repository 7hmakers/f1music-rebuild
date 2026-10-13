import { Link } from "react-router-dom";
import { ArrowRightOutlined } from "@ant-design/icons";
import { Spin } from "antd";
import MarkdownContent from "components/MarkdownContent";
import { useContent } from "services/content";

const VoteDescription = () => {
  const { data, isLoading } = useContent();

  return (
    <Spin spinning={isLoading}>
      <MarkdownContent html={data?.["home.vote"]} />
      <Link to="/vote/1" className="redirect">
        <ArrowRightOutlined /> 前往投票
      </Link>
    </Spin>
  );
};

export default VoteDescription;
