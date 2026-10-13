import { useEffect, useState } from "react";
import { Button, Card, Input, Space, Spin, Typography, message } from "antd";
import { SaveOutlined } from "@ant-design/icons";
import Title from "hooks/useTitle";
import { useContentSource, useUpdateContent } from "services/admin/content";

const { Paragraph, Text } = Typography;

const Content = () => {
  const { data, isLoading, mutate } = useContentSource();
  const { trigger, isMutating } = useUpdateContent();
  const [values, setValues] = useState({});

  const labels = data?.labels ?? {};

  useEffect(() => {
    setValues(data?.values ?? {});
  }, [data]);

  const handleChange = (key, value) =>
    setValues((prev) => ({ ...prev, [key]: value }));

  const handleSave = () => {
    trigger({ content: values })
      .then(() => {
        message.success("已保存，刷新前台即可看到最新文本");
        mutate();
      })
      .catch((e) => message.error(e.message));
  };

  return (
    <>
      <Title>文本</Title>
      <Paragraph type="secondary">
        正文使用 Markdown
        语法，保存后立即生效（无需重新部署）。也可直接编辑服务器上的{" "}
        <Text code>config/content/{"{key}"}.md</Text> 文件。
      </Paragraph>
      <Spin spinning={isLoading}>
        <Space direction="vertical" size="large" style={{ width: "100%" }}>
          {Object.keys(labels).map((key) => (
            <Card key={key} size="small" title={labels[key]}>
              <Input.TextArea
                value={values[key] ?? ""}
                autoSize={{ minRows: 6, maxRows: 24 }}
                onChange={(e) => handleChange(key, e.target.value)}
              />
            </Card>
          ))}
        </Space>
      </Spin>
      <Button
        type="primary"
        icon={<SaveOutlined />}
        loading={isMutating}
        style={{ marginTop: 16 }}
        onClick={handleSave}
      >
        保存
      </Button>
    </>
  );
};

export default Content;
