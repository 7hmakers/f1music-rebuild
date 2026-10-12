import { useState } from "react";
import {
  Button,
  Form,
  Input,
  Modal,
  Popconfirm,
  Space,
  Table,
  message,
} from "antd";
import { DeleteOutlined, EditOutlined } from "@ant-design/icons";
import { timeFilters, timeIdToText } from "config";
import {
  useEditUpload,
  useMyUploads,
  useWithdrawUpload,
} from "services/upload";

import { TimeSelector } from "components";

const FormItem = Form.Item;

const ViewUploads = () => {
  const { data, isLoading } = useMyUploads();
  const editUpload = useEditUpload();
  const withdrawUpload = useWithdrawUpload();

  const [form] = Form.useForm();
  const [modalVisible, setModalVisible] = useState(false);

  const openEdit = (row) => {
    form.setFieldsValue({
      id: row.id,
      time: row.playtime,
      name: row.name,
      origin: row.origin,
    });
    setModalVisible(true);
  };

  const handleSave = () =>
    form
      .validateFields()
      .then((values) => editUpload.trigger(values))
      .then(() => {
        message.success("修改成功");
        setModalVisible(false);
      })
      .catch(() => {});

  const handleWithdraw = (id) =>
    withdrawUpload
      .trigger(id)
      .then(() => message.success("已撤销上传"))
      .catch(() => {});

  const columns = [
    {
      dataIndex: "playtime",
      title: "时段",
      width: 70,
      render: (text) => timeIdToText[text],
      filters: timeFilters,
      onFilter: (value, record) => record.playtime === value,
    },
    { dataIndex: "name", title: "曲名", width: 200 },
    { dataIndex: "origin", title: "来源", width: 150 },
    {
      title: "操作",
      width: 160,
      render: (_, row) => (
        <Space>
          <Button
            type="primary"
            icon={<EditOutlined />}
            onClick={() => openEdit(row)}
          >
            编辑
          </Button>
          <Popconfirm
            title="确定撤销该上传吗？撤销后无法恢复"
            okText="撤销"
            okButtonProps={{ danger: true }}
            cancelText="取消"
            onConfirm={() => handleWithdraw(row.id)}
          >
            <Button danger type="primary" icon={<DeleteOutlined />}>
              撤销
            </Button>
          </Popconfirm>
        </Space>
      ),
    },
  ];

  return (
    <div>
      <Table
        dataSource={data}
        columns={columns}
        loading={isLoading}
        pagination={false}
        rowKey="id"
        scroll={{ x: 600 }}
      />
      <Modal
        forceRender
        centered
        open={modalVisible}
        confirmLoading={editUpload.isMutating}
        okText="保存"
        title="编辑曲目"
        onCancel={() => setModalVisible(false)}
        onOk={handleSave}
      >
        <Form form={form} labelCol={{ span: 3 }}>
          <FormItem hidden noStyle name="id">
            <Input type="hidden" />
          </FormItem>
          <FormItem
            label="时段"
            name="time"
            rules={[{ required: true, message: "请选择时段" }]}
          >
            <TimeSelector style={{ width: "120px" }} />
          </FormItem>
          <FormItem
            label="曲名"
            name="name"
            rules={[{ required: true, message: "请填写曲名" }]}
          >
            <Input
              placeholder="曲名"
              maxLength={100}
              onPressEnter={handleSave}
            />
          </FormItem>
          <FormItem label="来源" name="origin">
            <Input
              placeholder="来源"
              maxLength={200}
              onPressEnter={handleSave}
            />
          </FormItem>
        </Form>
      </Modal>
    </div>
  );
};

export default ViewUploads;
