import { request } from "utils/admin";
import { api } from "utils/admin/config";
import useSWR from "swr";
import useSWRMutation from "swr/mutation";

const { content, contentSource } = api;

export const useContentSource = () =>
  useSWR(contentSource, () =>
    request({
      url: contentSource,
      method: "get",
    }).then((data) => data.content)
  );

export const useUpdateContent = () =>
  useSWRMutation(content, (_, { arg }) =>
    request({
      url: content,
      method: "put",
      data: arg,
    }).then((data) => data.content)
  );
