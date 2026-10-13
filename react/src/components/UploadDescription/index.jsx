import { Link } from "react-router-dom";
import { ArrowRightOutlined } from "@ant-design/icons";
import { Spin } from "antd";
import MarkdownContent from "components/MarkdownContent";
import { useContent } from "services/content";

const UploadDescription = () => {
  const { data, isLoading } = useContent();

  return (
    <Spin spinning={isLoading}>
      <MarkdownContent html={data?.["home.upload"]} />
      <Link to="/upload" className="redirect">
        <ArrowRightOutlined /> 前往上传
      </Link>
    </Spin>
  );
};

export default UploadDescription;
