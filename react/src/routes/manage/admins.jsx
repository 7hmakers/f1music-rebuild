import { useState } from "react";
import {
  Button,
  Form,
  Input,
  Popconfirm,
  Space,
  Table,
  Tag,
  message,
} from "antd";
import {
  DeleteOutlined,
  SearchOutlined,
  UserAddOutlined,
} from "@ant-design/icons";
import Title from "hooks/useTitle";
import { getUser } from "hooks/useUser";
import { useUsers } from "services/admin/admins";

const renderRole = (permission) => {
  if (permission >= 20) {
    return <Tag color="blue">管理员</Tag>;
  }
  if (permission >= 10) {
    return <Tag color="green">审核员</Tag>;
  }
  return <Tag>普通用户</Tag>;
};

const Admins = () => {
  const [page, setPage] = useState(1);
  const [size, setSize] = useState(10);
  const { list, total, isLoading, add, remove, search } = useUsers(page, size);
  const user = getUser();
  const [form] = Form.useForm();
  const [searching, setSearching] = useState(false);
  const [adding, setAdding] = useState(false);
  const [result, setResult] = useState(null);

  const handleSearch = (values) => {
    const id = values.id;
    setSearching(true);
    setResult(null);
    search(id)
      .then((res) => {
        const found = res.user;
        if (!found) {
          setResult({ status: "empty", id });
        } else if (found.permission >= 20) {
          setResult({ status: "already", user: found });
        } else {
          setResult({ status: "found", user: found });
        }
      })
      .catch((e) => message.error(e.message))
      .finally(() => setSearching(false));
  };

  const handleAdd = (id) => {
    setAdding(true);
    add
      .trigger({ id })
      .then(() => {
        message.success("添加成功");
        setResult(null);
        form.resetFields();
      })
      .catch((e) => message.error(e.message))
      .finally(() => setAdding(false));
  };

  const handleRemove = (id) =>
    remove
      .trigger(id)
      .then(() => message.success("已移除"))
      .catch((e) => message.error(e.message));

  const columns = [
    { dataIndex: "id", title: "学号", width: "150px" },
    { dataIndex: "name", title: "姓名", render: (name) => name || "—" },
    {
      dataIndex: "permission",
      title: "身份",
      width: "110px",
      render: renderRole,
    },
    {
      title: "操作",
      width: "140px",
      render: (_, row) => {
        if (row.permission >= 20) {
          if (user?.id === row.id) {
            return <span style={{ color: "#999" }}>当前账号</span>;
          }
          return (
            <Popconfirm
              title={`确定移除 ${row.id} 的管理员权限？`}
              onConfirm={() => handleRemove(row.id)}
            >
              <Button danger size="small" icon={<DeleteOutlined />}>
                移除
              </Button>
            </Popconfirm>
          );
        }
        return (
          <Button
            size="small"
            icon={<UserAddOutlined />}
            loading={adding}
            onClick={() => handleAdd(row.id)}
          >
            设为管理员
          </Button>
        );
      },
    },
  ];

  return (
    <>
      <Title>管理员</Title>
      <div style={{ fontSize: "14px", color: "#777" }}>
        输入学号搜索用户以加入管理员用户组；若该学号尚无对应用户，可直接添加。
      </div>
      <br />
      <Form
        form={form}
        layout="inline"
        style={{ marginBottom: 12 }}
        onFinish={handleSearch}
      >
        <Form.Item
          name="id"
          rules={[
            { required: true, message: "请输入学号" },
            { pattern: /^[0-9A-Za-z]{11}$/, message: "学号应为11位" },
          ]}
        >
          <Input allowClear placeholder="输入学号" style={{ width: 220 }} />
        </Form.Item>
        <Form.Item>
          <Button
            htmlType="submit"
            icon={<SearchOutlined />}
            loading={searching}
          >
            搜索用户
          </Button>
        </Form.Item>
      </Form>
      {result && (
        <div style={{ marginBottom: 16 }}>
          {result.status === "already" && (
            <span style={{ color: "#999" }}>
              {result.user.name || "该用户"}（{result.user.id}）已是管理员
            </span>
          )}
          {result.status === "found" && (
            <Space>
              <span>
                找到用户：{result.user.name || "（未登记姓名）"}（
                {result.user.id}）
              </span>
              <Button
                type="primary"
                icon={<UserAddOutlined />}
                loading={adding}
                onClick={() => handleAdd(result.user.id)}
              >
                添加管理员
              </Button>
            </Space>
          )}
          {result.status === "empty" && (
            <Space>
              <span>学号 {result.id} 没有对应用户</span>
              <Button
                type="primary"
                icon={<UserAddOutlined />}
                loading={adding}
                onClick={() => handleAdd(result.id)}
              >
                直接添加
              </Button>
            </Space>
          )}
        </div>
      )}
      <Table
        dataSource={list}
        columns={columns}
        rowKey="id"
        loading={isLoading}
        scroll={{ x: 500 }}
        pagination={{
          current: page,
          pageSize: size,
          total,
          showSizeChanger: true,
          pageSizeOptions: [10, 20, 50],
          showTotal: (t) => `共 ${t} 名已注册用户`,
          onChange: (nextPage, nextSize) => {
            setPage(nextPage);
            setSize(nextSize);
          },
        }}
      />
    </>
  );
};

export default Admins;
