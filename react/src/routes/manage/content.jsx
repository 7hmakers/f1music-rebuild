import { useEffect, useState } from "react";
import { Button, Card, Input, Space, Spin, Typography, message } from "antd";
import { SaveOutlined } from "@ant-design/icons";
import Title from "hooks/useTitle";
import { useContentSource, useUpdateContent } from "services/admin/content";

const { Paragraph, Text, Title: AntTitle } = Typography;

const Content = () => {
  const { data, isLoading, mutate } = useContentSource();
  const { trigger, isMutating } = useUpdateContent();
  const [values, setValues] = useState({});
  const [hints, setHints] = useState({});

  const labels = data?.labels ?? {};
  const hintLabels = data?.hintLabels ?? {};

  useEffect(() => {
    setValues(data?.values ?? {});
    setHints(data?.hints ?? {});
  }, [data]);

  const handleValue = (key, value) =>
    setValues((prev) => ({ ...prev, [key]: value }));
  const handleHint = (key, value) =>
    setHints((prev) => ({ ...prev, [key]: value }));

  const handleSave = () => {
    trigger({ content: values, hints })
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
        语法，保存后立即生效（无需重新部署）。也可直接编辑服务器上{" "}
        <Text code>config/content/</Text> 目录下的 <Text code>*.md</Text>
        （正文）与 <Text code>*.txt</Text>（提示）文件。
      </Paragraph>
      <Spin spinning={isLoading}>
        <AntTitle level={5}>说明正文（Markdown）</AntTitle>
        <Space direction="vertical" size="large" style={{ width: "100%" }}>
          {Object.keys(labels).map((key) => (
            <Card key={key} size="small" title={labels[key]}>
              <Input.TextArea
                value={values[key] ?? ""}
                autoSize={{ minRows: 6, maxRows: 24 }}
                onChange={(e) => handleValue(key, e.target.value)}
              />
            </Card>
          ))}
        </Space>
        <AntTitle level={5} style={{ marginTop: 24 }}>
          提示文字（纯文本）
        </AntTitle>
        <Space direction="vertical" size="large" style={{ width: "100%" }}>
          {Object.keys(hintLabels).map((key) => (
            <Card key={key} size="small" title={hintLabels[key]}>
              <Input
                value={hints[key] ?? ""}
                onChange={(e) => handleHint(key, e.target.value)}
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
