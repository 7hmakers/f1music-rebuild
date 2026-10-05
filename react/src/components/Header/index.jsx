import { memo } from "react";
import { Layout } from "antd";
import classnames from "classnames";
import { MenuFoldOutlined, MenuUnfoldOutlined } from "@ant-design/icons";

import { useTitle } from "../../hooks/useTitle";
import useUser from "../../hooks/useUser";

import styles from "./index.module.less";

const Header = ({ sidebarCollapsed, toggleSidebar }) => {
  const title = useTitle();
  const { user } = useUser();

  const headerClass = classnames({
    [styles.header]: true,
    [styles.withSidebar]: !sidebarCollapsed,
  });

  return (
    <Layout.Header className={headerClass}>
      <div className={styles.left}>
        {sidebarCollapsed ? (
          <MenuUnfoldOutlined
            className={styles.trigger}
            onClick={toggleSidebar}
          />
        ) : (
          <MenuFoldOutlined className={styles.trigger} onClick={toggleSidebar} />
        )}
        <span className={styles.title}>{title}</span>
      </div>
      {user && (
        <span className={styles.user}>
          {user.name ? `${user.name}(${user.id})` : user.id}
        </span>
      )}
    </Layout.Header>
  );
};

export default memo(Header);
