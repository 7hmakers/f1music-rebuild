import { Link, useNavigate, useSearchParams } from "react-router-dom";
import { Button, Form, Input, message } from "antd";
import {
  ArrowLeftOutlined,
  IdcardOutlined,
  LockOutlined,
  UserOutlined,
} from "@ant-design/icons";
import Title from "hooks/useTitle";
import { useLogin } from "services/app";
import useUser from "hooks/useUser";

import styles from "./login.module.less";

const FormItem = Form.Item;

const Login = () => {
  const login = useLogin();
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();
  const { mutate } = useUser();

  const handleSubmit = (values) =>
    login
      .trigger(values)
      .then(() => {
        mutate();
        message.success("登录成功", 3);
        const redirect = searchParams.get("redirect");
        const redirectUrlValid = redirect && redirect.startsWith("/");
        const redirectUrl = redirectUrlValid ? redirect : "/";
        if (redirectUrl.startsWith("/manage")) {
          window.location.href = redirectUrl;
        } else {
          navigate(redirectUrl);
        }
      })
      .catch(() => {});

  return (
    <>
      <Title>登录</Title>
      <div className={styles.login}>
        <div style={{ width: "300px", margin: "auto" }}>
          <div className={styles.title}>
            <b>登录</b>
          </div>
          <Form onFinish={handleSubmit}>
            <FormItem
              name="name"
              validateTrigger={["onChange", "onBlur"]}
              rules={[{ required: true, message: "请输入姓名" }]}
            >
              <Input
                placeholder="姓名"
                prefix={<UserOutlined className={styles.inputIcon} />}
              />
            </FormItem>
            <FormItem
              name="stuId"
              validateTrigger={["onChange", "onBlur"]}
              rules={[
                { required: true, message: "请输入学号" },
                {
                  len: 11,
                  message: "学号应为11位",
                  validateTrigger: "onBlur",
                },
              ]}
            >
              <Input
                placeholder="学号"
                prefix={<IdcardOutlined className={styles.inputIcon} />}
              />
            </FormItem>
            <FormItem
              name="password"
              rules={[
                { required: true, message: "请输入密码" },
                { pattern: /^\d{6}$/, message: "密码应为6位数字" },
                {
                  pattern: /^(?!123456$).*$/,
                  message: "为保证投票质量禁止使用初始密码登录,请更改密码",
                },
              ]}
            >
              <Input.Password
                placeholder="一卡通密码（6位数字）"
                maxLength={6}
                prefix={<LockOutlined className={styles.inputIcon} />}
              />
            </FormItem>
            <FormItem>
              <Button
                block
                type="primary"
                loading={login.isMutating}
                htmlType="submit"
              >
                登录
              </Button>
            </FormItem>
          </Form>
          <Link to="/" style={{ fontSize: "13px", color: "#777" }}>
            <ArrowLeftOutlined /> 返回首页
          </Link>
        </div>
      </div>
    </>
  );
};

export default Login;
