import styles from "./index.module.less";

const MarkdownContent = ({ html }) => (
  <div
    className={styles.markdown}
    // 内容仅由管理员在后台编辑,并由服务端以 html_input=strip 渲染后返回
    dangerouslySetInnerHTML={{ __html: html ?? "" }}
  />
);

export default MarkdownContent;
