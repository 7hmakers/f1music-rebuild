import { useState } from "react";
import { Button, Form, Input, Popconfirm, Table, message } from "antd";
import { DeleteOutlined, UserAddOutlined } from "@ant-design/icons";
import Title from "hooks/useTitle";
import { getUser } from "hooks/useUser";
import { useAdmins } from "services/admin/admins";

const Admins = () => {
  const { data, isLoading, add, remove } = useAdmins();
  const user = getUser();
  const [form] = Form.useForm();
  const [submitting, setSubmitting] = useState(false);
  const list = data ?? [];

  const handleAdd = (values) => {
    setSubmitting(true);
    add
      .trigger(values)
      .then(() => {
        message.success("添加成功");
        form.resetFields();
      })
      .catch(() => {})
      .finally(() => setSubmitting(false));
  };

  const handleRemove = (id) =>
    remove
      .trigger(id)
      .then(() => message.success("已移除"))
      .catch(() => {});

  const columns = [
    { dataIndex: "id", title: "学号", width: "180px" },
    {
      dataIndex: "name",
      title: "姓名",
      render: (name) => name || "—",
    },
    {
      title: "操作",
      width: "120px",
      render: (_, row) =>
        user?.id === row.id ? (
          <span style={{ color: "#999" }}>当前账号</span>
        ) : (
          <Popconfirm
            title={`确定移除 ${row.id} 的管理员权限？`}
            onConfirm={() => handleRemove(row.id)}
          >
            <Button danger size="small" icon={<DeleteOutlined />}>
              移除
            </Button>
          </Popconfirm>
        ),
    },
  ];

  return (
    <>
      <Title>管理员</Title>
      <div style={{ fontSize: "14px", color: "#777" }}>
        管理员可管理曲目、审核内容并查看投票数据。输入学号即可将其加入管理员用户组。
      </div>
      <br />
      <Form
        form={form}
        layout="inline"
        onFinish={handleAdd}
        style={{ marginBottom: 16 }}
      >
        <Form.Item
          name="id"
          rules={[
            { required: true, message: "请输入学号" },
            { pattern: /^[0-9A-Za-z]{11}$/, message: "学号应为11位" },
          ]}
        >
          <Input placeholder="学号" style={{ width: 200 }} />
        </Form.Item>
        <Form.Item name="name">
          <Input placeholder="姓名（可选）" style={{ width: 160 }} />
        </Form.Item>
        <Form.Item>
          <Button
            type="primary"
            htmlType="submit"
            icon={<UserAddOutlined />}
            loading={submitting}
          >
            添加管理员
          </Button>
        </Form.Item>
      </Form>
      <Table
        dataSource={list}
        columns={columns}
        rowKey="id"
        loading={isLoading}
        pagination={false}
        scroll={{ x: 400 }}
      />
    </>
  );
};

export default Admins;
