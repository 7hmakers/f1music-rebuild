import { Button, Card, Descriptions, Tag, Typography } from "antd";
import { ReloadOutlined } from "@ant-design/icons";
import Title from "hooks/useTitle";
import { useDebug } from "services/admin/debug";

import styles from "./debug.module.less";

const { Text } = Typography;

const renderValue = (value) => {
  if (value === null || value === undefined || value === "") {
    return <Text type="secondary">—</Text>;
  }
  if (typeof value === "boolean") {
    return value ? <Tag color="green">true</Tag> : <Tag color="red">false</Tag>;
  }
  if (typeof value === "object") {
    return <pre className={styles.json}>{JSON.stringify(value, null, 2)}</pre>;
  }
  return <Text>{String(value)}</Text>;
};

const Section = ({ title, data }) => (
  <Card title={title} size="small" className={styles.card}>
    <Descriptions bordered column={1} size="small">
      {Object.entries(data ?? {}).map(([key, value]) => (
        <Descriptions.Item key={key} label={key}>
          {renderValue(value)}
        </Descriptions.Item>
      ))}
    </Descriptions>
  </Card>
);

const Debug = () => {
  const { data, isLoading, mutate } = useDebug();

  return (
    <>
      <Title>调试</Title>
      <div className={styles.header}>
        <Text type="secondary">
          仅 admin（最高权限）可见。汇总当前可读 / 可修改的配置，用于排查问题。
        </Text>
        <Button
          icon={<ReloadOutlined />}
          loading={isLoading}
          onClick={() => mutate()}
        >
          刷新
        </Button>
      </div>
      <Section title="时间" data={data?.time} />
      <Section title="应用" data={data?.app} />
      <Section title="运行时" data={data?.runtime} />
      <Section title="数据统计" data={data?.counts} />
      <Section title="音乐配置 (music)" data={data?.music} />
      <Section title="完整配置 (已脱敏)" data={data?.config} />
    </>
  );
};

export default Debug;
