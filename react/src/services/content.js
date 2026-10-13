import { api } from "config";
import useSWR from "swr";

import { request } from "utils";

const { content, hints } = api;

// 获取各说明文本渲染后的 HTML(热更新,无需重新部署)
export const useContent = () =>
  useSWR(content, () =>
    request({
      url: content,
      method: "get",
    }).then((data) => data.content ?? {})
  );

// 获取各提示的纯文本(热更新)
export const useHints = () =>
  useSWR(hints, () =>
    request({
      url: hints,
      method: "get",
    }).then((data) => data.hints ?? {})
  );
